<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\SendNotificationDelivery;
use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CrmReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipientResolver,
    ) {}

    public function dispatchDueOccurrences(
        ?CarbonInterface $at = null,
        ?int $occurrenceId = null,
    ): int {
        if (! config('crm_notifications.enabled')) {
            return 0;
        }

        $now = ($at ?? now())->copy();
        $dispatched = 0;
        $query = NotificationOccurrence::query()
            ->pending()
            ->where('trigger_at', '<=', $now);

        if ($occurrenceId !== null) {
            $query->whereKey($occurrenceId);
        }

        $query->orderBy('id')
            ->chunkById(100, function ($occurrences) use ($now, &$dispatched): void {
                foreach ($occurrences as $occurrence) {
                    $dispatched += $this->dispatchOccurrence((int) $occurrence->getKey(), $now);
                }
            });

        return $dispatched;
    }

    public function queueDueDeliveries(
        ?CarbonInterface $at = null,
        ?int $occurrenceId = null,
    ): int {
        $now = ($at ?? now())->copy();
        $queued = 0;
        $staleQuery = NotificationDelivery::query()
            ->where('status', NotificationDelivery::STATUS_PROCESSING)
            ->where('claim_type', 'delivery')
            ->where('processing_started_at', '<=', $now->copy()->subMinutes(20));

        if ($occurrenceId !== null) {
            $staleQuery->where('notification_occurrence_id', $occurrenceId);
        }

        $staleQuery->update([
            'status' => NotificationDelivery::STATUS_QUEUED,
            'claim_token' => null,
            'claim_type' => null,
            'processing_started_at' => null,
        ]);

        $deliveryQuery = NotificationDelivery::query()
            ->where('status', NotificationDelivery::STATUS_QUEUED)
            ->where(function ($query) use ($now): void {
                $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', $now);
            });

        if ($occurrenceId !== null) {
            $deliveryQuery->where('notification_occurrence_id', $occurrenceId);
        }

        $deliveryQuery->orderBy('id')
            ->chunkById(100, function ($deliveries) use ($now, &$queued): void {
                foreach ($deliveries as $delivery) {
                    $claimToken = (string) Str::uuid();
                    $claimed = NotificationDelivery::query()
                        ->whereKey($delivery->getKey())
                        ->where('status', NotificationDelivery::STATUS_QUEUED)
                        ->update([
                            'status' => NotificationDelivery::STATUS_PROCESSING,
                            'claim_token' => $claimToken,
                            'claim_type' => 'delivery',
                            'processing_started_at' => $now,
                        ]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    try {
                        SendNotificationDelivery::dispatch(
                            (int) $delivery->getKey(),
                            $claimToken,
                        );
                    } catch (Throwable $exception) {
                        NotificationDelivery::query()
                            ->whereKey($delivery->getKey())
                            ->where('claim_token', $claimToken)
                            ->update([
                                'status' => NotificationDelivery::STATUS_QUEUED,
                                'claim_token' => null,
                                'claim_type' => null,
                                'processing_started_at' => null,
                            ]);

                        throw $exception;
                    }

                    $queued++;
                }
            });

        return $queued;
    }

    private function dispatchOccurrence(int $occurrenceId, CarbonInterface $now): int
    {
        return DB::transaction(function () use ($occurrenceId, $now): int {
            $occurrence = NotificationOccurrence::query()
                ->with(['rule.channels', 'recipient'])
                ->lockForUpdate()
                ->find($occurrenceId);

            if (! $occurrence || $occurrence->status !== NotificationOccurrence::STATUS_PENDING) {
                return 0;
            }

            $recipient = User::query()
                ->lockForUpdate()
                ->find($occurrence->recipient_user_id);
            $source = $this->resolveSource($occurrence);
            if (! $recipient?->is_active || ! $source || ! $this->recipientResolver->canReceive($recipient, $source)) {
                $occurrence->update([
                    'status' => NotificationOccurrence::STATUS_CANCELED,
                    'canceled_at' => $now,
                ]);

                return 0;
            }

            $preference = NotificationPreference::query()->firstOrCreate(
                ['user_id' => $recipient->getKey()],
            );
            $preference = NotificationPreference::query()
                ->where('user_id', $recipient->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $channels = isset($occurrence->payload['test_channel'])
                ? [(string) $occurrence->payload['test_channel']]
                : $occurrence->rule->channels->pluck('channel')->all();

            foreach ($channels as $channel) {
                $this->prepareDelivery($occurrence, $preference, $channel, $now);
            }

            $occurrence->update([
                'status' => NotificationOccurrence::STATUS_DISPATCHED,
                'dispatched_at' => $now,
            ]);

            return 1;
        });
    }

    private function prepareDelivery(
        NotificationOccurrence $occurrence,
        NotificationPreference $preference,
        string $channel,
        CarbonInterface $now,
    ): void {
        $recipient = $occurrence->recipient;
        $destination = $this->maskedDestination($channel, $recipient?->email, $recipient?->mobile_phone);
        $status = NotificationDelivery::STATUS_QUEUED;
        $error = null;
        $scheduledAt = $now;

        if (! (bool) config("crm_notifications.channels.$channel", false)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'Channel is disabled by system configuration.';
        } elseif (! $preference->channelEnabled($channel)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'Channel is disabled in user preferences.';
        } elseif ($channel !== 'database' && ! $preference->allowsPriority($occurrence->priority)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'Notification priority is below the user threshold.';
        } elseif ($channel === 'mail' && empty($recipient?->email)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'The user has no email address.';
        } elseif (in_array($channel, ['sms', 'whatsapp'], true) && empty($recipient?->mobile_phone)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'The user has no E.164 mobile number.';
        } elseif ($channel === 'whatsapp' && $recipient?->whatsapp_opt_in_at === null) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'The user has not opted in to WhatsApp notifications.';
        } elseif ($channel !== 'database' && $this->rateLimitExceeded($occurrence, $channel, $now)) {
            $status = NotificationDelivery::STATUS_SUPPRESSED;
            $error = 'Per-user channel rate limit reached.';
        } elseif ($channel !== 'database' && $occurrence->priority !== 'urgent'
            && $preference->isQuietAt($now, $recipient->timezone ?: 'UTC')) {
            $scheduledAt = $this->quietHoursEnd($preference, $now, $recipient->timezone ?: 'UTC');
        }

        if ($channel === 'mail' && $status === NotificationDelivery::STATUS_QUEUED
            && $preference->daily_email_digest && $occurrence->priority !== 'urgent') {
            $status = NotificationDelivery::STATUS_DIGEST_PENDING;
            $scheduledAt = null;
        }

        $delivery = NotificationDelivery::query()->firstOrCreate([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => $channel,
        ], [
            'destination' => $destination,
            'status' => $status,
            'last_error' => $error,
            'scheduled_at' => $scheduledAt,
        ]);

        if ($channel === 'database' && $delivery->wasRecentlyCreated
            && $status === NotificationDelivery::STATUS_QUEUED) {
            $notification = new CrmReminderNotification($occurrence);
            $notification->id = (string) Str::uuid();
            $recipient->notifyNow($notification);
            $occurrence->update(['notification_id' => $notification->id]);
            $delivery->update([
                'status' => NotificationDelivery::STATUS_DELIVERED,
                'sent_at' => $now,
                'delivered_at' => $now,
            ]);
        }
    }

    private function resolveSource(NotificationOccurrence $occurrence): Lead|CalendarEvent|CollectionCase|User|null
    {
        return match ($occurrence->source_kind) {
            'lead_followup' => Lead::query()->find($occurrence->source_id),
            'calendar_event' => CalendarEvent::query()->find($occurrence->source_id),
            'collection_case' => CollectionCase::query()->find($occurrence->source_id),
            'user' => User::query()->find($occurrence->source_id),
            default => null,
        };
    }

    private function rateLimitExceeded(
        NotificationOccurrence $occurrence,
        string $channel,
        CarbonInterface $now,
    ): bool {
        $limit = max(1, (int) config('crm_notifications.max_deliveries_per_user_per_hour', 30));

        return NotificationDelivery::query()
            ->where('channel', $channel)
            ->whereIn('status', [
                NotificationDelivery::STATUS_QUEUED,
                NotificationDelivery::STATUS_DIGEST_PENDING,
                NotificationDelivery::STATUS_PROCESSING,
                NotificationDelivery::STATUS_SENT,
                NotificationDelivery::STATUS_DELIVERED,
            ])
            ->where('created_at', '>=', $now->copy()->subHour())
            ->whereHas('occurrence', fn ($query) => $query
                ->where('recipient_user_id', $occurrence->recipient_user_id))
            ->count() >= $limit;
    }

    private function quietHoursEnd(
        NotificationPreference $preference,
        CarbonInterface $at,
        string $timezone,
    ): CarbonInterface {
        $local = $at->copy()->setTimezone($timezone);
        [$hour, $minute, $second] = array_map('intval', explode(':', (string) $preference->quiet_hours_end));
        $end = $local->copy()->setTime($hour, $minute, $second);

        if ($end->lessThanOrEqualTo($local)) {
            $end->addDay();
        }

        return $end->setTimezone(config('app.timezone', 'UTC'));
    }

    private function maskedDestination(string $channel, ?string $email, ?string $phone): ?string
    {
        if ($channel === 'mail' && $email) {
            [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

            return mb_substr($name, 0, 1).'***@'.$domain;
        }

        if (in_array($channel, ['sms', 'whatsapp'], true) && $phone) {
            return '***'.mb_substr($phone, -4);
        }

        return $channel === 'push' ? 'browser' : null;
    }
}

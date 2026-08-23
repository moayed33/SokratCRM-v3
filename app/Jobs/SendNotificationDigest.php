<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CrmNotificationDigestMail;
use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifications\NotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNotificationDigest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /** @param list<int> $deliveryIds */
    public function __construct(
        public readonly int $userId,
        public readonly array $deliveryIds,
        public readonly string $claimToken,
    ) {}

    public function handle(NotificationRecipientResolver $recipientResolver): void
    {
        $user = User::query()->find($this->userId);
        $preference = NotificationPreference::query()->where('user_id', $this->userId)->first();
        $deliveries = NotificationDelivery::query()
            ->with('occurrence')
            ->whereKey($this->deliveryIds)
            ->where('status', NotificationDelivery::STATUS_PROCESSING)
            ->where('claim_token', $this->claimToken)
            ->get();

        if (! (bool) config('crm_notifications.channels.mail')
            || ! $user?->is_active || ! $user->email
            || ! $preference?->mail_enabled || ! $preference->daily_email_digest) {
            $this->suppress($deliveries->modelKeys(), 'Daily digest recipient or preference is unavailable.');

            return;
        }

        $authorized = $deliveries->filter(function (NotificationDelivery $delivery) use (
            $user,
            $recipientResolver,
        ): bool {
            $occurrence = $delivery->occurrence;
            $source = $occurrence
                ? $this->resolveSource($occurrence->source_kind, $occurrence->source_id)
                : null;
            $allowed = $occurrence?->status === 'dispatched'
                && $source !== null
                && $recipientResolver->canReceive($user, $source);

            if (! $allowed) {
                $this->suppress(
                    [$delivery->getKey()],
                    'Recipient or source access is no longer available.',
                );
            }

            return $allowed;
        });
        $payloads = $authorized->pluck('occurrence.payload')
            ->filter('is_array')
            ->values()
            ->all();

        if ($payloads === []) {
            return;
        }

        Mail::to($user->email)->send(new CrmNotificationDigestMail(
            $payloads,
            in_array($user->locale, ['ar', 'en'], true) ? $user->locale : 'ar',
        ));

        NotificationDelivery::query()
            ->whereKey($authorized->modelKeys())
            ->where('claim_token', $this->claimToken)
            ->update([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'last_error' => null,
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);
        $preference->update(['last_digest_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::query()
            ->whereKey($this->deliveryIds)
            ->where('claim_token', $this->claimToken)
            ->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'last_error' => mb_substr($exception?->getMessage() ?? 'Digest delivery failed.', 0, 4000),
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);
    }

    /** @param list<int|string> $deliveryIds */
    private function suppress(array $deliveryIds, string $reason): void
    {
        NotificationDelivery::query()
            ->whereKey($deliveryIds)
            ->where('claim_token', $this->claimToken)
            ->update([
                'status' => NotificationDelivery::STATUS_SUPPRESSED,
                'last_error' => $reason,
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);
    }

    private function resolveSource(string $kind, int $id): Lead|CalendarEvent|User|null
    {
        return match ($kind) {
            'lead_followup' => Lead::query()->find($id),
            'calendar_event' => CalendarEvent::query()->find($id),
            'user' => User::query()->find($id),
            default => null,
        };
    }
}

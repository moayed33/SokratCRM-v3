<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationRule;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReminderPlanner
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipientResolver,
        private readonly NotificationRuleMatcher $matcher,
        private readonly NotificationPayloadFactory $payloadFactory,
    ) {}

    public function planScheduled(?CarbonInterface $at = null): int
    {
        if (! config('crm_notifications.enabled')) {
            return 0;
        }

        $now = ($at ?? now())->copy()->startOfMinute();
        $rules = NotificationRule::query()
            ->with(['channels', 'recipients'])
            ->enabled()
            ->whereIn('event_key', [
                NotificationRule::EVENT_FOLLOWUP_DUE,
                NotificationRule::EVENT_FOLLOWUP_OVERDUE,
                NotificationRule::EVENT_CALENDAR_DUE,
                NotificationRule::EVENT_COLLECTION_DUE,
            ])
            ->get();
        $created = 0;

        foreach ($rules as $rule) {
            $created += match ($rule->event_key) {
                NotificationRule::EVENT_FOLLOWUP_DUE => $this->planFollowupsDue($rule, $now),
                NotificationRule::EVENT_FOLLOWUP_OVERDUE => $this->planFollowupsOverdue($rule, $now),
                NotificationRule::EVENT_CALENDAR_DUE => $this->planCalendarDue($rule, $now),
                NotificationRule::EVENT_COLLECTION_DUE => $this->planCollectionsDue($rule, $now),
                default => 0,
            };
        }

        return $created;
    }

    public function planImmediate(
        string $eventKey,
        Lead|CalendarEvent|CollectionCase $source,
        ?CarbonInterface $dueAt = null,
    ): int {
        if (! config('crm_notifications.enabled')) {
            return 0;
        }

        $rules = NotificationRule::query()
            ->with(['channels', 'recipients'])
            ->enabled()
            ->where('event_key', $eventKey)
            ->get();
        $triggerAt = now()->startOfMinute();
        $created = 0;

        foreach ($rules as $rule) {
            if (! $this->matcher->matches($rule, $source)) {
                continue;
            }

            foreach ($this->recipientResolver->resolve($rule, $source) as $recipient) {
                $created += $this->createOccurrence(
                    $rule,
                    $source,
                    $recipient,
                    $dueAt,
                    $triggerAt,
                );
            }
        }

        return $created;
    }

    public function cancelOutstanding(string $sourceKind, int $sourceId): int
    {
        return DB::transaction(function () use ($sourceKind, $sourceId): int {
            $deliveryStatuses = [
                NotificationDelivery::STATUS_QUEUED,
                NotificationDelivery::STATUS_DIGEST_PENDING,
                NotificationDelivery::STATUS_PROCESSING,
            ];
            $occurrenceIds = NotificationOccurrence::query()
                ->where('source_kind', $sourceKind)
                ->where('source_id', $sourceId)
                ->where(function ($query) use ($deliveryStatuses): void {
                    $query->pending()->orWhereHas(
                        'deliveries',
                        fn ($deliveryQuery) => $deliveryQuery->whereIn('status', $deliveryStatuses),
                    );
                })
                ->lockForUpdate()
                ->pluck('id');

            if ($occurrenceIds->isEmpty()) {
                return 0;
            }

            NotificationDelivery::query()
                ->whereIn('notification_occurrence_id', $occurrenceIds)
                ->whereIn('status', $deliveryStatuses)
                ->update([
                    'status' => NotificationDelivery::STATUS_SUPPRESSED,
                    'last_error' => 'The source changed before delivery.',
                    'claim_token' => null,
                    'claim_type' => null,
                    'processing_started_at' => null,
                ]);

            return NotificationOccurrence::query()
                ->whereKey($occurrenceIds)
                ->update([
                    'status' => NotificationOccurrence::STATUS_CANCELED,
                    'canceled_at' => now(),
                ]);
        });
    }

    private function planFollowupsDue(NotificationRule $rule, CarbonInterface $now): int
    {
        $offset = max(0, $rule->trigger_offset_minutes);
        $lookback = max(1, (int) config('crm_notifications.planner_lookback_hours', 24));
        $created = 0;

        Lead::query()
            ->with(['assignedUser.groups.permissions', 'creator.groups.permissions', 'status.stage'])
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [
                $now->copy()->subHours($lookback),
                $now->copy()->addMinutes($offset),
            ])
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($rule, $now, $offset, &$created): void {
                foreach ($leads as $lead) {
                    if (! $this->matcher->matches($rule, $lead)) {
                        continue;
                    }

                    $dueAt = $lead->next_follow_up_at;
                    $triggerAt = $dueAt->copy()->subMinutes($offset)->startOfMinute();
                    if ($triggerAt->isAfter($now)) {
                        continue;
                    }

                    foreach ($this->recipientResolver->resolve($rule, $lead) as $recipient) {
                        $created += $this->createOccurrence($rule, $lead, $recipient, $dueAt, $triggerAt);
                    }
                }
            });

        return $created;
    }

    private function planFollowupsOverdue(NotificationRule $rule, CarbonInterface $now): int
    {
        $delay = max(0, (int) ($rule->escalation_after_minutes ?? 0));
        $lookback = max(1, (int) config('crm_notifications.planner_lookback_hours', 24));
        $created = 0;

        Lead::query()
            ->with(['assignedUser.groups.permissions', 'creator.groups.permissions', 'status.stage'])
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [
                $now->copy()->subMinutes($delay)->subHours($lookback),
                $now->copy()->subMinutes($delay),
            ])
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($rule, $now, $delay, &$created): void {
                foreach ($leads as $lead) {
                    if (! $this->matcher->matches($rule, $lead)) {
                        continue;
                    }

                    $dueAt = $lead->next_follow_up_at;
                    $triggerAt = $dueAt->copy()->addMinutes($delay)->startOfMinute();
                    if ($triggerAt->isAfter($now)) {
                        continue;
                    }

                    foreach ($this->recipientResolver->resolve($rule, $lead) as $recipient) {
                        $created += $this->createOccurrence($rule, $lead, $recipient, $dueAt, $triggerAt);
                    }
                }
            });

        return $created;
    }

    private function planCalendarDue(NotificationRule $rule, CarbonInterface $now): int
    {
        $lookback = max(1, (int) config('crm_notifications.planner_lookback_hours', 24));
        $planningHorizonMinutes = max(
            1440,
            max(0, (int) $rule->trigger_offset_minutes),
        );
        $created = 0;

        CalendarEvent::query()
            ->with(['user.groups.permissions', 'lead.status.stage'])
            ->where('status', 'scheduled')
            ->whereBetween('start_time', [
                $now->copy()->subHours($lookback),
                $now->copy()->addMinutes($planningHorizonMinutes),
            ])
            ->orderBy('id')
            ->chunkById(200, function ($events) use ($rule, $now, &$created): void {
                foreach ($events as $event) {
                    if (! $this->matcher->matches($rule, $event)) {
                        continue;
                    }

                    $dueAt = $event->start_time;
                    $offset = max(0, (int) ($event->reminder_minutes_before ?? $rule->trigger_offset_minutes));
                    $triggerAt = $dueAt->copy()->subMinutes($offset)->startOfMinute();
                    if ($triggerAt->isAfter($now)) {
                        continue;
                    }

                    foreach ($this->recipientResolver->resolve($rule, $event) as $recipient) {
                        $created += $this->createOccurrence($rule, $event, $recipient, $dueAt, $triggerAt);
                    }
                }
            });

        return $created;
    }

    private function planCollectionsDue(NotificationRule $rule, CarbonInterface $now): int
    {
        $offset = max(0, $rule->trigger_offset_minutes);
        $lookback = max(1, (int) config('crm_notifications.planner_lookback_hours', 24));
        $created = 0;

        CollectionCase::query()
            ->with(['assignedCollector.groups.permissions', 'createdBy.groups.permissions', 'lead.status.stage'])
            ->open()
            ->whereNotNull('assigned_collector_user_id')
            ->whereBetween('due_at', [
                $now->copy()->subHours($lookback),
                $now->copy()->addMinutes($offset),
            ])
            ->orderBy('id')
            ->chunkById(200, function ($cases) use ($rule, $now, $offset, &$created): void {
                foreach ($cases as $collectionCase) {
                    $triggerAt = $collectionCase->due_at->copy()->subMinutes($offset)->startOfMinute();
                    if ($triggerAt->isAfter($now) || ! $this->matcher->matches($rule, $collectionCase)) {
                        continue;
                    }

                    foreach ($this->recipientResolver->resolve($rule, $collectionCase) as $recipient) {
                        $created += $this->createOccurrence($rule, $collectionCase, $recipient, $collectionCase->due_at, $triggerAt);
                    }
                }
            });

        return $created;
    }

    private function createOccurrence(
        NotificationRule $rule,
        Lead|CalendarEvent|CollectionCase $source,
        User $recipient,
        ?CarbonInterface $dueAt,
        CarbonInterface $triggerAt,
    ): int {
        $sourceKind = match (true) {
            $source instanceof Lead => 'lead_followup',
            $source instanceof CollectionCase => 'collection_case',
            default => 'calendar_event',
        };

        $occurrence = NotificationOccurrence::query()->firstOrCreate([
            'notification_rule_id' => $rule->getKey(),
            'source_kind' => $sourceKind,
            'source_id' => $source->getKey(),
            'recipient_user_id' => $recipient->getKey(),
            'trigger_at' => $triggerAt->copy()->startOfMinute(),
        ], [
            'event_key' => $rule->event_key,
            'source_due_at' => $dueAt,
            'priority' => $rule->priority,
            'status' => NotificationOccurrence::STATUS_PENDING,
            'payload' => $this->payloadFactory->make($rule, $source, $recipient, $dueAt),
        ]);

        if (! $occurrence->wasRecentlyCreated
            && $occurrence->status === NotificationOccurrence::STATUS_CANCELED
            && $occurrence->dispatched_at === null) {
            $occurrence->deliveries()->delete();
            $occurrence->update([
                'event_key' => $rule->event_key,
                'source_due_at' => $dueAt,
                'priority' => $rule->priority,
                'status' => NotificationOccurrence::STATUS_PENDING,
                'payload' => $this->payloadFactory->make($rule, $source, $recipient, $dueAt),
                'canceled_at' => null,
            ]);

            return 1;
        }

        return $occurrence->wasRecentlyCreated ? 1 : 0;
    }
}

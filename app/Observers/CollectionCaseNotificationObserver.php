<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CollectionCase;
use App\Models\NotificationRule;
use App\Services\Notifications\ReminderPlanner;
use Illuminate\Support\Facades\DB;

class CollectionCaseNotificationObserver
{
    public function created(CollectionCase $collectionCase): void
    {
        if ($collectionCase->assigned_collector_user_id === null) {
            return;
        }

        $this->planAfterCommit($collectionCase, NotificationRule::EVENT_COLLECTION_ASSIGNED);
    }

    public function updated(CollectionCase $collectionCase): void
    {
        $event = match (true) {
            $collectionCase->wasChanged('status') && $collectionCase->status === CollectionCase::STATUS_COLLECTED => NotificationRule::EVENT_COLLECTION_COMPLETED,
            $collectionCase->wasChanged('assigned_collector_user_id') => NotificationRule::EVENT_COLLECTION_ASSIGNED,
            $collectionCase->wasChanged('due_at') => NotificationRule::EVENT_COLLECTION_RESCHEDULED,
            default => null,
        };

        if ($event !== null) {
            $this->planAfterCommit($collectionCase, $event);
        }
    }

    public function deleted(CollectionCase $collectionCase): void
    {
        app(ReminderPlanner::class)->cancelOutstanding('collection_case', (int) $collectionCase->getKey());
    }

    private function planAfterCommit(CollectionCase $collectionCase, string $event): void
    {
        $caseId = (int) $collectionCase->getKey();
        DB::afterCommit(static function () use ($caseId, $event): void {
            $planner = app(ReminderPlanner::class);
            $planner->cancelOutstanding('collection_case', $caseId);
            $freshCase = CollectionCase::query()->with('lead')->find($caseId);
            if ($freshCase !== null) {
                $planner->planImmediate($event, $freshCase, $freshCase->due_at);
            }
        });
    }
}

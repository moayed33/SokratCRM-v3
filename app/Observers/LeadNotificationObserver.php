<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Models\NotificationRule;
use App\Services\Notifications\ReminderPlanner;
use Illuminate\Support\Facades\DB;

class LeadNotificationObserver
{
    public function updated(Lead $lead): void
    {
        $followUpChanged = $lead->wasChanged('next_follow_up_at');
        $assignmentChanged = $lead->wasChanged('assigned_user_id');
        $hadFollowUp = $lead->getOriginal('next_follow_up_at') !== null;
        $hasFollowUp = $lead->next_follow_up_at !== null;
        $leadId = (int) $lead->getKey();

        if (! $followUpChanged && ! $assignmentChanged) {
            return;
        }

        DB::afterCommit(static function () use (
            $leadId,
            $followUpChanged,
            $assignmentChanged,
            $hadFollowUp,
            $hasFollowUp,
        ): void {
            $planner = app(ReminderPlanner::class);
            $planner->cancelOutstanding('lead_followup', $leadId);
            $freshLead = Lead::query()->find($leadId);

            if (! $freshLead) {
                return;
            }

            if ($followUpChanged && $hadFollowUp && $hasFollowUp) {
                $planner->planImmediate(
                    NotificationRule::EVENT_FOLLOWUP_RESCHEDULED,
                    $freshLead,
                    $freshLead->next_follow_up_at,
                );
            }

            if ($assignmentChanged) {
                $planner->planImmediate(
                    NotificationRule::EVENT_FOLLOWUP_REASSIGNED,
                    $freshLead,
                    $freshLead->next_follow_up_at,
                );
            }
        });
    }

    public function deleted(Lead $lead): void
    {
        app(ReminderPlanner::class)->cancelOutstanding('lead_followup', (int) $lead->getKey());
    }
}

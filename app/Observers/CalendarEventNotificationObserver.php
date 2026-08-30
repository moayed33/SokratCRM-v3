<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Models\NotificationRule;
use App\Services\Notifications\ReminderPlanner;
use Illuminate\Support\Facades\DB;

class CalendarEventNotificationObserver
{
    public function updated(CalendarEvent $event): void
    {
        $eventChanged = $event->wasChanged([
            'title',
            'start_time',
            'end_time',
            'type',
            'user_id',
            'lead_id',
            'reminder_minutes_before',
        ]);
        $statusChanged = $event->wasChanged('status');
        $becameCanceled = $statusChanged && $event->status === 'canceled';
        $eventId = (int) $event->getKey();

        if (! $eventChanged && ! $statusChanged) {
            return;
        }

        DB::afterCommit(static function () use ($eventId, $eventChanged, $becameCanceled): void {
            $planner = app(ReminderPlanner::class);
            $planner->cancelOutstanding('calendar_event', $eventId);
            $freshEvent = CalendarEvent::query()->find($eventId);

            if (! $freshEvent) {
                return;
            }

            if ($becameCanceled) {
                $planner->planImmediate(
                    NotificationRule::EVENT_CALENDAR_CANCELED,
                    $freshEvent,
                    $freshEvent->start_time,
                );

                return;
            }

            if ($eventChanged && $freshEvent->status === 'scheduled') {
                $planner->planImmediate(
                    NotificationRule::EVENT_CALENDAR_UPDATED,
                    $freshEvent,
                    $freshEvent->start_time,
                );
            }
        });
    }

    public function deleted(CalendarEvent $event): void
    {
        app(ReminderPlanner::class)->cancelOutstanding('calendar_event', (int) $event->getKey());
    }
}

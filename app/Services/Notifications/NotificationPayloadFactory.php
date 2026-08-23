<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use Carbon\CarbonInterface;

class NotificationPayloadFactory
{
    /** @return array<string, mixed> */
    public function make(
        NotificationRule $rule,
        Lead|CalendarEvent $source,
        User $recipient,
        ?CarbonInterface $dueAt,
    ): array {
        $locale = in_array($recipient->locale, ['ar', 'en'], true)
            ? $recipient->locale
            : (string) config('app.locale', 'ar');
        $sourceName = $source instanceof Lead ? $source->name : $source->title;
        $time = $dueAt?->copy()->setTimezone($recipient->timezone ?: 'UTC')
            ->format('Y-m-d H:i');

        return [
            'event_key' => $rule->event_key,
            'priority' => $rule->priority,
            'title' => trans('crm.notification_event_'.$this->keySuffix($rule->event_key), [], $locale),
            'body' => trans('crm.notification_event_body_'.$this->keySuffix($rule->event_key), [
                'name' => $sourceName,
                'time' => $time ?? trans('crm.not_specified', [], $locale),
            ], $locale),
            'source_kind' => $source instanceof Lead ? 'lead_followup' : 'calendar_event',
            'source_id' => (int) $source->getKey(),
            'source_name' => $sourceName,
            'due_at' => $dueAt?->toIso8601String(),
            'action_url' => $source instanceof Lead
                ? route('v2.leads.show', $source, false)
                : route('v2.calendar.index', ['event' => $source->getKey()], false),
            'locale' => $locale,
        ];
    }

    private function keySuffix(string $eventKey): string
    {
        return str_replace('.', '_', $eventKey);
    }
}

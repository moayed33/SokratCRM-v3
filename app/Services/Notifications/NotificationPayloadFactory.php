<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use Carbon\CarbonInterface;

class NotificationPayloadFactory
{
    /** @return array<string, mixed> */
    public function make(
        NotificationRule $rule,
        Lead|CalendarEvent|CollectionCase $source,
        User $recipient,
        ?CarbonInterface $dueAt,
    ): array {
        $locale = in_array($recipient->locale, ['ar', 'en'], true)
            ? $recipient->locale
            : (string) config('app.locale', 'ar');

        $lead = match (true) {
            $source instanceof Lead => $source,
            $source instanceof CalendarEvent => $source->lead,
            $source instanceof CollectionCase => $source->lead,
            default => null,
        };

        $isTask = $source instanceof CalendarEvent && $source->type === 'task';
        $sourceName = $source instanceof CollectionCase
            ? ($source->lead?->name ?? 'Collection #'.$source->getKey())
            : ($source instanceof Lead ? $source->name : $source->title);
        $time = $dueAt?->copy()->setTimezone($recipient->timezone ?: 'UTC')
            ->format('Y-m-d H:i');

        if ($isTask && $rule->event_key === NotificationRule::EVENT_CALENDAR_DUE) {
            $isOverdue = $dueAt !== null && $dueAt->isPast();
            $title = $isOverdue
                ? trans('crm.notification_event_task_overdue', [], $locale)
                : trans('crm.notification_event_task_due', [], $locale);
            $body = $isOverdue
                ? trans('crm.notification_event_body_task_overdue', [
                    'name' => $sourceName,
                    'time' => $time ?? trans('crm.not_specified', [], $locale),
                ], $locale)
                : trans('crm.notification_event_body_task_due', [
                    'name' => $sourceName,
                    'time' => $time ?? trans('crm.not_specified', [], $locale),
                ], $locale);
        } else {
            $title = trans('crm.notification_event_'.$this->keySuffix($rule->event_key), [], $locale);
            $body = trans('crm.notification_event_body_'.$this->keySuffix($rule->event_key), [
                'name' => $sourceName,
                'time' => $time ?? trans('crm.not_specified', [], $locale),
            ], $locale);
        }

        $stage = $lead?->status?->stage;
        $stageName = $stage ? $stage->localizedName($locale) : ($lead ? trans('crm.stage_not_set', [], $locale) : null);

        return [
            'event_key' => $rule->event_key,
            'priority' => $rule->priority,
            'title' => $title,
            'body' => $body,
            'source_kind' => match (true) {
                $source instanceof Lead => 'lead_followup',
                $source instanceof CollectionCase => 'collection_case',
                default => 'calendar_event',
            },
            'source_id' => (int) $source->getKey(),
            'source_name' => $sourceName,
            'lead_id' => $lead ? (int) $lead->getKey() : null,
            'lead_name' => $lead?->name,
            'stage_id' => $stage?->id,
            'stage_name' => $stageName,
            'stage_color' => $stage?->color,
            'stage_icon' => $stage?->icon,
            'due_at' => $dueAt?->toIso8601String(),
            'action_url' => match (true) {
                $source instanceof Lead => route('v2.leads.show', $source, false),
                $source instanceof CollectionCase => route('v2.collections.show', $source, false),
                default => route('v2.calendar.index', ['event' => $source->getKey()], false),
            },
            'locale' => $locale,
        ];
    }

    private function keySuffix(string $eventKey): string
    {
        return str_replace('.', '_', $eventKey);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;

class CalendarSyncService
{
    /**
     * Prepare a standardized payload for external calendar APIs (Google Calendar / Outlook Graph API).
     *
     * @return array<string, mixed>
     */
    public function prepareSyncPayload(CalendarEvent $event): array
    {
        $event->loadMissing(['user', 'lead']);

        $timeZone = config('app.timezone', 'UTC');

        return [
            'summary' => $event->title,
            'description' => $event->description ?? '',
            'start' => [
                'dateTime' => $event->start_time->toIso8601String(),
                'timeZone' => $timeZone,
            ],
            'end' => [
                'dateTime' => $event->end_time->toIso8601String(),
                'timeZone' => $timeZone,
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    [
                        'method' => 'popup',
                        'minutes' => $event->reminder_minutes_before ?? 15,
                    ],
                ],
            ],
            'extendedProperties' => [
                'private' => [
                    'crm_event_id' => (string) $event->id,
                    'crm_user_id' => (string) $event->user_id,
                    'crm_lead_id' => (string) ($event->lead_id ?? ''),
                    'crm_type' => $event->type,
                    'crm_status' => $event->status,
                ],
            ],
            'attendees' => array_filter([
                $event->user && $event->user->email ? [
                    'email' => $event->user->email,
                    'displayName' => $event->user->name,
                ] : null,
            ]),
        ];
    }

    /**
     * Mark a calendar event as synchronized with an external provider.
     */
    public function markAsSynced(CalendarEvent $event, string $syncId, string $provider = 'google'): CalendarEvent
    {
        CrmDatabaseGuard::ensureConnected();

        $event->update([
            'sync_id' => $syncId,
            'provider' => $provider,
            'synced_at' => Carbon::now(),
        ]);

        return $event->refresh();
    }

    /**
     * Simulate pushing a calendar event to an external provider (Google / Outlook).
     *
     * @return array{success: bool, provider: string, sync_id: string, payload: array<string, mixed>}
     */
    public function syncEventToExternal(CalendarEvent $event, string $provider = 'google'): array
    {
        $payload = $this->prepareSyncPayload($event);
        $generatedSyncId = strtolower($provider) . '_evt_' . $event->id . '_' . bin2hex(random_bytes(4));

        $this->markAsSynced($event, $generatedSyncId, $provider);

        return [
            'success' => true,
            'provider' => $provider,
            'sync_id' => $generatedSyncId,
            'payload' => $payload,
        ];
    }

    /**
     * Import an external event payload into CRM calendar_events table.
     *
     * @param array<string, mixed> $externalData
     */
    public function importExternalEvent(array $externalData, User $user): CalendarEvent
    {
        CrmDatabaseGuard::ensureConnected();

        $startTime = Carbon::parse($externalData['start_time'] ?? $externalData['start']['dateTime'] ?? now());
        $endTime = Carbon::parse($externalData['end_time'] ?? $externalData['end']['dateTime'] ?? (clone $startTime)->addHour());

        return CalendarEvent::create([
            'user_id' => $user->id,
            'lead_id' => isset($externalData['lead_id']) ? (int) $externalData['lead_id'] : null,
            'title' => (string) ($externalData['title'] ?? $externalData['summary'] ?? 'حدث خارجي مستورد'),
            'description' => (string) ($externalData['description'] ?? ''),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => (string) ($externalData['type'] ?? 'meeting'),
            'status' => (string) ($externalData['status'] ?? 'scheduled'),
            'sync_id' => (string) ($externalData['sync_id'] ?? $externalData['id'] ?? 'ext_' . bin2hex(random_bytes(4))),
            'provider' => (string) ($externalData['provider'] ?? 'google'),
            'synced_at' => Carbon::now(),
            'reminder_minutes_before' => (int) ($externalData['reminder_minutes_before'] ?? 15),
        ]);
    }
}

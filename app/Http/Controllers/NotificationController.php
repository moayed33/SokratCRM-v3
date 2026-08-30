<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Lead;
use App\Models\NotificationOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'string', 'in:all,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $request->user();
        $query = $user->notifications()
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('notification_occurrences')
                    ->whereColumn('notification_occurrences.notification_id', 'notifications.id')
                    ->whereNotNull('notification_occurrences.dismissed_at');
            });

        if (($validated['filter'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        $paginator = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20));
        $notificationIds = collect($paginator->items())->pluck('id');
        $occurrences = NotificationOccurrence::query()
            ->whereIn('notification_id', $notificationIds)
            ->get()
            ->keyBy('notification_id');

        $locale = in_array($user->locale, ['ar', 'en'], true)
            ? $user->locale
            : (string) config('app.locale', 'ar');

        $directLeadIds = collect();
        $calendarEventIds = collect();
        $collectionCaseIds = collect();

        foreach ($paginator->items() as $notification) {
            $data = $notification->data;
            $occurrence = $occurrences->get($notification->id);
            $sourceKind = (string) ($data['source_kind'] ?? ($occurrence?->source_kind ?? ''));
            $sourceId = isset($data['source_id']) ? (int) $data['source_id'] : ($occurrence ? (int) $occurrence->source_id : null);

            if ($sourceKind === 'collection_case' && $sourceId) {
                $collectionCaseIds->push($sourceId);
            } elseif ($sourceKind === 'calendar_event' && $sourceId) {
                $calendarEventIds->push($sourceId);
            } elseif ($sourceKind === 'lead_followup' && $sourceId) {
                $directLeadIds->push($sourceId);
            } elseif (isset($data['lead_id']) && is_numeric($data['lead_id'])) {
                $directLeadIds->push((int) $data['lead_id']);
            } elseif (isset($occurrence->payload['lead_id']) && is_numeric($occurrence->payload['lead_id'])) {
                $directLeadIds->push((int) $occurrence->payload['lead_id']);
            } elseif (preg_match('#/(?:leads|v2/leads)/(\d+)#', (string) ($data['action_url'] ?? ''), $matches)) {
                $directLeadIds->push((int) $matches[1]);
            }
        }

        $accessibleLeads = collect();

        if ($directLeadIds->isNotEmpty()) {
            $directQuery = Lead::query()->whereIn('id', $directLeadIds->unique());
            if (! $user->isSuperAdmin()) {
                $directQuery->accessibleTo($user);
            }
            foreach ($directQuery->with(['status.stage'])->get() as $lead) {
                $accessibleLeads->put($lead->id, $lead);
            }
        }

        $calendarEventLeadIds = collect();
        if ($calendarEventIds->isNotEmpty()) {
            $eventsQuery = CalendarEvent::query()
                ->whereIn('id', $calendarEventIds->unique());
            if (! $user->isSuperAdmin()) {
                $eventsQuery->accessibleTo($user);
            }
            $accessibleEvents = $eventsQuery->with(['lead.status.stage'])->get();
            foreach ($accessibleEvents as $event) {
                if ($event->lead) {
                    $calendarEventLeadIds->put($event->id, $event->lead_id);
                    $accessibleLeads->put($event->lead_id, $event->lead);
                }
            }
        }

        $collectionCaseLeadIds = collect();
        if ($collectionCaseIds->isNotEmpty()) {
            $casesQuery = CollectionCase::query()
                ->whereIn('id', $collectionCaseIds->unique());
            if (! $user->isSuperAdmin()) {
                $casesQuery->accessibleTo($user);
            }
            $accessibleCases = $casesQuery->with(['lead.status.stage'])->get();
            foreach ($accessibleCases as $case) {
                if ($case->lead) {
                    $collectionCaseLeadIds->put($case->id, $case->lead_id);
                    $accessibleLeads->put($case->lead_id, $case->lead);
                }
            }
        }
        $items = collect($paginator->items())->map(function (DatabaseNotification $notification) use ($occurrences, $calendarEventLeadIds, $collectionCaseLeadIds, $accessibleLeads, $locale): array {
            $data = $notification->data;
            $occurrence = $occurrences->get($notification->id);

            $leadId = null;
            if (isset($data['lead_id']) && is_numeric($data['lead_id'])) {
                $leadId = (int) $data['lead_id'];
            } elseif (isset($occurrence->payload['lead_id']) && is_numeric($occurrence->payload['lead_id'])) {
                $leadId = (int) $occurrence->payload['lead_id'];
            } else {
                $sourceKind = $data['source_kind'] ?? ($occurrence?->source_kind ?? null);
                $sourceId = isset($data['source_id']) ? (int) $data['source_id'] : ($occurrence ? (int) $occurrence->source_id : null);
                if ($sourceKind === 'lead_followup' && $sourceId) {
                    $leadId = $sourceId;
                } elseif ($sourceKind === 'calendar_event' && $sourceId) {
                    $leadId = $calendarEventLeadIds->get($sourceId);
                } elseif ($sourceKind === 'collection_case' && $sourceId) {
                    $leadId = $collectionCaseLeadIds->get($sourceId);
                } elseif (preg_match('#/(?:leads|v2/leads)/(\d+)#', (string) ($data['action_url'] ?? ''), $matches)) {
                    $leadId = (int) $matches[1];
                }
            }

            $lead = $leadId ? $accessibleLeads->get($leadId) : null;
            $stage = $lead?->status?->stage;
            $stageName = null;
            $stageId = null;
            $stageColor = null;
            $stageIcon = null;

            if ($lead !== null) {
                $stageName = $stage ? $stage->localizedName($locale) : trans('crm.stage_not_set', [], $locale);
                $stageId = $stage?->id;
                $stageColor = $stage?->color;
                $stageIcon = $stage?->icon;
            }

            $rawUrl = (string) ($data['action_url'] ?? '');
            $actionUrl = $rawUrl;
            if ($rawUrl !== '') {
                $parsed = parse_url($rawUrl);
                if (! empty($parsed['path'])) {
                    $path = $parsed['path'];
                    $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                    $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
                    $actionUrl = $path.$query.$fragment;
                }
            }

            return [
                'id' => $notification->id,
                'title' => (string) ($data['title'] ?? ''),
                'body' => (string) ($data['body'] ?? ''),
                'priority' => (string) ($data['priority'] ?? 'normal'),
                'event_key' => (string) ($data['event_key'] ?? ''),
                'source_kind' => (string) ($data['source_kind'] ?? ($occurrence?->source_kind ?? '')),
                'source_id' => isset($data['source_id']) ? (int) $data['source_id'] : ($occurrence ? (int) $occurrence->source_id : null),
                'source_name' => (string) ($data['source_name'] ?? ''),
                'lead_id' => $lead ? (int) $lead->getKey() : null,
                'lead_name' => $lead ? (string) $lead->name : null,
                'stage_id' => $stageId,
                'stage_name' => $stageName,
                'stage_color' => $stageColor,
                'stage_icon' => $stageIcon,
                'due_at' => $data['due_at'] ?? null,
                'action_url' => $actionUrl,
                'created_at' => $notification->created_at?->toIso8601String(),
                'read_at' => $notification->read_at?->toIso8601String(),
                'can_snooze' => $occurrence !== null
                    && ! str_starts_with((string) $occurrence->event_key, 'system.'),
            ];
        });
        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('notification_occurrences')
                    ->whereColumn('notification_occurrences.notification_id', 'notifications.id')
                    ->whereNotNull('notification_occurrences.dismissed_at');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        $baseQuery = DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('notification_occurrences')
                    ->whereColumn('notification_occurrences.notification_id', 'notifications.id')
                    ->whereNotNull('notification_occurrences.dismissed_at');
            });
        $counts = (clone $baseQuery)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) AS unread')
            ->first();
        $state = [
            'count' => (int) ($counts->unread ?? 0),
            'total' => (int) ($counts->total ?? 0),
            'latest_id' => (clone $baseQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('id'),
        ];
        $retryMilliseconds = (int) config(
            'crm_notifications.stream_interval_milliseconds',
            2000,
        );

        return response()->stream(function () use ($state, $retryMilliseconds): void {
            echo 'retry: '.$retryMilliseconds."\n";
            echo 'event: notifications'."\n";
            echo 'data: '.json_encode($state, JSON_THROW_ON_ERROR)."\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        $record->markAsRead();

        return response()->json(['success' => true]);
    }

    public function unread(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        $record->forceFill(['read_at' => null])->save();

        return response()->json(['success' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function snooze(Request $request, string $notification): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ]);
        $snoozedUntil = now()
            ->addMinutes((int) $validated['minutes'])
            ->startOfMinute();

        DB::transaction(function () use ($request, $notification, $snoozedUntil): void {
            $record = $this->findNotification($request, $notification);
            $occurrence = NotificationOccurrence::query()
                ->where('notification_id', $record->id)
                ->where('recipient_user_id', $request->user()->getKey())
                ->firstOrFail();

            abort_if(str_starts_with($occurrence->event_key, 'system.'), 422);

            NotificationOccurrence::query()
                ->pending()
                ->where('notification_rule_id', $occurrence->notification_rule_id)
                ->where('source_kind', $occurrence->source_kind)
                ->where('source_id', $occurrence->source_id)
                ->where('recipient_user_id', $occurrence->recipient_user_id)
                ->where('trigger_at', '!=', $snoozedUntil)
                ->update([
                    'status' => NotificationOccurrence::STATUS_CANCELED,
                    'canceled_at' => now(),
                ]);

            NotificationOccurrence::query()->updateOrCreate([
                'notification_rule_id' => $occurrence->notification_rule_id,
                'source_kind' => $occurrence->source_kind,
                'source_id' => $occurrence->source_id,
                'recipient_user_id' => $occurrence->recipient_user_id,
                'trigger_at' => $snoozedUntil,
            ], [
                'event_key' => $occurrence->event_key,
                'source_due_at' => $occurrence->source_due_at,
                'priority' => $occurrence->priority,
                'status' => NotificationOccurrence::STATUS_PENDING,
                'payload' => $occurrence->payload,
                'canceled_at' => null,
                'dispatched_at' => null,
            ]);

            $occurrence->update(['snoozed_until' => $snoozedUntil]);
            $record->markAsRead();
        });

        return response()->json([
            'success' => true,
            'snoozed_until' => $snoozedUntil->toIso8601String(),
        ]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        NotificationOccurrence::query()
            ->where('notification_id', $record->id)
            ->where('recipient_user_id', $request->user()->getKey())
            ->update(['dismissed_at' => now()]);
        $record->markAsRead();

        return response()->json(['success' => true]);
    }

    private function findNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}

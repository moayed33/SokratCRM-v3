<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

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

        $items = collect($paginator->items())->map(function (DatabaseNotification $notification) use ($occurrences): array {
            $data = $notification->data;
            $occurrence = $occurrences->get($notification->id);

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
                'source_name' => (string) ($data['source_name'] ?? ''),
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

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        $record->markAsRead();

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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\User;
use App\Repositories\CalendarEventRepository;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarEventRepository $repository
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        if ($request->wantsJson() || $request->ajax()) {
            return $this->events($request);
        }

        $user = $request->user();

        // Get leads accessible to this user for the modal dropdown selector
        $leads = Lead::query()
            ->accessibleTo($user)
            ->select(['id', 'name', 'company_name', 'phone'])
            ->orderBy('name', 'asc')
            ->limit(300)
            ->get();

        // Get users list if super-admin or has leads.scope.all/group permission
        $assignableUsers = collect();
        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL) || $user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $assignableUsers = User::query()
                ->where('is_active', true)
                ->select(['id', 'name', 'username'])
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $assignableUsers = collect([$user]);
        }

        return view('calendar.index', [
            'leads' => $leads,
            'assignableUsers' => $assignableUsers,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        $user = $request->user();
        $start = $request->query('start');
        $end = $request->query('end');

        $filters = $request->only(['type', 'status', 'user_id', 'lead_id']);

        $events = $this->repository->getEventsForUser(
            $user,
            is_string($start) ? $start : null,
            is_string($end) ? $end : null,
            $filters
        );

        $formattedEvents = $events->map(function (CalendarEvent $event): array {
            $color = match ($event->type) {
                'meeting' => '#3b82f6', // blue
                'call' => '#10b981',    // green
                'task' => '#f59e0b',    // amber/orange
                'reminder' => '#8b5cf6',// purple
                default => '#6b7280',
            };

            if ($event->status === 'completed') {
                $color = '#059669'; // dark green
            } elseif ($event->status === 'canceled') {
                $color = '#ef4444'; // red
            }

            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start_time->toIso8601String(),
                'end' => $event->end_time->toIso8601String(),
                'type' => $event->type,
                'status' => $event->status,
                'user_id' => $event->user_id,
                'user_name' => $event->user?->name,
                'lead_id' => $event->lead_id,
                'lead_name' => $event->lead?->name,
                'lead_company' => $event->lead?->company_name,
                'lead_phone' => $event->lead?->phone,
                'reminder_minutes_before' => $event->reminder_minutes_before ?? 15,
                'sync_id' => $event->sync_id,
                'provider' => $event->provider,
                'synced_at' => $event->synced_at?->toIso8601String(),
                'color' => $color,
                'allDay' => false,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedEvents,
        ]);
    }

    public function show(CalendarEvent $event, Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('view', $event);

        $event->load(['user:id,name', 'lead:id,name,company_name,phone']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_time' => $event->start_time->toIso8601String(),
                'end_time' => $event->end_time->toIso8601String(),
                'type' => $event->type,
                'status' => $event->status,
                'user_id' => $event->user_id,
                'user_name' => $event->user?->name,
                'lead_id' => $event->lead_id,
                'lead_name' => $event->lead?->name,
                'lead_company' => $event->lead?->company_name,
                'reminder_minutes_before' => $event->reminder_minutes_before ?? 15,
                'sync_id' => $event->sync_id,
                'provider' => $event->provider,
                'synced_at' => $event->synced_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('create', CalendarEvent::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'type' => ['required', 'string', 'in:meeting,call,task,reminder'],
            'status' => ['required', 'string', 'in:scheduled,completed,canceled'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        $currentUser = $request->user();

        // Target user: if specified and permitted, use it; otherwise assign to current user
        $targetUserId = $currentUser->id;
        if (! empty($validated['user_id'])) {
            if ($currentUser->isSuperAdmin() || $currentUser->hasPermission(CrmPermission::LEADS_SCOPE_ALL) || $currentUser->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
                $targetUserId = (int) $validated['user_id'];
            }
        }
        $validated['user_id'] = $targetUserId;

        // Verify lead accessibility if lead_id is provided
        if (! empty($validated['lead_id'])) {
            $lead = Lead::find($validated['lead_id']);
            if (! $lead || ! $lead->isAccessibleTo($currentUser)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'العميل المحدد غير متاح لك.',
                        'errors' => ['lead_id' => ['العميل المحدد غير متاح لك.']],
                    ], 422);
                }

                return back()->withErrors(['lead_id' => 'العميل المحدد غير متاح لك.']);
            }
        }

        $event = $this->repository->create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التكليف / الحدث بنجاح.',
                'data' => $event,
            ], 201);
        }

        return redirect()->route('v2.calendar.index')->with('success', 'تم إنشاء التكليف / الحدث بنجاح.');
    }

    public function update(Request $request, CalendarEvent $event): JsonResponse|RedirectResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_time' => ['sometimes', 'required', 'date'],
            'end_time' => ['sometimes', 'required', 'date', 'after_or_equal:start_time'],
            'type' => ['sometimes', 'required', 'string', 'in:meeting,call,task,reminder'],
            'status' => ['sometimes', 'required', 'string', 'in:scheduled,completed,canceled'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        $currentUser = $request->user();

        // Verify lead accessibility if changing lead_id
        if (array_key_exists('lead_id', $validated) && ! empty($validated['lead_id'])) {
            $lead = Lead::find($validated['lead_id']);
            if (! $lead || ! $lead->isAccessibleTo($currentUser)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'العميل المحدد غير متاح لك.',
                        'errors' => ['lead_id' => ['العميل المحدد غير متاح لك.']],
                    ], 422);
                }

                return back()->withErrors(['lead_id' => 'العميل المحدد غير متاح لك.']);
            }
        }

        $updatedEvent = $this->repository->update($event, $validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحدث بنجاح.',
                'data' => $updatedEvent,
            ]);
        }

        return redirect()->route('v2.calendar.index')->with('success', 'تم تحديث الحدث بنجاح.');
    }

    public function reschedule(Request $request, CalendarEvent $event): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,canceled'],
        ]);

        $updatedEvent = $this->repository->update($event, array_filter($validated, fn ($val) => $val !== null));

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة جدولة الحدث بنجاح.',
            'data' => [
                'id' => $updatedEvent->id,
                'start' => $updatedEvent->start_time->toIso8601String(),
                'end' => $updatedEvent->end_time->toIso8601String(),
                'status' => $updatedEvent->status,
            ],
        ]);
    }

    public function reminders(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        $user = $request->user();
        $minutes = max(1, min(1440, (int) $request->query('within_minutes', 30)));

        $reminders = $this->repository->getUpcomingReminders($user, $minutes);

        $formatted = $reminders->map(fn (CalendarEvent $event) => [
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->type,
            'start_time' => $event->start_time->toIso8601String(),
            'minutes_until' => (int) round(now()->diffInMinutes($event->start_time, false)),
            'reminder_minutes_before' => $event->reminder_minutes_before ?? 15,
            'lead_name' => $event->lead?->name,
            'lead_phone' => $event->lead?->phone,
        ]);

        return response()->json([
            'success' => true,
            'count' => $formatted->count(),
            'data' => $formatted,
        ]);
    }

    public function syncExternal(Request $request, CalendarEvent $event, \App\Services\CalendarSyncService $syncService): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('update', $event);

        $provider = (string) $request->input('provider', 'google');
        $result = $syncService->syncEventToExternal($event, $provider);

        return response()->json([
            'success' => true,
            'message' => 'تمت المزامنة بنجاح مع التقويم الخارجي.',
            'data' => $result,
        ]);
    }

    public function destroy(CalendarEvent $event, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('delete', $event);

        $this->repository->delete($event);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحدث بنجاح.',
            ]);
        }

        return redirect()->route('v2.calendar.index')->with('success', 'تم حذف الحدث بنجاح.');
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

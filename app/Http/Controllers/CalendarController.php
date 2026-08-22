<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\User;
use App\Repositories\CalendarEventRepository;
use App\Security\CrmPermission;
use App\Support\BranchContext;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        // Branches for admin filtering
        $branches = collect();
        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::BRANCHES_VIEW)) {
            $branches = Branch::query()->where('is_active', true)->orderBy('name_ar')->get();
        }

        $currentBranchId = BranchContext::getCurrentBranchId($user);

        // Get leads accessible to this user for the modal dropdown selector
        $leadsQuery = Lead::query()
            ->accessibleTo($user)
            ->select(['id', 'name', 'company_name', 'phone', 'branch_id']);

        if ($currentBranchId !== null) {
            $leadsQuery->forBranch($currentBranchId);
        }

        $leads = $leadsQuery->orderBy('name', 'asc')
            ->limit(300)
            ->get();

        // Get users list if super-admin or has leads.scope.all/group permission
        $assignableUsers = collect();
        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL) || $user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $usersQuery = User::query()
                ->where('is_active', true)
                ->select(['id', 'name', 'username', 'branch_id']);

            if ($currentBranchId !== null) {
                $usersQuery->forBranch($currentBranchId);
            }

            $assignableUsers = $usersQuery->orderBy('name', 'asc')->get();
        } else {
            $assignableUsers = collect([$user]);
        }

        return view('calendar.index', [
            'leads' => $leads,
            'assignableUsers' => $assignableUsers,
            'branches' => $branches,
            'currentBranchId' => $currentBranchId,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        $user = $request->user();
        $start = $request->query('start');
        $end = $request->query('end');

        $filters = $request->only(['type', 'status', 'user_id', 'lead_id', 'branch_id']);

        // Determine active branch scope
        if (! array_key_exists('branch_id', $filters) || $filters['branch_id'] === null || $filters['branch_id'] === '') {
            $filters['branch_id'] = BranchContext::getCurrentBranchId($user);
        }

        // 1. Fetch manual Calendar Events
        $events = $this->repository->getEventsForUser(
            $user,
            is_string($start) ? $start : null,
            is_string($end) ? $end : null,
            $filters
        );

        $formattedManualEvents = $events->map(function (CalendarEvent $event): array {
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

            $donorName = $event->lead?->name;
            $donorPhone = $event->lead?->primaryPhone?->phone ?? $event->lead?->phone;
            $donorCompany = $event->lead?->company_name;
            $branchName = $event->branch?->name ?? $event->lead?->branch?->name ?? null;
            $donationTarget = $event->lead?->donationPurposeRel?->name_ar ?? $event->lead?->donation_purpose;
            $donationType = $event->lead?->donationTypeRel?->name_ar ?? $event->lead?->donation_type;
            $donationValue = $event->lead?->donation_value ? number_format((float) $event->lead->donation_value, 2) : null;
            $stageName = $event->lead?->status?->stage?->name_ar ?? $event->lead?->status?->name_ar;

            return [
                'id' => $event->id,
                'raw_id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start_time->toIso8601String(),
                'end' => $event->end_time->toIso8601String(),
                'type' => $event->type,
                'event_source' => 'manual',
                'status' => $event->status,
                'user_id' => $event->user_id,
                'user_name' => $event->user?->name,
                'lead_id' => $event->lead_id,
                'lead_name' => $donorName,
                'lead_company' => $donorCompany,
                'lead_phone' => $donorPhone,
                'lead_url' => $event->lead_id ? route('v2.leads.show', $event->lead_id) : null,
                'branch_id' => $event->branch_id ?? $event->lead?->branch_id,
                'branch_name' => $branchName,
                'donation_target' => $donationTarget,
                'donation_type' => $donationType,
                'donation_value' => $donationValue,
                'stage_name' => $stageName,
                'reminder_minutes_before' => $event->reminder_minutes_before ?? 15,
                'sync_id' => $event->sync_id,
                'provider' => $event->provider,
                'synced_at' => $event->synced_at?->toIso8601String(),
                'color' => $color,
                'borderColor' => $color,
                'allDay' => false,
                'is_lead_followup' => false,
            ];
        });

        // 2. Fetch Lead Follow-ups & Contact Dates if type is empty/all/lead_followup
        $typeFilter = $filters['type'] ?? '';
        $includeLeadFollowups = empty($typeFilter) || $typeFilter === 'all' || $typeFilter === 'lead_followup' || $typeFilter === 'call' || $typeFilter === 'task';

        $formattedLeadEvents = collect();

        if ($includeLeadFollowups) {
            $leadFollowups = $this->repository->getLeadFollowupsForUser(
                $user,
                is_string($start) ? $start : null,
                is_string($end) ? $end : null,
                $filters
            );

            $formattedFollowups = $leadFollowups->map(function (Lead $lead): array {
                $followUpTime = $lead->next_follow_up_at;
                $isPast = $followUpTime ? $followUpTime->isPast() : false;
                $status = $isPast ? 'overdue' : 'scheduled';

                $donorName = $lead->name ?: ($lead->company_name ?: __('crm.unnamed_lead'));
                $donorPhone = $lead->primaryPhone?->phone ?? $lead->phone ?? '—';
                $donationTarget = $lead->donationPurposeRel?->name_ar ?? $lead->donation_purpose ?? '—';
                $donationType = $lead->donationTypeRel?->name_ar ?? $lead->donation_type ?? '—';
                $donationValue = $lead->donation_value ? number_format((float) $lead->donation_value, 2) : null;
                $donationCycle = $lead->donation_cycle ?? null;
                $branchName = $lead->branch?->name_ar ?? $lead->branch?->name_en ?? __('crm.main_branch');
                $assignedName = $lead->assignedUser?->name ?? $lead->assigned_employee ?? '—';
                $stageName = $lead->status?->stage?->name_ar ?? $lead->status?->name_ar ?? '—';
                $statusName = $lead->status?->name_ar ?? '—';

                // Color code: Overdue = red/rose, Scheduled = Indigo/Stage color
                $baseColor = $isPast ? '#dc2626' : '#4f46e5';
                $stageColor = $lead->status?->color ?? $lead->status?->stage?->color ?? $baseColor;

                return [
                    'id' => 'lead_followup_' . $lead->id,
                    'raw_id' => $lead->id,
                    'title' => '📌 ' . $donorName . ($donationValue ? " ({$donationValue})" : ''),
                    'description' => $lead->response_details ?? $lead->notes ?? '',
                    'start' => $followUpTime->toIso8601String(),
                    'end' => $followUpTime->copy()->addMinutes(30)->toIso8601String(),
                    'type' => 'lead_followup',
                    'event_source' => 'lead_followup',
                    'status' => $status,
                    'status_name' => $statusName,
                    'stage_name' => $stageName,
                    'user_id' => $lead->assigned_user_id,
                    'user_name' => $assignedName,
                    'lead_id' => $lead->id,
                    'lead_name' => $donorName,
                    'lead_company' => $lead->company_name,
                    'lead_phone' => $donorPhone,
                    'lead_url' => route('v2.leads.show', $lead->id),
                    'branch_id' => $lead->branch_id,
                    'branch_name' => $branchName,
                    'donation_target' => $donationTarget,
                    'donation_type' => $donationType,
                    'donation_value' => $donationValue,
                    'donation_cycle' => $donationCycle,
                    'response_details' => $lead->response_details,
                    'notes' => $lead->notes,
                    'color' => $baseColor,
                    'borderColor' => $stageColor,
                    'allDay' => false,
                    'is_lead_followup' => true,
                ];
            });

            // Contact Dates (if distinct from next_follow_up_at)
            $leadContactDates = $this->repository->getLeadContactDatesForUser(
                $user,
                is_string($start) ? $start : null,
                is_string($end) ? $end : null,
                $filters
            );

            $formattedContacts = $leadContactDates->map(function (Lead $lead): array {
                $contactTime = $lead->contact_date;
                $donorName = $lead->name ?: ($lead->company_name ?: __('crm.unnamed_lead'));
                $donorPhone = $lead->primaryPhone?->phone ?? $lead->phone ?? '—';
                $branchName = $lead->branch?->name_ar ?? $lead->branch?->name_en ?? __('crm.main_branch');
                $assignedName = $lead->assignedUser?->name ?? $lead->assigned_employee ?? '—';
                $stageName = $lead->status?->stage?->name_ar ?? $lead->status?->name_ar ?? '—';

                return [
                    'id' => 'lead_contact_' . $lead->id,
                    'raw_id' => $lead->id,
                    'title' => '📞 ' . $donorName,
                    'description' => $lead->response_details ?? $lead->notes ?? '',
                    'start' => $contactTime->toIso8601String(),
                    'end' => $contactTime->copy()->addMinutes(30)->toIso8601String(),
                    'type' => 'call',
                    'event_source' => 'lead_contact',
                    'status' => 'completed',
                    'status_name' => __('crm.contact_made'),
                    'stage_name' => $stageName,
                    'user_id' => $lead->assigned_user_id,
                    'user_name' => $assignedName,
                    'lead_id' => $lead->id,
                    'lead_name' => $donorName,
                    'lead_company' => $lead->company_name,
                    'lead_phone' => $donorPhone,
                    'lead_url' => route('v2.leads.show', $lead->id),
                    'branch_id' => $lead->branch_id,
                    'branch_name' => $branchName,
                    'donation_target' => $lead->donationPurposeRel?->name_ar ?? $lead->donation_purpose,
                    'donation_type' => $lead->donationTypeRel?->name_ar ?? $lead->donation_type,
                    'donation_value' => $lead->donation_value ? number_format((float) $lead->donation_value, 2) : null,
                    'color' => '#0d9488', // teal
                    'borderColor' => '#0f766e',
                    'allDay' => false,
                    'is_lead_followup' => true,
                ];
            });

            $formattedLeadEvents = $formattedFollowups->concat($formattedContacts);
        }

        // Apply status filter to lead events if specified
        $statusFilter = $filters['status'] ?? '';
        if (! empty($statusFilter) && $statusFilter !== 'all') {
            $formattedLeadEvents = $formattedLeadEvents->filter(function (array $item) use ($statusFilter): bool {
                if ($statusFilter === 'scheduled') {
                    return $item['status'] === 'scheduled';
                }
                if ($statusFilter === 'completed') {
                    return $item['status'] === 'completed';
                }
                if ($statusFilter === 'overdue') {
                    return $item['status'] === 'overdue';
                }
                return $item['status'] === $statusFilter;
            });
        }

        // If filtering strictly for non-followup types, only return manual events
        if (! empty($typeFilter) && ! in_array($typeFilter, ['all', 'lead_followup', 'call', 'task'], true)) {
            $combined = $formattedManualEvents;
        } elseif ($typeFilter === 'lead_followup') {
            $combined = $formattedLeadEvents;
        } else {
            $combined = $formattedManualEvents->concat($formattedLeadEvents);
        }

        $sortedEvents = $combined->sortBy('start')->values()->all();

        return response()->json([
            'success' => true,
            'data' => $sortedEvents,
        ]);
    }

    public function show(CalendarEvent $event, Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('view', $event);

        $event->load([
            'user:id,name,username',
            'branch:id,name_ar,name_en,code',
            'lead:id,name,company_name,phone,branch_id,donation_type,donation_purpose,donation_value',
            'lead.branch:id,name_ar,name_en,code',
            'lead.primaryPhone:id,lead_id,phone,is_primary',
        ]);

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
                'branch_id' => $event->branch_id ?? $event->lead?->branch_id,
                'branch_name' => $event->branch?->name ?? $event->lead?->branch?->name,
                'lead_id' => $event->lead_id,
                'lead_name' => $event->lead?->name,
                'lead_company' => $event->lead?->company_name,
                'lead_phone' => $event->lead?->primaryPhone?->phone ?? $event->lead?->phone,
                'lead_url' => $event->lead_id ? route('v2.leads.show', $event->lead_id) : null,
                'donation_target' => $event->lead?->donation_purpose,
                'donation_value' => $event->lead?->donation_value,
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
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
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

            // Assign branch from lead if not set
            if (empty($validated['branch_id']) && ! empty($lead->branch_id)) {
                $validated['branch_id'] = $lead->branch_id;
            }
        }

        // Branch fallback
        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = BranchContext::getCurrentBranchId($currentUser);
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
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
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

            if (empty($validated['branch_id']) && ! empty($lead->branch_id)) {
                $validated['branch_id'] = $lead->branch_id;
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

    public function rescheduleLead(Request $request, Lead $lead): JsonResponse
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        abort_unless(
            $user !== null && $lead->isAccessibleTo($user) && ($user->hasPermission(CrmPermission::LEADS_UPDATE) || $user->hasPermission(CrmPermission::CALENDAR_MANAGE) || $user->hasPermission(CrmPermission::TASKS_VIEW)),
            403
        );

        $validated = $request->validate([
            'start_time' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $nextDate = Carbon::parse($validated['start_time']);

        $lead->update([
            'next_follow_up_at' => $nextDate,
        ]);

        LeadFollowup::create([
            'branch_id' => $lead->branch_id ?? $user->branch_id,
            'lead_id' => $lead->id,
            'from_status_id' => $lead->lead_status_id,
            'to_status_id' => $lead->lead_status_id,
            'employee_name' => $user->name ?? 'System',
            'user_id' => $user->id,
            'communication_type' => 'other',
            'outcome' => __('crm.reschedule_task') . (! empty($validated['reason']) ? ': ' . $validated['reason'] : ' (عبر التقويم)'),
            'next_follow_up_at' => $nextDate,
            'followed_up_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إعادة جدولة موعد المتابعة بنجاح.',
            'data' => [
                'lead_id' => $lead->id,
                'next_follow_up_at' => $nextDate->toIso8601String(),
            ],
        ]);
    }

    public function reminders(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        $user = $request->user();
        $minutes = max(1, min(1440, (int) $request->query('within_minutes', 30)));

        $manualReminders = $this->repository->getUpcomingReminders($user, $minutes);
        $leadReminders = $this->repository->getUpcomingLeadFollowupReminders($user, $minutes);

        $formattedManual = $manualReminders->map(fn (CalendarEvent $event) => [
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->type,
            'is_lead_followup' => false,
            'start_time' => $event->start_time->toIso8601String(),
            'minutes_until' => (int) round(now()->diffInMinutes($event->start_time, false)),
            'reminder_minutes_before' => $event->reminder_minutes_before ?? 15,
            'lead_name' => $event->lead?->name,
            'lead_phone' => $event->lead?->primaryPhone?->phone ?? $event->lead?->phone,
            'branch_name' => $event->branch?->name ?? $event->lead?->branch?->name,
        ]);

        $formattedLeads = $leadReminders->map(fn (Lead $lead) => [
            'id' => 'lead_followup_' . $lead->id,
            'title' => 'متابعة: ' . ($lead->name ?: $lead->company_name),
            'type' => 'lead_followup',
            'is_lead_followup' => true,
            'start_time' => $lead->next_follow_up_at->toIso8601String(),
            'minutes_until' => (int) round(now()->diffInMinutes($lead->next_follow_up_at, false)),
            'reminder_minutes_before' => 30,
            'lead_name' => $lead->name ?: $lead->company_name,
            'lead_phone' => $lead->primaryPhone?->phone ?? $lead->phone,
            'lead_url' => route('v2.leads.show', $lead->id),
            'branch_name' => $lead->branch?->name_ar ?? $lead->branch?->name_en,
            'donation_target' => $lead->donationPurposeRel?->name_ar ?? $lead->donation_purpose,
        ]);

        $allReminders = $formattedManual->concat($formattedLeads)->sortBy('start_time')->values();

        return response()->json([
            'success' => true,
            'count' => $allReminders->count(),
            'data' => $allReminders,
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

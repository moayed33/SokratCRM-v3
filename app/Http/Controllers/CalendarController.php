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

        $managedStaff = $user->canViewEmployeeStats()
            ? $user->managedEmployeesQuery()->orderBy('name')->get(['id', 'name', 'username'])
            : collect([$user]);

        $iCalFeedUrl = route('v2.calendar.feed', [
            'user' => $user->id,
            'token' => hash_hmac('sha256', (string) $user->id, config('app.key')),
        ]);

        return view('calendar.index', [
            'leads' => $leads,
            'assignableUsers' => $assignableUsers,
            'managedStaff' => $managedStaff,
            'branches' => $branches,
            'currentBranchId' => $currentBranchId,
            'iCalFeedUrl' => $iCalFeedUrl,
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
                    'title' => $donorName . ($donationValue ? " ({$donationValue})" : ''),
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
                    'title' => $donorName,
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
        // 3. Fetch Scheduled Collection Cases if type is empty/all/collection/task
        $includeCollections = empty($typeFilter) || in_array($typeFilter, ['all', 'collection', 'task'], true);
        $formattedCollectionEvents = collect();

        if ($includeCollections) {
            $collectionCases = $this->repository->getScheduledCollectionCasesForUser(
                $user,
                is_string($start) ? $start : null,
                is_string($end) ? $end : null,
                $filters
            );

            $formattedCollectionEvents = $collectionCases->map(function (\App\Models\CollectionCase $case): array {
                $dueTime = $case->due_at;
                $isPast = $dueTime ? $dueTime->isPast() : false;
                $isCompleted = $case->status === 'completed';
                $color = $isCompleted ? '#16a34a' : ($isPast ? '#dc2626' : '#8b5cf6');
                $donorName = $case->lead?->name ?: __('crm.unnamed_lead');
                $donorPhone = $case->lead?->phone ?: '—';
                $collectorName = $case->assignedCollector?->name ?: __('crm.unassigned');

                return [
                    'id' => 'collection_case_' . $case->id,
                    'raw_id' => $case->id,
                    'title' => '[' . __('crm.collection') . '] ' . number_format((float) $case->expected_amount) . ' EGP — ' . $donorName,
                    'description' => $case->notes ?: ($case->collection_address ? __('crm.address') . ': ' . $case->collection_address : ''),
                    'start' => $case->due_at->toIso8601String(),
                    'end' => $case->due_at->copy()->addMinutes(45)->toIso8601String(),
                    'type' => 'collection',
                    'event_source' => 'collection',
                    'status' => $case->status,
                    'user_id' => $case->assigned_collector_user_id,
                    'user_name' => $collectorName,
                    'lead_id' => $case->lead_id,
                    'lead_name' => $donorName,
                    'lead_company' => $case->lead?->company_name,
                    'lead_phone' => $donorPhone,
                    'lead_url' => $case->lead_id ? route('v2.leads.show', $case->lead_id) : null,
                    'collection_case_id' => $case->id,
                    'collection_url' => route('v2.collections.show', $case),
                    'collection_address' => $case->collection_address,
                    'expected_amount' => number_format((float) $case->expected_amount, 2),
                    'branch_id' => $case->branch_id,
                    'branch_name' => $case->branch?->name_ar ?? null,
                    'donation_type' => $case->donation_type,
                    'color' => $color,
                    'borderColor' => $color,
                    'allDay' => false,
                    'is_lead_followup' => false,
                    'is_collection' => true,
                ];
            });
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
        if (! empty($typeFilter) && ! in_array($typeFilter, ['all', 'lead_followup', 'call', 'task', 'collection'], true)) {
            $combined = $formattedManualEvents;
        } elseif ($typeFilter === 'lead_followup') {
            $combined = $formattedLeadEvents;
        } elseif ($typeFilter === 'collection') {
            $combined = $formattedCollectionEvents;
        } else {
            $combined = $formattedManualEvents->concat($formattedLeadEvents)->concat($formattedCollectionEvents);
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

        $conflict = $this->repository->findConflictingEvent((int) $validated['user_id'], $validated['start_time'], $validated['end_time'], $event->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التكليف / الحدث بنجاح.',
                'has_conflict' => $conflict !== null,
                'conflict_warning' => $conflict ? "تنبيه: يوجد حدث متداخل ({$conflict->title})" : null,
                'data' => $event,
            ], 201);
        }

        $notice = 'تم إنشاء التكليف / الحدث بنجاح.';
        if ($conflict) {
            $notice .= " (تنبيه تعارض مواعيد: يتداخل مع {$conflict->title})";
        }

        return redirect()->route('v2.calendar.index')->with('success', $notice);
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
    public function checkConflict(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        Gate::authorize('viewAny', CalendarEvent::class);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'ignore_id' => ['nullable', 'integer'],
        ]);

        $conflict = $this->repository->findConflictingEvent(
            (int) $validated['user_id'],
            $validated['start_time'],
            $validated['end_time'],
            ! empty($validated['ignore_id']) ? (int) $validated['ignore_id'] : null
        );

        return response()->json([
            'has_conflict' => $conflict !== null,
            'conflicting_event' => $conflict ? [
                'id' => $conflict->id,
                'title' => $conflict->title,
                'type' => $conflict->type,
                'start_time' => $conflict->start_time?->format('Y-m-d H:i'),
                'end_time' => $conflict->end_time?->format('Y-m-d H:i'),
            ] : null,
        ]);
    }

    public function feed(Request $request, User $user): \Illuminate\Http\Response
    {
        $this->assertCrmDatabase();

        $token = (string) $request->query('token', '');
        $expectedToken = hash_hmac('sha256', (string) $user->id, (string) config('app.key'));
        abort_unless(hash_equals($expectedToken, $token), 403, 'Invalid or expired calendar feed token.');

        $start = now()->subMonths(1)->toDateTimeString();
        $end = now()->addMonths(3)->toDateTimeString();

        $events = $this->repository->getEventsForUser($user, $start, $end);
        $followups = $this->repository->getLeadFollowupsForUser($user, $start, $end);
        $collections = $this->repository->getScheduledCollectionCasesForUser($user, $start, $end);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SokratCRM//Calendar Feed//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:SokratCRM - ' . $user->name,
            'X-WR-TIMEZONE:' . config('app.timezone', 'Africa/Cairo'),
        ];

        foreach ($events as $e) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:event-' . $e->id . '@sokratcrm';
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $e->start_time->timestamp);
            $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $e->end_time->timestamp);
            $lines[] = 'SUMMARY:' . addcslashes($e->title, ",;\\");
            $lines[] = 'DESCRIPTION:' . addcslashes((string) ($e->description ?? ''), ",;\\");
            $lines[] = 'STATUS:' . ($e->status === 'canceled' ? 'CANCELLED' : 'CONFIRMED');
            $lines[] = 'END:VEVENT';
        }

        foreach ($followups as $l) {
            if (! $l->next_follow_up_at) continue;
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:lead-followup-' . $l->id . '@sokratcrm';
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $l->next_follow_up_at->timestamp);
            $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $l->next_follow_up_at->copy()->addMinutes(30)->timestamp);
            $lines[] = 'SUMMARY:' . addcslashes('Follow-up: ' . $l->name, ",;\\");
            $lines[] = 'DESCRIPTION:' . addcslashes('Phone: ' . ($l->phone ?? '') . ' | Notes: ' . ($l->notes ?? ''), ",;\\");
            $lines[] = 'END:VEVENT';
        }

        foreach ($collections as $c) {
            if (! $c->due_at) continue;
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:collection-case-' . $c->id . '@sokratcrm';
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $c->due_at->timestamp);
            $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $c->due_at->copy()->addMinutes(45)->timestamp);
            $lines[] = 'SUMMARY:' . addcslashes('Collection: ' . number_format((float) $c->expected_amount) . ' EGP - ' . ($c->lead?->name ?? ''), ",;\\");
            $lines[] = 'DESCRIPTION:' . addcslashes('Address: ' . $c->collection_address . ' | Phone: ' . ($c->lead?->phone ?? ''), ",;\\");
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="sokrat-crm-calendar.ics"',
            'Cache-Control' => 'private, no-cache, must-revalidate',
        ]);
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

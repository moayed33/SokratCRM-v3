<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadPhone;
use App\Models\LeadRelatedPerson;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadController extends Controller
{
    private const DONATION_CYCLES = [
        'one_time' => 'مرة واحدة',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $this->assertCrmV2Database();
        $user = $request->user();

        $stages = PipelineStage::query()
            ->with('statuses:id,pipeline_stage_id,name_ar,code,color,position')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = LeadStatus::query()
            ->with([
                'stage:id,name_ar,color',
            ])
            ->withCount([
                'leads as leads_count' => static fn ($query) => $query
                    ->accessibleTo($user),
            ])
            ->orderBy('position')
            ->get();

        $stageCounts = $statuses->groupBy('pipeline_stage_id')->map(static fn ($group) => $group->sum('leads_count'));
        foreach ($stages as $stage) {
            $stage->setAttribute('scoped_leads_count', (int) ($stageCounts[$stage->id] ?? 0));
        }
        $donationTypes = DonationType::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $donationPurposes = DonationPurpose::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 150),
            'stage' => mb_substr(trim((string) $request->query('stage', '')), 0, 50),
            'status' => mb_substr(trim((string) $request->query('status', '')), 0, 50),
            'donation_type' => mb_substr(trim((string) $request->query('donation_type', '')), 0, 100),
            'donation_cycle' => mb_substr(trim((string) $request->query('donation_cycle', '')), 0, 50),
            'employee' => mb_substr(trim((string) $request->query('employee', '')), 0, 150),
            'source' => mb_substr(trim((string) $request->query('source', '')), 0, 100),
            'follow_up' => mb_substr(trim((string) $request->query('follow_up', '')), 0, 20),
            'sort' => mb_substr(trim((string) $request->query('sort', 'latest')), 0, 20),
        ];

        $statusCodes = $statuses->pluck('code')->all();
        if ($filters['status'] !== '' && ! in_array($filters['status'], $statusCodes, true) && ! is_numeric($filters['status'])) {
            $filters['status'] = '';
        }

        $allowedFollowUpFilters = ['', 'today', 'upcoming', 'overdue', 'none'];
        if (! in_array($filters['follow_up'], $allowedFollowUpFilters, true)) {
            $filters['follow_up'] = '';
        }

        $allowedSorts = ['latest', 'oldest', 'name', 'followup', 'donation_value'];
        if (! in_array($filters['sort'], $allowedSorts, true)) {
            $filters['sort'] = 'latest';
        }

        $visibleAssignedUserIds = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');

        $employees = User::query()
            ->whereIn('id', $visibleAssignedUserIds)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Lead::query()
                    ->accessibleTo($user)
                    ->whereNull('assigned_user_id')
                    ->whereNotNull('assigned_employee')
                    ->where('assigned_employee', '<>', '')
                    ->pluck('assigned_employee')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $sources = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        if ($filters['source'] !== '' && ! in_array($filters['source'], $sources->all(), true)) {
            $filters['source'] = '';
        }

        $query = Lead::query()
            ->accessibleTo($user)
            ->select([
                'leads.id',
                'leads.branch_id',
                'leads.lead_status_id',
                'leads.name',
                'leads.first_name',
                'leads.last_name',
                'leads.company_name',
                'leads.phone',
                'leads.email',
                'leads.source',
                'leads.donation_type',
                'leads.donation_type_id',
                'leads.donation_cycle',
                'leads.donation_value',
                'leads.donation_purpose',
                'leads.donation_purpose_id',
                'leads.assigned_employee',
                'leads.assigned_user_id',
                'leads.responding_user_id',
                'leads.created_by',
                'leads.created_by_user_id',
                'leads.contact_date',
                'leads.next_follow_up_at',
                'leads.created_at',
            ])
            ->with([
                'status.stage:id,name_ar,color,position,code',
                'branch:id,name_ar,name_en,code',
                'assignedUser:id,name',
                'respondingUser:id,name',
                'phones:id,lead_id,phone,label,is_primary',
                'relatedPeople:id,lead_id,name,phone,relationship_type',
                'donationTypeRel:id,name_ar',
                'donationPurposeRel:id,name_ar',
            ]);
        if ($filters['q'] !== '') {
            $search = '%'.$filters['q'].'%';
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', $search)
                    ->orWhere('company_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('donation_type', 'like', $search)
                    ->orWhere('donation_purpose', 'like', $search)
                    ->orWhere('response_details', 'like', $search)
                    ->orWhereHas('phones', static fn ($pq) => $pq->where('phone', 'like', $search))
                    ->orWhereHas('relatedPeople', static fn ($rq) => $rq->where('name', 'like', $search)->orWhere('phone', 'like', $search));
            });
        }

        // Filter: Stage
        if ($filters['stage'] !== '') {
            $stageVal = $filters['stage'];
            $query->whereHas('status', function ($statusQuery) use ($stageVal): void {
                if (is_numeric($stageVal)) {
                    $statusQuery->where('pipeline_stage_id', (int) $stageVal);
                } else {
                    $statusQuery->whereHas('stage', static fn ($sq) => $sq->where('code', $stageVal));
                }
            });
        }

        // Filter: Status
        if ($filters['status'] !== '') {
            $statusVal = $filters['status'];
            $query->whereHas('status', function ($statusQuery) use ($statusVal): void {
                if (is_numeric($statusVal)) {
                    $statusQuery->where('id', (int) $statusVal);
                } else {
                    $statusQuery->where('code', $statusVal);
                }
            });
        }

        // Filter: Donation Type
        if ($filters['donation_type'] !== '') {
            $query->where(function ($q) use ($filters): void {
                $q->where('donation_type', $filters['donation_type'])
                    ->orWhere('donation_type_id', $filters['donation_type']);
            });
        }

        // Filter: Donation Cycle
        if ($filters['donation_cycle'] !== '') {
            $query->where('donation_cycle', $filters['donation_cycle']);
        }

        // Filter: Employee
        if ($filters['employee'] !== '') {
            $query->where(function ($employeeQuery) use ($filters): void {
                $employeeQuery
                    ->whereHas('assignedUser', static fn ($userQuery) => $userQuery->where('name', $filters['employee']))
                    ->orWhereHas('respondingUser', static fn ($userQuery) => $userQuery->where('name', $filters['employee']))
                    ->orWhere(static function ($legacyQuery) use ($filters): void {
                        $legacyQuery
                            ->whereNull('assigned_user_id')
                            ->where('assigned_employee', $filters['employee']);
                    });
            });
        }

        // Filter: Source
        if ($filters['source'] !== '') {
            $query->where('source', $filters['source']);
        }

        // Filter: Follow-up
        switch ($filters['follow_up']) {
            case 'today':
                $query->whereBetween('next_follow_up_at', [
                    now()->startOfDay(),
                    now()->endOfDay(),
                ]);
                break;

            case 'upcoming':
                $query->where('next_follow_up_at', '>', now()->endOfDay());
                break;

            case 'overdue':
                $query->where('next_follow_up_at', '<', now()->startOfDay());
                break;

            case 'none':
                $query->whereNull('next_follow_up_at');
                break;
        }

        // Sort
        switch ($filters['sort']) {
            case 'oldest':
                $query->orderBy('created_at')->orderBy('id');
                break;

            case 'name':
                $query->orderBy('name')->orderByDesc('id');
                break;

            case 'followup':
                $query->orderByRaw('next_follow_up_at IS NULL')
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('id');
                break;

            case 'donation_value':
                $query->orderByDesc('donation_value')->orderByDesc('id');
                break;

            default:
                $query->orderByDesc('created_at')->orderByDesc('id');
                break;
        }

        $leads = $query->paginate(20)->withQueryString();
        $totalLeads = Lead::query()->accessibleTo($user)->count();

        $activeQuery = array_filter(
            $filters,
            static fn ($value) => $value !== '' && $value !== 'latest'
        );

        $queryWithoutStatus = $request->query();
        unset($queryWithoutStatus['status'], $queryWithoutStatus['stage'], $queryWithoutStatus['page']);

        return view('leads.index', [
            'leads' => $leads,
            'stages' => $stages,
            'statuses' => $statuses,
            'donationTypes' => $donationTypes,
            'donationPurposes' => $donationPurposes,
            'donationCycles' => self::DONATION_CYCLES,
            'employees' => $employees,
            'sources' => $sources,
            'filters' => $filters,
            'activeQuery' => $activeQuery,
            'queryWithoutStatus' => $queryWithoutStatus,
            'totalLeads' => $totalLeads,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->assertCrmV2Database();

        $actor = $request->user();
        $assignedEmployee = trim((string) $actor->name);

        abort_if($assignedEmployee === '', 403, 'Employee identity is required.');

        $canAssignLead = $actor->hasPermission(CrmPermission::LEADS_ASSIGN);
        $assignableUsers = LeadAssignment::assignableUsers($actor);
        $campaigns = Campaign::query()
            ->with('users:id')
            ->when(
                ! $actor->isSuperAdmin(),
                static fn ($query) => $actor->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
                    ? $query->where('created_by_user_id', $actor->id)
                    : $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'starts_at']);

        $campaign = $this->campaignForManualLead($request);

        $stages = PipelineStage::query()
            ->with('statuses')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->get();

        $donationTypes = DonationType::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $donationPurposes = DonationPurpose::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $sources = Lead::query()
            ->accessibleTo($actor)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $totalLeads = Lead::query()->accessibleTo($actor)->count();
        $defaultStageId = $stages->firstWhere('code', 'new')?->id ?? $stages->first()?->id;

        return view('leads.create', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name_ar')->get(),
            'stages' => $stages,
            'statuses' => $statuses,
            'defaultStageId' => $defaultStageId,
            'donationTypes' => $donationTypes,
            'donationPurposes' => $donationPurposes,
            'donationCycles' => self::DONATION_CYCLES,
            'sources' => $sources,
            'assignedEmployee' => $assignedEmployee,
            'canAssignLead' => $canAssignLead,
            'assignableUsers' => $assignableUsers,
            'totalLeads' => $totalLeads,
            'campaign' => $campaign,
            'campaigns' => $campaigns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCrmV2Database();

        $actor = $request->user();
        $campaign = $this->campaignForManualLead($request);
        $creatorName = trim((string) $actor->name);

        abort_if($creatorName === '', 403, 'Employee identity is required.');

        // Validation rules
        $rules = [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'first_name' => ['nullable', 'string', 'max:75'],
            'last_name' => ['nullable', 'string', 'max:75'],
            'phone' => ['required', 'string', 'max:50'],
            'additional_phones' => ['nullable', 'array'],
            'additional_phones.*.phone' => ['nullable', 'string', 'max:50'],
            'additional_phones.*.label' => ['nullable', 'string', 'max:50'],
            'donation_type' => ['nullable', 'string', 'max:100'],
            'donation_type_id' => ['nullable', 'integer', 'exists:donation_types,id'],
            'donation_cycle' => ['nullable', 'string', 'max:50'],
            'donation_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'donation_purpose' => ['nullable', 'string', 'max:150'],
            'donation_purpose_id' => ['nullable', 'integer', 'exists:donation_purposes,id'],
            'responding_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'pipeline_stage_id' => ['nullable', 'integer', 'exists:pipeline_stages,id'],
            'contact_date' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'string'],
            'response_details' => ['nullable', 'string', 'max:10000'],
            'related_people' => ['nullable', 'array'],
            'related_people.*.name' => ['nullable', 'string', 'max:150'],
            'related_people.*.phone' => ['nullable', 'string', 'max:50'],
            'related_people.*.relationship_type' => ['nullable', 'string', 'max:100'],
            'related_people.*.notes' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'activity' => ['nullable', 'string', 'max:150'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules, [
            'phone.required' => 'رقم الهاتف الأساسي مطلوب.',
            'donation_value.numeric' => 'قيمة التبرع يجب أن تكون قيمة رقمية صحيحة.',
            'donation_value.min' => 'قيمة التبرع لا يمكن أن تكون سالبة.',
        ]);

        // Customer Name resolution
        $nameInput = trim((string) ($validated['name'] ?? ''));
        $firstNameInput = trim((string) ($validated['first_name'] ?? ''));
        $lastNameInput = trim((string) ($validated['last_name'] ?? ''));

        if ($nameInput === '' && $firstNameInput === '') {
            return redirect()->back()->withInput()->withErrors([
                'name' => 'اسم العميل / المتبرع مطلوب.',
            ]);
        }

        if ($nameInput !== '') {
            $fullName = $nameInput;
            $nameParts = preg_split('/\s+/u', $fullName, 2);
            $firstName = $nameParts[0] ?? $fullName;
            $lastName = $nameParts[1] ?? ($lastNameInput !== '' ? $lastNameInput : null);
        } else {
            $firstName = $firstNameInput;
            $lastName = $lastNameInput !== '' ? $lastNameInput : null;
            $fullName = trim($firstName.' '.($lastName ?? ''));
        }

        // Status / Stage resolution
        $status = null;
        if (! empty($validated['lead_status_id'])) {
            $status = LeadStatus::query()->find((int) $validated['lead_status_id']);
        } elseif (! empty($validated['pipeline_stage_id'])) {
            $stage = PipelineStage::query()->find((int) $validated['pipeline_stage_id']);
            $status = $stage?->statuses()->first();
        }

        if ($status === null) {
            $newStage = PipelineStage::query()->where('code', 'new')->first();
            $status = $newStage?->statuses()->first() ?? LeadStatus::query()->orderBy('position')->first();
        }

        abort_if($status === null, 422, 'No valid lead status found.');

        // Assignee resolution
        $assignee = $actor;
        $assignedUserId = $validated['assigned_user_id'] ?? $validated['responding_user_id'] ?? null;
        if ($assignedUserId !== null) {
            $assignee = User::query()->findOrFail((int) $assignedUserId);
            abort_unless(
                LeadAssignment::canAssignTo($actor, $assignee),
                403,
                'You cannot assign leads to this user.'
            );
        }

        if ($campaign !== null) {
            abort_unless(
                $assignee->is($actor) || $campaign->users()->whereKey($assignee->id)->exists(),
                403,
                'The assignee must belong to this campaign.'
            );
        }

        $assignedEmployee = trim((string) $assignee->name);
        abort_if($assignedEmployee === '', 403, 'Assignee identity is required.');

        // Donation lookup names
        $donationTypeStr = $validated['donation_type'] ?? null;
        if (! empty($validated['donation_type_id'])) {
            $typeModel = DonationType::query()->find((int) $validated['donation_type_id']);
            if ($typeModel) {
                $donationTypeStr = $typeModel->name_ar;
            }
        }

        $donationPurposeStr = $validated['donation_purpose'] ?? null;
        if (! empty($validated['donation_purpose_id'])) {
            $purposeModel = DonationPurpose::query()->find((int) $validated['donation_purpose_id']);
            if ($purposeModel) {
                $donationPurposeStr = $purposeModel->name_ar;
            }
        }

        // Dates parsing
        $contactDate = ! empty($validated['contact_date'])
            ? Carbon::parse($validated['contact_date'])
            : null;

        $nextFollowUpAt = null;
        if (! empty($validated['next_follow_up_at'])) {
            try {
                $nextFollowUpAt = Carbon::parse($validated['next_follow_up_at']);
            } catch (\Throwable) {
                $nextFollowUpAt = null;
            }
        }

        $branchId = null;
        if ($actor->isSuperAdmin() && ! empty($validated['branch_id'])) {
            $branchId = (int) $validated['branch_id'];
        } elseif (! empty($actor->branch_id)) {
            $branchId = (int) $actor->branch_id;
        } elseif (! empty($validated['branch_id'])) {
            $branchId = (int) $validated['branch_id'];
        } else {
            $branchId = Branch::value('id');
        }

        $leadData = [
            'branch_id' => $branchId,
            'lead_status_id' => $status->id,
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string) $validated['phone']),
            'email' => $request->input('email'),
            'source' => trim((string) ($validated['source'] ?? 'مباشر')),
            'assigned_employee' => $assignedEmployee,
            'assigned_user_id' => $assignee->id,
            'responding_user_id' => $assignee->id,
            'created_by' => $creatorName,
            'created_by_user_id' => $actor->id,
            'donation_type' => $donationTypeStr,
            'donation_type_id' => ! empty($validated['donation_type_id']) ? (int) $validated['donation_type_id'] : null,
            'donation_cycle' => $validated['donation_cycle'] ?? null,
            'donation_value' => isset($validated['donation_value']) && $validated['donation_value'] !== '' ? (float) $validated['donation_value'] : null,
            'donation_purpose' => $donationPurposeStr,
            'donation_purpose_id' => ! empty($validated['donation_purpose_id']) ? (int) $validated['donation_purpose_id'] : null,
            'response_details' => $validated['response_details'] ?? null,
            'notes' => $validated['response_details'] ?? null,
            'contact_date' => $contactDate,
            'next_follow_up_at' => $nextFollowUpAt,
            'company_name' => $validated['company_name'] ?? null,
            'activity' => $validated['activity'] ?? null,
            'governorate' => $validated['governorate'] ?? null,
            'address' => $validated['address'] ?? null,
        ];

        $createdLead = DB::transaction(function () use ($leadData, $validated, $campaign, $actor, $status, $assignee, $assignedEmployee): Lead {
            $lead = Lead::query()->create($leadData);

            // 1. Primary phone in lead_phones
            LeadPhone::query()->create([
                'lead_id' => $lead->id,
                'phone' => $lead->phone,
                'is_primary' => true,
                'label' => 'أساسي',
            ]);

            // 2. Additional phones
            if (! empty($validated['additional_phones']) && is_array($validated['additional_phones'])) {
                foreach ($validated['additional_phones'] as $phoneRow) {
                    $extraPhone = trim((string) ($phoneRow['phone'] ?? ''));
                    if ($extraPhone !== '' && $extraPhone !== $lead->phone) {
                        LeadPhone::query()->create([
                            'lead_id' => $lead->id,
                            'phone' => $extraPhone,
                            'is_primary' => false,
                            'label' => trim((string) ($phoneRow['label'] ?? 'إضافي')) ?: 'إضافي',
                        ]);
                    }
                }
            }

            // 3. Related people
            if (! empty($validated['related_people']) && is_array($validated['related_people'])) {
                foreach ($validated['related_people'] as $personRow) {
                    $pName = trim((string) ($personRow['name'] ?? ''));
                    if ($pName !== '') {
                        LeadRelatedPerson::query()->create([
                            'lead_id' => $lead->id,
                            'name' => $pName,
                            'phone' => trim((string) ($personRow['phone'] ?? '')) ?: null,
                            'relationship_type' => trim((string) ($personRow['relationship_type'] ?? 'أخرى')) ?: 'أخرى',
                            'notes' => trim((string) ($personRow['notes'] ?? '')) ?: null,
                        ]);
                    }
                }
            }

            // 4. Attach campaign
            $campaign?->leads()->attach($lead->id);

            // 5. Initial status history
            LeadStatusHistory::query()->create([
                'lead_id' => $lead->id,
                'from_status_id' => null,
                'to_status_id' => $status->id,
                'changed_by' => $actor->name,
                'changed_by_user_id' => $actor->id,
                'note' => ! empty($lead->response_details) ? 'إنشاء العميل: '.$lead->response_details : 'إنشاء العميل',
                'changed_at' => now(),
            ]);

            // 6. Initial followup if response details or contact date provided
            if (! empty($lead->response_details) || $lead->contact_date !== null || $lead->next_follow_up_at !== null) {
                LeadFollowup::query()->create([
                    'branch_id' => $lead->branch_id,
                    'lead_id' => $lead->id,
                    'from_status_id' => null,
                    'to_status_id' => $status->id,
                    'employee_name' => $assignedEmployee,
                    'user_id' => $assignee->id,
                    'communication_type' => 'مكالمة هاتفية',
                    'outcome' => $lead->response_details ?? 'تم إنشاء العميل',
                    'next_follow_up_at' => $lead->next_follow_up_at,
                    'followed_up_at' => $lead->contact_date ?? now(),
                ]);
            }

            return $lead;
        });

        if ($campaign !== null) {
            return redirect()
                ->route('v2.campaigns.show', [
                    'campaign' => $campaign,
                    'assigned_user_id' => $createdLead->assigned_user_id,
                ])
                ->with('success', 'تمت إضافة العميل إلى الحملة بنجاح.');
        }

        return redirect()
            ->route('v2.leads')
            ->with('success', 'تمت إضافة العميل بنجاح.');
    }

    public function show(Request $request, string $lead): View|RedirectResponse
    {
        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with([
                'status.stage',
                'branch:id,name_ar,name_en,code',
                'assignedUser:id,name,username',
                'respondingUser:id,name,username',
                'creator:id,name',
                'phones',
                'relatedPeople',
                'donationTypeRel:id,name_ar',
                'donationPurposeRel:id,name_ar',
                'statusHistory.changedByUser:id,name',
                'statusHistory.fromStatus.stage',
                'statusHistory.toStatus.stage',
                'followups.user:id,name',
                'followups.fromStatus.stage',
                'followups.toStatus.stage',
            ])
            ->findOrFail((int) $lead);

        Gate::authorize('view', $leadRecord);

        // Build unified chronological timeline
        $timelineEvents = collect();

        foreach ($leadRecord->followups as $followup) {
            $timelineEvents->push([
                'type' => 'followup',
                'timestamp' => $followup->followed_up_at ?? $followup->created_at,
                'employee' => $followup->user?->name ?? $followup->employee_name ?? 'موظف',
                'from_status' => $followup->fromStatus?->name_ar,
                'to_status' => $followup->toStatus?->name_ar,
                'communication_type' => $followup->communication_type,
                'details' => $followup->outcome,
                'next_follow_up' => $followup->next_follow_up_at,
                'field_changes' => $followup->field_changes,
            ]);
        }

        foreach ($leadRecord->statusHistory as $history) {
            // Check if there's already a matching followup at approximately the same minute
            $alreadyIncluded = $timelineEvents->contains(function ($item) use ($history) {
                return $item['type'] === 'followup'
                    && abs(($item['timestamp']?->timestamp ?? 0) - ($history->changed_at?->timestamp ?? 0)) < 60;
            });

            if (! $alreadyIncluded) {
                $timelineEvents->push([
                    'type' => 'status_change',
                    'timestamp' => $history->changed_at ?? $history->created_at,
                    'employee' => $history->changedByUser?->name ?? $history->changed_by ?? 'النظام',
                    'from_status' => $history->fromStatus?->name_ar,
                    'to_status' => $history->toStatus?->name_ar,
                    'communication_type' => null,
                    'details' => $history->note,
                    'next_follow_up' => null,
                    'field_changes' => null,
                ]);
            }
        }

        $timelineEvents = $timelineEvents->sortByDesc('timestamp')->values();

        $statusColorValue = trim((string) $leadRecord->status?->stage?->color ?? $leadRecord->status?->color ?? '#3478f6');
        $statusColor = preg_match('/^#[0-9a-fA-F]{6}$/', $statusColorValue) === 1 ? $statusColorValue : '#3478f6';

        // Phone formatting
        $phoneRaw = trim((string) $leadRecord->phone);
        $phoneDigits = preg_replace('/\D+/', '', $phoneRaw) ?? '';
        $callPhone = preg_match('/^[0-9]{2,20}$/', $phoneDigits) === 1 ? $phoneDigits : null;
        $whatsappPhone = $callPhone;

        $backQuery = [];
        $queryLimits = [
            'q' => 150,
            'stage' => 50,
            'status' => 50,
            'donation_type' => 100,
            'donation_cycle' => 50,
            'employee' => 150,
            'source' => 100,
            'follow_up' => 20,
            'sort' => 20,
        ];

        foreach ($queryLimits as $key => $limit) {
            $rawValue = $request->query($key, '');
            if (is_scalar($rawValue) && trim((string) $rawValue) !== '') {
                $backQuery[$key] = mb_substr(trim((string) $rawValue), 0, $limit);
            }
        }

        return view('leads.show', [
            'lead' => $leadRecord,
            'timelineEvents' => $timelineEvents,
            'statusColor' => $statusColor,
            'callPhone' => $callPhone,
            'whatsappPhone' => $whatsappPhone,
            'donationCycles' => self::DONATION_CYCLES,
            'backQuery' => $backQuery,
        ]);
    }

    public function edit(Request $request, string $lead): View|RedirectResponse
    {
        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with([
                'branch:id,name_ar,name_en,code',
                'assignedUser:id,name,username',
                'respondingUser:id,name,username',
                'phones',
                'relatedPeople',
                'status.stage',
            ])
            ->findOrFail((int) $lead);

        Gate::authorize('update', $leadRecord);

        $actor = $request->user();
        $canAssignLead = $actor->hasPermission(CrmPermission::LEADS_ASSIGN);
        $assignableUsers = $canAssignLead
            ? LeadAssignment::assignableUsers($actor)
            : collect();

        if ($canAssignLead && $leadRecord->assignedUser !== null && ! $assignableUsers->contains('id', $leadRecord->assignedUser->id)) {
            $assignableUsers->push($leadRecord->assignedUser);
        }

        $stages = PipelineStage::query()
            ->with('statuses')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->get();

        $donationTypes = DonationType::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $donationPurposes = DonationPurpose::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $sources = Lead::query()
            ->accessibleTo($actor)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('leads.edit', [
            'lead' => $leadRecord,
            'branches' => Branch::query()->orderBy('name_ar')->get(),
            'stages' => $stages,
            'statuses' => $statuses,
            'donationTypes' => $donationTypes,
            'donationPurposes' => $donationPurposes,
            'donationCycles' => self::DONATION_CYCLES,
            'sources' => $sources,
            'canAssignLead' => $canAssignLead,
            'assignableUsers' => $assignableUsers,
            'assignedEmployee' => $leadRecord->assignedUser?->name ?? $leadRecord->assigned_employee ?: 'غير مسند',
        ]);
    }

    public function update(Request $request, string $lead): RedirectResponse
    {
        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with(['status.stage', 'phones', 'relatedPeople'])
            ->findOrFail((int) $lead);

        Gate::authorize('update', $leadRecord);
        $actor = $request->user();

        $rules = [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:150'],
            'first_name' => ['nullable', 'string', 'max:75'],
            'last_name' => ['nullable', 'string', 'max:75'],
            'phone' => ['required', 'string', 'max:50'],
            'additional_phones' => ['nullable', 'array'],
            'additional_phones.*.phone' => ['nullable', 'string', 'max:50'],
            'additional_phones.*.label' => ['nullable', 'string', 'max:50'],
            'donation_type' => ['nullable', 'string', 'max:100'],
            'donation_type_id' => ['nullable', 'integer', 'exists:donation_types,id'],
            'donation_cycle' => ['nullable', 'string', 'max:50'],
            'donation_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'donation_purpose' => ['nullable', 'string', 'max:150'],
            'donation_purpose_id' => ['nullable', 'integer', 'exists:donation_purposes,id'],
            'responding_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'pipeline_stage_id' => ['nullable', 'integer', 'exists:pipeline_stages,id'],
            'contact_date' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'string'],
            'response_details' => ['nullable', 'string', 'max:10000'],
            'related_people' => ['nullable', 'array'],
            'related_people.*.name' => ['nullable', 'string', 'max:150'],
            'related_people.*.phone' => ['nullable', 'string', 'max:50'],
            'related_people.*.relationship_type' => ['nullable', 'string', 'max:100'],
            'related_people.*.notes' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'activity' => ['nullable', 'string', 'max:150'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules, [
            'phone.required' => 'رقم الهاتف الأساسي مطلوب.',
            'donation_value.numeric' => 'قيمة التبرع يجب أن تكون رقمية.',
            'donation_value.min' => 'قيمة التبرع لا يمكن أن تكون سالبة.',
        ]);

        // Customer Name resolution
        $nameInput = trim((string) ($validated['name'] ?? ''));
        $firstNameInput = trim((string) ($validated['first_name'] ?? ''));
        $lastNameInput = trim((string) ($validated['last_name'] ?? ''));

        if ($nameInput !== '') {
            $fullName = $nameInput;
            $nameParts = preg_split('/\s+/u', $fullName, 2);
            $firstName = $nameParts[0] ?? $fullName;
            $lastName = $nameParts[1] ?? ($lastNameInput !== '' ? $lastNameInput : null);
        } elseif ($firstNameInput !== '') {
            $firstName = $firstNameInput;
            $lastName = $lastNameInput !== '' ? $lastNameInput : null;
            $fullName = trim($firstName.' '.($lastName ?? ''));
        } else {
            $fullName = $leadRecord->name;
            $firstName = $leadRecord->first_name;
            $lastName = $leadRecord->last_name;
        }

        // Status resolution
        $oldStatusId = (int) $leadRecord->lead_status_id;
        $newStatusId = $oldStatusId;

        if (! empty($validated['lead_status_id'])) {
            $newStatusId = (int) $validated['lead_status_id'];
        } elseif (! empty($validated['pipeline_stage_id'])) {
            $stage = PipelineStage::query()->find((int) $validated['pipeline_stage_id']);
            $firstStatus = $stage?->statuses()->first();
            if ($firstStatus) {
                $newStatusId = (int) $firstStatus->id;
            }
        }

        // Assignee resolution
        $assignmentChanged = false;
        $assignee = null;
        $assignedUserId = $validated['assigned_user_id'] ?? $validated['responding_user_id'] ?? null;

        if ($assignedUserId !== null) {
            $assignee = User::query()->findOrFail((int) $assignedUserId);
            $assignmentChanged = (int) $leadRecord->assigned_user_id !== (int) $assignee->id;

            if ($assignmentChanged) {
                abort_unless(
                    $actor->hasPermission(CrmPermission::LEADS_ASSIGN) && LeadAssignment::canAssignTo($actor, $assignee),
                    403,
                    'You cannot reassign this lead to the selected user.'
                );
            }
        }

        // Donation lookup names
        $donationTypeStr = $validated['donation_type'] ?? $leadRecord->donation_type;
        if (! empty($validated['donation_type_id'])) {
            $typeModel = DonationType::query()->find((int) $validated['donation_type_id']);
            if ($typeModel) {
                $donationTypeStr = $typeModel->name_ar;
            }
        }

        $donationPurposeStr = $validated['donation_purpose'] ?? $leadRecord->donation_purpose;
        if (! empty($validated['donation_purpose_id'])) {
            $purposeModel = DonationPurpose::query()->find((int) $validated['donation_purpose_id']);
            if ($purposeModel) {
                $donationPurposeStr = $purposeModel->name_ar;
            }
        }

        // Dates
        $contactDate = ! empty($validated['contact_date'])
            ? Carbon::parse($validated['contact_date'])
            : $leadRecord->contact_date;

        $nextFollowUpAt = $leadRecord->next_follow_up_at;
        if (array_key_exists('next_follow_up_at', $validated)) {
            $nextFollowUpAt = ! empty($validated['next_follow_up_at'])
                ? Carbon::parse($validated['next_follow_up_at'])
                : null;
        }

        $targetStatus = LeadStatus::query()->find($newStatusId);
        if ($targetStatus && $targetStatus->code === 'not_interested') {
            $nextFollowUpAt = null;
        }
        $leadData = [
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string) $validated['phone']),
            'source' => trim((string) ($validated['source'] ?? $leadRecord->source)),
            'lead_status_id' => $newStatusId,
            'donation_type' => $donationTypeStr,
            'donation_type_id' => ! empty($validated['donation_type_id']) ? (int) $validated['donation_type_id'] : $leadRecord->donation_type_id,
            'donation_cycle' => $validated['donation_cycle'] ?? $leadRecord->donation_cycle,
            'donation_value' => isset($validated['donation_value']) && $validated['donation_value'] !== '' ? (float) $validated['donation_value'] : $leadRecord->donation_value,
            'donation_purpose' => $donationPurposeStr,
            'donation_purpose_id' => ! empty($validated['donation_purpose_id']) ? (int) $validated['donation_purpose_id'] : $leadRecord->donation_purpose_id,
            'response_details' => $validated['response_details'] ?? $leadRecord->response_details,
            'notes' => $validated['response_details'] ?? $leadRecord->notes,
            'contact_date' => $contactDate,
            'next_follow_up_at' => $nextFollowUpAt,
            'governorate' => $validated['governorate'] ?? $leadRecord->governorate,
            'address' => $validated['address'] ?? $leadRecord->address,
        ];

        if ($actor->isSuperAdmin() && array_key_exists('branch_id', $validated)) {
            $leadData['branch_id'] = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        }

        if ($assignmentChanged && $assignee !== null) {
            $assignedEmployee = trim((string) $assignee->name);
            abort_if($assignedEmployee === '', 403, 'Assignee identity is required.');
            $leadData['assigned_user_id'] = $assignee->id;
            $leadData['responding_user_id'] = $assignee->id;
            $leadData['assigned_employee'] = $assignedEmployee;
        }

        DB::transaction(function () use ($leadRecord, $leadData, $validated, $oldStatusId, $newStatusId, $actor, $nextFollowUpAt): void {
            if ($oldStatusId !== $newStatusId) {
                $targetStatus = LeadStatus::query()->findOrFail($newStatusId);
                app(\App\Services\LeadTransitionService::class)->transition(
                    $leadRecord,
                    $targetStatus,
                    $actor,
                    [
                        'lead_attributes' => $leadData,
                        'next_follow_up_at' => $nextFollowUpAt,
                        'history_note' => 'تحديث الحالة من شاشة تعديل العميل',
                    ]
                );
            } else {
                $leadRecord->update($leadData);
            }

            // 1. Sync primary phone
            LeadPhone::query()->updateOrInsert(
                ['lead_id' => $leadRecord->id, 'is_primary' => true],
                ['phone' => $leadRecord->phone, 'label' => 'أساسي', 'updated_at' => now()]
            );

            // 2. Sync additional phones
            if (isset($validated['additional_phones']) && is_array($validated['additional_phones'])) {
                // Remove existing additional phones
                LeadPhone::query()
                    ->where('lead_id', $leadRecord->id)
                    ->where('is_primary', false)
                    ->delete();

                foreach ($validated['additional_phones'] as $phoneRow) {
                    $extraPhone = trim((string) ($phoneRow['phone'] ?? ''));
                    if ($extraPhone !== '' && $extraPhone !== $leadRecord->phone) {
                        LeadPhone::query()->create([
                            'lead_id' => $leadRecord->id,
                            'phone' => $extraPhone,
                            'is_primary' => false,
                            'label' => trim((string) ($phoneRow['label'] ?? 'إضافي')) ?: 'إضافي',
                        ]);
                    }
                }
            }

            // 3. Sync related people
            if (isset($validated['related_people']) && is_array($validated['related_people'])) {
                LeadRelatedPerson::query()
                    ->where('lead_id', $leadRecord->id)
                    ->delete();

                foreach ($validated['related_people'] as $personRow) {
                    $pName = trim((string) ($personRow['name'] ?? ''));
                    if ($pName !== '') {
                        LeadRelatedPerson::query()->create([
                            'lead_id' => $leadRecord->id,
                            'name' => $pName,
                            'phone' => trim((string) ($personRow['phone'] ?? '')) ?: null,
                            'relationship_type' => trim((string) ($personRow['relationship_type'] ?? 'أخرى')) ?: 'أخرى',
                            'notes' => trim((string) ($personRow['notes'] ?? '')) ?: null,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('v2.leads')
            ->with('success', 'تم تحديث بيانات العميل '.$fullName.' بنجاح.');
    }

    public function destroy(string $lead): RedirectResponse
    {
        $this->assertCrmV2Database();

        $leadRecord = Lead::query()->findOrFail((int) $lead);
        Gate::authorize('delete', $leadRecord);

        DB::transaction(static function () use ($leadRecord): void {
            $leadRecord->delete();
        });

        return redirect()
            ->route('v2.leads')
            ->with('success', 'تم حذف العميل بنجاح.');
    }

    public function exportSelected(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->assertCrmV2Database();
        $actor = $request->user();

        $leadIds = (array) $request->input('lead_ids', []);
        $leadIds = array_filter(array_map('intval', $leadIds));

        if ($leadIds === []) {
            return redirect()->back()->withErrors(['lead_ids' => 'لم يتم تحديد أي عميل.']);
        }

        $leads = Lead::query()
            ->accessibleTo($actor)
            ->with(['status.stage', 'assignedUser', 'phones', 'donationTypeRel'])
            ->whereIn('id', $leadIds)
            ->get();

        if ($leads->count() !== count($leadIds)) {
            abort(422, 'Invalid leads selected.');
        }

        $fileName = 'customers-export-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, [
                'ID',
                'الاسم',
                'الهاتف الأساسي',
                'المرحلة',
                'الحالة',
                'نوع التبرع',
                'دورة التبرع',
                'قيمة التبرع',
                'الموظف المسند',
                'تاريخ التواصل',
                'المتابعة القادمة',
                'تفاصيل الرد',
            ]);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->status?->stage?->name_ar ?? '—',
                    $lead->status?->name_ar ?? '—',
                    $lead->donation_type ?? '—',
                    $lead->donation_cycle ?? '—',
                    $lead->donation_value !== null ? number_format((float) $lead->donation_value, 2) : '—',
                    $lead->assignedUser?->name ?? $lead->assigned_employee ?? '—',
                    $lead->contact_date?->format('Y-m-d') ?? '—',
                    $lead->next_follow_up_at?->format('Y-m-d H:i') ?? '—',
                    $lead->response_details ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function quotationPreview(string $lead): BinaryFileResponse|RedirectResponse
    {
        $this->assertCrmV2Database();
        $leadRecord = Lead::query()->findOrFail((int) $lead);
        Gate::authorize('viewQuotation', $leadRecord);

        $path = trim((string) $leadRecord->quotation_file_path);
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Quotation file not found.');
        }

        return response()->download(Storage::disk('local')->path($path));
    }

    private function campaignForManualLead(Request $request): ?Campaign
    {
        $campaignId = $request->integer('campaign_id');
        if ($campaignId <= 0) {
            return null;
        }

        return Campaign::query()->find($campaignId);
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

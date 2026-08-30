<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadFormField;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use App\Support\CrmDatabaseGuard;
use App\Support\LeadFieldSchema;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    private const DONATION_CYCLES = [
        'one_time' => 'مرة واحدة',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
        'other' => 'أخرى',
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
            ->whereHas('stage', static fn ($q) => $q->where('is_active', true))
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

        $rawMin = $request->query('donation_min');
        $rawMax = $request->query('donation_max');

        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 150),
            'stage' => mb_substr(trim((string) $request->query('stage', '')), 0, 50),
            'status' => mb_substr(trim((string) $request->query('status', '')), 0, 50),
            'donation_type' => mb_substr(trim((string) $request->query('donation_type', '')), 0, 100),
            'donation_cycle' => mb_substr(trim((string) $request->query('donation_cycle', '')), 0, 50),
            'donation_min' => is_numeric($rawMin) ? (string) (float) $rawMin : '',
            'donation_max' => is_numeric($rawMax) ? (string) (float) $rawMax : '',
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

        // Configurable field filters managed through Settings -> Lead Fields.
        // Each field flagged "show in filter" contributes its own query param.
        $filterFields = LeadFieldSchema::filterable();
        foreach ($filterFields as $field) {
            $rawValue = $request->query($field->key);

            if ($field->type === LeadFormField::TYPE_MULTISELECT) {
                $filters[$field->key] = collect((array) ($rawValue ?? []))
                    ->map(static fn ($value) => mb_substr(trim((string) $value), 0, 150))
                    ->filter(static fn (string $value) => $value !== '')
                    ->unique()
                    ->take(20)
                    ->values()
                    ->all();

                continue;
            }

            $filters[$field->key] = mb_substr(trim((string) $rawValue), 0, 150);
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
                'leads.custom_fields',
            ])
            ->with([
                'status.stage:id,name_ar,color,position,code',
                'branch:id,name_ar,name_en,code',
                'assignedUser:id,name',
                'respondingUser:id,name',
                'phones:id,lead_id,phone,label,is_primary',
                'donationTypeRel:id,name_ar',
                'donationPurposeRel:id,name_ar',
            ]);
        if ($filters['q'] !== '') {
            $search = '%'.$filters['q'].'%';
            $searchableCustomFields = LeadFieldSchema::customFields()
                ->filter(static fn (LeadFormField $field) => in_array($field->type, [
                    LeadFormField::TYPE_TEXT,
                    LeadFormField::TYPE_EMAIL,
                    LeadFormField::TYPE_TEL,
                    LeadFormField::TYPE_URL,
                ], true) && LeadFieldSchema::isSafeKey($field->key));

            $query->where(function ($searchQuery) use ($search, $searchableCustomFields): void {
                $searchQuery
                    ->where('name', 'like', $search)
                    ->orWhere('company_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('donation_type', 'like', $search)
                    ->orWhere('donation_purpose', 'like', $search)
                    ->orWhere('response_details', 'like', $search)
                    ->orWhereHas('phones', static fn ($pq) => $pq->where('phone', 'like', $search));

                foreach ($searchableCustomFields as $customField) {
                    $searchQuery->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.".$customField->key."')) LIKE ?",
                        [$search],
                    );
                }
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

        // Filter: Donation Value Min / Max
        if ($filters['donation_min'] !== '') {
            $query->where('donation_value', '>=', (float) $filters['donation_min']);
        }
        if ($filters['donation_max'] !== '') {
            $query->where('donation_value', '<=', (float) $filters['donation_max']);
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

        // Filters: configurable fields (custom JSON values + whitelisted system columns)
        $this->applyFieldFilters($query, $filterFields, $filters);

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
            static fn ($value) => is_array($value) ? $value !== [] : ($value !== '' && $value !== 'latest')
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
            'filterFields' => $filterFields,
            'tableColumns' => LeadFieldSchema::tableColumns()
                ->where('is_system', false)
                ->values(),
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
            ->whereHas('stage', static fn ($q) => $q->where('is_active', true))
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
            'sources' => $sources,
            'assignedEmployee' => $assignedEmployee,
            'canAssignLead' => $canAssignLead,
            'assignableUsers' => $assignableUsers,
            'totalLeads' => $totalLeads,
            'campaign' => $campaign,
            'campaigns' => $campaigns,
            'customFields' => LeadFieldSchema::customFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCrmV2Database();

        $actor = $request->user();
        $campaign = $this->campaignForManualLead($request);
        $creatorName = trim((string) $actor->name);

        abort_if($creatorName === '', 403, 'Employee identity is required.');

        // Validation rules: system rules + rules generated from the
        // configurable lead form fields managed in Settings.
        $rules = LeadFieldSchema::validationRules();

        $validated = $request->validate($rules, [
            'phone.required' => 'رقم الهاتف الأساسي مطلوب.',
            'donation_value.numeric' => 'قيمة التبرع يجب أن تكون قيمة رقمية صحيحة.',
            'donation_value.min' => 'قيمة التبرع لا يمكن أن تكون سالبة.',
        ], LeadFieldSchema::validationAttributes());

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

        if (
            $status->code === 'donor'
            && (! is_numeric($validated['donation_value'] ?? null) || (float) $validated['donation_value'] <= 0)
        ) {
            return redirect()->back()->withInput()->withErrors([
                'donation_value' => 'يجب تسجيل قيمة التبرع عند إضافة العميل كمتبرع.',
            ]);
        }

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
            'custom_fields' => LeadFieldSchema::extractForStore((array) $request->input('custom_fields', [])),
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

            // 6. Always create an initial follow-up record so the lead
            //    has a complete audit trail from birth — regardless of
            //    whether response_details or dates were provided.
            $initialFollowup = LeadFollowup::query()->create([
                'branch_id' => $lead->branch_id,
                'lead_id' => $lead->id,
                'from_status_id' => null,
                'to_status_id' => $status->id,
                'employee_name' => $assignedEmployee,
                'user_id' => $assignee->id,
                'communication_type' => 'other',
                'outcome' => ! empty($lead->response_details)
                    ? 'إنشاء العميل: '.$lead->response_details
                    : 'تم إنشاء العميل',
                'next_follow_up_at' => $lead->next_follow_up_at,
                'followed_up_at' => $lead->contact_date ?? now(),
            ]);

            if ($status->code === 'donor') {
                Donation::query()->create([
                    'lead_id' => $lead->id,
                    'lead_followup_id' => $initialFollowup?->id,
                    'donation_type_id' => $lead->donation_type_id,
                    'donation_type' => trim((string) $lead->donation_type) ?: 'تبرع غير مصنف',
                    'amount' => $lead->donation_value,
                    'cycle' => trim((string) $lead->donation_cycle) ?: 'one_time',
                    'donated_at' => $lead->contact_date ?? now(),
                    'recorded_by_user_id' => $actor->id,
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
                'donationTypeRel:id,name_ar',
                'donationPurposeRel:id,name_ar',
                'donations.donationType:id,name_ar,name_en',
                'donations.recordedBy:id,name',
                'donations.followup:id,lead_id,user_id,employee_name,followed_up_at',
                'collectionCases.assignedCollector:id,name',
                'collectionCases.completedBy:id,name',
                'statusHistory.changedByUser:id,name',
                'statusHistory.fromStatus.stage',
                'statusHistory.toStatus.stage',
                'statusHistory.stageFieldValues.field',
                'followups.user:id,name',
                'followups.fromStatus.stage',
                'followups.toStatus.stage',
                'stageFieldValues.field',
                'stageFieldValues.stage',
                'stageFieldValues.createdByUser:id,name',
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
            $stageValuesForHistory = $history->stageFieldValues
                ->filter(static fn ($v) => $v->field === null || $v->field->show_in_history)
                ->map(static fn ($v) => [
                    'label' => $v->field ? $v->field->localizedLabel() : $v->field_key,
                    'value' => $v->formattedValue(),
                ])
                ->all();

            // Check if there's already a matching followup at approximately the same minute
            $alreadyIncluded = false;
            foreach ($timelineEvents as $key => $item) {
                if ($item['type'] === 'followup' && abs(($item['timestamp']?->timestamp ?? 0) - ($history->changed_at?->timestamp ?? 0)) < 60) {
                    $alreadyIncluded = true;
                    if (! empty($stageValuesForHistory)) {
                        $timelineEvents[$key]['stage_values'] = $stageValuesForHistory;
                    }
                    break;
                }
            }

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
                    'stage_values' => $stageValuesForHistory,
                ]);
            }
        }

        $stageHistoryGroups = $leadRecord->stageFieldValues
            ->filter(static fn ($val) => $val->field === null || $val->field->show_in_history)
            ->groupBy(static fn ($val) => ($val->pipeline_stage_id ?? 0) . '_' . ($val->lead_status_history_id ?? $val->created_at->format('Y-m-d_H:i')))
            ->map(static function ($group) {
                $first = $group->first();
                return [
                    'stage' => $first->stage,
                    'actor' => $first->createdByUser?->name ?? 'النظام',
                    'date' => $first->created_at,
                    'values' => $group->map(static fn ($v) => [
                        'label' => $v->field ? $v->field->localizedLabel() : $v->field_key,
                        'value' => $v->formattedValue(),
                    ]),
                ];
            })
            ->values();

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

        foreach (LeadFieldSchema::filterable() as $filterField) {
            if ($filterField->type === LeadFormField::TYPE_MULTISELECT) {
                continue;
            }

            $rawValue = $request->query($filterField->key, '');
            if (is_scalar($rawValue) && trim((string) $rawValue) !== '') {
                $backQuery[$filterField->key] = mb_substr(trim((string) $rawValue), 0, 150);
            }
        }

        return view('leads.show', [
            'lead' => $leadRecord,
            'timelineEvents' => $timelineEvents,
            'stageHistoryGroups' => $stageHistoryGroups,
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
            ->whereHas('stage', static fn ($q) => $q->where('is_active', true))
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

        $stageFieldsMap = PipelineStage::query()
            ->with(['activeFields'])
            ->get()
            ->keyBy('id')
            ->map(static fn ($st) => $st->activeFields);

        $currentStageValues = $leadRecord->stageFieldValues
            ->keyBy('field_key')
            ->map(static fn ($v) => $v->getTypedValue())
            ->all();

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
            'customFields' => LeadFieldSchema::customFields(),
            'stageFieldsMap' => $stageFieldsMap,
            'currentStageValues' => $currentStageValues,
        ]);
    }

    public function update(Request $request, string $lead): RedirectResponse
    {
        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with(['status.stage', 'phones'])
            ->findOrFail((int) $lead);

        Gate::authorize('update', $leadRecord);
        $actor = $request->user();

        // Validation rules: system rules + rules generated from the
        // configurable lead form fields managed in Settings.
        $rules = LeadFieldSchema::validationRules();

        $validated = $request->validate($rules, [
            'phone.required' => 'رقم الهاتف الأساسي مطلوب.',
            'donation_value.numeric' => 'قيمة التبرع يجب أن تكون رقمية.',
            'donation_value.min' => 'قيمة التبرع لا يمكن أن تكون سالبة.',
        ], LeadFieldSchema::validationAttributes());

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

        // Pipeline transitions are recorded only through the canonical
        // follow-up workflow. Generic profile edits preserve the status.
        $statusId = (int) $leadRecord->lead_status_id;

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

        if ($leadRecord->status?->code === 'not_interested') {
            $nextFollowUpAt = null;
        }
        $leadData = [
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string) $validated['phone']),
            'source' => trim((string) ($validated['source'] ?? $leadRecord->source)),
            'lead_status_id' => $statusId,
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
            'custom_fields' => LeadFieldSchema::mergeForUpdate($leadRecord->custom_fields, (array) $request->input('custom_fields', [])),
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

        $stageFieldsInput = (array) $request->input('stage_fields', []);
        $stageFieldsCustom = (array) $request->input('stage_fields_custom', []);

        DB::transaction(function () use ($leadRecord, $leadData, $validated, $actor, $stageFieldsInput, $stageFieldsCustom, $assignmentChanged, $assignee): void {
            // Capture field-level changes before the update
            $auditFields = [
                'name' => 'الاسم',
                'phone' => 'الهاتف',
                'source' => 'المصدر',
                'governorate' => 'المحافظة',
                'address' => 'العنوان',
                'donation_type' => 'نوع التبرع',
                'donation_value' => 'قيمة التبرع',
                'donation_cycle' => 'دورة التبرع',
            ];

            $fieldChanges = [];
            foreach ($auditFields as $field => $label) {
                $oldVal = (string) ($leadRecord->getOriginal($field) ?? '');
                $newVal = (string) ($leadData[$field] ?? '');
                if ($oldVal !== $newVal) {
                    $fieldChanges[] = [
                        'field' => $field,
                        'label' => $label,
                        'old' => $oldVal !== '' ? $oldVal : '----',
                        'new' => $newVal !== '' ? $newVal : '----',
                    ];
                }
            }

            if ($assignmentChanged && $assignee !== null) {
                $oldAssigneeName = $leadRecord->assignedUser?->name
                    ?? $leadRecord->assigned_employee
                    ?? '----';
                $fieldChanges[] = [
                    'field' => 'assigned_user_id',
                    'label' => 'الموظف المسؤول',
                    'old' => $oldAssigneeName,
                    'new' => $assignee->name,
                ];
            }

            $leadRecord->update($leadData);

            // Record an audit follow-up when any tracked field changed
            if ($fieldChanges !== []) {
                LeadFollowup::query()->create([
                    'lead_id' => $leadRecord->id,
                    'from_status_id' => $leadRecord->lead_status_id,
                    'to_status_id' => $leadRecord->lead_status_id,
                    'employee_name' => trim((string) $actor->name) ?: 'System',
                    'user_id' => $actor->id,
                    'communication_type' => 'other',
                    'outcome' => 'تعديل بيانات الملف الشخصي',
                    'field_changes' => $fieldChanges,
                    'followed_up_at' => now(),
                ]);
            }
            if (! empty($stageFieldsInput) && $leadRecord->status?->stage) {
                foreach ($stageFieldsInput as $k => $v) {
                    if (in_array(strtolower(trim((string) $v)), ['other', 'custom', 'أخرى'], true) && ! empty($stageFieldsCustom[$k])) {
                        $stageFieldsInput[$k] = $stageFieldsCustom[$k];
                    }
                }
                \App\Support\StageFieldSchema::persistValues(
                    $leadRecord,
                    $leadRecord->status->stage,
                    $stageFieldsInput,
                    null,
                    $actor
                );
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

        $receiptPaths = $leadRecord->donations()
            ->whereNotNull('receipt_path')
            ->pluck('receipt_path')
            ->filter()
            ->all();

        DB::transaction(static function () use ($leadRecord): void {
            $leadRecord->delete();
        });

        Storage::disk('local')->delete($receiptPaths);

        return redirect()
            ->route('v2.leads')
            ->with('success', 'تم حذف العميل بنجاح.');
    }

    public function exportSelected(Request $request): BinaryFileResponse|StreamedResponse|RedirectResponse
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

        $exportFields = LeadFieldSchema::exportColumns()
            ->where('is_system', false)
            ->values();

        $callback = function () use ($leads, $exportFields) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");
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
                ...$exportFields->map(static fn (LeadFormField $field) => $field->label())->all(),
            ]);

            foreach ($leads as $lead) {
                $leadCustomFields = is_array($lead->custom_fields) ? $lead->custom_fields : [];

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
                    ...$exportFields->map(static function (LeadFormField $field) use ($leadCustomFields): string {
                        $value = $leadCustomFields[$field->key] ?? null;

                        if ($value === null || $value === '' || $value === []) {
                            return '';
                        }

                        return LeadFieldSchema::formatValue($field, $value);
                    })->all(),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Applies the configurable field filters to the leads listing query.
     * Custom values live in leads.custom_fields JSON; whitelisted system
     * fields filter their real columns directly.
     */
    private function applyFieldFilters($query, $filterFields, array $filters): void
    {
        foreach ($filterFields as $field) {
            /** @var LeadFormField $field */
            $value = $filters[$field->key] ?? null;

            if ($field->type === LeadFormField::TYPE_MULTISELECT) {
                if (! is_array($value) || $value === []) {
                    continue;
                }
            } else {
                if (! is_string($value) || $value === '') {
                    continue;
                }
            }

            $isCustom = ! $field->is_system || $field->system_column === null;

            // Guard against unsafe identifiers before touching SQL.
            if ($isCustom && ! LeadFieldSchema::isSafeKey($field->key)) {
                continue;
            }

            if (! $isCustom && ($field->system_column === null || ! LeadFieldSchema::isSafeKey((string) $field->system_column))) {
                continue;
            }

            if ($isCustom) {
                switch ($field->type) {
                    case LeadFormField::TYPE_SELECT:
                        $query->where('custom_fields->'.$field->key, (string) $value);
                        break;

                    case LeadFormField::TYPE_MULTISELECT:
                        foreach ($value as $singleValue) {
                            $query->whereJsonContains('custom_fields->'.$field->key, $singleValue);
                        }
                        break;

                    case LeadFormField::TYPE_CHECKBOX:
                        $wantsTrue = in_array($value, ['1', 'true', 'yes'], true);
                        $query->whereRaw(
                            "JSON_EXTRACT(custom_fields, '$.".$field->key."') = CAST(? AS JSON)",
                            [$wantsTrue ? 'true' : 'false'],
                        );
                        break;

                    case LeadFormField::TYPE_NUMBER:
                        if (is_numeric($value)) {
                            $query->where('custom_fields->'.$field->key, (float) $value);
                        }
                        break;

                    case LeadFormField::TYPE_DATE:
                    case LeadFormField::TYPE_DATETIME:
                        if (strtotime($value) !== false) {
                            $query->whereRaw(
                                "DATE(JSON_UNQUOTE(JSON_EXTRACT(custom_fields, '$.".$field->key."'))) = ?",
                                [date('Y-m-d', (int) strtotime($value))],
                            );
                        }
                        break;

                    default:
                        $query->where('custom_fields->'.$field->key, 'like', '%'.$value.'%');
                        break;
                }

                continue;
            }

            $column = (string) $field->system_column;

            switch ($field->type) {
                case LeadFormField::TYPE_SELECT:
                case LeadFormField::TYPE_MULTISELECT:
                    $query->whereIn($column, is_array($value) ? $value : [$value]);
                    break;

                case LeadFormField::TYPE_CHECKBOX:
                    $query->where($column, (bool) in_array($value, ['1', 'true', 'yes'], true));
                    break;

                case LeadFormField::TYPE_NUMBER:
                    if (is_numeric($value)) {
                        $query->where($column, (float) $value);
                    }
                    break;

                case LeadFormField::TYPE_DATE:
                case LeadFormField::TYPE_DATETIME:
                    if (strtotime($value) !== false) {
                        $query->whereDate($column, date('Y-m-d', (int) strtotime($value)));
                    }
                    break;

                default:
                    $query->where($column, 'like', '%'.$value.'%');
                    break;
            }
        }
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

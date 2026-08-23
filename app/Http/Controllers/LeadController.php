<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Campaign;
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
    public function index(Request $request): View|RedirectResponse
    {

        $this->assertCrmV2Database();
        $user = $request->user();

        $statuses = LeadStatus::query()
            ->with([
                'stage:id,name_ar',
            ])
            ->withCount([
                'leads as leads_count' => static fn ($query) => $query
                    ->accessibleTo($user),
            ])
            ->orderBy('position')
            ->get();

        $filters = [
            'q' => mb_substr(
                trim((string) $request->query('q', '')),
                0,
                150
            ),
            'status' => mb_substr(
                trim((string) $request->query('status', '')),
                0,
                50
            ),
            'employee' => mb_substr(
                trim((string) $request->query('employee', '')),
                0,
                150
            ),
            'source' => mb_substr(
                trim((string) $request->query('source', '')),
                0,
                100
            ),
            'follow_up' => mb_substr(
                trim((string) $request->query('follow_up', '')),
                0,
                20
            ),
            'sort' => mb_substr(
                trim((string) $request->query('sort', 'latest')),
                0,
                20
            ),
        ];

        $statusCodes = $statuses
            ->pluck('code')
            ->all();

        if (
            $filters['status'] !== ''
            && ! in_array(
                $filters['status'],
                $statusCodes,
                true
            )
        ) {
            $filters['status'] = '';
        }

        $allowedFollowUpFilters = [
            '',
            'today',
            'upcoming',
            'overdue',
            'none',
        ];

        if (
            ! in_array(
                $filters['follow_up'],
                $allowedFollowUpFilters,
                true
            )
        ) {
            $filters['follow_up'] = '';
        }

        $allowedSorts = [
            'latest',
            'oldest',
            'name',
            'followup',
        ];

        if (
            ! in_array(
                $filters['sort'],
                $allowedSorts,
                true
            )
        ) {
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

        if (
            $filters['source'] !== ''
            && ! in_array(
                $filters['source'],
                $sources->all(),
                true
            )
        ) {
            $filters['source'] = '';
        }

        $query = Lead::query()
            ->accessibleTo($user)
            ->with([
                'status.stage:id,name_ar',
                'assignedUser:id,name',
            ]);

        if ($filters['q'] !== '') {
            $search = '%'.$filters['q'].'%';

            $query->where(
                function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', $search)
                        ->orWhere('company_name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('source', 'like', $search);
                }
            );
        }

        if ($filters['status'] !== '') {
            $query->whereHas(
                'status',
                function ($statusQuery) use ($filters): void {
                    $statusQuery->where(
                        'code',
                        $filters['status']
                    );
                }
            );
        }

        if ($filters['employee'] !== '') {
            $query->where(
                function ($employeeQuery) use ($filters): void {
                    $employeeQuery
                        ->whereHas(
                            'assignedUser',
                            function ($userQuery) use ($filters): void {
                                $userQuery->where(
                                    'name',
                                    $filters['employee']
                                );
                            }
                        )
                        ->orWhere(
                            function ($legacyQuery) use ($filters): void {
                                $legacyQuery
                                    ->whereNull('assigned_user_id')
                                    ->where(
                                        'assigned_employee',
                                        $filters['employee']
                                    );
                            }
                        );
                }
            );
        }

        if ($filters['source'] !== '') {
            $query->where(
                'source',
                $filters['source']
            );
        }

        switch ($filters['follow_up']) {
            case 'today':
                $query->whereBetween(
                    'next_follow_up_at',
                    [
                        now()->startOfDay(),
                        now()->endOfDay(),
                    ]
                );
                break;

            case 'upcoming':
                $query->where(
                    'next_follow_up_at',
                    '>',
                    now()->endOfDay()
                );
                break;

            case 'overdue':
                $query->where(
                    'next_follow_up_at',
                    '<',
                    now()->startOfDay()
                );
                break;

            case 'none':
                $query->whereNull('next_follow_up_at');
                break;
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query
                    ->orderBy('created_at')
                    ->orderBy('id');
                break;

            case 'name':
                $query
                    ->orderBy('name')
                    ->orderByDesc('id');
                break;

            case 'followup':
                $query
                    ->orderByRaw(
                        'next_follow_up_at IS NULL'
                    )
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('id');
                break;

            default:
                $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
                break;
        }

        $leads = $query
            ->paginate(20)
            ->withQueryString();

        $totalLeads = (int) $statuses
            ->sum('leads_count');

        $activeQuery = array_filter(
            $filters,
            static fn (string $value): bool => $value !== ''
                && $value !== 'latest'
        );

        $queryWithoutStatus = $activeQuery;

        unset($queryWithoutStatus['status']);

        return view(
            'leads.index',
            compact(
                'leads',
                'statuses',
                'employees',
                'sources',
                'filters',
                'activeQuery',
                'queryWithoutStatus',
                'totalLeads'
            )
        );
    }

    public function create(Request $request): View|RedirectResponse
    {

        $this->assertCrmV2Database();

        $actor = $request->user();
        $assignedEmployee = trim((string) $actor->name);

        abort_if(
            $assignedEmployee === '',
            403,
            'Employee identity is required.'
        );

        $canAssignLead = $actor->hasPermission(
            CrmPermission::LEADS_ASSIGN
        );
        $assignableUsers = LeadAssignment::assignableUsers($actor);
        $campaigns = Campaign::query()
            ->with('users:id')
            ->when(
                ! $actor->isSuperAdmin(),
                static fn ($query) => $actor->hasPermission(
                    CrmPermission::CAMPAIGNS_CREATE,
                )
                    ? $query->where('created_by_user_id', $actor->id)
                    : $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'starts_at']);
        $campaign = $this->campaignForManualLead($request);

        $statuses = LeadStatus::query()
            ->orderBy('position')
            ->get([
                'id',
                'code',
                'name_ar',
            ]);

        $sources = Lead::query()
            ->accessibleTo($actor)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $totalLeads = Lead::query()
            ->accessibleTo($actor)
            ->count();

        return view(
            'leads.create',
            compact(
                'statuses',
                'sources',
                'assignedEmployee',
                'canAssignLead',
                'assignableUsers',
                'totalLeads',
                'campaign',
                'campaigns'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {

        $this->assertCrmV2Database();

        $actor = $request->user();
        $campaign = $this->campaignForManualLead($request);
        $creatorName = trim((string) $actor->name);

        abort_if(
            $creatorName === '',
            403,
            'Employee identity is required.'
        );

        $statusInput = $request->validate(
            [
                'lead_status_id' => [
                    'required',
                    'integer',
                    'exists:lead_statuses,id',
                ],
            ],
            [
                'lead_status_id.required' => 'حالة العميل مطلوبة.',
                'lead_status_id.exists' => 'حالة العميل المختارة غير صحيحة.',
            ]
        );

        $status = LeadStatus::query()->findOrFail(
            (int) $statusInput['lead_status_id']
        );

        $quotationStageCodes = [
            'quotation',
            'discussion',
            'contract_closed',
            'execution',
        ];

        $isQuotationStage = in_array(
            $status->code,
            $quotationStageCodes,
            true
        );

        /*
         * CRM CREATE REQUIRED NEXT DATE V3 START
         *
         * Required for every new customer status
         * except no_answer and not_interested.
         */
        $requiresNextFollowUp = ! in_array(
            (string) $status->code,
            [
                'new',
                'no_answer',
                'not_interested',
                'execution',
            ],
            true
        );

        /* CRM CREATE REQUIRED NEXT DATE V3 END */

        /* CRM NEW EXECUTION NO FOLLOWUP V7 */

        $rules = [
            'first_name' => [
                'required',
                'string',
                'max:75',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:75',
            ],
            'phone' => [
                'required',
                'string',
                'max:50',
            ],
            'source' => [
                'required',
                'string',
                'max:100',
            ],
            'assigned_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'lead_status_id' => [
                'required',
                'integer',
                'exists:lead_statuses,id',
            ],
            'next_follow_up_at' => [
                $requiresNextFollowUp
                    ? 'required'
                    : 'nullable',
                'date_format:Y-m-d\TH:i',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'activity' => [
                'nullable',
                'string',
                'max:150',
            ],
            'governorate' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'users_count' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'branches_count' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'job_title' => [
                'nullable',
                'string',
                'max:150',
            ],
            'disinterest_reason' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'solution_type' => [
                'nullable',
                'in:call_center,erp',
            ],
            'lines_count' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
            'extensions' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'departments' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'quotation_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                'max:2048',
            ],
        ];

        if ($status->code === 'not_interested') {
            $rules['disinterest_reason'] = [
                'required',
                'string',
                'max:5000',
            ];
        }

        if ($isQuotationStage) {
            $rules['solution_type'] = [
                'required',
                'in:call_center,erp',
            ];

            $rules['quotation_file'] = [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                'max:2048',
            ];

            if (
                $request->input('solution_type')
                === 'call_center'
            ) {
                $rules['lines_count'] = [
                    'required',
                    'integer',
                    'min:1',
                    'max:1000000',
                ];

                $rules['extensions'] = [
                    'required',
                    'string',
                    'max:5000',
                ];
            }

            if (
                $request->input('solution_type')
                === 'erp'
            ) {
                $rules['departments'] = [
                    'required',
                    'string',
                    'max:5000',
                ];
            }
        }

        $validated = $request->validate(
            $rules,
            [
                'first_name.required' => 'اسم العميل الأول مطلوب.',
                'phone.required' => 'رقم الهاتف مطلوب.',
                'source.required' => 'المصدر مطلوب.',
                'next_follow_up_at.required' => 'حدد موعد المتابعة القادمة.',
                'next_follow_up_at.date_format' => 'موعد المتابعة القادمة غير صحيح.',
                'disinterest_reason.required' => 'سبب عدم الاهتمام مطلوب.',
                'solution_type.required' => 'نوع النظام مطلوب.',
                'lines_count.required' => 'عدد الخطوط مطلوب.',
                'extensions.required' => 'الملحقات مطلوبة.',
                'departments.required' => 'الأقسام مطلوبة.',
                'quotation_file.required' => 'ملف عرض السعر مطلوب.',
                'quotation_file.mimes' => 'صيغة ملف عرض السعر غير مدعومة.',
                'quotation_file.max' => 'حجم ملف عرض السعر يجب ألا يتجاوز 2 ميجابايت.',
            ]
        );
        $assignee = $actor;

        if (isset($validated['assigned_user_id'])) {
            $assignee = User::query()->findOrFail(
                (int) $validated['assigned_user_id']
            );

            abort_unless(
                LeadAssignment::canAssignTo($actor, $assignee),
                403,
                'You cannot assign leads to this user.'
            );
        }

        if ($campaign !== null) {
            abort_unless(
                $assignee->is($actor)
                || $campaign->users()->whereKey($assignee->id)->exists(),
                403,
                'The assignee must belong to this campaign.'
            );
        }

        $assignedEmployee = trim((string) $assignee->name);

        abort_if(
            $assignedEmployee === '',
            403,
            'Assignee identity is required.'
        );

        $nullableText = static function (
            mixed $value
        ): ?string {
            $value = trim((string) $value);

            return $value === ''
                ? null
                : $value;
        };

        $detailsEnabled = in_array(
            $status->code,
            [
                'interested',
                'no_answer',
                'meeting',
                'quotation',
                'discussion',
                'contract_closed',
                'execution',
            ],
            true
        );

        $quotationPath = null;

        if ($isQuotationStage) {
            $quotationPath = $request
                ->file('quotation_file')
                ->store(
                    'crm-v2/quotation-files',
                    'local'
                );

            if (! $quotationPath) {
                throw new \RuntimeException(
                    'Quotation file could not be stored.'
                );
            }
        }

        $firstName = trim(
            $validated['first_name']
        );

        $lastName = trim(
            (string) ($validated['last_name'] ?? '')
        );

        $fullName = preg_replace(
            '/\s+/u',
            ' ',
            trim($firstName.' '.$lastName)
        );

        $nextFollowUpAt = null;

        if ($requiresNextFollowUp) {
            $nextFollowUpAt =
                Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    (string)
                        $validated[
                            'next_follow_up_at'
                        ],
                    (string)
                        config(
                            'app.timezone',
                            'UTC'
                        )
                );
        }

        $leadData = [
            'lead_status_id' => $status->id,
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName === ''
                ? null
                : $lastName,
            'phone' => trim($validated['phone']),
            'source' => trim($validated['source']),
            'assigned_employee' => $assignedEmployee,
            'assigned_user_id' => $assignee->id,
            'created_by' => $creatorName,
            'created_by_user_id' => $actor->id,
            'quotation_sent' => $isQuotationStage,

            'company_name' => $detailsEnabled
                ? $nullableText(
                    $validated['company_name'] ?? null
                )
                : null,

            'activity' => $detailsEnabled
                ? $nullableText(
                    $validated['activity'] ?? null
                )
                : null,

            'governorate' => $detailsEnabled
                ? $nullableText(
                    $validated['governorate'] ?? null
                )
                : null,

            'address' => $detailsEnabled
                ? $nullableText(
                    $validated['address'] ?? null
                )
                : null,

            'users_count' => (
                $detailsEnabled
                && isset($validated['users_count'])
                && $validated['users_count'] !== ''
            )
                ? (int) $validated['users_count']
                : null,

            'branches_count' => (
                $detailsEnabled
                && isset($validated['branches_count'])
                && $validated['branches_count'] !== ''
            )
                ? (int) $validated['branches_count']
                : null,

            'job_title' => $detailsEnabled
                ? $nullableText(
                    $validated['job_title'] ?? null
                )
                : null,

            'disinterest_reason' => $status->code === 'not_interested'
                    ? $nullableText(
                        $validated[
                            'disinterest_reason'
                        ] ?? null
                    )
                    : null,

            'solution_type' => $isQuotationStage
                    ? $nullableText(
                        $validated['solution_type'] ?? null
                    )
                    : null,

            'lines_count' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'call_center'
            )
                ? (int) $validated['lines_count']
                : null,

            'extensions' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'call_center'
            )
                ? $nullableText(
                    $validated['extensions'] ?? null
                )
                : null,

            'departments' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'erp'
            )
                ? $nullableText(
                    $validated['departments'] ?? null
                )
                : null,

            'next_follow_up_at' => $nextFollowUpAt,

            'quotation_file_path' => $quotationPath,
        ];

        try {
            $createdLead = DB::transaction(
                static function () use ($campaign, $leadData): Lead {
                    $lead = Lead::query()->create($leadData);

                    $campaign?->leads()->attach($lead->id);

                    return $lead;
                }
            );
        } catch (\Throwable $exception) {
            if ($quotationPath !== null) {
                Storage::disk('local')
                    ->delete($quotationPath);
            }

            throw $exception;
        }

        if (
            $request->input('after_save')
                === 'followup'
            && $status->code === 'no_answer'
        ) {
            return redirect()
                ->route(
                    'v2.leads.followups.index',
                    $createdLead
                )
                ->with(
                    'success',
                    'تم حفظ بيانات العميل بنجاح. '
                    .'سجل المتابعة الآن.'
                );
        }

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
            ->with(
                'success',
                'تمت إضافة العميل بنجاح.'
            );
    }

    private function campaignForManualLead(Request $request): ?Campaign
    {
        $campaignId = $request->integer('campaign_id');

        if ($campaignId <= 0) {
            return null;
        }

        $campaign = Campaign::query()->findOrFail($campaignId);
        $actor = $request->user();

        abort_unless(
            $actor->isSuperAdmin()
            || (
                (int) $campaign->created_by_user_id === (int) $actor->id
                && $actor->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
            ),
            403,
        );

        return $campaign;
    }

    public function show(
        Request $request,
        string $lead
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with([
                'status.stage',
                'assignedUser:id,name',
            ])
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('view', $leadRecord);

        $latestFollowups = $request->user()->can(
            'leads.followups.view'
        )
            ? LeadFollowup::query()
                ->with([
                    'fromStatus.stage',
                    'toStatus.stage',
                    'user:id,name',
                ])
                ->where(
                    'lead_id',
                    $leadRecord->id
                )
                ->orderByDesc(
                    'followed_up_at'
                )
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        $followupCommunicationTypes = [
            'call' => 'اتصال هاتفي',
            'whatsapp' => 'واتساب',
            'meeting' => 'مقابلة',
            'email' => 'بريد إلكتروني',
            'other' => 'متابعة عامة',
        ];

        $statusColorValue = trim(
            (string) $leadRecord->status?->color
        );

        $statusColor = preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $statusColorValue
        ) === 1
            ? $statusColorValue
            : '#64748b';

        $quotationPath = trim(
            (string)
                $leadRecord->quotation_file_path
        );

        $quotationPrefix =
            'crm-v2/quotation-files/';

        $safeQuotationPath = (
            $quotationPath !== ''
            && str_starts_with(
                $quotationPath,
                $quotationPrefix
            )
            && ! str_contains(
                $quotationPath,
                '..'
            )
            && ! str_starts_with(
                $quotationPath,
                '/'
            )
        );

        $hasQuotationFile = false;

        if ($safeQuotationPath) {
            try {
                $disk = Storage::disk('local');

                if ($disk->exists($quotationPath)) {
                    $quotationDirectory = realpath(
                        $disk->path(
                            'crm-v2/quotation-files'
                        )
                    );

                    $absolutePath = realpath(
                        $disk->path(
                            $quotationPath
                        )
                    );

                    $hasQuotationFile = (
                        $quotationDirectory !== false
                        && $absolutePath !== false
                        && str_starts_with(
                            $absolutePath,
                            $quotationDirectory
                                .DIRECTORY_SEPARATOR
                        )
                    );
                }
            } catch (\Throwable) {
                $hasQuotationFile = false;
            }
        }

        $quotationFileName =
            $hasQuotationFile
                ? basename($quotationPath)
                : null;

        $solutionType = trim(
            (string) $leadRecord->solution_type
        );

        $solutionLabels = [
            'call_center' => 'Call Center',
            'erp' => 'ERP',
        ];

        $solutionTypeLabel =
            $solutionLabels[$solutionType]
            ?? (
                $solutionType !== ''
                    ? $solutionType
                    : 'غير محدد'
            );

        $phoneRaw = trim(
            (string) $leadRecord->phone
        );

        $phoneDigits = preg_replace(
            '/\D+/',
            '',
            $phoneRaw
        ) ?? '';

        $callPhone = preg_match(
            '/^[0-9]{2,20}$/',
            $phoneDigits
        ) === 1
            ? $phoneDigits
            : null;

        $whatsappPhone = null;

        if (
            str_starts_with(
                $phoneDigits,
                '0020'
            )
        ) {
            $whatsappPhone = substr(
                $phoneDigits,
                2
            );
        } elseif (
            preg_match(
                '/^01[0125][0-9]{8}$/',
                $phoneDigits
            ) === 1
        ) {
            $whatsappPhone =
                '20'.substr(
                    $phoneDigits,
                    1
                );
        } elseif (
            preg_match(
                '/^20[0-9]{10}$/',
                $phoneDigits
            ) === 1
        ) {
            $whatsappPhone =
                $phoneDigits;
        } elseif (
            preg_match(
                '/^[1-9][0-9]{7,14}$/',
                $phoneDigits
            ) === 1
        ) {
            $whatsappPhone =
                $phoneDigits;
        }

        $backQuery = [];

        $queryLimits = [
            'q' => 150,
            'status' => 50,
            'employee' => 150,
            'source' => 100,
            'follow_up' => 20,
            'sort' => 20,
        ];

        foreach (
            $queryLimits as $key => $limit
        ) {
            $rawValue = $request->query(
                $key,
                ''
            );

            if (! is_scalar($rawValue)) {
                continue;
            }

            $value = mb_substr(
                trim((string) $rawValue),
                0,
                $limit
            );

            if ($value !== '') {
                $backQuery[$key] = $value;
            }
        }

        $pageRaw = $request->query('page');

        $page = is_scalar($pageRaw)
            ? filter_var(
                (string) $pageRaw,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ]
            )
            : false;

        if ($page !== false) {
            $backQuery['page'] = (int) $page;
        }

        return view(
            'leads.show',
            [
                'lead' => $leadRecord,
                'latestFollowups' => $latestFollowups,
                'followupCommunicationTypes' => $followupCommunicationTypes,
                'statusColor' => $statusColor,
                'hasQuotationFile' => $hasQuotationFile,
                'quotationFileName' => $quotationFileName,
                'solutionTypeLabel' => $solutionTypeLabel,
                'callPhone' => $callPhone,
                'whatsappPhone' => $whatsappPhone,
                'backQuery' => $backQuery,
            ]
        );
    }

    public function edit(
        Request $request,
        string $lead
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with('assignedUser:id,name,username')
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('update', $leadRecord);

        $actor = $request->user();
        $canAssignLead = $actor->hasPermission(
            CrmPermission::LEADS_ASSIGN
        );
        $assignableUsers = $canAssignLead
            ? LeadAssignment::assignableUsers($actor)
            : collect();
        if (
            $canAssignLead
            && $leadRecord->assignedUser !== null
            && ! $assignableUsers->contains(
                'id',
                $leadRecord->assignedUser->id
            )
        ) {
            $assignableUsers->push($leadRecord->assignedUser);
        }

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $sources = Lead::query()
            ->accessibleTo($actor)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $quotationPath = trim(
            (string) $leadRecord->quotation_file_path
        );

        $hasQuotationFile = (
            $quotationPath !== ''
            && Storage::disk('local')->exists(
                $quotationPath
            )
        );

        $quotationFileName = $hasQuotationFile
            ? basename($quotationPath)
            : null;

        $quotationFileHelpText = $hasQuotationFile
            ? 'يوجد ملف حالي: '
                .$quotationFileName
                .' — ارفع ملفًا جديدًا لاستبداله.'
            : 'لا يوجد ملف حالي — الحد الأقصى 2MB.';

        return view(
            'leads.edit',
            [
                'lead' => $leadRecord,
                'statuses' => $statuses,
                'sources' => $sources,
                'canAssignLead' => $canAssignLead,
                'assignableUsers' => $assignableUsers,
                'assignedEmployee' => $leadRecord->assignedUser?->name
                    ?? $leadRecord->assigned_employee
                    ?: 'غير مسند',
                'hasQuotationFile' => $hasQuotationFile,
                'quotationFileName' => $quotationFileName,
                'quotationFileHelpText' => $quotationFileHelpText,
            ]
        );
    }

    public function exportSelected(
        Request $request
    ): BinaryFileResponse|RedirectResponse {

        $this->assertCrmV2Database();

        abort_unless(
            class_exists(\ZipArchive::class)
            && class_exists(\XMLWriter::class),
            500,
            'XLSX export support is unavailable.'
        );

        $validated = $request->validate(
            [
                'lead_ids' => [
                    'required',
                    'array',
                    'min:1',
                    'max:1000',
                ],
                'lead_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists(
                        'leads',
                        'id'
                    ),
                ],
            ],
            [
                'lead_ids.required' => 'اختر عميلًا واحدًا على الأقل.',
                'lead_ids.array' => 'قائمة العملاء المختارة غير صحيحة.',
                'lead_ids.min' => 'اختر عميلًا واحدًا على الأقل.',
                'lead_ids.max' => 'لا يمكن تصدير أكثر من 1000 عميل مرة واحدة.',
                'lead_ids.*.integer' => 'أحد العملاء المختارين غير صحيح.',
                'lead_ids.*.distinct' => 'تم تكرار أحد العملاء المختارين.',
                'lead_ids.*.exists' => 'أحد العملاء المختارين لم يعد موجودًا.',
            ]
        );

        $selectedIds = collect(
            $validated['lead_ids']
        )
            ->map(
                static fn (mixed $id): int => (int) $id
            )
            ->unique()
            ->values();

        $leadsById = Lead::query()
            ->accessibleTo($request->user())
            ->with([
                'status.stage',
                'assignedUser:id,name',
            ])
            ->whereIn(
                'id',
                $selectedIds->all()
            )
            ->get()
            ->keyBy('id');

        $selectedLeads = $selectedIds
            ->map(
                static fn (int $id): ?Lead => $leadsById->get($id)
            )
            ->filter()
            ->values();

        abort_unless(
            $selectedLeads->count()
                === $selectedIds->count(),
            422,
            'One or more selected leads could not be loaded.'
        );

        $headers = [
            'رقم العميل',
            'اسم العميل',
            'الهاتف',
            'البريد الإلكتروني',
            'الشركة',
            'النشاط',
            'المحافظة',
            'العنوان',
            'عدد المستخدمين',
            'عدد الفروع',
            'المسمى الوظيفي',
            'المصدر',
            'الحالة',
            'المرحلة',
            'الموظف المسؤول',
            'المتابعة القادمة',
            'نوع النظام',
            'عدد الخطوط',
            'الملحقات',
            'الأقسام',
            'عرض السعر مرسل',
            'ملف عرض السعر',
            'سبب عدم الاهتمام',
            'ملاحظات',
            'أنشأ بواسطة',
            'تاريخ الإضافة',
            'آخر تحديث',
        ];

        $solutionLabels = [
            'call_center' => 'Call Center',
            'erp' => 'ERP',
        ];

        $formatDate = static function (
            mixed $value
        ): string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(
                    'Y-m-d H:i'
                );
            }

            if (is_string($value)) {
                return trim($value);
            }

            return '';
        };

        $rows = $selectedLeads
            ->map(
                static function (
                    Lead $lead
                ) use (
                    $formatDate,
                    $solutionLabels
                ): array {
                    $quotationPath = trim(
                        (string)
                            $lead->quotation_file_path
                    );

                    $quotationFileState =
                        'غير مرفوع';

                    if ($quotationPath !== '') {
                        try {
                            $quotationFileState =
                                Storage::disk('local')
                                    ->exists(
                                        $quotationPath
                                    )
                                    ? 'موجود'
                                    : 'المسار مسجل والملف غير موجود';
                        } catch (\Throwable) {
                            $quotationFileState =
                                'تعذر التحقق من الملف';
                        }
                    }

                    $solutionType = trim(
                        (string) $lead->solution_type
                    );

                    return [
                        (int) $lead->id,
                        (string) $lead->name,
                        (string) $lead->phone,
                        (string) $lead->email,
                        (string) $lead->company_name,
                        (string) $lead->activity,
                        (string) $lead->governorate,
                        (string) $lead->address,

                        $lead->users_count === null
                            ? ''
                            : (int) $lead->users_count,

                        $lead->branches_count === null
                            ? ''
                            : (int) $lead->branches_count,

                        (string) $lead->job_title,
                        (string) $lead->source,

                        (string) (
                            $lead->status?->name_ar
                            ?? ''
                        ),

                        (string) (
                            $lead->status?->stage
                                ?->name_ar
                            ?? ''
                        ),

                        (string) (
                            $lead->assignedUser?->name
                            ?? $lead->assigned_employee
                        ),

                        $formatDate(
                            $lead->next_follow_up_at
                        ),

                        $solutionLabels[
                            $solutionType
                        ] ?? $solutionType,

                        $lead->lines_count === null
                            ? ''
                            : (int) $lead->lines_count,

                        (string) $lead->extensions,
                        (string) $lead->departments,

                        $lead->quotation_sent
                            ? 'نعم'
                            : 'لا',

                        $quotationFileState,

                        (string)
                            $lead->disinterest_reason,

                        (string) $lead->notes,
                        (string) $lead->created_by,

                        $formatDate(
                            $lead->created_at
                        ),

                        $formatDate(
                            $lead->updated_at
                        ),
                    ];
                }
            )
            ->all();

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'crm-v2-selected-leads-'
        );

        if (
            ! is_string($temporaryFile)
            || $temporaryFile === ''
        ) {
            throw new \RuntimeException(
                'Could not create the temporary export file.'
            );
        }

        try {
            $this->writeSelectedLeadsWorkbook(
                $temporaryFile,
                $headers,
                $rows
            );

            $downloadName =
                'crm-v2-selected-leads-'
                .now()->format('Ymd-His')
                .'.xlsx';

            return response()
                ->download(
                    $temporaryFile,
                    $downloadName,
                    [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'Cache-Control' => 'private, no-store, max-age=0',
                        'Pragma' => 'no-cache',
                        'X-Content-Type-Options' => 'nosniff',
                    ]
                )
                ->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }

            throw $exception;
        }
    }

    public function quotationPreview(
        string $lead
    ): BinaryFileResponse|RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()->findOrFail(
            (int) $lead
        );
        Gate::authorize('viewQuotation', $leadRecord);

        $quotationPath = trim(
            (string) $leadRecord->quotation_file_path
        );

        $expectedPrefix =
            'crm-v2/quotation-files/';

        $isSafeRelativePath = (
            $quotationPath !== ''
            && str_starts_with(
                $quotationPath,
                $expectedPrefix
            )
            && ! str_contains(
                $quotationPath,
                '..'
            )
            && ! str_starts_with(
                $quotationPath,
                '/'
            )
        );

        abort_unless(
            $isSafeRelativePath,
            404,
            'Quotation file was not found.'
        );

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($quotationPath),
            404,
            'Quotation file was not found.'
        );

        $quotationDirectory = realpath(
            $disk->path(
                'crm-v2/quotation-files'
            )
        );

        $absolutePath = realpath(
            $disk->path($quotationPath)
        );

        $isInsideQuotationDirectory = (
            $quotationDirectory !== false
            && $absolutePath !== false
            && str_starts_with(
                $absolutePath,
                $quotationDirectory
                    .DIRECTORY_SEPARATOR
            )
        );

        abort_unless(
            $isInsideQuotationDirectory,
            404,
            'Quotation file was not found.'
        );

        $extension = strtolower(
            pathinfo(
                $absolutePath,
                PATHINFO_EXTENSION
            )
        );

        $allowedExtensions = [
            'pdf',
            'png',
            'jpg',
            'jpeg',
            'doc',
            'docx',
            'xls',
            'xlsx',
        ];

        abort_unless(
            in_array(
                $extension,
                $allowedExtensions,
                true
            ),
            415,
            'Quotation file type is not supported.'
        );

        try {
            $mimeType = $disk->mimeType(
                $quotationPath
            );
        } catch (\Throwable) {
            $mimeType = null;
        }

        if (
            ! is_string($mimeType)
            || trim($mimeType) === ''
        ) {
            $mimeType =
                'application/octet-stream';
        }

        $fileName = basename($absolutePath);

        return response()->file(
            $absolutePath,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'
                    .addcslashes(
                        $fileName,
                        '"\\'
                    )
                    .'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ]
        );
    }

    public function update(
        Request $request,
        string $lead
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()->findOrFail(
            (int) $lead
        );
        Gate::authorize('update', $leadRecord);
        $actor = $request->user();

        $status = LeadStatus::query()
            ->findOrFail(
                (int)
                    $leadRecord->lead_status_id
            );

        $businessStatusCodes = [
            'interested',
            'no_answer',
            'meeting',
            'quotation',
            'discussion',
            'contract_closed',
            'execution',
        ];

        $quotationStageCodes = [
            'quotation',
            'discussion',
            'contract_closed',
            'execution',
        ];

        $hasBusinessDetails = in_array(
            $status->code,
            $businessStatusCodes,
            true
        );

        $isQuotationStage = in_array(
            $status->code,
            $quotationStageCodes,
            true
        );

        $currentQuotationPath = trim(
            (string) $leadRecord->quotation_file_path
        );

        $hasCurrentQuotationFile = (
            $currentQuotationPath !== ''
            && Storage::disk('local')->exists(
                $currentQuotationPath
            )
        );

        $solutionTypeInput = trim(
            (string) $request->input(
                'solution_type',
                ''
            )
        );

        $validated = $request->validate(
            [
                'first_name' => [
                    'required',
                    'string',
                    'max:75',
                ],
                'last_name' => [
                    'nullable',
                    'string',
                    'max:75',
                ],
                'phone' => [
                    'required',
                    'string',
                    'max:50',
                ],
                'source' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'assigned_user_id' => [
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],
                'company_name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'activity' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'governorate' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'address' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'users_count' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:1000000',
                ],
                'branches_count' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:1000000',
                ],
                'job_title' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'disinterest_reason' => [
                    Rule::requiredIf(
                        $status->code ===
                            'not_interested'
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'solution_type' => [
                    Rule::requiredIf(
                        $isQuotationStage
                    ),
                    'nullable',
                    Rule::in([
                        'call_center',
                        'erp',
                    ]),
                ],
                'lines_count' => [
                    Rule::requiredIf(
                        $isQuotationStage
                        && $solutionTypeInput ===
                            'call_center'
                    ),
                    'nullable',
                    'integer',
                    'min:1',
                    'max:1000000',
                ],
                'extensions' => [
                    Rule::requiredIf(
                        $isQuotationStage
                        && $solutionTypeInput ===
                            'call_center'
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'departments' => [
                    Rule::requiredIf(
                        $isQuotationStage
                        && $solutionTypeInput === 'erp'
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'quotation_file' => [
                    Rule::requiredIf(
                        $isQuotationStage
                        && ! $hasCurrentQuotationFile
                    ),
                    'nullable',
                    'file',
                    'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                    'max:2048',
                ],
            ],
            [
                'first_name.required' => 'اسم العميل الأول مطلوب.',
                'phone.required' => 'رقم الهاتف مطلوب.',
                'source.required' => 'المصدر مطلوب.',
                'disinterest_reason.required' => 'سبب عدم الاهتمام مطلوب.',
                'solution_type.required' => 'نوع النظام مطلوب.',
                'lines_count.required' => 'عدد الخطوط مطلوب.',
                'extensions.required' => 'تفاصيل الملحقات مطلوبة.',
                'departments.required' => 'الأقسام المطلوبة مطلوبة.',
                'quotation_file.required' => 'ملف عرض السعر مطلوب.',
                'quotation_file.max' => 'الحد الأقصى لملف عرض السعر 2MB.',
                'quotation_file.mimes' => 'صيغة ملف عرض السعر غير مدعومة.',
            ]
        );
        $assignmentChanged = false;
        $assignee = null;

        if (isset($validated['assigned_user_id'])) {
            $assignee = User::query()->findOrFail(
                (int) $validated['assigned_user_id']
            );
            $assignmentChanged = (int) $leadRecord->assigned_user_id
                !== (int) $assignee->id;

            if ($assignmentChanged) {
                abort_unless(
                    $actor->hasPermission(CrmPermission::LEADS_ASSIGN)
                    && LeadAssignment::canAssignTo($actor, $assignee),
                    403,
                    'You cannot reassign this lead to the selected user.'
                );
            }
        }

        $nullableText = static function (
            mixed $value
        ): ?string {
            if (! is_string($value)) {
                return null;
            }

            $value = trim($value);

            return $value === ''
                ? null
                : $value;
        };

        $integerOrNull = static function (
            mixed $value
        ): ?int {
            if ($value === null || $value === '') {
                return null;
            }

            return (int) $value;
        };

        $firstName = trim(
            (string) $validated['first_name']
        );

        $lastName = $nullableText(
            $validated['last_name'] ?? null
        );

        $fullName = trim(
            $firstName.' '.($lastName ?? '')
        );

        $newQuotationPath = null;

        if ($request->hasFile('quotation_file')) {
            $storedPath = $request
                ->file('quotation_file')
                ->store(
                    'crm-v2/quotation-files',
                    'local'
                );

            if (
                ! is_string($storedPath)
                || trim($storedPath) === ''
            ) {
                throw new \RuntimeException(
                    'Quotation file storage failed.'
                );
            }

            $newQuotationPath = $storedPath;
        }

        $leadData = [
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim(
                (string) $validated['phone']
            ),
            'source' => trim(
                (string) $validated['source']
            ),

            'company_name' => $hasBusinessDetails
                ? $nullableText(
                    $validated['company_name'] ?? null
                )
                : null,

            'activity' => $hasBusinessDetails
                ? $nullableText(
                    $validated['activity'] ?? null
                )
                : null,

            'governorate' => $hasBusinessDetails
                ? $nullableText(
                    $validated['governorate'] ?? null
                )
                : null,

            'address' => $hasBusinessDetails
                ? $nullableText(
                    $validated['address'] ?? null
                )
                : null,

            'users_count' => $hasBusinessDetails
                ? $integerOrNull(
                    $validated['users_count'] ?? null
                )
                : null,

            'branches_count' => $hasBusinessDetails
                ? $integerOrNull(
                    $validated['branches_count'] ?? null
                )
                : null,

            'job_title' => $hasBusinessDetails
                ? $nullableText(
                    $validated['job_title'] ?? null
                )
                : null,

            'disinterest_reason' => $status->code === 'not_interested'
                    ? $nullableText(
                        $validated[
                            'disinterest_reason'
                        ] ?? null
                    )
                    : null,

            'solution_type' => $isQuotationStage
                ? $nullableText(
                    $validated['solution_type'] ?? null
                )
                : null,

            'lines_count' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'call_center'
            )
                ? (int) $validated['lines_count']
                : null,

            'extensions' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'call_center'
            )
                ? $nullableText(
                    $validated['extensions'] ?? null
                )
                : null,

            'departments' => (
                $isQuotationStage
                && ($validated['solution_type'] ?? null)
                    === 'erp'
            )
                ? $nullableText(
                    $validated['departments'] ?? null
                )
                : null,

            'quotation_sent' => $isQuotationStage,

            'quotation_file_path' => $newQuotationPath
                ?? (
                    $hasCurrentQuotationFile
                        ? $currentQuotationPath
                        : null
                ),
        ];
        if ($assignmentChanged && $assignee !== null) {
            $assignedEmployee = trim((string) $assignee->name);

            abort_if(
                $assignedEmployee === '',
                403,
                'Assignee identity is required.'
            );

            $leadData['assigned_user_id'] = $assignee->id;
            $leadData['assigned_employee'] = $assignedEmployee;
        }

        try {
            DB::transaction(
                static function () use (
                    $leadRecord,
                    $leadData
                ): void {
                    $leadRecord->update($leadData);
                }
            );
        } catch (\Throwable $exception) {
            if ($newQuotationPath !== null) {
                Storage::disk('local')->delete(
                    $newQuotationPath
                );
            }

            throw $exception;
        }

        if (
            $newQuotationPath !== null
            && $currentQuotationPath !== ''
            && $currentQuotationPath
                !== $newQuotationPath
            && Storage::disk('local')->exists(
                $currentQuotationPath
            )
        ) {
            Storage::disk('local')->delete(
                $currentQuotationPath
            );
        }

        return redirect()
            ->route('v2.leads')
            ->with(
                'success',
                'تم تحديث بيانات العميل '
                .$fullName
                .' بنجاح.'
            );
    }

    public function destroy(
        string $lead
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()->findOrFail(
            (int) $lead
        );
        Gate::authorize('delete', $leadRecord);

        $leadName = trim(
            (string) $leadRecord->name
        );

        $quotationPath = trim(
            (string) $leadRecord->quotation_file_path
        );

        DB::transaction(
            static function () use (
                $leadRecord
            ): void {
                $leadRecord->delete();
            }
        );

        if (
            $quotationPath !== ''
            && Storage::disk('local')->exists(
                $quotationPath
            )
        ) {
            Storage::disk('local')->delete(
                $quotationPath
            );
        }

        return redirect()
            ->route('v2.leads')
            ->with(
                'success',
                'تم حذف العميل '
                .$leadName
                .' بنجاح.'
            );
    }

    private function writeSelectedLeadsWorkbook(
        string $path,
        array $headers,
        array $rows
    ): void {
        if ($headers === []) {
            throw new \InvalidArgumentException(
                'Workbook headers cannot be empty.'
            );
        }

        $lastColumn = $this->xlsxColumnName(
            count($headers)
        );

        $lastRow = count($rows) + 1;

        $worksheetWriter = new \XMLWriter;

        $worksheetWriter->openMemory();

        $worksheetWriter->startDocument(
            '1.0',
            'UTF-8',
            'yes'
        );

        $worksheetWriter->startElement(
            'worksheet'
        );

        $worksheetWriter->writeAttribute(
            'xmlns',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );

        $worksheetWriter->startElement(
            'dimension'
        );

        $worksheetWriter->writeAttribute(
            'ref',
            'A1:'.$lastColumn.$lastRow
        );

        $worksheetWriter->endElement();

        $worksheetWriter->startElement(
            'sheetViews'
        );

        $worksheetWriter->startElement(
            'sheetView'
        );

        $worksheetWriter->writeAttribute(
            'workbookViewId',
            '0'
        );

        $worksheetWriter->writeAttribute(
            'rightToLeft',
            '1'
        );

        $worksheetWriter->startElement(
            'pane'
        );

        $worksheetWriter->writeAttribute(
            'ySplit',
            '1'
        );

        $worksheetWriter->writeAttribute(
            'topLeftCell',
            'A2'
        );

        $worksheetWriter->writeAttribute(
            'activePane',
            'bottomLeft'
        );

        $worksheetWriter->writeAttribute(
            'state',
            'frozen'
        );

        $worksheetWriter->endElement();
        $worksheetWriter->endElement();
        $worksheetWriter->endElement();

        $worksheetWriter->startElement(
            'sheetFormatPr'
        );

        $worksheetWriter->writeAttribute(
            'defaultRowHeight',
            '20'
        );

        $worksheetWriter->endElement();

        $widths = [
            10,
            24,
            16,
            28,
            22,
            20,
            16,
            30,
            14,
            12,
            20,
            16,
            16,
            16,
            20,
            20,
            16,
            12,
            28,
            28,
            15,
            24,
            30,
            35,
            20,
            20,
            20,
        ];

        $worksheetWriter->startElement('cols');

        foreach (
            array_keys($headers) as $columnIndex
        ) {
            $excelColumn = $columnIndex + 1;

            $worksheetWriter->startElement(
                'col'
            );

            $worksheetWriter->writeAttribute(
                'min',
                (string) $excelColumn
            );

            $worksheetWriter->writeAttribute(
                'max',
                (string) $excelColumn
            );

            $worksheetWriter->writeAttribute(
                'width',
                (string) (
                    $widths[$columnIndex]
                    ?? 18
                )
            );

            $worksheetWriter->writeAttribute(
                'customWidth',
                '1'
            );

            $worksheetWriter->endElement();
        }

        $worksheetWriter->endElement();

        $worksheetWriter->startElement(
            'sheetData'
        );

        $workbookRows = array_merge(
            [$headers],
            $rows
        );

        foreach (
            $workbookRows as $rowIndex => $row
        ) {
            $excelRow = $rowIndex + 1;
            $isHeader = $excelRow === 1;

            $worksheetWriter->startElement(
                'row'
            );

            $worksheetWriter->writeAttribute(
                'r',
                (string) $excelRow
            );

            if ($isHeader) {
                $worksheetWriter->writeAttribute(
                    'ht',
                    '26'
                );

                $worksheetWriter->writeAttribute(
                    'customHeight',
                    '1'
                );
            }

            foreach (
                array_keys($headers) as $columnIndex
            ) {
                $cellReference =
                    $this->xlsxColumnName(
                        $columnIndex + 1
                    )
                    .$excelRow;

                $value =
                    $row[$columnIndex]
                    ?? '';

                $worksheetWriter->startElement(
                    'c'
                );

                $worksheetWriter->writeAttribute(
                    'r',
                    $cellReference
                );

                $worksheetWriter->writeAttribute(
                    's',
                    $isHeader
                        ? '1'
                        : '2'
                );

                if (
                    ! $isHeader
                    && (
                        is_int($value)
                        || is_float($value)
                    )
                ) {
                    $worksheetWriter->writeAttribute(
                        't',
                        'n'
                    );

                    $worksheetWriter->startElement(
                        'v'
                    );

                    $worksheetWriter->text(
                        (string) $value
                    );

                    $worksheetWriter->endElement();
                } else {
                    $worksheetWriter->writeAttribute(
                        't',
                        'inlineStr'
                    );

                    $worksheetWriter->startElement(
                        'is'
                    );

                    $worksheetWriter->startElement(
                        't'
                    );

                    $worksheetWriter->writeAttribute(
                        'xml:space',
                        'preserve'
                    );

                    $worksheetWriter->text(
                        (string) $value
                    );

                    $worksheetWriter->endElement();
                    $worksheetWriter->endElement();
                }

                $worksheetWriter->endElement();
            }

            $worksheetWriter->endElement();
        }

        $worksheetWriter->endElement();

        $worksheetWriter->startElement(
            'autoFilter'
        );

        $worksheetWriter->writeAttribute(
            'ref',
            'A1:'.$lastColumn.$lastRow
        );

        $worksheetWriter->endElement();

        $worksheetWriter->startElement(
            'pageMargins'
        );

        foreach (
            [
                'left' => '0.3',
                'right' => '0.3',
                'top' => '0.5',
                'bottom' => '0.5',
                'header' => '0.2',
                'footer' => '0.2',
            ] as $name => $value
        ) {
            $worksheetWriter->writeAttribute(
                $name,
                $value
            );
        }

        $worksheetWriter->endElement();
        $worksheetWriter->endElement();
        $worksheetWriter->endDocument();

        $worksheetXml =
            $worksheetWriter->outputMemory();

        $contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
 <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
 <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
 <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;

        $rootRelationships = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
 <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;

        $workbook = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <bookViews>
  <workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="15000"/>
 </bookViews>
 <sheets>
  <sheet name="العملاء المحددون" sheetId="1" r:id="rId1"/>
 </sheets>
</workbook>
XML;

        $workbookRelationships = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;

        $styles = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
 <fonts count="2">
  <font>
   <sz val="11"/>
   <name val="Calibri"/>
   <family val="2"/>
  </font>
  <font>
   <b/>
   <color rgb="FFFFFFFF"/>
   <sz val="11"/>
   <name val="Arial"/>
   <family val="2"/>
  </font>
 </fonts>
 <fills count="3">
  <fill>
   <patternFill patternType="none"/>
  </fill>
  <fill>
   <patternFill patternType="gray125"/>
  </fill>
  <fill>
   <patternFill patternType="solid">
    <fgColor rgb="FF1F4E78"/>
    <bgColor indexed="64"/>
   </patternFill>
  </fill>
 </fills>
 <borders count="1">
  <border>
   <left/>
   <right/>
   <top/>
   <bottom/>
   <diagonal/>
  </border>
 </borders>
 <cellStyleXfs count="1">
  <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
 </cellStyleXfs>
 <cellXfs count="3">
  <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
  <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
   <alignment horizontal="center" vertical="center" wrapText="1"/>
  </xf>
  <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">
   <alignment horizontal="right" vertical="center" wrapText="1"/>
  </xf>
 </cellXfs>
 <cellStyles count="1">
  <cellStyle name="Normal" xfId="0" builtinId="0"/>
 </cellStyles>
 <dxfs count="0"/>
 <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
XML;

        $applicationProperties = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
 <Application>CRM v2</Application>
 <AppVersion>1.0</AppVersion>
</Properties>
XML;

        $timestamp = gmdate(
            'Y-m-d\TH:i:s\Z'
        );

        $coreProperties =
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties '
            .'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>CRM v2</dc:creator>'
            .'<cp:lastModifiedBy>CRM v2</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'
            .$timestamp
            .'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'
            .$timestamp
            .'</dcterms:modified>'
            .'</cp:coreProperties>';

        $zip = new \ZipArchive;

        $openResult = $zip->open(
            $path,
            \ZipArchive::CREATE
            | \ZipArchive::OVERWRITE
        );

        if ($openResult !== true) {
            throw new \RuntimeException(
                'Could not create the XLSX archive.'
            );
        }

        $addFile = static function (
            \ZipArchive $archive,
            string $name,
            string $contents
        ): void {
            if (
                ! $archive->addFromString(
                    $name,
                    $contents
                )
            ) {
                throw new \RuntimeException(
                    'Could not add XLSX part: '
                    .$name
                );
            }
        };

        try {
            $addFile(
                $zip,
                '[Content_Types].xml',
                $contentTypes
            );

            $addFile(
                $zip,
                '_rels/.rels',
                $rootRelationships
            );

            $addFile(
                $zip,
                'xl/workbook.xml',
                $workbook
            );

            $addFile(
                $zip,
                'xl/_rels/workbook.xml.rels',
                $workbookRelationships
            );

            $addFile(
                $zip,
                'xl/styles.xml',
                $styles
            );

            $addFile(
                $zip,
                'xl/worksheets/sheet1.xml',
                $worksheetXml
            );

            $addFile(
                $zip,
                'docProps/core.xml',
                $coreProperties
            );

            $addFile(
                $zip,
                'docProps/app.xml',
                $applicationProperties
            );

            if (! $zip->close()) {
                throw new \RuntimeException(
                    'Could not finalize the XLSX archive.'
                );
            }
        } catch (\Throwable $exception) {
            $zip->close();

            if (is_file($path)) {
                @unlink($path);
            }

            throw $exception;
        }
    }

    private function xlsxColumnName(
        int $index
    ): string {
        if ($index < 1) {
            throw new \InvalidArgumentException(
                'XLSX column index must be positive.'
            );
        }

        $columnName = '';

        while ($index > 0) {
            $index--;

            $columnName =
                chr(65 + ($index % 26))
                .$columnName;

            $index = intdiv(
                $index,
                26
            );
        }

        return $columnName;
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

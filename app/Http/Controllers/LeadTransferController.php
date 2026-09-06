<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use XMLWriter;
use ZipArchive;

class LeadTransferController extends Controller
{
    private const MAX_IMPORT_ROWS = 1000;

    private const MAX_EXPORT_ROWS = 5000;

    private const PREVIEW_LIFETIME_SECONDS = 7200;

    public function importIndex(
        Request $request
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $campaign = $this->campaignFromRequest(
            $request,
            $request->query('campaign')
        );
        $assignableUsers = LeadAssignment::assignableUsers($request->user())
            ->loadMissing('groups:id,name');

        $groups = Group::query()
            ->whereHas('users', static fn ($q) => $q->where('is_active', true))
            ->get(['id', 'name']);

        $activeLeadsCounts = Lead::query()
            ->whereIn('assigned_user_id', $assignableUsers->pluck('id'))
            ->selectRaw('assigned_user_id, count(*) as count')
            ->groupBy('assigned_user_id')
            ->pluck('count', 'assigned_user_id')
            ->toArray();

        return view(
            'leads.import',
            [
                'statuses' => $this->statuses(),
                'preview' => null,
                'campaign' => $campaign,
                'assignableUsers' => $assignableUsers,
                'groups' => $groups,
                'activeLeadsCounts' => $activeLeadsCounts,
                'selectedDistributionMode' => 'round_robin',
                'selectedDistributionUserIds' => [],
                'selectedSingleUserId' => $request->user()->id,
            ]
        );
    }

    public function importTemplate(): BinaryFileResponse|RedirectResponse
    {

        $this->assertCrmV2Database();
        $this->assertXlsxSupport();

        return $this->downloadWorkbook(
            array_values(
                $this->importTemplateColumns()
            ),
            [
                ['أحمد محمد علي', '01012345678', '01123456789, 01234567890'],
                ['سارة إبراهيم خليل', '01098765432', '01511223344'],
            ],
            'crm-v3-leads-import-template.xlsx'
        );
    }

    private function importTemplateColumns(): array
    {
        return [
            'name' => 'اسم العميل / المتبرع',
            'phone' => 'رقم الهاتف الأساسي',
            'additional_phones' => 'أرقام هواتف إضافية',
        ];
    }

    public function importPreview(
        Request $request
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $validator = Validator::make(
            $request->all(),
            [
                'import_file' => [
                    'required',
                    'file',
                    'max:5120',
                ],
                'campaign_id' => [
                    'nullable',
                    'integer',
                    'exists:campaigns,id',
                ],
                'distribution_mode' => [
                    'nullable',
                    'string',
                    'in:round_robin,workload,single,file',
                ],
                'single_user_id' => [
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],
                'distribution_user_ids' => [
                    'nullable',
                    'array',
                ],
                'distribution_user_ids.*' => [
                    'integer',
                    'exists:users,id',
                ],
            ],
            [
                'import_file.required' => 'اختر ملف العملاء أولًا.',
                'import_file.file' => 'ملف الاستيراد غير صحيح.',
                'import_file.max' => 'الحد الأقصى لملف الاستيراد 5MB.',
                'distribution_mode.in' => 'طريقة التوزيع غير صحيحة.',
                'single_user_id.exists' => 'الموظف المحدد غير موجود.',
                'distribution_user_ids.*.exists' => 'أحد الموظفين المحددين غير موجود.',
            ]
        );

        if ($validator->fails()) {
            return $this->importPageRedirect($request)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $campaign = $this->campaignFromRequest(
            $request,
            $validated['campaign_id'] ?? null
        );

        $file = $request->file(
            'import_file'
        );

        if ($file === null) {
            return $this->importPageRedirect($request)->withErrors(
                [
                    'import_file' => 'تعذر قراءة ملف الاستيراد.',
                ]
            );
        }

        $extension = mb_strtolower(
            trim(
                (string)
                    $file
                        ->getClientOriginalExtension()
            )
        );

        if (
            ! in_array(
                $extension,
                [
                    'xlsx',
                    'csv',
                ],
                true
            )
        ) {
            return $this->importPageRedirect($request)->withErrors(
                [
                    'import_file' => 'الملفات المدعومة هي XLSX و CSV فقط.',
                ]
            );
        }

        $distMode = (string) ($validated['distribution_mode'] ?? 'single');
        $actor = $request->user();
        $distUsers = [];
        $hasSingleUserId = $request->has('single_user_id');
        $singleUserId = filter_var($request->input('single_user_id'), FILTER_VALIDATE_INT) ?: null;
        $distUserIds = array_map('intval', (array) ($validated['distribution_user_ids'] ?? []));

        if ($distMode === 'single') {
            if ($hasSingleUserId && ! $singleUserId) {
                return $this->importPageRedirect($request)
                    ->withErrors(['single_user_id' => 'يجب اختيار موظف مسؤول لإسناد العملاء إليه.'])
                    ->withInput();
            }
            $targetUserId = $singleUserId ?: (int) $actor->id;
            $targetUser = User::query()->where('id', $targetUserId)->where('is_active', true)->first() ?? $actor;
            if (! LeadAssignment::canAssignTo($actor, $targetUser)) {
                return $this->importPageRedirect($request)
                    ->withErrors(['single_user_id' => 'لا تملك صلاحية إسناد العملاء إلى الموظف المحدد.'])
                    ->withInput();
            }
            $distUsers = [$targetUser];
        } elseif (in_array($distMode, ['round_robin', 'workload'], true)) {
            if ($distUserIds === []) {
                return $this->importPageRedirect($request)
                    ->withErrors(['distribution_user_ids' => 'يجب اختيار موظف واحد على الأقل للتوزيع عليه.'])
                    ->withInput();
            }

            $distUsers = User::query()
                ->whereIn('id', $distUserIds)
                ->where('is_active', true)
                ->get();

            foreach ($distUsers as $u) {
                if (! LeadAssignment::canAssignTo($actor, $u)) {
                    return $this->importPageRedirect($request)
                        ->withErrors(['distribution_user_ids' => 'لا تملك صلاحية إسناد العملاء إلى: '.$u->name])
                        ->withInput();
                }
            }
        }

        $assignableUsers = LeadAssignment::assignableUsers($request->user())
            ->loadMissing('groups:id,name');

        $activeLeadsCounts = Lead::query()
            ->whereIn('assigned_user_id', $assignableUsers->pluck('id'))
            ->selectRaw('assigned_user_id, count(*) as count')
            ->groupBy('assigned_user_id')
            ->pluck('count', 'assigned_user_id')
            ->toArray();

        $distributionConfig = [
            'mode' => $distMode,
            'users' => $distUsers,
            'active_counts' => $activeLeadsCounts,
        ];

        try {
            $rows = $this->parseImportFile(
                $file->getPathname(),
                $extension
            );

            $preview =
                $this->buildImportPreview(
                    $rows,
                    $campaign?->id,
                    $distributionConfig
                );
        } catch (\Throwable $exception) {
            return $this->importPageRedirect($request)
                ->withErrors(
                    [
                        'import_file' => $exception->getMessage(),
                    ]
                );
        }

        $groups = Group::query()
            ->whereHas('users', static fn ($q) => $q->where('is_active', true))
            ->get(['id', 'name']);

        return view(
            'leads.import',
            [
                'statuses' => $this->statuses(),
                'preview' => $preview,
                'campaign' => $campaign,
                'assignableUsers' => $assignableUsers,
                'groups' => $groups,
                'activeLeadsCounts' => $activeLeadsCounts,
                'selectedDistributionMode' => $distMode,
                'selectedDistributionUserIds' => $distUserIds,
                'selectedSingleUserId' => $singleUserId ?? (int) $actor->id,
            ]
        );
    }

    private function importPageRedirect(
        Request $request
    ): RedirectResponse {
        $campaignId = filter_var(
            $request->input('campaign_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return redirect()->route(
            'v2.leads.import',
            $campaignId === false
                ? []
                : ['campaign' => $campaignId],
        );
    }

    public function importConfirm(
        Request $request
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $validated = $request->validate(
            [
                'preview_token' => [
                    'required',
                    'string',
                    'regex:/^[a-f0-9]{40}$/',
                ],
            ],
            [
                'preview_token.required' => 'جلسة المعاينة غير موجودة.',
                'preview_token.regex' => 'جلسة المعاينة غير صحيحة.',
            ]
        );

        $token = (string)
            $validated['preview_token'];

        $path = $this->previewPath(
            $token
        );

        if (! is_file($path)) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'انتهت جلسة المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        try {
            $payload = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'تعذر قراءة جلسة المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        if (
            ! is_array($payload)
            || ($payload['version'] ?? null) !== 1
            || (
                ($payload['session_id'] ?? '')
                    !== session()->getId()
                && (int) ($payload['user_id'] ?? 0)
                    !== (int) $request->user()->id
            )
        ) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'جلسة المعاينة لا تخص '
                            .'جلسة المستخدم الحالية.',
                    ]
                );
        }

        $createdAt = (int)
            ($payload['created_at'] ?? 0);

        if (
            $createdAt <= 0
            || (
                time() - $createdAt
            ) > self::PREVIEW_LIFETIME_SECONDS
        ) {
            @unlink($path);

            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'انتهت صلاحية المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        $campaign = $this->campaignFromRequest(
            $request,
            $payload['campaign_id'] ?? null
        );

        $rows = $payload['rows'] ?? [];

        if (
            ! is_array($rows)
            || $rows === []
        ) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'لا توجد صفوف صالحة للاستيراد.',
                    ]
                );
        }

        $actor = $request->user();
        $result = DB::transaction(
            function () use (
                $rows,
                $actor,
                $campaign
            ): array {
                $statusIds = LeadStatus::query()
                    ->pluck('id')
                    ->map(
                        static fn ($id): int => (int) $id
                    )
                    ->flip();

                $existingPhones = [];

                $existingLeads = Lead::query()
                    ->lockForUpdate()
                    ->get([
                        'id',
                        'phone',
                    ]);

                foreach (
                    $existingLeads as $existingLead
                ) {
                    $phone = $this
                        ->normalizePhone(
                            (string)
                                $existingLead->phone
                        );

                    if ($phone !== '') {
                        $existingPhones[
                            $phone
                        ] = true;
                    }
                }

                $imported = 0;
                $skipped = 0;

                foreach ($rows as $row) {
                    if (
                        ! is_array($row)
                        || ! isset($row['data'])
                        || ! is_array($row['data'])
                    ) {
                        continue;
                    }

                    $data = $row['data'];

                    $statusId = (int)
                        ($data['lead_status_id'] ?? 0);

                    if (
                        $statusId <= 0
                        || ! $statusIds->has(
                            $statusId
                        )
                    ) {
                        throw new \RuntimeException(
                            'إحدى الحالات الموجودة '
                            .'في المعاينة لم تعد متاحة.'
                        );
                    }
                    $assigneeId = (int) (
                        $data['assigned_user_id'] ?? 0
                    );
                    $assignee = User::query()
                        ->with('groups:id')
                        ->find($assigneeId);

                    if (
                        $assignee === null
                        || ! LeadAssignment::canAssignTo($actor, $assignee)
                    ) {
                        throw new \RuntimeException(
                            'لم تعد تملك صلاحية إسناد أحد العملاء إلى الموظف المحدد.'
                        );
                    }
                    $extraPhones = $data['_additional_phones'] ?? [];
                    $rowCampaignId = $data['_campaign_id'] ?? null;
                    unset($data['_additional_phones'], $data['_campaign_id']);

                    if (empty($data['branch_id'])) {
                        $data['branch_id'] = $assignee->branch_id ?? $actor->branch_id ?? Branch::value('id');
                    }

                    $phone = $this
                        ->normalizePhone(
                            (string)
                                ($data['phone'] ?? '')
                        );

                    if (
                        $phone === ''
                        || isset(
                            $existingPhones[
                                $phone
                            ]
                        )
                    ) {
                        $skipped++;

                        continue;
                    }

                    $lead = Lead::query()->create(
                        $data
                    );

                    // 1. Primary phone in lead_phones
                    LeadPhone::query()->create([
                        'lead_id' => $lead->id,
                        'phone' => $lead->phone,
                        'is_primary' => true,
                        'label' => 'أساسي',
                    ]);

                    // 2. Additional phones in lead_phones
                    if (is_array($extraPhones) && $extraPhones !== []) {
                        foreach ($extraPhones as $extraPhone) {
                            $cleanExtra = trim((string) $extraPhone);
                            if ($cleanExtra !== '' && $cleanExtra !== $lead->phone) {
                                LeadPhone::query()->create([
                                    'lead_id' => $lead->id,
                                    'phone' => $cleanExtra,
                                    'is_primary' => false,
                                    'label' => 'إضافي',
                                ]);
                            }
                        }
                    }

                    // 3. Attach campaign
                    $targetCampaign = $campaign ?? ($rowCampaignId ? Campaign::find($rowCampaignId) : null);
                    if ($targetCampaign !== null) {
                        $targetCampaign->leads()->syncWithoutDetaching([
                            $lead->id,
                        ]);
                    }

                    // 4. Initial status history
                    LeadStatusHistory::query()->create([
                        'lead_id' => $lead->id,
                        'from_status_id' => null,
                        'to_status_id' => $lead->lead_status_id,
                        'changed_by' => $actor->name,
                        'changed_by_user_id' => $actor->id,
                        'note' => ! empty($lead->response_details) ? 'استيراد: '.$lead->response_details : 'استيراد عميل',
                        'changed_at' => now(),
                    ]);

                    $existingPhones[
                        $phone
                    ] = true;

                    $imported++;
                }

                return [
                    'imported' => $imported,
                    'skipped' => $skipped,
                ];
            }
        );

        @unlink($path);

        $message =
            'تم استيراد '
            .$result['imported']
            .' عميل بنجاح.';

        if ($result['skipped'] > 0) {
            $message .=
                ' وتم تخطي '
                .$result['skipped']
                .' عميل لأن رقم الهاتف '
                .'أصبح موجودًا بالفعل.';
        }

        $redirect = $campaign !== null
            ? route('v2.campaigns.show', $campaign)
            : route('v2.leads.import');

        return redirect($redirect)
            ->with(
                'success',
                $message
            );
    }

    public function exportIndex(Request $request): View|RedirectResponse
    {

        $this->assertCrmV2Database();
        $user = $request->user();

        $sources = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

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

        return view(
            'leads.export',
            [
                'statuses' => $this->statuses(),
                'stages' => PipelineStage::activeOrdered(),
                'sources' => $sources,
                'employees' => $employees,
                'columns' => $this->exportColumns(),
                'totalLeads' => Lead::query()
                    ->accessibleTo($user)
                    ->count(),
            ]
        );
    }

    public function exportDownload(
        Request $request
    ): BinaryFileResponse|RedirectResponse {

        $this->assertCrmV2Database();
        $this->assertXlsxSupport();

        $columns =
            $this->exportColumns();

        $validated = $request->validate(
            [
                'status_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],

                'stage_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'pipeline_stages',
                        'id'
                    ),
                ],

                'employee' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'source' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'date_from' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],

                'date_to' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:date_from',
                ],

                'columns' => [
                    'required',
                    'array',
                    'min:1',
                    'max:40',
                ],

                'columns.*' => [
                    'required',
                    'string',
                    'distinct',
                    Rule::in(
                        array_keys($columns)
                    ),
                ],
            ],
            [
                'columns.required' => 'اختر عمودًا واحدًا على الأقل.',
                'columns.min' => 'اختر عمودًا واحدًا على الأقل.',
                'columns.*.in' => 'أحد أعمدة التصدير غير صحيح.',
                'date_from.date_format' => 'تاريخ البداية غير صحيح.',
                'date_to.date_format' => 'تاريخ النهاية غير صحيح.',
                'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون '
                    .'بعد أو مساويًا لتاريخ البداية.',
            ]
        );

        $query = Lead::query()
            ->accessibleTo($request->user())
            ->with([
                'status.stage',
                'assignedUser:id,name',
            ]);

        if (
            ! empty(
                $validated['status_id']
            )
        ) {
            $query->where(
                'lead_status_id',
                (int)
                    $validated['status_id']
            );
        }

        if (
            ! empty(
                $validated['stage_id']
            )
        ) {
            $stageId = (int)
                $validated['stage_id'];

            $query->whereHas(
                'status',
                static function (
                    $statusQuery
                ) use ($stageId): void {
                    $statusQuery->where(
                        'pipeline_stage_id',
                        $stageId
                    );
                }
            );
        }

        if (
            ! empty(
                $validated['employee']
            )
        ) {
            $employee = trim(
                (string) $validated['employee']
            );

            $query->where(
                function ($employeeQuery) use ($employee): void {
                    $employeeQuery
                        ->whereHas(
                            'assignedUser',
                            static fn ($userQuery) => $userQuery->where('name', $employee)
                        )
                        ->orWhere(
                            static fn ($legacyQuery) => $legacyQuery
                                ->whereNull('assigned_user_id')
                                ->where('assigned_employee', $employee)
                        );
                }
            );
        }

        if (
            ! empty(
                $validated['source']
            )
        ) {
            $query->where(
                'source',
                trim(
                    (string)
                        $validated[
                            'source'
                        ]
                )
            );
        }

        if (
            ! empty(
                $validated['date_from']
            )
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                (string)
                    $validated[
                        'date_from'
                    ]
            );
        }

        if (
            ! empty(
                $validated['date_to']
            )
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                (string)
                    $validated[
                        'date_to'
                    ]
            );
        }

        $count = (clone $query)
            ->count();

        if ($count === 0) {
            return back()
                ->withInput()
                ->withErrors(
                    [
                        'export' => 'لا توجد عملاء مطابقون '
                            .'للفلاتر المختارة.',
                    ]
                );
        }

        if (
            $count
            > self::MAX_EXPORT_ROWS
        ) {
            return back()
                ->withInput()
                ->withErrors(
                    [
                        'export' => 'عدد العملاء المطابقين '
                            .'أكبر من '
                            .self::MAX_EXPORT_ROWS
                            .'. قلل النطاق باستخدام '
                            .'الفلاتر.',
                    ]
                );
        }

        $selectedColumns =
            array_values(
                $validated['columns']
            );

        $headers = array_map(
            static fn (string $key): string => $columns[$key],
            $selectedColumns
        );

        $leads = $query
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($leads as $lead) {
            $row = [];

            foreach (
                $selectedColumns as $column
            ) {
                $row[] =
                    $this->exportValue(
                        $lead,
                        $column
                    );
            }

            $rows[] = $row;
        }

        return $this->downloadWorkbook(
            $headers,
            $rows,
            'crm-v2-leads-'
                .now()->format(
                    'Ymd-His'
                )
                .'.xlsx'
        );
    }

    private function statuses()
    {
        return LeadStatus::query()
            ->with('stage')
            ->whereHas('stage', static fn ($q) => $q->where('is_active', true))
            ->orderBy('position')
            ->get();
    }

    private function importColumns(): array
    {
        $columns = [
            'name' => 'اسم العميل / المتبرع',
            'phone' => 'رقم الهاتف الأساسي',
            'additional_phones' => 'أرقام هواتف إضافية',
            'email' => 'البريد الإلكتروني',
            'company_name' => 'اسم الشركة',
            'activity' => 'نشاط الشركة',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'donation_type' => 'نوع التبرع',
            'donation_cycle' => 'دورة التبرع',
            'donation_value' => 'قيمة التبرع',
            'donation_purpose' => 'غرض التبرع',
            'assigned_employee' => 'الموظف المسؤول',
            'branch' => 'الفرع',
            'source' => 'المصدر',
            'campaign' => 'الحملة',
            'contact_date' => 'تاريخ التواصل',
            'response_details' => 'تفاصيل الرد والمكالمة',
            'status' => 'الحالة',
        ];

        try {
            $customFields = LeadFieldSchema::customFields();
            foreach ($customFields as $customField) {
                $columns[$customField->key] = $customField->label();
            }
        } catch (\Throwable) {
        }

        return $columns;
    }

    private function importHeaderAliases(): array
    {
        $aliases = [
            'name' => [
                'اسم العميل / المتبرع',
                'اسم العميل او المتبرع',
                'اسم العميل',
                'اسم المتبرع',
                'الاسم',
                'الاسم الكامل',
                'name',
                'full_name',
                'customer_name',
                'donor_name',
                'lead_name',
            ],

            'first_name' => [
                'اسم العميل الأول',
                'الاسم الأول',
                'الاسم الاول',
                'first_name',
                'first name',
            ],

            'last_name' => [
                'اسم العميل الأخير',
                'الاسم الأخير',
                'الاسم الاخير',
                'last_name',
                'last name',
            ],

            'phone' => [
                'رقم الهاتف الأساسي',
                'الهاتف الأساسي',
                'الهاتف الاساسي',
                'الهاتف',
                'رقم الهاتف',
                'phone',
                'mobile',
                'primary_phone',
                'primary phone',
            ],

            'additional_phones' => [
                'أرقام هواتف إضافية',
                'ارقام هواتف اضافية',
                'هواتف إضافية',
                'هواتف اضافية',
                'أرقام إضافية',
                'ارقام اضافية',
                'additional_phones',
                'additional phones',
                'extra_phones',
                'other_phones',
            ],

            'email' => [
                'البريد الإلكتروني',
                'البريد الالكتروني',
                'البريد',
                'email',
                'email_address',
                'e-mail',
            ],

            'company_name' => [
                'اسم الشركة',
                'الشركة',
                'المؤسسة',
                'اسم المؤسسة',
                'company_name',
                'company',
                'organization',
            ],

            'activity' => [
                'نشاط الشركة',
                'النشاط',
                'activity',
                'business_activity',
            ],

            'governorate' => [
                'المحافظة',
                'المدينة',
                'governorate',
                'city',
            ],

            'address' => [
                'العنوان',
                'الشارع',
                'address',
            ],

            'donation_type' => [
                'نوع التبرع',
                'نوع التبرعات',
                'نوع_التبرع',
                'donation_type',
                'donation type',
                'donation_type_id',
            ],

            'donation_cycle' => [
                'دورة التبرع',
                'دورة التبرع (التكرار)',
                'تكرار التبرع',
                'دورة التبرعات',
                'donation_cycle',
                'donation cycle',
                'frequency',
                'donation_frequency',
            ],

            'donation_value' => [
                'قيمة التبرع',
                'مبلغ التبرع',
                'قيمة التبرع المتوقعة',
                'donation_value',
                'donation value',
                'donation_amount',
                'amount',
                'value',
            ],

            'donation_purpose' => [
                'غرض التبرع',
                'غرض / وجهة التبرع',
                'غرض او وجهة التبرع',
                'وجهة التبرع',
                'donation_purpose',
                'donation purpose',
                'purpose',
                'donation_purpose_id',
            ],

            'assigned_employee' => [
                'الموظف المسؤول',
                'الموظف المستجيب',
                'الموظف المستجيب / المسؤول',
                'الموظف المسند',
                'الموظف',
                'المسؤول',
                'assigned_employee',
                'assigned employee',
                'assigned_user',
                'assigned_user_id',
                'assignee',
            ],

            'branch' => [
                'الفرع',
                'الفرع التابع له العميل',
                'فرع العميل',
                'اسم الفرع',
                'branch',
                'branch_name',
                'branch_id',
            ],

            'source' => [
                'المصدر',
                'مصدر العميل',
                'source',
                'lead_source',
                'lead source',
            ],

            'campaign' => [
                'الحملة',
                'الحملة المرتبطة',
                'اسم الحملة',
                'campaign',
                'campaign_name',
                'campaign_id',
            ],

            'contact_date' => [
                'تاريخ التواصل',
                'تاريخ الاتصال',
                'contact_date',
                'contact date',
            ],

            'response_details' => [
                'تفاصيل الرد والمكالمة',
                'تفاصيل الرد',
                'ملاحظات التواصل',
                'تفاصيل المكالمة',
                'response_details',
                'response details',
                'call_details',
            ],

            'notes' => [
                'ملاحظات',
                'الملاحظات',
                'notes',
                'comment',
                'comments',
            ],

            'status' => [
                'الحالة',
                'حالة العميل',
                'المرحلة',
                'المرحلة الحالية',
                'status',
                'status_code',
                'status code',
                'stage',
                'pipeline_stage',
            ],

            // Legacy aliases
            'users_count' => [
                'عدد المستخدمين',
                'users_count',
                'users count',
            ],

            'branches_count' => [
                'عدد الفروع',
                'branches_count',
                'branches count',
            ],

            'job_title' => [
                'المنصب',
                'المسمى الوظيفي',
                'job_title',
                'job title',
            ],

            'solution_type' => [
                'نوع النظام',
                'solution_type',
                'solution type',
            ],

            'lines_count' => [
                'عدد الخطوط',
                'lines_count',
                'lines count',
            ],

            'extensions' => [
                'الملحقات',
                'extensions',
            ],

            'departments' => [
                'الأقسام',
                'الاقسام',
                'departments',
            ],

            'disinterest_reason' => [
                'سبب عدم الاهتمام',
                'disinterest_reason',
                'disinterest reason',
            ],
        ];

        try {
            $customFields = LeadFieldSchema::customFields();
            foreach ($customFields as $field) {
                $fieldAliases = array_filter([
                    $field->key,
                    $field->label_ar,
                    $field->label_en,
                ]);
                $aliases[$field->key] = array_values(array_unique(array_merge($aliases[$field->key] ?? [], $fieldAliases)));
            }
        } catch (\Throwable) {
        }

        $map = [];

        foreach (
            $aliases as $key => $values
        ) {
            foreach ($values as $value) {
                $map[
                    $this->normalizeHeader(
                        $value
                    )
                ] = $key;
            }
        }

        return $map;
    }

    private function buildImportPreview(
        array $rows,
        ?int $campaignId = null,
        array $distributionConfig = []
    ): array {
        if ($rows === []) {
            throw new \RuntimeException(
                'ملف الاستيراد فارغ.'
            );
        }

        $headerIndex = null;

        foreach ($rows as $index => $row) {
            if (
                $this->rowHasContent(
                    $row
                )
            ) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException(
                'ملف الاستيراد لا يحتوي '
                .'على بيانات.'
            );
        }

        $headerRow =
            $rows[$headerIndex];

        $aliasMap =
            $this->importHeaderAliases();

        $indexes = [];
        $ignoredHeaders = [];

        foreach (
            $headerRow as $columnIndex => $header
        ) {
            $headerText = trim(
                (string) $header
            );

            if ($headerText === '') {
                continue;
            }

            $normalized =
                $this->normalizeHeader(
                    $headerText
                );

            if (
                ! isset(
                    $aliasMap[
                        $normalized
                    ]
                )
            ) {
                $ignoredHeaders[] =
                    $headerText;

                continue;
            }
            $key = $aliasMap[
                $normalized
            ];

            if (isset($indexes[$key])) {
                throw new \RuntimeException(
                    'يوجد عمود مكرر للبيان: '
                    .$this->importColumns()[
                        $key
                    ]
                );
            }

            $indexes[$key] =
                $columnIndex;
        }

        $hasNameHeader = isset($indexes['name']) || isset($indexes['first_name']);
        if (! $hasNameHeader) {
            throw new \RuntimeException(
                'العمود المطلوب غير موجود: '
                .($this->importColumns()['name'] ?? 'اسم العميل / المتبرع')
            );
        }

        if (! isset($indexes['phone'])) {
            throw new \RuntimeException(
                'العمود المطلوب غير موجود: '
                .($this->importColumns()['phone'] ?? 'رقم الهاتف الأساسي')
            );
        }

        $dataRows = array_slice(
            $rows,
            $headerIndex + 1
        );

        if (
            count($dataRows)
            > self::MAX_IMPORT_ROWS
        ) {
            throw new \RuntimeException(
                'الحد الأقصى للاستيراد هو '
                .self::MAX_IMPORT_ROWS
                .' صف في العملية الواحدة.'
            );
        }

        $statuses =
            $this->statuses();
        $statusByCode = $statuses->keyBy('code');

        $statusMap = [];

        foreach ($statuses as $status) {
            $statusMap[
                $this->normalizeToken(
                    (string) $status->code
                )
            ] = $status;

            $statusMap[
                $this->normalizeToken(
                    (string) $status->name_ar
                )
            ] = $status;
        }

        $aliases = [
            'interested' => 'new',
            'no-answer' => 'no_answer',
            'donor_no_answer' => 'no_answer',
            'لم يرد' => 'no_answer',
            'not-interested' => 'not_interested',
            'donation_confirmed' => 'donor',
            'donation_collected' => 'donor',
            'active_donor' => 'donor',
            'completed' => 'donor',
            'meeting' => 'new',
            'quotation' => 'new',
            'discussion' => 'new',
            'contract_closed' => 'donor',
            'contract-closed' => 'donor',
            'contract_closing' => 'donor',
            'contract-closing' => 'donor',
            'execution' => 'donor',
        ];

        foreach ($aliases as $alias => $targetCode) {
            $targetStatus = $statusByCode->get($targetCode);
            if ($targetStatus !== null) {
                $statusMap[
                    $this->normalizeToken($alias)
                ] = $targetStatus;
            }
        }
        $actor = auth()->user();
        $userIdMap = [];

        $assignableUsers =
            LeadAssignment::assignableUsers($actor);

        if ($campaignId !== null) {
            $campaignUserIds = Campaign::query()
                ->findOrFail($campaignId)
                ->users()
                ->pluck('users.id')
                ->push($actor->id)
                ->map(
                    static fn ($id): int => (int) $id
                )
                ->unique();

            $assignableUsers = $assignableUsers
                ->whereIn('id', $campaignUserIds);
        }
        foreach (
            $assignableUsers as $user
        ) {
            foreach (
                [
                    $user->name,
                    $user->username,
                ] as $identity
            ) {
                $token = $this->normalizeToken(
                    (string) $identity
                );

                if (
                    $token !== ''
                    && ! isset($userIdMap[$token])
                ) {
                    $userIdMap[$token] =
                        (int) $user->id;
                }
            }
        }

        $donationTypeMap = [];
        try {
            foreach (DonationType::all() as $dt) {
                $donationTypeMap[$this->normalizeToken((string) $dt->name_ar)] = $dt;
                if (! empty($dt->name_en)) {
                    $donationTypeMap[$this->normalizeToken((string) $dt->name_en)] = $dt;
                }
                $donationTypeMap[(string) $dt->id] = $dt;
            }
        } catch (\Throwable) {
        }

        $donationPurposeMap = [];
        try {
            foreach (DonationPurpose::all() as $dp) {
                $donationPurposeMap[$this->normalizeToken((string) $dp->name_ar)] = $dp;
                if (! empty($dp->name_en)) {
                    $donationPurposeMap[$this->normalizeToken((string) $dp->name_en)] = $dp;
                }
                $donationPurposeMap[(string) $dp->id] = $dp;
            }
        } catch (\Throwable) {
        }

        $branchMap = [];
        try {
            foreach (Branch::all() as $br) {
                $branchMap[$this->normalizeToken((string) $br->name_ar)] = $br;
                if (! empty($br->name_en)) {
                    $branchMap[$this->normalizeToken((string) $br->name_en)] = $br;
                }
                if (! empty($br->code)) {
                    $branchMap[$this->normalizeToken((string) $br->code)] = $br;
                }
                $branchMap[(string) $br->id] = $br;
            }
        } catch (\Throwable) {
        }

        $campaignMap = [];
        try {
            foreach (Campaign::all() as $camp) {
                $campaignMap[$this->normalizeToken((string) $camp->name)] = $camp;
                $campaignMap[(string) $camp->id] = $camp;
            }
        } catch (\Throwable) {
        }

        $customFieldKeys = [];
        try {
            $customFieldKeys = LeadFieldSchema::customFields()->pluck('key')->all();
        } catch (\Throwable) {
        }

        $existingPhones = [];

        foreach (
            Lead::query()
                ->pluck('phone') as $phone
        ) {
            $normalized =
                $this->normalizePhone(
                    (string) $phone
                );

            if ($normalized !== '') {
                $existingPhones[
                    $normalized
                ] = true;
            }
        }

        $filePhones = [];

        $previewRows = [];
        $validPayloadRows = [];
        $employeeDistribution = [];

        $distMode = $distributionConfig['mode'] ?? 'round_robin';
        $distUsers = isset($distributionConfig['users'])
            ? (is_array($distributionConfig['users']) ? array_values($distributionConfig['users']) : $distributionConfig['users']->values()->all())
            : [];
        $distUserCount = count($distUsers);
        $distUsersById = [];
        $virtualWorkloads = [];
        $activeCounts = $distributionConfig['active_counts'] ?? [];

        foreach ($distUsers as $u) {
            $distUsersById[$u->id] = $u;
            $virtualWorkloads[$u->id] = (int) ($activeCounts[$u->id] ?? 0);
        }

        $roundRobinIndex = 0;
        $validCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;
        foreach (
            $dataRows as $offset => $row
        ) {
            if (
                ! $this->rowHasContent(
                    $row
                )
            ) {
                continue;
            }

            $excelRowNumber =
                $headerIndex
                + $offset
                + 2;

            $value = function (
                string $key
            ) use (
                $row,
                $indexes
            ): string {
                if (
                    ! isset(
                        $indexes[$key]
                    )
                ) {
                    return '';
                }

                return trim(
                    (string) (
                        $row[
                            $indexes[$key]
                        ] ?? ''
                    )
                );
            };

            $errors = [];
            $warnings = [];

            $rawName = $value('name');
            $rawFirstName = $value('first_name');
            $rawLastName = $this->nullableText($value('last_name'));

            if ($rawName !== '') {
                $fullName = $rawName;
                $parts = preg_split('/\s+/u', $fullName, 2);
                $firstName = $parts[0] ?? $fullName;
                $lastName = $rawLastName ?? ($parts[1] ?? null);
            } else {
                $firstName = $rawFirstName;
                $lastName = $rawLastName;
                $fullName = trim($firstName.' '.($lastName ?? ''));
            }

            $phone = $value('phone');
            $source = $this->nullableText($value('source')) ?? 'مباشر';

            if ($fullName === '' && $firstName === '') {
                $errors[] = 'اسم العميل / المتبرع مطلوب.';
            }

            $this->validateLength(
                $firstName,
                75,
                'اسم العميل الأول',
                $errors
            );

            if ($phone === '') {
                $errors[] = 'رقم الهاتف الأساسي مطلوب.';
            }

            $this->validateLength(
                $phone,
                50,
                'رقم الهاتف',
                $errors
            );

            $this->validateLength(
                $source,
                100,
                'المصدر',
                $errors
            );

            if ($lastName !== null) {
                $this->validateLength(
                    $lastName,
                    75,
                    'اسم العميل الأخير',
                    $errors
                );
            }

            $additionalPhonesRaw = $this->nullableText($value('additional_phones'));
            $additionalPhonesList = [];
            if ($additionalPhonesRaw !== null) {
                $splitPhones = preg_split('/[,;\/\n]+/u', $additionalPhonesRaw);
                $seenExtras = [];
                foreach ($splitPhones as $rawExtra) {
                    $cleanedExtra = $this->normalizePhone($rawExtra);
                    if ($cleanedExtra !== '' && $cleanedExtra !== $this->normalizePhone($phone) && ! in_array($cleanedExtra, $seenExtras, true)) {
                        $seenExtras[] = $cleanedExtra;
                        $additionalPhonesList[] = trim($rawExtra);
                    }
                }
            }

            $email = $this->nullableText($value('email'));
            if (
                $email !== null
                && (
                    mb_strlen($email) > 190
                    || filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    ) === false
                )
            ) {
                $errors[] = 'البريد الإلكتروني غير صحيح.';
            }

            $statusInput = $value('status');
            if ($statusInput === '') {
                $statusInput = $value('stage') !== '' ? $value('stage') : 'new';
            }

            $statusKey = $this->normalizeToken($statusInput);
            $status = $statusMap[$statusKey] ?? null;
            if ($status === null) {
                $errors[] = 'الحالة "'.$statusInput.'" غير موجودة في النظام.';
            }

            $companyName = $this->nullableText($value('company_name'));
            $activity = $this->nullableText($value('activity'));
            $governorate = $this->nullableText($value('governorate'));
            $address = $this->nullableText($value('address'));

            $donationTypeInput = $this->nullableText($value('donation_type'));
            $donationTypeStr = null;
            $donationTypeId = null;
            if ($donationTypeInput !== null) {
                $normType = $this->normalizeToken($donationTypeInput);
                $matchedType = $donationTypeMap[$normType] ?? null;
                if ($matchedType) {
                    $donationTypeStr = $matchedType->name_ar;
                    $donationTypeId = (int) $matchedType->id;
                } else {
                    $donationTypeStr = $donationTypeInput;
                }
            }

            $donationCycleInput = $this->nullableText($value('donation_cycle'));
            $donationCycle = null;
            if ($donationCycleInput !== null) {
                $normCycle = $this->normalizeToken($donationCycleInput);
                $donationCycle = match ($normCycle) {
                    'one_time', 'one-time', 'مرة واحدة', 'مرة' => 'one_time',
                    'monthly', 'شهري', 'شهر' => 'monthly',
                    'quarterly', 'ربع سنوي', 'ربع' => 'quarterly',
                    'semi_annual', 'semi-annual', 'نصف سنوي', 'نصف' => 'semi_annual',
                    'annual', 'سنوي', 'سنة' => 'annual',
                    default => in_array($donationCycleInput, ['one_time', 'monthly', 'quarterly', 'semi_annual', 'annual'], true) ? $donationCycleInput : $donationCycleInput,
                };
            }

            $donationValueRaw = $value('donation_value');
            $donationValue = null;
            if ($donationValueRaw !== '') {
                $cleanedVal = $this->normalizeDigits($donationValueRaw);
                $cleanedVal = preg_replace('/[^\d.]/u', '', str_replace(',', '.', $cleanedVal));
                if (is_numeric($cleanedVal)) {
                    $donationValue = (float) $cleanedVal;
                    if ($donationValue < 0) {
                        $errors[] = 'قيمة التبرع لا يمكن أن تكون سالبة.';
                    }
                } else {
                    $errors[] = 'قيمة التبرع يجب أن تكون قيمة رقمية صحيحة.';
                }
            }

            $donationPurposeInput = $this->nullableText($value('donation_purpose'));
            $donationPurposeStr = null;
            $donationPurposeId = null;
            if ($donationPurposeInput !== null) {
                $normPurpose = $this->normalizeToken($donationPurposeInput);
                $matchedPurpose = $donationPurposeMap[$normPurpose] ?? null;
                if ($matchedPurpose) {
                    $donationPurposeStr = $matchedPurpose->name_ar;
                    $donationPurposeId = (int) $matchedPurpose->id;
                } else {
                    $donationPurposeStr = $donationPurposeInput;
                }
            }

            $branchInput = $this->nullableText($value('branch'));
            $branchId = null;
            if ($branchInput !== null) {
                $normBranch = $this->normalizeToken($branchInput);
                $matchedBranch = $branchMap[$normBranch] ?? null;
                if ($matchedBranch) {
                    $branchId = (int) $matchedBranch->id;
                }
            }

            $campaignInput = $this->nullableText($value('campaign'));
            $rowCampaignId = $campaignId;
            if ($rowCampaignId === null && $campaignInput !== null) {
                $normCamp = $this->normalizeToken($campaignInput);
                $matchedCamp = $campaignMap[$normCamp] ?? null;
                if ($matchedCamp) {
                    $rowCampaignId = (int) $matchedCamp->id;
                }
            }

            $contactDateInput = $this->nullableText($value('contact_date'));
            $contactDate = null;
            if ($contactDateInput !== null) {
                try {
                    $contactDate = Carbon::parse($contactDateInput)->format('Y-m-d');
                } catch (\Throwable) {
                    $contactDate = date('Y-m-d');
                }
            } else {
                $contactDate = date('Y-m-d');
            }

            $nextFollowUpInput = $this->nullableText($value('next_follow_up_at'));
            $nextFollowUpAt = null;
            if ($nextFollowUpInput !== null) {
                try {
                    $nextFollowUpAt = Carbon::parse($nextFollowUpInput)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $nextFollowUpAt = null;
                }
            }

            $responseDetails = $this->nullableText($value('response_details')) ?? $this->nullableText($value('notes'));
            $notes = $this->nullableText($value('notes')) ?? $responseDetails;

            if ($distMode === 'single' && $distUserCount > 0) {
                $assignedUser = $distUsers[0];
                $assignedEmployee = (string) $assignedUser->name;
                $assignedUserId = (int) $assignedUser->id;
            } elseif ($distMode === 'workload' && $distUserCount > 0) {
                $minVal = min($virtualWorkloads);
                $minUids = array_keys($virtualWorkloads, $minVal);
                $minUid = $minUids[0];
                $virtualWorkloads[$minUid]++;
                $assignedUser = $distUsersById[$minUid];
                $assignedEmployee = (string) $assignedUser->name;
                $assignedUserId = (int) $assignedUser->id;
            } elseif ($distMode === 'round_robin' && $distUserCount > 0) {
                $assignedUser = $distUsers[$roundRobinIndex % $distUserCount];
                $roundRobinIndex++;
                $assignedEmployee = (string) $assignedUser->name;
                $assignedUserId = (int) $assignedUser->id;
            } else {
                // Fallback: file employee if provided, otherwise default to current user
                $fileEmployeeInput = $this->nullableText($value('assigned_employee'));
                $assignedEmployee = $fileEmployeeInput ?? $this->currentEmployeeName();
                $assignedUserId = $userIdMap[$this->normalizeToken($assignedEmployee)] ?? (int) $actor->id;

                if ($assignedUserId === null) {
                    $errors[] = 'لا تملك صلاحية الإسناد إلى الموظف المسؤول المحدد: '.$assignedEmployee;
                }
            }

            $customFieldsPayload = [];
            foreach ($customFieldKeys as $cfKey) {
                if (isset($indexes[$cfKey])) {
                    $cfVal = trim((string) ($row[$indexes[$cfKey]] ?? ''));
                    if ($cfVal !== '') {
                        $customFieldsPayload[$cfKey] = $cfVal;
                    }
                }
            }

            // Legacy fields for backward compatibility
            $usersCount = $this->parseUnsignedInteger($value('users_count'), 0, 1000000, 'عدد المستخدمين', $errors);
            $branchesCount = $this->parseUnsignedInteger($value('branches_count'), 0, 1000000, 'عدد الفروع', $errors);
            $linesCount = $this->parseUnsignedInteger($value('lines_count'), 1, 1000000, 'عدد الخطوط', $errors);
            $solutionType = $this->parseSolutionType($value('solution_type'), $errors);
            $jobTitle = $this->nullableText($value('job_title'));
            $extensions = $this->nullableText($value('extensions'));
            $departments = $this->nullableText($value('departments'));
            $disinterestReason = $this->nullableText($value('disinterest_reason'));

            foreach (
                [
                    [$companyName, 150, 'اسم الشركة'],
                    [$activity, 150, 'النشاط'],
                    [$governorate, 100, 'المحافظة'],
                    [$address, 255, 'العنوان'],
                    [$jobTitle, 150, 'المنصب'],
                    [$assignedEmployee, 150, 'الموظف المسؤول'],
                    [$extensions, 5000, 'الملحقات'],
                    [$departments, 5000, 'الأقسام'],
                    [$disinterestReason, 5000, 'سبب عدم الاهتمام'],
                    [$notes, 5000, 'الملاحظات'],
                    [$responseDetails, 5000, 'تفاصيل الرد'],
                ] as [$text, $max, $label]
            ) {
                if ($text !== null) {
                    $this->validateLength($text, $max, $label, $errors);
                }
            }

            if (
                $status !== null
                && $status->code
                    === 'not_interested'
                && $disinterestReason
                    === null
            ) {
                $warnings[] =
                    'الحالة غير مهتم بدون سبب؛ '
                    .'يمكن استكمال السبب لاحقًا.';
            }

            $normalizedPhone =
                $this->normalizePhone(
                    $phone
                );

            if (
                $phone !== ''
                && $normalizedPhone === ''
            ) {
                $errors[] =
                    'رقم الهاتف لا يحتوي '
                    .'على أرقام صحيحة.';
            }

            $duplicateReason = null;

            if (
                $normalizedPhone !== ''
                && isset(
                    $existingPhones[
                        $normalizedPhone
                    ]
                )
            ) {
                $duplicateReason =
                    'رقم الهاتف موجود بالفعل '
                    .'في CRM.';
            } elseif (
                $normalizedPhone !== ''
                && isset(
                    $filePhones[
                        $normalizedPhone
                    ]
                )
            ) {
                $duplicateReason =
                    'رقم الهاتف مكرر داخل الملف.';
            }

            if ($normalizedPhone !== '') {
                $filePhones[
                    $normalizedPhone
                ] = true;
            }

            $fullName = trim(
                $firstName
                .' '
                .($lastName ?? '')
            );

            $state = 'valid';

            if ($errors !== []) {
                $state = 'error';
                $errorCount++;
            } elseif (
                $duplicateReason !== null
            ) {
                $state = 'duplicate';
                $duplicateCount++;
            } else {
                $validCount++;
            }

            $leadData = null;

            if ($state === 'valid') {
                $leadData = [
                    'lead_status_id' => (int) $status->id,
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'email' => $email,
                    'company_name' => $companyName,
                    'activity' => $activity,
                    'governorate' => $governorate,
                    'address' => $address,
                    'donation_type' => $donationTypeStr,
                    'donation_type_id' => $donationTypeId,
                    'donation_cycle' => $donationCycle,
                    'donation_value' => $donationValue,
                    'donation_purpose' => $donationPurposeStr,
                    'donation_purpose_id' => $donationPurposeId,
                    'source' => $source,
                    'assigned_employee' => $assignedEmployee,
                    'assigned_user_id' => $assignedUserId,
                    'responding_user_id' => $assignedUserId,
                    'created_by' => $actor->name,
                    'created_by_user_id' => (int) $actor->id,
                    'contact_date' => $contactDate,
                    'next_follow_up_at' => $nextFollowUpAt,
                    'response_details' => $responseDetails,
                    'notes' => $notes,
                    'custom_fields' => $customFieldsPayload,
                    'users_count' => $usersCount,
                    'branches_count' => $branchesCount,
                    'job_title' => $jobTitle,
                    'disinterest_reason' => $disinterestReason,
                    'solution_type' => $solutionType,
                    'lines_count' => $solutionType === 'call_center' ? $linesCount : null,
                    'extensions' => $solutionType === 'call_center' ? $extensions : null,
                    'departments' => $solutionType === 'erp' ? $departments : null,
                    'quotation_file_path' => null,
                    'quotation_sent' => false,
                    '_additional_phones' => $additionalPhonesList,
                    '_campaign_id' => $rowCampaignId,
                ];
                $validPayloadRows[] = [
                    'row_number' => $excelRowNumber,
                    'data' => $leadData,
                ];

                $distKey = $assignedEmployee;
                if (! isset($employeeDistribution[$distKey])) {
                    $employeeDistribution[$distKey] = [
                        'name' => $distKey,
                        'user_id' => $assignedUserId,
                        'count' => 0,
                        'percentage' => 0.0,
                    ];
                }
                $employeeDistribution[$distKey]['count']++;
            }

            $previewRows[] = [
                'row_number' => $excelRowNumber,

                'name' => $fullName !== ''
                        ? $fullName
                        : '----',
                'phone' => $phone !== ''
                        ? $phone
                        : '----',

                'assigned_employee' => $assignedEmployee,
                'status' => $status?->name_ar
                    ?? $statusInput,

                'stage' => $status?->stage
                    ?->name_ar
                    ?? '----',

                'state' => $state,

                'duplicate_reason' => $duplicateReason,

                'additional_phones' => $additionalPhonesList,

                'errors' => $errors,

                'warnings' => $warnings,
            ];
        }

        if ($previewRows === []) {
            throw new \RuntimeException(
                'لا توجد صفوف بيانات بعد '
                .'صف العناوين.'
            );
        }

        $token = null;

        if ($validPayloadRows !== []) {
            $token =
                $this->writePreviewPayload(
                    $validPayloadRows,
                    $campaignId
                );
        }
        if ($validCount > 0 && $employeeDistribution !== []) {
            foreach ($employeeDistribution as &$distItem) {
                $distItem['percentage'] = round(($distItem['count'] / $validCount) * 100, 1);
            }
            unset($distItem);
            uasort($employeeDistribution, static fn ($a, $b) => $b['count'] <=> $a['count']);
        }

        return [
            'token' => $token,
            'rows' => $previewRows,
            'total_count' => count($previewRows),
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'duplicate_count' => $duplicateCount,
            'employee_distribution' => array_values($employeeDistribution),
            'ignored_headers' => array_values(
                array_unique(
                    $ignoredHeaders
                )
            ),
        ];
    }

    private function parseImportFile(
        string $path,
        string $extension
    ): array {
        return match ($extension) {
            'csv' => $this->parseCsv($path),

            'xlsx' => $this->parseXlsx($path),

            default => throw new \RuntimeException(
                'صيغة الملف غير مدعومة.'
            ),
        };
    }

    private function parseCsv(
        string $path
    ): array {
        $content = File::get($path);

        if (
            str_starts_with(
                $content,
                "\xEF\xBB\xBF"
            )
        ) {
            $content = substr(
                $content,
                3
            );
        } elseif (
            str_starts_with(
                $content,
                "\xFF\xFE"
            )
        ) {
            $content =
                mb_convert_encoding(
                    substr($content, 2),
                    'UTF-8',
                    'UTF-16LE'
                );
        } elseif (
            str_starts_with(
                $content,
                "\xFE\xFF"
            )
        ) {
            $content =
                mb_convert_encoding(
                    substr($content, 2),
                    'UTF-8',
                    'UTF-16BE'
                );
        }

        $firstLine = '';

        foreach (
            preg_split(
                '/\R/u',
                $content
            ) ?: [] as $line
        ) {
            if (trim($line) !== '') {
                $firstLine = $line;
                break;
            }
        }

        if ($firstLine === '') {
            return [];
        }

        $delimiter = ',';
        $bestCount = 0;

        foreach (
            [
                ',',
                ';',
                "\t",
            ] as $candidate
        ) {
            $count = count(
                str_getcsv(
                    $firstLine,
                    $candidate
                )
            );

            if ($count > $bestCount) {
                $bestCount = $count;
                $delimiter = $candidate;
            }
        }

        $stream = fopen(
            'php://temp',
            'r+'
        );

        if ($stream === false) {
            throw new \RuntimeException(
                'تعذر تجهيز ملف CSV.'
            );
        }

        fwrite(
            $stream,
            $content
        );

        rewind($stream);

        $rows = [];

        while (
            (
                $row = fgetcsv(
                    $stream,
                    0,
                    $delimiter
                )
            ) !== false
        ) {
            $rows[] = array_map(
                static fn ($value): string => trim(
                    (string) $value
                ),
                $row
            );

            if (
                count($rows)
                > self::MAX_IMPORT_ROWS
                    + 20
            ) {
                break;
            }
        }

        fclose($stream);

        return $rows;
    }

    private function parseXlsx(
        string $path
    ): array {
        $this->assertXlsxSupport();

        $zip = new ZipArchive;

        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new \RuntimeException(
                'تعذر فتح ملف Excel.'
            );
        }

        try {
            $sharedStrings =
                $this->xlsxSharedStrings(
                    $zip
                );

            $sheetPath =
                'xl/worksheets/sheet1.xml';

            if (
                $zip->locateName(
                    $sheetPath
                ) === false
            ) {
                $sheetPath = '';

                for (
                    $index = 0;
                    $index < $zip->numFiles;
                    $index++
                ) {
                    $name = (string)
                        $zip->getNameIndex(
                            $index
                        );

                    if (
                        preg_match(
                            '#^xl/worksheets/'
                            .'sheet\d+\.xml$#',
                            $name
                        )
                    ) {
                        $sheetPath = $name;
                        break;
                    }
                }

                if ($sheetPath === '') {
                    throw new \RuntimeException(
                        'لا توجد ورقة بيانات '
                        .'صالحة داخل ملف Excel.'
                    );
                }
            }

            $sheetXml =
                $zip->getFromName(
                    $sheetPath
                );

            if (
                ! is_string($sheetXml)
                || $sheetXml === ''
            ) {
                throw new \RuntimeException(
                    'تعذر قراءة ورقة Excel.'
                );
            }

            $xml = simplexml_load_string(
                $sheetXml,
                \SimpleXMLElement::class,
                LIBXML_NONET
                | LIBXML_COMPACT
            );

            if ($xml === false) {
                throw new \RuntimeException(
                    'بيانات Excel الداخلية '
                    .'غير صحيحة.'
                );
            }

            $namespaces =
                $xml->getNamespaces(true);

            $mainNamespace =
                $namespaces[''] ?? '';

            $main = $mainNamespace !== ''
                ? $xml->children(
                    $mainNamespace
                )
                : $xml;

            $rows = [];

            foreach (
                $main->sheetData->row as $rowNode
            ) {
                $cells = [];
                $maxColumn = -1;

                foreach (
                    $rowNode->children(
                        $mainNamespace
                    )->c as $cell
                ) {
                    $attributes =
                        $cell->attributes();

                    $reference = (string)
                        ($attributes['r'] ?? '');

                    if (
                        ! preg_match(
                            '/^([A-Z]+)\d+$/',
                            $reference,
                            $match
                        )
                    ) {
                        continue;
                    }

                    $columnIndex =
                        $this
                            ->xlsxColumnIndex(
                                $match[1]
                            );

                    $type = (string)
                        ($attributes['t'] ?? '');

                    $cellMain =
                        $mainNamespace !== ''
                            ? $cell->children(
                                $mainNamespace
                            )
                            : $cell;

                    $cellValue = '';

                    if ($type === 's') {
                        $sharedIndex = (int)
                            ($cellMain->v ?? 0);

                        $cellValue =
                            $sharedStrings[
                                $sharedIndex
                            ] ?? '';
                    } elseif (
                        $type === 'inlineStr'
                    ) {
                        $cellValue =
                            isset(
                                $cellMain->is
                            )
                                ? $this
                                    ->xlsxNodeText(
                                        $cellMain->is,
                                        $mainNamespace
                                    )
                                : '';
                    } else {
                        $cellValue =
                            isset(
                                $cellMain->v
                            )
                                ? (string)
                                    $cellMain->v
                                : '';
                    }

                    $cells[
                        $columnIndex
                    ] = trim(
                        $cellValue
                    );

                    $maxColumn = max(
                        $maxColumn,
                        $columnIndex
                    );
                }

                if ($maxColumn < 0) {
                    $rows[] = [];

                    continue;
                }

                $row = [];

                for (
                    $column = 0;
                    $column <= $maxColumn;
                    $column++
                ) {
                    $row[] =
                        $cells[$column]
                        ?? '';
                }

                $rows[] = $row;

                if (
                    count($rows)
                    > self::MAX_IMPORT_ROWS
                        + 20
                ) {
                    break;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function xlsxSharedStrings(
        ZipArchive $zip
    ): array {
        $xmlContent =
            $zip->getFromName(
                'xl/sharedStrings.xml'
            );

        if (
            ! is_string($xmlContent)
            || $xmlContent === ''
        ) {
            return [];
        }

        $xml = simplexml_load_string(
            $xmlContent,
            \SimpleXMLElement::class,
            LIBXML_NONET
            | LIBXML_COMPACT
        );

        if ($xml === false) {
            return [];
        }

        $namespaces =
            $xml->getNamespaces(true);

        $mainNamespace =
            $namespaces[''] ?? '';

        $main = $mainNamespace !== ''
            ? $xml->children(
                $mainNamespace
            )
            : $xml;

        $values = [];

        foreach ($main->si as $item) {
            $values[] =
                $this->xlsxNodeText(
                    $item,
                    $mainNamespace
                );
        }

        return $values;
    }

    private function xlsxNodeText(
        \SimpleXMLElement $node,
        string $namespace
    ): string {
        $children = $namespace !== ''
            ? $node->children($namespace)
            : $node;

        if (isset($children->t)) {
            return (string)
                $children->t;
        }

        $text = '';

        foreach ($children->r as $run) {
            $runChildren =
                $namespace !== ''
                    ? $run->children(
                        $namespace
                    )
                    : $run;

            $text .= (string)
                ($runChildren->t ?? '');
        }

        return $text;
    }

    private function writePreviewPayload(
        array $rows,
        ?int $campaignId = null
    ): string {
        $directory =
            $this->previewDirectory();

        File::ensureDirectoryExists(
            $directory
        );

        foreach (
            File::files($directory) as $file
        ) {
            if (
                $file->getMTime()
                < time() - 86400
            ) {
                @unlink(
                    $file->getPathname()
                );
            }
        }

        $token = bin2hex(
            random_bytes(20)
        );

        $payload = [
            'version' => 1,
            'created_at' => time(),
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'employee' => $this
                ->currentEmployeeName(),
            'campaign_id' => $campaignId,
            'rows' => $rows,
        ];

        File::put(
            $this->previewPath(
                $token
            ),
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        return $token;
    }

    private function campaignFromRequest(
        Request $request,
        mixed $campaignId
    ): ?Campaign {
        $campaignId = (int) $campaignId;

        if ($campaignId <= 0) {
            return null;
        }

        $campaign = Campaign::query()
            ->findOrFail($campaignId);
        $actor = $request->user();

        abort_unless(
            $actor->isSuperAdmin()
            || (
                (int) $campaign->created_by_user_id === (int) $actor->id
                && $actor->hasPermission(
                    CrmPermission::CAMPAIGNS_CREATE
                )
            ),
            403
        );

        return $campaign;
    }

    private function previewDirectory(): string
    {
        return storage_path(
            'app/crm-v2/import-previews'
        );
    }

    private function previewPath(
        string $token
    ): string {
        return $this->previewDirectory()
            .DIRECTORY_SEPARATOR
            .$token
            .'.json';
    }

    private function parseUnsignedInteger(
        string $value,
        int $minimum,
        int $maximum,
        string $label,
        array &$errors
    ): ?int {
        $value = trim(
            $this->normalizeDigits(
                $value
            )
        );

        if ($value === '') {
            return null;
        }

        if (
            ! preg_match(
                '/^\d+$/',
                $value
            )
        ) {
            $errors[] =
                $label
                .' يجب أن يكون رقمًا صحيحًا.';

            return null;
        }

        $number = (int) $value;

        if (
            $number < $minimum
            || $number > $maximum
        ) {
            $errors[] =
                $label
                .' يجب أن يكون بين '
                .$minimum
                .' و '
                .$maximum
                .'.';

            return null;
        }

        return $number;
    }

    private function parseSolutionType(
        string $value,
        array &$errors
    ): ?string {
        $value =
            $this->normalizeToken(
                $value
            );

        if ($value === '') {
            return null;
        }

        $map = [
            'call_center' => 'call_center',
            'call center' => 'call_center',
            'callcenter' => 'call_center',
            'كول سنتر' => 'call_center',
            'erp' => 'erp',
        ];

        if (! isset($map[$value])) {
            $errors[] =
                'نوع النظام يجب أن يكون '
                .'Call Center أو ERP.';

            return null;
        }

        return $map[$value];
    }

    private function nullableText(
        string $value
    ): ?string {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function validateLength(
        string $value,
        int $maximum,
        string $label,
        array &$errors
    ): void {
        if (
            mb_strlen($value)
            > $maximum
        ) {
            $errors[] =
                $label
                .' يتجاوز الحد الأقصى '
                .$maximum
                .' حرف.';
        }
    }

    private function rowHasContent(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (
                trim(
                    (string) $value
                ) !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(
        string $value
    ): string {
        $value = $this
            ->normalizeToken(
                $value
            );

        $value = str_replace(
            [
                '_',
                '-',
            ],
            ' ',
            $value
        );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            ) ?? $value
        );
    }

    private function normalizeToken(
        string $value
    ): string {
        $value = trim(
            mb_strtolower(
                $value
            )
        );

        $value = preg_replace(
            '/[\x{200E}\x{200F}\x{061C}]/u',
            '',
            $value
        ) ?? $value;

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            ) ?? $value
        );
    }

    private function normalizePhone(
        string $value
    ): string {
        $value =
            $this->normalizeDigits(
                $value
            );

        return preg_replace(
            '/\D+/u',
            '',
            $value
        ) ?? '';
    }

    private function normalizeDigits(
        string $value
    ): string {
        return strtr(
            $value,
            [
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
            ]
        );
    }

    private function exportColumns(): array
    {
        $columns = [
            'id' => 'رقم العميل',
            'name' => 'اسم العميل',
            'first_name' => 'الاسم الأول',
            'last_name' => 'الاسم الأخير',
            'phone' => 'الهاتف الأساسي',
            'additional_phones' => 'هواتف إضافية',
            'email' => 'البريد الإلكتروني',
            'company_name' => 'اسم الشركة',
            'activity' => 'النشاط',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'donation_type' => 'نوع التبرع',
            'donation_cycle' => 'دورة التبرع',
            'donation_value' => 'قيمة التبرع',
            'donation_purpose' => 'غرض التبرع',
            'source' => 'المصدر',
            'status' => 'الحالة',
            'stage' => 'المرحلة',
            'assigned_employee' => 'الموظف المسؤول',
            'branch' => 'الفرع',
            'campaign' => 'الحملة',
            'contact_date' => 'تاريخ التواصل',
            'next_follow_up_at' => 'المتابعة القادمة',
            'response_details' => 'تفاصيل الرد',
            'notes' => 'ملاحظات',
            'created_by' => 'أنشأ بواسطة',
            'created_at' => 'تاريخ الإضافة',
            'updated_at' => 'آخر تحديث',
        ];

        try {
            $customFields = LeadFieldSchema::customFields();
            foreach ($customFields as $customField) {
                $columns['cf_'.$customField->key] = $customField->label();
            }
        } catch (\Throwable) {
        }

        return $columns;
    }

    private function exportValue(
        Lead $lead,
        string $column
    ): string {
        if (str_starts_with($column, 'cf_')) {
            $key = substr($column, 3);
            $customFields = is_array($lead->custom_fields) ? $lead->custom_fields : [];
            $val = $customFields[$key] ?? '';

            return is_array($val) ? implode(', ', $val) : (string) $val;
        }

        return match ($column) {
            'id' => (string) $lead->id,

            'name' => (string) $lead->name,

            'first_name' => (string)
                    $lead->first_name,

            'last_name' => (string)
                    $lead->last_name,

            'phone' => (string) $lead->phone,

            'additional_phones' => (string) $lead->additionalPhones->pluck('phone')->implode(', '),

            'email' => (string) $lead->email,

            'company_name' => (string)
                    $lead->company_name,

            'activity' => (string)
                    $lead->activity,

            'governorate' => (string)
                    $lead->governorate,

            'address' => (string)
                    $lead->address,

            'donation_type' => (string) ($lead->donationType?->name_ar ?? $lead->donation_type ?? ''),

            'donation_cycle' => (string) ($lead->donation_cycle ?? ''),

            'donation_value' => $lead->donation_value !== null ? (string) $lead->donation_value : '',

            'donation_purpose' => (string) ($lead->donationPurposeRel?->name_ar ?? $lead->donation_purpose ?? ''),

            'source' => (string)
                    $lead->source,

            'status' => (string) (
                $lead->status
                    ?->name_ar
                ?? ''
            ),

            'stage' => (string) (
                $lead->status
                    ?->stage
                    ?->name_ar
                ?? ''
            ),

            'assigned_employee' => (string) (
                $lead->assignedUser?->name
                ?? $lead->assigned_employee
            ),

            'branch' => (string) ($lead->branch?->name_ar ?? ''),

            'campaign' => (string) ($lead->campaigns->pluck('name')->implode(', ')),

            'contact_date' => $this->formatDate(
                $lead->contact_date
            ),

            'next_follow_up_at' => $this->formatDate(
                $lead
                    ->next_follow_up_at
            ),

            'response_details' => (string) ($lead->response_details ?? ''),

            'users_count' => $lead->users_count === null
                    ? ''
                    : (string)
                        $lead->users_count,

            'branches_count' => $lead->branches_count === null
                    ? ''
                    : (string)
                        $lead->branches_count,

            'job_title' => (string)
                    $lead->job_title,

            'solution_type' => match (
                (string)
                    $lead->solution_type
            ) {
                'call_center' => 'Call Center',
                'erp' => 'ERP',
                default => (string)
                        $lead
                            ->solution_type,
            },

            'lines_count' => $lead->lines_count === null
                    ? ''
                    : (string)
                        $lead->lines_count,

            'extensions' => (string)
                    $lead->extensions,

            'departments' => (string)
                    $lead->departments,

            'quotation_sent' => $lead->quotation_sent
                    ? 'نعم'
                    : 'لا',

            'quotation_file' => $this
                ->quotationFileLabel(
                    $lead
                ),

            'disinterest_reason' => (string)
                    $lead
                        ->disinterest_reason,

            'notes' => (string) $lead->notes,

            'created_by' => (string)
                    $lead->created_by,

            'created_at' => $this->formatDate(
                $lead->created_at
            ),

            'updated_at' => $this->formatDate(
                $lead->updated_at
            ),

            default => '',
        };
    }

    private function quotationFileLabel(
        Lead $lead
    ): string {
        $path = trim(
            (string)
                $lead->quotation_file_path
        );

        if ($path === '') {
            return 'غير مرفوع';
        }

        try {
            if (
                Storage::disk('local')
                    ->exists($path)
            ) {
                return basename($path);
            }

            return 'المسار مسجل والملف غير موجود';
        } catch (\Throwable) {
            return 'تعذر التحقق من الملف';
        }
    }

    private function formatDate(
        mixed $value
    ): string {
        if (
            $value
            instanceof \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d H:i'
            );
        }

        return is_string($value)
            ? trim($value)
            : '';
    }

    private function downloadWorkbook(
        array $headers,
        array $rows,
        string $downloadName
    ): BinaryFileResponse {
        $this->assertXlsxSupport();

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'crm-v2-transfer-'
        );

        if (
            ! is_string($temporaryFile)
            || $temporaryFile === ''
        ) {
            throw new \RuntimeException(
                'تعذر إنشاء ملف Excel المؤقت.'
            );
        }

        try {
            $this->writeWorkbook(
                $temporaryFile,
                $headers,
                $rows
            );

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
                ->deleteFileAfterSend(
                    true
                );
        } catch (\Throwable $exception) {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }

            throw $exception;
        }
    }

    private function writeWorkbook(
        string $path,
        array $headers,
        array $rows
    ): void {
        if ($headers === []) {
            throw new \RuntimeException(
                'لا توجد أعمدة لملف Excel.'
            );
        }

        $zip = new ZipArchive;

        $result = $zip->open(
            $path,
            ZipArchive::CREATE
            | ZipArchive::OVERWRITE
        );

        if ($result !== true) {
            throw new \RuntimeException(
                'تعذر إنشاء ملف Excel.'
            );
        }

        try {
            $zip->addFromString(
                '[Content_Types].xml',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML
            );

            $zip->addFromString(
                '_rels/.rels',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML
            );

            $zip->addFromString(
                'xl/workbook.xml',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <sheets>
  <sheet name="Leads" sheetId="1" r:id="rId1"/>
 </sheets>
</workbook>
XML
            );

            $zip->addFromString(
                'xl/_rels/workbook.xml.rels',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML
            );

            $writer = new XMLWriter;

            $writer->openMemory();

            $writer->startDocument(
                '1.0',
                'UTF-8',
                'yes'
            );

            $writer->startElement(
                'worksheet'
            );

            $writer->writeAttribute(
                'xmlns',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $writer->startElement(
                'sheetViews'
            );

            $writer->startElement(
                'sheetView'
            );

            $writer->writeAttribute(
                'workbookViewId',
                '0'
            );

            $writer->writeAttribute(
                'rightToLeft',
                '1'
            );

            $writer->endElement();
            $writer->endElement();

            $writer->startElement(
                'sheetData'
            );

            $allRows = array_merge(
                [
                    $headers,
                ],
                $rows
            );

            foreach (
                $allRows as $rowIndex => $row
            ) {
                $excelRow =
                    $rowIndex + 1;

                $writer->startElement(
                    'row'
                );

                $writer->writeAttribute(
                    'r',
                    (string)
                        $excelRow
                );

                foreach (
                    array_values($row) as $columnIndex => $value
                ) {
                    $cellReference =
                        $this
                            ->xlsxColumnName(
                                $columnIndex
                            )
                        .$excelRow;

                    $writer->startElement(
                        'c'
                    );

                    $writer->writeAttribute(
                        'r',
                        $cellReference
                    );

                    $writer->writeAttribute(
                        't',
                        'inlineStr'
                    );

                    $writer->startElement(
                        'is'
                    );

                    $writer->startElement(
                        't'
                    );

                    $writer->text(
                        (string) $value
                    );

                    $writer->endElement();
                    $writer->endElement();
                    $writer->endElement();
                }

                $writer->endElement();
            }

            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();

            $sheetXml =
                $writer->outputMemory();

            if (
                ! is_string($sheetXml)
                || $sheetXml === ''
            ) {
                throw new \RuntimeException(
                    'تعذر إنشاء ورقة Excel.'
                );
            }

            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                $sheetXml
            );
        } finally {
            $zip->close();
        }

        if (
            ! is_file($path)
            || filesize($path) === 0
        ) {
            throw new \RuntimeException(
                'ملف Excel الناتج فارغ.'
            );
        }
    }

    private function xlsxColumnName(
        int $index
    ): string {
        $index++;

        $name = '';

        while ($index > 0) {
            $remainder =
                ($index - 1) % 26;

            $name =
                chr(
                    65 + $remainder
                )
                .$name;

            $index = intdiv(
                $index - 1,
                26
            );
        }

        return $name;
    }

    private function xlsxColumnIndex(
        string $letters
    ): int {
        $letters =
            strtoupper($letters);

        $number = 0;

        foreach (
            str_split($letters) as $letter
        ) {
            $number =
                ($number * 26)
                + (
                    ord($letter)
                    - 64
                );
        }

        return max(
            0,
            $number - 1
        );
    }

    private function currentEmployeeName(): string
    {
        $name = trim(
            (string)
                auth()->user()->name
        );

        return $name !== ''
            ? $name
            : 'غير مسند';
    }

    private function assertXlsxSupport(): void
    {
        if (
            ! class_exists(
                ZipArchive::class
            )
            || ! class_exists(
                XMLWriter::class
            )
            || ! class_exists(
                \SimpleXMLElement::class
            )
        ) {
            throw new \RuntimeException(
                'دعم Excel غير متاح '
                .'على السيرفر.'
            );
        }
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

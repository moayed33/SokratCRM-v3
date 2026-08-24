<?php

namespace App\Http\Controllers;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
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

class LeadFollowupController extends Controller
{
    public function index(
        Request $request,
        string $lead
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with([
                'status.stage',
                'assignedUser:id,name',
                'campaigns:id,name',
            ])
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('viewFollowups', $leadRecord);

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        /*
         * Kanban drag/drop chooses only the
         * proposed destination status.
         * This GET request never changes the lead.
         */
        $requestedStatusId = (int)
            $request->query(
                'target_status_id',
                0
            );

        $defaultStatusId =
            $statuses->contains(
                static fn (
                    LeadStatus $status
                ): bool => (int) $status->id
                    === $requestedStatusId
            )
                ? $requestedStatusId
                : (int)
                    $leadRecord
                        ->lead_status_id;

        $statusGroups = $statuses->groupBy(
            static fn (
                LeadStatus $status
            ): string => trim(
                (string)
                    $status->stage?->name_ar
            ) !== ''
                    ? (string)
                        $status->stage?->name_ar
                    : 'بدون مرحلة'
        );

        $communicationTypes =
            $this->communicationTypes();

        $requestedType = trim(
            (string)
                $request->query(
                    'channel',
                    'other'
                )
        );

        $defaultCommunicationType =
            array_key_exists(
                $requestedType,
                $communicationTypes
            )
                ? $requestedType
                : 'other';

        $followups = LeadFollowup::query()
            ->with([
                'fromStatus.stage',
                'toStatus.stage',
                'user:id,name',
            ])
            ->where(
                'lead_id',
                $leadRecord->id
            )
            ->orderByDesc('followed_up_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $currentEmployee =
            $this->currentEmployeeName();

        $callPhone = $this->callPhone(
            (string) $leadRecord->phone
        );

        $quotationPath = trim(
            (string)
                $leadRecord->quotation_file_path
        );

        $hasQuotationFile = (
            $quotationPath !== ''
            && Storage::disk('local')->exists(
                $quotationPath
            )
        );

        $quotationFileName =
            $hasQuotationFile
                ? basename($quotationPath)
                : null;

        $quotationFileHelpText =
            $hasQuotationFile
                ? 'يوجد ملف عرض سعر حالي. '
                    .'اختر ملفًا جديدًا فقط '
                    .'لاستبداله.'
                : 'الملفات المدعومة: PDF, Word, '
                    .'Excel والصور. الحد الأقصى 2MB.';
        $totalLeads = Lead::query()
            ->accessibleTo($request->user())
            ->count();
        $actor = $request->user();
        $manageableCampaigns = Campaign::query()
            ->with([
                'users' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
            ])
            ->when(
                ! $actor->isSuperAdmin(),
                static fn ($query) => $actor->hasPermission(
                    CrmPermission::CAMPAIGNS_CREATE,
                )
                    ? $query->where('created_by_user_id', $actor->id)
                    : $query->whereRaw('1 = 0'),
            )
            ->orderBy('name')
            ->get()
            ->each(function (Campaign $campaign) use ($actor): void {
                $campaign->setRelation(
                    'users',
                    $campaign->users
                        ->filter(
                            static fn (User $target): bool => LeadAssignment::canAssignTo(
                                $actor,
                                $target,
                            ),
                        )
                        ->values(),
                );
            });
        $campaignAssignees = $manageableCampaigns
            ->flatMap->users
            ->unique('id')
            ->sortBy('name')
            ->values();
        $currentCampaign = $leadRecord->campaigns->first(
            static fn (Campaign $campaign): bool => $manageableCampaigns
                ->contains('id', $campaign->id),
        );

        return view(
            'leads.followups.index',
            [
                'lead' => $leadRecord,
                'statusGroups' => $statusGroups,
                'communicationTypes' => $communicationTypes,
                'defaultCommunicationType' => $defaultCommunicationType,
                'followups' => $followups,
                'currentEmployee' => $currentEmployee,
                'callPhone' => $callPhone,
                'hasQuotationFile' => $hasQuotationFile,
                'quotationFileName' => $quotationFileName,
                'quotationFileHelpText' => $quotationFileHelpText,
                'defaultStatusId' => $defaultStatusId,
                'totalLeads' => $totalLeads,
                'manageableCampaigns' => $manageableCampaigns,
                'campaignAssignees' => $campaignAssignees,
                'currentCampaign' => $currentCampaign,
            ]
        );
    }

    public function store(
        Request $request,
        string $lead
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $communicationTypes =
            $this->communicationTypes();

        $leadRecord = Lead::query()
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('createFollowup', $leadRecord);
        $campaign = null;
        $targetUser = null;

        if ($request->filled('campaign_id') || $request->filled('assigned_user_id')) {
            $campaign = Campaign::query()->findOrFail(
                $request->integer('campaign_id'),
            );
            $actor = $request->user();

            abort_unless(
                $actor->isSuperAdmin()
                || (
                    (int) $campaign->created_by_user_id === (int) $actor->id
                    && $actor->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
                ),
                403,
            );

            $targetUser = User::query()->findOrFail(
                $request->integer('assigned_user_id'),
            );

            abort_unless(
                $campaign->users()->whereKey($targetUser->id)->exists()
                && LeadAssignment::canAssignTo($actor, $targetUser),
                403,
            );
        }

        $statusInput = $request->validate(
            [
                'lead_status_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],
            ],
            [
                'lead_status_id.required' => 'اختر الحالة التي تمت عليها المتابعة.',
                'lead_status_id.exists' => 'الحالة المختارة غير موجودة.',
            ]
        );

        $status = LeadStatus::query()
            ->findOrFail(
                (int)
                    $statusInput[
                        'lead_status_id'
                    ]
            );

        $businessStatusCodes = [
            'new',
            'no_answer',
            'not_interested',
            'donor',
        ];

        $hasBusinessDetails = in_array(
            $status->code,
            $businessStatusCodes,
            true
        );

        $isQuotationStage = false;

        $currentQuotationPath = trim(
            (string)
                $leadRecord->quotation_file_path
        );

        $hasCurrentQuotationFile = (
            $currentQuotationPath !== ''
            && Storage::disk('local')->exists(
                $currentQuotationPath
            )
        );

        $solutionTypeInput = trim(
            (string)
                $request->input(
                    'solution_type',
                    ''
                )
        );

        /*
         * CRM FOLLOWUP CONDITIONAL DATE V2 START
         *
         * A next follow-up date is mandatory
         * for all statuses except not_interested.
         */
        $followupStatusCode = (string)
            LeadStatus::query()
                ->whereKey(
                    (int)
                        $statusInput[
                            'lead_status_id'
                        ]
                )
                ->value('code');

        if ($followupStatusCode === '') {
            abort(422);
        }

        /*
         * CRM NEW EXECUTION NO FOLLOWUP V7
         *
         * No future appointment while the
         * destination status is:
         * new, not_interested or execution.
         */
        $requiresNextFollowUp = $followupStatusCode === 'no_answer';

        /* CRM FOLLOWUP CONDITIONAL DATE V2 END */

        $validated = $request->validate(
            [
                'lead_status_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],

                'communication_type' => [
                    'required',
                    'string',
                    Rule::in(
                        array_keys(
                            $communicationTypes
                        )
                    ),
                ],

                'outcome' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'next_follow_up_at' => [
                    $requiresNextFollowUp
                        ? 'required'
                        : 'nullable',
                    'date',
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
                        && $solutionTypeInput ===
                            'erp'
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
                'campaign_id' => [
                    'nullable',
                    'integer',
                    'exists:campaigns,id',
                ],
                'assigned_user_id' => [
                    Rule::requiredIf($campaign !== null),
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],
            ],
            [
                'lead_status_id.required' => 'اختر الحالة التي تمت عليها المتابعة.',
                'lead_status_id.exists' => 'الحالة المختارة غير موجودة.',

                'communication_type.required' => 'اختر نوع التواصل.',
                'communication_type.in' => 'نوع التواصل المختار غير صحيح.',
                'outcome.max' => 'نتيجة المتابعة لا يمكن أن '
                    .'تتجاوز 5000 حرف.',

                'next_follow_up_at.required' => 'حدد موعد المتابعة القادمة. '
                    .'الموعد اختياري فقط في حالة '
                    .'غير مهتم.',
                'next_follow_up_at.date_format' => 'موعد المتابعة القادمة غير صحيح.',

                'disinterest_reason.required' => 'سبب عدم الاهتمام مطلوب.',

                'solution_type.required' => 'نوع النظام مطلوب.',

                'lines_count.required' => 'عدد الخطوط مطلوب.',

                'extensions.required' => 'تفاصيل الملحقات مطلوبة.',

                'departments.required' => 'الأقسام المطلوبة مطلوبة.',

                'quotation_file.required' => 'ملف عرض السعر مطلوب.',

                'quotation_file.max' => 'الحد الأقصى لملف عرض '
                    .'السعر 2MB.',

                'quotation_file.mimes' => 'صيغة ملف عرض السعر '
                    .'غير مدعومة.',
            ]
        );

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
            if (
                $value === null
                || $value === ''
            ) {
                return null;
            }

            return (int) $value;
        };

        $employeeName =
            $this->currentEmployeeName();
        $currentUserId = (int) $request->user()->id;

        $outcome = trim(
            (string) (
                $validated['outcome']
                ?? ''
            )
        );

        $communicationType =
            (string)
                $validated[
                    'communication_type'
                ];

        $nextFollowUpAt = null;

        if (
            isset(
                $validated[
                    'next_follow_up_at'
                ]
            )
            && trim(
                (string)
                    $validated[
                        'next_follow_up_at'
                    ]
            ) !== ''
        ) {
            try {
                $nextFollowUpAt = Carbon::parse(
                    (string) $validated['next_follow_up_at'],
                    (string) config('app.timezone', 'UTC')
                );
            } catch (\Throwable) {
                $nextFollowUpAt = null;
            }
        }
        if ($status->code === 'not_interested') {
            $nextFollowUpAt = null;
        }

        $newQuotationPath = null;
        $uploadedQuotationName = null;

        if (
            $request->hasFile(
                'quotation_file'
            )
        ) {
            $uploadedQuotationName =
                mb_substr(
                    trim(
                        (string)
                            $request
                                ->file(
                                    'quotation_file'
                                )
                                ->getClientOriginalName()
                    ),
                    0,
                    255
                );

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

            $newQuotationPath =
                $storedPath;
        }

        $stageData = [
            'company_name' => $hasBusinessDetails
                    ? $nullableText(
                        $validated[
                            'company_name'
                        ] ?? null
                    )
                    : null,

            'activity' => $hasBusinessDetails
                    ? $nullableText(
                        $validated[
                            'activity'
                        ] ?? null
                    )
                    : null,

            'governorate' => $hasBusinessDetails
                    ? $nullableText(
                        $validated[
                            'governorate'
                        ] ?? null
                    )
                    : null,

            'address' => $hasBusinessDetails
                    ? $nullableText(
                        $validated[
                            'address'
                        ] ?? null
                    )
                    : null,

            'users_count' => $hasBusinessDetails
                    ? $integerOrNull(
                        $validated[
                            'users_count'
                        ] ?? null
                    )
                    : null,

            'branches_count' => $hasBusinessDetails
                    ? $integerOrNull(
                        $validated[
                            'branches_count'
                        ] ?? null
                    )
                    : null,

            'job_title' => $hasBusinessDetails
                    ? $nullableText(
                        $validated[
                            'job_title'
                        ] ?? null
                    )
                    : null,

            'disinterest_reason' => $status->code
                    === 'not_interested'
                        ? $nullableText(
                            $validated[
                                'disinterest_reason'
                            ] ?? null
                        )
                        : null,

            'solution_type' => $isQuotationStage
                    ? $nullableText(
                        $validated[
                            'solution_type'
                        ] ?? null
                    )
                    : null,

            'lines_count' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'call_center'
            )
                ? (int)
                    $validated[
                        'lines_count'
                    ]
                : null,

            'extensions' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'call_center'
            )
                ? $nullableText(
                    $validated[
                        'extensions'
                    ] ?? null
                )
                : null,

            'departments' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'erp'
            )
                ? $nullableText(
                    $validated[
                        'departments'
                    ] ?? null
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

        $fieldLabels = [
            'company_name' => 'اسم الشركة',
            'activity' => 'النشاط',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'users_count' => 'عدد المستخدمين',
            'branches_count' => 'عدد الفروع',
            'job_title' => 'المنصب',
            'disinterest_reason' => 'سبب عدم الاهتمام',
            'solution_type' => 'نوع النظام',
            'lines_count' => 'عدد الخطوط',
            'extensions' => 'الملحقات',
            'departments' => 'الأقسام',
            'quotation_file_path' => 'ملف عرض السعر',
        ];

        $formatChangeValue =
            static function (
                string $field,
                mixed $value
            ): string {
                if (
                    $value === null
                    || $value === ''
                ) {
                    return '----';
                }

                if (
                    $field
                    === 'solution_type'
                ) {
                    return match (
                        (string) $value
                    ) {
                        'call_center' => 'Call Center',
                        'erp' => 'ERP',
                        default => (string) $value,
                    };
                }

                if (
                    $field
                    === 'quotation_file_path'
                ) {
                    return basename(
                        (string) $value
                    );
                }

                return trim(
                    (string) $value
                );
            };

        try {
            $lockedLead = Lead::query()
                ->findOrFail((int) $lead);

            $fieldChanges = [];

            if ($campaign !== null && $targetUser !== null) {
                $oldCampaignName = $lockedLead->campaigns()
                    ->value('campaigns.name') ?? 'بدون حملة';
                $oldAssigneeName = $lockedLead->assignedUser?->name
                    ?? $lockedLead->assigned_employee
                    ?? 'غير مسند';

                if ($oldCampaignName !== $campaign->name) {
                    $fieldChanges[] = [
                        'field' => 'campaign_id',
                        'label' => 'الحملة',
                        'old' => $oldCampaignName,
                        'new' => $campaign->name,
                    ];
                }

                if ((int) $lockedLead->assigned_user_id !== (int) $targetUser->id) {
                    $fieldChanges[] = [
                        'field' => 'assigned_user_id',
                        'label' => 'الموظف المسؤول',
                        'old' => $oldAssigneeName,
                        'new' => $targetUser->name,
                    ];
                }
            }

            foreach ($fieldLabels as $field => $label) {
                $oldRaw = $lockedLead->getAttribute($field);
                $newRaw = $stageData[$field] ?? null;

                $oldComparable = $oldRaw === null ? null : (string) $oldRaw;
                $newComparable = $newRaw === null ? null : (string) $newRaw;

                if ($oldComparable === $newComparable) {
                    continue;
                }

                $oldValue = $formatChangeValue($field, $oldRaw);
                $newValue = $formatChangeValue($field, $newRaw);

                if (
                    $field === 'quotation_file_path'
                    && $uploadedQuotationName !== null
                    && $uploadedQuotationName !== ''
                ) {
                    $newValue = $uploadedQuotationName;
                }

                $fieldChanges[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }

            $transitionContext = [
                'record_followup' => true,
                'communication_type' => $communicationType,
                'outcome' => $outcome,
                'employee_name' => $employeeName,
                'next_follow_up_at' => $nextFollowUpAt,
                'response_details' => $outcome,
                'contact_date' => now(),
                'responding_user_id' => $currentUserId,
                'lead_attributes' => $stageData,
                'field_changes' => $fieldChanges === [] ? null : $fieldChanges,
                'history_note' => 'متابعة - '
                    .$communicationTypes[$communicationType]
                    .': '
                    .$outcome,
            ];

            if ($targetUser !== null) {
                $transitionContext['assigned_user_id'] = $targetUser->id;
                $transitionContext['assigned_employee'] = $targetUser->name;
            }
            if ($campaign !== null) {
                $transitionContext['campaign'] = $campaign;
            }

            $transitionResult = app(\App\Services\LeadTransitionService::class)->transition(
                $lockedLead,
                $status,
                $request->user(),
                $transitionContext
            );

            $leadRecord = $transitionResult['lead'];
        } catch (\Throwable $exception) {
            if (
                $newQuotationPath
                !== null
            ) {
                Storage::disk('local')
                    ->delete(
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

        $redirectParameters =
            $request->boolean(
                'kanban_popup'
            )
                ? [
                    'lead' => $leadRecord->id,

                    'kanban_popup' => 1,

                    'saved' => 1,
                ]
                : $leadRecord;

        return redirect()
            ->route(
                'v2.leads.followups.index',
                $redirectParameters
            )
            ->with(
                'success',
                'تم حفظ بيانات المرحلة '
                .'وتسجيل المتابعة بنجاح باسم '
                .$employeeName
                .'.'
            );
    }

    private function communicationTypes(): array
    {
        return [
            'call' => 'اتصال هاتفي',
            'whatsapp' => 'واتساب',
            'meeting' => 'مقابلة',
            'email' => 'بريد إلكتروني',
            'other' => 'متابعة عامة',
        ];
    }

    private function currentEmployeeName(): string
    {
        $employeeName = mb_substr(
            trim(
                (string)
                    auth()->user()->name
            ),
            0,
            150
        );

        return $employeeName !== ''
            ? $employeeName
            : 'المستخدم';
    }

    private function callPhone(
        string $phone
    ): ?string {
        $digits = preg_replace(
            '/\D+/',
            '',
            trim($phone)
        ) ?? '';

        return preg_match(
            '/^[0-9]{2,20}$/',
            $digits
        ) === 1
            ? $digits
            : null;
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\Campaign;
use App\Models\DonationType;
use App\Models\InstantDonationMethod;
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
    private const DONATION_CYCLES = [
        'one_time' => null,
        'monthly' => 1,
        'quarterly' => 3,
        'semi_annual' => 6,
        'annual' => 12,
        'other' => null,
    ];

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
                'donationTypeRel',
                'donations',
            ])
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('viewFollowups', $leadRecord);

        $statuses = LeadStatus::query()
            ->with('stage')
            ->whereHas('stage', static fn ($q) => $q->where('is_active', true))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        /*
         * A transition trigger proposes a destination. Opening the form
         * never mutates the lead; the change happens only after submit.
         */
        $requestedStatusId = (int) $request->query('target_status_id', 0);
        $requestedStatusCode = mb_substr(
            trim((string) $request->query('target_status_code', '')),
            0,
            50,
        );

        if (
            $requestedStatusCode === ''
            && ($request->boolean('make_donation') || $request->query('donation') === '1')
        ) {
            $requestedStatusCode = 'donor';
        }

        if ($requestedStatusId === 0 && $requestedStatusCode !== '') {
            $requestedStatusId = (int) ($statuses->firstWhere('code', $requestedStatusCode)?->id ?? 0);
        }

        $defaultStatusId = $statuses->contains(
            static fn (
                LeadStatus $status
            ): bool => (int) $status->id === $requestedStatusId
        )
            ? $requestedStatusId
            : (int) $leadRecord->lead_status_id;
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

        $donationTypes = DonationType::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $instantDonationMethods = InstantDonationMethod::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

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
                'donation.donationType',
                'donation.recordedBy:id,name',
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
        $totalLeads = Lead::query()
            ->accessibleTo($request->user())
            ->count();
        $actor = $request->user();
        $canAssignCollections = $actor->hasPermission(CrmPermission::COLLECTIONS_ASSIGN);
        $collectors = $canAssignCollections
            ? User::query()
                ->where('is_active', true)
                ->when(
                    $leadRecord->branch_id === null,
                    static fn ($query) => $query->whereNull('branch_id'),
                    static fn ($query) => $query->where('branch_id', $leadRecord->branch_id),
                )
                ->whereHas('groups.permissions', static fn ($query) => $query->where(
                    'permissions.code',
                    CrmPermission::COLLECTIONS_COLLECT->value,
                ))
                ->with(['collectionSubregion.governorate'])
                ->orderBy('name')
                ->get(['id', 'name', 'collection_subregion_id', 'collection_zone'])
            : collect();
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
        $stageFieldsMap = \App\Models\PipelineStage::query()
            ->with(['activeFields'])
            ->get()
            ->keyBy('id')
            ->map(static fn ($st) => $st->activeFields);

        $currentStageValues = $leadRecord->stageFieldValues
            ->keyBy('field_key')
            ->map(static fn ($v) => $v->getTypedValue())
            ->all();

        return view(
            'leads.followups.index',
            [
                'lead' => $leadRecord,
                'statusGroups' => $statusGroups,
                'communicationTypes' => $communicationTypes,
                'donationTypes' => $donationTypes,
                'instantDonationMethods' => $instantDonationMethods,
                'donationCycles' => array_keys(self::DONATION_CYCLES),
                'canAssignCollections' => $canAssignCollections,
                'collectors' => $collectors,
                'defaultCommunicationType' => $defaultCommunicationType,
                'followups' => $followups,
                'currentEmployee' => $currentEmployee,
                'callPhone' => $callPhone,
                'defaultStatusId' => $defaultStatusId,
                'totalLeads' => $totalLeads,
                'manageableCampaigns' => $manageableCampaigns,
                'campaignAssignees' => $campaignAssignees,
                'currentCampaign' => $currentCampaign,
                'stageFieldsMap' => $stageFieldsMap,
                'currentStageValues' => $currentStageValues,
                'governorates' => \App\Models\Governorate::query()->where('is_active', true)->with('activeSubregions')->orderBy('name_ar')->get(),
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

        $isCurrentDonor = $leadRecord->status()
            ->where('code', 'donor')
            ->exists();
        $isDonorConversion = $status->code === 'donor' && ! $isCurrentDonor;
        $recordsDonation = $isDonorConversion
            || ($status->code === 'donor' && $request->boolean('record_donation'));
        $donationWay = $recordsDonation
            ? trim((string) $request->input('donation_way'))
            : null;
        if ($recordsDonation && $donationWay === '') {
            $donationWay = 'instant';
            $request->merge(['donation_way' => $donationWay]);
        }
        $recordsInstantDonation = $recordsDonation && $donationWay === 'instant';
        $createsCollection = $recordsDonation && $donationWay === 'collection';
        if ($recordsInstantDonation && ! $request->filled('instant_donation_method_id')) {
            $fallbackMethodId = InstantDonationMethod::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->value('id');

            if ($fallbackMethodId !== null) {
                $request->merge(['instant_donation_method_id' => (int) $fallbackMethodId]);
            }
        }

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

                'donation_type_id' => [
                    Rule::requiredIf($recordsDonation),
                    'nullable',
                    'integer',
                    Rule::exists('donation_types', 'id')->where(
                        static fn ($query) => $query->where('is_active', true)
                    ),
                ],

                'donation_value' => [
                    Rule::requiredIf($recordsDonation),
                    'nullable',
                    'numeric',
                    'gt:0',
                    'max:999999999999',
                ],

                'donation_cycle' => [
                    Rule::requiredIf($recordsDonation),
                    'nullable',
                    'string',
                    Rule::in(array_keys(self::DONATION_CYCLES)),
                ],

                'preferred_donation_date' => [
                    Rule::requiredIf($recordsDonation && $request->input('donation_cycle') === 'other'),
                    'nullable',
                    'date',
                ],

                'preferred_donation_time' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'preferred_donation_note' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'donation_way' => [
                    Rule::requiredIf($recordsDonation),
                    'nullable',
                    'string',
                    Rule::in(['instant', 'collection']),
                ],

                'instant_donation_method_id' => [
                    Rule::requiredIf($recordsInstantDonation),
                    'nullable',
                    'integer',
                    Rule::exists('instant_donation_methods', 'id')->where(
                        static fn ($query) => $query->where('is_active', true)
                    ),
                ],
                'instant_donation_account' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'collection_due_at' => [
                    Rule::requiredIf($createsCollection),
                    'nullable',
                    'date',
                ],

                'collection_address' => [
                    Rule::requiredIf($createsCollection),
                    'nullable',
                    'string',
                    'max:255',
                ],

                'collection_notes' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'assigned_collector_user_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('users', 'id')->where(
                        static fn ($query) => $query->where('is_active', true)
                    ),
                ],

                'record_donation' => [
                    'nullable',
                    'boolean',
                ],

                'donation_receipt' => [
                    'nullable',
                    'file',
                    'mimes:png,jpg,jpeg,webp',
                    'max:5120',
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

                'governorate_id' => [
                    'nullable',
                    'integer',
                    'exists:governorates,id',
                ],

                'subregion_id' => [
                    'nullable',
                    'integer',
                    'exists:governorate_subregions,id',
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

                'donation_type_id.required' => 'اختر نوع التبرع قبل تحويل العميل إلى متبرع.',
                'donation_type_id.exists' => 'نوع التبرع المختار غير متاح.',
                'donation_value.required' => 'أدخل قيمة التبرع قبل تحويل العميل إلى متبرع.',
                'donation_value.numeric' => 'قيمة التبرع يجب أن تكون رقمًا صحيحًا.',
                'donation_value.gt' => 'قيمة التبرع يجب أن تكون أكبر من صفر.',
                'donation_cycle.required' => 'اختر دورة التبرع قبل تحويل العميل إلى متبرع.',
                'donation_cycle.in' => 'دورة التبرع المختارة غير صحيحة.',
                'preferred_donation_date.required' => __('crm.preferred_donation_date_required') ?: 'يجب تحديد التاريخ المناسب عند اختيار دورة تبرع أخرى.',
                'preferred_donation_date.date' => 'يرجى إدخال تاريخ صحيح للموعد المناسب.',
                'preferred_donation_time.date_format' => 'يرجى إدخال وقت صحيح للموعد المناسب (ساعة:دقيقة).',
                'donation_way.required' => 'اختر طريقة استلام التبرع.',
                'donation_way.in' => 'طريقة استلام التبرع غير صحيحة.',
                'instant_donation_method_id.required' => 'اختر وسيلة التبرع الفوري.',
                'instant_donation_method_id.exists' => 'وسيلة التبرع الفوري غير متاحة.',
                'collection_due_at.required' => 'حدد موعد التحصيل.',
                'collection_due_at.date' => 'موعد التحصيل غير صحيح.',
                'governorate_id.required' => __('crm.collection_governorate_required'),
                'subregion_id.required' => __('crm.collection_subregion_required'),
                'collection_address.required' => __('crm.collection_address_required'),
                'donation_receipt.mimes' => 'إيصال التبرع يجب أن يكون صورة PNG أو JPG أو WEBP.',
                'donation_receipt.max' => 'الحد الأقصى لصورة إيصال التبرع هو 5MB.',

                'disinterest_reason.required' => 'سبب عدم الاهتمام مطلوب.',
            ]
        );

        $assignedCollectorId = isset($validated['assigned_collector_user_id'])
            ? (int) $validated['assigned_collector_user_id']
            : null;
        if ($assignedCollectorId !== null) {
            abort_unless(
                $createsCollection
                && $request->user()->hasPermission(CrmPermission::COLLECTIONS_ASSIGN)
                && User::query()
                    ->whereKey($assignedCollectorId)
                    ->where('is_active', true)
                    ->where('branch_id', $leadRecord->branch_id)
                    ->whereHas('groups.permissions', static fn ($query) => $query->where(
                        'permissions.code',
                        CrmPermission::COLLECTIONS_COLLECT->value,
                    ))
                    ->exists(),
                403,
            );
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

        $contactedAt = now();
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

        if ($recordsInstantDonation || $recordsDonation) {
            $cycle = (string) ($validated['donation_cycle'] ?? '');
            if ($cycle === 'other' && ! empty($validated['preferred_donation_date'])) {
                $dateStr = (string) $validated['preferred_donation_date'];
                $timeStr = ! empty($validated['preferred_donation_time']) ? (string) $validated['preferred_donation_time'] : '00:00';
                try {
                    $nextFollowUpAt = Carbon::parse($dateStr . ' ' . $timeStr);
                } catch (\Throwable) {
                    $nextFollowUpAt = Carbon::parse($dateStr);
                }
            } elseif ($recordsInstantDonation) {
                $cycleMonths = self::DONATION_CYCLES[$cycle] ?? null;
                $nextFollowUpAt = $cycleMonths === null
                    ? null
                    : $contactedAt->copy()->addMonthsNoOverflow($cycleMonths);
            }
        }

        $newReceiptPath = null;
        $receiptOriginalName = null;
        $donationType = $recordsDonation
            ? DonationType::query()->findOrFail((int) $validated['donation_type_id'])
            : null;

        if ($recordsInstantDonation && $request->hasFile('donation_receipt')) {
            try {
                $receipt = $request->file('donation_receipt');
                $receiptOriginalName = mb_substr(
                    trim((string) $receipt->getClientOriginalName()),
                    0,
                    255,
                );
                $storedReceipt = $receipt->store(
                    'crm-v2/donation-receipts/'.$leadRecord->id,
                    'local',
                );

                if (! is_string($storedReceipt) || trim($storedReceipt) === '') {
                    throw new \RuntimeException('Donation receipt storage failed.');
                }

                $newReceiptPath = $storedReceipt;
            } catch (\Throwable $exception) {
                throw $exception;
            }
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

            'governorate_id' => ! empty($validated['governorate_id'])
                    ? (int) $validated['governorate_id']
                    : ($hasBusinessDetails && ! empty($validated['lead_business_governorate_id']) ? (int) $validated['lead_business_governorate_id'] : $leadRecord->governorate_id),

            'subregion_id' => ! empty($validated['subregion_id'])
                    ? (int) $validated['subregion_id']
                    : ($hasBusinessDetails && ! empty($validated['lead_business_subregion_id']) ? (int) $validated['lead_business_subregion_id'] : $leadRecord->subregion_id),
            'address' => ! empty($validated['collection_address'])
                    ? trim((string) $validated['collection_address'])
                    : ($hasBusinessDetails ? $nullableText($validated['address'] ?? null) : $leadRecord->address),

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
        ];

        if ($recordsDonation) {
            $stageData['donation_type'] = $donationType->name_ar;
            $stageData['donation_type_id'] = $donationType->id;
            $stageData['donation_value'] = (float) $validated['donation_value'];
            $stageData['donation_cycle'] = (string) $validated['donation_cycle'];
        }

        $fieldLabels = [
            'company_name' => 'اسم الشركة',
            'activity' => 'النشاط',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'users_count' => 'عدد المستخدمين',
            'branches_count' => 'عدد الفروع',
            'job_title' => 'المنصب',
            'disinterest_reason' => 'سبب عدم الاهتمام',
        ];

        if ($recordsDonation) {
            $fieldLabels += [
                'donation_type' => 'نوع التبرع',
                'donation_value' => 'قيمة التبرع',
                'donation_cycle' => 'دورة التبرع',
            ];
        }

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
                'contact_date' => $contactedAt,
                'responding_user_id' => $currentUserId,
                'disinterest_reason' => $request->input('disinterest_reason'),
                'lead_attributes' => $stageData,
                'field_changes' => $fieldChanges === [] ? null : $fieldChanges,
                'history_note' => 'متابعة - '
                    .$communicationTypes[$communicationType]
                    .': '
                    .$outcome
                    .((($validated['donation_cycle'] ?? '') === 'other' && ! empty($validated['preferred_donation_date']))
                        ? ' (الموعد المناسب: ' . $validated['preferred_donation_date'] . (! empty($validated['preferred_donation_time']) ? ' ' . $validated['preferred_donation_time'] : '') . ')'
                        : ''),
                'stage_fields' => (array) $request->input('stage_fields', []),
                'stage_fields_custom' => (array) $request->input('stage_fields_custom', []),
                'preferred_donation_date' => $validated['preferred_donation_date'] ?? null,
                'preferred_donation_time' => $validated['preferred_donation_time'] ?? null,
                'preferred_donation_note' => $validated['preferred_donation_note'] ?? null,
            ];

            if ($recordsInstantDonation) {
                $transitionContext['donation'] = [
                    'donation_type_id' => $stageData['donation_type_id'],
                    'donation_type' => $stageData['donation_type'],
                    'amount' => (string) $validated['donation_value'],
                    'cycle' => (string) $validated['donation_cycle'],
                    'donation_way' => 'instant',
                    'instant_donation_method_id' => (int) $validated['instant_donation_method_id'],
                    'instant_donation_account' => trim((string) ($validated['instant_donation_account'] ?? '')) ?: null,
                    'receipt_path' => $newReceiptPath,
                    'receipt_original_name' => $receiptOriginalName,
                    'donated_at' => $contactedAt,
                ];
            } elseif ($createsCollection) {
                $transitionContext['collection'] = [
                    'donation_type_id' => $stageData['donation_type_id'],
                    'donation_type' => $stageData['donation_type'],
                    'expected_amount' => (string) $validated['donation_value'],
                    'cycle' => (string) $validated['donation_cycle'],
                    'due_at' => (string) $validated['collection_due_at'],
                    'collection_address' => trim((string) $validated['collection_address']),
                    'governorate_id' => ! empty($validated['governorate_id']) ? (int) $validated['governorate_id'] : $leadRecord->governorate_id,
                    'subregion_id' => ! empty($validated['subregion_id']) ? (int) $validated['subregion_id'] : $leadRecord->subregion_id,
                    'notes' => isset($validated['collection_notes'])
                        ? trim((string) $validated['collection_notes']) ?: null
                        : null,
                    'assigned_collector_user_id' => $assignedCollectorId,
                ];
            }

            if ($recordsDonation) {
                $transitionContext['donation_intent'] = $isDonorConversion
                    ? 'conversion'
                    : 'explicit';
            }

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
            if ($newReceiptPath !== null) {
                Storage::disk('local')->delete($newReceiptPath);
            }

            throw $exception;
        }

        $nextLead = Lead::query()
            ->accessibleTo($request->user())
            ->where('id', '!=', $leadRecord->id)
            ->where(function ($q) {
                $q->where('next_follow_up_at', '<=', now())
                  ->orWhereNull('next_follow_up_at');
            })
            ->orderByRaw('next_follow_up_at IS NULL, next_follow_up_at ASC')
            ->first();

        $context = $request->input('context', $request->input('source', ''));

        $redirectParameters =
            $request->boolean(
                'kanban_popup'
            )
                ? array_filter([
                    'lead' => $leadRecord->id,
                    'kanban_popup' => 1,
                    'saved' => 1,
                    'context' => $context ?: null,
                ])
                : $leadRecord;

        $redirect = redirect()
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

        if ($nextLead !== null) {
            $nextLeadUrl = route(
                'v2.leads.followups.index',
                $request->boolean('kanban_popup')
                    ? array_filter(['lead' => $nextLead->id, 'kanban_popup' => 1, 'context' => $context ?: null])
                    : $nextLead
            );

            $redirect->with('next_lead_url', $nextLeadUrl);
            $redirect->with('next_lead_id', $nextLead->id);
        }

        return $redirect;
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

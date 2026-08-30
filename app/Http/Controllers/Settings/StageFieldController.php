<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StageFieldController extends Controller
{
    /**
     * Friendly preset templates for common CRM stage workflows.
     */
    public const PRESETS = [
        'no_answer' => [
            'key' => 'no_answer',
            'name_ar' => 'لم يتم الرد / معاودة اتصال',
            'name_en' => 'No Answer / Callback',
            'description_ar' => 'موعد إعادة الاتصال الإجباري وملاحظات المحاولة',
            'description_en' => 'Mandatory callback date and attempt notes',
            'icon' => 'bi-telephone-x',
            'questions' => [
                [
                    'key' => 'callback_at',
                    'label_ar' => 'موعد إعادة الاتصال',
                    'label_en' => 'Next Callback Date',
                    'type' => 'datetime',
                    'is_required' => true,
                    'placeholder_ar' => 'حدد تاريخ ووقت إعادة الاتصال',
                    'placeholder_en' => 'Select callback date and time',
                    'help_text_ar' => 'موعد الاتصال القادم بالعميل لمتابعة الرد',
                    'help_text_en' => 'Next scheduled callback date for this lead',
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات المحاولة',
                    'label_en' => 'Attempt Notes',
                    'type' => 'textarea',
                    'is_required' => false,
                    'placeholder_ar' => 'أدخل أي ملاحظات حول محاولة الاتصال',
                    'placeholder_en' => 'Enter any notes about this attempt',
                ],
            ],
        ],
        'not_interested' => [
            'key' => 'not_interested',
            'name_ar' => 'غير مهتم / اعتذار',
            'name_en' => 'Not Interested / Decline',
            'description_ar' => 'سبب عدم الاهتمام وتفاصيل الرفض',
            'description_en' => 'Reason for disinterest and rejection details',
            'icon' => 'bi-x-circle',
            'questions' => [
                [
                    'key' => 'reason',
                    'label_ar' => 'سبب عدم الاهتمام',
                    'label_en' => 'Disinterest Reason',
                    'type' => 'select',
                    'is_required' => true,
                    'options' => [
                        ['value' => 'high_price', 'label_ar' => 'السعر مرتفع', 'label_en' => 'High Price'],
                        ['value' => 'not_convinced', 'label_ar' => 'غير مقتنع بالفكرة', 'label_en' => 'Not Convinced'],
                        ['value' => 'no_budget', 'label_ar' => 'لا توجد ميزانية حالياً', 'label_en' => 'No Budget Currently'],
                        ['value' => 'competitor', 'label_ar' => 'يتعامل مع جهة أخرى', 'label_en' => 'Using Another Provider'],
                        ['value' => 'bad_timing', 'label_ar' => 'التوقيت غير مناسب', 'label_en' => 'Bad Timing'],
                        ['value' => 'other', 'label_ar' => 'سبب آخر', 'label_en' => 'Other Reason'],
                    ],
                    'placeholder_ar' => 'اختر سبب عدم الاهتمام',
                    'placeholder_en' => 'Select disinterest reason',
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات وتفاصيل',
                    'label_en' => 'Notes & Details',
                    'type' => 'textarea',
                    'is_required' => false,
                    'placeholder_ar' => 'أدخل تفاصيل إضافية حول سبب الرفض',
                    'placeholder_en' => 'Enter additional details about the refusal',
                ],
            ],
        ],
        'viewing_appointment' => [
            'key' => 'viewing_appointment',
            'name_ar' => 'معاينة / موعد زيارة',
            'name_en' => 'Viewing / Appointment',
            'description_ar' => 'موعد ومكان المعاينة، وتأكيد الحضور مع سبب الإلغاء الشرطي',
            'description_en' => 'Appointment date/location, confirmation, and conditional cancellation reason',
            'icon' => 'bi-calendar-check',
            'questions' => [
                [
                    'key' => 'appointment_at',
                    'label_ar' => 'تاريخ ووقت المعاينة',
                    'label_en' => 'Appointment Date & Time',
                    'type' => 'datetime',
                    'is_required' => true,
                    'placeholder_ar' => 'حدد تاريخ ووقت المعاينة',
                    'placeholder_en' => 'Select appointment date & time',
                ],
                [
                    'key' => 'location',
                    'label_ar' => 'مكان / عنوان المعاينة',
                    'label_en' => 'Viewing Location',
                    'type' => 'text',
                    'is_required' => false,
                    'placeholder_ar' => 'عنوان العقار أو موقع المقابلة',
                    'placeholder_en' => 'Property address or meeting location',
                ],
                [
                    'key' => 'is_confirmed',
                    'label_ar' => 'هل تم تأكيد الموعد مع العميل؟',
                    'label_en' => 'Appointment Confirmed?',
                    'type' => 'checkbox',
                    'is_required' => false,
                    'placeholder_ar' => 'نعم، تم التأكيد',
                    'placeholder_en' => 'Yes, Confirmed',
                ],
                [
                    'key' => 'cancellation_reason',
                    'label_ar' => 'سبب الإلغاء أو عدم التأكيد',
                    'label_en' => 'Cancellation / Unconfirmed Reason',
                    'type' => 'text',
                    'is_required' => true,
                    'conditions' => [
                        'field' => 'is_confirmed',
                        'operator' => 'is_not_checked',
                    ],
                    'placeholder_ar' => 'اذكر سبب عدم التأكيد أو طلب التأجيل',
                    'placeholder_en' => 'Explain reason for non-confirmation or postponement',
                ],
            ],
        ],
        'donation_followup' => [
            'key' => 'donation_followup',
            'name_ar' => 'متابعة تبرع / تحصيل',
            'name_en' => 'Donation Follow-up',
            'description_ar' => 'موعد استلام التبرع وطريقة الدفع وملاحظات التحصيل',
            'description_en' => 'Donation collection date, payment method, and receipt notes',
            'icon' => 'bi-heart',
            'questions' => [
                [
                    'key' => 'scheduled_donation_date',
                    'label_ar' => 'موعد استلام / تحصيل التبرع',
                    'label_en' => 'Scheduled Collection Date',
                    'type' => 'select',
                    'is_required' => true,
                    'options' => [
                        ['value' => '1_week', 'label_ar' => 'بعد أسبوع', 'label_en' => '1 Week'],
                        ['value' => '2_weeks', 'label_ar' => 'بعد أسبوعين', 'label_en' => '2 Weeks'],
                        ['value' => '1_month', 'label_ar' => 'بعد شهر', 'label_en' => '1 Month'],
                        ['value' => '3_months', 'label_ar' => 'بعد 3 أشهر', 'label_en' => '3 Months'],
                        ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
                    ],
                    'validation_rules' => [
                        'allow_custom' => true,
                        'custom_type' => 'date',
                    ],
                    'placeholder_ar' => 'اختر موعد استلام التبرع',
                    'placeholder_en' => 'Select agreed collection date',
                ],
                [
                    'key' => 'payment_method',
                    'label_ar' => 'طريقة الدفع المقترحة',
                    'label_en' => 'Payment Method',
                    'type' => 'select',
                    'is_required' => false,
                    'options' => [
                        ['value' => 'bank_transfer', 'label_ar' => 'تحويل بنكي', 'label_en' => 'Bank Transfer'],
                        ['value' => 'collector', 'label_ar' => 'مندوب تحصيل', 'label_en' => 'Representative / Collector'],
                        ['value' => 'wallet', 'label_ar' => 'محفظة إلكترونية / فودافون كاش', 'label_en' => 'Vodafone Cash / E-Wallet'],
                        ['value' => 'office', 'label_ar' => 'مقر المؤسسة', 'label_en' => 'Headquarters'],
                    ],
                    'placeholder_ar' => 'اختر طريقة الدفع',
                    'placeholder_en' => 'Select payment method',
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات التحصيل',
                    'label_en' => 'Collection Notes',
                    'type' => 'textarea',
                    'is_required' => false,
                    'placeholder_ar' => 'أي شروط أو تعليمات للمندوب',
                    'placeholder_en' => 'Any instructions for collection representative',
                ],
            ],
        ],
    ];

    public function index(PipelineStage $stage): View
    {
        CrmDatabaseGuard::ensureConnected();

        $stage->load([
            'fields' => static fn ($q) => $q->orderBy('position')->orderBy('id')->withCount('values'),
        ]);

        $otherFields = $stage->fields->where('is_active', true)->values();

        return view('settings.stages.fields', [
            'stage' => $stage,
            'fields' => $stage->fields,
            'otherFields' => $otherFields,
            'presets' => self::PRESETS,
            'types' => PipelineStageField::TYPES,
            'operators' => PipelineStageField::OPERATORS,
        ]);
    }

    public function store(Request $request, PipelineStage $stage): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $this->validateFieldInput($request, $stage, null);

        $nextPosition = (int) ($stage->fields()->max('position') ?? 0) + 1;
        if (! empty($validated['position'])) {
            $nextPosition = (int) $validated['position'];
        }

        $conditions = $this->extractConditions($validated);
        $options = $this->extractOptions($request, $validated);

        $validationRules = [];
        if (! empty($validated['allow_custom'])) {
            $validationRules['allow_custom'] = true;
            $validationRules['custom_type'] = $validated['custom_type'] ?? 'date';
        }
        $stage->fields()->create([
            'key' => $validated['key'],
            'label_ar' => $validated['label_ar'],
            'label_en' => $validated['label_en'] ?? null,
            'type' => $validated['type'],
            'placeholder_ar' => $validated['placeholder_ar'] ?? null,
            'placeholder_en' => $validated['placeholder_en'] ?? null,
            'help_text_ar' => $validated['help_text_ar'] ?? null,
            'help_text_en' => $validated['help_text_en'] ?? null,
            'is_required' => ! empty($validated['is_required']),
            'options' => ! empty($options) ? $options : null,
            'conditions' => $conditions,
            'validation_rules' => ! empty($validationRules) ? $validationRules : null,
            'show_on_transition' => isset($validated['show_on_transition']) ? (bool) $validated['show_on_transition'] : true,
            'show_on_stage_view' => isset($validated['show_on_stage_view']) ? (bool) $validated['show_on_stage_view'] : true,
            'show_in_history' => isset($validated['show_in_history']) ? (bool) $validated['show_in_history'] : true,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
            'position' => $nextPosition,
        ]);

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', __('crm.stage_field_created_success'));
    }

    public function update(Request $request, PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        abort_if((int) $field->pipeline_stage_id !== (int) $stage->id, 404);

        $validated = $this->validateFieldInput($request, $stage, $field);

        $conditions = $this->extractConditions($validated);
        $options = $this->extractOptions($request, $validated);

        $validationRules = ! empty($field->validation_rules) && is_array($field->validation_rules) ? $field->validation_rules : [];
        if (! empty($validated['allow_custom'])) {
            $validationRules['allow_custom'] = true;
            $validationRules['custom_type'] = $validated['custom_type'] ?? 'date';
        } else {
            unset($validationRules['allow_custom'], $validationRules['custom_type']);
        }
        $field->update([
            'label_ar' => $validated['label_ar'],
            'label_en' => $validated['label_en'] ?? null,
            'type' => $validated['type'],
            'placeholder_ar' => $validated['placeholder_ar'] ?? null,
            'placeholder_en' => $validated['placeholder_en'] ?? null,
            'help_text_ar' => $validated['help_text_ar'] ?? null,
            'help_text_en' => $validated['help_text_en'] ?? null,
            'is_required' => ! empty($validated['is_required']),
            'options' => ! empty($options) ? $options : null,
            'conditions' => $conditions,
            'validation_rules' => ! empty($validationRules) ? $validationRules : null,
            'show_on_transition' => isset($validated['show_on_transition']) ? (bool) $validated['show_on_transition'] : true,
            'show_on_stage_view' => isset($validated['show_on_stage_view']) ? (bool) $validated['show_on_stage_view'] : true,
            'show_in_history' => isset($validated['show_in_history']) ? (bool) $validated['show_in_history'] : true,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
            'position' => isset($validated['position']) ? (int) $validated['position'] : $field->position,
        ]);

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', __('crm.stage_field_updated_success'));
    }

    public function destroy(PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        abort_if((int) $field->pipeline_stage_id !== (int) $stage->id, 404);

        $hasValues = $field->values()->exists();
        $field->delete();

        StageFieldSchema::flushCache((int) $stage->id);

        $message = $hasValues
            ? __('crm.stage_field_archived_with_history_notice')
            : __('crm.stage_field_deleted_success');

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', $message);
    }

    public function toggle(PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        abort_if((int) $field->pipeline_stage_id !== (int) $stage->id, 404);

        $field->update([
            'is_active' => ! $field->is_active,
        ]);

        StageFieldSchema::flushCache((int) $stage->id);

        $message = $field->is_active
            ? __('crm.stage_field_activated_success')
            : __('crm.stage_field_deactivated_success');

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', $message);
    }

    public function reorder(Request $request, PipelineStage $stage): JsonResponse|RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*' => ['integer', 'exists:pipeline_stage_fields,id'],
        ]);

        foreach ($validated['fields'] as $position => $fieldId) {
            PipelineStageField::query()
                ->where('pipeline_stage_id', $stage->id)
                ->where('id', $fieldId)
                ->update(['position' => $position + 1]);
        }

        StageFieldSchema::flushCache((int) $stage->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', __('crm.stage_fields_reordered_success'));
    }

    public function applyPreset(Request $request, PipelineStage $stage): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $request->validate([
            'preset' => ['required', 'string', Rule::in(array_keys(self::PRESETS))],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $presetKey = $validated['preset'];
        $preset = self::PRESETS[$presetKey];

        $hasExisting = $stage->fields()->exists();
        $overwrite = $request->boolean('overwrite');

        if ($hasExisting && ! $overwrite) {
            return redirect()
                ->route('v2.settings.stages.fields.index', $stage)
                ->with('error', __('crm.preset_cannot_overwrite_without_confirmation'));
        }

        if ($overwrite) {
            $stage->fields()->delete();
        }

        $position = 1;
        foreach ($preset['questions'] as $q) {
            $stage->fields()->create([
                'key' => $q['key'],
                'label_ar' => $q['label_ar'],
                'label_en' => $q['label_en'] ?? null,
                'type' => $q['type'],
                'placeholder_ar' => $q['placeholder_ar'] ?? null,
                'placeholder_en' => $q['placeholder_en'] ?? null,
                'help_text_ar' => $q['help_text_ar'] ?? null,
                'help_text_en' => $q['help_text_en'] ?? null,
                'is_required' => (bool) ($q['is_required'] ?? false),
                'options' => $q['options'] ?? null,
                'conditions' => $q['conditions'] ?? null,
                'show_on_transition' => true,
                'show_on_stage_view' => true,
                'show_in_history' => true,
                'is_active' => true,
                'position' => $position++,
            ]);
        }

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', __('crm.preset_applied_success'));
    }

    /**
     * @throws ValidationException
     */
    private function validateFieldInput(Request $request, PipelineStage $stage, ?PipelineStageField $field): array
    {
        // Safe key auto-generation and collision handling for non-technical users
        $keyInput = trim((string) $request->input('key', ''));
        if ($keyInput === '' && $field === null) {
            $labelEn = trim((string) $request->input('label_en', ''));
            $labelAr = trim((string) $request->input('label_ar', ''));
            
            // Build a base key from english label, transliterated arabic label, or random slug
            $baseKey = Str::slug($labelEn, '_');
            if ($baseKey === '') {
                $baseKey = Str::slug($labelAr, '_');
            }
            if ($baseKey === '') {
                $baseKey = 'q_' . Str::lower(Str::random(6));
            }

            // Ensure key starts with valid character and is clean alphanumeric
            $baseKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $baseKey);
            $baseKey = trim((string) $baseKey, '_');
            if ($baseKey === '') {
                $baseKey = 'question_' . Str::lower(Str::random(5));
            }

            // Resolve collision safely in this stage
            $candidateKey = $baseKey;
            $counter = 1;
            while (PipelineStageField::query()->where('pipeline_stage_id', $stage->id)->where('key', $candidateKey)->exists()) {
                $candidateKey = $baseKey . '_' . $counter++;
            }

            $keyInput = $candidateKey;
            $request->merge(['key' => $keyInput]);
        }

        $rules = [
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(PipelineStageField::TYPES)],
            'placeholder_ar' => ['nullable', 'string', 'max:255'],
            'placeholder_en' => ['nullable', 'string', 'max:255'],
            'help_text_ar' => ['nullable', 'string', 'max:1000'],
            'help_text_en' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'string', 'max:5000'],
            'options_list' => ['nullable', 'array'],
            'condition_field' => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['nullable', 'string', Rule::in(PipelineStageField::OPERATORS)],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'show_on_transition' => ['nullable', 'boolean'],
            'show_on_stage_view' => ['nullable', 'boolean'],
            'show_in_history' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:1'],
            'allow_custom' => ['nullable', 'boolean'],
            'custom_type' => ['nullable', 'string', Rule::in(['date', 'datetime', 'text'])],
        ];

        if ($field === null) {
            $rules['key'] = [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('pipeline_stage_fields', 'key')
                    ->where('pipeline_stage_id', $stage->id)
                    ->whereNull('deleted_at'),
            ];
        }

        return $request->validate($rules, [
            'label_ar.required' => __('crm.question_label_ar_required'),
            'type.required' => __('crm.question_type_required'),
            'type.in' => __('crm.question_type_invalid'),
        ]);
    }

    private function extractOptions(Request $request, array $validated): ?array
    {
        // 1. If options_list rows array submitted from interactive builder
        if ($request->has('options_list') && is_array($request->input('options_list'))) {
            $result = [];
            foreach ($request->input('options_list') as $optRow) {
                if (is_array($optRow)) {
                    $lblAr = trim((string) ($optRow['label_ar'] ?? $optRow['label'] ?? ''));
                    $lblEn = trim((string) ($optRow['label_en'] ?? $lblAr));
                    $val = trim((string) ($optRow['value'] ?? ''));
                    if ($val === '' && $lblAr !== '') {
                        $val = Str::slug($lblEn ?: $lblAr, '_');
                    }
                    if ($lblAr !== '' || $val !== '') {
                        $result[] = [
                            'value' => $val ?: 'opt_' . Str::lower(Str::random(4)),
                            'label_ar' => $lblAr ?: $val,
                            'label_en' => $lblEn ?: ($lblAr ?: $val),
                        ];
                    }
                }
            }
            if (! empty($result)) {
                return $result;
            }
        }

        // 2. Fallback to textarea options
        return StageFieldSchema::normalizeOptions($validated['options'] ?? null);
    }

    private function extractConditions(array $validated): ?array
    {
        if (! empty($validated['condition_field']) && ! empty($validated['condition_operator'])) {
            return [
                'field' => trim((string) $validated['condition_field']),
                'operator' => trim((string) $validated['condition_operator']),
                'value' => isset($validated['condition_value']) ? trim((string) $validated['condition_value']) : '',
            ];
        }

        return null;
    }
}

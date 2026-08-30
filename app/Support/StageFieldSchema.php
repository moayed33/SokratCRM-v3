<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StageFieldSchema
{
    private static array $memoizedFields = [];

    /**
     * Resolve fields for a pipeline stage with per-request memoization.
     *
     * @return Collection<int, PipelineStageField>
     */
    public static function getFieldsForStage(PipelineStage|int $stage, bool $onlyActive = true): Collection
    {
        $stageId = $stage instanceof PipelineStage ? (int) $stage->id : (int) $stage;
        $memoKey = $stageId . '_' . ($onlyActive ? '1' : '0');

        if (isset(self::$memoizedFields[$memoKey])) {
            return self::$memoizedFields[$memoKey];
        }

        $query = PipelineStageField::query()
            ->where('pipeline_stage_id', $stageId)
            ->ordered();

        if ($onlyActive) {
            $query->active();
        }

        return self::$memoizedFields[$memoKey] = $query->get();
    }

    /**
     * Clear cached fields for a stage.
     */
    public static function flushCache(int $stageId): void
    {
        unset(self::$memoizedFields[$stageId . '_1'], self::$memoizedFields[$stageId . '_0']);
        PipelineStageField::flushCache($stageId);
    }

    /**
     * Normalize options input into a standard format:
     * [['value' => '...', 'label_ar' => '...', 'label_en' => '...']]
     */
    public static function normalizeOptions(mixed $options): array
    {
        if (empty($options)) {
            return [];
        }

        if (is_string($options)) {
            $trimmed = trim($options);
            if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $options = $decoded;
                }
            } else {
                // Comma or newline separated
                $lines = preg_split('/[\r\n,]+/', $trimmed);
                $options = array_filter(array_map('trim', (array) $lines));
            }
        }

        if (! is_array($options)) {
            return [];
        }

        $result = [];
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $val = trim((string) ($option['value'] ?? $option['key'] ?? (is_numeric($key) ? '' : $key)));
                $lblAr = trim((string) ($option['label_ar'] ?? $option['label'] ?? $val));
                $lblEn = trim((string) ($option['label_en'] ?? $lblAr));
                if ($val !== '') {
                    $result[] = [
                        'value' => $val,
                        'label_ar' => $lblAr,
                        'label_en' => $lblEn,
                    ];
                }
            } elseif (is_scalar($option)) {
                $str = trim((string) $option);
                if ($str !== '') {
                    $result[] = [
                        'value' => $str,
                        'label_ar' => $str,
                        'label_en' => $str,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate a conditional rule against submitted or current data.
     */
    public static function evaluateCondition(?array $condition, array $data): bool
    {
        if (empty($condition) || empty($condition['field'])) {
            return true;
        }

        $field = (string) $condition['field'];
        $operator = (string) ($condition['operator'] ?? 'equals');
        $expectedValue = $condition['value'] ?? null;

        $actualValue = $data[$field] ?? null;

        return match ($operator) {
            'equals' => (string) ($actualValue ?? '') === (string) ($expectedValue ?? ''),
            'not_equals' => (string) ($actualValue ?? '') !== (string) ($expectedValue ?? ''),
            'is_checked' => in_array($actualValue, [true, 1, '1', 'true', 'on', 'yes'], true),
            'is_not_checked' => ! in_array($actualValue, [true, 1, '1', 'true', 'on', 'yes'], true),
            'is_empty' => $actualValue === null || $actualValue === '' || $actualValue === [],
            'is_not_empty' => $actualValue !== null && $actualValue !== '' && $actualValue !== [],
            'in' => is_array($expectedValue)
                ? in_array($actualValue, $expectedValue, true)
                : in_array($actualValue, array_map('trim', explode(',', (string) $expectedValue)), true),
            'not_in' => is_array($expectedValue)
                ? ! in_array($actualValue, $expectedValue, true)
                : ! in_array($actualValue, array_map('trim', explode(',', (string) $expectedValue)), true),
            default => true,
        };
    }

    /**
     * Build Laravel validation rules for active stage fields.
     */
    public static function buildValidationRules(
        PipelineStage|int $stage,
        array $submittedValues = [],
        string $prefix = 'stage_fields.'
    ): array {
        $fields = self::getFieldsForStage($stage, true);
        $rules = [];

        foreach ($fields as $field) {
            $fieldKey = $prefix . $field->key;
            $fieldRules = [];

            // Check conditional visibility
            $conditionMet = true;
            if (! empty($field->conditions)) {
                $conditionMet = self::evaluateCondition($field->conditions, $submittedValues);
            }

            if ($field->is_required && $conditionMet) {
                if ($field->type === 'checkbox') {
                    $fieldRules[] = 'accepted';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
            }

            // Type-specific rules
            switch ($field->type) {
                case 'text':
                case 'tel':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:10000';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;

                case 'url':
                    $fieldRules[] = 'url';
                    $fieldRules[] = 'max:500';
                    break;

                case 'date':
                    $fieldRules[] = 'date';
                    break;

                case 'datetime':
                    $fieldRules[] = 'date';
                    break;

                case 'select':
                    $opts = $field->normalizedOptions();
                    $allowedVals = array_column($opts, 'value');
                    $allowedLabelsAr = array_column($opts, 'label_ar');
                    $allowedLabelsEn = array_column($opts, 'label_en');
                    $allAllowed = array_unique(array_filter(array_merge($allowedVals, $allowedLabelsAr, $allowedLabelsEn)));
                    if ($field->allowsCustomValue()) {
                        $allAllowed = array_unique(array_merge($allAllowed, ['other', 'custom', 'أخرى']));
                    }
                    if (! empty($allAllowed)) {
                        if ($field->allowsCustomValue()) {
                            $fieldRules[] = function ($attribute, $value, $fail) use ($allAllowed, $field) {
                                if ($value === null || $value === '') {
                                    return;
                                }
                                if (in_array($value, $allAllowed, true) || in_array(strtolower((string) $value), ['other', 'custom', 'أخرى'], true)) {
                                    return;
                                }
                                if ($field->customInputType() === 'text') {
                                    return;
                                }
                                try {
                                    Carbon::parse((string) $value);
                                    return;
                                } catch (\Throwable) {
                                    $fail(app()->getLocale() === 'ar' ? 'يرجى اختيار قيمة صحيحة لـ :attribute.' : 'Please select a valid option for :attribute.');
                                }
                            };
                        } else {
                            $fieldRules[] = Rule::in($allAllowed);
                        }
                    }
                    break;
                case 'multiselect':
                    $fieldRules[] = 'array';
                    $opts = $field->normalizedOptions();
                    $allowedVals = array_column($opts, 'value');
                    $allowedLabelsAr = array_column($opts, 'label_ar');
                    $allowedLabelsEn = array_column($opts, 'label_en');
                    $allAllowed = array_unique(array_filter(array_merge($allowedVals, $allowedLabelsAr, $allowedLabelsEn)));
                    if (! empty($allAllowed)) {
                        $rules[$fieldKey . '.*'] = ['string', Rule::in($allAllowed)];
                    }
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;

                default:
                    $fieldRules[] = 'string';
                    break;
            }

            // Additional custom validation rules if configured
            if (! empty($field->validation_rules) && is_array($field->validation_rules)) {
                $customRules = array_filter($field->validation_rules, static function ($rule, $key): bool {
                    if (is_string($key) && in_array(strtolower($key), ['allow_custom', 'allow_other', 'custom_type', 'custom_input_type', 'options'], true)) {
                        return false;
                    }
                    if (is_numeric($rule) || is_bool($rule)) {
                        return false;
                    }
                    return is_string($rule) || is_object($rule) || is_callable($rule);
                }, ARRAY_FILTER_USE_BOTH);
                if (! empty($customRules)) {
                    $fieldRules = array_merge($fieldRules, array_values($customRules));
                }
            }
            $rules[$fieldKey] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Build custom attribute labels for validation error messages.
     */
    public static function buildValidationAttributes(
        PipelineStage|int $stage,
        string $prefix = 'stage_fields.',
        ?string $locale = null
    ): array {
        $fields = self::getFieldsForStage($stage, true);
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$prefix . $field->key] = $field->localizedLabel($locale);
        }

        return $attributes;
    }

    /**
     * Validate and extract stage fields from incoming raw request input.
     * Rejects unknown keys, inactive fields, and fields from other stages.
     *
     * @throws ValidationException
     */
    public static function validateAndExtract(
        PipelineStage|int $stage,
        array $rawInput,
        ?User $actor = null
    ): array {
        $stageId = $stage instanceof PipelineStage ? (int) $stage->id : (int) $stage;
        $stageModel = $stage instanceof PipelineStage ? $stage : PipelineStage::query()->findOrFail($stageId);

        $activeFields = self::getFieldsForStage($stageModel, true);
        $allowedKeys = $activeFields->pluck('key')->all();

        // Extract submitted stage fields array
        $submitted = isset($rawInput['stage_fields']) && is_array($rawInput['stage_fields'])
            ? $rawInput['stage_fields']
            : $rawInput;

        unset($submitted['stage_fields'], $submitted['stage_fields_custom']);
        // Security: Check for unknown keys submitted under stage_fields
        $invalidKeys = array_diff(array_keys($submitted), $allowedKeys);
        if (! empty($invalidKeys)) {
            $systemParams = [
                '_token', '_method', 'lead_status_id', 'pipeline_stage_id', 'target_status_id',
                'communication_type', 'outcome', 'employee_name', 'next_follow_up_at', 'followed_up_at',
                'donation_type', 'donation_type_id', 'donation_value', 'donation_cycle', 'donation_purpose', 'donation_purpose_id',
                'response_details', 'notes', 'contact_date', 'assigned_user_id', 'responding_user_id', 'campaign_id', 'branch_id',
                'name', 'first_name', 'last_name', 'phone', 'email', 'address', 'source', 'governorate', 'activity', 'company_name',
                'users_count', 'branches_count', 'job_title', 'disinterest_reason', 'solution_type', 'lines_count', 'extensions',
                'departments', 'quotation_file_path', 'quotation_sent', 'custom_fields', 'additional_phones', 'related_people',
                'kanban_popup', 'record_followup', 'lead_attributes', 'field_changes', 'history_note', 'force_history', 'campaign',
                'stage_fields', 'stage_fields_custom',
            ];
            $actualUnknown = array_diff($invalidKeys, $systemParams);
            if (! empty($actualUnknown)) {
                throw ValidationException::withMessages([
                    'stage_fields' => [
                        __('crm.unknown_stage_fields_rejected', ['keys' => implode(', ', $actualUnknown)])
                        ?: 'Unknown stage fields: ' . implode(', ', $actualUnknown),
                    ],
                ]);
            }
        }

        // Build validation rules, custom friendly messages and attributes
        $rules = self::buildValidationRules($stageModel, $submitted, '');
        $attributes = self::buildValidationAttributes($stageModel, '', app()->getLocale());

        $messages = [
            'required' => app()->getLocale() === 'ar' ? 'يرجى إدخال :attribute.' : 'Please enter :attribute.',
            'accepted' => app()->getLocale() === 'ar' ? 'يرجى تأكيد :attribute.' : 'Please confirm :attribute.',
            'in' => app()->getLocale() === 'ar' ? 'يرجى اختيار قيمة صحيحة لـ :attribute.' : 'Please select a valid option for :attribute.',
            'date' => app()->getLocale() === 'ar' ? 'يرجى إدخال تاريخ صحيح لـ :attribute.' : 'Please enter a valid date for :attribute.',
            'numeric' => app()->getLocale() === 'ar' ? 'يرجى إدخال رقم صحيح لـ :attribute.' : 'Please enter a valid number for :attribute.',
        ];

        $validator = Validator::make($submitted, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $normalized = [];

        foreach ($activeFields as $field) {
            if (! array_key_exists($field->key, $validated)) {
                // If checkbox was not submitted, default to false
                if ($field->type === 'checkbox') {
                    $normalized[$field->key] = false;
                }
                continue;
            }

            $val = $validated[$field->key];
            // Handle custom value for select fields (Other / أخرى)
            if ($field->type === 'select' && $field->allowsCustomValue()) {
                $isOtherSelected = in_array(strtolower(trim((string) $val)), ['other', 'custom', 'أخرى'], true);

                $customSubmitted = $rawInput['stage_fields_custom'][$field->key]
                    ?? $rawInput[$field->key . '_custom']
                    ?? $rawInput['stage_fields'][$field->key . '_custom']
                    ?? null;

                if ($isOtherSelected) {
                    $customType = $field->customInputType();
                    if ($customType === 'date' || $customType === 'datetime') {
                        if ($customSubmitted === null || trim((string) $customSubmitted) === '') {
                            $attrName = $attributes[$field->key] ?? $field->localizedLabel();
                            throw ValidationException::withMessages([
                                $field->key => [
                                    app()->getLocale() === 'ar'
                                        ? 'يجب تحديد الموعد المخصص.'
                                        : 'A custom date is required.',
                                ],
                            ]);
                        }

                        if ($customType === 'date') {
                            try {
                                $parsed = Carbon::parse((string) $customSubmitted);
                                $normalized[$field->key] = $parsed->format('Y-m-d');
                            } catch (\Throwable) {
                                $attrName = $attributes[$field->key] ?? $field->localizedLabel();
                                throw ValidationException::withMessages([
                                    $field->key => [
                                        app()->getLocale() === 'ar'
                                            ? 'يرجى إدخال تاريخ صحيح لـ ' . $attrName . '.'
                                            : 'Please enter a valid date for ' . $attrName . '.',
                                    ],
                                ]);
                            }
                        } else {
                            try {
                                $parsed = Carbon::parse((string) $customSubmitted);
                                $normalized[$field->key] = $parsed->toDateTimeString();
                            } catch (\Throwable) {
                                $attrName = $attributes[$field->key] ?? $field->localizedLabel();
                                throw ValidationException::withMessages([
                                    $field->key => [
                                        app()->getLocale() === 'ar'
                                            ? 'يرجى إدخال تاريخ ووقت صحيح لـ ' . $attrName . '.'
                                            : 'Please enter a valid date and time for ' . $attrName . '.',
                                    ],
                                ]);
                            }
                        }
                    } else {
                        $normalized[$field->key] = ($customSubmitted !== null && trim((string) $customSubmitted) !== '')
                            ? trim((string) $customSubmitted)
                            : (string) $val;
                    }
                    continue;
                }
            }

            // Normalize values based on field type
            if ($field->type === 'checkbox') {
                $normalized[$field->key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } elseif ($field->type === 'number') {
                $normalized[$field->key] = ($val === null || $val === '')
                    ? null
                    : (str_contains((string) $val, '.') ? (float) $val : (int) $val);
            } elseif ($field->type === 'multiselect') {
                $normalized[$field->key] = is_array($val) ? array_values($val) : [];
            } elseif ($field->type === 'date' || $field->type === 'datetime') {
                if ($val !== null && $val !== '') {
                    try {
                        $normalized[$field->key] = Carbon::parse($val)->toDateTimeString();
                    } catch (\Throwable) {
                        $normalized[$field->key] = (string) $val;
                    }
                } else {
                    $normalized[$field->key] = null;
                }
            } else {
                $normalized[$field->key] = $val !== null ? trim((string) $val) : null;
            }
        }

        return $normalized;
    }

    /**
     * Persist stage field values for a Lead atomically.
     *
     * @return Collection<int, LeadStageFieldValue>
     */
    public static function persistValues(
        Lead $lead,
        PipelineStage|int $stage,
        array $values,
        ?LeadStatusHistory $history = null,
        ?User $actor = null
    ): Collection {
        $stageId = $stage instanceof PipelineStage ? (int) $stage->id : (int) $stage;
        $fields = self::getFieldsForStage($stageId, false)->keyBy('key');

        $createdRecords = new Collection();

        foreach ($values as $key => $val) {
            /** @var PipelineStageField|null $field */
            $field = $fields->get($key);
            $fieldType = $field ? $field->type : 'text';

            $rawVal = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : ($val === null ? null : (string) $val);

            $record = LeadStageFieldValue::query()->create([
                'lead_id' => $lead->id,
                'pipeline_stage_id' => $stageId,
                'pipeline_stage_field_id' => $field?->id,
                'lead_status_history_id' => $history?->id,
                'field_key' => (string) $key,
                'field_type' => $fieldType,
                'value' => $rawVal,
                'created_by_user_id' => $actor?->id,
            ]);

            $createdRecords->push($record);
        }

        return $createdRecords;
    }
}

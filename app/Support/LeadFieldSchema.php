<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LeadFormField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeadFieldSchema
{
    public const CACHE_KEY = 'lead_form_fields.active_schema';

    /**
     * Validation rules for the system-managed inputs. These mirror the
     * rules that were previously hardcoded in LeadController so hiding
     * a field never breaks persistence of historical flows.
     */
    private const SYSTEM_RULES = [
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
        'campaign_id' => ['nullable', 'integer'],
        'contact_date' => ['nullable', 'date'],
        'next_follow_up_at' => ['nullable', 'string'],
        'response_details' => ['nullable', 'string', 'max:10000'],
        'source' => ['nullable', 'string', 'max:100'],
        'email' => ['nullable', 'email', 'max:255'],
        'company_name' => ['nullable', 'string', 'max:150'],
        'activity' => ['nullable', 'string', 'max:150'],
        'governorate' => ['nullable', 'string', 'max:100'],
        'address' => ['nullable', 'string', 'max:255'],
    ];

    private const TEXT_LIKE_TYPES = [
        LeadFormField::TYPE_TEXT,
        LeadFormField::TYPE_EMAIL,
        LeadFormField::TYPE_TEL,
        LeadFormField::TYPE_URL,
    ];

    /**
     * @return Collection<int, LeadFormField>
     */
    public static function active(?string $entity = null)
    {
        $entityKey = self::normalizeEntity($entity);

        // Plain arrays are cached (the database cache store disallows object
        // deserialization) and rehydrated into models on every read.
        // getAttributes() keeps raw values so hydrate() can cast once.
        $fields = Cache::rememberForever(self::cacheKey($entityKey), static fn () => LeadFormField::query()
            ->forEntity($entityKey)
            ->active()
            ->get()
            ->map(static fn (LeadFormField $field) => $field->getAttributes())
            ->all());

        return LeadFormField::hydrate(is_array($fields) ? $fields : []);
    }

    public static function cacheKey(string $entity): string
    {
        return self::CACHE_KEY.'.'.self::normalizeEntity($entity);
    }

    private static function normalizeEntity(?string $entity): string
    {
        $normalized = mb_strtolower(trim((string) $entity));

        return $normalized !== '' ? $normalized : 'leads';
    }

    public static function find(string $key): ?LeadFormField
    {
        return self::active()->firstWhere('key', $key);
    }

    public static function shows(string $key, string $surface = 'show_in_create'): bool
    {
        $field = self::find($key);

        return $field !== null && (bool) $field->{$surface};
    }

    /**
     * Active custom (non-system) fields.
     *
     * @return Collection<int, LeadFormField>
     */
    public static function customFields(?string $section = null, ?string $entity = null)
    {
        return self::active($entity)
            ->where('is_system', false)
            ->when($section !== null, static fn ($fields) => $fields->where('section', $section))
            ->values();
    }

    /**
     * Legacy system fields rendered through the generic renderer
     * (they map 1:1 onto real lead columns).
     */
    public static function legacyFields(string $surface): array
    {
        return self::active()
            ->where('is_system', true)
            ->whereIn('type', [
                LeadFormField::TYPE_TEXT,
                LeadFormField::TYPE_EMAIL,
                LeadFormField::TYPE_TEL,
                LeadFormField::TYPE_NUMBER,
            ])
            ->filter(static fn (LeadFormField $field) => $field->system_column !== null
                && ! in_array($field->key, ['name', 'phone', 'source'], true)
                && (bool) $field->{$surface})
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, LeadFormField>
     */
    public static function filterable()
    {
        return self::active()
            ->where('show_in_filter', true)
            ->reject(static fn (LeadFormField $field) => in_array($field->key, [
                'q', 'stage', 'status', 'donation_type', 'donation_type_id',
                'donation_cycle', 'employee', 'source', 'follow_up', 'sort',
                'donation_value',
            ], true))
            ->filter(static fn (LeadFormField $field) => self::isFilterableType($field))
            ->values();
    }

    /**
     * @return Collection<int, LeadFormField>
     */
    public static function tableColumns()
    {
        return self::active()->where('show_in_table', true)->values();
    }

    /**
     * @return Collection<int, LeadFormField>
     */
    public static function exportColumns()
    {
        return self::active()->where('show_in_export', true)->values();
    }

    public static function customKeys(): array
    {
        return self::customFields()->pluck('key')->all();
    }

    /**
     * Full validation rules for leads: system rules + generated rules for
     * the active custom fields.
     */
    public static function validationRules(): array
    {
        return [
            ...self::SYSTEM_RULES,
            ...self::rulesForCustomFields('leads'),
        ];
    }

    /**
     * Validation rules generated only from the fields of one entity —
     * used by the generic Module Builder CRUD.
     *
     * @return array<string, list<string>>
     */
    public static function rulesForCustomFields(?string $entity = null): array
    {
        $rules = [];

        foreach (self::customFields(null, $entity) as $field) {
            foreach (self::rulesForField($field) as $name => $fieldRules) {
                $rules[$name] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Friendly attribute names so validation errors show labels.
     */
    public static function validationAttributes(?string $entity = null): array
    {
        $attributes = [];

        foreach (self::customFields(null, $entity) as $field) {
            $attributes['custom_fields.'.$field->key] = $field->label();
        }

        return $attributes;
    }

    private static function rulesForField(LeadFormField $field): array
    {
        $base = "custom_fields.{$field->key}";
        $required = $field->is_required ? ['required'] : ['nullable'];
        $optionValues = $field->optionValues();

        switch ($field->type) {
            case LeadFormField::TYPE_TEXT:
                return [$base => [...$required, 'string', 'max:500']];

            case LeadFormField::TYPE_EMAIL:
                return [$base => [...$required, 'email:max:255']];

            case LeadFormField::TYPE_TEL:
                return [$base => [...$required, 'string', 'max:50']];

            case LeadFormField::TYPE_URL:
                return [$base => [...$required, 'url', 'max:500']];

            case LeadFormField::TYPE_TEXTAREA:
                return [$base => [...$required, 'string', 'max:5000']];

            case LeadFormField::TYPE_NUMBER:
                return [$base => [...$required, 'numeric', 'min:-999999999999', 'max:999999999999']];

            case LeadFormField::TYPE_DATE:
            case LeadFormField::TYPE_DATETIME:
                return [$base => [...$required, 'date']];

            case LeadFormField::TYPE_CHECKBOX:
                return [$base => [...$required, 'boolean']];

            case LeadFormField::TYPE_SELECT:
                return [
                    $base => [...$required, 'string', 'in:'.implode(',', self::escapeInValues($optionValues))],
                ];

            case LeadFormField::TYPE_MULTISELECT:
                return [
                    $base => [...$required, 'array', 'max:100'],
                    "{$base}.*" => ['string', 'in:'.implode(',', self::escapeInValues($optionValues))],
                ];

            default:
                return [];
        }
    }

    private static function escapeInValues(array $values): array
    {
        return array_map(
            static fn (string $value): string => addcslashes($value, ',\\'),
            array_map(static fn ($value): string => (string) $value, $values),
        );
    }

    private static function isFilterableType(LeadFormField $field): bool
    {
        if ($field->type === 'repeater') {
            return false;
        }

        if ($field->is_system && $field->system_column === null) {
            return false;
        }

        return in_array($field->type, [
            ...self::TEXT_LIKE_TYPES,
            LeadFormField::TYPE_NUMBER,
            LeadFormField::TYPE_DATE,
            LeadFormField::TYPE_DATETIME,
            LeadFormField::TYPE_SELECT,
            LeadFormField::TYPE_MULTISELECT,
            LeadFormField::TYPE_CHECKBOX,
            'donation_types',
            'donation_cycles',
            'donation_purposes',
        ], true);
    }

    /**
     * Normalizes raw request input for one custom field into its stored
     * representation. Returns null when the value is empty and should be
     * dropped from the JSON payload.
     */
    public static function normalizeValue(LeadFormField $field, mixed $raw): mixed
    {
        switch ($field->type) {
            case LeadFormField::TYPE_CHECKBOX:
                return (bool) $raw;

            case LeadFormField::TYPE_MULTISELECT:
                $values = collect((array) $raw)
                    ->map(static fn ($value) => trim((string) $value))
                    ->filter(static fn (string $value) => $value !== '')
                    ->unique()
                    ->values();

                return $values->isEmpty() ? null : $values->all();

            case LeadFormField::TYPE_NUMBER:
                if (! is_numeric($raw)) {
                    return null;
                }

                $number = (float) $raw;

                return fmod($number, 1.0) === 0.0 ? (int) $number : $number;

            default:
                $text = is_scalar($raw) ? trim((string) $raw) : '';

                return $text === '' ? null : $text;
        }
    }

    /**
     * Builds the custom_fields payload for a create from raw input.
     */
    public static function extractForStore(mixed $input, ?string $entity = null): ?array
    {
        $payload = [];

        foreach (self::customFields(null, $entity) as $field) {
            $raw = is_array($input) ? ($input[$field->key] ?? null) : null;
            $value = self::normalizeValue($field, $raw);

            if ($value !== null && $value !== []) {
                $payload[$field->key] = $value;
            }
        }

        return $payload === [] ? null : $payload;
    }

    /**
     * Merges submitted values over an existing custom_fields payload on
     * update. Keys of deactivated fields are preserved untouched.
     */
    public static function mergeForUpdate(mixed $current, mixed $input, ?string $entity = null): ?array
    {
        $payload = is_array($current) ? $current : [];

        foreach (self::customFields(null, $entity) as $field) {
            $raw = is_array($input) ? ($input[$field->key] ?? null) : null;
            $value = self::normalizeValue($field, $raw);

            if ($value === null || $value === []) {
                unset($payload[$field->key]);
            } else {
                $payload[$field->key] = $value;
            }
        }

        return $payload === [] ? null : $payload;
    }

    /**
     * Human readable value for tables, exports and detail pages.
     */
    public static function formatValue(LeadFormField $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }

        switch ($field->type) {
            case LeadFormField::TYPE_CHECKBOX:
                return $value ? __('crm.yes') : __('crm.no');

            case LeadFormField::TYPE_MULTISELECT:
                return collect((array) $value)
                    ->map(static fn ($item) => self::formatSingle($field, $item))
                    ->implode('، ');

            case LeadFormField::TYPE_DATE:
                $timestamp = strtotime((string) $value);

                return $timestamp !== false ? date('Y-m-d', $timestamp) : (string) $value;

            case LeadFormField::TYPE_DATETIME:
                $timestamp = strtotime((string) $value);

                return $timestamp !== false ? date('Y-m-d H:i', $timestamp) : (string) $value;

            case LeadFormField::TYPE_NUMBER:
                return is_numeric($value) ? number_format((float) $value, 2) : (string) $value;

            case 'donation_cycles':
                $cycleKey = (string) $value;

                return __('crm.donation_cycle_'.$cycleKey);

            default:
                return self::formatSingle($field, $value);
        }
    }

    private static function formatSingle(LeadFormField $field, mixed $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '—';
        }

        if ($field->isChoice()) {
            foreach ($field->options ?? [] as $option) {
                if (($option['value'] ?? null) === $value) {
                    return app()->getLocale() === 'en' && trim((string) ($option['label_en'] ?? '')) !== ''
                        ? trim((string) $option['label_en'])
                        : trim((string) ($option['label_ar'] ?? $value));
                }
            }
        }

        return $value;
    }

    /**
     * Safe slug for embedding into JSON_EXTRACT SQL.
     */
    public static function isSafeKey(string $key): bool
    {
        return preg_match('/^[a-z][a-z0-9_]{0,49}$/', $key) === 1;
    }

    public static function flush(?string $entity = null): void
    {
        if ($entity !== null) {
            Cache::forget(self::cacheKey($entity));

            return;
        }

        Cache::forget(self::cacheKey('leads'));

        Cache::forget(self::CACHE_KEY);
    }
}

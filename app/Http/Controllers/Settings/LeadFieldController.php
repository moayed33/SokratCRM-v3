<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadFormField;
use App\Support\CrmDatabaseGuard;
use App\Support\LeadFieldSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeadFieldController extends Controller
{
    /**
     * Keys that can never be claimed by a custom field because they
     * collide with reserved list filters or existing system fields.
     */
    private const RESERVED_KEYS = [
        'q', 'stage', 'status', 'donation_type', 'donation_type_id',
        'donation_cycle', 'employee', 'source', 'follow_up', 'sort',
        'page', 'custom_fields', 'id', 'branch_id', 'lead_status_id',
        'pipeline_stage_id', 'campaign_id', 'assigned_user_id',
        'responding_user_id', 'created_by_user_id', 'next_follow_up_at',
        'contact_date', 'response_details', 'notes', 'created_at',
        'updated_at', 'deleted_at',
    ];

    public function index(): View
    {
        CrmDatabaseGuard::ensureConnected();

        $fields = LeadFormField::query()->ordered()->get();

        $stats = [
            'total' => $fields->count(),
            'active' => $fields->where('is_active', true)->count(),
            'custom' => $fields->where('is_system', false)->count(),
            'filterable' => $fields->where('show_in_filter', true)->count(),
        ];

        return view('settings.lead-fields.index', [
            'fields' => $fields,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        CrmDatabaseGuard::ensureConnected();

        return view('settings.lead-fields.create', [
            'field' => new LeadFormField([
                'type' => LeadFormField::TYPE_TEXT,
                'section' => LeadFormField::SECTION_OTHER,
                'is_active' => true,
            ]),
            'maxPosition' => (int) (LeadFormField::query()->max('position') ?? 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $validated = $this->validateCustomField($request, null);

        $field = LeadFormField::query()->create([
            ...$validated['attributes'],
            'is_system' => false,
            'is_locked' => false,
            'system_column' => null,
            'options' => $validated['options'],
            'position' => (int) (
                $validated['attributes']['position']
                ?? ((int) (LeadFormField::query()->max('position') ?? 0)) + 10
            ),
        ]);

        LeadFieldSchema::flush();

        return redirect()
            ->route('v2.settings.fields.index')
            ->with('success', __('crm.lf_created_success'));
    }

    public function edit(LeadFormField $field): View
    {
        CrmDatabaseGuard::ensureConnected();

        return view('settings.lead-fields.edit', [
            'field' => $field,
            'optionsInput' => $this->optionsToText($field),
            'maxPosition' => (int) (LeadFormField::query()->max('position') ?? 0),
        ]);
    }

    public function update(Request $request, LeadFormField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        if ($field->is_system) {
            $validated = $this->validateSystemField($request, $field);

            $field->update([
                ...$validated['attributes'],
                'options' => $validated['options'],
            ]);
        } else {
            $validated = $this->validateCustomField($request, $field);

            $field->update([
                ...$validated['attributes'],
                'options' => $validated['options'],
            ]);
        }

        LeadFieldSchema::flush();

        return redirect()
            ->route('v2.settings.fields.index')
            ->with('success', __('crm.lf_updated_success'));
    }

    public function toggleActive(LeadFormField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        if ($field->is_locked) {
            return redirect()
                ->back()
                ->withErrors(['toggle' => __('crm.lf_cannot_deactivate_locked')]);
        }

        $field->update(['is_active' => ! $field->is_active]);

        LeadFieldSchema::flush();

        return redirect()
            ->back()
            ->with('success', __('crm.lf_toggled_success'));
    }

    public function move(Request $request, LeadFormField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $siblings = LeadFormField::query()->ordered()->get(['id', 'position'])->values();
        $currentIndex = $siblings->search(static fn (LeadFormField $item) => $item->id === $field->id);

        if ($currentIndex !== false) {
            $neighborIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if ($neighborIndex >= 0 && $neighborIndex < $siblings->count()) {
                $current = $siblings[$currentIndex];
                $neighbor = $siblings[$neighborIndex];

                DB::transaction(function () use ($current, $neighbor): void {
                    $originalPosition = $current->position;
                    $current->update(['position' => $neighbor->position]);
                    $neighbor->update(['position' => $originalPosition]);
                });
            }
        }

        LeadFieldSchema::flush();

        return redirect()->back();
    }

    public function destroy(LeadFormField $field): RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        if ($field->is_system) {
            return redirect()
                ->back()
                ->withErrors(['delete' => __('crm.lf_cannot_delete_system')]);
        }

        DB::transaction(function () use ($field): void {
            $field->delete();

            // Strip the orphaned key from every stored lead payload.
            if (LeadFieldSchema::isSafeKey($field->key)) {
                $path = '$.'.$field->key;

                Lead::query()
                    ->whereNotNull('custom_fields')
                    ->whereRaw('JSON_EXTRACT(custom_fields, ?) IS NOT NULL', [$path])
                    ->update(['custom_fields' => DB::raw("JSON_REMOVE(custom_fields, '".str_replace("'", '', $path)."')")]);
            }
        });

        LeadFieldSchema::flush();

        return redirect()
            ->route('v2.settings.fields.index')
            ->with('success', __('crm.lf_deleted_success'));
    }

    private function validateCustomField(Request $request, ?LeadFormField $field): array
    {
        $keyRules = [
            'required',
            'string',
            'regex:/^[a-z][a-z0-9_]{0,49}$/',
            Rule::unique('lead_form_fields', 'key')->ignore($field?->id),
            static function (string $attribute, mixed $value, \Closure $fail): void {
                if (in_array((string) $value, self::RESERVED_KEYS, true)) {
                    $fail(__('crm.lf_reserved_key'));
                }
            },
        ];

        $validated = $request->validate([
            'label_ar' => ['required', 'string', 'max:150'],
            'label_en' => ['nullable', 'string', 'max:150'],
            'key' => $keyRules,
            'type' => ['required', 'string', 'in:'.implode(',', LeadFormField::CUSTOM_TYPES)],
            'options_input' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'section' => ['required', 'string', 'in:'.implode(',', LeadFormField::SECTIONS)],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'help_text_ar' => ['nullable', 'string', 'max:255'],
            'help_text_en' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_create' => ['nullable', 'boolean'],
            'show_in_edit' => ['nullable', 'boolean'],
            'show_in_filter' => ['nullable', 'boolean'],
            'show_in_table' => ['nullable', 'boolean'],
            'show_in_export' => ['nullable', 'boolean'],
        ], [], [
            'key' => __('crm.lf_key'),
            'label_ar' => __('crm.lf_label_ar'),
            'options_input' => __('crm.lf_options'),
        ]);

        // Dropdown types must define at least one option (checked outside the
        // rule set because empty strings are converted to null upstream).
        $type = (string) $validated['type'];
        $options = $this->parseOptionsInput((string) $request->input('options_input', ''));

        if (in_array($type, [LeadFormField::TYPE_SELECT, LeadFormField::TYPE_MULTISELECT], true)) {
            if ($options === []) {
                throw ValidationException::withMessages([
                    'options_input' => __('crm.lf_options_required'),
                ]);
            }

            if (count($options) > 200) {
                throw ValidationException::withMessages([
                    'options_input' => __('crm.lf_options_too_many'),
                ]);
            }
        }

        return [
            'attributes' => [
                'key' => trim((string) $validated['key']),
                'label_ar' => trim((string) $validated['label_ar']),
                'label_en' => isset($validated['label_en']) && trim((string) $validated['label_en']) !== ''
                    ? trim((string) $validated['label_en'])
                    : null,
                'type' => (string) $validated['type'],
                'section' => (string) $validated['section'],
                'position' => isset($validated['position'])
                    ? (int) $validated['position']
                    : ($field->position ?? null),
                'help_text_ar' => isset($validated['help_text_ar']) && trim((string) $validated['help_text_ar']) !== ''
                    ? trim((string) $validated['help_text_ar'])
                    : null,
                'help_text_en' => isset($validated['help_text_en']) && trim((string) $validated['help_text_en']) !== ''
                    ? trim((string) $validated['help_text_en'])
                    : null,
                'is_required' => $request->boolean('is_required'),
                'is_active' => $request->boolean('is_active', true),
                'show_in_create' => $request->boolean('show_in_create', true),
                'show_in_edit' => $request->boolean('show_in_edit', true),
                'show_in_filter' => $request->boolean('show_in_filter'),
                'show_in_table' => $request->boolean('show_in_table'),
                'show_in_export' => $request->boolean('show_in_export'),
            ],
            'options' => in_array((string) $validated['type'], [LeadFormField::TYPE_SELECT, LeadFormField::TYPE_MULTISELECT], true)
                ? $options
                : null,
        ];
    }

    private function validateSystemField(Request $request, LeadFormField $field): array
    {
        $validated = $request->validate([
            'label_ar' => ['required', 'string', 'max:150'],
            'label_en' => ['nullable', 'string', 'max:150'],
            'section' => ['required', 'string', 'in:'.implode(',', LeadFormField::SECTIONS)],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'help_text_ar' => ['nullable', 'string', 'max:255'],
            'help_text_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_create' => ['nullable', 'boolean'],
            'show_in_edit' => ['nullable', 'boolean'],
            'show_in_filter' => ['nullable', 'boolean'],
        ], [], [
            'label_ar' => __('crm.lf_label_ar'),
        ]);

        $attributes = [
            'label_ar' => trim((string) $validated['label_ar']),
            'label_en' => isset($validated['label_en']) && trim((string) $validated['label_en']) !== ''
                ? trim((string) $validated['label_en'])
                : null,
            'section' => (string) $validated['section'],
            'position' => isset($validated['position']) ? (int) $validated['position'] : $field->position,
            'help_text_ar' => isset($validated['help_text_ar']) && trim((string) $validated['help_text_ar']) !== ''
                ? trim((string) $validated['help_text_ar'])
                : null,
            'help_text_en' => isset($validated['help_text_en']) && trim((string) $validated['help_text_en']) !== ''
                ? trim((string) $validated['help_text_en'])
                : null,
            'show_in_create' => $request->boolean('show_in_create', true),
            'show_in_edit' => $request->boolean('show_in_edit', true),
            'show_in_filter' => $request->boolean('show_in_filter'),
        ];

        if ($field->exists && ! $field->is_locked) {
            $attributes['is_active'] = $request->boolean('is_active', true);
        }

        return [
            'attributes' => $attributes,
            'options' => $field->options,
        ];
    }

    /**
     * Parses the options textarea. Each non-empty line becomes one option:
     * "value | Arabic label | English label" — the pipes are optional and
     * missing parts fall back to the value itself.
     *
     * @return list<array{value: string, label_ar: string, label_en: string|null}>
     */
    public static function parseOptionsInput(string $input): array
    {
        $options = [];

        foreach (preg_split('/\r\n|\r|\n/', $input) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map(static fn (string $part) => trim($part), explode('|', $line));

            $value = $parts[0] ?? '';
            if ($value === '') {
                continue;
            }

            $labelAr = ($parts[1] ?? '') !== '' ? $parts[1] : $value;

            $options[] = [
                'value' => mb_substr($value, 0, 100),
                'label_ar' => mb_substr($labelAr, 0, 150),
                'label_en' => (($parts[2] ?? '') !== '') ? mb_substr($parts[2], 0, 150) : null,
            ];
        }

        return $options;
    }

    /**
     * Renders stored options back into textarea format.
     */
    private function optionsToText(LeadFormField $field): string
    {
        return collect($field->options ?? [])
            ->map(static fn (array $option): string => implode(' | ', array_filter([
                (string) ($option['value'] ?? ''),
                (string) ($option['label_ar'] ?? ''),
                (string) ($option['label_en'] ?? ''),
            ], static fn (string $part) => $part !== '')))
            ->implode("\n");
    }
}

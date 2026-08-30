@php
    $isSystem = $field->is_system;
    $isChoice = in_array($field->type, [App\Models\LeadFormField::TYPE_SELECT, App\Models\LeadFormField::TYPE_MULTISELECT], true);
@endphp

<input type="hidden" name="is_system" value="{{ $isSystem ? '1' : '0' }}">
<input type="hidden" name="entity" value="{{ old('entity', $field->entity ?? 'leads') }}">

<div class="form-grid">
    <div class="field">
        <label for="labelAr">{{ __('crm.lf_label_ar') }} <span style="color:#dc2637">*</span></label>
        <input type="text" id="labelAr" name="label_ar" value="{{ old('label_ar', $field->label_ar) }}" required maxlength="150">
    </div>
    <div class="field">
        <label for="labelEn">{{ __('crm.lf_label_en') }}</label>
        <input type="text" id="labelEn" name="label_en" value="{{ old('label_en', $field->label_en) }}" maxlength="150" dir="ltr">
    </div>

    @unless ($isSystem)
        <div class="field">
            <label for="fieldKey">{{ __('crm.lf_key') }} <span style="color:#dc2637">*</span></label>
            <input type="text" id="fieldKey" name="key" value="{{ old('key', $field->key) }}" required maxlength="50" pattern="[a-z][a-z0-9_]*" dir="ltr" style="text-align:start">
            <div class="hint">{{ __('crm.lf_key_hint') }}</div>
        </div>
        <div class="field">
            <label for="fieldType">{{ __('crm.lf_type') }} <span style="color:#dc2637">*</span></label>
            <select id="fieldType" name="type" required>
                @foreach (\App\Models\LeadFormField::CUSTOM_TYPES as $typeOption)
                    <option value="{{ $typeOption }}" {{ (string) old('type', $field->type) === $typeOption ? 'selected' : '' }}>
                        {{ __('crm.lf_type_'.$typeOption) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endunless

    @unless ($isSystem)
        <div class="field full" id="optionsWrap" style="{{ $isChoice ? '' : 'display:none' }}">
            <label for="optionsInput">{{ __('crm.lf_options') }}</label>
            <textarea id="optionsInput" name="options_input" rows="5" dir="ltr" style="text-align:start" placeholder="{{ __('crm.lf_options_placeholder') }}">{{ old('options_input', $optionsInput ?? '') }}</textarea>
            <div class="hint">{{ __('crm.lf_options_hint') }}</div>
        </div>
    @endunless

    @unless ($isSystem)
        @php
            $conditionParents = collect($conditionCandidates ?? []);
            $currentConditionField = old('condition_field', $field->condition_field);
        @endphp
        <div class="field">
            <label for="conditionField">{{ __('crm.lf_condition_field') }}</label>
            <select id="conditionField" name="condition_field">
                <option value="">{{ __('crm.lf_condition_always') }}</option>
                @foreach ($conditionParents as $parent)
                    <option value="{{ $parent->key }}" data-type="{{ $parent->type }}" @selected($currentConditionField === $parent->key)>
                        {{ $parent->label() }} ({{ __('crm.lf_type_'.$parent->type) }})
                    </option>
                @endforeach
            </select>
            <div class="hint">{{ __('crm.lf_condition_hint') }}</div>
        </div>
        <div class="field">
            <label for="conditionValue">{{ __('crm.lf_condition_value') }}</label>
            <select id="conditionValue" name="condition_value" {{ empty($currentConditionField) ? 'disabled' : '' }}>
                <option value="">{{ __('crm.lf_select_placeholder') }}</option>
                @foreach ($conditionParents as $parent)
                    @if (in_array($parent->type, [\App\Models\LeadFormField::TYPE_SELECT, \App\Models\LeadFormField::TYPE_MULTISELECT], true))
                        @foreach ($parent->optionValues() as $optionValue)
                            <option value="{{ $optionValue }}" data-parent="{{ $parent->key }}" @selected($field->condition_value === $optionValue && $currentConditionField === $parent->key)>
                                {{ $optionValue }}
                            </option>
                        @endforeach
                    @else
                        <option value="1" data-parent="{{ $parent->key }}" @selected($field->condition_value === '1' && $currentConditionField === $parent->key)>{{ __('crm.yes') }}</option>
                        <option value="0" data-parent="{{ $parent->key }}" @selected($field->condition_value === '0' && $currentConditionField === $parent->key)>{{ __('crm.no') }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        @push('scripts')
        <script>
        (() => {
            const parentSelect = document.getElementById('conditionField');
            const valueSelect = document.getElementById('conditionValue');
            if (! parentSelect || ! valueSelect) return;

            const syncValues = () => {
                const parentKey = parentSelect.value;
                const previous = valueSelect.value;
                Array.from(valueSelect.options).forEach((option) => {
                    if (! option.dataset.parent) return;
                    option.style.display = option.dataset.parent === parentKey ? '' : 'none';
                });
                const stillVisible = previous
                    && Array.from(valueSelect.options).some((option) => option.value === previous && option.style.display !== 'none' && option.dataset.parent);
                valueSelect.value = stillVisible ? previous : '';
                valueSelect.disabled = parentKey === '';
            };
            parentSelect.addEventListener('change', () => {
                syncValues();
                // prefer previously stored value when it matches the chosen parent
                const stored = @json((string) ($field->condition_value ?? ''));
                if (stored) {
                    const match = Array.from(valueSelect.options).find(
                        (option) => option.dataset.parent === parentSelect.value && option.value === stored,
                    );
                    if (match) valueSelect.value = match.value;
                }
            });
            syncValues();
        })();
        </script>
        @endpush
    @endunless

    <div class="field">
        <label for="fieldSection">{{ __('crm.lf_section') }}</label>
        <select id="fieldSection" name="section">
            @foreach (\App\Models\LeadFormField::SECTIONS as $sectionOption)
                <option value="{{ $sectionOption }}" {{ (string) old('section', $field->section) === $sectionOption ? 'selected' : '' }}>
                    {{ __('crm.section_'.$sectionOption) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="fieldPosition">{{ __('crm.lf_position') }}</label>
        <input type="number" id="fieldPosition" name="position" value="{{ old('position', $field->position) }}" min="0" max="9999">
    </div>

    <div class="field">
        <label for="helpAr">{{ __('crm.lf_help_ar') }}</label>
        <input type="text" id="helpAr" name="help_text_ar" value="{{ old('help_text_ar', $field->help_text_ar) }}" maxlength="255">
    </div>
    <div class="field">
        <label for="helpEn">{{ __('crm.lf_help_en') }}</label>
        <input type="text" id="helpEn" name="help_text_en" value="{{ old('help_text_en', $field->help_text_en) }}" maxlength="255" dir="ltr">
    </div>
</div>

<div class="panel" style="box-shadow:none;margin-top:18px">
    <strong style="display:block;margin-bottom:12px"><i class="bi bi-toggles"></i> {{ __('crm.lf_visibility') }}</strong>
    <div class="checkbox-grid">
        <label class="check-card">
            <input type="checkbox" name="show_in_create" value="1" {{ old('show_in_create', $isSystem ? $field->show_in_create : true) ? 'checked' : '' }}>
            <span><strong>{{ __('crm.lf_show_in_create') }}</strong></span>
        </label>
        <label class="check-card">
            <input type="checkbox" name="show_in_edit" value="1" {{ old('show_in_edit', $isSystem ? $field->show_in_edit : true) ? 'checked' : '' }}>
            <span><strong>{{ __('crm.lf_show_in_edit') }}</strong></span>
        </label>
        <label class="check-card">
            <input type="checkbox" name="show_in_filter" value="1" {{ old('show_in_filter', $field->show_in_filter) ? 'checked' : '' }}>
            <span><strong>{{ __('crm.lf_show_in_filter') }}</strong><small>{{ __('crm.lf_show_in_filter_hint') }}</small></span>
        </label>
        @unless ($isSystem)
            <label class="check-card">
                <input type="checkbox" name="show_in_table" value="1" {{ old('show_in_table', $field->show_in_table) ? 'checked' : '' }}>
                <span><strong>{{ __('crm.lf_show_in_table') }}</strong></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="show_in_export" value="1" {{ old('show_in_export', $field->show_in_export) ? 'checked' : '' }}>
                <span><strong>{{ __('crm.lf_show_in_export') }}</strong></span>
            </label>
            <label class="check-card">
                <input type="checkbox" name="is_required" value="1" {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                <span><strong>{{ __('crm.lf_required') }}</strong></span>
            </label>
        @endunless
        @if (! $isSystem || ! $field->is_locked)
            <label class="check-card">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $field->is_active) ? 'checked' : '' }}>
                <span><strong>{{ __('crm.active') }}</strong></span>
            </label>
        @endif
    </div>
</div>

@unless ($isSystem)
    @push('scripts')
    <script>
    (() => {
        const typeSelect = document.getElementById('fieldType');
        const optionsWrap = document.getElementById('optionsWrap');
        const sync = () => {
            if (! typeSelect || ! optionsWrap) return;
            const choice = ['select', 'multiselect'].includes(typeSelect.value);
            optionsWrap.style.display = choice ? '' : 'none';
        };
        typeSelect?.addEventListener('change', sync);
        sync();
    })();
    </script>
    @endpush
@endunless

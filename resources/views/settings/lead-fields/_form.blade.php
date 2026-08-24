@php
    $isSystem = $field->is_system;
    $isChoice = in_array($field->type, [App\Models\LeadFormField::TYPE_SELECT, App\Models\LeadFormField::TYPE_MULTISELECT], true);
@endphp

<input type="hidden" name="is_system" value="{{ $isSystem ? '1' : '0' }}">

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

<div class="panel" style="box-shadow:none;margin-top:18px;background:#fafbfc">
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

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\LeadFormField> $fields */
    /** @var \App\Models\Lead|null $currentLead */
    $oldCustomFields = (array) old('custom_fields', []);
@endphp
@foreach ($fields as $field)
    @php
        $storedValue = $currentLead?->custom_fields[$field->key] ?? null;
        $value = $oldCustomFields[$field->key] ?? $storedValue;
        $inputId = 'cf_field_'.$field->key;
    @endphp
    <div class="field{{ $field->type === 'textarea' ? ' full' : '' }}">
        <label for="{{ $inputId }}">
            {{ $field->label() }}
            @if ($field->is_required) <span class="req">*</span> @endif
        </label>

        @switch ($field->type)
            @case (\App\Models\LeadFormField::TYPE_TEXTAREA)
                <textarea id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" rows="3" {{ $field->is_required ? 'required' : '' }}>{{ old('custom_fields.'.$field->key, is_scalar($storedValue) ? (string) $storedValue : '') }}</textarea>
                @break

            @case (\App\Models\LeadFormField::TYPE_NUMBER)
                <input type="number" step="any" id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" value="{{ old('custom_fields.'.$field->key, is_scalar($storedValue) ? (string) $storedValue : '') }}" {{ $field->is_required ? 'required' : '' }}>
                @break

            @case (\App\Models\LeadFormField::TYPE_DATE)
                @php $dateValue = old('custom_fields.'.$field->key, is_string($storedValue) && strtotime($storedValue) !== false ? date('Y-m-d', (int) strtotime($storedValue)) : ''); @endphp
                <input type="date" id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" value="{{ $dateValue }}" {{ $field->is_required ? 'required' : '' }}>
                @break

            @case (\App\Models\LeadFormField::TYPE_DATETIME)
                @php $datetimeValue = old('custom_fields.'.$field->key, is_string($storedValue) && strtotime($storedValue) !== false ? date('Y-m-d\TH:i', (int) strtotime($storedValue)) : ''); @endphp
                <input type="datetime-local" id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" value="{{ $datetimeValue }}" {{ $field->is_required ? 'required' : '' }}>
                @break

            @case (\App\Models\LeadFormField::TYPE_SELECT)
                <select id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" {{ $field->is_required ? 'required' : '' }}>
                    <option value="">{{ __('crm.lf_select_placeholder') }}</option>
                    @foreach ($field->options ?? [] as $option)
                        @php $optionValue = (string) ($option['value'] ?? ''); @endphp
                        <option value="{{ $optionValue }}" @selected((string) $value === $optionValue)>
                            {{ app()->getLocale() === 'en' && trim((string) ($option['label_en'] ?? '')) !== '' ? trim((string) $option['label_en']) : trim((string) ($option['label_ar'] ?? $optionValue)) }}
                        </option>
                    @endforeach
                </select>
                @break

            @case (\App\Models\LeadFormField::TYPE_MULTISELECT)
                @php $selectedValues = collect((array) $value)->map(static fn ($v) => (string) $v)->all(); @endphp
                <select id="{{ $inputId }}" name="custom_fields[{{ $field->key }}][]" multiple size="4" {{ $field->is_required ? 'required' : '' }}>
                    @foreach ($field->options ?? [] as $option)
                        @php $optionValue = (string) ($option['value'] ?? ''); @endphp
                        <option value="{{ $optionValue }}" @selected(in_array($optionValue, $selectedValues, true))>
                            {{ app()->getLocale() === 'en' && trim((string) ($option['label_en'] ?? '')) !== '' ? trim((string) $option['label_en']) : trim((string) ($option['label_ar'] ?? $optionValue)) }}
                        </option>
                    @endforeach
                </select>
                @break

            @case (\App\Models\LeadFormField::TYPE_CHECKBOX)
                <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin:0">
                    <input type="hidden" name="custom_fields[{{ $field->key }}]" value="0">
                    <input type="checkbox" id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" value="1" style="width:auto" @checked(! empty($value))>
                    <span>{{ __('crm.lf_checkbox_toggle') }}</span>
                </label>
                @break

            @default
                @php $inputType = match ($field->type) {
                    \App\Models\LeadFormField::TYPE_EMAIL => 'email',
                    \App\Models\LeadFormField::TYPE_TEL => 'tel',
                    \App\Models\LeadFormField::TYPE_URL => 'url',
                    default => 'text',
                }; @endphp
                <input type="{{ $inputType }}" id="{{ $inputId }}" name="custom_fields[{{ $field->key }}]" value="{{ old('custom_fields.'.$field->key, is_scalar($storedValue) ? (string) $storedValue : '') }}" {{ $field->is_required ? 'required' : '' }} @if($field->type === 'tel') dir="ltr" @endif>
        @endswitch

        @if ($field->helpText())
            <div style="color:var(--muted);font-size:12px;margin-top:5px">{{ $field->helpText() }}</div>
        @endif
        @error('custom_fields.'.$field->key)
            <div style="color:#dc2637;font-size:12px;margin-top:5px;font-weight:700">{{ $message }}</div>
        @enderror
    </div>
@endforeach

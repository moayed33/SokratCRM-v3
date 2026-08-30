@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PipelineStageField> $fields */
    /** @var array<string,mixed> $recordValues */
    /** @var string $prefix */
    /** @var string $scope */
    $recordValues = is_array($recordValues ?? null) ? $recordValues : [];
    $prefix = $prefix ?? 'stage_fields';
    $scope = ($scope ?? 'stage_scope') . '_' . uniqid();
@endphp

@if ($fields->isNotEmpty())
    <style>
    .stage-fields-container .control{
        width:100%;
        border:1px solid var(--line, #dbe0e8);
        border-radius:9px;
        padding:10px 12px;
        background:var(--card, #fff);
        color:var(--dark, #303b4f);
        font:inherit;
        font-size:13px;
        outline:none;
        transition:border-color .15s, box-shadow .15s;
    }
    .stage-fields-container .control:focus{
        border-color:var(--red, #dc2637);
        box-shadow:0 0 0 3px rgba(220, 38, 55, 0.1);
    }
    .stage-fields-container label{
        display:block;
        font-weight:700;
        margin-bottom:6px;
        font-size:13px;
        color:var(--dark, inherit);
    }
    .stage-fields-container .stage-field-check-card{
        display:flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        padding:10px 12px;
        border:1px solid var(--line, #dbe1e9);
        border-radius:10px;
        background:var(--card, #fff);
        color:var(--dark, inherit);
        margin:0;
        transition:border-color .15s, background .15s;
    }
    .stage-fields-container .hint{
        display:block;
        color:var(--muted, #94a3b8);
        font-size:11px;
        margin-top:4px;
    }
    .stage-fields-container .stage-field-custom-wrap{
        transition:opacity 180ms ease, transform 180ms ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .stage-fields-container .stage-field-custom-wrap{
            transition:none !important;
            transform:none !important;
        }
    }
    html.dark-mode .stage-fields-container .control{
        background:var(--bg-input, rgba(39, 39, 42, 0.65)) !important;
        border-color:rgba(255, 255, 255, 0.14) !important;
        color:#f4f4f5 !important;
    }
    html.dark-mode .stage-fields-container .control option{
        background:#18181b !important;
        color:#f4f4f5 !important;
    }
    html.dark-mode .stage-fields-container label{
        color:#e4e4e7 !important;
    }
    html.dark-mode .stage-fields-container .stage-field-check-card{
        background:var(--bg-input, rgba(39, 39, 42, 0.65)) !important;
        border-color:rgba(255, 255, 255, 0.14) !important;
        color:#f4f4f5 !important;
    }
    html.dark-mode .stage-fields-container .hint{
        color:#a1a1aa !important;
    }
    </style>
    <div class="stage-fields-container" id="stageFieldsWrap_{{ $scope }}">
        <div class="form-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px;">
            @foreach ($fields as $field)
                @php
                    $val = old($prefix . '.' . $field->key, $recordValues[$field->key] ?? null);
                    $inputId = 'sf_' . $field->key . '_' . $scope;
                    $hasCondition = !empty($field->conditions) && !empty($field->conditions['field']);
                    $condField = $hasCondition ? (string)$field->conditions['field'] : '';
                    $condOp = $hasCondition ? (string)($field->conditions['operator'] ?? 'equals') : '';
                    $condVal = $hasCondition ? (string)($field->conditions['value'] ?? '') : '';
                @endphp

                <div class="field stage-field-item {{ $field->type === 'textarea' ? 'full' : '' }}"
                     data-sf-key="{{ $field->key }}"
                     data-sf-type="{{ $field->type }}"
                     data-sf-required="{{ $field->is_required ? '1' : '0' }}"
                     data-has-condition="{{ $hasCondition ? '1' : '0' }}"
                     data-condition-field="{{ $condField }}"
                     data-condition-operator="{{ $condOp }}"
                     data-condition-value="{{ $condVal }}"
                     id="sf_item_{{ $field->key }}_{{ $scope }}"
                     style="{{ $field->type === 'textarea' ? 'grid-column: 1 / -1;' : '' }}">

                    <label for="{{ $inputId }}" style="display:block; font-weight:700; margin-bottom:6px; font-size:13px;">
                        {{ $field->localizedLabel() }}
                        @if ($field->is_required)
                            <span class="required" style="color:var(--red); font-weight:bold;">*</span>
                        @endif
                    </label>

                    @switch ($field->type)
                        @case ('textarea')
                            <textarea id="{{ $inputId }}"
                                      name="{{ $prefix }}[{{ $field->key }}]"
                                      rows="3"
                                      placeholder="{{ $field->localizedPlaceholder() }}"
                                      class="control"
                                      style="min-height:75px; width:100%;"
                                      {{ $field->is_required ? 'required' : '' }}>{{ is_scalar($val) ? (string) $val : '' }}</textarea>
                            @break

                        @case ('number')
                            <input type="number"
                                   step="any"
                                   id="{{ $inputId }}"
                                   name="{{ $prefix }}[{{ $field->key }}]"
                                   value="{{ is_scalar($val) ? (string) $val : '' }}"
                                   placeholder="{{ $field->localizedPlaceholder() }}"
                                   class="control"
                                   style="width:100%;"
                                   {{ $field->is_required ? 'required' : '' }}>
                            @break

                        @case ('date')
                            @php
                                $dateStr = '';
                                if ($val) {
                                    try { $dateStr = \Carbon\Carbon::parse((string)$val)->format('Y-m-d'); } catch (\Throwable) { $dateStr = (string)$val; }
                                }
                            @endphp
                            <input type="date"
                                   id="{{ $inputId }}"
                                   name="{{ $prefix }}[{{ $field->key }}]"
                                   value="{{ $dateStr }}"
                                   class="control"
                                   style="width:100%;"
                                   {{ $field->is_required ? 'required' : '' }}>
                            @break

                        @case ('datetime')
                            @php
                                $dateTimeStr = '';
                                if ($val) {
                                    try { $dateTimeStr = \Carbon\Carbon::parse((string)$val)->format('Y-m-d\TH:i'); } catch (\Throwable) { $dateTimeStr = (string)$val; }
                                }
                            @endphp
                            <input type="datetime-local"
                                   id="{{ $inputId }}"
                                   name="{{ $prefix }}[{{ $field->key }}]"
                                   value="{{ $dateTimeStr }}"
                                   class="control"
                                   style="width:100%;"
                                   {{ $field->is_required ? 'required' : '' }}>
                            @break

                        @case ('select')
                            @php
                                $normalizedOpts = $field->normalizedOptions();
                                $optValues = array_map(static fn($o) => (string)$o['value'], $normalizedOpts);
                                $isAllowsCustom = $field->allowsCustomValue();
                                $isCustomSubmitted = (string)$val === 'other'
                                    || (string)$val === 'custom'
                                    || (string)$val === 'أخرى'
                                    || ($isAllowsCustom && (string)$val !== '' && !in_array((string)$val, array_diff($optValues, ['other', 'custom', 'أخرى']), true));

                                $customSubmittedVal = old('stage_fields_custom.' . $field->key, old('stage_fields.' . $field->key . '_custom', $isCustomSubmitted && (string)$val !== 'other' ? (string)$val : ''));
                                $customHtmlType = $field->customInputHtmlType();
                                if ($customSubmittedVal && ($field->customInputType() === 'date' || $field->customInputType() === 'datetime')) {
                                    try {
                                        $customSubmittedVal = $field->customInputType() === 'datetime'
                                            ? \Carbon\Carbon::parse((string)$customSubmittedVal)->format('Y-m-d\TH:i')
                                            : \Carbon\Carbon::parse((string)$customSubmittedVal)->format('Y-m-d');
                                    } catch (\Throwable) {}
                                }
                                $customInputId = 'sf_custom_' . $field->key . '_' . $scope;
                            @endphp
                            <select id="{{ $inputId }}"
                                    name="{{ $prefix }}[{{ $field->key }}]"
                                    class="control stage-field-select"
                                    data-allows-custom="{{ $isAllowsCustom ? '1' : '0' }}"
                                    data-custom-input-id="{{ $customInputId }}"
                                    style="width:100%;"
                                    {{ $field->is_required ? 'required' : '' }}>
                                <option value="">{{ $field->localizedPlaceholder() ?: __('crm.choose_option') }}</option>
                                @foreach ($normalizedOpts as $opt)
                                    @php
                                        $optVal = (string)$opt['value'];
                                        $isSelected = ((string)$val === $optVal)
                                            || ($optVal === 'other' && $isCustomSubmitted);
                                    @endphp
                                    <option value="{{ $optVal }}" @selected($isSelected)>
                                        {{ app()->getLocale() === 'en' ? $opt['label_en'] : $opt['label_ar'] }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($isAllowsCustom)
                                <div class="stage-field-custom-wrap"
                                     id="sf_custom_wrap_{{ $field->key }}_{{ $scope }}"
                                     data-parent-select="{{ $inputId }}"
                                     style="{{ $isCustomSubmitted ? '' : 'display:none;' }} margin-top:8px;">
                                    <label for="{{ $customInputId }}" style="display:flex; align-items:center; gap:5px; font-weight:600; font-size:12px; color:var(--dark, #334155); margin-bottom:4px;">
                                        <i class="bi bi-calendar-plus" style="color:var(--red, #dc2637); font-size:12px;"></i>
                                        <span>{{ $field->customInputLabel() }}</span>
                                        @if ($field->is_required)
                                            <span class="required" style="color:var(--red); font-weight:bold;">*</span>
                                        @endif
                                    </label>
                                    <input type="{{ $customHtmlType }}"
                                           id="{{ $customInputId }}"
                                           name="stage_fields_custom[{{ $field->key }}]"
                                           value="{{ $customSubmittedVal }}"
                                           placeholder="{{ $field->customInputPlaceholder() }}"
                                           class="control stage-field-custom-input"
                                           style="width:100%;"
                                           {{ $isCustomSubmitted && $field->is_required ? 'required' : '' }}>
                                </div>
                            @endif
                            @break

                        @case ('multiselect')
                            @php
                                $selectedArr = is_array($val) ? $val : ($val ? explode(',', (string)$val) : []);
                                $selectedArr = array_map('strval', $selectedArr);
                            @endphp
                            <select id="{{ $inputId }}"
                                    name="{{ $prefix }}[{{ $field->key }}][]"
                                    multiple
                                    size="3"
                                    class="control"
                                    style="width:100%; min-height:80px;"
                                    {{ $field->is_required ? 'required' : '' }}>
                                @foreach ($field->normalizedOptions() as $opt)
                                    @php $optVal = (string)$opt['value']; @endphp
                                    <option value="{{ $optVal }}" @selected(in_array($optVal, $selectedArr, true))>
                                        {{ app()->getLocale() === 'en' ? $opt['label_en'] : $opt['label_ar'] }}
                                    </option>
                                @endforeach
                            </select>
                            @break

                        @case ('checkbox')
                            <label class="check-card stage-field-check-card">
                                <input type="hidden" name="{{ $prefix }}[{{ $field->key }}]" value="0">
                                <input type="checkbox"
                                       id="{{ $inputId }}"
                                       name="{{ $prefix }}[{{ $field->key }}]"
                                       value="1"
                                       style="width:auto; margin:0;"
                                       @checked(filter_var($val, FILTER_VALIDATE_BOOLEAN))
                                       {{ $field->is_required ? 'required' : '' }}>
                                <span style="font-weight:600; font-size:13px;">{{ $field->localizedPlaceholder() ?: __('crm.yes') }}</span>
                            </label>
                            @break

                        @default
                            @php
                                $inputHtmlType = match($field->type) {
                                    'email' => 'email',
                                    'tel' => 'tel',
                                    'url' => 'url',
                                    default => 'text',
                                };
                            @endphp
                            <input type="{{ $inputHtmlType }}"
                                   id="{{ $inputId }}"
                                   name="{{ $prefix }}[{{ $field->key }}]"
                                   value="{{ is_scalar($val) ? (string) $val : '' }}"
                                   placeholder="{{ $field->localizedPlaceholder() }}"
                                   class="control"
                                   style="width:100%;"
                                   {{ $field->is_required ? 'required' : '' }}>
                    @endswitch

                    @if ($field->help_text_ar || $field->help_text_en)
                        <small class="hint">
                            {{ $field->localizedHelpText() }}
                        </small>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <script>
    (() => {
        const wrap = document.getElementById('stageFieldsWrap_{{ $scope }}');
        if (!wrap) return;

        function evalCondition(operator, actualVal, expectedVal) {
            actualVal = (actualVal === null || actualVal === undefined) ? '' : String(actualVal).trim();
            expectedVal = (expectedVal === null || expectedVal === undefined) ? '' : String(expectedVal).trim();

            switch (operator) {
                case 'equals':
                    return actualVal.toLowerCase() === expectedVal.toLowerCase();
                case 'not_equals':
                    return actualVal.toLowerCase() !== expectedVal.toLowerCase();
                case 'is_checked':
                    return actualVal === '1' || actualVal === 'true' || actualVal === 'yes' || actualVal === 'on';
                case 'is_not_checked':
                    return actualVal !== '1' && actualVal !== 'true' && actualVal !== 'yes' && actualVal !== 'on';
                case 'is_empty':
                    return actualVal === '';
                case 'is_not_empty':
                    return actualVal !== '';
                default:
                    return true;
            }
        }

        function getFieldValue(fieldKey) {
            const el = wrap.querySelector(`[name="{{ $prefix }}[${fieldKey}]"], [name="{{ $prefix }}[${fieldKey}][]"]`);
            if (!el) return '';
            if (el.type === 'checkbox') {
                return el.checked ? '1' : '0';
            }
            if (el.multiple) {
                return Array.from(el.selectedOptions).map(o => o.value).join(',');
            }
            return el.value || '';
        }

        function updateConditions() {
            const items = wrap.querySelectorAll('.stage-field-item[data-has-condition="1"]');
            items.forEach(item => {
                const parentKey = item.getAttribute('data-condition-field');
                const op = item.getAttribute('data-condition-operator') || 'equals';
                const exp = item.getAttribute('data-condition-value') || '';
                const isReq = item.getAttribute('data-sf-required') === '1';

                const parentVal = getFieldValue(parentKey);
                const met = evalCondition(op, parentVal, exp);

                const inputEl = item.querySelector('input:not([type="hidden"]), select, textarea');

                if (met) {
                    item.style.display = item.getAttribute('data-sf-type') === 'textarea' ? 'block' : '';
                    if (inputEl) {
                        if (isReq) inputEl.setAttribute('required', 'required');
                        inputEl.removeAttribute('disabled');
                    }
                } else {
                    item.style.display = 'none';
                    if (inputEl) {
                        inputEl.removeAttribute('required');
                    }
                }
            });
        }

        function updateCustomFields(activeTarget) {
            const customWraps = wrap.querySelectorAll('.stage-field-custom-wrap');
            customWraps.forEach(customWrap => {
                const selectId = customWrap.getAttribute('data-parent-select');
                const selectEl = document.getElementById(selectId);
                if (!selectEl) return;

                const inputEl = customWrap.querySelector('input');
                const selectedVal = (selectEl.value || '').trim().toLowerCase();
                const isOther = selectedVal === 'other' || selectedVal === 'custom' || selectedVal === 'أخرى';
                const isRequired = customWrap.closest('.stage-field-item')?.getAttribute('data-sf-required') === '1';

                if (isOther) {
                    if (customWrap.style.display === 'none') {
                        customWrap.style.display = 'block';
                        customWrap.style.opacity = '0';
                        customWrap.style.transform = 'translateY(-4px)';
                        requestAnimationFrame(() => {
                            customWrap.style.opacity = '1';
                            customWrap.style.transform = 'translateY(0)';
                        });
                    }
                    if (inputEl) {
                        if (isRequired) inputEl.setAttribute('required', 'required');
                        inputEl.removeAttribute('disabled');
                        if (activeTarget === selectEl) {
                            setTimeout(() => inputEl.focus(), 50);
                        }
                    }
                } else {
                    customWrap.style.display = 'none';
                    if (inputEl) {
                        inputEl.removeAttribute('required');
                    }
                }
            });
        }

        wrap.addEventListener('change', (e) => {
            updateConditions();
            updateCustomFields(e.target);
        });
        wrap.addEventListener('input', (e) => {
            updateConditions();
        });
        updateConditions();
        updateCustomFields(null);
    })();
    </script>
@endif

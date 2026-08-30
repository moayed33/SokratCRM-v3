@extends('settings.layout')

@section('title', __('crm.stage_fields_title', ['stage' => $stage->localizedName()]))
@section('heading', __('crm.stage_fields_heading', ['stage' => $stage->localizedName()]))
@section('subheading', __('crm.stage_fields_subheading'))

@section('content')
<style>
.stage-q-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.stage-q-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;background:#fff;border:1px solid var(--line)}
.stage-q-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px}
.question-cards{display:flex;flex-direction:column;gap:12px}
.q-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:14px;box-shadow:0 2px 6px rgba(0,0,0,0.02);transition:all .15s ease}
.q-card:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(0,0,0,0.04)}
.q-card.inactive{opacity:0.6;background:#f8fafc}
.q-info{display:flex;align-items:flex-start;gap:14px;min-width:0;flex:1}
.q-type-icon{width:40px;height:40px;border-radius:10px;background:#f1f5f9;color:#475569;display:grid;place-items:center;font-size:18px;flex-shrink:0}
.q-details h4{margin:0 0 4px;font-size:15px;color:var(--dark);font-weight:800}
.q-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:var(--muted)}
.q-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
.preview-panel{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;position:sticky;top:20px}
.preview-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.preview-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px}
.accordion-toggle{background:none;border:none;padding:0;color:#4f46e5;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.options-builder-table{width:100%;margin-top:8px}
.options-builder-table input{padding:7px 10px;font-size:13px}
.preset-card{border:1px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;background:#fff;transition:all .15s ease}
.preset-card:hover{border-color:#4f46e5;background:#f5f3ff}
@media(max-width:1024px){.stage-q-grid{grid-template-columns:1fr}}
html.dark-mode .stage-q-badge{background:#18181b!important;border-color:rgba(255,255,255,0.08)!important;color:#f4f4f5!important}
html.dark-mode .q-card{background:rgba(255,255,255,0.03)!important;border-color:rgba(255,255,255,0.08)!important}
html.dark-mode .q-card.inactive{background:rgba(255,255,255,0.01)!important}
html.dark-mode .q-type-icon{background:rgba(255,255,255,0.06)!important;color:#f4f4f5!important}
html.dark-mode .q-details h4{color:#f4f4f5!important}
html.dark-mode .preview-panel{background:#18181b!important;border-color:rgba(255,255,255,0.08)!important}
html.dark-mode .preview-head{border-bottom-color:rgba(255,255,255,0.08)!important}
html.dark-mode .preview-head h3{color:#f4f4f5!important}
html.dark-mode .preview-box{background:rgba(255,255,255,0.02)!important;border-color:rgba(255,255,255,0.08)!important}
html.dark-mode .preset-card{background:#18181b!important;border-color:rgba(255,255,255,0.08)!important;color:#f4f4f5!important}
html.dark-mode .preset-card:hover{background:#27272a!important;border-color:#6366f1!important}
html.dark-mode #questionModal > div, html.dark-mode #presetsModal > div{background:#18181b!important;color:#f4f4f5!important;border:1px solid rgba(255,255,255,0.1)!important}
html.dark-mode #optionsBuilderWrap{background:rgba(255,255,255,0.03)!important;border-color:rgba(255,255,255,0.08)!important}
html.dark-mode .condition-builder-box{background:rgba(255,255,255,0.04)!important;border-color:rgba(255,255,255,0.08)!important}
</style>

<div class="stage-q-header">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <a href="{{ route('v2.settings.stages.index') }}" class="btn soft">
            <i class="bi bi-arrow-right"></i> {{ __('crm.back_to_stages') }}
        </a>
        <div class="stage-q-badge">
            <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:{{ $stage->color ?? '#64748b' }};"></span>
            <strong>{{ $stage->localizedName() }}</strong>
            <span class="badge {{ $stage->isPrimary() ? 'system' : '' }}">
                {{ $stage->isPrimary() ? __('crm.primary_stage_badge') : __('crm.additional_stage_badge') }}
            </span>
        </div>
    </div>
    <div style="display:flex; gap:8px;">
        <button type="button" class="btn soft" onclick="openPresetsModal()">
            <i class="bi bi-magic"></i> {{ __('crm.use_preset_template') }}
        </button>
        <button type="button" class="btn primary" onclick="openAddQuestionModal()">
            <i class="bi bi-plus-lg"></i> {{ __('crm.add_question_btn') }}
        </button>
    </div>
</div>

<div class="stage-q-grid">
    <!-- LEFT: QUESTIONS LIST -->
    <div>
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2><i class="bi bi-ui-checks"></i> {{ __('crm.stage_questions_heading') }}</h2>
                    <p>{{ __('crm.stage_questions_subheading') }}</p>
                </div>
            </div>

            @if ($fields->isEmpty())
                <div style="text-align:center; padding: 48px 20px; color:var(--muted);">
                    <i class="bi bi-chat-square-text" style="font-size:42px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                    <h3 style="margin:0 0 6px; font-size:16px; color:#334155;">{{ __('crm.no_stage_questions_title') }}</h3>
                    <p style="font-size:13px; margin:0 0 18px;">{{ __('crm.no_stage_questions_desc') }}</p>
                    <div style="display:flex; justify-content:center; gap:10px;">
                        <button type="button" class="btn primary small" onclick="openAddQuestionModal()">
                            <i class="bi bi-plus-lg"></i> {{ __('crm.add_first_question') }}
                        </button>
                        <button type="button" class="btn soft small" onclick="openPresetsModal()">
                            <i class="bi bi-magic"></i> {{ __('crm.use_preset_template') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="question-cards" id="questionsContainer">
                    @foreach ($fields as $field)
                        @php
                            $typeIcon = match($field->type) {
                                'datetime', 'date' => 'bi-calendar-event',
                                'select', 'multiselect' => 'bi-menu-button-wide',
                                'checkbox' => 'bi-check-square',
                                'number' => 'bi-hash',
                                'textarea' => 'bi-textarea-t',
                                'tel' => 'bi-telephone',
                                'email' => 'bi-envelope',
                                default => 'bi-fonts',
                            };
                            $typeLabel = __('crm.field_type_' . $field->type) ?? $field->type;
                            $hasCondition = !empty($field->conditions) && !empty($field->conditions['field']);
                            $condField = $hasCondition ? $fields->firstWhere('key', $field->conditions['field']) : null;
                        @endphp

                        <article class="q-card {{ $field->is_active ? '' : 'inactive' }}" data-field-id="{{ $field->id }}">
                            <div class="q-info">
                                <div class="q-type-icon">
                                    <i class="bi {{ $typeIcon }}"></i>
                                </div>
                                <div class="q-details">
                                    <h4>{{ $field->localizedLabel() }}</h4>
                                    <div class="q-meta">
                                        <span><i class="bi bi-tag"></i> {{ $typeLabel }}</span>
                                        <span>•</span>
                                        @if ($field->is_required)
                                            <span style="color:#b91c1c; font-weight:800;"><i class="bi bi-asterisk" style="font-size:9px"></i> {{ __('crm.required') }}</span>
                                        @else
                                            <span>{{ __('crm.optional') }}</span>
                                        @endif

                                        @if ($hasCondition)
                                            <span>•</span>
                                            <span class="badge" style="background:#fdf4ff; color:#a21caf; border:1px solid #f5d0fe;">
                                                <i class="bi bi-diagram-2"></i> {{ __('crm.conditional_rule_summary', ['field' => $condField?->localizedLabel() ?? $field->conditions['field']]) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="q-actions">
                                <button type="button" class="btn small soft" onclick="openEditQuestionModal({{ json_encode($field) }})" title="{{ __('crm.edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('v2.settings.stages.fields.toggle', [$stage, $field]) }}" style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn small soft" title="{{ $field->is_active ? __('crm.deactivate_action') : __('crm.activate_action') }}">
                                        <i class="bi {{ $field->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('v2.settings.stages.fields.destroy', [$stage, $field]) }}" style="margin:0;" onsubmit="return confirm(@json($field->values_count > 0 ? __('crm.stage_field_archive_confirm') : __('crm.confirm_delete_stage_field')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn small danger" title="{{ __('crm.delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <!-- RIGHT: LIVE EMPLOYEE PREVIEW -->
    <div>
        <aside class="preview-panel">
            <div class="preview-head">
                <div>
                    <h3 style="margin:0; font-size:15px; color:#1e293b;"><i class="bi bi-eye"></i> {{ __('crm.employee_transition_preview_title') }}</h3>
                    <small style="color:var(--muted)">{{ __('crm.employee_transition_preview_desc') }}</small>
                </div>
                <span class="badge active" style="font-size:10px;">{{ __('crm.live_preview') }}</span>
            </div>

            <div class="preview-box">
                <div style="margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $stage->color ?? '#3478f6' }};"></span>
                    <strong style="font-size:13px;">{{ __('crm.move_to_stage_preview', ['stage' => $stage->localizedName()]) }}</strong>
                </div>

                @if ($fields->where('is_active', true)->isEmpty())
                    <p style="color:var(--muted); font-size:12px; text-align:center; margin:16px 0;">
                        <i class="bi bi-check-circle" style="font-size:20px; display:block; margin-bottom:6px; color:#94a3b8;"></i>
                        {{ __('crm.no_extra_questions_for_stage') }}
                    </p>
                @else
                    @include('partials.stage-field-inputs', [
                        'fields' => $fields->where('is_active', true)->values(),
                        'recordValues' => [],
                        'prefix' => 'preview_fields',
                        'scope' => 'admin_live_preview',
                    ])
                @endif
            </div>
        </aside>
    </div>
</div>

<!-- MODAL: ADD / EDIT QUESTION -->
<div id="questionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:620px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;" id="questionModalTitle">{{ __('crm.add_question_modal_title') }}</h3>
            <button type="button" onclick="closeQuestionModal()" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>

        <form id="questionForm" method="POST" action="">
            @csrf
            <div id="methodContainer"></div>

            <!-- CORE QUESTION DETAILS -->
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.question_label_ar_field') }} <span style="color:var(--red)">*</span></label>
                <input type="text" id="qLabelAr" name="label_ar" required placeholder="{{ __('crm.question_label_ar_hint') }}" autofocus>
            </div>

            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.answer_type_label') }} <span style="color:var(--red)">*</span></label>
                    <select name="type" id="qType" required onchange="handleAnswerTypeChange(this.value)">
                        <option value="text">{{ __('crm.type_short_text') }}</option>
                        <option value="textarea">{{ __('crm.type_long_text') }}</option>
                        <option value="datetime">{{ __('crm.type_datetime') }}</option>
                        <option value="date">{{ __('crm.type_date') }}</option>
                        <option value="select">{{ __('crm.type_dropdown') }}</option>
                        <option value="multiselect">{{ __('crm.type_multiselect') }}</option>
                        <option value="checkbox">{{ __('crm.type_yes_no') }}</option>
                        <option value="number">{{ __('crm.type_number') }}</option>
                        <option value="tel">{{ __('crm.type_phone') }}</option>
                        <option value="email">{{ __('crm.type_email') }}</option>
                        <option value="url">{{ __('crm.type_url') }}</option>
                    </select>
                </div>
                <div>
                    <label style="margin-bottom:8px;">{{ __('crm.requirement_label') }}</label>
                    <label class="check-card" style="cursor:pointer; padding:10px; margin:0;">
                        <input type="checkbox" id="qIsRequired" name="is_required" value="1">
                        <div>
                            <strong style="font-size:13px;">{{ __('crm.is_required_question') }}</strong>
                            <small>{{ __('crm.is_required_question_hint') }}</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- INTERACTIVE OPTIONS BUILDER -->
            <div id="optionsBuilderWrap" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="margin:0; font-size:13px; font-weight:800;">{{ __('crm.options_list_title') }}</label>
                    <button type="button" class="btn small soft" onclick="addOptionRow()" style="background:#fff;">
                        <i class="bi bi-plus"></i> {{ __('crm.add_option_btn') }}
                    </button>
                </div>
                <div id="optionsListContainer" style="display:flex; flex-direction:column; gap:8px;"></div>
                <small class="hint" style="margin-bottom:10px; display:block;">{{ __('crm.options_builder_hint') }}</small>

                <!-- ALLOW OTHER OPTION -->
                <div id="allowCustomOptionWrap" style="margin-top:12px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                    <label class="check-card" style="cursor:pointer; padding:8px 10px; margin:0; background:#fff; border:1px solid #e2e8f0; border-radius:8px;">
                        <input type="checkbox" id="qAllowCustom" name="allow_custom" value="1" onchange="toggleCustomTypeSelect(this.checked)">
                        <div>
                            <strong style="font-size:13px;"><i class="bi bi-calendar-plus" style="color:var(--red);"></i> {{ __('crm.allow_custom_other') }}</strong>
                            <small style="display:block; color:var(--muted); font-size:11px;">{{ __('crm.allow_custom_other_hint') }}</small>
                        </div>
                    </label>
                    <div id="customTypeWrap" style="display:none; margin-top:8px; grid-template-columns:1fr 1.5fr; gap:8px; align-items:center;">
                        <label style="font-size:12px; font-weight:700; margin:0;">{{ __('crm.custom_input_type') }}:</label>
                        <select name="custom_type" id="qCustomType" style="padding:6px 10px; font-size:13px;">
                            <option value="date">{{ __('crm.custom_input_type_date') }}</option>
                            <option value="datetime">{{ __('crm.custom_input_type_datetime') }}</option>
                            <option value="text">{{ __('crm.custom_input_type_text') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ADVANCED OPTIONS TOGGLE -->
            <div style="margin-top:16px; margin-bottom:16px; border-top:1px solid var(--line); padding-top:12px;">
                <button type="button" class="accordion-toggle" onclick="toggleAdvancedOptions()">
                    <i class="bi bi-sliders"></i> <span id="advancedOptionsToggleText">{{ __('crm.show_advanced_options') }}</span>
                    <i class="bi bi-chevron-down" id="advancedOptionsChevron"></i>
                </button>

                <div id="advancedOptionsContent" style="display:none; margin-top:14px;">
                    <div class="form-grid" style="margin-bottom:14px;">
                        <div>
                            <label style="font-size:12px;">{{ __('crm.question_label_en_field') }}</label>
                            <input type="text" id="qLabelEn" name="label_en" placeholder="e.g. Next Callback Date">
                        </div>
                        <div>
                            <label style="font-size:12px;">{{ __('crm.question_placeholder_field') }}</label>
                            <input type="text" id="qPlaceholder" name="placeholder_ar" placeholder="{{ __('crm.optional_placeholder_hint') }}">
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;">{{ __('crm.question_help_text_field') }}</label>
                        <input type="text" id="qHelpText" name="help_text_ar" placeholder="{{ __('crm.optional_help_text_hint') }}">
                    </div>

                    <!-- NATURAL SENTENCE CONDITION BUILDER -->
                    <div class="condition-builder-box" style="background:#f1f5f9; border-radius:10px; padding:12px; margin-bottom:14px; border:1px solid var(--line);">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:0; font-size:13px; font-weight:700;">
                            <input type="checkbox" id="qHasCondition" onchange="toggleConditionInputs(this.checked)">
                            <span><i class="bi bi-diagram-2"></i> {{ __('crm.show_this_question_when') }}</span>
                        </label>

                        <div id="conditionInputsRow" style="display:none; margin-top:10px; display:none; grid-template-columns:1.5fr 1fr 1.5fr; gap:8px;">
                            <div>
                                <select id="qCondField" name="condition_field">
                                    <option value="">{{ __('crm.select_previous_question') }}</option>
                                    @foreach($otherFields as $of)
                                        <option value="{{ $of->key }}">{{ $of->localizedLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="qCondOperator" name="condition_operator" onchange="handleOperatorChange(this.value)">
                                    <option value="equals">{{ __('crm.operator_equals') }}</option>
                                    <option value="not_equals">{{ __('crm.operator_not_equals') }}</option>
                                    <option value="is_checked">{{ __('crm.operator_is_checked') }}</option>
                                    <option value="is_not_checked">{{ __('crm.operator_is_not_checked') }}</option>
                                    <option value="is_empty">{{ __('crm.operator_is_empty') }}</option>
                                    <option value="is_not_empty">{{ __('crm.operator_is_not_empty') }}</option>
                                </select>
                            </div>
                            <div id="qCondValueWrap">
                                <input type="text" id="qCondValue" name="condition_value" placeholder="{{ __('crm.expected_value_placeholder') }}">
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <label class="check-card" style="cursor:pointer; padding:8px 12px; font-size:12px; flex:1;">
                            <input type="checkbox" id="qShowInHistory" name="show_in_history" value="1" checked>
                            <div>
                                <strong>{{ __('crm.show_in_history_label') }}</strong>
                                <small>{{ __('crm.show_in_history_subtext') }}</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="btn soft" onclick="closeQuestionModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary" id="questionSubmitBtn">{{ __('crm.save_question_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PRESET TEMPLATES -->
<div id="presetsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:640px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <div>
                <h3 style="margin:0; font-size:18px;"><i class="bi bi-magic"></i> {{ __('crm.choose_preset_template_title') }}</h3>
                <small style="color:var(--muted)">{{ __('crm.choose_preset_template_subtitle') }}</small>
            </div>
            <button type="button" onclick="document.getElementById('presetsModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>

        <form method="POST" action="{{ route('v2.settings.stages.fields.preset', $stage) }}">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:18px;">
                @foreach($presets as $pKey => $preset)
                    <label class="preset-card">
                        <div style="display:flex; align-items:flex-start; gap:10px;">
                            <input type="radio" name="preset" value="{{ $pKey }}" required style="width:auto; margin-top:3px;">
                            <div>
                                <strong style="display:block; font-size:14px; color:#1e293b;">
                                    <i class="bi {{ $preset['icon'] ?? 'bi-bookmark' }}"></i> {{ app()->getLocale() === 'en' ? $preset['name_en'] : $preset['name_ar'] }}
                                </strong>
                                <small style="display:block; color:var(--muted); font-size:12px; margin-top:4px;">
                                    {{ app()->getLocale() === 'en' ? $preset['description_en'] : $preset['description_ar'] }}
                                </small>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            @if($fields->isNotEmpty())
                <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:12px; margin-bottom:16px;">
                    <label class="check-card" style="cursor:pointer; background:none; border:none; padding:0; margin:0;">
                        <input type="checkbox" name="overwrite" value="1" style="margin-top:2px;">
                        <div>
                            <strong style="color:#9a3412; font-size:13px;">{{ __('crm.overwrite_existing_questions_warning') }}</strong>
                            <small style="color:#c2410c;">{{ __('crm.overwrite_warning_details') }}</small>
                        </div>
                    </label>
                </div>
            @endif

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('presetsModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.apply_template_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
let optionRowIndex = 0;

function openAddQuestionModal() {
    document.getElementById('questionModalTitle').textContent = @json(__('crm.add_question_modal_title'));
    const form = document.getElementById('questionForm');
    form.action = "{{ route('v2.settings.stages.fields.store', $stage) }}";
    document.getElementById('methodContainer').innerHTML = '';

    document.getElementById('qLabelAr').value = '';
    document.getElementById('qLabelEn').value = '';
    document.getElementById('qType').value = 'text';
    document.getElementById('qIsRequired').checked = false;
    document.getElementById('qPlaceholder').value = '';
    document.getElementById('qHelpText').value = '';

    // Reset allow custom
    document.getElementById('qAllowCustom').checked = false;
    toggleCustomTypeSelect(false);
    document.getElementById('qCustomType').value = 'date';

    // Reset options builder
    document.getElementById('optionsListContainer').innerHTML = '';
    handleAnswerTypeChange('text');

    // Reset conditions
    document.getElementById('qHasCondition').checked = false;
    toggleConditionInputs(false);
    document.getElementById('qCondField').value = '';
    document.getElementById('qCondOperator').value = 'equals';
    document.getElementById('qCondValue').value = '';

    // Advanced collapsed
    hideAdvancedOptions();

    document.getElementById('questionModal').style.display = 'flex';
}

function openEditQuestionModal(field) {
    document.getElementById('questionModalTitle').textContent = @json(__('crm.edit_question_modal_title'));
    const form = document.getElementById('questionForm');
    form.action = `/settings/stages/{{ $stage->id }}/fields/${field.id}`;
    document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PATCH">';

    document.getElementById('qLabelAr').value = field.label_ar || '';
    document.getElementById('qLabelEn').value = field.label_en || '';
    document.getElementById('qType').value = field.type || 'text';
    document.getElementById('qIsRequired').checked = Boolean(field.is_required);
    document.getElementById('qPlaceholder').value = field.placeholder_ar || '';
    document.getElementById('qHelpText').value = field.help_text_ar || '';

    // Options
    document.getElementById('optionsListContainer').innerHTML = '';
    if (field.type === 'select' || field.type === 'multiselect') {
        const opts = field.options || [];
        if (Array.isArray(opts) && opts.length > 0) {
            opts.forEach(opt => {
                const ar = typeof opt === 'object' && opt !== null ? (opt.label_ar || opt.value || '') : String(opt);
                const en = typeof opt === 'object' && opt !== null ? (opt.label_en || '') : '';
                const val = typeof opt === 'object' && opt !== null ? (opt.value || '') : '';
                addOptionRow(ar, en, val);
            });
        } else {
            addOptionRow();
        }
    }
    const hasCustom = Boolean(field.validation_rules && (field.validation_rules.allow_custom || field.validation_rules.allow_other));
    document.getElementById('qAllowCustom').checked = hasCustom;
    toggleCustomTypeSelect(hasCustom);
    document.getElementById('qCustomType').value = (field.validation_rules && (field.validation_rules.custom_type || field.validation_rules.custom_input_type)) || 'date';

    handleAnswerTypeChange(field.type);

    // Conditions
    const hasCond = field.conditions && field.conditions.field;
    document.getElementById('qHasCondition').checked = Boolean(hasCond);
    toggleConditionInputs(Boolean(hasCond));
    if (hasCond) {
        document.getElementById('qCondField').value = field.conditions.field || '';
        document.getElementById('qCondOperator').value = field.conditions.operator || 'equals';
        document.getElementById('qCondValue').value = field.conditions.value || '';
        handleOperatorChange(field.conditions.operator || 'equals');
    }

    document.getElementById('qShowInHistory').checked = Boolean(field.show_in_history ?? true);

    hideAdvancedOptions();
    document.getElementById('questionModal').style.display = 'flex';
}

function closeQuestionModal() {
    document.getElementById('questionModal').style.display = 'none';
}

function openPresetsModal() {
    document.getElementById('presetsModal').style.display = 'flex';
}

function handleAnswerTypeChange(type) {
    const wrap = document.getElementById('optionsBuilderWrap');
    if (type === 'select' || type === 'multiselect') {
        wrap.style.display = 'block';
        if (document.getElementById('optionsListContainer').children.length === 0) {
            addOptionRow();
        }
    } else {
        wrap.style.display = 'none';
    }
}

function addOptionRow(labelAr = '', labelEn = '', value = '') {
    const container = document.getElementById('optionsListContainer');
    const idx = optionRowIndex++;
    const row = document.createElement('div');
    row.style.cssText = 'display:flex; gap:8px; align-items:center;';
    row.innerHTML = `
        <input type="text" name="options_list[${idx}][label_ar]" value="${escapeHtml(labelAr)}" placeholder="{{ __('crm.option_text_placeholder') }}" required style="flex:2; padding:7px 10px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
        <input type="text" name="options_list[${idx}][label_en]" value="${escapeHtml(labelEn)}" placeholder="{{ __('crm.option_text_en_placeholder') }}" style="flex:1.5; padding:7px 10px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
        <input type="hidden" name="options_list[${idx}][value]" value="${escapeHtml(value)}">
        <button type="button" class="btn small danger" onclick="this.closest('div').remove()" style="padding:4px 8px; font-size:12px;" title="{{ __('crm.delete') }}">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(row);
}

function toggleAdvancedOptions() {
    const content = document.getElementById('advancedOptionsContent');
    const chevron = document.getElementById('advancedOptionsChevron');
    const text = document.getElementById('advancedOptionsToggleText');
    const isVisible = content.style.display === 'block';

    if (isVisible) {
        content.style.display = 'none';
        chevron.className = 'bi bi-chevron-down';
        text.textContent = @json(__('crm.show_advanced_options'));
    } else {
        content.style.display = 'block';
        chevron.className = 'bi bi-chevron-up';
        text.textContent = @json(__('crm.hide_advanced_options'));
    }
}

function hideAdvancedOptions() {
    document.getElementById('advancedOptionsContent').style.display = 'none';
    document.getElementById('advancedOptionsChevron').className = 'bi bi-chevron-down';
    document.getElementById('advancedOptionsToggleText').textContent = @json(__('crm.show_advanced_options'));
}

function toggleConditionInputs(show) {
    const row = document.getElementById('conditionInputsRow');
    row.style.display = show ? 'grid' : 'none';
}

function handleOperatorChange(op) {
    const wrap = document.getElementById('qCondValueWrap');
    const noValOps = ['is_checked', 'is_not_checked', 'is_empty', 'is_not_empty'];
    wrap.style.display = noValOps.includes(op) ? 'none' : 'block';
}

function toggleCustomTypeSelect(show) {
    const wrap = document.getElementById('customTypeWrap');
    if (wrap) wrap.style.display = show ? 'grid' : 'none';
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
@endsection

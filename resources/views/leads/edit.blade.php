<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('crm.app_name') }} — {{ __('crm.edit_lead_title', ['name' => $lead->name]) }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<style>
:root{
 --red:#dc2637;
 --red-hover:#b81829;
 --dark:#182033;
 --muted:#64748b;
 --line:#e2e8f0;
 --bg:#f8fafc;
 --card:#ffffff;
 --shadow:0 10px 30px rgba(15,23,42,.05);
 --radius:14px;
}
html.dark-mode{
 --dark:#f1f5f9;
 --muted:#94a3b8;
 --line:#334155;
 --bg:#0f172a;
 --card:#1e293b;
 --shadow:0 10px 30px rgba(0,0,0,.3);
}
*{box-sizing:border-box}
body{
 margin:0;
 min-width:320px;
 background:var(--bg);
 color:var(--dark);
 font-family:var(--font-primary);
 font-size:14px;
}
button,input,select,textarea{font:inherit}
a{color:inherit}
.crm-app{display:flex;min-height:100vh}
.crm-main{flex:1;min-width:0;padding:24px 32px 60px}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}
.topbar-left{display:flex;align-items:center;gap:12px}
.topbar h1{margin:0;font-size:24px;font-weight:900}
.topbar p{margin:4px 0 0;color:var(--muted);font-size:13px}
.btn{
 display:inline-flex;align-items:center;justify-content:center;gap:8px;
 min-height:42px;padding:0 18px;border:1px solid var(--line);border-radius:10px;
 background:var(--card);color:var(--dark);font-weight:700;cursor:pointer;text-decoration:none;
 transition:.15s;font-size:13px;
}
.btn:hover{border-color:#cbd5e1;background:#f1f5f9}
.btn.primary{background:var(--red);border-color:var(--red);color:#fff;box-shadow:0 4px 14px rgba(220,38,55,.25)}
.btn.primary:hover{background:var(--red-hover);border-color:var(--red-hover)}
.btn.danger{color:#dc2637;border-color:#fecdd3}
.btn.danger:hover{background:#fff1f2}
.btn.soft{background:#f1f5f9;border-color:transparent}
.btn.small{min-height:34px;padding:0 12px;font-size:12px}

.form-card{
 background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
 box-shadow:var(--shadow);padding:24px;margin-bottom:24px;
}
.section-head{
 display:flex;align-items:center;justify-content:space-between;
 margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--line);
}
.section-head h2{margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:9px}
.section-head h2 i{color:var(--red);font-size:19px}
.section-head p{margin:4px 0 0;color:var(--muted);font-size:12px}

.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.form-grid.two-cols{grid-template-columns:repeat(2,minmax(0,1fr))}
.form-grid.four-cols{grid-template-columns:repeat(4,minmax(0,1fr))}
.field{display:flex;flex-direction:column}
.field.full{grid-column:1/-1}
.field.two-span{grid-column:span 2}
label{display:block;font-weight:700;font-size:13px;margin-bottom:7px}
label span.req{color:var(--red)}
input,select,textarea{
 width:100%;border:1px solid var(--line);border-radius:9px;padding:10px 12px;
 background:var(--card);color:var(--dark);outline:none;transition:.15s;
}
input:focus,select:focus,textarea:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,55,.1)}
textarea{resize:vertical;min-height:90px}

.repeater-row{
 display:grid;grid-template-columns:1fr 140px 42px;gap:10px;align-items:center;
 margin-bottom:10px;padding:10px 12px;background:var(--bg);border:1px solid var(--line);border-radius:9px;
}
.person-row{
 display:grid;grid-template-columns:2fr 1.5fr 1.5fr 3fr 42px;gap:10px;align-items:center;
 margin-bottom:10px;padding:12px;background:var(--bg);border:1px solid var(--line);border-radius:10px;
}

.flash{border-radius:10px;padding:14px 16px;margin-bottom:20px;font-weight:700;font-size:13px}
.flash.error{background:#fff1f2;color:#991b1b;border:1px solid #fecdd3}
.flash.error ul{margin:6px 0 0;padding-inline-start:20px}

@media(max-width:1100px){
 .form-grid{grid-template-columns:repeat(2,1fr)}
 .person-row{grid-template-columns:1fr 1fr;gap:8px}
 .person-row .btn-remove{grid-column:1/-1}
}
@media(max-width:768px){
 .crm-main{padding:16px}
 .form-grid,.form-grid.two-cols,.form-grid.four-cols{grid-template-columns:1fr}
 .field.two-span{grid-column:1}
 .repeater-row{grid-template-columns:1fr 1fr auto}
}
</style>
</head>
<body>
@include('partials.page-loader')
<div class="crm-app">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        <header class="topbar">
            <div class="topbar-left">
                <a href="{{ route('v2.leads.show', $lead) }}" class="btn soft small" title="{{ __('crm.back_to_lead_profile') }}">
                    <i class="bi bi-arrow-left rtl:rotate-180"></i>
                </a>
                <div>
                    <h1>{{ __('crm.edit_lead_title', ['name' => $lead->name]) }}</h1>
                    <p>{{ __('crm.edit_lead_subtitle') }}</p>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="{{ route('v2.leads.show', $lead) }}" class="btn soft">
                    <i class="bi bi-eye"></i> {{ __('crm.view_profile') }}
                </a>
                <a href="{{ route('v2.leads') }}" class="btn soft">
                    <i class="bi bi-x-lg"></i> {{ __('crm.cancel') }}
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash error">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>{{ __('crm.please_correct_errors') }}</strong>
                </div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('v2.leads.update', $lead) }}" id="leadEditForm">
            @csrf
            @method('PATCH')

            <!-- SECTION 1: CUSTOMER INFORMATION -->
            <section class="form-card">
                <div class="section-head">
                    <div>
                        <h2><i class="bi bi-person-badge"></i> {{ __('crm.section_basic_info') }}</h2>
                        <p>{{ __('crm.section_basic_info_desc') }}</p>
                    </div>
                </div>

                <div class="form-grid two-cols">
                    <div class="field">
                        <label for="customerName">{{ __('crm.customer_name_label') }} <span class="req">*</span></label>
                        <input type="text" id="customerName" name="name" value="{{ old('name', $lead->name) }}" placeholder="{{ __('crm.customer_name_placeholder') }}" required autofocus>
                    </div>

                    <div class="field">
                        <label for="primaryPhone">{{ __('crm.primary_phone_label') }} <span class="req">*</span></label>
                        <input type="tel" id="primaryPhone" name="phone" value="{{ old('phone', $lead->phone) }}" placeholder="{{ __('crm.primary_phone_placeholder') }}" required dir="ltr">
                    </div>
                </div>

                <!-- CONFIGURABLE BASIC-INFO FIELDS -->
                @php $basicInfoFields = $customFields->where('section', 'basic_info')->values(); @endphp
                @if ($basicInfoFields->isNotEmpty())
                    <div class="form-grid two-cols" style="margin-top:20px">
                        @include('partials.lead-custom-field-inputs', ['fields' => $basicInfoFields, 'recordValues' => is_array($lead->custom_fields) ? $lead->custom_fields : []])
                    </div>
                @endif

                <!-- ADDITIONAL PHONE NUMBERS REPEATER -->
                <div style="margin-top:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <label style="margin:0;font-size:13px;color:var(--muted)">
                            <i class="bi bi-telephone-plus"></i> {{ __('crm.additional_phones_label') }}
                        </label>
                        <button type="button" class="btn small soft" id="addPhoneBtn">
                            <i class="bi bi-plus"></i> {{ __('crm.add_another_phone') }}
                    </div>
                    <div id="additionalPhonesContainer">
                        @php
                            $currentPhones = old('additional_phones') !== null
                                ? old('additional_phones')
                                : $lead->additionalPhones->map(fn($p) => ['phone' => $p->phone, 'label' => $p->label])->toArray();
                        @endphp
                        @foreach ($currentPhones as $index => $phoneRow)
                            <div class="repeater-row">
                                <input type="tel" name="additional_phones[{{ $index }}][phone]" value="{{ $phoneRow['phone'] ?? '' }}" placeholder="{{ __('crm.additional_phone_placeholder') }}" dir="ltr">
                                <select name="additional_phones[{{ $index }}[label]">
                                    @foreach (\App\Support\CrmOptions::get('phone_label') as $phoneLabel)
                                        <option value="{{ $phoneLabel['value'] }}" @selected(($phoneRow['label'] ?? '') === $phoneLabel['value'])>{{ \App\Support\CrmOptions::labelOf($phoneLabel) }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn small danger" onclick="this.closest('.repeater-row').remove()" title="{{ __('crm.delete') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- SECTION 2: DONATION INFORMATION -->
            <section class="form-card">
                <div class="section-head">
                    <div>
                        <h2><i class="bi bi-cash-coin"></i> {{ __('crm.section_donation_info') }}</h2>
                        <p>{{ __('crm.section_donation_info_desc') }}</p>
                    </div>
                </div>

                <div class="form-grid four-cols">
                    <div class="field">
                        <label for="donationType">{{ __('crm.donation_type') }}</label>
                        <select id="donationType" name="donation_type">
                            <option value="">{{ __('crm.select_donation_type') }}</option>
                            @foreach ($donationTypes as $dType)
                                <option value="{{ $dType->name_ar }}" {{ old('donation_type', $lead->donation_type) === $dType->name_ar ? 'selected' : '' }}>
                                    {{ (app()->getLocale() === 'en' && !empty($dType->name_en)) ? $dType->name_en : $dType->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="donationCycle">{{ __('crm.donation_cycle_frequency') }}</label>
                        <select id="donationCycle" name="donation_cycle">
                            <option value="">{{ __('crm.select_donation_cycle') }}</option>
                            @foreach ($donationCycles as $cKey => $cLabel)
                                <option value="{{ $cKey }}" {{ old('donation_cycle', $lead->donation_cycle) === $cKey ? 'selected' : '' }}>
                                    {{ __('crm.donation_cycle_' . $cKey) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="donationValue">{{ __('crm.donation_value_currency') }}</label>
                        <input type="number" id="donationValue" name="donation_value" value="{{ old('donation_value', $lead->donation_value) }}" min="0" step="0.01" placeholder="500.00" style="font-weight:700">
                    </div>

                    <div class="field">
                        <label for="donationPurpose">{{ __('crm.donation_purpose_label') }}</label>
                        <select id="donationPurpose" name="donation_purpose">
                            <option value="">{{ __('crm.select_donation_purpose') }}</option>
                            @foreach ($donationPurposes as $purpose)
                                <option value="{{ $purpose->name_ar }}" {{ old('donation_purpose', $lead->donation_purpose) === $purpose->name_ar ? 'selected' : '' }}>
                                    {{ (app()->getLocale() === 'en' && !empty($purpose->name_en)) ? $purpose->name_en : $purpose->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @include('partials.lead-custom-field-inputs', ['fields' => $customFields->where('section', 'donation_info')->values(), 'recordValues' => is_array($lead->custom_fields) ? $lead->custom_fields : []])
                </div>
            </section>

            <!-- SECTION 3: COMMUNICATION & FOLLOW-UP -->
            <section class="form-card">
                <div class="section-head">
                    <div>
                        <h2><i class="bi bi-chat-left-text"></i> {{ __('crm.section_contact_followup') }}</h2>
                        <p>{{ __('crm.section_contact_followup_desc') }}</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="pipelineStage">{{ __('crm.current_pipeline_stage') }} <span class="req">*</span></label>
                        <select id="pipelineStage" name="pipeline_stage_id" required>
                            @php
                                $selectedStageId = old('pipeline_stage_id', $lead->status?->pipeline_stage_id ?? $stages->first()->id ?? '');
                            @endphp
                            @foreach ($stages as $stage)
                                <option value="{{ $stage->id }}" {{ (string) $selectedStageId === (string) $stage->id ? 'selected' : '' }}>
                                    {{ (app()->getLocale() === 'en' && !empty($stage->name_en)) ? $stage->name_en : $stage->name_ar }} {{ $stage->isPrimary() ? '(' . __('crm.stage_type_primary') . ')' : '(' . __('crm.stage_type_additional') . ')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="assignedUser">{{ __('crm.responding_responsible_employee') }}</label>
                        @if ($canAssignLead)
                            <select id="assignedUser" name="assigned_user_id">
                                @foreach ($assignableUsers as $u)
                                    <option value="{{ $u->id }}" {{ (string) old('assigned_user_id', $lead->assigned_user_id ?? auth()->id()) === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" value="{{ $assignedEmployee }}" disabled style="background:#f1f5f9">
                            <input type="hidden" name="assigned_user_id" value="{{ $lead->assigned_user_id }}">
                        @endif
                    </div>

                    <div class="field">
                        <label for="source">{{ __('crm.lead_source') }}</label>
                        <input type="text" id="source" name="source" value="{{ old('source', $lead->source) }}" placeholder="{{ __('crm.lead_source_placeholder') }}">
                    </div>

                    <div class="field">
                        <label for="contactDate">{{ __('crm.contact_date') }}</label>
                        <input type="date" id="contactDate" name="contact_date" value="{{ old('contact_date', $lead->contact_date ? $lead->contact_date->format('Y-m-d') : '') }}">
                    </div>

                    <div class="field two-span">
                        <label for="nextFollowUpAt">{{ __('crm.next_followup_date') }}</label>
                        <input type="datetime-local" id="nextFollowUpAt" name="next_follow_up_at" value="{{ old('next_follow_up_at', $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d\TH:i') : '') }}">
                    </div>

                    <div class="field full">
                        <label for="responseDetails">{{ __('crm.response_call_details') }}</label>
                        <textarea id="responseDetails" name="response_details" rows="3">{{ old('response_details', $lead->response_details ?? $lead->notes) }}</textarea>
                    </div>

                    @include('partials.lead-custom-field-inputs', ['fields' => $customFields->where('section', 'contact_followup')->values(), 'recordValues' => is_array($lead->custom_fields) ? $lead->custom_fields : []])
                </div>
            </section>

            @php $otherFields = $customFields->where('section', 'other')->values(); @endphp
            @if ($otherFields->isNotEmpty())
                <!-- SECTION 5: CONFIGURABLE ADDITIONAL FIELDS -->
                <section class="form-card">
                    <div class="section-head">
                        <div>
                            <h2><i class="bi bi-grid-3x3-gap"></i> {{ __('crm.lf_additional_section') }}</h2>
                            <p>{{ __('crm.lf_additional_section_desc') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        @include('partials.lead-custom-field-inputs', ['fields' => $otherFields, 'recordValues' => is_array($lead->custom_fields) ? $lead->custom_fields : []])
                    </div>
                </section>
            @endif

            <!-- SECTION 4: RELATED PEOPLE -->
            <section class="form-card">
                <div class="section-head">
                    <div>
                        <h2><i class="bi bi-people"></i> {{ __('crm.section_related_people') }}</h2>
                        <p>{{ __('crm.section_related_people_desc') }}</p>
                    </div>
                    <button type="button" class="btn small soft" id="addPersonBtn">
                        <i class="bi bi-plus-lg"></i> {{ __('crm.add_related_person') }}
                </div>

                <div id="relatedPeopleContainer">
                    @php
                        $currentPeople = old('related_people') !== null
                            ? old('related_people')
                            : $lead->relatedPeople->map(fn($p) => [
                                'name' => $p->name,
                                'phone' => $p->phone,
                                'relationship_type' => $p->relationship_type,
                                'notes' => $p->notes,
                            ])->toArray();
                    @endphp
                    @foreach ($currentPeople as $pIndex => $person)
                        <div class="person-row">
                            <input type="text" name="related_people[{{ $pIndex }}][name]" value="{{ $person['name'] ?? '' }}" placeholder="{{ __('crm.person_name_placeholder') }}" required>
                            <input type="tel" name="related_people[{{ $pIndex }}][phone]" value="{{ $person['phone'] ?? '' }}" placeholder="{{ __('crm.person_phone_placeholder') }}" dir="ltr">
                            <select name="related_people[{{ $pIndex }}[relationship_type]">
                                @foreach (\App\Support\CrmOptions::get('relation_type') as $relationType)
                                    <option value="{{ $relationType['value'] }}" @selected(($person['relationship_type'] ?? '') === $relationType['value'])>{{ \App\Support\CrmOptions::labelOf($relationType) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="related_people[{{ $pIndex }}][notes]" value="{{ $person['notes'] ?? '' }}" placeholder="{{ __('crm.note_optional_placeholder') }}">
                            <button type="button" class="btn small danger btn-remove" onclick="this.closest('.person-row').remove()" title="{{ __('crm.delete') }}">
                                <i class="bi bi-trash"></i>
                        </div>
                    @endforeach
                </div>
                <div id="noPeopleNotice" style="{{ count($currentPeople) > 0 ? 'display:none;' : '' }} text-align:center; padding:20px; color:var(--muted); background:var(--bg); border:1px dashed var(--line); border-radius:10px;">
                    <i class="bi bi-person-plus" style="font-size:24px; display:block; margin-bottom:6px;"></i>
                    <span>{{ __('crm.no_related_people_yet') }}</span>
                </div>
            </section>

            <!-- SUBMIT BAR -->
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <a href="{{ route('v2.leads.show', $lead) }}" class="btn soft">{{ __('crm.cancel') }}</a>
                <button type="submit" class="btn primary" style="min-width:180px; font-size:15px;">
                    <i class="bi bi-check2-circle"></i> {{ __('crm.save_changes') }}
            </div>
        </form>
    </main>
</div>

<script>
(() => {

    const PHONE_LABELS = @json(\App\Support\CrmOptions::get('phone_label'), JSON_UNESCAPED_UNICODE);
    const RELATION_TYPES = @json(\App\Support\CrmOptions::get('relation_type'), JSON_UNESCAPED_UNICODE);
    const OPTION_LABEL_LOCALE = @json(app()->getLocale());
    const optionLabel = (opt) => (OPTION_LABEL_LOCALE === 'en' && opt.label_en && String(opt.label_en).trim() !== '') ? String(opt.label_en).trim() : String(opt.label_ar);
    const escapeHtml = (text) => String(text ?? '').replace(/[&<>\"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const escapeAttr = escapeHtml;
    let phoneCounter = {{ count($currentPhones) }};
    const phoneContainer = document.getElementById('additionalPhonesContainer');
    const addPhoneBtn = document.getElementById('addPhoneBtn');

    addPhoneBtn?.addEventListener('click', () => {
        phoneCounter++;
        const row = document.createElement('div');
        row.className = 'repeater-row';
        row.innerHTML = `
            <input type="tel" name="additional_phones[${phoneCounter}][phone]" placeholder="@json(__('crm.additional_phone_placeholder'))" dir="ltr" required>
            <select name="additional_phones[${phoneCounter}][label]">
                ${PHONE_LABELS.map((opt, i) => `<option value="${escapeAttr(opt.value)}" ${i === 0 ? 'selected' : ''}>${escapeHtml(optionLabel(opt))}</option>`).join('')}
            </select>
            <button type="button" class="btn small danger" onclick="this.closest('.repeater-row').remove()" title="@json(__('crm.delete'))">
                <i class="bi bi-trash"></i>
            </button>
        `;
        phoneContainer.appendChild(row);
        row.querySelector('input').focus();
    });

    let personCounter = {{ count($currentPeople) }};
    const personContainer = document.getElementById('relatedPeopleContainer');
    const addPersonBtn = document.getElementById('addPersonBtn');
    const noPeopleNotice = document.getElementById('noPeopleNotice');

    addPersonBtn?.addEventListener('click', () => {
        personCounter++;
        if (noPeopleNotice) noPeopleNotice.style.display = 'none';

        const row = document.createElement('div');
        row.className = 'person-row';
        row.innerHTML = `
            <input type="text" name="related_people[${personCounter}][name]" placeholder="@json(__('crm.person_name_placeholder'))" required autofocus>
            <input type="tel" name="related_people[${personCounter}][phone]" placeholder="@json(__('crm.person_phone_placeholder'))" dir="ltr">
            <select name="related_people[${personCounter}][relationship_type]">
                ${RELATION_TYPES.map((opt, i) => `<option value="${escapeAttr(opt.value)}" ${i === 0 ? 'selected' : ''}>${escapeHtml(optionLabel(opt))}</option>`).join('')}
            </select>
            <input type="text" name="related_people[${personCounter}][notes]" placeholder="@json(__('crm.note_optional_placeholder'))">
            <button type="button" class="btn small danger btn-remove" onclick="removePersonRow(this)" title="@json(__('crm.delete'))">
                <i class="bi bi-trash"></i>
            </button>
        `;
        personContainer.appendChild(row);
    });
    window.removePersonRow = (btn) => {
        btn.closest('.person-row').remove();
        if (personContainer.children.length === 0 && noPeopleNotice) {
            noPeopleNotice.style.display = 'block';
        }
    };
})();
</script>
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
</body>
</html>

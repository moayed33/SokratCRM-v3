<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ $lead->name }}</title>
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
 --radius:16px;
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

.btn{
 display:inline-flex;align-items:center;justify-content:center;gap:7px;
 min-height:40px;padding:0 16px;border:1px solid var(--line);border-radius:10px;
 background:var(--card);color:var(--dark);font-weight:700;cursor:pointer;text-decoration:none;
 transition:.15s;font-size:13px;
}
.btn:hover{border-color:#cbd5e1;background:#f1f5f9}
.btn.primary{background:var(--red);border-color:var(--red);color:#fff;box-shadow:0 4px 14px rgba(220,38,55,.25)}
.btn.primary:hover{background:var(--red-hover);border-color:var(--red-hover)}
.btn.success{background:#16a34a;border-color:#16a34a;color:#fff}
.btn.success:hover{background:#15803d}
.btn.soft{background:#f1f5f9;border-color:transparent}
.btn.small{min-height:32px;padding:0 10px;font-size:12px}

.summary-card{
 background:linear-gradient(135deg, #ffffff 0%, #fdfdfd 100%);
 border:1px solid var(--line);border-radius:var(--radius);
 box-shadow:var(--shadow);padding:24px 28px;margin-bottom:24px;
}
.summary-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap}
.summary-lead-name{font-size:26px;font-weight:900;margin:0 0 8px}
.summary-badges{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

.summary-metrics{
 display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:22px;
 padding-top:20px;border-top:1px solid var(--line);
}
.metric-box{
 background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:14px 16px;
}
.metric-box span{display:block;font-size:12px;color:var(--muted);font-weight:700}
.metric-box b{display:block;font-size:17px;font-weight:900;margin-top:4px;color:var(--dark)}

.badge{
 display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;
 font-size:12px;font-weight:800;background:#eef2f7;color:#475569;
}

.details-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.panel{
 background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
 box-shadow:var(--shadow);padding:22px;margin-bottom:22px;
}
.panel-head{
 display:flex;align-items:center;justify-content:space-between;
 margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line);
}
.panel-head h2{margin:0;font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px}
.panel-head h2 i{color:var(--red);font-size:18px}

.info-list{display:grid;gap:12px}
.info-row{
 display:flex;justify-content:space-between;align-items:center;
 padding:9px 12px;border-radius:9px;background:var(--bg);border:1px solid rgba(0,0,0,.03);
}
.info-label{color:var(--muted);font-weight:700;font-size:13px}
.info-value{font-weight:800;color:var(--dark);font-size:13px}

.timeline{position:relative;padding-inline-start:24px;margin-top:10px}
.timeline::before{
 content:'';position:absolute;top:0;bottom:0;inset-inline-start:7px;
 width:2px;background:var(--line);
}
.timeline-item{position:relative;margin-bottom:20px}
.timeline-dot{
 position:absolute;inset-inline-start:-24px;top:4px;
 width:16px;height:16px;border-radius:50%;background:var(--red);border:3px solid #fff;
 box-shadow:0 0 0 2px var(--line);
}
.timeline-card{
 background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:14px 16px;
}
.timeline-head{
 display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;
}
.timeline-date{color:var(--muted);font-size:12px;font-weight:700}
.timeline-employee{font-weight:800;color:var(--dark);font-size:13px}
.timeline-body{color:#334155;font-size:13px;line-height:1.6}

@media(max-width:1024px){
 .details-grid{grid-template-columns:1fr}
 .summary-metrics{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
 .crm-main{padding:16px}
 .summary-metrics{grid-template-columns:1fr}
 .summary-top{flex-direction:column}
}
</style>
</head>
<body>
@include('partials.page-loader')
<div class="crm-app">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        <!-- TOP ACTIONS & BREADCRUMB -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <a href="{{ route('v2.leads', $backQuery) }}" class="btn soft small">
                <i class="bi bi-arrow-left rtl:rotate-180"></i> {{ __('crm.back_to_leads_arrow') }}
            </a>
            <div style="display:flex;gap:8px">
                @can('update', $lead)
                    <a href="{{ route('v2.leads.edit', $lead) }}" class="btn primary small">
                        <i class="bi bi-pencil-square"></i> {{ __('crm.edit_lead_details') }}
                    </a>
                @endcan
                @can('createFollowup', $lead)
                    <a href="{{ route('v2.leads.followups.index', $lead) }}" class="btn small" style="background:#2563eb; color:#fff; border-color:#2563eb;">
                        <i class="bi bi-telephone-outbound"></i> {{ __('crm.log_new_followup') }}
                    </a>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:10px; padding:14px 16px; margin-bottom:20px; font-weight:800;">
                {{ session('success') }}
            </div>
        @endif

        <!-- TOP CONCISE CUSTOMER SUMMARY (Section 20 Requirement) -->
        <section class="summary-card">
            <div class="summary-top">
                <div>
                    <h1 class="summary-lead-name">{{ $lead->name }}</h1>
                    <div class="summary-badges">
                        <!-- Stage Badge -->
                        @php
                            $stageColor = $lead->status?->stage?->color ?? $statusColor;
                        @endphp
                        <span class="badge" style="background:{{ $stageColor }}18; color:{{ $stageColor }}; border:1px solid {{ $stageColor }}40; font-size:13px; padding:6px 14px;">
                            <i class="bi bi-diagram-3-fill"></i>
                            {{ __('crm.stage') }}: <strong>{{ (app()->getLocale() === 'en' && !empty($lead->status?->stage?->name_en)) ? $lead->status?->stage?->name_en : ($lead->status?->stage?->name_ar ?? $lead->status?->name_ar ?? __('crm.unspecified_female')) }}</strong>
                        </span>

                        @if ($lead->status)
                            <span class="badge" style="font-size:13px">
                                {{ __('crm.status') }}: {{ $lead->status->localizedName() }}
                            </span>
                        @endif

                        @if ($lead->branch)
                            <span class="badge" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:13px; padding:6px 14px;">
                                <i class="bi bi-buildings"></i>
                                {{ __('crm.branch') }}: <strong>{{ (app()->getLocale() === 'en' && !empty($lead->branch->name_en)) ? $lead->branch->name_en : $lead->branch->name_ar }}</strong>
                            </span>
                        @endif

                        @if ($lead->donation_type)
                            <span class="badge" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:13px">
                                <i class="bi bi-tag-fill"></i> {{ $lead->donation_type }}
                            </span>
                        @endif
                </div>

                <!-- Quick Action Phone Buttons -->
                <div style="display:flex;gap:10px;align-items:center;">
                    @if ($callPhone)
                        <a href="tel:{{ $callPhone }}" class="btn soft" title="{{ __('crm.phone_call') }}">
                            <i class="bi bi-telephone-fill" style="color:#0284c7"></i>
                            <span>{{ $lead->phone }}</span>
                        </a>
                        <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener" class="btn success" title="{{ __('crm.whatsapp_chat') }}">
                            <i class="bi bi-whatsapp"></i> {{ __('crm.phone_type_whatsapp') }}
                        </a>
                    @else
                        <span class="btn soft" style="cursor:default">
                            <i class="bi bi-telephone"></i> {{ $lead->phone }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Summary 4 Metrics -->
            <div class="summary-metrics">
                <div class="metric-box">
                    <span>{{ __('crm.donation_value') }}</span>
                    <b>
                        @if ($lead->donation_value !== null)
                            {{ number_format((float) $lead->donation_value, 2) }} <small style="font-size:12px;font-weight:normal">{{ __('crm.currency_egp') }}</small>
                        @else
                            <span style="color:var(--muted);font-weight:normal">—</span>
                        @endif
                    </b>
                </div>

                <div class="metric-box">
                    <span>{{ __('crm.donation_cycle') }}</span>
                    <b>
                        {{ !empty($lead->donation_cycle) ? __('crm.donation_cycle_' . $lead->donation_cycle) : '—' }}
                    </b>
                </div>

                <div class="metric-box">
                    <span>{{ __('crm.responding_responsible_employee') }}</span>
                    <b>
                        {{ $lead->respondingUser?->name ?? $lead->assignedUser?->name ?? $lead->assigned_employee ?? __('crm.unassigned') }}
                    </b>
                </div>

                <div class="metric-box">
                    <span>{{ __('crm.next_followup') }}</span>
                    <b>
                        @if ($lead->next_follow_up_at)
                            @php
                                $isOverdue = $lead->next_follow_up_at->isPast();
                                $isToday = $lead->next_follow_up_at->isToday();
                            @endphp
                            <span style="color:{{ $isOverdue ? '#dc2637' : ($isToday ? '#d97706' : '#16a34a') }}">
                                {{ $lead->next_follow_up_at->format('Y-m-d H:i') }}
                                <small style="font-size:11px;display:block">
                                    {{ $isOverdue ? __('crm.status_overdue_tag') : ($isToday ? __('crm.status_today_tag') : __('crm.status_upcoming_tag')) }}
                                </small>
                            </span>
                        @else
                            <span style="color:var(--muted);font-weight:normal">{{ __('crm.no_scheduled_date_full') }}</span>
                        @endif
                    </b>
                </div>
            </div>
        </section>

        <!-- DETAILS GRID -->
        <div class="details-grid">
            <!-- LEFT COLUMN -->
            <div>
                <!-- CUSTOMER & DONATION INFO -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-info-circle"></i> {{ __('crm.lead_and_donation_data') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.lead_name_field') }}</span>
                            <span class="info-value">{{ $lead->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.primary_phone') }}</span>
                            <span class="info-value" dir="ltr">{{ $lead->phone }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_type') }}</span>
                            <span class="info-value">{{ $lead->donation_type ?? __('crm.unspecified') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_cycle') }}</span>
                            <span class="info-value">{{ !empty($lead->donation_cycle) ? __('crm.donation_cycle_' . $lead->donation_cycle) : __('crm.unspecified') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_value') }}</span>
                            <span class="info-value">
                                {{ $lead->donation_value !== null ? number_format((float) $lead->donation_value, 2) . ' ' . __('crm.currency_egp') : __('crm.unspecified_female') }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_purpose_label') }}</span>
                            <span class="info-value">{{ $lead->donation_purpose ?? __('crm.general_purpose') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.last_contact_date') }}</span>
                            <span class="info-value">{{ $lead->contact_date ? $lead->contact_date->format('Y-m-d') : '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.lead_source') }}</span>
                            <span class="info-value">{{ $lead->source ?? __('crm.direct_source') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.registration_date') }}</span>
                            <span class="info-value">{{ $lead->created_at ? $lead->created_at->format('Y-m-d H:i') : '—' }}</span>
                        </div>
                        @if ($lead->creator)
                            <div class="info-row">
                                <span class="info-label">{{ __('crm.registered_by') }}</span>
                                <span class="info-value">{{ $lead->creator->name }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- PHONE NUMBERS (Section 8 Requirement) -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-telephone"></i> {{ __('crm.lead_phone_numbers') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                            <div>
                                <span class="badge active" style="margin-inline-end:6px">{{ __('crm.primary_badge') }}</span>
                                <strong dir="ltr">{{ $lead->phone }}</strong>
                            </div>
                            <div style="display:flex;gap:6px">
                                @if ($callPhone)
                                    <a href="tel:{{ $callPhone }}" class="btn small soft" title="{{ __('crm.phone_call') }}">
                                        <i class="bi bi-telephone"></i>
                                    </a>
                                    <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener" class="btn small success" title="{{ __('crm.whatsapp_chat') }}">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @foreach ($lead->additionalPhones as $addPhone)
                            @php
                                $rawDigits = preg_replace('/\D+/', '', $addPhone->phone);
                            @endphp
                            <div class="info-row">
                                <div>
                                    <span class="badge" style="margin-inline-end:6px">{{ $addPhone->label ?: __('crm.phone_type_extra') }}</span>
                                    <span dir="ltr" style="font-weight:700">{{ $addPhone->phone }}</span>
                                </div>
                                <div style="display:flex;gap:6px">
                                    <a href="tel:{{ $rawDigits }}" class="btn small soft" title="{{ __('crm.phone_call') }}">
                                        <i class="bi bi-telephone"></i>
                                    </a>
                                    <a href="https://wa.me/{{ $rawDigits }}" target="_blank" rel="noopener" class="btn small success" title="{{ __('crm.whatsapp_chat') }}">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        @if ($lead->additionalPhones->isEmpty())
                            <small style="color:var(--muted); text-align:center; padding:8px 0; display:block">
                                {{ __('crm.no_extra_phones_registered') }}
                            </small>
                        @endif
                    </div>
                </section>

                <!-- RELATED PEOPLE (Section 17 Requirement) -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-people"></i> {{ __('crm.related_people_title') }}</h2>
                    </div>
                    @if ($lead->relatedPeople->isNotEmpty())
                        <div class="info-list">
                            @foreach ($lead->relatedPeople as $person)
                                <div class="info-row" style="align-items:flex-start; flex-direction:column; gap:6px;">
                                    <div style="display:flex; justify-content:space-between; width:100%; align-items:center;">
                                        <div>
                                            <strong>{{ $person->name }}</strong>
                                            <span class="badge" style="margin-inline-start:6px">{{ $person->relationship_type }}</span>
                                        </div>
                                        @if ($person->phone)
                                            <a href="tel:{{ preg_replace('/\D+/', '', $person->phone) }}" class="btn small soft" dir="ltr">
                                                <i class="bi bi-telephone"></i> {{ $person->phone }}
                                            </a>
                                        @endif
                                    </div>
                                    @if ($person->notes)
                                        <p style="margin:0; font-size:12px; color:var(--muted)">
                                            {{ $person->notes }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center; padding:16px 0; color:var(--muted);">
                            <i class="bi bi-person-x" style="font-size:22px; display:block; margin-bottom:4px"></i>
                            {{ __('crm.no_related_people_registered') }}
                        </div>
                    @endif
                </section>

                <!-- CONFIGURABLE ADDITIONAL DATA -->
                @php
                    $profileFields = \App\Support\LeadFieldSchema::customFields();
                    $leadCustomValues = is_array($lead->custom_fields) ? $lead->custom_fields : [];
                @endphp
                @if ($profileFields->isNotEmpty())
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-grid-3x3-gap"></i> {{ __('crm.lf_additional_data') }}</h2>
                        </div>
                        <div class="info-list">
                            @foreach ($profileFields as $profileField)
                                @php
                                    $profileValue = $leadCustomValues[$profileField->key] ?? null;
                                    $hasValue = ! ($profileValue === null || $profileValue === '' || $profileValue === []);
                                @endphp
                                <div class="info-row">
                                    <span class="info-label">
                                        {{ $profileField->label() }}@if ($profileField->is_required) <span style="color:#dc2637">*</span> @endif
                                    </span>
                                    <span class="info-value" style="max-width:60%;text-align:end">
                                        {{ $hasValue ? \App\Support\LeadFieldSchema::formatValue($profileField, $profileValue) : '—' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- LATEST RESPONSE DETAILS -->
                @if ($lead->response_details)
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-chat-quote"></i> {{ __('crm.latest_response_notes') }}</h2>
                        </div>
                        <div style="background:var(--bg); border:1px solid var(--line); border-radius:10px; padding:14px 16px; line-height:1.7; color:var(--dark); white-space:pre-line;">{{ $lead->response_details }}</div>
                    </section>
                @endif
            </div>

            <!-- RIGHT COLUMN: CUSTOMER TIMELINE (Section 21 Requirement) -->
            <div>
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2><i class="bi bi-clock-history"></i> {{ __('crm.lead_activity_timeline') }}</h2>
                            <small style="color:var(--muted)">{{ __('crm.timeline_subtitle') }}</small>
                        </div>
                        @can('createFollowup', $lead)
                            <a href="{{ route('v2.leads.followups.index', $lead) }}" class="btn primary small">
                                <i class="bi bi-plus-lg"></i> {{ __('crm.add_followup') }}
                            </a>
                        @endcan
                    </div>

                    @if ($timelineEvents->isNotEmpty())
                        <div class="timeline">
                            @foreach ($timelineEvents as $event)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-card">
                                        <div class="timeline-head">
                                            <div>
                                                <span class="timeline-employee">
                                                    <i class="bi bi-person"></i> {{ $event['employee'] }}
                                                </span>
                                                @if ($event['type'] === 'followup')
                                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; margin-inline-start:6px">
                                                        <i class="bi bi-telephone"></i> {{ __('crm.followup') }}
                                                    </span>
                                                @else
                                                    <span class="badge" style="background:#fef3c7; color:#92400e; margin-inline-start:6px">
                                                        <i class="bi bi-arrow-left-right"></i> {{ __('crm.status_change') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="timeline-date">
                                                {{ $event['timestamp'] ? $event['timestamp']->format('Y-m-d H:i') : '—' }}
                                            </span>
                                        </div>

                                        @if ($event['from_status'] && $event['to_status'] && $event['from_status'] !== $event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status_changed_to') }} <span style="color:#64748b">{{ $event['from_status'] }}</span> {{ app()->getLocale() === 'ar' ? '←' : '→' }} <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @elseif ($event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status') }}: <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @endif

                                        @if ($event['details'])
                                            <div class="timeline-body">
                                                {{ $event['details'] }}
                                            </div>
                                        @endif

                                        @if ($event['next_follow_up'])
                                            <div style="margin-top:10px; padding-top:8px; border-top:1px dashed var(--line); font-size:12px; color:var(--muted)">
                                                <i class="bi bi-calendar-event"></i> {{ __('crm.next_followup_at_label') }} <strong>{{ $event['next_follow_up']->format('Y-m-d H:i') }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center; padding:30px 20px; color:var(--muted); background:var(--bg); border-radius:12px;">
                            <i class="bi bi-chat-square-dots" style="font-size:30px; display:block; margin-bottom:8px"></i>
                            <p style="margin:0; font-weight:700">{{ __('crm.no_timeline_records_yet') }}</p>
                            @can('createFollowup', $lead)
                                <a href="{{ route('v2.leads.followups.index', $lead) }}" class="btn primary small" style="margin-top:12px;">
                                    {{ __('crm.log_first_followup_now') }}
                                </a>
                            @endcan
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </main>
</div>
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
</body>
</html>

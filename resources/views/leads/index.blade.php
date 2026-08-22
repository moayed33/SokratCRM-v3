<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.view_leads') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<style>
:root {
  --red: #dc2637;
  --red-hover: #b81829;
  --primary: #4f46e5;
  --primary-hover: #4338ca;
  --dark: #182033;
  --muted: #64748b;
  --line: #e2e8f0;
  --bg: #f8fafc;
  --card: #ffffff;
  --shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
  --radius: 16px;
  --font-primary: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

html.dark-mode {
  --dark: #f1f5f9;
  --muted: #94a3b8;
  --line: #334155;
  --bg: #0f172a;
  --card: #1e293b;
  --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

* { box-sizing: border-box; }
body {
  margin: 0;
  min-width: 320px;
  background: var(--bg);
  color: var(--dark);
  font-family: var(--font-primary);
  font-size: 14px;
  line-height: 1.5;
}
button, input, select, textarea { font: inherit; }
a { color: inherit; text-decoration: none; }

.crm-app { display: flex; min-height: 100vh; }
.crm-main { flex: 1; min-width: 0; padding: 24px 32px 60px; }

/* Topbar */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}
.topbar h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 900;
  color: var(--dark);
}
.topbar p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 13px;
}
.top-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 40px;
  padding: 0 16px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--card);
  color: var(--dark);
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
  font-size: 13px;
  white-space: nowrap;
}
.btn:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateY(-1px);
}
html.dark-mode .btn {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--line);
  color: var(--dark);
}
html.dark-mode .btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: #475569;
}
.btn.primary {
  background: var(--red);
  border-color: var(--red);
  color: #fff;
  box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25);
}
.btn.primary:hover {
  background: var(--red-hover);
  border-color: var(--red-hover);
  color: #fff;
}
.btn.soft {
  background: #f1f5f9;
  border-color: transparent;
  color: #334155;
}
html.dark-mode .btn.soft {
  background: rgba(255, 255, 255, 0.08);
  color: #f1f5f9;
}
.btn.soft:hover {
  background: #e2e8f0;
}
html.dark-mode .btn.soft:hover {
  background: rgba(255, 255, 255, 0.15);
}
.btn.small {
  height: 38px;
  padding: 0 14px;
  font-size: 13px;
  border-radius: 10px;
}

/* Stats Summary Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.stat-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  padding: 16px 18px;
  box-shadow: var(--shadow);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.stat-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
}
.stat-card span {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 700;
}
.stat-card b {
  display: block;
  font-size: 22px;
  font-weight: 900;
  margin-top: 6px;
  color: var(--dark);
}

/* Filters Panel: Compact Responsive CSS Grid */
.filter-panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  padding: 18px 20px;
  box-shadow: var(--shadow);
  margin-bottom: 24px;
}
.filter-form-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
  gap: 12px;
  align-items: end;
}
.filter-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.filter-field label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
  white-space: nowrap;
}
.filter-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}
.filter-input-icon {
  position: absolute;
  inset-inline-start: 12px;
  color: var(--muted);
  pointer-events: none;
  font-size: 14px;
}
.filter-control {
  width: 100%;
  height: 40px;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0 12px;
  background: var(--card);
  color: var(--dark);
  font-size: 13px;
  font-weight: 600;
  outline: none;
  transition: all 0.15s ease;
}
.filter-control.with-icon {
  padding-inline-start: 34px;
}
.filter-control:focus {
  border-color: var(--red);
  box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.15);
  background: var(--card);
}
html.dark-mode .filter-control {
  background: rgba(30, 41, 59, 0.7);
  border-color: var(--line);
  color: var(--dark);
}
html.dark-mode .filter-control:focus {
  border-color: rgba(239, 68, 68, 0.6);
  background: rgba(30, 41, 59, 0.95);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}
html.dark-mode .filter-control option {
  background: #1e293b;
  color: #f1f5f9;
}
.filter-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Leads Table Layout */
.table-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1100px;
}
th, td {
  text-align: start;
  padding: 12px 14px;
  border-bottom: 1px solid var(--line);
  vertical-align: middle;
}
th {
  background: #f8fafc;
  color: var(--muted);
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
  letter-spacing: 0.2px;
}
html.dark-mode th {
  background: rgba(255, 255, 255, 0.03);
  color: var(--muted);
}
tr:last-child td {
  border-bottom: none;
}
tr:hover td {
  background: #f8fafc;
}
html.dark-mode tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  background: #f1f5f9;
  color: #475569;
  white-space: nowrap;
}
html.dark-mode .badge {
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
}
.badge.active { background: #dcfce7; color: #166534; }
html.dark-mode .badge.active { background: rgba(22, 101, 52, 0.25); color: #86efac; }
.badge.overdue { background: #fee2e2; color: #991b1b; }
html.dark-mode .badge.overdue { background: rgba(153, 27, 27, 0.25); color: #fca5a5; }
.badge.today { background: #fef3c7; color: #92400e; }
html.dark-mode .badge.today { background: rgba(146, 64, 14, 0.25); color: #fcd34d; }
.badge.upcoming { background: #e0f2fe; color: #0369a1; }
html.dark-mode .badge.upcoming { background: rgba(3, 105, 161, 0.25); color: #7dd3fc; }

.badge-branch {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}
html.dark-mode .badge-branch {
  background: rgba(37, 99, 235, 0.15);
  color: #93c5fd;
  border-color: rgba(37, 99, 235, 0.3);
}

/* Table Cells */
.customer-name-cell strong {
  display: block;
  font-size: 14px;
  font-weight: 800;
  color: var(--dark);
}
.customer-name-cell a {
  text-decoration: none;
  color: inherit;
}
.customer-name-cell a:hover strong {
  color: var(--red);
}
.customer-name-cell small {
  color: var(--muted);
  font-size: 11px;
  margin-top: 3px;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Action Icons Cell */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f1f5f9;
  border: 1px solid var(--line);
  color: var(--dark);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-action:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
html.dark-mode .btn-action {
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--line);
  color: var(--dark);
}
html.dark-mode .btn-action:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: #475569;
}
.btn-action.call {
  color: #2563eb;
}
.btn-action.call:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}
html.dark-mode .btn-action.call:hover {
  background: rgba(37, 99, 235, 0.2);
  border-color: rgba(37, 99, 235, 0.4);
}

/* Pagination Wrap */
.pagination-wrap {
  padding: 16px 20px;
  border-top: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  background: var(--card);
}
.pagination-info {
  font-size: 13px;
  font-weight: 700;
  color: var(--muted);
}

/* Unified Clean Pagination Bar */
.crm-pagination-nav {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}
.crm-page-btn, .crm-page-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--card);
  color: var(--dark);
  font-weight: 700;
  font-size: 13px;
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
}
.crm-page-btn:hover:not(.disabled), .crm-page-num:hover:not(.active) {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateY(-1px);
}
html.dark-mode .crm-page-btn, html.dark-mode .crm-page-num {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--line);
  color: var(--dark);
}
html.dark-mode .crm-page-btn:hover:not(.disabled), html.dark-mode .crm-page-num:hover:not(.active) {
  background: rgba(255, 255, 255, 0.12);
  border-color: #475569;
}
.crm-page-num.active {
  background: var(--red) !important;
  border-color: var(--red) !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(220, 38, 55, 0.25);
}
.crm-page-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}
.crm-page-numbers {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.crm-page-dots {
  padding: 0 6px;
  color: var(--muted);
  font-weight: 700;
}

/* Explicit SVG Icon Constraints */
.pagination-wrap svg,
.crm-pagination-nav svg,
.pagination svg {
  width: 14px !important;
  height: 14px !important;
  max-width: 14px !important;
  max-height: 14px !important;
  display: inline-block !important;
  vertical-align: middle !important;
}

/* Flash alerts */
.flash {
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 20px;
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.flash.success {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}
html.dark-mode .flash.success {
  background: rgba(22, 101, 52, 0.2);
  color: #86efac;
  border-color: rgba(22, 101, 52, 0.4);
}

/* RTL Icon direction helper */
[dir="rtl"] .rtl-flip {
  transform: scaleX(-1);
}

/* Responsive Breakpoints */
@media(max-width: 1200px) {
  .stats-grid { grid-template-columns: repeat(3, 1fr); }
  .filter-form-grid { grid-template-columns: repeat(3, 1fr); }
  .filter-field.col-search { grid-column: span 3; }
}
@media(max-width: 900px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .filter-form-grid { grid-template-columns: repeat(2, 1fr); }
  .filter-field.col-search { grid-column: span 2; }
}
@media(max-width: 600px) {
  .crm-main { padding: 16px; }
  .stats-grid { grid-template-columns: 1fr; }
  .filter-form-grid { grid-template-columns: 1fr; }
  .filter-field.col-search { grid-column: span 1; }
  .pagination-wrap { flex-direction: column; align-items: center; text-align: center; }
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
                <div>
                    <h1>{{ __('crm.view_leads') }}</h1>
                    <p>{{ __('crm.leads_page_subtitle') }}</p>
                </div>
            </div>
            <div class="top-actions">
                @can('create', App\Models\Lead::class)
                    <a href="{{ route('v2.leads.create') }}" class="btn primary">
                        <i class="bi bi-plus-lg"></i> {{ __('crm.add_lead') }}
                    </a>
                @endcan
                <a href="{{ route('v2.leads.export') }}" class="btn soft">
                    <i class="bi bi-file-earmark-arrow-down"></i> {{ __('crm.export') }}
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        @if (session('success'))
            <div class="flash success">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        <!-- STAGES STATS CARDS -->
        <section class="stats-grid">
            <article class="stat-card">
                <span><i class="bi bi-people-fill"></i> {{ __('crm.total_leads') }}</span>
                <b>{{ number_format($totalLeads) }}</b>
            </article>
            @foreach ($stages as $stg)
                <article class="stat-card">
                    <span>
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $stg->color ?? '#3478f6' }};flex-shrink:0;"></span>
                        {{ (app()->getLocale() === 'en' && !empty($stg->name_en)) ? $stg->name_en : $stg->name_ar }}
                    </span>
                    <b>{{ number_format($stg->scoped_leads_count ?? 0) }}</b>
                </article>
            @endforeach
        </section>

        <!-- FILTERS PANEL (Compact CSS Grid) -->
        <section class="filter-panel">
            <form method="GET" action="{{ route('v2.leads') }}" class="filter-form-grid">
                <!-- Search Input: 2 Columns on large screens -->
                <div class="filter-field col-search">
                    <label for="searchQuery"><i class="bi bi-search"></i> {{ __('crm.search_query_label') }}</label>
                    <div class="filter-input-wrap">
                        <i class="bi bi-search filter-input-icon"></i>
                        <input type="text" id="searchQuery" name="q" value="{{ $filters['q'] }}" class="filter-control with-icon" placeholder="{{ __('crm.search_leads_placeholder') }}">
                    </div>
                </div>

                <!-- Stage Filter -->
                <div class="filter-field">
                    <label for="stageFilter"><i class="bi bi-diagram-3"></i> {{ __('crm.stage') }}</label>
                    <select id="stageFilter" name="stage" class="filter-control" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_stages') }}</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->id }}" {{ (string) $filters['stage'] === (string) $stage->id ? 'selected' : '' }}>
                                {{ (app()->getLocale() === 'en' && !empty($stage->name_en)) ? $stage->name_en : $stage->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Type Filter -->
                <div class="filter-field">
                    <label for="typeFilter"><i class="bi bi-cash-stack"></i> {{ __('crm.donation_type') }}</label>
                    <select id="typeFilter" name="donation_type" class="filter-control" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_types') }}</option>
                        @foreach ($donationTypes as $dType)
                            <option value="{{ $dType->name_ar }}" {{ $filters['donation_type'] === $dType->name_ar ? 'selected' : '' }}>
                                {{ (app()->getLocale() === 'en' && !empty($dType->name_en)) ? $dType->name_en : $dType->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Cycle Filter -->
                <div class="filter-field">
                    <label for="cycleFilter"><i class="bi bi-arrow-repeat"></i> {{ __('crm.donation_cycle') }}</label>
                    <select id="cycleFilter" name="donation_cycle" class="filter-control" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_cycles') }}</option>
                        @foreach ($donationCycles as $cKey => $cLabel)
                            <option value="{{ $cKey }}" {{ $filters['donation_cycle'] === $cKey ? 'selected' : '' }}>
                                {{ __('crm.donation_cycle_' . $cKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employee Filter -->
                <div class="filter-field">
                    <label for="employeeFilter"><i class="bi bi-person-check"></i> {{ __('crm.assigned_employee') }}</label>
                    <select id="employeeFilter" name="employee" class="filter-control" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_employees') }}</option>
                        @foreach ($employees as $empName)
                            <option value="{{ $empName }}" {{ $filters['employee'] === $empName ? 'selected' : '' }}>
                                {{ $empName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons: Apply & Reset -->
                <div class="filter-actions">
                    <button type="submit" class="btn primary small" title="{{ __('crm.apply_filter') }}">
                        <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                    </button>
                    @if ($activeQuery !== [])
                        <a href="{{ route('v2.leads') }}" class="btn soft small" title="{{ __('crm.reset_filters') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- CUSTOMERS TABLE -->
        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="min-width:220px">{{ __('crm.lead_name_or_donor') }}</th>
                            @if (auth()->user()->isSuperAdmin())
                                <th style="min-width:130px">{{ __('crm.branch') }}</th>
                            @endif
                            <th style="min-width:140px">{{ __('crm.primary_phone') }}</th>
                            <th style="min-width:130px">{{ __('crm.donation_type') }}</th>
                            <th style="min-width:120px">{{ __('crm.donation_value') }}</th>
                            <th style="min-width:130px">{{ __('crm.current_stage') }}</th>
                            <th style="min-width:140px">{{ __('crm.assigned_employee') }}</th>
                            <th style="min-width:110px">{{ __('crm.last_contact') }}</th>
                            <th style="min-width:130px">{{ __('crm.next_followup') }}</th>
                            <th style="min-width:120px">{{ __('crm.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <!-- 1. Customer Name -->
                                <td class="customer-name-cell">
                                    <a href="{{ route('v2.leads.show', $lead) }}">
                                        <strong>{{ $lead->name }}</strong>
                                    </a>
                                    @if ($lead->donation_purpose)
                                        <small><i class="bi bi-bullseye" style="color:var(--red)"></i> {{ $lead->donation_purpose }}</small>
                                    @elseif ($lead->company_name)
                                        <small><i class="bi bi-building"></i> {{ $lead->company_name }}</small>
                                    @endif
                                </td>

                                <!-- Branch for Super Admins -->
                                @if (auth()->user()->isSuperAdmin())
                                    <td>
                                        @if ($lead->branch)
                                            <span class="badge badge-branch">
                                                <i class="bi bi-buildings"></i> {{ $lead->branch->name_ar }}
                                            </span>
                                        @else
                                            <span style="color:var(--muted)">—</span>
                                        @endif
                                    </td>
                                @endif

                                <!-- 2. Primary Phone & Extra count -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px">
                                        <span dir="ltr" style="font-weight:700">{{ $lead->phone }}</span>
                                        @if ($lead->phones->count() > 1)
                                            <span class="badge" title="{{ __('crm.additional_phone_count', ['count' => $lead->phones->count() - 1]) }}">
                                                +{{ $lead->phones->count() - 1 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- 3. Donation Type -->
                                <td>
                                    @if ($lead->donation_type)
                                        <span class="badge active">
                                            {{ $lead->donation_type }}
                                            @if ($lead->donation_cycle)
                                                <span style="opacity:0.7">({{ __('crm.donation_cycle_' . $lead->donation_cycle) }})</span>
                                            @endif
                                        </span>
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>

                                <!-- 4. Donation Value -->
                                <td>
                                    @if ($lead->donation_value !== null)
                                        <strong style="color:var(--dark)">
                                            {{ number_format((float) $lead->donation_value, 2) }}
                                        </strong>
                                        <small style="font-size:11px;color:var(--muted)">{{ __('crm.currency_egp') }}</small>
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>

                                <!-- 5. Current Stage -->
                                <td>
                                    @php
                                        $stageColor = $lead->status?->stage?->color ?? '#3478f6';
                                    @endphp
                                    <span class="badge" style="background:{{ $stageColor }}18; color:{{ $stageColor }}; border:1px solid {{ $stageColor }}40">
                                        {{ (app()->getLocale() === 'en' && !empty($lead->status?->stage?->name_en)) ? $lead->status?->stage?->name_en : ($lead->status?->stage?->name_ar ?? $lead->status?->name_ar ?? '—') }}
                                    </span>
                                </td>

                                <!-- 6. Responding / Assigned Employee -->
                                <td>
                                    <span style="font-weight:700;color:var(--dark)">
                                        <i class="bi bi-person-fill" style="color:var(--muted);font-size:12px"></i>
                                        {{ $lead->respondingUser?->name ?? $lead->assignedUser?->name ?? $lead->assigned_employee ?? '—' }}
                                    </span>
                                </td>

                                <!-- 7. Last Contact -->
                                <td>
                                    @if ($lead->contact_date)
                                        <span style="font-size:13px">{{ $lead->contact_date->format('Y-m-d') }}</span>
                                    @else
                                        <span style="color:var(--muted)">{{ $lead->created_at ? $lead->created_at->format('Y-m-d') : '—' }}</span>
                                    @endif
                                </td>

                                <!-- 8. Next Follow-up -->
                                <td>
                                    @if ($lead->next_follow_up_at)
                                        @php
                                            $isOverdue = $lead->next_follow_up_at->isPast();
                                            $isToday = $lead->next_follow_up_at->isToday();
                                            $statusClass = $isOverdue ? 'overdue' : ($isToday ? 'today' : 'upcoming');
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            <i class="bi {{ $isOverdue ? 'bi-exclamation-circle' : ($isToday ? 'bi-clock' : 'bi-calendar-event') }}"></i>
                                            {{ $lead->next_follow_up_at->format('m-d H:i') }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted);font-size:12px">{{ __('crm.no_scheduled_date') }}</span>
                                    @endif
                                </td>

                                <!-- 9. Actions -->
                                <td>
                                    <div class="actions-cell">
                                        <a href="{{ route('v2.leads.show', $lead) }}" class="btn-action" title="{{ __('crm.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('update', $lead)
                                            <a href="{{ route('v2.leads.edit', $lead) }}" class="btn-action" title="{{ __('crm.edit') }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        @endcan
                                        @can('createFollowup', $lead)
                                            <a href="{{ route('v2.leads.followups.index', $lead) }}" class="btn-action call" title="{{ __('crm.log_followup') }}">
                                                <i class="bi bi-telephone-outbound"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isSuperAdmin() ? 10 : 9 }}" style="text-align:center; padding:48px 20px; color:var(--muted)">
                                    <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;"></i>
                                    <strong style="font-size:15px; display:block; margin-bottom:4px; color:var(--dark)">{{ __('crm.no_leads_data') }}</strong>
                                    <span>{{ __('crm.no_leads_matching_filter') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pagination-wrap">
                <div class="pagination-info">
                    {{ __('crm.showing_items_of_total', ['from' => $leads->firstItem() ?? 0, 'to' => $leads->lastItem() ?? 0, 'total' => number_format($leads->total())]) }}
                </div>
                <div>
                    {{ $leads->links('partials.pagination') }}
                </div>
            </div>
        </section>
    </main>
</div>
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
</body>
</html>

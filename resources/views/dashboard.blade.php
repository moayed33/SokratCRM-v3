<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SokratCRM — {{ __('crm.dashboard') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
<style>
:root {
    --red: #dc2637;
    --red-hover: #b81829;
    --dark: #182033;
    --text: #475569;
    --muted: #64748b;
    --line: #e2e8f0;
    --bg: #f8fafc;
    --card: #ffffff;
    --shadow: 0 6px 18px rgba(15, 23, 42, 0.035);
    --radius: 14px;
}
html.dark-mode {
    --dark: #f1f5f9;
    --text: #cbd5e1;
    --muted: #94a3b8;
    --line: #334155;
    --bg: #0f172a;
    --card: #1e293b;
    --shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
}
html.crm-monochrome {
    --red: #171717;
    --red-hover: #000000;
    --dark: #171717;
    --text: #525252;
    --muted: #737373;
    --line: #e5e5e5;
    --bg: #fafafa;
    --card: #ffffff;
    --shadow: none;
    --radius: 5px;
}
html.crm-monochrome.dark-mode {
    --dark: #f5f5f5;
    --text: #d4d4d4;
    --muted: #a3a3a3;
    --line: #404040;
    --bg: #111111;
    --card: #1c1c1c;
    --shadow: none;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    min-width: 320px;
    background: var(--bg);
    color: var(--dark);
    font-family: var(--font-primary);
    font-size: 14px;
}
button, input, select { font: inherit; }
a { color: inherit; }
.app { display: flex; min-height: 100vh; }
.main { flex: 1; min-width: 0; padding: 22px 32px 56px; }

/* Topbar */
.topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.topbar h1 { margin: 0; font-size: 23px; font-weight: 800; letter-spacing: -.015em; }
.topbar p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
.topbar-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.dashboard-search {
    flex: 1 1 260px; max-width: 360px; min-width: 200px; position: relative;
}
.dashboard-search i {
    position: absolute; inset-inline-start: 11px; top: 50%; transform: translateY(-50%);
    color: #a3a3a3; font-size: 13px; pointer-events: none;
}
.dashboard-search input {
    width: 100%; height: 42px; padding: 0 34px; border: 1px solid var(--line); border-radius: 4px;
    background: #fff; color: var(--dark); outline: none; font-size: 12px;
}
.dashboard-search input:focus { border-color: #737373; box-shadow: 0 0 0 2px rgba(23, 23, 23, .08); }
html.dark-mode .dashboard-search input { background: var(--card); color: var(--dark); }
.btn-leads-pill {
    display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 16px;
    border-radius: 4px; border: 1px solid var(--line); background: var(--card);
    color: var(--dark); font-size: 13px; font-weight: 700; text-decoration: none;
    cursor: pointer; transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.btn-leads-pill i { font-size: 15px; color: var(--red); }
.btn-leads-pill:hover, .btn-leads-pill:focus-visible {
    background: #fff5f6; border-color: rgba(220, 38, 55, 0.4); color: var(--red);
}
html.crm-monochrome .btn-leads-pill:hover,
html.crm-monochrome .btn-leads-pill:focus-visible { background: #f5f5f5; border-color: #a3a3a3; color: #171717; }
html.dark-mode .btn-leads-pill {
    background: var(--bg-card, rgba(24, 24, 27, .75)) !important;
    border-color: var(--line, rgba(255, 255, 255, .1)) !important;
    color: var(--text-primary, #f4f4f5) !important;
    box-shadow: var(--shadow-glass) !important;
}
html.dark-mode .btn-leads-pill:hover, html.dark-mode .btn-leads-pill:focus-visible {
    background: rgba(239, 68, 68, .18) !important;
    border-color: rgba(239, 68, 68, .45) !important;
    color: #f87171 !important;
}
html.crm-monochrome.dark-mode .btn-leads-pill:hover,
html.crm-monochrome.dark-mode .btn-leads-pill:focus-visible { background: #292929 !important; border-color: #737373 !important; color: #f5f5f5 !important; }
@media (max-width: 768px) {
    .topbar { flex-direction: column; align-items: stretch; gap: 14px; }
    .dashboard-search { max-width: none; min-width: 0; flex-basis: auto; }
    .topbar-actions { width: 100%; justify-content: flex-start; gap: 8px; }
}
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    min-height: 40px; padding: 0 16px; border: 1px solid var(--line); border-radius: 4px;
    background: var(--card); color: var(--dark); font-weight: 700; cursor: pointer; text-decoration: none;
    transition: background .15s ease, border-color .15s ease, color .15s ease; font-size: 13px;
}
.topbar .btn { min-height: 42px; }
.btn:hover { border-color: #cbd5e1; background: #f1f5f9; }
.btn.primary { background: var(--red); border-color: var(--red); color: #fff; }
.btn.primary:hover { background: var(--red-hover); border-color: var(--red-hover); }
.btn.soft { background: #f1f5f9; border-color: transparent; }
.btn.small { min-height: 32px; padding: 0 10px; font-size: 12px; }

/* Filters Panel */
.filters-panel {
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
    margin-bottom: 18px; overflow: hidden;
}
.filters-summary {
    min-height: 50px; padding: 0 16px; display: flex; align-items: center; gap: 9px;
    color: var(--dark); font-size: 13px; font-weight: 700; cursor: pointer; list-style: none;
}
.filters-summary::-webkit-details-marker { display: none; }
.filters-summary i { color: var(--muted); }
.filters-summary .summary-chevron { margin-inline-start: auto; transition: transform .18s ease; }
.filters-panel[open] .summary-chevron { transform: rotate(180deg); }
.active-filter-count {
    min-width: 22px; height: 22px; display: inline-grid; place-items: center; padding: 0 6px;
    border-radius: 999px; background: #fff1f3; color: var(--red); font-size: 11px;
}
html.crm-monochrome .active-filter-count { background: #ededed; color: #262626; }
.filters-form {
    display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) auto; gap: 12px; align-items: end;
    padding: 14px 16px 16px; border-top: 1px solid var(--line);
}
.filter-group label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 6px; }
.filter-group input, .filter-group select {
    width: 100%; min-height: 38px; border: 1px solid var(--line); border-radius: 4px; padding: 7px 10px;
    background: var(--card); color: var(--dark); outline: none; transition: .15s; font-size: 13px;
}
.filter-group input:focus, .filter-group select:focus {
    border-color: var(--red); box-shadow: 0 0 0 2px rgba(220, 38, 55, .08);
}
html.crm-monochrome .filter-group input:focus,
html.crm-monochrome .filter-group select:focus { border-color: #737373; box-shadow: 0 0 0 2px rgba(23, 23, 23, .08); }

/* KPI Summary Cards Grid (Section 6 & 7) */
.kpi-grid {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px;
}
.kpi-card {
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
    padding: 14px 15px; display: flex; align-items: center; justify-content: space-between;
    text-decoration: none; color: inherit; transition: border-color .15s ease, background .15s ease; min-width: 0;
}
.kpi-card:hover { border-color: color-mix(in srgb, var(--card-accent, #94a3b8) 40%, var(--line)); background: color-mix(in srgb, var(--card-accent, #94a3b8) 3%, var(--card)); }
.kpi-info span { display: block; font-size: 11px; color: var(--muted); font-weight: 650; }
.kpi-info b { display: block; font-size: 19px; font-weight: 800; margin-top: 4px; color: var(--dark); font-variant-numeric: tabular-nums; }
.kpi-icon-box {
    width: 34px; height: 34px; border-radius: 4px; display: grid; place-items: center; font-size: 15px; flex-shrink: 0;
    margin-inline-start: 8px;
}
html.crm-monochrome .kpi-card { --card-accent: #525252 !important; }
html.crm-monochrome .kpi-card .kpi-icon-box { background: #f5f5f5 !important; color: #404040 !important; }
html.crm-monochrome .kpi-card .kpi-info b { color: #171717 !important; }
html.crm-monochrome.dark-mode .kpi-card .kpi-icon-box { background: #292929 !important; color: #d4d4d4 !important; }
html.crm-monochrome.dark-mode .kpi-card .kpi-info b { color: #f5f5f5 !important; }

/* Dynamic Pipeline Stages Section (Section 5 & 8) */
.stages-panel {
    background: transparent; border: 1px solid var(--line); border-radius: var(--radius);
    padding: 20px; margin-bottom: 18px;
}
.panel-head {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; background: transparent;
}
.panel-head h2 { margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.panel-head h2 i { color: var(--muted); font-size: 15px; }
.panel-head p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }

.stages-grid {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px;
}
.stage-summary-box {
    position: relative; padding: 15px; border-radius: 4px; border: 1px solid var(--line);
    background: var(--card); transition: border-color .15s ease, background .15s ease; text-decoration: none; color: inherit; display: block;
}
html.crm-monochrome .stage-summary-box { --stage-color: #525252 !important; }
html.crm-monochrome .stage-summary-box .stage-box-title > span:first-child { border-radius: 3px !important; background: #f5f5f5 !important; color: #525252 !important; }
html.crm-monochrome.dark-mode .stage-summary-box .stage-box-title > span:first-child { background: #292929 !important; color: #d4d4d4 !important; }
.stage-summary-box:hover { border-color: color-mix(in srgb, var(--stage-color, #3478f6) 45%, var(--line)); background: color-mix(in srgb, var(--stage-color, #3478f6) 3%, var(--card)); }
.stage-box-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
.stage-box-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 750; }
.stage-box-count { font-size: 20px; font-weight: 800; color: var(--dark); font-variant-numeric: tabular-nums; }
.stage-box-percentage { font-size: 11px; font-weight: 600; color: var(--muted); display: block; }
.stage-statuses-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.stage-status-chip {
    display: inline-flex; align-items: center; justify-content: space-between; gap: 6px;
    padding: 4px 9px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--line);
    font-size: 11px; font-weight: 700; color: #475569;
}
.stage-status-chip b { color: var(--stage-color, #3478f6); }

/* Charts Layout (Section 11) */
.charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr);
    gap: 14px;
    margin-bottom: 18px;
    width: 100%;
    max-width: 100%;
}
.chart-card {
    background: transparent;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 20px;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}
.chart-wrap-responsive {
    position: relative;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    height: 250px;
    min-height: 250px;
}

/* Modern Fluid Collection Status Card */
.collection-status-fluid-body {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.4fr);
    gap: 16px;
    align-items: center;
    padding-top: 6px;
}
.collection-donut-wrap {
    position: relative;
    height: 220px !important;
    min-height: 220px !important;
    display: grid;
    place-items: center;
}
.collection-donut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    text-align: center;
}
.collection-donut-center .center-total {
    font-size: 22px;
    font-weight: 900;
    line-height: 1;
    color: var(--dark);
    font-family: var(--font-primary);
}
.collection-donut-center .center-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--muted);
    margin-top: 3px;
}
.status-bars-list {
    display: grid;
    gap: 8px;
}
.status-bar-row {
    padding: 7px 9px;
    border-radius: 8px;
    background: color-mix(in srgb, var(--bg) 60%, var(--card));
    border: 1px solid var(--line);
    transition: transform 0.15s, background-color 0.15s;
}
.status-bar-row:hover {
    background: var(--card);
    transform: translateX(-2px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
}
[dir="ltr"] .status-bar-row:hover {
    transform: translateX(2px);
}
.status-bar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 5px;
    font-size: 11px;
}
.status-bar-title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.status-color-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex: 0 0 8px;
}
.status-bar-numbers {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 800;
}
.status-bar-count {
    color: var(--dark);
}
.status-bar-pct {
    color: var(--muted);
    font-size: 10px;
}
.status-progress-track {
    width: 100%;
    height: 5px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--line) 80%, transparent);
    overflow: hidden;
}
.status-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
@media (max-width: 992px) {
    .collection-status-fluid-body {
        grid-template-columns: 1fr;
    }
}

/* Bottom Grid */
.bottom-grid {
    display: grid; grid-template-columns: 2fr 1fr; gap: 14px;
}
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 600px; }
th, td { text-align: start; padding: 11px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
th { background: color-mix(in srgb, var(--bg) 65%, var(--card)); color: var(--muted); font-size: 11px; font-weight: 750; }
tr:hover td { background: color-mix(in srgb, var(--bg) 50%, var(--card)); }

.badge {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569;
}

.quick-actions-list { display: grid; gap: 10px; }
.quick-action-link {
    display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 4px;
    background: transparent; border: 1px solid transparent; text-decoration: none; font-weight: 700;
    transition: background .15s ease, border-color .15s ease, color .15s ease; font-size: 13px;
}
.quick-action-link:hover { background: #fff7f8; border-color: #fecdd3; color: var(--red); }
html.crm-monochrome .quick-action-link:hover { background: #f5f5f5; border-color: #d4d4d4; color: #171717; }
html.crm-monochrome.dark-mode .quick-action-link:hover { background: #292929; border-color: #525252; color: #f5f5f5; }
.quick-action-link i { font-size: 15px; }
html.crm-monochrome .quick-action-link i { color: #525252 !important; }
html.crm-monochrome .stages-panel .badge,
html.crm-monochrome .bottom-grid .badge { background: #f5f5f5 !important; color: #525252 !important; border-color: #e5e5e5 !important; }

:focus-visible { outline: 3px solid rgba(220, 38, 55, .18); outline-offset: 2px; }
::selection { background: rgba(220, 38, 55, .16); color: var(--dark); }
html.crm-monochrome :focus-visible { outline-color: rgba(23, 23, 23, .16); }
html.crm-monochrome ::selection { background: rgba(23, 23, 23, .16); }

@media (max-width: 1250px) {
    .filters-form { grid-template-columns: repeat(3, 1fr); }
    .bottom-grid { grid-template-columns: 1fr; }
}
@media (max-width: 992px) {
    .charts-grid { grid-template-columns: 1fr; }
}
@media (max-width: 1100px) {
    .stages-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
    .stages-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .filters-form { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .main { padding: 16px; }
    .topbar-actions > a { flex: 1; }
    .stages-grid { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: 1fr; }
    .chart-card, .stages-panel { padding: 16px; }
    .panel-head { align-items: flex-start; gap: 10px; }
    .kanban-popup { width: 100vw; height: 100dvh; border: 0; border-radius: 0; }
}
</style>
</head>
<body>
@include('partials.page-loader')
<div class="app">
    @include('partials.crm-sidebar')

    <main class="main">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <h1>{{ __('crm.dashboard') }}</h1>
                </div>
            </div>
            @can('leads.view')
                <form class="dashboard-search" method="GET" action="{{ route('v2.leads') }}" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input name="q" type="search" placeholder="{{ __('crm.search') }}" aria-label="{{ __('crm.search') }}">
                </form>
            @endcan
            <div class="topbar-actions">
                @can('leads.create')
                    <a class="btn primary" href="{{ route('v2.leads.create') }}">
                        <i class="bi bi-plus-lg"></i> {{ __('crm.add_lead') }}
                    </a>
                @endcan
                @can('leads.view')
                    <a href="{{ route('v2.leads') }}" class="btn-leads-pill" title="{{ __('crm.all_leads') }}">
                        <i class="bi bi-people"></i>
                        <span>{{ __('crm.all_leads') }}</span>
                    </a>
                @endcan
                @include('partials.profile-dropdown')
            </div>
        </header>

        @php
            $activeDashboardFilters = collect([
                $filters['employee'] ?? '',
                $filters['status'] ?? '',
                $filters['donation_type'] ?? '',
                $filters['donation_cycle'] ?? '',
                ($filters['period'] ?? 'all') !== 'all' ? $filters['period'] : '',
            ])->filter(static fn ($value) => $value !== '')->count();
        @endphp
        <details class="filters-panel" @if($activeDashboardFilters > 0) open @endif>
            <summary class="filters-summary">
                <i class="bi bi-sliders2" aria-hidden="true"></i>
                <span>{{ __('crm.filter') }}</span>
                @if($activeDashboardFilters > 0)
                    <span class="active-filter-count">{{ $activeDashboardFilters }}</span>
                @endif
                <i class="bi bi-chevron-down summary-chevron" aria-hidden="true"></i>
            </summary>
            <form method="GET" action="{{ route('dashboard') }}" class="filters-form">
                <!-- Employee Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.assigned_employee') }}</label>
                    <select name="employee" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_employees') }}</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp }}" {{ $filters['employee'] === $emp ? 'selected' : '' }}>{{ $emp }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter (Dynamic from DB) -->
                <div class="filter-group">
                    <label>{{ __('crm.lead_status') }}</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_statuses') }}</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->code }}" {{ ($filters['status'] ?? '') === $st->code || (string) ($filters['status'] ?? '') === (string) $st->id ? 'selected' : '' }}>
                                {{ $st->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Type Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.donation_type') }}</label>
                    <select name="donation_type" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_types') }}</option>
                        @foreach ($donationTypes as $dType)
                            <option value="{{ $dType->name_ar }}" {{ $filters['donation_type'] === $dType->name_ar ? 'selected' : '' }}>
                                {{ (app()->getLocale() === 'en' && !empty($dType->name_en)) ? $dType->name_en : $dType->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Cycle Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.donation_cycle') }}</label>
                    <select name="donation_cycle" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_cycles') }}</option>
                        @foreach ($donationCycles as $cKey => $cLabel)
                            <option value="{{ $cKey }}" {{ $filters['donation_cycle'] === $cKey ? 'selected' : '' }}>
                                {{ __('crm.donation_cycle_' . $cKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Period Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.period') }}</label>
                    <select name="period" onchange="this.form.submit()">
                        <option value="all" {{ $filters['period'] === 'all' ? 'selected' : '' }}>{{ __('crm.all_periods') }}</option>
                        <option value="today" {{ $filters['period'] === 'today' ? 'selected' : '' }}>{{ __('crm.today') }}</option>
                        <option value="week" {{ $filters['period'] === 'week' ? 'selected' : '' }}>{{ __('crm.this_week') }}</option>
                        <option value="month" {{ $filters['period'] === 'month' ? 'selected' : '' }}>{{ __('crm.this_month') }}</option>
                        <option value="year" {{ $filters['period'] === 'year' ? 'selected' : '' }}>{{ __('crm.this_year') }}</option>
                    </select>
                </div>

                <!-- Submit / Reset Actions -->
                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn primary small" style="min-height:38px">
                        {{ __('crm.filter') }}
                    </button>
                    @if (($filters['employee'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['stage'] ?? '') !== '' || ($filters['donation_type'] ?? '') !== '' || ($filters['donation_cycle'] ?? '') !== '' || ($filters['period'] ?? 'all') !== 'all')
                        <a href="{{ route('dashboard') }}" class="btn soft small" style="min-height:38px" title="{{ __('crm.reset') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </details>

        <!-- 3. KPI METRICS GRID (Sections 6, 7 & 23) -->
        <section class="kpi-grid">
            <!-- Total Customers -->
            @can('leads.view')
            <a href="{{ route('v2.leads') }}" class="kpi-card" style="--card-accent: #3478f6;">
            @else
            <div class="kpi-card" style="cursor:default; --card-accent: #3478f6;">
            @endcan
                <div class="kpi-info">
                    <span>{{ __('crm.total_leads') }}</span>
                    <b>{{ number_format($totalCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(52,120,246,0.1); color:#3478f6;">
                    <i class="bi bi-people-fill"></i>
                </div>
            @can('leads.view')
            </a>
            @else
            </div>
            @endcan

            <!-- New Stage Customers -->
            @can('leads.view')
            <a href="{{ route('v2.leads', ['stage' => $stages->firstWhere('code', 'new')?->id ?? 'new']) }}" class="kpi-card" style="--card-accent: #0284c7;">
            @else
            <div class="kpi-card" style="cursor:default; --card-accent: #0284c7;">
            @endcan
                <div class="kpi-info">
                    <span>{{ __('crm.status_new') }}</span>
                    <b>{{ number_format($newCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(56,189,248,0.12); color:#0284c7;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
            @can('leads.view')
            </a>
            @else
            </div>
            @endcan

            <!-- Confirmed Donors -->
            @can('leads.view')
            <a href="{{ route('v2.leads', ['stage' => $stages->firstWhere('code', 'donor')?->id ?? 'donor']) }}" class="kpi-card" style="--card-accent: #16a34a;">
            @else
            <div class="kpi-card" style="cursor:default; --card-accent: #16a34a;">
            @endcan
                <div class="kpi-info">
                    <span>{{ __('crm.donor') }}</span>
                    <b>{{ number_format($donorCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(22,163,74,0.12); color:#16a34a;">
                    <i class="bi bi-heart-fill"></i>
                </div>
            @can('leads.view')
            </a>
            @else
            </div>
            @endcan

            <!-- Donor Conversion Rate (Section 7) -->
            <div class="kpi-card" style="cursor:default; --card-accent: #16a34a;">
                <div class="kpi-info">
                    <span>{{ __('crm.conversion_rate_to_donor') }}</span>
                    <b style="color:#16a34a">{{ $donorConversionRate }}%</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(22,163,74,0.1); color:#16a34a;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>

            <!-- Total Donation Value -->
            <div class="kpi-card" style="cursor:default; --card-accent: #d97706;">
                <div class="kpi-info">
                    <span>{{ __('crm.total_donations_value') }}</span>
                    <b style="font-size:17px">{{ number_format($totalDonationValue, 2) }} <small style="font-size:11px; font-weight:normal">{{ __('crm.currency_egp') }}</small></b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(245,158,11,0.1); color:#d97706;">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>

            <!-- Today Follow-ups -->
            @can('tasks.view')
            <a href="{{ route('v2.tasks.daily', ['scope' => 'today']) }}" class="kpi-card" style="--card-accent: #d97706;">
            @else
            <div class="kpi-card" style="cursor:default; --card-accent: #d97706;">
            @endcan
                <div class="kpi-info">
                    <span>{{ __('crm.today_followups') }}</span>
                    <b style="color:{{ $followupCounts['today'] > 0 ? '#d97706' : 'inherit' }}">{{ number_format($followupCounts['today']) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(245,158,11,0.1); color:#d97706;">
                    <i class="bi bi-telephone-inbound-fill"></i>
                </div>
            @can('tasks.view')
            </a>
            @else
            </div>
            @endcan
        </section>

        <!-- 4. DYNAMIC PIPELINE STATUSES SUMMARY -->
        <section class="stages-panel">
            <div class="panel-head">
                <div>
                    <h2><i class="bi bi-diagram-3"></i> {{ __('crm.donor_journey_pipeline_title') }}</h2>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:12px; padding:6px 12px;">
                        {{ $pipelineStages->count() }} {{ __('crm.active_stages') }}
                    </span>
                    @can('leads.view')
                    <a href="{{ route('v2.leads.kanban') }}" class="btn primary" style="font-size:14px; padding:8px 18px; min-height:40px; font-weight:800; display:inline-flex; align-items:center; gap:8px; box-shadow: 0 2px 10px rgba(220,38,55,0.22); text-decoration:none;" title="{{ __('crm.view_interactive_board') }}">
                        <i class="bi bi-kanban" style="font-size:16px;"></i>
                        <span>{{ __('crm.kanban_board_title') ?? __('crm.kanban') }}</span>
                    </a>
                    @endcan
                </div>
            </div>

            <div class="stages-grid">
                @foreach ($pipelineStages as $stage)
                    @can('leads.view')
                    <a href="{{ route('v2.leads', ['stage' => $stage->id]) }}" class="stage-summary-box" style="--stage-color: {{ $stage->color ?: '#3478f6' }};">
                    @else
                    <div class="stage-summary-box" style="cursor:default; --stage-color: {{ $stage->color ?: '#3478f6' }};">
                    @endcan
                        <div class="stage-box-top">
                            <div class="stage-box-title">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:6px; background:{{ $stage->color ?: '#3478f6' }}18; color:{{ $stage->color ?: '#3478f6' }}; font-size:13px;">
                                    <i class="bi {{ $stage->icon ? (str_starts_with($stage->icon, 'bi-') ? $stage->icon : 'bi-' . $stage->icon) : 'bi-app-indicator' }}"></i>
                                </span>
                                <span>{{ $stage->localizedName() }}</span>
                            </div>
                            <span class="stage-box-count">{{ number_format($stage->leads_count) }}</span>
                        </div>

                        <span class="stage-box-percentage">
                            {{ $stage->percentage }}% {{ __('crm.of_total_leads') }}
                        </span>
                    @can('leads.view')
                    </a>
                    @else
                    </div>
                    @endcan
                @endforeach
            </div>
        </section>

        <!-- 5. CHARTS ROW (Section 11) -->
        <section class="charts-grid">
            <!-- Chart 2: Activity Over Time -->
            <article class="chart-card">
                <div class="panel-head" style="margin-bottom:12px; padding-bottom:10px;">
                    <div>
                        <h2><i class="bi bi-activity"></i> {{ __('crm.activity_trend_title') }}</h2>
                        <p>{{ __('crm.activity_trend_desc') }}</p>
                    </div>
                </div>
                <div class="chart-wrap-responsive">
                    <canvas id="activityTrendChart" role="img" aria-label="{{ __('crm.activity_trend_title') }}"></canvas>
                </div>
            </article>

            <!-- Chart 1: Customer Stage Distribution (Donut Chart) -->
            <article class="chart-card">
                <div class="panel-head" style="margin-bottom:12px; padding-bottom:10px;">
                    <div>
                        <h2><i class="bi bi-pie-chart"></i> {{ __('crm.stage_distribution_title') }}</h2>
                        <p>{{ __('crm.stage_distribution_desc') }}</p>
                    </div>
                </div>
                <div class="chart-wrap-responsive">
                    <canvas id="stageDonutChart" role="img" aria-label="{{ __('crm.stage_distribution_title') }}"></canvas>
                </div>
            </article>
        </section>

        <!-- 6. BOTTOM ROW: LATEST FOLLOW-UPS & QUICK SHORTCUTS -->
        <section class="bottom-grid">
            <article class="chart-card">
                <div class="panel-head">
                    <div>
                        <h2><i class="bi bi-clock-history"></i> {{ __('crm.latest_followups_calls') }}</h2>
                        <p>{{ __('crm.latest_activities_desc') }}</p>
                    </div>
                    @can('tasks.view')
                    <a href="{{ route('v2.tasks.daily') }}" class="btn small soft">
                        {{ __('crm.view_all') }}
                    </a>
                    @endcan
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.client') }}</th>
                                <th>{{ __('crm.followup_type') }}</th>
                                <th>{{ __('crm.employee') }}</th>
                                <th>{{ __('crm.datetime') }}</th>
                                <th>{{ __('crm.stage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestFollowups as $followup)
                                <tr>
                                    <td>
                                        @if ($followup->lead)
                                            @can('leads.view')
                                            <a href="{{ route('v2.leads.show', $followup->lead) }}" style="font-weight:900; text-decoration:none; color:var(--dark)">
                                                {{ $followup->lead->name }}
                                            </a>
                                            @else
                                            <span style="font-weight:900; color:var(--dark)">
                                                {{ $followup->lead->name }}
                                            </span>
                                            @endcan
                                        @else
                                            <span style="color:var(--muted)">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge">
                                            {{ $communicationLabels[$followup->communication_type] ?? $followup->communication_type }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $followup->employee_name ?: ($followup->user?->name ?? '—') }}</strong></td>
                                    <td><small>{{ $followup->followed_up_at ? $followup->followed_up_at->format('Y-m-d H:i') : '—' }}</small></td>
                                    <td>
                                        @php
                                            $stgName = $followup->toStatus?->stage?->name_ar ?? $followup->lead?->status?->stage?->name_ar ?? $followup->lead?->status?->name_ar ?? '—';
                                            $stgColor = $followup->toStatus?->stage?->color ?? $followup->lead?->status?->stage?->color ?? '#3478f6';
                                        @endphp
                                        <span class="badge" style="background:{{ $stgColor }}15; color:{{ $stgColor }}; border:1px solid {{ $stgColor }}30">
                                            {{ $stgName }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:24px; color:var(--muted)">
                                        {{ __('crm.no_followups_recorded') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <!-- Quick Actions Panel -->
            <article class="chart-card">
                <div class="panel-head">
                    <div>
                        <h2><i class="bi bi-lightning-charge"></i> {{ __('crm.quick_actions_title') }}</h2>
                        <p>{{ __('crm.quick_actions_desc') }}</p>
                    </div>
                </div>

                <div class="quick-actions-list">
                    @can('leads.create')
                        <a href="{{ route('v2.leads.create') }}" class="quick-action-link">
                            <i class="bi bi-person-plus-fill" style="color:#0284c7"></i>
                            <span>{{ __('crm.add_new_donor_lead') }}</span>
                        </a>
                    @endcan
                    @can('tasks.view')
                        <a href="{{ route('v2.tasks.daily') }}" class="quick-action-link">
                            <i class="bi bi-calendar-check-fill" style="color:#d97706"></i>
                            <span>{{ __('crm.today_followups_tasks') }}</span>
                        </a>
                    @endcan
                    @can('collections.view')
                        <a href="{{ route('v2.collections.index') }}" class="quick-action-link">
                            <i class="bi bi-cash-stack" style="color:#0f766e"></i>
                            <span>{{ __('crm.collections') }}</span>
                        </a>
                    @endcan
                    @can('leads.export')
                        <a href="{{ route('v2.leads.export') }}" class="quick-action-link">
                            <i class="bi bi-file-earmark-arrow-down-fill" style="color:#16a34a"></i>
                            <span>{{ __('crm.export_donors_data') }}</span>
                        </a>
                    @endcan
                    @can('settings.access')
                    <a href="{{ route('v2.settings.stages.index') }}" class="quick-action-link">
                        <i class="bi bi-sliders" style="color:#475569"></i>
                        <span>{{ __('crm.donor_stages_settings') }}</span>
                    </a>
                    @endcan
                </div>
            </article>
        </section>

        <!-- 7. COLLECTIONS MANAGEMENT & PERFORMANCE -->
        @if($collectionSummary !== null)
            <section class="stages-panel" style="margin-top:18px;">
                <div class="panel-head">
                    <div>
                        <h2><i class="bi bi-cash-stack"></i> {{ __('crm.collection_dashboard_title') }}</h2>
                        <p>{{ __('crm.collection_dashboard_desc') }}</p>
                    </div>
                    @can('collections.view')
                    <a href="{{ route('v2.collections.index') }}" class="btn small soft">{{ __('crm.view_all') }}</a>
                    @endcan
                </div>
                <div class="stages-grid">
                    @can('collections.view')
                    <a href="{{ route('v2.collections.index', ['scope' => 'open']) }}" class="stage-summary-box" style="--stage-color:#0f766e">
                    @else
                    <div class="stage-summary-box" style="cursor:default; --stage-color:#0f766e">
                    @endcan
                        <div class="stage-box-top"><span class="stage-box-title">{{ __('crm.collection_open') }}</span><span class="stage-box-count">{{ number_format($collectionSummary['open']) }}</span></div>
                    @can('collections.view')
                    </a>
                    @else
                    </div>
                    @endcan

                    @can('collections.view')
                    <a href="{{ route('v2.collections.index', ['scope' => 'overdue']) }}" class="stage-summary-box" style="--stage-color:#dc2637">
                    @else
                    <div class="stage-summary-box" style="cursor:default; --stage-color:#dc2637">
                    @endcan
                        <div class="stage-box-top"><span class="stage-box-title">{{ __('crm.collection_overdue') }}</span><span class="stage-box-count">{{ number_format($collectionSummary['overdue']) }}</span></div>
                    @can('collections.view')
                    </a>
                    @else
                    </div>
                    @endcan

                    <div class="stage-summary-box" style="--stage-color:#d97706">
                        <div class="stage-box-top"><span class="stage-box-title">{{ __('crm.collection_expected_open') }}</span><span class="stage-box-count">{{ number_format($collectionSummary['expected_open'], 2) }}</span></div>
                    </div>
                    <div class="stage-summary-box" style="--stage-color:#16a34a">
                        <div class="stage-box-top"><span class="stage-box-title">{{ __('crm.collection_received_value') }}</span><span class="stage-box-count">{{ number_format($collectionSummary['received'], 2) }}</span></div>
                    </div>
                </div>
            </section>

            @if(auth()->user()->isSuperAdmin() && $collectionStatusDistribution !== null)
                <section class="charts-grid">
                    <article class="chart-card">
                        <div class="panel-head">
                            <div>
                                <h2><i class="bi bi-pie-chart-fill" style="color:#0f766e"></i> {{ __('crm.collection_status_report') }}</h2>
                                <p>{{ __('crm.by_current_status') }}</p>
                            </div>
                            <span class="badge" style="background:#f0fdf4; color:#16a34a; font-size:11px; padding:4px 10px;">
                                <i class="bi bi-layers-fill"></i>
                                {{ number_format(array_sum($collectionStatusDistribution['data'])) }} {{ __('crm.cases') }}
                            </span>
                        </div>
                        <div class="collection-status-fluid-body">
                            <div class="collection-status-chart-col">
                                <div class="chart-wrap-responsive collection-donut-wrap">
                                    <canvas id="collectionStatusChart" role="img" aria-label="{{ __('crm.collection_status_report') }}"></canvas>
                                    <div class="collection-donut-center" id="collectionDonutCenter">
                                        <span class="center-total">{{ number_format(array_sum($collectionStatusDistribution['data'])) }}</span>
                                        <span class="center-label">{{ __('crm.total') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="collection-status-breakdown-col">
                                @php
                                    $totalCases = max(1, array_sum($collectionStatusDistribution['data']));
                                    $statusColors = [
                                        '#0f766e',
                                        '#2563eb',
                                        '#7c3aed',
                                        '#ef4444',
                                        '#16a34a',
                                        '#64748b',
                                    ];
                                @endphp
                                <div class="status-bars-list">
                                    @foreach ($collectionStatusDistribution['labels'] as $idx => $label)
                                        @php
                                            $count = $collectionStatusDistribution['data'][$idx] ?? 0;
                                            $pct = round(($count / $totalCases) * 100, 1);
                                            $colColor = $statusColors[$idx % count($statusColors)];
                                        @endphp
                                        <div class="status-bar-row" data-status-index="{{ $idx }}">
                                            <div class="status-bar-header">
                                                <span class="status-bar-title">
                                                    <span class="status-color-dot" style="background:{{ $colColor }}"></span>
                                                    <strong>{{ $label }}</strong>
                                                </span>
                                                <div class="status-bar-numbers">
                                                    <span class="status-bar-count">{{ number_format($count) }}</span>
                                                    <span class="status-bar-pct">({{ $pct }}%)</span>
                                                </div>
                                            </div>
                                            <div class="status-progress-track">
                                                <div class="status-progress-fill" style="width:{{ max($count > 0 ? 3 : 0, $pct) }}%; background:{{ $colColor }}"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="chart-card">
                        <div class="panel-head">
                            <div>
                                <h2><i class="bi bi-person-check"></i> {{ __('crm.collector_performance') }}</h2>
                                <p>{{ __('crm.sales_team_performance_overview') }}</p>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>{{ __('crm.collector') }}</th><th>{{ __('crm.collected_cases') }}</th><th>{{ __('crm.open_value') }}</th></tr></thead>
                                <tbody>
                                    @forelse($collectorPerformance as $collector)
                                        <tr><td>{{ $collector['name'] }}</td><td>{{ $collector['collected'] }} / {{ $collector['total'] }}</td><td>{{ number_format($collector['open_value'], 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" style="text-align:center;color:var(--muted)">{{ __('crm.no_collection_cases') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>
            @endif
        @endif
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'Tajawal, Tahoma, Arial, sans-serif';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;
    const mountedCharts = new Map();
    const stageSourceColors = {!! json_encode($stageDistribution['colors']) !!};
    const stageGrayColors = ['#171717', '#525252', '#8a8a8a', '#b8b8b8', '#dedede'];
    const chartPalette = () => {
        const monochrome = root.classList.contains('crm-monochrome');
        const dark = root.classList.contains('dark-mode');

        return {
            monochrome,
            text: dark ? '#a3a3a3' : '#64748b',
            surface: dark ? (monochrome ? '#1c1c1c' : '#1e293b') : '#ffffff',
            stages: monochrome ? stageGrayColors : stageSourceColors,
            donation: monochrome ? (dark ? '#f5f5f5' : '#171717') : '#f59e0b',
            newLeads: monochrome ? '#b8b8b8' : '#38bdf8',
            donors: monochrome ? '#525252' : '#22c55e',
            donationTicks: monochrome ? (dark ? '#d4d4d4' : '#737373') : '#d97706',
        };
    };

    const chartAnimation = () => {
        if (prefersReducedMotion) {
            return false;
        }

        return {
            duration: 280,
            easing: 'easeOutQuart',
        };
    };

    const animateChartWhenVisible = (canvas, config) => {
        if (canvas.dataset.chartMounted === 'true') {
            return;
        }

        canvas.dataset.chartMounted = 'true';
        const chart = new Chart(canvas.getContext('2d'), config);
        mountedCharts.set(canvas.id, chart);
    };

    // 1. Chart 1 — Stage Donut Chart (Section 11)
    const donutCtx = document.getElementById('stageDonutChart');
    if (donutCtx) {
        const stageLabels = {!! json_encode($stageDistribution['labels']) !!};
        const stageData = {!! json_encode($stageDistribution['data']) !!};
        const palette = chartPalette();
        const stageColors = stageData.map((_, index) => palette.stages[index % palette.stages.length]);

        animateChartWhenVisible(donutCtx, {
            type: 'doughnut',
            data: {
                labels: stageLabels,
                datasets: [{
                    data: stageData,
                    backgroundColor: stageColors,
                    borderWidth: 2,
                    borderColor: palette.surface,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                animation: prefersReducedMotion ? false : {
                    ...chartAnimation(),
                    animateRotate: true,
                    animateScale: true
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
                        labels: {
                            color: palette.text,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 10,
                            boxWidth: 8,
                            font: { size: 11, weight: '700' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart 2 — Monthly Trend Activity Chart (Section 11)
    const trendCtx = document.getElementById('activityTrendChart');
    if (trendCtx) {
        const palette = chartPalette();
        const monthsData = {!! json_encode($monthsTrend) !!};
        const labels = monthsData.map(m => m.label);
        const newCounts = monthsData.map(m => m.new_count);
        const donorCounts = monthsData.map(m => m.donor_count);
        const donationVals = monthsData.map(m => m.donation_value);

        animateChartWhenVisible(trendCtx, {
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'line',
                        label: @json(__('crm.donations_value_egp')),
                        data: donationVals,
                        yAxisID: 'y1',
                        borderColor: palette.donation,
                        backgroundColor: palette.donation,
                        borderWidth: 1.8,
                        pointRadius: 2,
                        tension: 0.25,
                    },
                    {
                        type: 'bar',
                        label: @json(__('crm.new_leads_chart')),
                        data: newCounts,
                        yAxisID: 'y',
                        backgroundColor: palette.newLeads,
                        borderRadius: 2,
                        barThickness: 14,
                    },
                    {
                        type: 'bar',
                        label: @json(__('crm.donors_chart')),
                        data: donorCounts,
                        yAxisID: 'y',
                        backgroundColor: palette.donors,
                        borderRadius: 2,
                        barThickness: 14,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: chartAnimation(),
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
                        labels: {
                            color: palette.text,
                            usePointStyle: true,
                            padding: 14,
                            font: { size: 12, weight: '700' }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: '{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}',
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: { color: palette.text, precision: 0 }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: '{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: palette.donationTicks }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: palette.text }
                    }
                }
            }
        });
    }

    const collectionStatusCtx = document.getElementById('collectionStatusChart');
    if (collectionStatusCtx) {
        const collectionStatus = @json($collectionStatusDistribution);
        const palette = chartPalette();
        const colors = palette.monochrome
            ? ['#171717', '#525252', '#737373', '#a3a3a3', '#d4d4d4', '#e5e5e5']
            : ['#0f766e', '#2563eb', '#7c3aed', '#ef4444', '#16a34a', '#64748b'];
        const total = collectionStatus ? collectionStatus.data.reduce((a, b) => a + b, 0) : 0;
        const centerEl = document.getElementById('collectionDonutCenter');

        animateChartWhenVisible(collectionStatusCtx, {
            type: 'doughnut',
            data: {
                labels: collectionStatus.labels,
                datasets: [{
                    data: collectionStatus.data,
                    backgroundColor: colors,
                    borderColor: palette.surface,
                    borderWidth: 2,
                    borderRadius: 5,
                    spacing: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '74%',
                animation: chartAnimation(),
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const val = ctx.parsed || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                onHover: (evt, elements) => {
                    if (!centerEl) return;
                    if (elements && elements.length > 0) {
                        const idx = elements[0].index;
                        const val = collectionStatus.data[idx];
                        const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                        const totalSpan = centerEl.querySelector('.center-total');
                        const labelSpan = centerEl.querySelector('.center-label');
                        if (totalSpan) totalSpan.textContent = val;
                        if (labelSpan) labelSpan.textContent = `${pct}%`;
                    } else {
                        const totalSpan = centerEl.querySelector('.center-total');
                        const labelSpan = centerEl.querySelector('.center-label');
                        if (totalSpan) totalSpan.textContent = total;
                        if (labelSpan) labelSpan.textContent = @json(__('crm.total'));
                    }
                }
            }
        });
    }

    window.addEventListener('crm:theme-changed', () => {
        const palette = chartPalette();
        const donut = mountedCharts.get('stageDonutChart');
        if (donut) {
            donut.data.datasets[0].backgroundColor = donut.data.datasets[0].data
                .map((_, index) => palette.stages[index % palette.stages.length]);
            donut.data.datasets[0].borderColor = palette.surface;
            donut.options.plugins.legend.labels.color = palette.text;
            donut.update('none');
        }

        const trend = mountedCharts.get('activityTrendChart');
        if (trend) {
            trend.data.datasets[0].borderColor = palette.donation;
            trend.data.datasets[0].backgroundColor = palette.donation;
            trend.data.datasets[1].backgroundColor = palette.newLeads;
            trend.data.datasets[2].backgroundColor = palette.donors;
            trend.options.plugins.legend.labels.color = palette.text;
            trend.options.scales.y.ticks.color = palette.text;
            trend.options.scales.y1.ticks.color = palette.donationTicks;
            trend.options.scales.x.ticks.color = palette.text;
            trend.update('none');
        }

        const collections = mountedCharts.get('collectionStatusChart');
        if (collections) {
            collections.data.datasets[0].borderColor = palette.surface;
            collections.options.plugins.legend.labels.color = palette.text;
            collections.update('none');
        }
    });
});
</script>
<script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
</body>
</html>

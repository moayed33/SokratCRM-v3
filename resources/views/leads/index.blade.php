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
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v5">
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
  height: 42px;
  min-height: 42px;
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
  border-radius: 14px;
  padding: 12px 14px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  margin-bottom: 16px;
}
.filter-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 8px 10px;
  align-items: end;
}
.filter-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.filter-field.col-search {
  grid-column: span 2;
  min-width: 220px;
}
.filter-actions-col {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 34px;
  justify-self: start;
  align-self: end;
  white-space: nowrap;
}
.filter-apply-btn {
  height: 34px;
  min-height: 34px;
  padding: 0 16px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 9px;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.filter-reset-btn {
  height: 34px;
  min-height: 34px;
  padding: 0 10px;
  font-size: 12px;
  border-radius: 9px;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.filter-field label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  white-space: nowrap;
  line-height: 1.2;
}
.filter-field label i {
  font-size: 11px;
  opacity: .75;
}
.filter-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}
.filter-input-icon {
  position: absolute;
  inset-inline-start: 10px;
  color: var(--muted);
  pointer-events: none;
  font-size: 12px;
}
.filter-control {
  width: 100%;
  height: 34px;
  border: 1px solid var(--line);
  border-radius: 9px;
  padding: 0 10px;
  background: var(--card);
  color: var(--dark);
  font-size: 12px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.filter-field .crm-select-trigger,
.filter-control-wrap .crm-select-trigger {
  height: 34px !important;
  min-height: 34px !important;
  max-height: 34px !important;
  border-radius: 9px !important;
  padding: 0 10px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
}

.filter-field .crm-select-trigger .crm-select-trigger-label,
.filter-control-wrap .crm-select-trigger .crm-select-trigger-label {
  font-size: 12px !important;
  font-weight: 600 !important;
}
.filter-control.with-icon {
  padding-inline-start: 28px;
}
.filter-control:focus {
  border-color: var(--red);
  box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
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
  display: none;
}
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
.btn-action.whatsapp {
  color: #12805c;
}
.btn-action.whatsapp:hover,
.btn-action.whatsapp:focus-visible {
  background: #ecfdf5;
  border-color: #a7f3d0;
}
html.dark-mode .btn-action.whatsapp {
  color: #6ee7b7;
}
html.dark-mode .btn-action.whatsapp:hover,
html.dark-mode .btn-action.whatsapp:focus-visible {
  background: rgba(16, 185, 129, 0.18);
  border-color: rgba(52, 211, 153, 0.4);
}
.btn-action.is-disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.btn-action.is-disabled:hover {
  background: #f1f5f9;
  border-color: var(--line);
  transform: none;
}
html.dark-mode .btn-action.is-disabled:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--line);
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
  .filter-form-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
  .filter-field.col-search { grid-column: span 2; }
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
  .filter-actions-col { width: 100%; justify-self: stretch; }
  .filter-apply-btn { flex: 1; justify-content: center; }
  .pagination-wrap { flex-direction: column; align-items: center; text-align: center; }
}

/* Leads Per-Page Slider */
.col-per-page {
  min-width: 190px;
}
.filter-range-slider {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 6px;
  border-radius: 999px;
  background: var(--line, #e2e8f0);
  outline: none;
  margin: 7px 0 2px 0;
  cursor: pointer;
  accent-color: var(--red, #dc2637);
}
.filter-range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--red, #dc2637);
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(220, 38, 55, 0.4);
  transition: transform 0.12s ease, background-color 0.12s ease;
}
.filter-range-slider::-webkit-slider-thumb:hover {
  transform: scale(1.15);
  background: #b81829;
}
.filter-range-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--red, #dc2637);
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 6px rgba(220, 38, 55, 0.4);
}

/* ── ARABIC RTL SCROLLBAR ON LEFT + RED & THICKER ── */
html[dir="rtl"] {
  height: 100% !important;
  overflow: hidden !important;
  direction: rtl !important;
}

html[dir="rtl"] body {
  height: 100% !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  direction: rtl !important;
}

html[dir="rtl"] .crm-app,
html[dir="rtl"] .crm-main,
html[dir="rtl"] .table-wrap,
html[dir="rtl"] .filter-panel {
  direction: rtl !important;
}

/* Red & Thicker Scrollbar (Global) */
html,
body,
* {
  scrollbar-color: #dc2637 transparent !important;
  scrollbar-width: auto !important;
}

*::-webkit-scrollbar,
::-webkit-scrollbar {
  width: 10px !important;
  height: 10px !important;
}

*::-webkit-scrollbar-track,
::-webkit-scrollbar-track {
  background: transparent !important;
}

*::-webkit-scrollbar-thumb,
::-webkit-scrollbar-thumb {
  background-color: #dc2637 !important;
  border-radius: 9999px !important;
  border: 2px solid transparent !important;
  background-clip: padding-box !important;
}

*::-webkit-scrollbar-thumb:hover,
::-webkit-scrollbar-thumb:hover {
  background-color: #b91c1c !important;
  background-clip: padding-box !important;
}

*::-webkit-scrollbar-thumb:active,
::-webkit-scrollbar-thumb:active {
  background-color: #7f1d1d !important;
  background-clip: padding-box !important;
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
                </div>
            </div>
            <div class="top-actions">
                @can('leads.export')
                <a href="{{ route('v2.leads.export') }}" class="btn soft">
                    <i class="bi bi-file-earmark-arrow-down"></i> {{ __('crm.export') }}
                </a>
                @endcan
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
                    <select id="stageFilter" name="stage" class="filter-control">
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
                    <select id="typeFilter" name="donation_type" class="filter-control">
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
                    <select id="cycleFilter" name="donation_cycle" class="filter-control">
                        <option value="">{{ __('crm.all_donation_cycles') }}</option>
                        @foreach ($donationCycles as $cKey => $cLabel)
                            <option value="{{ $cKey }}" {{ $filters['donation_cycle'] === $cKey ? 'selected' : '' }}>
                                {{ __('crm.donation_cycle_' . $cKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Min Donation Value Filter -->
                <div class="filter-field">
                    <label for="donationMinFilter"><i class="bi bi-cash"></i> {{ __('crm.min_donation_value') }}</label>
                    <input type="number" step="any" min="0" id="donationMinFilter" name="donation_min" value="{{ $filters['donation_min'] }}" class="filter-control" placeholder="0.00">
                </div>

                <!-- Max Donation Value Filter -->
                <div class="filter-field">
                    <label for="donationMaxFilter"><i class="bi bi-cash-stack"></i> {{ __('crm.max_donation_value') }}</label>
                    <input type="number" step="any" min="0" id="donationMaxFilter" name="donation_max" value="{{ $filters['donation_max'] }}" class="filter-control" placeholder="10000.00">
                </div>

                <!-- Employee Filter -->
                <div class="filter-field">
                    <label for="employeeFilter"><i class="bi bi-person-check"></i> {{ __('crm.assigned_employee') }}</label>
                    <select id="employeeFilter" name="employee" class="filter-control">
                        <option value="">{{ __('crm.all_employees') }}</option>
                        @foreach ($employees as $empName)
                            <option value="{{ $empName }}" {{ $filters['employee'] === $empName ? 'selected' : '' }}>
                                {{ $empName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Configurable Field Filters (Settings -> Lead Fields) -->
                @foreach ($filterFields as $filterField)
                    @php
                        $fieldFilterValue = $filters[$filterField->key] ?? ($filterField->type === 'multiselect' ? [] : '');
                        $isMulti = $filterField->type === 'multiselect';
                    @endphp
                    <div class="filter-field">
                        <label for="cfFilter_{{ $filterField->key }}"><i class="bi bi-list-ul"></i> {{ $filterField->label() }}</label>
                        @switch ($filterField->type)
                            @case ('select')
                                <select id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}" class="filter-control">
                                    <option value="">{{ __('crm.lf_filter_all') }}</option>
                                    @foreach ($filterField->options ?? [] as $option)
                                        <option value="{{ $option['value'] ?? '' }}" @selected($fieldFilterValue === (string) ($option['value'] ?? ''))>
                                            {{ app()->getLocale() === 'en' && trim((string) ($option['label_en'] ?? '')) !== '' ? trim((string) $option['label_en']) : trim((string) ($option['label_ar'] ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                                @break
                            @case ('multiselect')
                                <select id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}[]" class="filter-control" multiple size="3">
                                    @foreach ($filterField->options ?? [] as $option)
                                        <option value="{{ $option['value'] ?? '' }}" @selected(in_array((string) ($option['value'] ?? ''), (array) $fieldFilterValue, true))>
                                            {{ app()->getLocale() === 'en' && trim((string) ($option['label_en'] ?? '')) !== '' ? trim((string) $option['label_en']) : trim((string) ($option['label_ar'] ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                                @break
                            @case ('checkbox')
                                <select id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}" class="filter-control">
                                    <option value="">{{ __('crm.lf_filter_any') }}</option>
                                    <option value="1" @selected($fieldFilterValue === '1')>{{ __('crm.lf_filter_yes') }}</option>
                                    <option value="0" @selected($fieldFilterValue === '0')>{{ __('crm.lf_filter_no') }}</option>
                                </select>
                                @break
                            @case ('number')
                                <input type="number" step="any" id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}" value="{{ $fieldFilterValue }}" class="filter-control">
                                @break
                            @case ('date')
                            @case ('datetime')
                                <input type="date" id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}" value="{{ $fieldFilterValue }}" class="filter-control">
                                @break
                            @default
                                <input type="text" id="cfFilter_{{ $filterField->key }}" name="{{ $filterField->key }}" value="{{ $fieldFilterValue }}" class="filter-control" placeholder="{{ __('crm.lf_filter_contains') }}">
                        @endswitch
                    </div>
                @endforeach

                <!-- Action Buttons & Slider: Next to filters -->
                <div class="filter-actions-col" style="display: flex; align-items: flex-end; gap: 14px; grid-column: span 2; min-width: 270px;">
                    <div class="col-per-page" style="flex: 1; min-width: 150px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; font-weight: 800; color: var(--muted); margin-bottom: 3px;">
                            <label for="perPageSlider" style="margin: 0; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <i class="bi bi-sliders" style="color: var(--red, #dc2637);"></i>
                                <span>{{ __('crm.per_page') }}:</span>
                            </label>
                            <span id="perPageBadge" style="background: var(--red, #dc2637); color: #fff; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 800; font-family: monospace;">{{ $perPage ?? 20 }}</span>
                        </div>
                        <input type="range" 
                               id="perPageSlider" 
                               name="per_page" 
                               min="10" 
                               max="200" 
                               step="10" 
                               value="{{ $perPage ?? 20 }}" 
                               class="filter-range-slider"
                               oninput="document.getElementById('perPageBadge').textContent = this.value"
                               onchange="this.form.submit()"
                               title="{{ __('crm.drag_to_change_per_page') ?? 'اسحب لتغيير عدد العملاء في الصفحة' }}">
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px; height: 34px; flex-shrink: 0; margin-bottom: 2px;">
                        <button type="submit" class="btn primary small filter-apply-btn" title="{{ __('crm.apply_filter') }}">
                            <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                        </button>
                        @if ($activeQuery !== [])
                            <a href="{{ route('v2.leads') }}" class="btn soft small filter-reset-btn" title="{{ __('crm.reset_filters') }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
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
                            <th style="min-width:140px">{{ __('crm.primary_phone') }}</th>
                            <th style="min-width:120px">{{ __('crm.donation_value') }}</th>
                            <th style="min-width:130px">{{ __('crm.donation_cycle') }}</th>
                            <th style="min-width:130px">{{ __('crm.current_stage') }}</th>
                            @foreach ($tableColumns as $tableColumn)
                                <th style="min-width:120px">{{ $tableColumn->label() }}</th>
                            @endforeach
                            <th style="min-width:160px">{{ __('crm.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <!-- 1. Customer Name -->
                                <td class="customer-name-cell">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <a href="{{ route('v2.leads.show', $lead) }}">
                                            <strong>{{ $lead->name }}</strong>
                                        </a>
                                        @if ($lead->branch_id && auth()->user()->branch_id && (int) $lead->branch_id !== (int) auth()->user()->branch_id)
                                            <span class="badge-branch" style="font-size:10px;padding:2px 6px;border-radius:6px;" title="{{ __('crm.lead_branch') ?? 'الفرع' }}: {{ $lead->branch?->name_ar }}">
                                                <i class="bi bi-geo-alt-fill"></i> {{ $lead->branch?->name_ar ?? 'فرع آخر' }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($lead->donation_purpose)
                                        <small><i class="bi bi-bullseye" style="color:var(--red)"></i> {{ $lead->donation_purpose }}</small>
                                    @elseif ($lead->company_name)
                                        <small><i class="bi bi-building"></i> {{ $lead->company_name }}</small>
                                    @endif
                                </td>

                                <!-- 2. Primary Phone & Extra count -->
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;position:relative;" class="lead-phone-cell">
                                        <span dir="ltr" style="font-weight:700">{{ $lead->display_phone }}</span>
                                        @if (!empty($lead->phone))
                                            <button type="button" class="btn-dial-inline" data-voice-dial="{{ $lead->phone }}" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name }}" title="{{ __('crm.call') ?? 'اتصال' }}"><i class="bi bi-telephone-outbound-fill"></i></button>
                                        @endif
                                        @php
                                            $extraPhones = $lead->phones->where('is_primary', false);
                                        @endphp
                                        @if ($extraPhones->isNotEmpty())
                                            <div class="extra-phones-dropdown-wrap" style="position:relative;display:inline-block;">
                                                <button type="button" 
                                                        class="badge extra-phones-toggle-btn" 
                                                        onclick="toggleLeadPhones(event, this)"
                                                        style="cursor:pointer;border:none;background:rgba(52,120,246,0.12);color:var(--blue,#3478f6);font-weight:800;padding:2px 7px;border-radius:999px;font-size:11px;"
                                                        title="{{ __('crm.additional_phone_count', ['count' => $extraPhones->count()]) }}">
                                                    +{{ $extraPhones->count() }} <i class="bi bi-chevron-down" style="font-size:8px;"></i>
                                                </button>
                                                <div class="extra-phones-popover" style="display:none;position:absolute;top:calc(100% + 4px);inset-inline-start:0;min-width:190px;background:var(--card,#ffffff);border:1px solid var(--line,#e2e8f0);border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.18);padding:6px;z-index:1050;">
                                                    <div style="font-size:10px;font-weight:800;color:var(--muted);padding:2px 6px 4px;border-bottom:1px solid var(--line,#e2e8f0);margin-bottom:4px;text-transform:uppercase;">
                                                        {{ __('crm.additional_phones') ?? 'أرقام هواتف إضافية' }}
                                                    </div>
                                                    @foreach ($extraPhones as $extraPhone)
                                                        @php
                                                            $extraRaw = preg_replace('/\D+/', '', $extraPhone->phone);
                                                        @endphp
                                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;padding:4px 6px;border-radius:6px;transition:background 0.12s;">
                                                            <span dir="ltr" style="font-size:11.5px;font-weight:700;font-family:monospace;color:var(--dark);">{{ $extraPhone->display_phone }}</span>
                                                            <div style="display:flex;align-items:center;gap:3px;">
                                                                <button type="button" class="btn-dial-inline" data-voice-dial="{{ $extraRaw }}" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name }}" title="{{ __('crm.call') ?? 'اتصال' }}" style="width:24px;height:24px;font-size:10px;">
                                                                    <i class="bi bi-telephone-outbound-fill"></i>
                                                                </button>
                                                                <a href="https://wa.me/{{ $extraRaw }}" target="_blank" rel="noopener" class="btn-dial-inline" title="WhatsApp" style="width:24px;height:24px;font-size:10px;background:rgba(22,163,74,0.1);color:#16a34a;">
                                                                    <i class="bi bi-whatsapp"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Donation Value -->
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

                                <!-- Donation Cycle -->
                                <td>
                                    @if ($lead->donation_cycle)
                                        <span class="badge active" style="font-size:12px">
                                            <i class="bi bi-arrow-repeat" style="font-size:11px"></i>
                                            {{ __('crm.donation_cycle_' . $lead->donation_cycle) }}
                                        </span>
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

                                <!-- 9. Configurable table columns -->
                                @php $leadCustomValues = is_array($lead->custom_fields) ? $lead->custom_fields : []; @endphp
                                @foreach ($tableColumns as $tableColumn)
                                    @php
                                        $columnValue = $leadCustomValues[$tableColumn->key] ?? null;
                                        $hasColumnValue = ! ($columnValue === null || $columnValue === '' || $columnValue === []);
                                    @endphp
                                    <td>
                                        {{ $hasColumnValue ? \App\Support\LeadFieldSchema::formatValue($tableColumn, $columnValue) : '—' }}
                                    </td>
                                @endforeach

                                <!-- 10. Actions -->
                                <td>
                                    @php
                                        $phoneDigits = preg_replace('/\D+/', '', (string) $lead->phone) ?? '';
                                        $whatsappPhone = null;

                                        if (str_starts_with($phoneDigits, '0020')) {
                                            $whatsappPhone = substr($phoneDigits, 2);
                                        } elseif (preg_match('/^01[0125][0-9]{8}$/', $phoneDigits)) {
                                            $whatsappPhone = '20'.substr($phoneDigits, 1);
                                        } elseif (preg_match('/^20[0-9]{10}$/', $phoneDigits)) {
                                            $whatsappPhone = $phoneDigits;
                                        } elseif (preg_match('/^[1-9][0-9]{7,14}$/', $phoneDigits)) {
                                            $whatsappPhone = $phoneDigits;
                                        }
                                    @endphp
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
                                            <a href="{{ route('v2.leads.followups.index', $lead) }}"
                                               class="btn-action call"
                                               title="{{ $phoneDigits ? __('crm.call_and_followup') : __('crm.log_followup') }}"
                                               data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
                                               data-lead-name="{{ $lead->name }}"
                                               @if ($phoneDigits) data-voice-dial="{{ $phoneDigits }}" @endif
                                            >
                                                <i class="bi bi-telephone-outbound"></i>
                                            </a>
                                        @endcan
                                        @if ($whatsappPhone)
                                            <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener noreferrer" class="btn-action whatsapp" title="{{ __('crm.open_whatsapp') }}" aria-label="{{ __('crm.open_whatsapp') }}">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @else
                                            <span class="btn-action whatsapp is-disabled" title="{{ __('crm.invalid_whatsapp_number') }}" aria-label="{{ __('crm.invalid_whatsapp_number') }}" aria-disabled="true">
                                                <i class="bi bi-whatsapp"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (auth()->user()->isSuperAdmin() ? 10 : 9) + $tableColumns->count() }}" style="text-align:center; padding:48px 20px; color:var(--muted)">
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
<script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
@include('partials.transition-popup')
<script>
window.toggleLeadPhones = function(e, btn) {
    e.stopPropagation();
    const popover = btn.nextElementSibling;
    if (!popover) return;
    const isOpen = popover.style.display !== 'none';
    document.querySelectorAll('.extra-phones-popover').forEach(p => p.style.display = 'none');
    if (!isOpen) popover.style.display = 'block';
};
document.addEventListener('click', (e) => {
    if (!e.target.closest('.extra-phones-dropdown-wrap')) {
        document.querySelectorAll('.extra-phones-popover').forEach(p => p.style.display = 'none');
    }
});
</script>
</body>
</html>

<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.calendar_and_events') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v5">
<style>
:root {
  --red: #dc2637;
  --red-hover: #b81d2c;
  --primary: #4f46e5;
  --primary-hover: #4338ca;
  --dark: #182033;
  --text: #4b5568;
  --muted: #8b94a5;
  --line: #e7e9ef;
  --bg: #f5f6f9;
  --card: #fff;
  --shadow: 0 12px 35px rgba(23, 32, 51, 0.05);
  --font-primary: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

html.dark-mode {
  --dark: #f4f4f5;
  --text: #a1a1aa;
  --muted: #a1a1aa;
  --line: rgba(255, 255, 255, 0.08);
  --bg: #121214;
  --card: rgba(24, 24, 27, 0.75);
  --shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

* { box-sizing: border-box; }
body {
  margin: 0;
  min-width: 320px;
  background: radial-gradient(circle at 8% 0, rgba(220, 38, 55, 0.05), transparent 25rem), var(--bg);
  color: var(--dark);
  font-family: var(--font-primary);
}
html.dark-mode body {
  background: radial-gradient(circle at 8% 0, rgba(255, 255, 255, 0.03), transparent 28rem), var(--bg);
  color: var(--dark);
}
button, input, select, textarea { font: inherit; }
a { color: inherit; text-decoration: none; }

/* Layout Structure */
.app {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  min-height: 100vh;
}
.side {
  order: 0;
  flex: 0 0 288px;
  width: 288px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: auto;
  padding: 24px 17px;
  background: #fff;
  border-inline-end: 1px solid var(--line);
  z-index: 20;
}
.main-content {
  order: 1;
  flex: 1 1 auto;
  width: calc(100% - 288px);
  min-width: 0;
  padding: 24px 30px;
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}
.page-title h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  color: var(--dark);
}
.page-title p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 13px;
}

/* Buttons */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 42px;
  min-height: 42px;
  padding: 0 18px;
  border-radius: 12px;
  background: var(--red);
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(220, 38, 55, 0.25);
  transition: all .2s ease;
  box-sizing: border-box;
}
.btn-primary:hover {
  background: var(--red-hover);
  transform: translateY(-1px);
}
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  background: #f1f5f9;
  color: #334155;
  font-weight: 600;
  border: 1px solid var(--line);
  cursor: pointer;
  transition: all .2s ease;
}
.btn-secondary:hover {
  background: #e2e8f0;
}
html.dark-mode .btn-secondary {
  background: rgba(255, 255, 255, 0.08);
  color: #f4f4f5;
  border-color: rgba(255, 255, 255, 0.14);
}
html.dark-mode .btn-secondary:hover {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.28);
  color: #ffffff;
}
.btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  background: #fee2e2;
  color: #dc2626;
  font-weight: 600;
  border: 1px solid #fca5a5;
  cursor: pointer;
  transition: all .2s ease;
}
.btn-danger:hover {
  background: #fecaca;
}
html.dark-mode .btn-danger {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.4);
}
html.dark-mode .btn-danger:hover {
  background: rgba(239, 68, 68, 0.25);
  color: #fca5a5;
  border-color: rgba(239, 68, 68, 0.6);
}
.btn-indigo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 12px;
  background: #4f46e5;
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
  transition: all .2s ease;
}
.btn-indigo:hover {
  background: #4338ca;
  transform: translateY(-1px);
}

/* Reminder Banner */
.reminder-alert-banner {
  margin-bottom: 16px;
  padding: 12px 18px;
  border: 1px solid #fcd34d;
  border-radius: 14px;
  background: #fffbeb;
  color: #92400e;
  font-weight: 700;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
  animation: pulseLight 2s infinite ease-in-out;
}
@keyframes pulseLight {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.85; }
}
.reminder-alert-icon {
  margin-inline-end: 10px;
  font-size: 18px;
  color: #d97706;
}
html.dark-mode .reminder-alert-banner {
  background: rgba(245, 158, 11, 0.12) !important;
  border-color: rgba(245, 158, 11, 0.35) !important;
  color: #fbbf24 !important;
}
html.dark-mode .reminder-alert-icon {
  color: #f59e0b !important;
}

/* Card & Filters */
.card {
  background: var(--card);
  border-radius: 20px;
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
  padding: 24px;
  margin-bottom: 24px;
}
html.dark-mode .card {
  background: var(--bg-card);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
}
.filters-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--line);
}
html.dark-mode .filters-bar {
  border-bottom-color: rgba(255, 255, 255, 0.08);
}
.filters-left {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
}
.filters-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.filter-group { display: flex; align-items: center; gap: 8px; }
.filter-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--muted);
  white-space: nowrap;
}
html.dark-mode .filter-label {
  color: #a1a1aa !important;
}
.filter-select {
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: #fafbfc;
  color: var(--dark);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s ease;
}
.filter-select:focus {
  outline: none;
  border-color: var(--red);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.15);
}
html.dark-mode .filter-select {
  background: rgba(39, 39, 42, 0.65) !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
}
html.dark-mode .filter-select:hover {
  border-color: rgba(255, 255, 255, 0.25) !important;
  background: rgba(39, 39, 42, 0.85) !important;
}
html.dark-mode .filter-select:focus {
  border-color: rgba(239, 68, 68, 0.6) !important;
  background: rgba(39, 39, 42, 0.95) !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
  outline: none !important;
}
html.dark-mode .filter-select option {
  background: #18181b !important;
  color: #f4f4f5 !important;
}

/* Loading indicator (Non-blocking) */
.calendar-loading-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  background: rgba(79, 70, 229, 0.1);
  color: #4f46e5;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid rgba(79, 70, 229, 0.2);
  transition: opacity 0.2s ease;
}
html.dark-mode .calendar-loading-badge {
  background: rgba(99, 102, 241, 0.15);
  color: #818cf8;
  border-color: rgba(99, 102, 241, 0.3);
}
.calendar-loading-spinner {
  width: 12px;
  height: 12px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Calendar Container & FullCalendar */
#calendar-container {
  min-height: 75vh;
  width: 100%;
  position: relative;
  display: block;
}
#calendar {
  min-height: 75vh;
  width: 100%;
  display: block;
}
.fc .fc-toolbar-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--dark);
}
html.dark-mode .fc .fc-toolbar-title {
  color: #f4f4f5 !important;
}
.fc .fc-toolbar {
  gap: 12px !important;
  flex-wrap: wrap !important;
}
.fc .fc-button-group {
  display: inline-flex !important;
  gap: 6px !important;
}
.fc-direction-ltr .fc-button-group > .fc-button,
.fc-direction-rtl .fc-button-group > .fc-button,
.fc .fc-button-group > .fc-button {
  margin: 0 !important;
  border-radius: 10px !important;
}
.fc .fc-button-primary,
.fc .fc-button {
  background: #f8fafc;
  border: 1px solid var(--line);
  color: #1e293b;
  font-weight: 700;
  border-radius: 10px !important;
  padding: 9px 16px !important;
  box-shadow: none;
  text-shadow: none;
  transition: all .2s ease;
}
.fc .fc-button-primary:hover,
.fc .fc-button:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
  color: #0f172a;
  transform: translateY(-1px);
}
html.dark-mode .fc .fc-button-primary,
html.dark-mode .fc .fc-button {
  background: rgba(255, 255, 255, 0.08) !important;
  background-color: rgba(255, 255, 255, 0.08) !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  border-color: rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
  font-weight: 700 !important;
  border-radius: 10px !important;
  box-shadow: none !important;
}
html.dark-mode .fc .fc-button-primary:hover,
html.dark-mode .fc .fc-button:hover {
  background: rgba(255, 255, 255, 0.16) !important;
  background-color: rgba(255, 255, 255, 0.16) !important;
  border-color: rgba(255, 255, 255, 0.28) !important;
  color: #ffffff !important;
}
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active,
.fc .fc-button-primary.fc-button-active,
.fc .fc-button.fc-button-active {
  background: linear-gradient(135deg, #e83243, #c91d2e) !important;
  background-color: #dc2637 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  font-weight: 800 !important;
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.45) !important;
  opacity: 1 !important;
}
.fc-event {
  cursor: pointer;
  border-radius: 6px;
  padding: 3px 6px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.fc-event:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* Event Pill Elements */
.fc-event-custom {
  display: flex;
  align-items: center;
  gap: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fc-event-branch-badge {
  display: inline-block;
  font-size: 9px;
  font-weight: 800;
  padding: 1px 4px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.25);
  margin-inline-end: 3px;
}
.fc-event-tag {
  font-weight: 700;
}

/* Modal styling */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  z-index: 100;
  display: none;
  place-items: center;
  padding: 16px;
}
.modal-backdrop.open { display: grid; }
html.dark-mode .modal-backdrop {
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
}
.modal-dialog {
  background: #fff;
  border-radius: 20px;
  border: 1px solid var(--line);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: min(580px, 100%);
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  padding: 24px;
}
html.dark-mode .modal-dialog {
  background: #18181b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
  color: #f4f4f5;
}
.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--line);
}
html.dark-mode .modal-head {
  border-bottom-color: rgba(255, 255, 255, 0.08);
}
.modal-head h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: var(--dark);
}
html.dark-mode .modal-head h3 {
  color: #f4f4f5;
}
.modal-close {
  background: none;
  border: none;
  font-size: 22px;
  color: var(--muted);
  cursor: pointer;
  transition: color .2s;
  padding: 0;
  line-height: 1;
}
.modal-close:hover { color: var(--red); }
html.dark-mode .modal-close { color: #a1a1aa; }
html.dark-mode .modal-close:hover { color: #ef4444; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: span 2; }
.form-label { font-size: 13px; font-weight: 700; color: #334155; }
html.dark-mode .form-label { color: #e4e4e7; }
.form-control {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: #fafbfc;
  font-size: 14px;
  color: var(--dark);
  transition: .2s;
}
.form-control:focus {
  outline: none;
  border-color: var(--red);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.15);
}
html.dark-mode .form-control {
  background: rgba(39, 39, 42, 0.65) !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
}
html.dark-mode .form-control:focus {
  border-color: rgba(239, 68, 68, 0.6) !important;
  background: rgba(39, 39, 42, 0.95) !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
}
html.dark-mode .form-control option {
  background: #18181b !important;
  color: #f4f4f5 !important;
}
textarea.form-control { min-height: 90px; resize: vertical; }

.sync-status-box {
  padding: 10px 14px;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--dark);
}
html.dark-mode .sync-status-box {
  background: rgba(255, 255, 255, 0.04) !important;
  border-color: rgba(255, 255, 255, 0.14) !important;
  color: #f4f4f5 !important;
}
.btn-sync {
  padding: 4px 10px;
  font-size: 11px;
}

.modal-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--line);
}
html.dark-mode .modal-actions {
  border-top-color: rgba(255, 255, 255, 0.08);
}
.modal-actions-end {
  display: flex;
  gap: 8px;
}

/* Lead Preview Modal Specific Cards */
.lead-info-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}
html.dark-mode .lead-info-card {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
}
 .quick-reschedule-box {
   background: var(--bg, #f1f5f9);
   border: 1px solid var(--line, #e2e8f0);
   border-radius: 12px;
   padding: 12px;
   margin-bottom: 16px;
 }
 .quick-reschedule-heading {
   font-weight: 700;
   font-size: 13px;
   margin-bottom: 8px;
   color: var(--dark, #334155);
 }
 html.dark-mode .quick-reschedule-box {
   background: rgba(255, 255, 255, 0.04) !important;
   border-color: rgba(255, 255, 255, 0.08) !important;
 }
 html.dark-mode .quick-reschedule-heading {
   color: #f4f4f5 !important;
 }
 .lead-info-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.lead-donor-name {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}
html.dark-mode .lead-donor-name {
  color: #f4f4f5;
}
.lead-badges-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.crm-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}
.crm-badge-branch {
  background: #e0e7ff;
  color: #4338ca;
}
html.dark-mode .crm-badge-branch {
  background: rgba(99, 102, 241, 0.2);
  color: #a5b4fc;
}
.crm-badge-status {
  background: #f1f5f9;
  color: #334155;
}
html.dark-mode .crm-badge-status {
  background: rgba(255, 255, 255, 0.1);
  color: #e4e4e7;
}

.lead-data-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.lead-data-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.lead-data-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
}
.lead-data-value {
  font-size: 13px;
  font-weight: 700;
  color: var(--dark);
}
html.dark-mode .lead-data-value {
  color: #f4f4f5;
}
.lead-phone-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #059669;
  font-weight: 800;
  direction: ltr;
}
.lead-phone-link:hover {
  text-decoration: underline;
}
.lead-notes-box {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 12px;
  font-size: 13px;
  line-height: 1.5;
  color: var(--dark);
}
html.dark-mode .lead-notes-box {
  background: rgba(24, 24, 27, 0.6);
  border-color: rgba(255, 255, 255, 0.08);
  color: #f4f4f5;
}

/* Floating custom tooltip */
.cal-tooltip {
  position: absolute;
  z-index: 1000;
  padding: 8px 12px;
  background: #1e293b;
  color: #fff;
  border-radius: 8px;
  font-size: 11px;
  line-height: 1.4;
  pointer-events: none;
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  display: none;
  max-width: 250px;
}
html.dark-mode .cal-tooltip {
  background: #09090b;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

@media(max-width: 992px) {
  .app { flex-direction: column; }
  .side { display: none; }
  .main-content { width: 100%; padding: 16px; }
  .form-grid, .lead-data-grid { grid-template-columns: 1fr; }
  .form-group.full { grid-column: span 1; }
}
</style>
</head>
<body>
<div class="app">
  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1 data-ar-label="{{ __('crm.calendar_and_events', [], 'ar') }}">{{ __('crm.calendar_and_events') }}</h1>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <button class="btn-secondary" id="openIcalFeedModalBtn" type="button" style="display:inline-flex;align-items:center;gap:6px;">
          <i class="bi bi-cloud-arrow-down-fill"></i>
          <span>{{ __('crm.sync_external_calendar') ?? 'اشتراك التقويم (iCal)' }}</span>
        </button>
        @can('calendar.manage')
        <button class="btn-primary" id="openCreateModalBtn" type="button">
          <i class="bi bi-plus-lg"></i>
          {{ __('crm.add_event') }}
        </button>
        @endcan
        @include('partials.profile-dropdown')
      </div>
    </div>

    <div id="reminderAlertBanner" class="reminder-alert-banner" style="display:none;">
      <i class="bi bi-bell-fill reminder-alert-icon"></i>
      <span id="reminderAlertText">{{ __('crm.upcoming_events_attention') }}</span>
    </div>

    <div class="card">
      <div class="filters-bar">
        <div class="filters-left">
          {{-- Branch Filter (For Admins / Multi-Branch) --}}
          @if(!empty($branches) && $branches->count() > 0 && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission(\App\Security\CrmPermission::BRANCHES_VIEW)))
          <div class="filter-group">
            <span class="filter-label"><i class="bi bi-building"></i> {{ __('crm.branch') }}</span>
            <select class="filter-select" id="filterBranch">
              <option value="all" {{ ($currentBranchId === null || $currentBranchId === 'all') ? 'selected' : '' }}>{{ __('crm.all_branches') }}</option>
              @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ (string)$currentBranchId === (string)$b->id ? 'selected' : '' }}>
                {{ $b->name_ar }} {{ $b->name_en ? '('.$b->name_en.')' : '' }}
              </option>
              @endforeach
            </select>
          </div>
          @endif

          {{-- Type Filter --}}
          <div class="filter-group">
            <span class="filter-label">{{ __('crm.type_label') }}</span>
            <select class="filter-select" id="filterType">
              <option value="">{{ __('crm.all') }}</option>
              <option value="lead_followup">{{ __('crm.leads_donors_followup_dates') }}</option>
              <option value="collection">{{ __('crm.collections') ?? 'تحصيلات ميدانية' }}</option>
              <option value="meeting">{{ __('crm.meeting') }}</option>
              <option value="call">{{ __('crm.call') }}</option>
              <option value="task">{{ __('crm.task') }}</option>
              <option value="reminder">{{ __('crm.reminder') }}</option>
            </select>
          </div>

          {{-- Status Filter --}}
          <div class="filter-group">
            <span class="filter-label">{{ __('crm.status_label') }}</span>
            <select class="filter-select" id="filterStatus">
              <option value="">{{ __('crm.all') }}</option>
              <option value="scheduled">{{ __('crm.scheduled') }}</option>
              <option value="overdue">{{ __('crm.overdue_single') }}</option>
              <option value="completed">{{ __('crm.completed') }}</option>
              <option value="canceled">{{ __('crm.canceled') }}</option>
            </select>
          </div>

          {{-- Responsible User Filter --}}
          @if(!empty($managedStaff) && $managedStaff->count() > 1)
          <div class="filter-group">
            <span class="filter-label"><i class="bi bi-person-badge"></i> {{ __('crm.responsible_label') }}</span>
            <select class="filter-select" id="filterUser">
              <option value="">{{ __('crm.all_supervised_staff') ?? 'جميع الموظفين' }}</option>
              @foreach($managedStaff as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
          </div>
          @endif
        </div>

        <div class="filters-right">
          <div id="calendarLoadingSpinner" class="calendar-loading-badge" style="display:none;">
            <div class="calendar-loading-spinner"></div>
            <span>{{ __('crm.updating_ellipsis') }}</span>
          </div>
        </div>
      </div>

      <div id="calendar-container">
        <div id="calendar"></div>
      </div>
    </div>
  </main>

  {{-- Sidebar Partial --}}
  @include('partials.crm-sidebar')
</div>

<!-- Floating Tooltip -->
<div id="calendarTooltip" class="cal-tooltip"></div>

<!-- 1. Lead / Donor Follow-up Details Modal -->
<div class="modal-backdrop" id="leadFollowupModal">
  <div class="modal-dialog">
    <div class="modal-head">
      <h3 id="leadModalHeaderTitle"><i class="bi bi-person-badge"></i> {{ __('crm.lead_followup_details_title') }}</h3>
      <button class="modal-close" id="closeLeadModalBtn" type="button">&times;</button>
    </div>

    <div class="lead-info-card">
      <div class="lead-info-header">
        <div class="lead-donor-name">
          <span id="previewDonorName">—</span>
        </div>
        <div class="lead-badges-row">
          <span class="crm-badge crm-badge-branch" id="previewBranchBadge"><i class="bi bi-geo-alt-fill"></i> —</span>
          <span class="crm-badge crm-badge-status" id="previewStageBadge">—</span>
        </div>
      </div>

      <div class="lead-data-grid">
        <div class="lead-data-item">
          <span class="lead-data-label"><i class="bi bi-telephone-fill"></i> {{ __('crm.phone_number') }}</span>
          <div style="display:flex;align-items:center;gap:8px;">
            <a href="#" id="previewPhoneLink" class="lead-phone-link" target="_blank">
              <span id="previewPhoneText">—</span>
            </a>
            <button type="button" id="copyPhoneBtn" class="btn-secondary" style="padding:2px 8px;font-size:11px;" title="{{ __('crm.copy_number') }}">
              <i class="bi bi-copy"></i>
            </button>
          </div>
        </div>

        <div class="lead-data-item">
          <span class="lead-data-label"><i class="bi bi-person-fill"></i> {{ __('crm.assigned_employee') }}</span>
          <span class="lead-data-value" id="previewAssignedUser">—</span>
        </div>

        <div class="lead-data-item">
          <span class="lead-data-label"><i class="bi bi-bullseye"></i> {{ __('crm.donation_goal_target') }}</span>
          <span class="lead-data-value" id="previewDonationTarget">—</span>
        </div>

        <div class="lead-data-item">
          <span class="lead-data-label"><i class="bi bi-cash-stack"></i> {{ __('crm.donation_amount_type') }}</span>
          <span class="lead-data-value" id="previewDonationValue">—</span>
        </div>

        <div class="lead-data-item full" style="grid-column: span 2;">
          <span class="lead-data-label"><i class="bi bi-clock-fill"></i> {{ __('crm.scheduled_followup_time') }}</span>
          <span class="lead-data-value" id="previewFollowupTime" style="color:var(--red);font-size:14px;">—</span>
        </div>
      </div>

      {{-- Quick Communications Tray --}}
      <div id="modalQuickCommsTray" style="display:flex;align-items:center;gap:8px;margin:12px 0;padding:10px 14px;background:var(--bg);border:1px solid var(--line);border-radius:12px;flex-wrap:wrap;">
        <span style="font-size:12px;font-weight:800;color:var(--muted);display:flex;align-items:center;gap:5px;">
          <i class="bi bi-broadcast"></i> {{ __('crm.quick_contact') ?? 'تواصل مباشر:' }}
        </span>
        <a href="#" id="modalSipDialBtn" class="btn-primary" style="padding:6px 12px;font-size:12px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;" title="{{ __('crm.call') ?? 'اتصال' }}">
          <i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.call') ?? 'اتصال WebRTC' }}
        </a>
        <a href="#" id="modalWhatsappBtn" target="_blank" rel="noopener" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;background:#16a34a;border-color:#16a34a;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:6px;" title="{{ __('crm.whatsapp_chat') }}">
          <i class="bi bi-whatsapp"></i> WhatsApp
        </a>
      </div>
      <div class="lead-data-item">
        <span class="lead-data-label"><i class="bi bi-chat-left-text-fill"></i> {{ __('crm.notes_and_contact_details') }}</span>
        <div class="lead-notes-box" id="previewNotesText">—</div>
      </div>
    </div>

    {{-- Quick Reschedule Inline Form --}}
    @can('calendar.manage')
    <div id="quickRescheduleBox" class="quick-reschedule-box" style="display:none;">
      <div class="quick-reschedule-heading">
        <i class="bi bi-calendar-event"></i> {{ __('crm.set_new_followup_date') }}
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="datetime-local" id="quickRescheduleInput" class="form-control" style="flex:1;min-width:200px;">
        <button type="button" id="saveQuickRescheduleBtn" class="btn-primary" style="padding:8px 16px;">{{ __('crm.save_date') }}</button>
        <button type="button" id="cancelQuickRescheduleBtn" class="btn-secondary" style="padding:8px 12px;">{{ __('crm.cancel') }}</button>
      </div>
    </div>
    @endcan

    <div class="modal-actions">
      <div>
        @can('calendar.manage')
        <button class="btn-secondary" id="toggleQuickRescheduleBtn" type="button">
          <i class="bi bi-calendar-check"></i> {{ __('crm.reschedule') }}
        </button>
        @endcan
      </div>
      <div class="modal-actions-end">
        <button class="btn-secondary" id="closeLeadModalActionBtn" type="button">{{ __('crm.cancel') }}</button>
        <a href="#" id="previewLeadFullProfileBtn" class="btn-primary" target="_self">
          <i class="bi bi-box-arrow-up-right"></i> {{ __('crm.view_full_lead_profile') }}
        </a>
      </div>
    </div>
  </div>
</div>

<!-- 2. Manual Event Create / Edit Modal Form -->
<div class="modal-backdrop" id="eventModal">
  <div class="modal-dialog">
    <div class="modal-head">
      <h3 id="modalTitleText">{{ __('crm.add_event') }}</h3>
      <button class="modal-close" id="closeModalBtn" type="button">&times;</button>
    </div>
    <div id="eventConflictWarning" class="alert warning" style="display:none;margin:0 24px 16px;padding:10px 14px;border-radius:10px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);color:#b45309;font-size:12px;font-weight:700;">
      <i class="bi bi-exclamation-triangle-fill"></i> <span id="conflictWarningText"></span>
    </div>

    <form id="eventForm">
      <input type="hidden" id="eventId" name="id" value="">

      <div class="form-grid">
        <div class="form-group full">
          <label class="form-label" for="eventTitle">{{ __('crm.event_title') }} <span style="color:var(--red)">*</span></label>
          <input class="form-control" id="eventTitle" name="title" type="text" placeholder="{{ __('crm.event_title_placeholder') }}" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="eventType">{{ __('crm.event_type') }}</label>
          <select class="form-control" id="eventType" name="type" required>
            <option value="meeting">{{ __('crm.meeting') }}</option>
            <option value="call">{{ __('crm.phone_call') }}</option>
            <option value="task">{{ __('crm.work_task') }}</option>
            <option value="reminder">{{ __('crm.reminder') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="eventStatus">{{ __('crm.status') }}</label>
          <select class="form-control" id="eventStatus" name="status" required>
            <option value="scheduled">{{ __('crm.scheduled') }}</option>
            <option value="completed">{{ __('crm.completed') }}</option>
            <option value="canceled">{{ __('crm.canceled') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="eventStartTime">{{ __('crm.start_time') }} <span style="color:var(--red)">*</span></label>
          <input class="form-control" id="eventStartTime" name="start_time" type="datetime-local" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="eventEndTime">{{ __('crm.end_time') }} <span style="color:var(--red)">*</span></label>
          <input class="form-control" id="eventEndTime" name="end_time" type="datetime-local" required>
        </div>

        {{-- Branch Selection if Admin --}}
        @if(!empty($branches) && $branches->count() > 0 && auth()->user()->isSuperAdmin())
        <div class="form-group full">
          <label class="form-label" for="eventBranchId">{{ __('crm.branch') }}</label>
          <select class="form-control" id="eventBranchId" name="branch_id">
            <option value="">{{ __('crm.default_branch_client') }}</option>
            @foreach($branches as $b)
            <option value="{{ $b->id }}">{{ $b->name_ar }} {{ $b->name_en ? '('.$b->name_en.')' : '' }}</option>
            @endforeach
          </select>
        </div>
        @endif

        <div class="form-group full">
          <label class="form-label" for="eventLeadId">{{ __('crm.linked_client_optional') }}</label>
          <select class="form-control" id="eventLeadId" name="lead_id">
            <option value="">{{ __('crm.no_client_option') }}</option>
            @foreach($leads as $lead)
            <option value="{{ $lead->id }}">{{ $lead->name }} {{ $lead->company_name ? "({$lead->company_name})" : '' }}</option>
            @endforeach
          </select>
        </div>

        @if($assignableUsers->count() > 1)
        <div class="form-group full">
          <label class="form-label" for="eventUserId">{{ __('crm.event_owner') }}</label>
          <select class="form-control" id="eventUserId" name="user_id">
            @foreach($assignableUsers as $u)
            <option value="{{ $u->id }}" {{ $u->id === auth()->id() ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        @endif

        <div class="form-group full">
          <label class="form-label" for="eventReminderMinutes">{{ __('crm.reminder_before') }}</label>
          <select class="form-control" id="eventReminderMinutes" name="reminder_minutes_before">
            <option value="15" selected>{{ __('crm.before_15_minutes') }}</option>
            <option value="30">{{ __('crm.before_30_minutes') }}</option>
            <option value="60">{{ __('crm.before_one_hour') }}</option>
            <option value="120">{{ __('crm.before_two_hours') }}</option>
            <option value="1440">{{ __('crm.before_one_day') }}</option>
            <option value="0">{{ __('crm.at_event_time') }}</option>
          </select>
        </div>

        <div class="form-group full">
          <label class="form-label" for="eventDescription">{{ __('crm.details_notes') }}</label>
          <textarea class="form-control" id="eventDescription" name="description" placeholder="{{ __('crm.details_placeholder') }}"></textarea>
        </div>

        <div id="syncStatusWrapper" class="form-group full" style="display:none">
          <div class="sync-status-box">
            <span><i class="bi bi-cloud-check-fill" style="color:#0284c7"></i> {{ __('crm.external_sync') }} <strong id="syncStatusText">{{ __('crm.not_synced') }}</strong></span>
            @can('calendar.manage')
            <button type="button" id="syncEventBtn" class="btn-secondary btn-sync">
              <i class="bi bi-arrow-repeat"></i> {{ __('crm.sync_google_outlook') }}
            </button>
            @endcan
          </div>
        </div>
      </div>

      <div class="modal-actions">
        <div>
          <button class="btn-danger" id="deleteEventBtn" type="button" style="display:none">
            <i class="bi bi-trash"></i> {{ __('crm.delete') }}
          </button>
        </div>
        <div class="modal-actions-end">
          <button class="btn-secondary" id="cancelModalBtn" type="button">{{ __('crm.cancel') }}</button>
          <button class="btn-primary" id="saveEventBtn" type="submit">{{ __('crm.save_event') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- 3. iCal / External Calendar Subscription Modal -->
<div class="modal-backdrop" id="icalFeedModal">
  <div class="modal-dialog">
    <div class="modal-head">
      <h3><i class="bi bi-cloud-arrow-down-fill" style="color:var(--red);"></i> {{ __('crm.sync_external_calendar') ?? 'الاشتراك في تقويم iCal' }}</h3>
      <button class="modal-close" id="closeIcalModalBtn" type="button">&times;</button>
    </div>

    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">
      <p style="margin:0;font-size:13px;color:var(--muted);line-height:1.6;">
        {{ __('crm.ical_subscription_description') ?? 'يمكنك مزامنة أحداثك ومواعيد المتابعة والتحصيلات تلقائياً مع تطبيق التقويم على هاتفك (Apple Calendar أو Google Calendar أو Microsoft Outlook).' }}
      </p>

      <div class="form-group full">
        <label class="form-label">{{ __('crm.calendar_feed_url') ?? 'رابط تغذية التقويم الخاص بك (iCal Feed URL)' }}</label>
        <div style="display:flex;gap:8px;">
          <input class="form-control" id="icalFeedUrlInput" type="text" value="{{ $iCalFeedUrl ?? '' }}" readonly dir="ltr" style="font-family:monospace;font-size:11px;">
          <button type="button" id="copyIcalFeedUrlBtn" class="btn-primary" style="padding:0 14px;white-space:nowrap;">
            <i class="bi bi-clipboard-check"></i> {{ __('crm.copy') ?? 'نسخ' }}
          </button>
        </div>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--bg);border:1px solid var(--line);border-radius:10px;">
        <span style="font-size:12px;font-weight:700;color:var(--dark);"><i class="bi bi-file-earmark-arrow-down"></i> {{ __('crm.direct_download_ics') ?? 'تحميل ملف التقويم مباشرة' }}</span>
        <a href="{{ $iCalFeedUrl ?? '#' }}" download class="btn-secondary" style="font-size:12px;padding:6px 12px;text-decoration:none;">
          <i class="bi bi-download"></i> .ICS
        </a>
      </div>
    </div>

    <div class="modal-actions">
      <div class="modal-actions-end">
        <button class="btn-secondary" id="closeIcalModalActionBtn" type="button">{{ __('crm.close') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- FullCalendar JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : '';
  const calendarEl = document.getElementById('calendar');

  if (!calendarEl) {
    console.error('Calendar element #calendar not found');
    return;
  }

  // Modals
  const eventModal = document.getElementById('eventModal');
  const eventForm = document.getElementById('eventForm');
  const modalTitleText = document.getElementById('modalTitleText');
  const deleteBtn = document.getElementById('deleteEventBtn');

  const leadFollowupModal = document.getElementById('leadFollowupModal');
  const quickRescheduleBox = document.getElementById('quickRescheduleBox');
  const quickRescheduleInput = document.getElementById('quickRescheduleInput');
  let activeLeadIdForReschedule = null;

  // Tooltip
  const tooltipEl = document.getElementById('calendarTooltip');

  // Loading indicator
  const loadingSpinner = document.getElementById('calendarLoadingSpinner');

  // Filters
  const filterBranch = document.getElementById('filterBranch');
  const filterType = document.getElementById('filterType');
  const filterStatus = document.getElementById('filterStatus');
  const filterUser = document.getElementById('filterUser');

  let calendar = null;

  function initCalendar() {
    try {
      calendar = new FullCalendar.Calendar(calendarEl, {
        direction: '{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}',
        locale: '{{ app()->getLocale() }}',
        height: 'auto',
        aspectRatio: 1.65,
        editable: true,
        droppable: true,
        selectable: true,
        headerToolbar: {
          start: 'prev,next today',
          center: 'title',
          end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
          today: '{{ __('crm.today') }}',
          month: '{{ __('crm.month_view') }}',
          week: '{{ __('crm.week_view') }}',
          day: '{{ __('crm.day_view') }}',
          list: '{{ __('crm.list_view') }}'
        },
        loading: function(isLoading) {
          if (loadingSpinner) {
            loadingSpinner.style.display = isLoading ? 'inline-flex' : 'none';
          }
        },
        events: function(fetchInfo, successCallback, failureCallback) {
          let url = '{{ route("v2.calendar.events") }}?start=' + encodeURIComponent(fetchInfo.startStr) + '&end=' + encodeURIComponent(fetchInfo.endStr);
          if (filterBranch && filterBranch.value) url += '&branch_id=' + encodeURIComponent(filterBranch.value);
          if (filterType && filterType.value) url += '&type=' + encodeURIComponent(filterType.value);
          if (filterStatus && filterStatus.value) url += '&status=' + encodeURIComponent(filterStatus.value);
          if (filterUser && filterUser.value) url += '&user_id=' + encodeURIComponent(filterUser.value);

          fetch(url, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('HTTP error ' + response.status);
            }
            return response.json();
          })
          .then(data => {
            const list = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
            successCallback(list);
          })
          .catch(error => {
            console.error('Error fetching events:', error);
            if (failureCallback) failureCallback(error);
            else successCallback([]);
          });
        },
        eventContent: function(arg) {
          const props = arg.event.extendedProps || {};
          const title = arg.event.title || '';
          const branchName = props.branch_name ? `<span class="fc-event-branch-badge">${escapeHtml(props.branch_name)}</span>` : '';
          
          let html = `<div class="fc-event-custom">${branchName}<span class="fc-event-tag">${escapeHtml(title)}</span></div>`;
          return { html: html };
        },
        eventMouseEnter: function(info) {
          showEventTooltip(info);
        },
        eventMouseLeave: function() {
          hideEventTooltip();
        },
        select: function(info) {
          openModalForCreate(info.startStr, info.endStr);
        },
        eventClick: function(info) {
          hideEventTooltip();
          handleEventClick(info.event);
        },
        eventDrop: function(info) {
          rescheduleAnyEvent(info.event, info.revert);
        },
        eventResize: function(info) {
          rescheduleAnyEvent(info.event, info.revert);
        }
      });

      calendar.render();
    } catch (err) {
      console.error('Calendar initialization error:', err);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function showEventTooltip(info) {
    if (!tooltipEl) return;
    const props = info.event.extendedProps || {};
    
    let content = `<strong>${escapeHtml(info.event.title)}</strong><br>`;
    if (props.lead_name) content += `<i class="bi bi-person"></i> @json(__('crm.donor')): ${escapeHtml(props.lead_name)}<br>`;
    if (props.branch_name) content += `<i class="bi bi-building"></i> @json(__('crm.branch')): ${escapeHtml(props.branch_name)}<br>`;
    if (props.donation_target) content += `<i class="bi bi-bullseye"></i> @json(__('crm.goal')): ${escapeHtml(props.donation_target)}<br>`;
    if (props.donation_value) content += `<i class="bi bi-cash"></i> @json(__('crm.amount')): ${escapeHtml(props.donation_value)} @json(__('crm.currency_egp'))<br>`;
    if (props.status_name || props.status) content += `<i class="bi bi-flag"></i> @json(__('crm.status')): ${escapeHtml(props.status_name || props.status)}<br>`;
    if (props.user_name) content += `<i class="bi bi-person-check"></i> @json(__('crm.assigned_employee')): ${escapeHtml(props.user_name)}<br>`;
    
    tooltipEl.innerHTML = content;
    tooltipEl.style.display = 'block';

    const rect = info.el.getBoundingClientRect();
    tooltipEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    tooltipEl.style.left = (rect.left + window.scrollX) + 'px';
  }

  function hideEventTooltip() {
    if (tooltipEl) tooltipEl.style.display = 'none';
  }

  function handleEventClick(fcEvent) {
    const props = fcEvent.extendedProps || {};

    if (props.is_lead_followup || String(fcEvent.id).startsWith('lead_followup_') || String(fcEvent.id).startsWith('lead_contact_')) {
      openLeadFollowupPreview(fcEvent);
    } else {
      openModalForEdit(fcEvent);
    }
  }

  function openLeadFollowupPreview(fcEvent) {
    const props = fcEvent.extendedProps || {};
    activeLeadIdForReschedule = props.lead_id || props.raw_id;

    const isCollection = props.is_collection || props.type === 'collection' || String(fcEvent.id).startsWith('collection_case_');
    const modalHeaderTitle = document.getElementById('leadModalHeaderTitle');

    if (isCollection) {
      if (modalHeaderTitle) modalHeaderTitle.innerHTML = '<i class="bi bi-box-seam" style="color:#8b5cf6;"></i> @json(__('crm.collection_details') ?? 'تفاصيل زيارة التحصيل')';
    } else {
      if (modalHeaderTitle) modalHeaderTitle.innerHTML = '<i class="bi bi-person-badge" style="color:#3b82f6;"></i> @json(__('crm.lead_followup_details_title'))';
    }

    document.getElementById('previewDonorName').textContent = props.lead_name || @json(__('crm.donor'));
    document.getElementById('previewBranchBadge').innerHTML = props.branch_name ? '<i class="bi bi-geo-alt-fill"></i> ' + escapeHtml(props.branch_name) : '<i class="bi bi-geo-alt-fill"></i> ' + escapeHtml(@json(__('crm.main_branch')));
    document.getElementById('previewStageBadge').textContent = isCollection ? (@json(__('crm.collection_case') ?? 'حالة تحصيل') + ' (' + (props.status || '') + ')') : (props.stage_name || props.status_name || @json(__('crm.scheduled')));
    
    const phone = props.lead_phone || '—';
    document.getElementById('previewPhoneText').textContent = phone;
    document.getElementById('previewPhoneLink').href = phone !== '—' ? 'tel:' + phone.replace(/\s+/g, '') : '#';

    // Quick Comms Tray (MicroSIP & WhatsApp)
    const phoneDigits = String(phone).replace(/[^0-9]/g, '');
    const modalSipBtn = document.getElementById('modalSipDialBtn');
    const modalWaBtn = document.getElementById('modalWhatsappBtn');
    if (modalSipBtn) {
      if (phoneDigits) {
        modalSipBtn.setAttribute('data-voice-dial', phoneDigits);
        modalSipBtn.setAttribute('href', 'tel:' + phoneDigits);
        modalSipBtn.setAttribute('data-lead-name', event.title || '');
        modalSipBtn.style.display = 'inline-flex';
        modalSipBtn.onclick = (e) => {
          e.preventDefault();
          // MicroSIP Integration via tel: protocol
          window.location.href = 'tel:' + phoneDigits;
          /*
          // Sokrat Voice Softphone Launcher (commented out in favor of MicroSIP)
          if (typeof window.sokratVoiceDial === 'function') {
            window.sokratVoiceDial(phoneDigits, event.title || '');
          }
          */
        };
      } else {
        modalSipBtn.style.display = 'none';
      }
    }
    if (modalWaBtn) {
      if (phoneDigits) {
        let waPhone = phoneDigits;
        if (waPhone.startsWith('01') && waPhone.length === 11) waPhone = '20' + waPhone.substring(1);
        modalWaBtn.href = 'https://wa.me/' + waPhone;
        modalWaBtn.style.display = 'inline-flex';
      } else {
        modalWaBtn.style.display = 'none';
      }
    }
    
    document.getElementById('previewAssignedUser').textContent = props.user_name || '—';
    document.getElementById('previewDonationTarget').textContent = props.donation_target || '—';
    
    let donationInfo = '—';
    if (isCollection && props.expected_amount) {
      donationInfo = props.expected_amount + ' ' + @json(__('crm.currency_egp')) + ' (' + @json(__('crm.expected_collection_amount') ?? 'قيمة متوقعة') + ')';
    } else if (props.donation_value) {
      donationInfo = props.donation_value + ' ' + @json(__('crm.currency_egp'));
      if (props.donation_type) donationInfo += ' (' + props.donation_type + ')';
      if (props.donation_cycle) donationInfo += ' / ' + props.donation_cycle;
    } else if (props.donation_type) {
      donationInfo = props.donation_type;
    }
    document.getElementById('previewDonationValue').textContent = donationInfo;

    const localeCode = @json(app()->getLocale() === 'ar' ? 'ar-EG' : 'en-US');
    const dt = fcEvent.start ? fcEvent.start.toLocaleString(localeCode, { dateStyle: 'full', timeStyle: 'short' }) : '—';
    document.getElementById('previewFollowupTime').textContent = dt;
    document.getElementById('previewNotesText').textContent = props.description || props.notes || props.response_details || @json(__('crm.no_notes_recorded'));

    const fullProfileBtn = document.getElementById('previewLeadFullProfileBtn');
    if (fullProfileBtn) {
      if (isCollection && props.collection_url) {
        fullProfileBtn.href = props.collection_url;
        fullProfileBtn.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> @json(__('crm.view_collection_case') ?? 'عرض حالة التحصيل')';
      } else {
        fullProfileBtn.href = props.lead_url || ('/leads/' + activeLeadIdForReschedule);
        fullProfileBtn.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> @json(__('crm.view_full_lead_profile'))';
      }
    }

    if (quickRescheduleBox) quickRescheduleBox.style.display = 'none';

    leadFollowupModal.classList.add('open');
  }

  function closeLeadFollowupModal() {
    leadFollowupModal.classList.remove('open');
  }

  // Reschedule handler supporting both Manual events and Lead Followups
  function rescheduleAnyEvent(fcEvent, revertFunc) {
    const props = fcEvent.extendedProps || {};
    const isLead = props.is_lead_followup || String(fcEvent.id).startsWith('lead_followup_') || String(fcEvent.id).startsWith('lead_contact_');

    if (isLead) {
      const leadId = props.lead_id || props.raw_id || String(fcEvent.id).replace('lead_followup_', '').replace('lead_contact_', '');
      const payload = {
        start_time: fcEvent.start.toISOString(),
        reason: @json(__('crm.reschedule_by_calendar_drag'))
      };

      fetch('/calendar/leads/' + leadId + '/reschedule', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(@json(__('crm.donor_followup_updated_success')));
        } else {
          showToast(data.message || @json(__('crm.reschedule_failed')), true);
          if (revertFunc) revertFunc();
        }
      })
      .catch(err => {
        console.error(err);
        showToast(@json(__('crm.server_connection_error')), true);
        if (revertFunc) revertFunc();
      });

    } else {
      const payload = {
        start_time: fcEvent.start.toISOString(),
        end_time: (fcEvent.end ? fcEvent.end : new Date(fcEvent.start.getTime() + 3600000)).toISOString()
      };

      fetch('/calendar/events/' + fcEvent.id + '/reschedule', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(@json(__('crm.event_rescheduled')));
        } else {
          showToast(data.message || @json(__('crm.reschedule_failed')), true);
          if (revertFunc) revertFunc();
        }
      })
      .catch(error => {
        console.error(error);
        showToast(@json(__('crm.server_connection_error')), true);
        if (revertFunc) revertFunc();
      });
    }
  }

  function checkUpcomingReminders() {
    fetch('{{ route("v2.calendar.reminders") }}?within_minutes=30', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.count > 0) {
        const banner = document.getElementById('reminderAlertBanner');
        const text = document.getElementById('reminderAlertText');
        if (banner && text) {
          text.textContent = @json(__('crm.reminder_30min_warning')).replace(':count', data.count);
          banner.style.display = 'flex';
        }
      }
    })
    .catch(err => console.error(err));
  }

  function showToast(message, isError = false) {
    let container = document.getElementById('calendarToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'calendarToastContainer';
      container.style.cssText = 'position:fixed;bottom:20px;inset-inline-start:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = 'padding:12px 18px;border-radius:12px;background:' + (isError ? '#ef4444' : '#10b981') + ';color:#fff;font-weight:700;box-shadow:0 10px 25px rgba(0,0,0,0.2);font-size:13px;transition:all 0.3s ease;';
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  function openModalForCreate(startStr, endStr) {
    eventForm.reset();
    document.getElementById('eventId').value = '';
    modalTitleText.textContent = @json(__('crm.add_event'));
    deleteBtn.style.display = 'none';

    let now = new Date();
    let startIso = startStr ? new Date(startStr).toISOString().slice(0, 16) : now.toISOString().slice(0, 16);
    let endIso = endStr ? new Date(endStr).toISOString().slice(0, 16) : new Date(now.getTime() + 60*60*1000).toISOString().slice(0, 16);

    document.getElementById('eventStartTime').value = startIso;
    document.getElementById('eventEndTime').value = endIso;

    eventModal.classList.add('open');
  }

  function openModalForEdit(fcEvent) {
    eventForm.reset();
    const props = fcEvent.extendedProps || {};
    document.getElementById('eventId').value = fcEvent.id;
    modalTitleText.textContent = @json(__('crm.edit_event_task'));
    deleteBtn.style.display = 'inline-flex';

    document.getElementById('eventTitle').value = fcEvent.title || '';
    document.getElementById('eventType').value = props.type || 'meeting';
    document.getElementById('eventStatus').value = props.status || 'scheduled';
    document.getElementById('eventDescription').value = props.description || '';
    document.getElementById('eventReminderMinutes').value = props.reminder_minutes_before !== undefined ? props.reminder_minutes_before : 15;

    const branchSelect = document.getElementById('eventBranchId');
    if (branchSelect && props.branch_id) {
      branchSelect.value = props.branch_id;
    }

    const syncWrapper = document.getElementById('syncStatusWrapper');
    const syncText = document.getElementById('syncStatusText');
    if (syncWrapper && syncText) {
      if (props.sync_id) {
        syncText.textContent = (props.provider || 'Google') + ' (' + props.sync_id + ')';
        syncWrapper.style.display = 'block';
      } else {
        syncText.textContent = @json(__('crm.not_synced'));
        syncWrapper.style.display = 'block';
      }
    }

    if (fcEvent.start) {
      document.getElementById('eventStartTime').value = formatDateForInput(fcEvent.start);
    }
    if (fcEvent.end) {
      document.getElementById('eventEndTime').value = formatDateForInput(fcEvent.end);
    } else if (fcEvent.start) {
      document.getElementById('eventEndTime').value = formatDateForInput(fcEvent.start);
    }

    if (props.lead_id) {
      document.getElementById('eventLeadId').value = props.lead_id;
    }

    const userIdSelect = document.getElementById('eventUserId');
    if (userIdSelect && props.user_id) {
      userIdSelect.value = props.user_id;
    }

    eventModal.classList.add('open');
  }

  function closeModal() {
    eventModal.classList.remove('open');
  }

  function formatDateForInput(dateObj) {
    let tzoffset = (new Date()).getTimezoneOffset() * 60000;
    let localISOTime = (new Date(dateObj - tzoffset)).toISOString().slice(0, 16);
    return localISOTime;
  }
  // Real-time Conflict Detection on Event Modal
  const conflictBanner = document.getElementById('eventConflictWarning');
  const conflictText = document.getElementById('conflictWarningText');
  let conflictTimer = null;

  const triggerConflictCheck = () => {
    clearTimeout(conflictTimer);
    conflictTimer = setTimeout(async () => {
      const userVal = document.getElementById('eventUserId')?.value || @json(auth()->id());
      const startVal = document.getElementById('eventStartTime')?.value;
      const endVal = document.getElementById('eventEndTime')?.value;
      const eventIdVal = document.getElementById('eventId')?.value;

      if (!userVal || !startVal || !endVal) {
        if (conflictBanner) conflictBanner.style.display = 'none';
        return;
      }

      try {
        const res = await fetch('/calendar/check-conflict', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            user_id: userVal,
            start_time: startVal,
            end_time: endVal,
            ignore_id: eventIdVal || null
          })
        });
        if (res.ok) {
          const data = await res.json();
          if (data.has_conflict && data.conflicting_event) {
            if (conflictText) conflictText.textContent = `تنبيه تعارض مواعيد: يتداخل مع (${data.conflicting_event.title}) في ${data.conflicting_event.start_time}`;
            if (conflictBanner) conflictBanner.style.display = 'block';
          } else {
            if (conflictBanner) conflictBanner.style.display = 'none';
          }
        }
      } catch (e) {}
    }, 300);
  };

  document.getElementById('eventStartTime')?.addEventListener('change', triggerConflictCheck);
  document.getElementById('eventEndTime')?.addEventListener('change', triggerConflictCheck);
  document.getElementById('eventUserId')?.addEventListener('change', triggerConflictCheck);

  // iCal Feed Modal Handlers
  const icalModal = document.getElementById('icalFeedModal');
  const openIcalBtn = document.getElementById('openIcalFeedModalBtn');
  const closeIcalBtn = document.getElementById('closeIcalModalBtn');
  const closeIcalActionBtn = document.getElementById('closeIcalModalActionBtn');
  const copyIcalBtn = document.getElementById('copyIcalFeedUrlBtn');
  const icalUrlInput = document.getElementById('icalFeedUrlInput');

  if (openIcalBtn && icalModal) {
    openIcalBtn.addEventListener('click', () => icalModal.classList.add('open'));
    closeIcalBtn?.addEventListener('click', () => icalModal.classList.remove('open'));
    closeIcalActionBtn?.addEventListener('click', () => icalModal.classList.remove('open'));
    copyIcalBtn?.addEventListener('click', () => {
      if (icalUrlInput) {
        navigator.clipboard.writeText(icalUrlInput.value).then(() => {
          showToast(@json(__('crm.copied_to_clipboard') ?? 'تم النسخ إلى الحافظة بنجاح.'));
        });
      }
    });
  }

  // Submit handler (Store or Update)
  eventForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const eventId = document.getElementById('eventId').value;
    const isUpdate = !!eventId;

    const payload = {
      title: document.getElementById('eventTitle').value,
      type: document.getElementById('eventType').value,
      status: document.getElementById('eventStatus').value,
      start_time: document.getElementById('eventStartTime').value,
      end_time: document.getElementById('eventEndTime').value,
      lead_id: document.getElementById('eventLeadId').value || null,
      description: document.getElementById('eventDescription').value || null,
      reminder_minutes_before: parseInt(document.getElementById('eventReminderMinutes').value, 10) || 15
    };

    const branchSelect = document.getElementById('eventBranchId');
    if (branchSelect && branchSelect.value) {
      payload.branch_id = branchSelect.value;
    }

    const userIdSelect = document.getElementById('eventUserId');
    if (userIdSelect) {
      payload.user_id = userIdSelect.value;
    }

    const url = isUpdate ? '/calendar/events/' + eventId : '{{ route("v2.calendar.store") }}';
    const method = isUpdate ? 'PATCH' : 'POST';

    fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        closeModal();
        if (calendar) calendar.refetchEvents();
        showToast(data.message || @json(__('crm.event_saved_success')));
      } else {
        alert(data.message || @json(__('crm.save_data_error')));
      }
    })
    .catch(error => {
      console.error(error);
      alert(@json(__('crm.server_error')));
    });
  });

  // Copy Phone Handler
  document.getElementById('copyPhoneBtn')?.addEventListener('click', function() {
    const text = document.getElementById('previewPhoneText').textContent;
    if (text && text !== '—') {
      navigator.clipboard.writeText(text).then(() => {
        showToast(@json(__('crm.phone_copied_toast')));
      });
    }
  });

  // Quick Reschedule Handlers inside Lead Preview Modal
  document.getElementById('toggleQuickRescheduleBtn')?.addEventListener('click', function() {
    if (!quickRescheduleBox) return;
    if (quickRescheduleBox.style.display === 'none') {
      quickRescheduleBox.style.display = 'block';
      let nowPlusOneHour = new Date(Date.now() + 3600000);
      quickRescheduleInput.value = nowPlusOneHour.toISOString().slice(0, 16);
    } else {
      quickRescheduleBox.style.display = 'none';
    }
  });

  document.getElementById('cancelQuickRescheduleBtn')?.addEventListener('click', function() {
    if (quickRescheduleBox) quickRescheduleBox.style.display = 'none';
  });

  document.getElementById('saveQuickRescheduleBtn')?.addEventListener('click', function() {
    if (!activeLeadIdForReschedule || !quickRescheduleInput.value) return;

    fetch('/calendar/leads/' + activeLeadIdForReschedule + '/reschedule', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        start_time: quickRescheduleInput.value,
        reason: @json(__('crm.reschedule_via_calendar_modal'))
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(@json(__('crm.rescheduled_successfully')));
        closeLeadFollowupModal();
        if (calendar) calendar.refetchEvents();
      } else {
        showToast(data.message || @json(__('crm.reschedule_failed')), true);
      }
    })
    .catch(err => {
      console.error(err);
      showToast(@json(__('crm.server_connection_error')), true);
    });
  });

  // External Sync Button Handler
  document.getElementById('syncEventBtn')?.addEventListener('click', function() {
    const eventId = document.getElementById('eventId').value;
    if (!eventId) return;

    fetch('/calendar/events/' + eventId + '/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ provider: 'google' })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast(@json(__('crm.calendar_sync_success')));
        closeModal();
        if (calendar) calendar.refetchEvents();
      } else {
        showToast(data.message || @json(__('crm.sync_failed')), true);
      }
    })
    .catch(err => {
      console.error(err);
      showToast(@json(__('crm.sync_error')), true);
    });
  });

  // Delete handler
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function() {
      const eventId = document.getElementById('eventId').value;
      if (!eventId) return;

      if (!confirm(@json(__('crm.confirm_delete_event')))) return;

      fetch('/calendar/events/' + eventId, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          closeModal();
          if (calendar) calendar.refetchEvents();
          showToast(@json(__('crm.event_deleted')));
        } else {
          showToast(data.message || @json(__('crm.delete_event_failed')), true);
        }
      })
      .catch(error => {
        console.error(error);
        showToast(@json(__('crm.delete_error_toast')), true);
      });
    });
  }

  // Filter change listeners
  if (filterBranch) filterBranch.addEventListener('change', () => calendar && calendar.refetchEvents());
  if (filterType) filterType.addEventListener('change', () => calendar && calendar.refetchEvents());
  if (filterStatus) filterStatus.addEventListener('change', () => calendar && calendar.refetchEvents());
  if (filterUser) filterUser.addEventListener('change', () => calendar && calendar.refetchEvents());

  // Modal open/close listeners
  document.getElementById('openCreateModalBtn')?.addEventListener('click', function() {
    openModalForCreate();
  });
  document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
  document.getElementById('cancelModalBtn')?.addEventListener('click', closeModal);

  document.getElementById('closeLeadModalBtn')?.addEventListener('click', closeLeadFollowupModal);
  document.getElementById('closeLeadModalActionBtn')?.addEventListener('click', closeLeadFollowupModal);

  // Check reminders
  checkUpcomingReminders();
  setInterval(checkUpcomingReminders, 60000);

  // Initialize Calendar
  initCalendar();
});
</script>
<script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
</body>
</html>

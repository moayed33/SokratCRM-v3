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
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<style>
:root {
  --red: #dc2637;
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

/* Layout Structure matching CRM Dashboard */
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
  padding: 10px 20px;
  border-radius: 12px;
  background: var(--red);
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(220, 38, 55, 0.25);
  transition: all .2s ease;
}
.btn-primary:hover {
  background: #b81d2c;
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

/* Reminder Banner */
.reminder-alert-banner {
  margin-bottom: 16px;
  padding: 12px 16px;
  border: 1px solid #fcd34d;
  border-radius: 12px;
  background: #fffbeb;
  color: #92400e;
  font-weight: 700;
  display: flex;
  align-items: center;
}
.reminder-alert-icon {
  margin-inline-end: 8px;
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
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--line);
}
html.dark-mode .filters-bar {
  border-bottom-color: rgba(255, 255, 255, 0.08);
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

/* Calendar Container & FullCalendar Overrides */
#calendar-container {
  min-height: 80vh;
  width: 100%;
  position: relative;
  display: block;
}
#calendar {
  min-height: 80vh;
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
.fc .fc-button-primary,
.fc .fc-button {
  background: #f8fafc;
  border: 1px solid var(--line);
  color: #1e293b;
  font-weight: 700;
  border-radius: 10px;
  padding: 8px 14px;
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
  padding: 2px 6px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
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
  width: min(550px, 100%);
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
  font-size: 20px;
  color: var(--muted);
  cursor: pointer;
  transition: color .2s;
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

@media(max-width: 992px) {
  .app { flex-direction: column; }
  .side { display: none; }
  .main-content { width: 100%; padding: 16px; }
  .form-grid { grid-template-columns: 1fr; }
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
        <p>{{ __('crm.calendar_subtitle') }}</p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
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
        <div class="filter-group">
          <span class="filter-label">{{ __('crm.type_label') }}</span>
          <select class="filter-select" id="filterType">
            <option value="">{{ __('crm.all') }}</option>
            <option value="meeting">{{ __('crm.meeting') }}</option>
            <option value="call">{{ __('crm.call') }}</option>
            <option value="task">{{ __('crm.task') }}</option>
            <option value="reminder">{{ __('crm.reminder') }}</option>
          </select>
        </div>

        <div class="filter-group">
          <span class="filter-label">{{ __('crm.status_label') }}</span>
          <select class="filter-select" id="filterStatus">
            <option value="">{{ __('crm.all') }}</option>
            <option value="scheduled">{{ __('crm.scheduled') }}</option>
            <option value="completed">{{ __('crm.completed') }}</option>
            <option value="canceled">{{ __('crm.canceled') }}</option>
          </select>
        </div>

        @if($assignableUsers->count() > 1)
        <div class="filter-group">
          <span class="filter-label">{{ __('crm.responsible_label') }}</span>
          <select class="filter-select" id="filterUser">
            <option value="">{{ __('crm.all_users') }}</option>
            @foreach($assignableUsers as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        @endif
      </div>

      <div id="calendar-container">
        <div id="calendar"></div>
      </div>
    </div>
  </main>

  {{-- Sidebar Partial --}}
  @include('partials.crm-sidebar')
</div>

<!-- Modal Form -->
<div class="modal-backdrop" id="eventModal">
  <div class="modal-dialog">
    <div class="modal-head">
      <h3 id="modalTitleText">{{ __('crm.add_event') }}</h3>
      <button class="modal-close" id="closeModalBtn" type="button">&times;</button>
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
          <label class="form-label" for="eventStatus">الحالة</label>
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

<!-- FullCalendar JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : '';
  const calendarEl = document.getElementById('calendar');

  if (!calendarEl) {
    console.error('Calendar element #calendar not found');
    return;
  }

  const modal = document.getElementById('eventModal');
  const eventForm = document.getElementById('eventForm');
  const modalTitleText = document.getElementById('modalTitleText');
  const deleteBtn = document.getElementById('deleteEventBtn');

  // Filters
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
          right: 'prev,next today',
          center: 'title',
          left: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
          today: '{{ __('crm.today') }}',
          month: '{{ __('crm.month_view') }}',
          week: '{{ __('crm.week_view') }}',
          day: '{{ __('crm.day_view') }}',
          list: '{{ __('crm.list_view') }}'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
          let url = '{{ route("v2.calendar.events") }}?start=' + encodeURIComponent(fetchInfo.startStr) + '&end=' + encodeURIComponent(fetchInfo.endStr);
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
        select: function(info) {
          openModalForCreate(info.startStr, info.endStr);
        },
        eventClick: function(info) {
          openModalForEdit(info.event);
        },
        eventDrop: function(info) {
          rescheduleEvent(info.event, info.revert);
        },
        eventResize: function(info) {
          rescheduleEvent(info.event, info.revert);
        }
      });

      calendar.render();
      console.log('Calendar initialized');
    } catch (err) {
      console.error('Calendar initialization error:', err);
    }
  }

  function rescheduleEvent(fcEvent, revertFunc) {
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
          text.textContent = @json(__('crm.reminder_prefix')) + data.count + @json(__('crm.reminder_suffix'));
          banner.style.display = 'block';
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
      container.style.cssText = 'position:fixed;bottom:20px;left:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
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

    modal.classList.add('open');
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

    modal.classList.add('open');
  }

  function closeModal() {
    modal.classList.remove('open');
  }

  function formatDateForInput(dateObj) {
    let tzoffset = (new Date()).getTimezoneOffset() * 60000;
    let localISOTime = (new Date(dateObj - tzoffset)).toISOString().slice(0, 16);
    return localISOTime;
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
      } else {
        alert(data.message || @json(__('crm.save_data_error')));
      }
    })
    .catch(error => {
      console.error(error);
      alert(@json(__('crm.server_error')));
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
        showToast('حدث خطأ أثناء عملية الحذف.', true);
      });
    });
  }

  // Filter change listeners
  if (filterType) filterType.addEventListener('change', () => calendar && calendar.refetchEvents());
  if (filterStatus) filterStatus.addEventListener('change', () => calendar && calendar.refetchEvents());
  if (filterUser) filterUser.addEventListener('change', () => calendar && calendar.refetchEvents());

  // Modal open/close listeners
  document.getElementById('openCreateModalBtn')?.addEventListener('click', function() {
    openModalForCreate();
  });
  document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
  document.getElementById('cancelModalBtn')?.addEventListener('click', closeModal);

  // Check reminders
  checkUpcomingReminders();
  setInterval(checkUpcomingReminders, 60000);

  // Initialize Calendar
  initCalendar();
});
</script>
</body>
</html>

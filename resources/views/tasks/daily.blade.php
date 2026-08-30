@extends('leads.transfer-layout')

@section('title', __('crm.daily_tasks'))
@section('page-title', __('crm.daily_tasks'))


@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
 .daily-tasks-page {
  --task-red: #dc2637;
  --task-blue: #3478f6;
  --task-green: #169a64;
  --task-amber: #e59b16;
  --task-purple: #7b61df;
  --task-border: #e4e8ef;
  --task-bg-soft: #f8fafc;
  --task-card-shadow: 0 10px 30px #1720330a;
  --task-card-shadow-hover: 0 16px 36px #17203314;
 }

 html.dark-mode .daily-tasks-page {
  --task-border: rgba(255, 255, 255, 0.08);
  --task-bg-soft: rgba(255, 255, 255, 0.04);
  --task-card-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  --task-card-shadow-hover: 0 16px 36px rgba(0, 0, 0, 0.7);
 }

 html.dark-mode .task-kpi-card {
  background: var(--bg-card, rgba(24, 24, 27, 0.75)) !important;
  border-color: var(--task-border) !important;
  backdrop-filter: var(--glass-blur, blur(16px)) !important;
  -webkit-backdrop-filter: var(--glass-blur, blur(16px)) !important;
 }

 html.dark-mode .task-kpi-info strong {
  color: #f4f4f5 !important;
 }

 html.dark-mode .task-kpi-info span {
  color: var(--text-muted, #a1a1aa) !important;
 }

 html.dark-mode .task-progress-box,
 html.dark-mode .task-filters-card,
 html.dark-mode .task-section-block {
  background: var(--bg-card, rgba(24, 24, 27, 0.75)) !important;
  border-color: var(--task-border) !important;
  backdrop-filter: var(--glass-blur, blur(16px)) !important;
 }

 html.dark-mode .task-progress-head,
 html.dark-mode .task-section-header h2 {
  color: #f4f4f5 !important;
 }

 html.dark-mode .task-section-header {
  background: rgba(255, 255, 255, 0.03) !important;
  border-bottom-color: rgba(255, 255, 255, 0.08) !important;
 }

 html.dark-mode .task-item-card {
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
 }

 html.dark-mode .task-item-card:hover {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
 }

 /* Top summary & KPI cards */
 .task-kpis-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 18px;
 }

 .task-kpi-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border: 1px solid var(--task-border);
  border-radius: 16px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  box-shadow: var(--task-card-shadow);
  transition: transform .18s, border-color .18s, box-shadow .18s;
  position: relative;
  overflow: hidden;
 }

 .task-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--task-card-shadow-hover);
  border-color: #cbd5e1;
 }

 .task-kpi-card.active {
  border-color: currentColor;
  box-shadow: 0 0 0 2px currentColor;
 }

 .task-kpi-icon {
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 20px;
 }

 .task-kpi-card.overdue { color: var(--task-red); }
 .task-kpi-card.overdue .task-kpi-icon { background: #fee2e2; color: var(--task-red); }

 .task-kpi-card.today { color: var(--task-blue); }
 .task-kpi-card.today .task-kpi-icon { background: #dbeafe; color: var(--task-blue); }

 .task-kpi-card.completed { color: var(--task-green); }
 .task-kpi-card.completed .task-kpi-icon { background: #dcfce7; color: var(--task-green); }

 .task-kpi-card.no-date { color: var(--task-amber); }
 .task-kpi-card.no-date .task-kpi-icon { background: #fef3c7; color: var(--task-amber); }

 .task-kpi-info {
  min-width: 0;
  flex: 1;
 }

 .task-kpi-info strong {
  display: block;
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.2;
 }

 .task-kpi-info span {
  display: block;
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
 }

 /* Progress bar strip */
 .task-progress-box {
  margin-bottom: 20px;
  padding: 14px 18px;
  border: 1px solid var(--task-border);
  border-radius: 14px;
  background: #fff;
  box-shadow: var(--task-card-shadow);
 }

 .task-progress-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
 }

 .task-progress-ratio {
  color: #64748b;
  font-size: 12px;
 }

 .task-progress-bar-bg {
  height: 8px;
  border-radius: 999px;
  background: #e2e8f0;
  overflow: hidden;
 }

 .task-progress-bar-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #10b981, #059669);
 }

 /* Filters and control bar */
 .task-filters-card {
  margin-bottom: 24px;
  padding: 16px 20px;
  border: 1px solid var(--task-border);
  border-radius: 16px;
  background: #fff;
  box-shadow: var(--task-card-shadow);
 }

 .task-filters-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
 }

 .task-filters-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
 }

 .task-search-input-wrap {
  flex: 1 1 280px;
  position: relative;
 }

 .task-search-input-wrap i {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 15px;
 }

 [dir="rtl"] .task-search-input-wrap i { right: 12px; }
 [dir="ltr"] .task-search-input-wrap i { left: 12px; }

 .task-search-input {
  width: 100%;
  height: 42px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  color: #1e293b;
  font-size: 13px;
  outline: none;
  transition: border-color .18s, background .18s;
 }

 [dir="rtl"] .task-search-input { padding: 0 38px 0 14px; }
 [dir="ltr"] .task-search-input { padding: 0 14px 0 38px; }

 .task-search-input:focus {
  border-color: #3b82f6;
  background: #fff;
  box-shadow: 0 0 0 3px #3b82f61a;
 }

 .task-select-filter {
  height: 42px;
  min-width: 140px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  color: #334155;
  font-size: 13px;
  outline: none;
  cursor: pointer;
 }

 .task-select-filter:focus {
  border-color: #3b82f6;
  background: #fff;
 }

 .task-scope-pills {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  padding-top: 2px;
  border-top: 0;
 }

 .task-scope-pill {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #f8fafc;
  color: #475569;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
  transition: all .16s;
 }

 .task-scope-pill:hover {
  background: #f1f5f9;
  color: #1e293b;
 }

 .task-scope-pill.active {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #2563eb;
 }

 .task-scope-pill.overdue.active {
  border-color: var(--task-red);
  background: #fef2f2;
  color: var(--task-red);
 }

 .task-scope-pill.today.active {
  border-color: var(--task-blue);
  background: #eff6ff;
  color: var(--task-blue);
 }

 .task-scope-pill.upcoming.active {
  border-color: var(--task-purple);
  background: #faf5ff;
  color: var(--task-purple);
 }

 .task-scope-pill.no-date.active {
  border-color: var(--task-amber);
  background: #fffbeb;
  color: #b45309;
 }

 .task-scope-pill.completed.active {
  border-color: var(--task-green);
  background: #f0fdf4;
  color: var(--task-green);
 }

 .task-pill-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #1e293b;
  font-size: 11px;
  font-weight: 800;
  line-height: 1.2;
 }

 .task-scope-pill.active .task-pill-badge {
  background: #dbeafe;
  color: #1d4ed8;
 }

 .task-scope-pill.overdue.active .task-pill-badge {
  background: #fee2e2;
  color: #b91c1c;
 }

 .task-scope-pill.today.active .task-pill-badge {
  background: #dbeafe;
  color: #1d4ed8;
 }

 .task-scope-pill.upcoming.active .task-pill-badge {
  background: #f3e8ff;
  color: #6b21a8;
 }

 .task-scope-pill.no-date.active .task-pill-badge {
  background: #fef3c7;
  color: #92400e;
 }

 .task-scope-pill.completed.active .task-pill-badge {
  background: #dcfce7;
  color: #15803d;
 }

 .task-view-toggle {
  display: inline-flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
 }

 .task-view-btn {
  height: 40px;
  padding: 0 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
  background: transparent;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all .16s;
 }

 .task-view-btn.active {
  background: #fff;
  color: #1e293b;
  box-shadow: 0 2px 6px #0000000d;
 }

 /* Sections styling */
 .task-section-block {
  margin-bottom: 28px;
  border: 1px solid var(--task-border);
  border-radius: 18px;
  background: #fff;
  box-shadow: var(--task-card-shadow);
  overflow: hidden;
 }

 .task-section-block.overdue-block {
  border-color: #fca5a5;
 }

 .task-section-block.today-block {
  border-color: #bfdbfe;
 }

 .task-section-block.upcoming-block {
  border-color: #e9d5ff;
 }

 .task-section-block.no-date-block {
  border-color: #fde68a;
 }

 .task-section-block.completed-block {
  border-color: #bbf7d0;
 }

 .task-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
 }

 .task-section-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
 }

 .task-section-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
 }

 .overdue-block .task-section-indicator { background: var(--task-red); }
 .today-block .task-section-indicator { background: var(--task-blue); }
 .upcoming-block .task-section-indicator { background: var(--task-purple); }
 .no-date-block .task-section-indicator { background: var(--task-amber); }
 .completed-block .task-section-indicator { background: var(--task-green); }

 .task-section-header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #1e293b;
 }

 .task-section-header p {
  margin: 3px 0 0;
  color: #64748b;
  font-size: 12px;
 }

 .task-section-count-badge {
  min-width: 32px;
  height: 28px;
  display: inline-grid;
  place-items: center;
  padding: 0 8px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #334155;
  font-size: 12px;
  font-weight: 800;
 }

 /* Task Cards Grid */
 .task-items-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 16px;
  padding: 20px;
 }

 .task-item-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 18px;
  border: 1px solid var(--task-border);
  border-radius: 14px;
  background: #fff;
  transition: transform .18s, box-shadow .18s, border-color .18s;
  position: relative;
 }

 .task-item-card:hover {
  transform: translateY(-2px);
  border-color: #cbd5e1;
  box-shadow: 0 12px 28px #1720330f;
 }

 .task-card-topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
 }

 .task-lead-info {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
 }

 .task-lead-avatar {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #e0e7ff;
  color: #3730a3;
  font-size: 16px;
  font-weight: 800;
 }

 .task-lead-names {
  min-width: 0;
 }

 .task-lead-names strong a {
  color: #1e293b;
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
 }

 .task-lead-names strong a:hover {
  color: #2563eb;
 }

 .task-lead-names small {
  display: block;
  margin-top: 2px;
  color: #64748b;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
 }

 .task-status-pill {
  padding: 4px 10px;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
 }

 /* Urgency and follow-up time strip */
 .task-time-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 12px;
  margin-bottom: 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
 }

 .task-time-strip.overdue {
  background: #fef2f2;
  color: var(--task-red);
 }

 .task-time-strip.today {
  background: #eff6ff;
  color: var(--task-blue);
 }

 .task-time-strip.upcoming {
  background: #faf5ff;
  color: var(--task-purple);
 }

 .task-time-strip.no-date {
  background: #fffbeb;
  color: var(--task-amber);
 }

 /* Key metadata grid */
 .task-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-bottom: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 12px;
 }

 .task-meta-item span {
  display: block;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  margin-bottom: 2px;
 }

 .task-meta-item strong {
  display: block;
  color: #334155;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
 }

 .task-phone-link {
  color: #0284c7;
  text-decoration: none;
  font-weight: 700;
  direction: ltr;
  display: inline-block;
 }

 /* Last followup summary box */
 .task-last-followup {
  margin-bottom: 14px;
  padding: 10px 12px;
  border: 1px solid #f1f5f9;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 12px;
 }

 .task-last-followup-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
 }

 .task-last-followup-note {
  color: #334155;
  font-size: 12px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
 }

 /* Action buttons grid */
 .task-actions-toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) repeat(5, 36px);
  align-items: center;
  gap: 6px;
  padding-top: 12px;
  margin-top: auto;
  border-top: 1px solid var(--line, #f1f5f9);
 }

 .task-btn-main {
  height: 36px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0 12px;
  border-radius: 9px;
  background: var(--red, #dc2637);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  border: 1px solid var(--red, #dc2637);
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(220, 38, 55, 0.22);
  transition: all .16s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
  box-sizing: border-box;
 }

 .task-btn-main:hover:not(:disabled) {
  background: #b91c1c;
  border-color: #b91c1c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 55, 0.32);
 }

 .task-btn-main:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #94a3b8;
  border-color: #94a3b8;
  box-shadow: none;
 }

 .task-btn-icon {
  width: 36px;
  height: 36px;
  min-width: 36px;
  min-height: 36px;
  display: inline-grid;
  place-items: center;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 14px;
  text-decoration: none;
  cursor: pointer;
  transition: all .16s ease;
  padding: 0;
  box-sizing: border-box;
  flex-shrink: 0;
 }

 .task-btn-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
 }

 .task-btn-icon.donation {
  color: #16a34a;
  background: #f0fdf4;
  border-color: #bbf7d0;
 }
 .task-btn-icon.donation:hover {
  background: #dcfce7;
  border-color: #86efac;
  color: #15803d;
 }

 .task-btn-icon.log {
  color: #4f46e5;
  background: #eef2ff;
  border-color: #c7d2fe;
 }
 .task-btn-icon.log:hover {
  background: #e0e7ff;
  border-color: #a5b4fc;
  color: #4338ca;
 }

 .task-btn-icon.whatsapp {
  color: #059669;
  background: #ecfdf5;
  border-color: #a7f3d0;
 }
 .task-btn-icon.whatsapp:hover {
  background: #d1fae5;
  border-color: #6ee7b7;
  color: #047857;
 }

 .task-btn-icon.reschedule {
  color: #d97706;
  background: #fffbeb;
  border-color: #fde68a;
 }
 .task-btn-icon.reschedule:hover {
  background: #fef3c7;
  border-color: #fcd34d;
  color: #b45309;
 }

 .task-btn-icon.view {
  color: #64748b;
  background: #f8fafc;
  border-color: #e2e8f0;
 }
 .task-btn-icon.view:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #1e293b;
 }

 .task-btn-icon.call {
  color: #dc2637;
  background: #fff0f1;
  border-color: #fecdd3;
 }
 .task-btn-icon.call:hover {
  background: #ffe4e6;
  border-color: #fda4af;
  color: #b91c1c;
 }

 /* Dark mode task action buttons */
 html.dark-mode .task-actions-toolbar {
  border-top-color: rgba(255, 255, 255, 0.08) !important;
 }

 html.dark-mode .task-btn-main {
  background: #dc2637 !important;
  border-color: #dc2637 !important;
  color: #fff !important;
  box-shadow: 0 4px 14px rgba(220, 38, 55, 0.3) !important;
 }

 html.dark-mode .task-btn-main:hover:not(:disabled) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45) !important;
 }

 html.dark-mode .task-btn-main:disabled {
  background: #3f3f46 !important;
  border-color: #3f3f46 !important;
  color: #71717a !important;
 }

 html.dark-mode .task-btn-icon {
  background: #27272a !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #e4e4e7 !important;
  box-shadow: none !important;
 }

 html.dark-mode .task-btn-icon.donation {
  background: rgba(16, 185, 129, 0.16) !important;
  border-color: rgba(16, 185, 129, 0.32) !important;
  color: #6ee7b7 !important;
 }
 html.dark-mode .task-btn-icon.donation:hover {
  background: rgba(16, 185, 129, 0.28) !important;
  border-color: rgba(16, 185, 129, 0.5) !important;
  color: #fff !important;
 }

 html.dark-mode .task-btn-icon.log {
  background: rgba(99, 102, 241, 0.16) !important;
  border-color: rgba(99, 102, 241, 0.32) !important;
  color: #a5b4fc !important;
 }
 html.dark-mode .task-btn-icon.log:hover {
  background: rgba(99, 102, 241, 0.28) !important;
  border-color: rgba(99, 102, 241, 0.5) !important;
  color: #fff !important;
 }

 html.dark-mode .task-btn-icon.whatsapp {
  background: rgba(34, 197, 94, 0.16) !important;
  border-color: rgba(34, 197, 94, 0.32) !important;
  color: #4ade80 !important;
 }
 html.dark-mode .task-btn-icon.whatsapp:hover {
  background: rgba(34, 197, 94, 0.28) !important;
  border-color: rgba(34, 197, 94, 0.5) !important;
  color: #fff !important;
 }

 html.dark-mode .task-btn-icon.reschedule {
  background: rgba(245, 158, 11, 0.16) !important;
  border-color: rgba(245, 158, 11, 0.32) !important;
  color: #fcd34d !important;
 }
 html.dark-mode .task-btn-icon.reschedule:hover {
  background: rgba(245, 158, 11, 0.28) !important;
  border-color: rgba(245, 158, 11, 0.5) !important;
  color: #fff !important;
 }

 html.dark-mode .task-btn-icon.view {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #d4d4d8 !important;
 }
 html.dark-mode .task-btn-icon.view:hover {
  background: rgba(255, 255, 255, 0.14) !important;
  border-color: rgba(255, 255, 255, 0.22) !important;
  color: #fff !important;
 }

 html.dark-mode .task-btn-icon.call {
  background: rgba(220, 38, 55, 0.16) !important;
  border-color: rgba(220, 38, 55, 0.32) !important;
  color: #fca5a5 !important;
 }
 html.dark-mode .task-btn-icon.call:hover {
  background: rgba(220, 38, 55, 0.28) !important;
  border-color: rgba(220, 38, 55, 0.5) !important;
  color: #fff !important;
 }
 /* Table View */
 .task-table-wrap {
  overflow-x: auto;
  padding: 10px 20px 20px;
 }

 .task-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
 }

 .task-table th {
  padding: 12px 14px;
  background: #f8fafc;
  color: #64748b;
  font-weight: 700;
  font-size: 11px;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
 }

 [dir="rtl"] .task-table th { text-align: right; }
 [dir="ltr"] .task-table th { text-align: left; }

 .task-table td {
  padding: 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
  vertical-align: middle;
 }

 .task-table tr:hover td {
  background: #f8fafc;
 }

 .task-table-lead-link {
  color: #1e293b;
  text-decoration: none;
 }
 .task-table-lead-link:hover {
  color: #2563eb;
 }
 html.dark-mode .task-table-lead-link {
  color: #f4f4f5 !important;
 }
 .task-table-company,
 .task-table-stage,
 .task-table-last-time {
  display: block;
  color: #64748b;
 }
 html.dark-mode .task-table-company,
 html.dark-mode .task-table-stage,
 html.dark-mode .task-table-last-time {
  color: var(--text-muted, #a1a1aa) !important;
 }
 .task-table-date {
  font-weight: 700;
  color: #1e293b;
 }
 html.dark-mode .task-table-date {
  color: #f4f4f5 !important;
 }
 .task-table-last-outcome {
  font-size: 12px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
 }
 html.dark-mode .task-table-last-outcome {
  color: #d4d4d8 !important;
 }
 .task-table-empty-followup {
  color: #94a3b8;
  font-size: 11px;
  font-style: italic;
 }
 html.dark-mode .task-table-empty-followup {
  color: #71717a !important;
 }
 /* Empty State */
 .task-empty-card {
  padding: 42px 20px;
  text-align: center;
  color: #64748b;
 }

 .task-empty-icon {
  width: 60px;
  height: 60px;
  margin: 0 auto 14px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 26px;
 }

 .task-empty-card strong {
  display: block;
  font-size: 16px;
  color: #334155;
  margin-bottom: 6px;
 }

 /* Modal Dialogs */
 .task-modal {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 20px;
 }

 .task-modal.open {
  display: flex;
 }

 .task-modal-backdrop {
  position: absolute;
  inset: 0;
  background: #0f172a80;
  backdrop-filter: blur(4px);
 }

 .task-modal-content {
  position: relative;
  width: min(520px, 100%);
  max-height: calc(100vh - 40px);
  overflow-y: auto;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 25px 60px #0f172a33;
  padding: 24px;
 }

 .task-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 14px;
  margin-bottom: 18px;
  border-bottom: 1px solid #e2e8f0;
 }

 .task-modal-head h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
 }

 .task-modal-close {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  font-size: 16px;
  cursor: pointer;
 }

 .task-modal-close:hover {
  background: #fee2e2;
  color: var(--task-red);
 }

 .task-form-group {
  margin-bottom: 16px;
 }

 .task-form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
 }

 .task-form-control {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  color: #1e293b;
  font-size: 13px;
  outline: none;
 }

 .task-form-control:focus {
  border-color: #3b82f6;
  background: #fff;
  box-shadow: 0 0 0 3px #3b82f61a;
 }

 .task-quick-presets {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
 }

 .task-preset-btn {
  padding: 4px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
 }

 .task-preset-btn:hover {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #2563eb;
 }

 .task-modal-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 16px;
  margin-top: 20px;
  border-top: 1px solid #e2e8f0;
 }

 /* Toast Notification */
 .task-toast {
  position: fixed;
  bottom: 24px;
  z-index: 2000;
  padding: 12px 20px;
  border-radius: 12px;
  background: #1e293b;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 10px 30px #00000026;
  display: flex;
  align-items: center;
  gap: 8px;
  opacity: 0;
  transform: translateY(20px);
  transition: all .25s ease;
  pointer-events: none;
 }

 [dir="rtl"] .task-toast { right: 24px; }
 [dir="ltr"] .task-toast { left: 24px; }

 .task-toast.show {
  opacity: 1;
  transform: translateY(0);
 }

 .task-date-pill {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
  font-weight: 700;
 }

 @media (max-width: 1024px) {
  .task-kpis-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .task-items-cards { grid-template-columns: 1fr; }
 }

 @media (max-width: 640px) {
  .task-kpis-grid { grid-template-columns: 1fr; }
  .task-filters-row { flex-direction: column; align-items: stretch; }
 }
 @media (max-width: 480px) {
  .task-actions-toolbar {
   display: grid;
   grid-template-columns: repeat(5, 1fr);
   gap: 6px;
  }
  .task-actions-toolbar .task-btn-main {
   grid-column: 1 / -1;
   width: 100%;
   height: 38px;
   font-size: 13px;
  }
  .task-actions-toolbar .task-btn-icon {
   width: 100%;
   height: 38px;
  }
 }
 
 /* Monochrome Theme Overrides for Tasks */
 html.crm-monochrome .task-kpi-icon-box { background: #f0f0f0 !important; color: #171717 !important; border: 1px solid #d4d4d4 !important; }
 html.crm-monochrome.dark-mode .task-kpi-icon-box { background: #262626 !important; color: #f5f5f5 !important; border: 1px solid #404040 !important; }
 html.crm-monochrome .task-progress-bar-fill { background: #171717 !important; }
 html.crm-monochrome.dark-mode .task-progress-bar-fill { background: #f5f5f5 !important; }
 html.crm-monochrome .task-scope-pill { background: #f5f5f5 !important; border-color: #d4d4d4 !important; color: #171717 !important; }
 html.crm-monochrome .task-scope-pill.active { background: #171717 !important; border-color: #171717 !important; color: #fff !important; }
 html.crm-monochrome .task-scope-pill.active .task-pill-badge { background: #fff !important; color: #171717 !important; }
 html.crm-monochrome.dark-mode .task-scope-pill { background: #1f1f1f !important; border-color: #404040 !important; color: #e5e5e5 !important; }
 html.crm-monochrome.dark-mode .task-scope-pill.active { background: #e5e5e5 !important; border-color: #e5e5e5 !important; color: #171717 !important; }
 html.crm-monochrome.dark-mode .task-scope-pill.active .task-pill-badge { background: #171717 !important; color: #fff !important; }
 html.crm-monochrome .task-section-indicator { background: #171717 !important; }
 html.crm-monochrome.dark-mode .task-section-indicator { background: #e5e5e5 !important; }
 html.crm-monochrome .task-lead-avatar { background: #ededed !important; color: #171717 !important; }
 html.crm-monochrome.dark-mode .task-lead-avatar { background: #262626 !important; color: #f5f5f5 !important; }
 html.crm-monochrome .task-status-pill { background: #ededed !important; border-color: #d4d4d4 !important; color: #171717 !important; }
 html.crm-monochrome.dark-mode .task-status-pill { background: #262626 !important; border-color: #404040 !important; color: #f5f5f5 !important; }
 html.crm-monochrome .task-time-strip { background: #f0f0f0 !important; color: #171717 !important; }
 html.crm-monochrome.dark-mode .task-time-strip { background: #222222 !important; color: #e5e5e5 !important; }
 html.crm-monochrome .task-action-btn { background: #fff !important; border-color: #d4d4d4 !important; color: #171717 !important; }
 html.crm-monochrome .task-action-btn.followup, html.crm-monochrome .task-action-btn.call { background: #171717 !important; border-color: #171717 !important; color: #fff !important; }
 html.crm-monochrome.dark-mode .task-action-btn { background: #262626 !important; border-color: #404040 !important; color: #f5f5f5 !important; }
 html.crm-monochrome.dark-mode .task-action-btn.followup, html.crm-monochrome.dark-mode .task-action-btn.call { background: #e5e5e5 !important; border-color: #e5e5e5 !important; color: #171717 !important; }
</style>
@endpush

@section('content')
<div class="daily-tasks-page">

 {{-- 1. KPI Cards Grid --}}
 <div class="task-kpis-grid">
  <a class="task-kpi-card overdue {{ $scope === 'overdue' ? 'active' : '' }}" href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'overdue'])) }}">
   <div class="task-kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
   <div class="task-kpi-info">
    <strong>{{ number_format($overdueCount) }}</strong>
    <span>{{ __('crm.tasks_overdue') }}</span>
   </div>
  </a>

  <a class="task-kpi-card today {{ $scope === 'today' ? 'active' : '' }}" href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'today'])) }}">
   <div class="task-kpi-icon"><i class="bi bi-calendar-check-fill"></i></div>
   <div class="task-kpi-info">
    <strong>{{ number_format($todayCount) }}</strong>
    <span>{{ __('crm.tasks_due_today') }}</span>
   </div>
  </a>

  <a class="task-kpi-card completed {{ $scope === 'completed' ? 'active' : '' }}" href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'completed'])) }}">
   <div class="task-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
   <div class="task-kpi-info">
    <strong>{{ number_format($completedTodayCount) }}</strong>
    <span>{{ __('crm.tasks_completed_today') }}</span>
   </div>
  </a>

  <a class="task-kpi-card no-date {{ $scope === 'no_date' ? 'active' : '' }}" href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'no_date'])) }}">
   <div class="task-kpi-icon"><i class="bi bi-question-circle-fill"></i></div>
   <div class="task-kpi-info">
    <strong>{{ number_format($noDateCount) }}</strong>
    <span>{{ __('crm.tasks_without_date') }}</span>
   </div>
  </a>
 </div>

 {{-- 2. Progress Box --}}
 <div class="task-progress-box">
  <div class="task-progress-head">
   <span>{{ __('crm.daily_progress') }}</span>
   <span class="task-progress-ratio">
    {{ __('crm.tasks_completed_ratio', [
        'completed' => number_format($completedTodayCount),
        'total' => number_format($completedTodayCount + $totalDueToday),
        'percent' => $completionRate
    ]) }}
   </span>
  </div>
  <div class="task-progress-bar-bg">
   <div class="task-progress-bar-fill" style="width: {{ min(100, $completionRate) }}%;"></div>
  </div>
 </div>

 {{-- 3. Search and Filter Bar --}}
 <div class="task-filters-card">
  <form class="task-filters-form" method="GET" action="{{ route('v2.tasks.daily') }}">
   <input type="hidden" name="scope" value="{{ $scope }}">
   <input type="hidden" name="view" value="{{ $viewMode }}">

   <div class="task-filters-row">
    <div class="task-search-input-wrap">
     <i class="bi bi-search"></i>
     <input
      class="task-search-input"
      type="text"
      name="search"
      value="{{ $search }}"
      placeholder="{{ __('crm.search_tasks_placeholder') }}"
     >
    </div>

    <button class="btn soft" type="submit" style="min-height: 42px;" title="{{ __('crm.search') }}">
     <i class="bi bi-search"></i>
     <span>{{ __('crm.search') }}</span>
    </button>

    <select class="task-select-filter" name="stage_id" onchange="this.form.submit()">
     <option value="">{{ __('crm.filter_by_stage') }} ({{ __('crm.all') }})</option>
     @foreach ($stages as $sg)
      <option value="{{ $sg->id }}" {{ (string)$stageId === (string)$sg->id ? 'selected' : '' }}>
       {{ (app()->getLocale() === 'en' && !empty($sg->name_en)) ? $sg->name_en : $sg->name_ar }}
      </option>
     @endforeach
    </select>

    @if ($assignableUsers->count() > 1)
     <select class="task-select-filter" name="employee_id" onchange="this.form.submit()">
      <option value="">{{ __('crm.filter_by_employee') }} ({{ __('crm.all') }})</option>
      @foreach ($assignableUsers as $u)
       <option value="{{ $u->id }}" {{ (string)$employeeId === (string)$u->id ? 'selected' : '' }}>
        {{ $u->name }}
       </option>
      @endforeach
     </select>
    @endif

    <select class="task-select-filter" name="sort" onchange="this.form.submit()">
     <option value="followup_asc" {{ $sort === 'followup_asc' ? 'selected' : '' }}>{{ __('crm.sort_followup_asc') }}</option>
     <option value="followup_desc" {{ $sort === 'followup_desc' ? 'selected' : '' }}>{{ __('crm.sort_followup_desc') }}</option>
     <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>{{ __('crm.sort_name_asc') }}</option>
     <option value="created_desc" {{ $sort === 'created_desc' ? 'selected' : '' }}>{{ __('crm.sort_created_desc') }}</option>
    </select>

    <div class="task-view-toggle">
     <a
      class="task-view-btn {{ $viewMode === 'cards' ? 'active' : '' }}"
      href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['view' => 'cards'])) }}"
      title="{{ __('crm.view_cards') }}"
     >
      <i class="bi bi-grid"></i>
     </a>
     <a
      class="task-view-btn {{ $viewMode === 'table' ? 'active' : '' }}"
      href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['view' => 'table'])) }}"
      title="{{ __('crm.view_table') }}"
     >
      <i class="bi bi-list-ul"></i>
     </a>
    </div>

    @if ($search !== '' || $statusId !== null || $stageId !== null || $employeeId !== null || $scope !== 'all')
     <a class="btn soft" href="{{ route('v2.tasks.daily') }}">
      <i class="bi bi-x-circle"></i>
      {{ __('crm.clear_filters') }}
     </a>
    @endif
   </div>

   {{-- Scope pills row --}}
   <div class="task-scope-pills">
    <a
     class="task-scope-pill {{ $scope === 'all' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'all'])) }}"
    >
     <span>{{ __('crm.scope_all') }}</span>
     <span class="task-pill-badge">{{ number_format($totalDueToday) }}</span>
    </a>

    <a
     class="task-scope-pill overdue {{ $scope === 'overdue' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'overdue'])) }}"
    >
     <span>{{ __('crm.scope_overdue') }}</span>
     <span class="task-pill-badge">{{ number_format($overdueCount) }}</span>
    </a>

    <a
     class="task-scope-pill today {{ $scope === 'today' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'today'])) }}"
    >
     <span>{{ __('crm.scope_today') }}</span>
     <span class="task-pill-badge">{{ number_format($todayCount) }}</span>
    </a>

    <a
     class="task-scope-pill upcoming {{ $scope === 'upcoming' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'upcoming'])) }}"
    >
     <span>{{ __('crm.scope_upcoming') }}</span>
     <span class="task-pill-badge">{{ number_format($upcomingCount) }}</span>
    </a>

    <a
     class="task-scope-pill no-date {{ $scope === 'no_date' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'no_date'])) }}"
    >
     <span>{{ __('crm.scope_no_date') }}</span>
     <span class="task-pill-badge">{{ number_format($noDateCount) }}</span>
    </a>

    <a
     class="task-scope-pill completed {{ $scope === 'completed' ? 'active' : '' }}"
     href="{{ route('v2.tasks.daily', array_merge(request()->query(), ['scope' => 'completed'])) }}"
    >
     <span>{{ __('crm.scope_completed') }}</span>
     <span class="task-pill-badge">{{ number_format($completedTodayCount) }}</span>
    </a>
   </div>
  </form>
 </div>

{{-- 4. Content Area --}}
@if ($scope === 'completed')
 {{-- COMPLETED TODAY FOLLOWUPS --}}
 <section class="task-section-block completed-block">
  <header class="task-section-header">
   <div class="task-section-title-wrap">
    <span class="task-section-indicator"></span>
    <div>
     <h2>{{ __('crm.completed_today_section_title') }}</h2>
     <p>{{ __('crm.completed_today_section_desc') }}</p>
    </div>
   </div>
   <span class="task-section-count-badge">{{ number_format($completedTodayFollowups ? $completedTodayFollowups->total() : 0) }}</span>
  </header>

  @if (!$completedTodayFollowups || $completedTodayFollowups->isEmpty())
   <div class="task-empty-card">
    <div class="task-empty-icon"><i class="bi bi-clock-history"></i></div>
    <strong>{{ __('crm.no_followups_currently') }}</strong>
    <p>{{ __('crm.followups_appear_here') }}</p>
   </div>
  @else
   <div class="task-table-wrap">
    <table class="task-table">
     <thead>
      <tr>
       <th>{{ __('crm.client') }}</th>
       <th>{{ __('crm.phone') }}</th>
       <th>{{ __('crm.followup_type') }}</th>
       <th>{{ __('crm.status') }}</th>
       <th>{{ __('crm.employee') }}</th>
       <th>{{ __('crm.date') }}</th>
       <th>{{ __('crm.followup_notes') }}</th>
       <th>{{ __('crm.view_lead') }}</th>
      </tr>
     </thead>
     <tbody>
      @foreach ($completedTodayFollowups as $f)
       <tr>
        <td>
         <strong>{{ $f->lead?->name ?? __('crm.unavailable_client') }}</strong>
         @if ($f->lead?->company_name)
          <small style="display:block; color:#64748b;">{{ $f->lead->company_name }}</small>
         @endif
        </td>
        <td>
         @if ($f->lead?->phone)
          <a class="task-phone-link" href="tel:{{ $f->lead->phone }}">{{ $f->lead->phone }}</a>
         @else
          <span style="color:#94a3b8;">{{ __('crm.not_registered') }}</span>
         @endif
        </td>
        <td>
         <span style="font-weight:700;">
          {{ $communicationTypes[$f->communication_type] ?? $f->communication_type }}
         </span>
        </td>
        <td>
         <span
          class="task-status-pill"
          style="background: {{ ($f->toStatus?->color ?? '#64748b') }}1a; color: {{ $f->toStatus?->color ?? '#64748b' }}; border-color: {{ ($f->toStatus?->color ?? '#64748b') }}33;"
         >
          {{ $f->toStatus?->name_ar ?? '-' }}
         </span>
        </td>
        <td>{{ $f->user?->name ?? $f->employee_name }}</td>
        <td>
         <span style="font-weight:700; color:#475569;">
          {{ $f->followed_up_at ? $f->followed_up_at->format('h:i A') : '-' }}
         </span>
        </td>
        <td style="max-width:320px;">
         <div style="font-size:12px; color:#334155; line-height:1.4;">{{ $f->outcome }}</div>
        </td>
        <td>
         @if ($f->lead)
          <a class="btn soft" href="{{ route('v2.leads.show', $f->lead) }}">
           <i class="bi bi-eye"></i>
          </a>
         @endif
        </td>
       </tr>
      @endforeach
     </tbody>
    </table>
   </div>

   @if ($completedTodayFollowups->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--task-border, #e2e8f0);">
     {{ $completedTodayFollowups->links() }}
    </div>
   @endif
  @endif
 </section>

@else
 {{-- PAGINATED TASKS (all, overdue, today, upcoming, no_date) --}}
 @php
  $blockClass = match($scope) {
      'overdue' => 'overdue-block',
      'today' => 'today-block',
      'upcoming' => 'upcoming-block',
      'no_date' => 'no-date-block',
      default => 'today-block',
  };
  $timeClass = match($scope) {
      'overdue' => 'overdue',
      'today' => 'today',
      'upcoming' => 'upcoming',
      'no_date' => 'no-date',
      default => 'all',
  };
  $scopeTitle = match($scope) {
      'overdue' => __('crm.overdue_section_title'),
      'today' => __('crm.today_section_title'),
      'upcoming' => __('crm.upcoming_section_title'),
      'no_date' => __('crm.no_date_section_title'),
      default => __('crm.scope_all'),
  };
  $scopeDesc = match($scope) {
      'overdue' => __('crm.overdue_section_desc'),
      'today' => __('crm.today_section_desc'),
      'upcoming' => __('crm.upcoming_section_desc'),
      'no_date' => __('crm.no_date_section_desc'),
      default => __('crm.all_tasks_overview_desc') ?? __('crm.today_section_desc'),
  };
 @endphp

 <section class="task-section-block {{ $blockClass }}">
  <header class="task-section-header">
   <div class="task-section-title-wrap">
    <span class="task-section-indicator"></span>
    <div>
     <h2>{{ $scopeTitle }}</h2>
     <p>{{ $scopeDesc }}</p>
    </div>
   </div>
   <div style="display: flex; align-items: center; gap: 10px;">
    @if ($paginatedTasks && $paginatedTasks->total() > 0)
     <small style="color: var(--text-muted, #8b94a5); font-size: 12px; font-weight: 700;">
      {{ $paginatedTasks->firstItem() }} - {{ $paginatedTasks->lastItem() }} / {{ $paginatedTasks->total() }}
     </small>
    @endif
    <span class="task-section-count-badge">{{ number_format($paginatedTasks ? $paginatedTasks->total() : 0) }}</span>
   </div>
  </header>

  @if (!$paginatedTasks || $paginatedTasks->isEmpty())
   <div class="task-empty-card">
    <div class="task-empty-icon"><i class="bi bi-check2-circle"></i></div>
    <strong>{{ __('crm.all_caught_up') }}</strong>
    <p>{{ __('crm.all_tasks_completed_cheer') }}</p>
   </div>
  @else
   @if ($viewMode === 'table')
    @include('tasks._table_view', ['leads' => $paginatedTasks, 'timeClass' => $timeClass])
   @else
    <div class="task-items-cards">
     @foreach ($paginatedTasks as $lead)
      @include('tasks._lead_task_card', ['lead' => $lead, 'timeClass' => $timeClass])
     @endforeach
    </div>
   @endif

   @if ($paginatedTasks->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--task-border, #e2e8f0);">
     {{ $paginatedTasks->links() }}
    </div>
   @endif
  @endif
 </section>
@endif

</div>

{{-- MODAL 1: Reschedule Modal --}}
<div class="task-modal" id="rescheduleModal">
 <div class="task-modal-backdrop" onclick="closeTaskModal('rescheduleModal')"></div>
 <div class="task-modal-content">
  <div class="task-modal-head">
   <h3><i class="bi bi-calendar-event"></i> {{ __('crm.reschedule_task') }}</h3>
   <button class="task-modal-close" type="button" onclick="closeTaskModal('rescheduleModal')">&times;</button>
  </div>

  <form id="rescheduleForm" method="POST" onsubmit="submitReschedule(event)">
   @csrf
   <div class="task-form-group">
    <label for="rescheduleLeadName">{{ __('crm.client') }}</label>
    <input class="task-form-control" id="rescheduleLeadName" type="text" readonly disabled>
   </div>

   <div class="task-form-group">
    <label for="rescheduleDateInput">{{ __('crm.select_new_date') }}</label>
    <input class="task-form-control" id="rescheduleDateInput" name="next_follow_up_at" type="datetime-local" required>
    <div class="task-quick-presets">
     <button class="task-preset-btn" type="button" onclick="presetReschedule(2)">+2 {{ __('crm.hours_count') }}</button>
     <button class="task-preset-btn" type="button" onclick="presetReschedule(4)">+4 {{ __('crm.hours_count') }}</button>
     <button class="task-preset-btn" type="button" onclick="presetReschedule(24)">{{ __('crm.postpone_tomorrow') }}</button>
     <button class="task-preset-btn" type="button" onclick="presetReschedule(168)">{{ __('crm.postpone_next_week') }}</button>
    </div>
   </div>

   <div class="task-form-group">
    <label for="rescheduleReasonInput">{{ __('crm.followup_outcome_notes') }} ({{ __('crm.not_specified') }})</label>
    <input class="task-form-control" id="rescheduleReasonInput" name="reschedule_reason" type="text" placeholder="...">
   </div>

   <div class="task-modal-actions">
    <button class="btn soft" type="button" onclick="closeTaskModal('rescheduleModal')">{{ __('crm.cancel') }}</button>
    <button class="btn primary" type="submit">{{ __('crm.save_changes') }}</button>
   </div>
  </form>
 </div>
</div>


{{-- Global Toast --}}
<div class="task-toast" id="taskToast">
 <i class="bi bi-check-circle-fill" style="color: #22c55e;"></i>
 <span id="taskToastMessage"></span>
</div>

@push('scripts')
<script>
 (() => {
  let activeLeadId = null;

  window.openRescheduleModal = function (leadId, leadName, currentFollowup) {
   activeLeadId = leadId;
   const modal = document.getElementById('rescheduleModal');
   const nameInput = document.getElementById('rescheduleLeadName');
   const dateInput = document.getElementById('rescheduleDateInput');
   const form = document.getElementById('rescheduleForm');

   nameInput.value = leadName || '';
   form.action = '/tasks/leads/' + leadId + '/reschedule';

   if (currentFollowup) {
    try {
     const d = new Date(currentFollowup);
     if (!isNaN(d.getTime())) {
      dateInput.value = d.toISOString().slice(0, 16);
     }
    } catch(e) {}
   } else {
    const d = new Date(Date.now() + 2 * 3600000);
    dateInput.value = d.toISOString().slice(0, 16);
   }

   modal.classList.add('open');
  };

  window.presetReschedule = function (hours) {
   const dateInput = document.getElementById('rescheduleDateInput');
   const target = new Date(Date.now() + hours * 3600000);
   dateInput.value = target.toISOString().slice(0, 16);
  };


  window.closeTaskModal = function (modalId) {
   const modal = document.getElementById(modalId);
   if (modal) modal.classList.remove('open');
  };

  window.showTaskToast = function (message) {
   const toast = document.getElementById('taskToast');
   const msg = document.getElementById('taskToastMessage');
   if (!toast || !msg) return;
   msg.textContent = message;
   toast.classList.add('show');
   setTimeout(() => { toast.classList.remove('show'); }, 3500);
  };

  window.submitReschedule = async function (e) {
   e.preventDefault();
   const form = e.target;
   const formData = new FormData(form);

   try {
    const response = await fetch(form.action, {
     method: 'POST',
     headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
     },
     body: formData
    });

    if (response.ok) {
     const data = await response.json();
     closeTaskModal('rescheduleModal');
     showTaskToast(data.message || '{{ __('crm.task_rescheduled_success') }}');
     setTimeout(() => { window.location.reload(); }, 600);
    } else {
     form.submit();
    }
   } catch (err) {
    form.submit();
   }
  };


  // Keyboard shortcut to close modal
  document.addEventListener('keydown', (e) => {
   if (e.key === 'Escape') {
    closeTaskModal('rescheduleModal');
   }
  });
 })();
</script>
@endpush
@endsection

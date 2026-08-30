<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.kanban_view') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
<style>
:root{
 --red:#dc2637;
 --dark:#182033;
 --muted:#8b94a5;
 --line:#e7e9ef;
 --bg:#f5f6f9;
 --shadow:0 12px 35px #1720330d
}
*{box-sizing:border-box}
body{
 margin:0;
 min-width:320px;
 background:
  radial-gradient(circle at 8% 0,#dc26370c,transparent 25rem),
  var(--bg);
 color:var(--dark);
 font-family:var(--font-primary)
}
a{color:inherit}
.crm-app{display:flex;min-height:100vh}
.crm-main{flex:1;min-width:0;padding:24px clamp(16px,3vw,32px) 60px;text-align:start}
.topbar{
 display:flex;
 justify-content:space-between;
 align-items:center;
 gap:18px;
 margin-bottom:24px;
 flex-wrap:wrap
}
.topbar-left{display:flex;align-items:center;gap:12px}
.topbar-left h1{margin:0;font-size:24px;font-weight:900;color:var(--dark)}
.top-actions{
 margin-inline-start:auto;
 display:flex;
 align-items:center;
 gap:10px;
 flex-wrap:wrap
}
.btn{
 min-height:40px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 gap:7px;
 padding:8px 14px;
 border:1px solid transparent;
 border-radius:11px;
 background:var(--red);
 color:#fff;
 text-decoration:none;
 font-size:12px;
 font-weight:900;
 box-shadow:0 10px 22px #dc26372b
}
.btn.light{
 border-color:var(--line);
 background:#fff;
 color:#596477;
 box-shadow:none
}
main{
 padding:28px clamp(16px,3vw,38px) 40px
}
.page-head{
 display:flex;
 align-items:flex-end;
 justify-content:space-between;
 gap:16px;
 margin-bottom:18px
}
.page-head h1{
 margin:0;
 font-size:28px
}
.page-head p{
 margin:7px 0 0;
 color:var(--muted);
 font-size:13px
}
.summary{
 display:flex;
 gap:8px;
 flex-wrap:wrap
}
.summary span{
 padding:9px 12px;
 border:1px solid var(--line);
 border-radius:10px;
 background:#fff;
 color:#687385;
 font-size:11px;
 font-weight:bold
}
.summary b{
 margin-inline-start:5px;
 color:var(--dark);
 font:900 14px var(--font-primary)
}
.board-shell{
 overflow:hidden;
 border:1px solid var(--line);
 border-radius:20px;
 background:#eef0f4;
 box-shadow:var(--shadow)
}
.board{
  display:grid;
  grid-auto-flow:column;
  grid-auto-columns:minmax(270px,1fr);
 gap:12px;
 min-height:610px;
 padding:14px;
 overflow-x:auto;
 overscroll-behavior-inline:contain;
 scroll-snap-type:x proximity
}
.kanban-column{
 --column-color:#3478f6;
 min-width:0;
 height:max-content;
 min-height:560px;
 padding:12px;
 border:1px solid var(--line);
 border-top:4px solid var(--column-color);
 border-radius:16px;
 background:#f9fafb;
 scroll-snap-align:start
}
.kanban-column.new{--column-color:#3478f6}
.kanban-column.no-answer{--column-color:#e59b16}
.kanban-column.not-interested{--column-color:#dc2637}
.kanban-column.donor{--column-color:#16a34a}
.column-head{
 display:flex;
 align-items:center;
 gap:9px;
 padding:3px 2px 13px;
 border-bottom:1px solid var(--line)
}
.column-icon{
 width:34px;
 height:34px;
 flex:0 0 34px;
 display:grid;
 place-items:center;
 border-radius:10px;
 background:color-mix(in srgb,var(--column-color) 12%,white);
 color:var(--column-color);
 font-size:16px
}
.column-title{
  flex:1;
  min-width:0;
  margin:0;
  overflow-wrap:anywhere;
  font-size:13px
}
.column-count{
 min-width:30px;
 height:30px;
 display:grid;
 place-items:center;
 border-radius:9px;
 background:var(--column-color);
 color:#fff;
 font:900 12px var(--font-primary)
}
.column-body{
 display:grid;
 place-items:center;
 min-height:470px;
 padding:18px
}
.kanban-empty{
 text-align:center;
 color:#929aa7
}
.kanban-empty i{
 width:52px;
 height:52px;
 display:grid;
 place-items:center;
 margin:0 auto 11px;
 border:1px dashed #ced3dc;
 border-radius:15px;
 background:#fff;
 color:var(--column-color);
 font-style:normal;
 font-size:21px
}
.kanban-empty strong{
 display:block;
 color:#687385;
 font-size:12px
}
.kanban-empty p{
 max-width:190px;
 margin:7px auto 0;
 font-size:10px;
 line-height:1.7
}
.notice{
 margin-top:14px;
 padding:12px 14px;
 border:1px solid #efd59f;
 border-radius:12px;
 background:#fff9ed;
 color:#93640e;
 font-size:11px;
 font-weight:bold
}
@media(max-width:760px){
 .topbar{align-items:flex-start;flex-wrap:wrap}
 .top-actions{width:100%;margin:0}
 .top-actions .btn{flex:1}
 .page-head{align-items:flex-start;flex-direction:column}
 .board{grid-auto-columns:minmax(260px,82vw)}
}

body.kanban-popup-view .topbar,
body.kanban-popup-view .page-head{
 display:none
}
body.kanban-popup-view .kanban-main{
 padding:14px;
 min-height:100dvh
}
body.kanban-popup-view .board-shell{
 border-radius:12px
}

/* CRM KANBAN TOOLBAR & PAGINATION START */
.kanban-toolbar{
 display:flex;
 flex-direction:column;
 gap:10px;
 margin-bottom:14px;
 padding:12px 16px;
 background:#ffffff;
 border:1px solid var(--line,#e7e9ef);
 border-radius:14px;
 box-shadow:0 4px 16px rgba(23,32,51,0.04)
}
.kanban-toolbar-main{
 display:flex;
 align-items:center;
 flex-wrap:wrap;
 gap:10px
}
.kanban-search-box{
 position:relative;
 flex:1 1 220px;
 min-width:180px
}
.kanban-search-icon{
 position:absolute;
 inset-inline-start:12px;
 top:50%;
 transform:translateY(-50%);
 color:#929aa7;
 font-size:13px;
 pointer-events:none
}
.kanban-search-input{
 width:100%;
 height:38px;
 padding-inline-start:34px;
 padding-inline-end:30px;
 border:1px solid #dfe3ea;
 border-radius:10px;
 background:#f8f9fb;
 color:#1f2937;
 font-family:inherit;
 font-size:12px;
 font-weight:700;
 outline:none;
 transition:border-color .15s,background-color .15s,box-shadow .15s
}
.kanban-search-input:focus{
 border-color:#3478f6;
 background:#ffffff;
 box-shadow:0 0 0 3px rgba(52,120,246,0.12)
}
.kanban-search-clear{
 position:absolute;
 inset-inline-end:8px;
 top:50%;
 transform:translateY(-50%);
 border:none;
 background:none;
 color:#9ca3af;
 padding:4px;
 cursor:pointer;
 font-size:14px;
 display:flex;
 align-items:center
}
.kanban-search-clear:hover{
 color:#dc2637
}
.kanban-filter-select-wrap{
 display:flex;
 align-items:center;
 gap:6px
}
.kanban-filter-label{
 display:flex;
 align-items:center;
 gap:4px;
 color:#64748b;
 font-size:11px;
 font-weight:800;
 white-space:nowrap
}
.kanban-filter-select{
 height:38px;
 padding:0 10px;
 border:1px solid #dfe3ea;
 border-radius:10px;
 background:#f8f9fb;
 color:#374151;
 font-family:inherit;
 font-size:12px;
 font-weight:700;
 outline:none;
 cursor:pointer;
 transition:border-color .15s,background-color .15s
}
.kanban-filter-select:focus{
 border-color:#3478f6;
 background:#ffffff;
 box-shadow:0 0 0 3px rgba(52,120,246,0.12)
}
.kanban-reset-btn{
 height:38px;
 padding:0 12px;
 display:inline-flex;
 align-items:center;
 gap:6px;
 border:1px solid #fecaca;
 border-radius:10px;
 background:#fef2f2;
 color:#dc2626;
 font-family:inherit;
 font-size:11px;
 font-weight:800;
 cursor:pointer;
 transition:background-color .15s,border-color .15s
}
.kanban-reset-btn:hover{
 background:#fee2e2;
 border-color:#fca5a5
}
.kanban-toolbar-status{
 display:flex;
 align-items:center;
 gap:8px;
 padding-top:8px;
 border-top:1px solid #f1f3f7;
 font-size:11px;
 color:#64748b
}
.kanban-filter-badge{
 display:inline-flex;
 align-items:center;
 gap:5px;
 padding:3px 8px;
 border-radius:6px;
 background:#eff6ff;
 color:#2563eb;
 font-size:11px;
 font-weight:700
}
.kanban-column-pagination{
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:6px;
 padding:8px 10px;
 margin-top:8px;
 border-top:1px solid var(--line,#e7e9ef);
 background:#ffffff;
 border-radius:10px
}
.kanban-page-btn{
 width:30px;
 height:30px;
 display:grid;
 place-items:center;
 padding:0;
 border:1px solid #e2e8f0;
 border-radius:8px;
 background:#f8fafc;
 color:#475569;
 font-size:12px;
 cursor:pointer;
 transition:background-color .15s,border-color .15s,color .15s
}
.kanban-page-btn:hover:not(:disabled){
 border-color:var(--column-color,#3478f6);
 color:var(--column-color,#3478f6);
 background:#ffffff
}
.kanban-page-btn:disabled{
 opacity:0.35;
 cursor:not-allowed;
 border-color:#edf2f7;
 background:#f8fafc;
 color:#94a3b8
}
.kanban-page-info{
 font-size:11px;
 font-weight:800;
 color:#475569;
 direction:ltr;
 text-align:center;
 flex:1
}
.kanban-filter-empty{
 display:flex;
 flex-direction:column;
 align-items:center;
 justify-content:center;
 gap:8px;
 min-height:180px;
 padding:24px 12px;
 text-align:center;
 color:#94a3b8
}
.kanban-filter-empty i{
 font-size:24px;
 color:#cbd5e1
}
.kanban-filter-empty strong{
 font-size:11px;
 color:#64748b
}
html.dark-mode {
 --bg:#09090b;
 --card:#18181b;
 --dark:#f4f4f5;
 --muted:#a1a1aa;
 --line:rgba(255,255,255,0.08);
}
html.dark-mode body {
 background: #09090b !important;
 color: #f4f4f5 !important;
}
html.dark-mode .page-head h1 { color: #f4f4f5 !important; }
html.dark-mode .page-head p { color: #a1a1aa !important; }
html.dark-mode .summary { background: #18181b !important; border-color: rgba(255,255,255,0.08) !important; color: #a1a1aa !important; }
html.dark-mode .summary b { color: #f4f4f5 !important; }
html.dark-mode .board-shell {
 background: #121214 !important;
 border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .kanban-column {
 background: #18181b !important;
 border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .column-head {
 border-bottom-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .column-icon {
 background: color-mix(in srgb, var(--column-color) 22%, #18181b) !important;
}
html.dark-mode .column-title {
 color: #f4f4f5 !important;
}
html.dark-mode .kanban-followup-toolbar {
 background: #18181b !important;
 border-bottom-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .kanban-scope-btn {
 background: #27272a !important;
 border-color: rgba(255,255,255,0.08) !important;
 color: #a1a1aa !important;
}
html.dark-mode .kanban-scope-btn.active {
 background: color-mix(in srgb, var(--column-color) 25%, #27272a) !important;
 border-color: var(--column-color) !important;
 color: var(--column-color) !important;
}
html.dark-mode .kanban-card {
 background: #27272a !important;
 border-color: rgba(255,255,255,0.08) !important;
 box-shadow: 0 4px 14px rgba(0,0,0,0.35) !important;
}
html.dark-mode .kanban-card-name {
 color: #f4f4f5 !important;
}
html.dark-mode .kanban-card-stage {
 background: color-mix(in srgb, var(--card-stage-color) 22%, #27272a) !important;
 color: var(--card-stage-color) !important;
}
html.dark-mode .kanban-card-row {
 background: #1f1f23 !important;
 border-color: rgba(255,255,255,0.06) !important;
}
html.dark-mode .kanban-card-row span {
 color: #a1a1aa !important;
}
html.dark-mode .kanban-card-row strong {
 color: #f4f4f5 !important;
}
html.dark-mode .kanban-phone {
 color: #60a5fa !important;
}
html.dark-mode .kanban-card-actions .btn {
 background: #18181b !important;
 border-color: rgba(255,255,255,0.12) !important;
 color: #f4f4f5 !important;
}
html.dark-mode .kanban-card-actions .call {
 background: var(--card-stage-color) !important;
 border-color: var(--card-stage-color) !important;
 color: #fff !important;
}
html.dark-mode .kanban-card-actions .donation {
 background: #16a34a !important;
 border-color: #16a34a !important;
 color: #fff !important;
}
html.dark-mode .kanban-stepper-dot{
 background:#52525b;
 border-color:#52525b
}
html.dark-mode .kanban-stepper-line{
 background:#3f3f46
}
html.dark-mode .kanban-stepper-label{
 color:#71717a
}
html.dark-mode .kanban-stage-btn{
 background:color-mix(in srgb,var(--btn-stage-color,#3478f6) 15%,#27272a) !important;
 border-color:color-mix(in srgb,var(--btn-stage-color,#3478f6) 30%,#3f3f46) !important;
 color:var(--btn-stage-color,#3478f6) !important
}
html.dark-mode .kanban-stage-btn:hover{
 background:color-mix(in srgb,var(--btn-stage-color,#3478f6) 25%,#27272a) !important
}
html.crm-monochrome .kanban-stepper-dot.active{
 background:#71717a !important;
 border-color:#71717a !important;
 box-shadow:0 0 0 2.5px rgba(113,113,122,0.22) !important
}
html.crm-monochrome .kanban-stepper-dot.completed{
 background:#a1a1aa !important;
 border-color:#a1a1aa !important
}
html.crm-monochrome .kanban-stepper-line.completed{
 background:#a1a1aa !important
}
html.crm-monochrome .kanban-stage-btn{
 border-color:#d4d4d8 !important;
 background:#f4f4f5 !important;
 color:#52525b !important
}
html.crm-monochrome.dark-mode .kanban-stage-btn{
 border-color:#3f3f46 !important;
 background:#27272a !important;
 color:#a1a1aa !important
}
html.dark-mode .kanban-no-date {
 background: #27272a !important;
 color: #a1a1aa !important;
}
html.dark-mode .kanban-empty i,
html.dark-mode .kanban-scope-empty i {
 background: #27272a !important;
 border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .kanban-scope-empty strong {
 color: #e4e4e7 !important;
}
html.dark-mode .kanban-scope-empty p {
 color: #a1a1aa !important;
}
html.dark-mode .btn {
 background: #27272a !important;
 border-color: rgba(255,255,255,0.12) !important;
 color: #f4f4f5 !important;
}
html.dark-mode .btn.primary {
 background: var(--red) !important;
 border-color: var(--red) !important;
 color: #fff !important;
}
html.dark-mode .kanban-toolbar,
html.crm-monochrome.dark-mode .kanban-toolbar{
 background:#1e1e20;
 border-color:rgba(255,255,255,0.08)
}
html.dark-mode .kanban-search-input,
html.dark-mode .kanban-filter-select,
html.crm-monochrome.dark-mode .kanban-search-input,
html.crm-monochrome.dark-mode .kanban-filter-select{
 background:#27272a;
 border-color:rgba(255,255,255,0.12);
 color:#e4e4e7
}
html.dark-mode .kanban-column-pagination,
html.crm-monochrome.dark-mode .kanban-column-pagination{
 background:#1e1e20;
 border-color:rgba(255,255,255,0.08)
}
html.dark-mode .kanban-page-btn,
html.crm-monochrome.dark-mode .kanban-page-btn{
 background:#27272a;
 border-color:rgba(255,255,255,0.12);
 color:#a1a1aa
}
html.dark-mode .kanban-page-info,
html.crm-monochrome.dark-mode .kanban-page-info{
 color:#a1a1aa
}

/* Monochrome Theme Overrides */
html.crm-monochrome:not(.dark-mode) .board-shell { background: #f0f0f0 !important; border-color: #e5e5e5 !important; }
html.crm-monochrome:not(.dark-mode) .kanban-column { background: #fafafa !important; border-color: #e5e5e5 !important; }
html.crm-monochrome:not(.dark-mode) .kanban-card { background: #fff !important; border-color: #e5e5e5 !important; }
html.crm-monochrome.dark-mode .board-shell { background: #111 !important; border-color: #333 !important; }
html.crm-monochrome.dark-mode .kanban-column { background: #1c1c1c !important; border-color: #333 !important; }
html.crm-monochrome.dark-mode .kanban-card { background: #262626 !important; border-color: #333 !important; }
html.crm-monochrome.dark-mode .kanban-card-row { background: #1a1a1a !important; border-color: #333 !important; }
/* CRM KANBAN TOOLBAR & PAGINATION END */


/* CRM LIVE KANBAN START */

.column-body{
 display:flex;
 flex-direction:column;
 align-items:stretch;
 justify-content:flex-start;
 gap:10px;
 min-height:470px;
 padding:12px
}

.kanban-card{
 --card-stage-color:#3478f6;
 display:flex;
 flex-direction:column;
 gap:11px;
 padding:13px;
 border:1px solid
  color-mix(
   in srgb,
   var(--card-stage-color) 28%,
   var(--line)
  );
 border-top:4px solid
  var(--card-stage-color);
 border-radius:14px;
 background:
  linear-gradient(
   180deg,
   color-mix(
    in srgb,
    var(--card-stage-color) 8%,
    white
   ),
   #fff 72px
  );
 box-shadow:0 8px 20px #1720330b
}

.kanban-card-head{
 display:flex;
 align-items:flex-start;
 justify-content:space-between;
 gap:8px
}

.kanban-card-name{
 min-width:0;
 color:#263247;
 font-size:14px;
 font-weight:900;
 text-decoration:none;
 overflow:hidden;
 text-overflow:ellipsis;
 white-space:nowrap
}

.kanban-card-stage{
 flex:0 0 auto;
 padding:5px 8px;
 border-radius:999px;
 background:
  color-mix(
   in srgb,
   var(--card-stage-color) 11%,
   white
  );
 color:var(--card-stage-color);
 font-size:9px;
 font-weight:900
}

.kanban-card-info{
 display:grid;
 gap:7px
}

.kanban-card-row{
 display:flex;
 align-items:flex-start;
 justify-content:space-between;
 gap:8px;
 padding:7px 8px;
 border:1px solid #eef0f4;
 border-radius:9px;
 background:#fff
}

.kanban-card-row span{
 display:inline-flex;
 align-items:center;
 gap:4px;
 color:#8b94a5;
 font-size:9px;
 font-weight:bold
}
.kanban-card-row span i{
 font-size:11px;
 color:#788294
}

.kanban-card-row strong{
 max-width:155px;
 overflow-wrap:anywhere;
 color:#536074;
 font-size:10px;
 text-align:end
}

.kanban-phone{
 direction:ltr;
 display:inline-block;
 color:#356cb2;
 text-decoration:none
}

.kanban-card-actions{
 display:grid;
 grid-template-columns:
  repeat(2,minmax(0,1fr));
 gap:6px
}

.kanban-card-actions .btn{
 min-height:34px;
 padding:6px 8px;
 font-size:10px;
 font-weight:900;
 box-shadow:none;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 gap:4px
}
.kanban-card-actions .btn i{
 font-size:12px
}

.kanban-card-actions .call{
 grid-column:1/-1;
 border-color:var(--card-stage-color);
 background:var(--card-stage-color)
}
.kanban-card-actions .donation{
 grid-column:1/-1;
 border-color:#16a34a;
 background:#16a34a;
 color:#fff
}
.kanban-card-actions .donation:hover{
 border-color:#15803d;
 background:#15803d
}

.kanban-empty{
 margin:auto
}

@media(max-width:760px){
 .kanban-card-actions{
  grid-template-columns:1fr
 }

 .kanban-card-actions .call{
  grid-column:auto
 }
}

/* KANBAN STAGE STEPPER START */
.kanban-stage-stepper{
 display:flex;
 align-items:center;
 justify-content:center;
 gap:0;
 padding:4px 6px;
 margin:-2px 0
}
.kanban-stepper-step{
 display:flex;
 flex-direction:column;
 align-items:center;
 gap:2px;
 flex:0 0 auto;
 position:relative
}
.kanban-stepper-dot{
 width:8px;
 height:8px;
 border-radius:50%;
 background:#d1d5db;
 border:1.5px solid #d1d5db;
 transition:background .2s,border-color .2s
}
.kanban-stepper-dot.completed{
 background:color-mix(in srgb,var(--card-stage-color) 45%,#d1d5db);
 border-color:color-mix(in srgb,var(--card-stage-color) 45%,#d1d5db)
}
.kanban-stepper-dot.active{
 background:var(--card-stage-color);
 border-color:var(--card-stage-color);
 box-shadow:0 0 0 2.5px color-mix(in srgb,var(--card-stage-color) 22%,transparent)
}
.kanban-stepper-line{
 width:16px;
 height:2px;
 background:#d1d5db;
 flex:0 0 auto;
 align-self:flex-start;
 margin-top:3px
}
.kanban-stepper-line.completed{
 background:color-mix(in srgb,var(--card-stage-color) 45%,#d1d5db)
}
.kanban-stepper-label{
 font-size:7px;
 font-weight:800;
 color:#9ca3af;
 line-height:1;
 margin-top:1px
}
.kanban-stepper-step.active .kanban-stepper-label{
 color:var(--card-stage-color)
}
.kanban-stepper-step.completed .kanban-stepper-label{
 color:color-mix(in srgb,var(--card-stage-color) 60%,#9ca3af)
}
@media(max-width:760px){
 .kanban-stepper-line{width:10px}
}
/* KANBAN STAGE STEPPER END */

/* KANBAN STAGE TRANSITION BUTTONS START */
.kanban-stage-transitions{
 display:flex;
 align-items:center;
 gap:4px;
 padding-top:4px
}
.kanban-stage-btn{
 width:28px;
 height:28px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 padding:0;
 border:1px solid color-mix(in srgb,var(--btn-stage-color,#3478f6) 30%,transparent);
 border-radius:8px;
 background:color-mix(in srgb,var(--btn-stage-color,#3478f6) 8%,transparent);
 color:var(--btn-stage-color,#3478f6);
 font-size:13px;
 cursor:pointer;
 text-decoration:none;
 transition:transform .15s,background .15s,border-color .15s
}
.kanban-stage-btn:hover{
 transform:scale(1.12);
 background:color-mix(in srgb,var(--btn-stage-color,#3478f6) 18%,transparent);
 border-color:color-mix(in srgb,var(--btn-stage-color,#3478f6) 50%,transparent)
}
.kanban-stage-btn:active{
 transform:scale(0.95)
}
@media(max-width:760px){
 .kanban-stage-btn{width:26px;height:26px;font-size:12px}
}
/* KANBAN STAGE TRANSITION BUTTONS END */



/* CRM KANBAN FOLLOWUP SCOPES START */

.kanban-followup-toolbar{
 padding:10px 11px;
 border-bottom:1px solid var(--line);
 background:#fff
}

.kanban-scope-buttons{
 display:grid;
 grid-template-columns:
  repeat(3,minmax(0,1fr));
 gap:5px
}

.kanban-scope-btn{
 min-width:0;
 min-height:38px;
 display:flex;
 flex-direction:column;
 align-items:center;
 justify-content:center;
 gap:2px;
 padding:5px 3px;
 border:1px solid #e5e8ef;
 border-radius:9px;
 background:#f7f8fa;
 color:#697489;
 font-family:inherit;
 cursor:pointer;
 transition:none;
 animation:none
}

.kanban-scope-btn span{
 display:block;
 max-width:100%;
 overflow:hidden;
 text-overflow:ellipsis;
 white-space:nowrap;
 font-size:9px;
 font-weight:900
}

.kanban-scope-btn b{
 font:900 14px var(--font-primary);
 color:inherit
}

.kanban-scope-btn.active{
 border-color:var(--column-color);
 background:
  color-mix(
   in srgb,
   var(--column-color) 11%,
   white
  );
 color:var(--column-color);
 box-shadow:none
}

.kanban-followup-current{
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:7px;
 margin-top:7px;
 color:#7d8798;
 font-size:9px;
 font-weight:bold
}

.kanban-followup-current strong{
 color:#445166;
 font-size:9px
}

.kanban-no-date{
 padding:4px 7px;
 border-radius:999px;
 background:#f1f3f6;
 color:#8a93a2;
 white-space:nowrap
}

.kanban-scope-panel[hidden]{
 display:none!important
}

.kanban-scope-panel{
 display:flex;
 flex-direction:column;
 gap:10px;
 width:100%
}

.kanban-scope-empty{
 min-height:220px;
 display:flex;
 flex-direction:column;
 align-items:center;
 justify-content:center;
 gap:7px;
 padding:18px 10px;
 color:#929bab;
 text-align:center
}

.kanban-scope-empty i{
 width:42px;
 height:42px;
 display:grid;
 place-items:center;
 border-radius:12px;
 background:#f3f5f8;
 font-style:normal;
 font-size:18px
}

.kanban-scope-empty strong{
 color:#657086;
 font-size:11px
}

.kanban-scope-empty p{
 margin:0;
 font-size:9px;
 line-height:1.7
}

@media(max-width:760px){
 .kanban-scope-btn span{
  font-size:8px
 }
}





/* CRM KANBAN FOLLOWUP PRIORITY COLORS START */

/*
 * Customer follow-up priority:
 * overdue  = red
 * today    = orange
 * upcoming = green
 */

.kanban-card[
 data-kanban-lead-scope="overdue"
]{
 --card-stage-color:
  #dc2637!important
}

.kanban-card[
 data-kanban-lead-scope="today"
]{
 --card-stage-color:
  #e59b16!important
}

.kanban-card[
 data-kanban-lead-scope="upcoming"
]{
 --card-stage-color:
  #169a64!important
}

.kanban-scope-btn[
 data-kanban-scope="overdue"
]{
 --followup-scope-color:#dc2637;
 border-color:
  color-mix(
   in srgb,
   #dc2637 28%,
   #e5e8ef
  );
 background:
  color-mix(
   in srgb,
   #dc2637 5%,
   white
  );
 color:#dc2637
}

.kanban-scope-btn[
 data-kanban-scope="today"
]{
 --followup-scope-color:#e59b16;
 border-color:
  color-mix(
   in srgb,
   #e59b16 28%,
   #e5e8ef
  );
 background:
  color-mix(
   in srgb,
   #e59b16 5%,
   white
  );
 color:#b87800
}

.kanban-scope-btn[
 data-kanban-scope="upcoming"
]{
 --followup-scope-color:#169a64;
 border-color:
  color-mix(
   in srgb,
   #169a64 28%,
   #e5e8ef
  );
 background:
  color-mix(
   in srgb,
   #169a64 5%,
   white
  );
 color:#11784e
}

.kanban-scope-btn[
 data-kanban-scope
].active{
 border-color:
  var(
   --followup-scope-color,
   var(--column-color)
  )!important;

 background:
  color-mix(
   in srgb,
   var(
    --followup-scope-color,
    var(--column-color)
   ) 13%,
   white
  )!important;

 color:
  var(
   --followup-scope-color,
   var(--column-color)
  )!important
}

/* CRM KANBAN FOLLOWUP PRIORITY COLORS END */



/* CRM KANBAN NO DATE EMPHASIS START */

/*
 * "Without appointment" is intentionally
 * purple so it is distinct from:
 * overdue red, today orange and upcoming green.
 */

.kanban-followup-current{
 gap:8px!important;
 flex-wrap:wrap
}

.kanban-no-date{
 min-height:34px!important;
 display:inline-flex!important;
 align-items:center!important;
 justify-content:center!important;
 gap:6px!important;
 padding:7px 11px!important;
 border:1px solid #7b61df!important;
 border-radius:10px!important;
 background:#7b61df!important;
 color:#fff!important;
 font-size:10px!important;
 font-weight:900!important;
 line-height:1.2!important;
 white-space:nowrap!important;
 box-shadow:
  0 7px 18px #7b61df35!important
}

.kanban-no-date::before{
 content:"⚠";
 display:inline-block;
 color:#fff;
 font-size:12px;
 line-height:1
}

/* CRM KANBAN NO DATE EMPHASIS END */

/* CRM KANBAN DRAG DROP START */

.kanban-card[draggable="true"]{
 cursor:grab;
 user-select:none
}

.kanban-card[draggable="true"]:active{
 cursor:grabbing
}

.kanban-card.is-dragging{
 opacity:.42!important
}

.kanban-column.is-drop-target{
 outline:3px dashed
  var(--column-color)!important;
 outline-offset:-4px;
 background:
  color-mix(
   in srgb,
   var(--column-color) 5%,
   white
  )
}

.kanban-column.is-drop-target
 .column-head{
 background:
  color-mix(
   in srgb,
   var(--column-color) 15%,
   white
  )!important
}


.kanban-followup-modal{
 position:fixed;
 inset:0;
 z-index:10000;
 display:none;
 align-items:center;
 justify-content:center;
 padding:20px;
 background:#111827a8;
 backdrop-filter:blur(4px)
}

.kanban-followup-modal.open{
 display:flex
}

.kanban-followup-dialog{
 width:min(1000px,96vw);
 height:min(860px,92vh);
 display:flex;
 flex-direction:column;
 overflow:hidden;
 border:1px solid #dfe3ea;
 border-radius:18px;
 background:#fff;
 box-shadow:0 30px 90px #11182755
}

.kanban-followup-modal-head{
 min-height:68px;
 display:flex;
 align-items:center;
 gap:12px;
 padding:12px 16px;
 border-bottom:1px solid #e8ebf0;
 background:#fff
}

.kanban-followup-modal-title{
 min-width:0;
 flex:1
}

.kanban-followup-modal-title h3{
 margin:0;
 color:#28354a;
 font-size:17px
}

.kanban-followup-modal-title p{
 margin:5px 0 0;
 color:#858f9f;
 font-size:10px;
 line-height:1.6
}

.kanban-followup-close{
 width:38px;
 height:38px;
 display:grid;
 place-items:center;
 flex:0 0 38px;
 border:1px solid #e1e5eb;
 border-radius:10px;
 background:#f8f9fb;
 color:#606b7e;
 font:900 20px var(--font-primary);
 cursor:pointer
}

.kanban-followup-frame{
 width:100%;
 min-height:0;
 flex:1;
 border:0;
 background:#f5f6f8
}

html.dark-mode .kanban-followup-dialog,
html.crm-monochrome.dark-mode .kanban-followup-dialog {
 background: #18181b !important;
 border-color: rgba(255, 255, 255, 0.12) !important;
 box-shadow: 0 30px 90px rgba(0, 0, 0, 0.8) !important;
}

html.dark-mode .kanban-followup-modal-head,
html.crm-monochrome.dark-mode .kanban-followup-modal-head {
 background: #18181b !important;
 border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

html.dark-mode .kanban-followup-modal-title h3,
html.crm-monochrome.dark-mode .kanban-followup-modal-title h3 {
 color: #f4f4f5 !important;
}

html.dark-mode .kanban-followup-modal-title p,
html.crm-monochrome.dark-mode .kanban-followup-modal-title p {
 color: #a1a1aa !important;
}

html.dark-mode .kanban-followup-close,
html.crm-monochrome.dark-mode .kanban-followup-close {
 background: #27272a !important;
 border-color: rgba(255, 255, 255, 0.12) !important;
 color: #e4e4e7 !important;
}

html.dark-mode .kanban-followup-close:hover,
html.crm-monochrome.dark-mode .kanban-followup-close:hover {
 background: #3f3f46 !important;
 color: #fff !important;
}

html.dark-mode .kanban-followup-frame,
html.crm-monochrome.dark-mode .kanban-followup-frame {
 background: #09090b !important;
}

html.crm-monochrome:not(.dark-mode) .kanban-followup-dialog {
 background: #fff !important;
 border-color: #e5e5e5 !important;
}
html.crm-monochrome:not(.dark-mode) .kanban-followup-modal-head {
 background: #fafafa !important;
 border-bottom-color: #e5e5e5 !important;
}
html.crm-monochrome:not(.dark-mode) .kanban-followup-modal-title h3 {
 color: #171717 !important;
}
html.crm-monochrome:not(.dark-mode) .kanban-followup-close {
 background: #f5f5f5 !important;
 border-color: #e5e5e5 !important;
 color: #171717 !important;
}
html.crm-monochrome:not(.dark-mode) .kanban-followup-frame {
 background: #fafafa !important;
}

body.kanban-modal-open{
 overflow:hidden
}

.kanban-drag-toast{
 position:fixed;
 left:50%;
 bottom:25px;
 z-index:10020;
 max-width:min(520px,90vw);
 padding:11px 15px;
 border-radius:11px;
 background:#263247;
 color:#fff;
 font-size:11px;
 font-weight:900;
 box-shadow:0 14px 35px #11182735;
 transform:translateX(-50%);
 display:none
}

.kanban-drag-toast.show{
 display:block
}

@media(max-width:700px){
 .kanban-followup-modal{
  padding:7px
 }

 .kanban-followup-dialog{
  width:100%;
  height:96vh;
  border-radius:13px
 }
}

/* CRM KANBAN DRAG DROP END */

/* CRM KANBAN FOLLOWUP SCOPES END */

/* CRM LIVE KANBAN END */


/* CRM KANBAN UTILITY POPUPS START */

.kanban-no-date-slot{
 display:flex;
 align-items:center;
 justify-content:flex-end;
 min-height:28px;
 margin-top:6px
}

.kanban-no-date{
 min-width:0!important;
 min-height:24px!important;
 display:inline-flex!important;
 flex-direction:row!important;
 align-items:center!important;
 justify-content:center!important;
 gap:5px!important;
 margin:0!important;
 padding:3px 7px!important;
 border:1px solid #d9cffb!important;
 border-radius:7px!important;
 background:#f7f4ff!important;
 color:#6b50c9!important;
 font-family:inherit!important;
 font-size:8px!important;
 font-weight:900!important;
 line-height:1.1!important;
 white-space:nowrap!important;
 box-shadow:none!important;
 cursor:pointer!important;
 transition:
  background .12s ease,
  border-color .12s ease,
  transform .12s ease!important
}

.kanban-no-date::before{
 content:""!important;
 display:none!important
}

.kanban-no-date span{
 font-size:8px!important;
 font-weight:900!important
}

.kanban-no-date b{
 min-width:17px;
 height:17px;
 display:inline-grid;
 place-items:center;
 padding:0 4px;
 border-radius:999px;
 background:#7157cf;
 color:#fff;
 font:900 9px var(--font-primary)
}

.kanban-no-date:hover{
 border-color:#7157cf!important;
 background:#efeaff!important;
 transform:translateY(-1px)
}

.kanban-utility-dialog{
 width:min(1180px,96vw)!important;
 height:min(900px,92vh)!important
}

/* CRM KANBAN UTILITY POPUPS END */


/* CRM KANBAN PAGE SIZE 15 V7 START */

/*
 * Make all columns occupy the same grid-row
 * height, even when a column has no toolbar.
 */
.board{
 align-items:stretch
}

.kanban-column{
 display:flex;
 flex-direction:column;
 height:auto!important;
 align-self:stretch
}

.kanban-column .column-body{
 flex:1 1 auto;
 width:100%
}

/*
 * "More" button used after the first
 * 15 cards in the currently selected panel.
 */
.kanban-more-wrap{
 display:flex;
 justify-content:center;
 width:100%;
 padding-top:5px
}

.kanban-more-wrap[hidden]{
 display:none!important
}

.kanban-more-btn{
 width:100%;
 min-height:38px;
 padding:8px 12px;
 border:1px solid
  color-mix(
   in srgb,
   var(--column-color) 30%,
   #dce1e9
  );
 border-radius:10px;
 background:
  color-mix(
   in srgb,
   var(--column-color) 7%,
   white
  );
 color:var(--column-color);
 font-family:inherit;
 font-size:10px;
 font-weight:900;
 cursor:pointer;
 box-shadow:none
}

.kanban-more-btn:hover{
 background:
  color-mix(
   in srgb,
   var(--column-color) 13%,
   white
  )
}

/* CRM KANBAN PAGE SIZE 15 V7 CSS END */

</style>
</head>
<body>
@include('partials.page-loader')

<div class="crm-app app">
    @include('partials.crm-sidebar')

    <main class="crm-main main kanban-main">
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <h1>{{ __('crm.kanban_view') }}</h1>
                </div>
            </div>

            <div class="top-actions">
                <div class="summary">
                    <span>
                        {{ __('crm.total_leads') }}
                        <b>{{ number_format($totalLeads) }}</b>
                    </span>
                    <span>
                        {{ __('crm.status_count') }}
                        <b>{{ count($kanbanColumns) }}</b>
                    </span>
                </div>


                @include('partials.profile-dropdown')
            </div>
        </header>
 <section class="kanban-toolbar" aria-label="{{ __('crm.filter_leads') }}">
  <div class="kanban-toolbar-main">
   <div class="kanban-search-box">
    <i class="bi bi-search kanban-search-icon" aria-hidden="true"></i>
    <input
     type="search"
     id="kanbanSearchInput"
     class="kanban-search-input"
     placeholder="{{ __('crm.lead_search_placeholder') }}"
     aria-label="{{ __('crm.lead_search_placeholder') }}"
     autocomplete="off"
    >
    <button type="button" id="kanbanSearchClear" class="kanban-search-clear" aria-label="{{ __('crm.close') }}" style="display:none;">
     <i class="bi bi-x-circle-fill"></i>
    </button>
   </div>

   <div class="kanban-filter-select-wrap">
    <label for="kanbanScopeFilterSelect" class="kanban-filter-label">
     <i class="bi bi-calendar-event"></i>
     <span>{{ __('crm.period') }}:</span>
    </label>
    <select id="kanbanScopeFilterSelect" class="kanban-filter-select" aria-label="{{ __('crm.period') }}">
     <option value="all">{{ __('crm.all_leads') }}</option>
     <option value="today">{{ __('crm.today') }}</option>
     <option value="overdue">{{ __('crm.overdue') }}</option>
     <option value="upcoming">{{ __('crm.upcoming') }}</option>
     <option value="no_date">{{ __('crm.no_date') }}</option>
    </select>
   </div>

   <div class="kanban-filter-select-wrap">
    <label for="kanbanEmployeeFilterSelect" class="kanban-filter-label">
     <i class="bi bi-person-badge"></i>
     <span>{{ __('crm.employee') }}:</span>
    </label>
    <select id="kanbanEmployeeFilterSelect" class="kanban-filter-select" aria-label="{{ __('crm.employee') }}">
     <option value="">{{ __('crm.all_employees') }}</option>
     @if (isset($employees))
      @foreach ($employees as $emp)
       <option value="{{ $emp->id }}" data-name="{{ $emp->name }}">{{ $emp->name }}</option>
      @endforeach
     @endif
    </select>
   </div>

   <div class="kanban-filter-select-wrap">
    <label for="kanbanLimitSelect" class="kanban-filter-label">
     <i class="bi bi-list-ol"></i>
     <span>{{ __('crm.leads_per_column') }}:</span>
    </label>
    <select id="kanbanLimitSelect" class="kanban-filter-select" aria-label="{{ __('crm.leads_per_column') }}">
     <option value="5">5</option>
     <option value="10" selected>10</option>
     <option value="15">15</option>
     <option value="25">25</option>
     <option value="50">50</option>
     <option value="all">{{ __('crm.all') }}</option>
    </select>
   </div>

   <button
    type="button"
    id="kanbanResetFiltersBtn"
    class="kanban-reset-btn"
    style="display:none;"
    title="{{ __('crm.reset_filters') }}"
   >
    <i class="bi bi-x-circle"></i>
    <span>{{ __('crm.clear_filters') }}</span>
   </button>
  </div>

  <div class="kanban-toolbar-status" id="kanbanToolbarStatus" style="display:none;">
   <span class="kanban-filter-badge">
    <i class="bi bi-funnel-fill"></i>
    <span id="kanbanFilterCountText"></span>
   </span>
  </div>
 </section>


 <section class="board-shell">
  <div
   class="board"
   aria-label="{{ __('crm.kanban_board_title') }}"
  >
   @foreach (
    $kanbanColumns
    as $column
   )
    <!-- CRM KANBAN DIRECT STATUS COLUMNS V4 START -->
    @php
     $kanbanDirectStatus =
      in_array(
       $column['code'],
       [
        'new',
        'not_interested',
       ],
       true
      );
    @endphp

    <article
     class="kanban-column {{ $column['class'] }}"
     data-kanban-column="{{ $column['code'] }}"
     data-kanban-status-id="{{ $column['status_id'] }}"
     data-kanban-status-name="{{ $column['name'] }}"
     style="
      --column-color:
       {{ $column['status_color'] }};
     "
    >
     @php
      $columnIconClass = match($column['code']) {
          'new' => 'bi bi-person-plus-fill',
          'no-answer', 'no_answer' => 'bi bi-telephone-x-fill',
          'not_interested', 'not-interested' => 'bi bi-x-circle-fill',
          'donor' => 'bi bi-heart-fill',
          default => !empty($column['icon']) ? (str_starts_with($column['icon'], 'bi-') ? 'bi ' . $column['icon'] : 'bi bi-' . $column['icon']) : 'bi bi-app-indicator',
      };
     @endphp

     <header class="column-head">
      <span class="column-icon">
       <i class="{{ $columnIconClass }}"></i>
      </span>
      <h2 class="column-title">
       {{ $column['name'] }}
      </h2>

      <span
       class="column-count"
       data-kanban-visible-count
       data-kanban-total-count-value="{{
        $column['total_count']
       }}"
       title="{{ __('crm.all_leads_in_stage') }}"
      >
       {{
        number_format(
         $column['total_count']
        )
       }}
      </span>
     </header>


          @if (!$kanbanDirectStatus)
<div class="kanban-followup-toolbar">
      <div class="kanban-scope-buttons">
       <button
        class="kanban-scope-btn active"
        type="button"
        data-kanban-scope="today"
        data-count="{{
         $column['scope_counts']['today']
        }}"
        data-label="{{ __('crm.today') }}"
        aria-pressed="true"
        title="{{ __('crm.today') }}"
       >
        <span><i class="bi bi-calendar-check" style="margin-inline-end:3px"></i> {{ __('crm.today') }}</span>
        <b>
         {{
          number_format(
           $column[
            'scope_counts'
           ]['today']
          )
         }}
        </b>
       </button>

       <button
        class="kanban-scope-btn"
        type="button"
        data-kanban-scope="overdue"
        data-count="{{
         $column['scope_counts']['overdue']
        }}"
        data-label="{{ __('crm.overdue') }}"
        aria-pressed="false"
        title="{{ __('crm.overdue') }}"
       >
        <span><i class="bi bi-exclamation-triangle" style="margin-inline-end:3px"></i> {{ __('crm.overdue') }}</span>
        <b>
         {{
          number_format(
           $column[
            'scope_counts'
           ]['overdue']
          )
         }}
        </b>
       </button>

       <button
        class="kanban-scope-btn"
        type="button"
        data-kanban-scope="upcoming"
        data-count="{{
         $column['scope_counts']['upcoming']
        }}"
        data-label="{{ __('crm.upcoming') }}"
        aria-pressed="false"
        title="{{ __('crm.upcoming') }}"
       >
        <span><i class="bi bi-calendar-week" style="margin-inline-end:3px"></i> {{ __('crm.upcoming') }}</span>
        <b>
         {{
          number_format(
           $column[
            'scope_counts'
           ]['upcoming']
          )
         }}
        </b>
       </button>
      </div>

      <div class="kanban-no-date-slot">
       @if (
        (
         $column[
          'no_date_count'
         ]
         ?? 0
        ) > 0
       )
        <button
         class="kanban-no-date"
         type="button"
         data-kanban-no-date-popup
         data-status-name="{{ $column['name'] }}"
         data-count="{{ $column['no_date_count'] }}"
         data-popup-url="{{
          route(
           'v2.leads',
           [
            'status' =>
             $column['code'],

            'follow_up' =>
             'none',
           ]
          )
         }}"
         title="{{ __('crm.no_date') }}"
        >
         <span><i class="bi bi-calendar-minus" style="margin-inline-end:3px"></i> {{ __('crm.no_date') }}</span>

         <b>
          {{
           number_format(
            $column[
             'no_date_count'
            ]
           )
          }}
         </b>
        </button>
       @endif
      </div>

      <div class="kanban-followup-current">
       <strong data-kanban-scope-label>
        {{ __('crm.today') }}
       </strong>

       <span>
        {{ __('crm.total_status') }}:
        {{ number_format(
         $column['total_count']
        ) }}
       </span>

       
      </div>
     </div>
     @endif

     <div class="column-body">
     @foreach (
      [
       'today' =>
        __('crm.today'),

       'overdue' =>
        __('crm.overdue'),

       'upcoming' =>
        __('crm.upcoming'),
      ]
      as $scope => $scopeLabel
     )
       @php
        if ($kanbanDirectStatus) {
         $scopeLeads =
          $scope === 'today'
           ? ($column['leads'] ?? collect())
           : collect();

         $scopeLabel =
          __('crm.all_leads_in_stage');
        } else {
         $scopeLeads =
          $column[
           'scope_leads'
          ][$scope] ?? collect();
        }
       @endphp

       <div
        class="kanban-scope-panel"
        data-kanban-panel="{{ $scope }}"
        @if ($scope !== 'today')
         hidden
        @endif
       >
        @forelse (
         $scopeLeads
         as $lead
        )
         <article
          class="kanban-card"
          draggable="{{ auth()->user()->can('leads.followups.create') ? 'true' : 'false' }}"
          data-kanban-lead="{{ $lead->id }}"
          data-kanban-lead-name="{{ $lead->name }}"
          data-kanban-lead-phone="{{ $lead->phone }}"
          data-kanban-lead-company="{{ $lead->company_name ?: $lead->source }}"
          data-kanban-lead-employee="{{ $lead->assignedUser?->name ?? $lead->assigned_employee }}"
          data-kanban-lead-employee-id="{{ $lead->assigned_user_id ?? '' }}"
          data-kanban-lead-scope="{{ $lead->next_follow_up_at ? $scope : 'no_date' }}"
          data-kanban-lead-has-date="{{ $lead->next_follow_up_at ? '1' : '0' }}"
          data-current-status-id="{{ $lead->lead_status_id ?? ($column['status_id'] ?? '') }}"
          data-current-status-name="{{ $lead->status?->name_ar ?? $column['name'] }}"
          data-followup-url="{{ route(
           'v2.leads.followups.index',
           $lead
          ) }}"
          style="
           --card-stage-color:
            {{ $column['stage_color'] }};
          "
          data-stage-position="{{ $column['position'] ?? 0 }}"
         >
          <div class="kanban-card-head">
           <a
            class="kanban-card-name"
            href="{{ route(
             'v2.leads.show',
             $lead
            ) }}"
           >
            {{ $lead->name }}
           </a>

           <span class="kanban-card-stage">
            {{
             $column['stage_name']
             ?: $column['name']
            }}
           </span>
          </div>

          {{-- Stage progress stepper --}}
          <div class="kanban-stage-stepper">
           @foreach ($kanbanColumns as $stepIdx => $stepCol)
            @if ($stepIdx > 0)
             <span class="kanban-stepper-line{{ ($column['position'] ?? 0) > ($kanbanColumns[$stepIdx - 1]['position'] ?? 0) ? ' completed' : '' }}"></span>
            @endif
            @php
             $stepPos = $stepCol['position'] ?? $stepIdx;
             $curPos = $column['position'] ?? 0;
             $stepClass = $stepPos === $curPos ? 'active' : ($stepPos < $curPos ? 'completed' : '');
             $stepShort = match($stepCol['code']) {
              'new' => 'ج',
              'no_answer' => 'ل',
              'not_interested' => 'غ',
              'donor' => 'م',
              default => mb_substr($stepCol['name'], 0, 1),
             };
            @endphp
            <span class="kanban-stepper-step {{ $stepClass }}">
             <span class="kanban-stepper-dot {{ $stepClass }}"></span>
             <span class="kanban-stepper-label">{{ $stepShort }}</span>
            </span>
           @endforeach
          </div>

          <div class="kanban-card-info">
           <div class="kanban-card-row">
            <span><i class="bi bi-telephone"></i> {{ __('crm.phone') }}</span>

            <strong>
             @if ($lead->phone)
              <a
               class="kanban-phone"
               href="tel:{{
                preg_replace(
                 '/[^0-9+]/',
                 '',
                 (string) $lead->phone
                )
               }}"
              >
               {{ $lead->phone }}
              </a>
             @else
              {{ __('crm.not_registered') }}
             @endif
            </strong>
           </div>

           <div class="kanban-card-row">
            <span><i class="bi bi-building"></i> {{ __('crm.company_or_source') }}</span>

            <strong>
             {{
              $lead->company_name
              ?: $lead->source
              ?: __('crm.not_specified')
             }}
            </strong>
           </div>

           <div class="kanban-card-row">
            <span><i class="bi bi-person-badge"></i> {{ __('crm.employee') }}</span>

            <strong>
             {{
              $lead->assignedUser?->name
              ?? $lead->assigned_employee
              ?: __('crm.unassigned')
             }}
            </strong>
           </div>

           <div class="kanban-card-row">
            <span><i class="bi bi-clock-history"></i> {{ __('crm.followup_date') }}</span>

            <strong>
             {{
              $lead
               ->next_follow_up_at
               ?->format(
                'd/m/Y H:i'
               )
              ?? __('crm.no_date')
             }}
            </strong>
           </div>
          </div>

          <div class="kanban-card-actions">
           @if ($lead->phone)
            @php
             $phoneClean = preg_replace('/[^0-9+]/', '', (string) $lead->phone);
            @endphp
            <a
             class="btn call"
             @can('leads.followups.create')
             data-transition-popup="{{ route(
              'v2.leads.followups.index',
              ['lead' => $lead, 'channel' => 'call']
             ) }}"
             data-transition-context="kanban"
             data-lead-name="{{ $lead->name }}"
             @endcan
             data-sip-href="sip:{{ $phoneClean }}"
             href="sip:{{ $phoneClean }}"
             draggable="false"
             title="{{ __('crm.call_via_microsip') }}"
            >
             <i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.call') }}
            </a>
           @endif

           @can('leads.followups.create')
           <a
            class="btn donation"
            href="{{ route(
             'v2.leads.followups.index',
             [$lead, 'make_donation' => 1]
            ) }}"
            data-transition-popup="{{ route(
             'v2.leads.followups.index',
             [$lead, 'make_donation' => 1]
            ) }}"
            data-transition-context="kanban"
            data-lead-name="{{ $lead->name }}"
            title="{{ __('crm.record_donation') }}"
           >
            <i class="bi bi-heart-fill"></i> {{ __('crm.record_donation') }}
           </a>
           @endcan

           <a
            class="btn light"
            href="{{ route(
             'v2.leads.show',
             $lead
            ) }}"
            data-kanban-customer-popup
            draggable="false">
            <i class="bi bi-eye"></i> {{ __('crm.view_lead') }}
           </a>

           @can('leads.followups.create')
           <a
            class="btn light"
            href="{{ route(
             'v2.leads.followups.index',
             $lead
            ) }}"
            data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
            data-transition-context="kanban"
            data-lead-name="{{ $lead->name }}"
            draggable="false">
            <i class="bi bi-plus-circle"></i> {{ __('crm.log_followup') }}
           </a>
           @endcan
          </div>

          {{-- Stage transition buttons --}}
          @can('leads.followups.create')
          <div class="kanban-stage-transitions">
           @php
            $transitionStages = [
             'new' => ['icon' => 'bi-plus-circle', 'color' => '#3478f6'],
             'no_answer' => ['icon' => 'bi-telephone-x', 'color' => '#e59b16'],
             'not_interested' => ['icon' => 'bi-x-circle', 'color' => '#dc2637'],
             'donor' => ['icon' => 'bi-heart', 'color' => '#16a34a'],
            ];
           @endphp
           @foreach ($kanbanColumns as $targetCol)
            @if ($targetCol['code'] !== $column['code'])
             <a
              class="kanban-stage-btn"
              style="--btn-stage-color:{{ $transitionStages[$targetCol['code']]['color'] ?? $targetCol['stage_color'] }}"
              href="{{ route('v2.leads.followups.index', [$lead, 'kanban_popup' => 1, 'target_status_code' => $targetCol['code']]) }}"
              data-transition-popup="{{ route('v2.leads.followups.index', [$lead, 'target_status_code' => $targetCol['code']]) }}"
              data-transition-context="kanban"
              data-lead-name="{{ $lead->name }}"
              data-transition-description="{{ __('crm.move_to_stage') }} {{ $targetCol['name'] }}"
              draggable="false"
              title="{{ __('crm.move_to_stage') }} {{ $targetCol['name'] }}"
             >
              <i class="bi {{ $transitionStages[$targetCol['code']]['icon'] ?? 'bi-arrow-right-circle' }}"></i>
             </a>
            @endif
           @endforeach
          </div>
          @endcan
         </article>
        @empty
         <div class="kanban-scope-empty">
          <i><i class="bi bi-inbox-fill"></i></i>

          <strong>
           {{ __('crm.no_leads_found') }}
          </strong>

          <p>
           {{ __('crm.no_leads_in_column') }}
           {{ $column['name'] }}
           {{ __('crm.in_scope') }}
           {{ $scopeLabel }}.
         </div>
        @endforelse
       </div>
      @endforeach
     </div>
     <footer class="kanban-column-pagination" data-kanban-pagination>
      <button
       type="button"
       class="kanban-page-btn"
       data-page-action="prev"
       title="{{ __('crm.previous') }}"
       aria-label="{{ __('crm.previous') }}"
      >
       <i class="bi bi-chevron-right ltr:rotate-180"></i>
      </button>
      <span class="kanban-page-info" data-page-info>1 / 1</span>
      <button
       type="button"
       class="kanban-page-btn"
       data-page-action="next"
       title="{{ __('crm.next') }}"
       aria-label="{{ __('crm.next') }}"
      >
       <i class="bi bi-chevron-left ltr:rotate-180"></i>
      </button>
     </footer>
    </article>
   @endforeach
  </div>
 </section>
<!-- CRM KANBAN DIRECT STATUS COLUMNS V4 END -->
</main>

<!-- CRM KANBAN UTILITY MODAL START -->
<div
 class="kanban-followup-modal"
 id="crmKanbanUtilityModal"
 role="dialog"
 aria-modal="true"
 aria-hidden="true"
 aria-labelledby="crmKanbanUtilityTitle"
>
 <div
  class="kanban-followup-dialog kanban-utility-dialog"
 >
  <header class="kanban-followup-modal-head">
   <div class="kanban-followup-modal-title">
    <h3 id="crmKanbanUtilityTitle">
     Kanban
    </h3>

    <p id="crmKanbanUtilityDescription">
     {{ __('crm.view_in_kanban') }}
    </p>
   </div>

   <button
    class="kanban-followup-close"
    id="crmKanbanUtilityClose"
    type="button"
    aria-label="{{ __('crm.close') }}"
    title="{{ __('crm.close') }}"
   >
    ×
   </button>
  </header>

  <iframe
   class="kanban-followup-frame"
   id="crmKanbanUtilityFrame"
   src="about:blank"
   title="{{ __('crm.kanban_popup') }}"
  ></iframe>
 </div>
</div>
<!-- CRM KANBAN UTILITY MODAL END -->


<!-- CRM KANBAN CARD ACTION MODAL START -->
<div
 class="kanban-followup-modal"
 id="crmKanbanActionModal"
 role="dialog"
 aria-modal="true"
 aria-hidden="true"
 aria-labelledby="crmKanbanActionTitle"
>
 <div class="kanban-followup-dialog">
  <header class="kanban-followup-modal-head">
   <div class="kanban-followup-modal-title">
    <h3 id="crmKanbanActionTitle">
     {{ __('crm.lead_data') }}
    </h3>

    <p id="crmKanbanActionDescription">
     {{ __('crm.view_data_in_kanban') }}
    </p>
   </div>

   <button
    class="kanban-followup-close"
    id="crmKanbanActionClose"
    type="button"
    aria-label="{{ __('crm.close') }}"
    title="{{ __('crm.close') }}"
   >
    ×
   </button>
  </header>

  <iframe
   class="kanban-followup-frame"
   id="crmKanbanActionFrame"
   src="about:blank"
   title="{{ __('crm.client_data') }}"
  ></iframe>
 </div>
</div>
<!-- CRM KANBAN CARD ACTION MODAL END -->


@include('partials.transition-popup')

<div
 class="kanban-drag-toast"
 id="crmKanbanDragToast"
 role="status"
 aria-live="polite"
></div>



<!-- CRM KANBAN FOLLOWUP FILTER JS START -->
<script>
document.addEventListener(
 'DOMContentLoaded',
 () => {
  document
   .querySelectorAll(
    '[data-kanban-column]'
   )
   .forEach(
    (column) => {
     const buttons =
      Array.from(
       column.querySelectorAll(
        '[data-kanban-scope]'
       )
      );

     const panels =
      Array.from(
       column.querySelectorAll(
        '[data-kanban-panel]'
       )
      );

     const visibleCount =
      column.querySelector(
       '[data-kanban-visible-count]'
      );

     const scopeLabel =
      column.querySelector(
       '[data-kanban-scope-label]'
      );

     buttons.forEach(
      (button) => {
       button.addEventListener(
        'click',
        () => {
         const scope =
          button.dataset.kanbanScope;

         buttons.forEach(
          (item) => {
           const active =
            item === button;

           item.classList.toggle(
            'active',
            active
           );

           item.setAttribute(
            'aria-pressed',
            active
             ? 'true'
             : 'false'
           );
          }
         );

         panels.forEach(
          (panel) => {
           panel.hidden =
            panel.dataset.kanbanPanel
            !== scope;
          }
         );

         if (visibleCount) {
          visibleCount.textContent =
           button.dataset.count
           || '0';
         }

         if (scopeLabel) {
          scopeLabel.textContent =
           button.dataset.label
           || '';
         }
        }
       );
      }
     );
    }
   );
 }
);
</script>
<!-- CRM KANBAN FOLLOWUP FILTER JS END -->


<!-- CRM KANBAN DRAG DROP JS START -->
<script>
(() => {

 const toast =
  document.getElementById(
   'crmKanbanDragToast'
  );

 let dragData = null;
 let toastTimer = null;

 const clearDropTargets = () => {
  document
   .querySelectorAll(
    '.kanban-column.is-drop-target'
   )
   .forEach(
    (column) => {
     column.classList.remove(
      'is-drop-target'
     );
    }
   );
 };

 const showToast = (message) => {
  if (!toast) {
   return;
  }

  window.clearTimeout(
   toastTimer
  );

  toast.textContent = message;

  toast.classList.add(
   'show'
  );

  toastTimer =
   window.setTimeout(
    () => {
     toast.classList.remove(
      'show'
     );
    },
    2600
   );
 };
 window.showKanbanToast = showToast;

 try {
  const pendingToast = sessionStorage.getItem('crm_kanban_toast');
  if (pendingToast) {
   sessionStorage.removeItem('crm_kanban_toast');
   showToast(pendingToast);
  }
 } catch (e) {}

 const openFollowupModal = (targetColumn) => {
  if (!dragData || !window.crmTransitionPopup) {
   return;
  }

  const targetStatusId = targetColumn.dataset.kanbanStatusId || '';
  const targetStatusName = targetColumn.dataset.kanbanStatusName || '';

  if (!targetStatusId) {
   showToast(@json(__('crm.stage_has_no_status_for_transfer')));
   dragData = null;
   return;
  }

  if (String(dragData.currentStatusId) === String(targetStatusId)) {
   showToast(@json(__('crm.lead_already_in_status')));
   dragData = null;
   return;
  }

  const url = new URL(dragData.followupUrl, window.location.href);
  url.searchParams.set('target_status_id', targetStatusId);
  url.searchParams.set('context', 'kanban');

  const heading = @json(__('crm.move_lead_to'))
   .replace(':lead', dragData.leadName)
   .replace(':status', targetStatusName);
  const description = @json(__('crm.from_status_to_status_notice'))
   .replace(':from', dragData.currentStatusName)
   .replace(':to', targetStatusName);

  window.crmTransitionPopup.open(url.toString(), heading, description, 'kanban');
  dragData = null;
 };

 document
  .querySelectorAll(
   '.kanban-card[draggable="true"]'
  )
  .forEach(
   (card) => {
    card.addEventListener(
     'dragstart',
     (event) => {
      dragData = {
       leadId:
        card.dataset
         .kanbanLead || '',

       leadName:
        card.dataset
         .kanbanLeadName
         || @json(__('crm.client')),

       currentStatusId:
        card.dataset
         .currentStatusId
         || '',

       currentStatusName:
        card.dataset
         .currentStatusName
         || '',

       followupUrl:
        card.dataset
         .followupUrl
         || '',
      };

      card.classList.add(
       'is-dragging'
      );

      if (event.dataTransfer) {
       event.dataTransfer
        .effectAllowed = 'move';

       event.dataTransfer
        .setData(
         'text/plain',
         dragData.leadId
        );
      }
     }
    );

    card.addEventListener(
     'dragend',
     () => {
      card.classList.remove(
       'is-dragging'
      );

      clearDropTargets();
     }
    );
   }
  );

 document
  .querySelectorAll(
   '[data-kanban-status-id]'
  )
  .forEach(
   (column) => {
    column.addEventListener(
     'dragover',
     (event) => {
      if (!dragData) {
       return;
      }

      event.preventDefault();

      if (event.dataTransfer) {
       event.dataTransfer
        .dropEffect = 'move';
      }

      clearDropTargets();

      column.classList.add(
       'is-drop-target'
      );
     }
    );

    column.addEventListener(
     'drop',
     (event) => {
      event.preventDefault();

      clearDropTargets();

      openFollowupModal(
       column
      );
     }
    );
   }
  );

})();
</script>
<!-- CRM KANBAN DRAG DROP JS END -->




<!-- CRM KANBAN CUSTOMER DETAIL POPUP START -->
<script>
(() => {
 const modal = document.getElementById('crmKanbanActionModal');
 const frame = document.getElementById('crmKanbanActionFrame');
 const closeButton = document.getElementById('crmKanbanActionClose');
 const title = document.getElementById('crmKanbanActionTitle');
 const description = document.getElementById('crmKanbanActionDescription');

 if (!modal || !frame) return;

 const closePopup = () => {
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('kanban-modal-open');
  frame.src = 'about:blank';
 };

 const openPopup = (url, heading, subheading) => {
  if (title) title.textContent = heading;
  if (description) description.textContent = subheading;
  frame.src = url;
  modal.classList.add('open');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('kanban-modal-open');
 };

 document
  .querySelectorAll('[data-kanban-customer-popup]')
  .forEach((button) => {
   button.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    const href = button.getAttribute('href');
    if (!href) return;

    const card = button.closest('[data-kanban-lead]');
    const leadName = card?.dataset.kanbanLeadName || @json(__('crm.client'));
    const statusName = card?.dataset.currentStatusName || '';

    openPopup(
     new URL(href, window.location.href).toString(),
     @json(__('crm.view_lead_details_for')).replace(':name', leadName),
     statusName
      ? @json(__('crm.current_status_prefix')) + statusName
      : @json(__('crm.view_lead_details_hint'))
    );
   });

   button.addEventListener('dragstart', (event) => {
    event.preventDefault();
    event.stopPropagation();
   });
  });

 closeButton?.addEventListener('click', closePopup);
 modal.addEventListener('click', (event) => {
  if (event.target === modal) closePopup();
 });
 document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && modal.classList.contains('open')) {
   closePopup();
  }
 });
})();
</script>
<!-- CRM KANBAN CUSTOMER DETAIL POPUP END -->


<!-- CRM KANBAN FIXED TOTAL COUNT START -->
<script>
(() => {
 const formatter =
  new Intl.NumberFormat(
   'en-US'
  );

 document
  .querySelectorAll(
   '[data-kanban-column]'
  )
  .forEach(
   (column) => {
    const counter =
     column.querySelector(
      '[data-kanban-total-count-value]'
     );

    if (!counter) {
     return;
    }

    const total =
     Number(
      counter.dataset
       .kanbanTotalCountValue
      || 0
     );

    const keepTotal = () => {
     counter.textContent =
      formatter.format(
       total
      );
    };

    keepTotal();

    column
     .querySelectorAll(
      '[data-kanban-scope]'
     )
     .forEach(
      (button) => {
       button.addEventListener(
        'click',
        () => {
         /*
          * Existing scope script may update
          * the header with the selected scope.
          * Restore the permanent STATUS TOTAL
          * immediately afterwards.
          */
         window.setTimeout(
          keepTotal,
          0
         );
        }
       );
      }
     );
   }
  );
})();
</script>
<!-- CRM KANBAN FIXED TOTAL COUNT END -->


<!-- CRM KANBAN UTILITY POPUPS JS START -->
<script>
(() => {
 const modal =
  document.getElementById(
   'crmKanbanUtilityModal'
  );

 const frame =
  document.getElementById(
   'crmKanbanUtilityFrame'
  );

 const closeButton =
  document.getElementById(
   'crmKanbanUtilityClose'
  );

 const title =
  document.getElementById(
   'crmKanbanUtilityTitle'
  );

 const description =
  document.getElementById(
   'crmKanbanUtilityDescription'
  );

 const leadsIndexUrl =
  @json(
   route('v2.leads')
  );

 if (
  !modal
  || !frame
 ) {
  return;
 }

 let utilityMode = '';

 const closePopup = () => {
  modal.classList.remove(
   'open'
  );

  modal.setAttribute(
   'aria-hidden',
   'true'
  );

  document.body.classList.remove(
   'kanban-modal-open'
  );

  frame.src = 'about:blank';
  utilityMode = '';
 };

 const openPopup = (
  url,
  heading,
  subheading,
  mode
 ) => {
  utilityMode = mode;

  if (title) {
   title.textContent =
    heading;
  }

  if (description) {
   description.textContent =
    subheading;
  }

  frame.src = url;

  modal.classList.add(
   'open'
  );

  modal.setAttribute(
   'aria-hidden',
   'false'
  );

  document.body.classList.add(
   'kanban-modal-open'
  );
 };

 document
  .querySelectorAll(
   '[data-kanban-no-date-popup]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopPropagation();

      const url =
       button.dataset
        .popupUrl;

      if (!url) {
       return;
      }

      const statusName =
       button.dataset
        .statusName
       || @json(__('crm.stage_label'));

      const count =
       button.dataset
        .count
       || '0';

      openPopup(
       url,
       @json(__('crm.undated_leads_for_stage'))
        .replace(':stage', statusName),
       @json(__('crm.undated_leads_count'))
        .replace(':count', count),
       'no_date'
      );
     }
    );
   }
  );


 /*
  * Normal successful creation redirects
  * from /leads/create back to /leads.
  * When that happens inside the iframe,
  * refresh Kanban so the new customer
  * appears immediately.
  */
 frame.addEventListener(
  'load',
  () => {
   if (
    utilityMode !== 'create'
   ) {
    return;
   }

   try {
    const current =
     new URL(
      frame.contentWindow
       .location.href
     );

    const index =
     new URL(
      leadsIndexUrl,
      window.location.href
     );

    const clean = (value) =>
     value.replace(
      /\/+$/,
      ''
     );

    if (
     current.origin
      === index.origin
     &&
     clean(current.pathname)
      === clean(index.pathname)
     &&
     current.search === ''
    ) {
     window.location.reload();
    }
   } catch (error) {
    console.error(
     'Unable to inspect create popup.',
     error
    );
   }
  }
 );

 closeButton?.addEventListener(
  'click',
  closePopup
 );

 modal.addEventListener(
  'click',
  (event) => {
   if (
    event.target === modal
   ) {
    closePopup();
   }
  }
 );

 document.addEventListener(
  'keydown',
  (event) => {
   if (
    event.key === 'Escape'
    && modal.classList.contains(
     'open'
    )
   ) {
    closePopup();
   }
  }
 );

 /*
  * Keep the number beside each stage as
  * the TOTAL number of customers even
  * when changing follow-up filters.
  */
 document
  .querySelectorAll(
   '[data-kanban-column]'
  )
  .forEach(
   (column) => {
    const counter =
     column.querySelector(
      '[data-kanban-total-count-value]'
     );

    if (!counter) {
     return;
    }

    const total =
     Number(
      counter.dataset
       .kanbanTotalCountValue
      || 0
     );

    const restoreTotal = () => {
     counter.textContent =
      new Intl.NumberFormat(
       'en-US'
      ).format(
       total
      );
    };

    restoreTotal();

    column
     .querySelectorAll(
      '[data-kanban-scope]'
     )
     .forEach(
      (button) => {
       button.addEventListener(
        'click',
        () => {
         window.setTimeout(
          restoreTotal,
          0
         );
        }
       );
      }
     );
   }
  );
})();
</script>
<!-- CRM KANBAN UTILITY POPUPS JS END -->


<!-- CRM KANBAN FILTER & PAGINATION JS START -->
<script>
(() => {
 const searchInput = document.getElementById('kanbanSearchInput');
 const searchClear = document.getElementById('kanbanSearchClear');
 const scopeSelect = document.getElementById('kanbanScopeFilterSelect');
 const employeeSelect = document.getElementById('kanbanEmployeeFilterSelect');
 const limitSelect = document.getElementById('kanbanLimitSelect');
 const resetBtn = document.getElementById('kanbanResetFiltersBtn');
 const statusToolbar = document.getElementById('kanbanToolbarStatus');
 const filterCountText = document.getElementById('kanbanFilterCountText');
 const columns = Array.from(document.querySelectorAll('[data-kanban-column]'));

 if (!columns.length) return;

 let searchQuery = '';
 let scopeFilter = 'all';
 let employeeFilter = '';
 let leadsLimit = 10;
 const columnPages = new Map();

 const normalize = (str) => (str || '').toString().trim().toLowerCase();

 const matchesCard = (card) => {
  if (searchQuery) {
   const name = normalize(card.dataset.kanbanLeadName);
   const phone = normalize(card.dataset.kanbanLeadPhone);
   const company = normalize(card.dataset.kanbanLeadCompany);
   const employee = normalize(card.dataset.kanbanLeadEmployee);
   if (!name.includes(searchQuery) &&
       !phone.includes(searchQuery) &&
       !company.includes(searchQuery) &&
       !employee.includes(searchQuery)) {
    return false;
   }
  }

  if (employeeFilter) {
   const empId = card.dataset.kanbanLeadEmployeeId || '';
   const empName = card.dataset.kanbanLeadEmployee || '';
   if (empId !== employeeFilter && normalize(empName) !== normalize(employeeFilter)) {
    return false;
   }
  }

  if (scopeFilter !== 'all') {
   const cardScope = card.dataset.kanbanLeadScope || '';
   const hasDate = card.dataset.kanbanLeadHasDate === '1';
   if (scopeFilter === 'no_date') {
    if (hasDate) return false;
   } else {
    if (cardScope !== scopeFilter) return false;
   }
  }

  return true;
 };

 const updateColumn = (column) => {
  const visibleCounter = column.querySelector('[data-kanban-visible-count]');
  const pagination = column.querySelector('[data-kanban-pagination]');
  const pageInfo = pagination?.querySelector('[data-page-info]');
  const prevBtn = pagination?.querySelector('[data-page-action="prev"]');
  const nextBtn = pagination?.querySelector('[data-page-action="next"]');
  const columnBody = column.querySelector('.column-body');

  const panels = Array.from(column.querySelectorAll('[data-kanban-panel]'));
  let activeCards = [];
  const activePanel = panels.find(p => !p.hidden) || panels[0];

  if (activePanel) {
   activeCards = Array.from(activePanel.querySelectorAll('.kanban-card'));
  } else {
   activeCards = Array.from(column.querySelectorAll('.kanban-card'));
  }

  const matchingCards = [];
  const nonMatchingCards = [];

  activeCards.forEach(card => {
   if (matchesCard(card)) {
    matchingCards.push(card);
   } else {
    nonMatchingCards.push(card);
   }
  });

  nonMatchingCards.forEach(card => {
   card.style.display = 'none';
  });

  const limit = (leadsLimit === 'all' || leadsLimit <= 0) ? Infinity : Number(leadsLimit);
  const totalItems = matchingCards.length;
  const totalPages = Math.max(1, limit === Infinity ? 1 : Math.ceil(totalItems / limit));

  let currentPage = columnPages.get(column) || 1;
  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;
  columnPages.set(column, currentPage);

  const startIdx = limit === Infinity ? 0 : (currentPage - 1) * limit;
  const endIdx = limit === Infinity ? totalItems : startIdx + limit;

  matchingCards.forEach((card, idx) => {
   if (idx >= startIdx && idx < endIdx) {
    card.style.display = '';
   } else {
    card.style.display = 'none';
   }
  });

  if (visibleCounter) {
   visibleCounter.textContent = new Intl.NumberFormat('en-US').format(totalItems);
  }

  let emptyFilterMsg = column.querySelector('.kanban-filter-empty');
  if (totalItems === 0 && activeCards.length > 0) {
   if (!emptyFilterMsg) {
    emptyFilterMsg = document.createElement('div');
    emptyFilterMsg.className = 'kanban-filter-empty';
    emptyFilterMsg.innerHTML = '<i class="bi bi-search"></i><strong>' + @json(__('crm.no_matching_leads')) + '</strong>';
    if (activePanel) activePanel.appendChild(emptyFilterMsg);
    else if (columnBody) columnBody.appendChild(emptyFilterMsg);
   }
   emptyFilterMsg.style.display = 'flex';
  } else if (emptyFilterMsg) {
   emptyFilterMsg.style.display = 'none';
  }

  if (pagination) {
   if (totalPages <= 1) {
    pagination.style.display = 'none';
   } else {
    pagination.style.display = 'flex';
    if (pageInfo) {
     const fromItem = startIdx + 1;
     const toItem = Math.min(endIdx, totalItems);
     pageInfo.textContent = `${currentPage} / ${totalPages} (${fromItem}–${toItem})`;
    }
    if (prevBtn) {
     prevBtn.disabled = (currentPage <= 1);
    }
    if (nextBtn) {
     nextBtn.disabled = (currentPage >= totalPages);
    }
   }
  }

  return totalItems;
 };

 const render = () => {
  let totalMatched = 0;
  let totalAll = 0;

  columns.forEach(col => {
   const allCards = col.querySelectorAll('.kanban-card');
   totalAll += allCards.length;
   const matchedInCol = updateColumn(col);
   totalMatched += matchedInCol;
  });

  const isFiltered = Boolean(searchQuery || scopeFilter !== 'all' || employeeFilter || leadsLimit !== 10);

  if (resetBtn) {
   resetBtn.style.display = isFiltered ? 'inline-flex' : 'none';
  }

  if (statusToolbar && filterCountText) {
   if (isFiltered) {
    statusToolbar.style.display = 'flex';
    filterCountText.textContent = @json(__('crm.showing_leads_range'))
     .replace(':from', '1')
     .replace(':to', totalMatched.toString())
     .replace(':total', totalAll.toString()) || `Showing ${totalMatched} of ${totalAll} leads`;
   } else {
    statusToolbar.style.display = 'none';
   }
  }

  if (searchClear) {
   searchClear.style.display = searchQuery ? 'flex' : 'none';
  }
 };

 let searchDebounceTimer = null;
 if (searchInput) {
  searchInput.addEventListener('input', () => {
   window.clearTimeout(searchDebounceTimer);
   searchDebounceTimer = window.setTimeout(() => {
    searchQuery = normalize(searchInput.value);
    columns.forEach(col => columnPages.set(col, 1));
    render();
   }, 120);
  });
 }

 if (searchClear) {
  searchClear.addEventListener('click', () => {
   if (searchInput) searchInput.value = '';
   searchQuery = '';
   columns.forEach(col => columnPages.set(col, 1));
   render();
   if (searchInput) searchInput.focus();
  });
 }

 if (scopeSelect) {
  scopeSelect.addEventListener('change', () => {
   scopeFilter = scopeSelect.value;
   if (['today', 'overdue', 'upcoming'].includes(scopeFilter)) {
    columns.forEach(col => {
     const btn = col.querySelector(`[data-kanban-scope="${scopeFilter}"]`);
     if (btn) btn.click();
    });
   }
   columns.forEach(col => columnPages.set(col, 1));
   render();
  });
 }

 if (employeeSelect) {
  employeeSelect.addEventListener('change', () => {
   employeeFilter = employeeSelect.value;
   columns.forEach(col => columnPages.set(col, 1));
   render();
  });
 }

 if (limitSelect) {
  limitSelect.addEventListener('change', () => {
   leadsLimit = limitSelect.value === 'all' ? 'all' : parseInt(limitSelect.value, 10);
   columns.forEach(col => columnPages.set(col, 1));
   render();
  });
 }

 if (resetBtn) {
  resetBtn.addEventListener('click', () => {
   if (searchInput) searchInput.value = '';
   if (scopeSelect) scopeSelect.value = 'all';
   if (employeeSelect) employeeSelect.value = '';
   if (limitSelect) limitSelect.value = '10';

   searchQuery = '';
   scopeFilter = 'all';
   employeeFilter = '';
   leadsLimit = 10;

   columns.forEach(col => {
    columnPages.set(col, 1);
    const todayBtn = col.querySelector('[data-kanban-scope="today"]');
    if (todayBtn && !todayBtn.classList.contains('active')) {
     todayBtn.click();
    }
   });

   render();
  });
 }

 columns.forEach(col => {
  const pagination = col.querySelector('[data-kanban-pagination]');
  if (!pagination) return;

  pagination.addEventListener('click', (e) => {
   const actionBtn = e.target.closest('[data-page-action]');
   if (!actionBtn || actionBtn.disabled) return;

   const action = actionBtn.dataset.pageAction;
   let cur = columnPages.get(col) || 1;

   if (action === 'prev') {
    cur = Math.max(1, cur - 1);
   } else if (action === 'next') {
    cur += 1;
   }

   columnPages.set(col, cur);
   updateColumn(col);
  });
 });

 columns.forEach(col => {
  col.querySelectorAll('[data-kanban-scope]').forEach(btn => {
   btn.addEventListener('click', () => {
    columnPages.set(col, 1);
    window.setTimeout(() => updateColumn(col), 0);
   });
  });
 });

 render();

 const syncIframeTheme = (frameEl) => {
  try {
   if (!frameEl || !frameEl.contentDocument) return;
   const doc = frameEl.contentDocument;
   const rootEl = doc.documentElement;
   if (!rootEl) return;
   const isDark = document.documentElement.classList.contains('dark-mode');
   const isMono = document.documentElement.classList.contains('crm-monochrome');
   rootEl.classList.toggle('dark-mode', isDark);
   rootEl.classList.toggle('crm-monochrome', isMono);
   if (document.documentElement.dataset.theme) {
    rootEl.dataset.theme = document.documentElement.dataset.theme;
   }
   if (document.documentElement.dataset.palette) {
    rootEl.dataset.palette = document.documentElement.dataset.palette;
   }
  } catch (e) {}
 };

 document.querySelectorAll('.kanban-followup-frame').forEach((frameEl) => {
  frameEl.addEventListener('load', () => syncIframeTheme(frameEl));
 });

 window.addEventListener('crm:theme-changed', () => {
  document.querySelectorAll('.kanban-followup-frame').forEach((frameEl) => {
   syncIframeTheme(frameEl);
  });
 });
})();
</script>
<!-- CRM KANBAN FILTER & PAGINATION JS END -->
    </main>
</div>
<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

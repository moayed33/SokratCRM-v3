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
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
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
.topbar{
 position:sticky;
 top:0;
 z-index:20;
 display:flex;
 align-items:center;
 gap:16px;
 min-height:78px;
 padding:12px clamp(16px,3vw,38px);
 border-bottom:1px solid var(--line);
 background:#ffffffed;
 backdrop-filter:blur(12px);
 box-shadow:0 8px 30px #1720330a
}
.brand{
 display:flex;
 align-items:center;
 gap:10px;
 text-decoration:none
}
.brand img{
 width:52px;
 height:52px;
 object-fit:contain
}
.brand strong{
 display:block;
 color:var(--red);
 font:900 21px var(--font-primary)
}
.brand small{
 display:block;
 margin-top:4px;
 color:var(--muted);
 font-size:10px
}
.top-actions{
 margin-inline-start:auto;
 display:flex;
 align-items:center;
 gap:8px
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
 grid-auto-columns:minmax(270px,290px);
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
 margin:0;
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

.kanban-drag-hint{
 padding:9px 11px;
 border-bottom:1px dashed #e0e4eb;
 background:#fbfcfd;
 color:#7a8495;
 font-size:9px;
 font-weight:800;
 line-height:1.6;
 text-align:center
}

.kanban-drag-hint strong{
 color:#48566b
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

<header class="topbar">
 <a class="brand" href="{{ route('dashboard') }}">
  <img src="{{ asset('images/sokrat-pro-tech.png') }}" alt="Sokrat PRO">
  <span>
   <strong>SokratCRM</strong>
   <small>{{ __('crm.crm_subtitle') }}</small>
  </span>
 </a>

 <div class="top-actions">
  <a class="btn light" href="{{ route('dashboard') }}"><i class="bi bi-arrow-right"></i> {{ __('crm.dashboard') }}</a>
  @can('leads.create')
  <a class="btn" href="{{ route('v2.leads.create') }}"
   data-kanban-create-popup
   draggable="false"><i class="bi bi-plus-lg"></i> {{ __('crm.add_lead_short') }}</a>
  @endcan
 </div>
</header>

<main>
 <section class="page-head">
  <div>
   <h1>Kanban View</h1>
   <p>
    {{ __('crm.kanban_subtitle') }}
   </p>
  </div>

  <div class="summary">
   <span>
    {{ __('crm.total_leads') }}
    <b>
     {{ number_format($totalLeads) }}
    </b>
   </span>

   <span>
    {{ __('crm.status_count') }}
    <b>
     {{ count($kanbanColumns) }}
    </b>
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

     <div class="kanban-drag-hint">
      {{ __('crm.drag_hint_title') }}
      <strong>{{ __('crm.confirm_transfer') }}</strong>
      {{ __('crm.drag_hint_end') }}
     </div>

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
          data-current-status-id="{{ $lead->lead_status_id ?? ($column['status_id'] ?? '') }}"
          data-current-status-name="{{ $lead->status?->name_ar ?? $column['name'] }}"
          data-followup-url="{{ route(
           'v2.leads.followups.index',
           $lead
          ) }}"
          data-kanban-lead-scope="{{ $scope }}"
          style="
           --card-stage-color:
            {{ $column['stage_color'] }};
          "
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
            <a
             class="btn call"
             @can('leads.followups.create')
             data-kanban-call-dial-popup
             data-followup-url="{{ route(
              'v2.leads.followups.index',
              $lead
             ) }}"
             @endcan
             data-tel-href="tel:{{
              preg_replace(
               '/[^0-9+]/',
               '',
               (string) $lead->phone
              )
             }}"
             href="tel:{{
              preg_replace(
               '/[^0-9+]/',
               '',
               (string) $lead->phone
              )
             }}"
             draggable="false"
             title="{{ __('crm.call_and_log_followup') }}"
            >
             <i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.call') }}
            </a>
           @endif

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
            class="btn"
            href="{{ route(
             'v2.leads.followups.index',
             $lead
            ) }}"
           
             data-kanban-followup-popup
             draggable="false">
            <i class="bi bi-plus-circle"></i> {{ __('crm.log_followup') }}
           </a>
           @endcan
          </div>
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
   title="Kanban Popup"
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


<!-- CRM KANBAN FOLLOWUP MODAL START -->
<div
 class="kanban-followup-modal"
 id="crmKanbanFollowupModal"
 role="dialog"
 aria-modal="true"
 aria-hidden="true"
 aria-labelledby="crmKanbanFollowupTitle"
>
 <div class="kanban-followup-dialog">
  <header class="kanban-followup-modal-head">
   <div class="kanban-followup-modal-title">
    <h3 id="crmKanbanFollowupTitle">
     {{ __('crm.log_followup_and_change_status') }}
    </h3>

    <p id="crmKanbanFollowupDescription">
     {{ __('crm.status_change_notice') }}
    </p>
   </div>

   <button
    class="kanban-followup-close"
    id="crmKanbanFollowupClose"
    type="button"
    aria-label="{{ __('crm.cancel_and_close') }}"
    title="{{ __('crm.close') }}"
   >
    ×
   </button>
  </header>

  <iframe
   class="kanban-followup-frame"
   id="crmKanbanFollowupFrame"
   src="about:blank"
   title="{{ __('crm.log_lead_followup') }}"
  ></iframe>
 </div>
</div>
<!-- CRM KANBAN FOLLOWUP MODAL END -->

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
 const modal =
  document.getElementById(
   'crmKanbanFollowupModal'
  );

 const frame =
  document.getElementById(
   'crmKanbanFollowupFrame'
  );

 const closeButton =
  document.getElementById(
   'crmKanbanFollowupClose'
  );

 const modalTitle =
  document.getElementById(
   'crmKanbanFollowupTitle'
  );

 const modalDescription =
  document.getElementById(
   'crmKanbanFollowupDescription'
  );

 const toast =
  document.getElementById(
   'crmKanbanDragToast'
  );

 let dragData = null;
 let toastTimer = null;
 let callPopupActive = false;

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

 const closeModal = () => {
  if (!modal || !frame) {
   return;
  }

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

  dragData = null;
  callPopupActive = false;
 };

 const openFollowupModal = (
  targetColumn
 ) => {
  callPopupActive = false;

  if (
   !dragData
   || !modal
   || !frame
  ) {
   return;
  }

  const targetStatusId =
   targetColumn.dataset
    .kanbanStatusId || '';

  const targetStatusName =
   targetColumn.dataset
    .kanbanStatusName || '';

  if (!targetStatusId) {
   showToast(
    @json(__('crm.stage_has_no_status_for_transfer'))
   );

   dragData = null;
   return;
  }

  if (
   String(
    dragData.currentStatusId
   )
   === String(
    targetStatusId
   )
  ) {
   showToast(
    @json(__('crm.lead_already_in_status'))
   );

   dragData = null;
   return;
  }

  const url = new URL(
   dragData.followupUrl,
   window.location.href
  );

  url.searchParams.set(
   'kanban_popup',
   '1'
  );

  url.searchParams.set(
   'target_status_id',
   targetStatusId
  );

  if (modalTitle) {
   modalTitle.textContent =
    @json(__('crm.move_lead_to'))
     .replace(':lead', dragData.leadName)
     .replace(':status', targetStatusName);
  }

  if (modalDescription) {
   modalDescription.textContent =
    @json(__('crm.from_status_to_status_notice'))
     .replace(':from', dragData.currentStatusName)
     .replace(':to', targetStatusName);
  }

  frame.src =
   url.toString();

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


 /* CRM KANBAN CALL POPUP START */

 const openCallFollowupModal = (
  button
 ) => {
  if (
   !modal
   || !frame
   || !button
  ) {
   return;
  }

  const card =
   button.closest(
    '[data-kanban-lead]'
   );

  if (!card) {
   return;
  }

  const followupUrl =
   button.dataset
    .followupUrl
   || card.dataset
    .followupUrl
   || '';

  if (!followupUrl) {
   return;
  }

  const leadName =
   card.dataset
    .kanbanLeadName
   || @json(__('crm.client'));

  const currentStatusName =
   card.dataset
    .currentStatusName
   || '';

  const url = new URL(
   followupUrl,
   window.location.href
  );

  url.searchParams.set(
   'kanban_popup',
   '1'
  );

  url.searchParams.set(
   'channel',
   'call'
  );

  /*
   * No target_status_id here.
   * A call follow-up starts from the current
   * status and allows the employee to choose
   * a different status before saving.
   */
  url.searchParams.delete(
   'target_status_id'
  );

  callPopupActive = true;
  dragData = null;

  if (modalTitle) {
   modalTitle.textContent =
    @json(__('crm.log_call_followup_for'))
     .replace(':name', leadName);
  }

  if (modalDescription) {
   modalDescription.textContent =
    currentStatusName
     ? (
      @json(__('crm.current_status_prefix'))
      + currentStatusName
      + ' — '
      + @json(__('crm.change_status_after_call_hint'))
     )
     : @json(__('crm.log_call_result_hint'));
  }

  frame.src =
   url.toString();

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

 /*
  * Drag/drop popup deliberately locks the
  * destination status.
  *
  * Call popup uses the same follow-up screen,
  * but unlocks the status selector because the
  * employee may change the customer's status
  * after the telephone call.
  */
 frame?.addEventListener(
  'load',
  () => {
   if (!callPopupActive) {
    return;
   }

   try {
    const doc =
     frame.contentDocument;

    if (!doc) {
     return;
    }

    const statusSelect =
     doc.getElementById(
      'lead_status_id'
     );

    if (statusSelect) {
     statusSelect.disabled = false;

     const hiddenStatus =
      doc.querySelector(
       'input[type="hidden"]'
       + '[name="lead_status_id"]'
      );

     hiddenStatus?.remove();

     const field =
      statusSelect.closest(
       '.field'
      );

     const helper =
      field?.querySelector(
       'small'
      );

     if (helper) {
      helper.textContent =
       @json(__('crm.change_status_after_call_hint'));
     }
    }

    const communicationSelect =
     doc.getElementById(
      'communication_type'
     );

    if (communicationSelect) {
     communicationSelect.value =
      'call';

     communicationSelect
      .dispatchEvent(
       new Event(
        'change',
        {
         bubbles:true
        }
       )
      );
    }
   } catch (error) {
    console.error(
     'Unable to prepare call follow-up popup.',
     error
    );
   }
  }
 );

 document
  .querySelectorAll(
   '[data-kanban-call]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopPropagation();

      openCallFollowupModal(
       button
      );
     }
    );

    button.addEventListener(
     'dragstart',
     (event) => {
      event.preventDefault();
      event.stopPropagation();
     }
    );
   }
  );

 /* CRM KANBAN CALL POPUP END */

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

 closeButton?.addEventListener(
  'click',
  closeModal
 );

 modal?.addEventListener(
  'click',
  (event) => {
   if (event.target === modal) {
    closeModal();
   }
  }
 );

 document.addEventListener(
  'keydown',
  (event) => {
   if (
    event.key === 'Escape'
    && modal?.classList.contains(
     'open'
    )
   ) {
    closeModal();
   }
  }
 );

 window.addEventListener(
  'message',
  (event) => {
   if (
    event.origin
    !== window.location.origin
   ) {
    return;
   }

   if (
    event.data?.type
    !== 'crm-kanban-followup-saved'
   ) {
    return;
   }

   if (modalTitle) {
    modalTitle.textContent =
     @json(__('crm.followup_saved_successfully'));
   }

   if (modalDescription) {
    modalDescription.textContent =
     @json(__('crm.updating_kanban_now'));
   }

   window.setTimeout(
    () => {
     window.location.reload();
    },
    250
   );
  }
 );
})();
</script>
<!-- CRM KANBAN DRAG DROP JS END -->


<!-- CRM KANBAN CALL DIALER AND POPUP START -->
<script>
(() => {
 const modal =
  document.getElementById(
   'crmKanbanFollowupModal'
  );

 const frame =
  document.getElementById(
   'crmKanbanFollowupFrame'
  );

 const title =
  document.getElementById(
   'crmKanbanFollowupTitle'
  );

 const description =
  document.getElementById(
   'crmKanbanFollowupDescription'
  );

 let activeCallPopup = false;

 const prepareCallPopup = (
  button
 ) => {
  if (
   !button
   || !modal
   || !frame
  ) {
   return false;
  }

  const card =
   button.closest(
    '[data-kanban-lead]'
   );

  if (!card) {
   return false;
  }

  const followupUrl =
   button.dataset.followupUrl
   || card.dataset.followupUrl
   || '';

  if (!followupUrl) {
   return false;
  }

  const leadName =
   card.dataset.kanbanLeadName
   || @json(__('crm.client'));

  const currentStatus =
   card.dataset.currentStatusName
   || '';

  const url = new URL(
   followupUrl,
   window.location.href
  );

  url.searchParams.set(
   'kanban_popup',
   '1'
  );

  url.searchParams.set(
   'channel',
   'call'
  );

  /*
   * A normal CALL follow-up starts from
   * the current status.
   * It is not a drag/drop destination.
   */
  url.searchParams.delete(
   'target_status_id'
  );

  if (title) {
   title.textContent =
    @json(__('crm.log_call_followup_for'))
     .replace(':name', leadName);
  }

  if (description) {
   description.textContent =
    currentStatus
     ? (
      @json(__('crm.current_status_prefix'))
      + currentStatus
      + ' — '
      + @json(__('crm.log_call_result_hint'))
     )
     : @json(__('crm.log_call_result_hint'));
  }

  activeCallPopup = true;

  frame.src =
   url.toString();

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

  return true;
 };

 /*
  * Drag/drop popup locks the destination status.
  * A telephone call is different:
  * employee can change the status after talking
  * with the customer.
  */
 frame?.addEventListener(
  'load',
  () => {
   if (!activeCallPopup) {
    return;
   }

   try {
    const doc =
     frame.contentDocument;

    if (!doc) {
     return;
    }

    const statusSelect =
     doc.getElementById(
      'lead_status_id'
     );

    if (statusSelect) {
     statusSelect.disabled = false;

     const hiddenStatus =
      doc.querySelector(
       'input[type="hidden"]'
       + '[name="lead_status_id"]'
      );

     hiddenStatus?.remove();

     const field =
      statusSelect.closest(
       '.field'
      );

     const helper =
      field?.querySelector(
       'small'
      );

     if (helper) {
      helper.textContent =
       @json(__('crm.change_status_after_call_end_hint'));
    }

    const communicationSelect =
     doc.getElementById(
      'communication_type'
     );

    if (communicationSelect) {
     communicationSelect.value =
      'call';

     communicationSelect
      .dispatchEvent(
       new Event(
        'change',
        {
         bubbles:true
        }
       )
      );
    }
   } catch (error) {
    console.error(
     'Unable to prepare call popup.',
     error
    );
   }
  }
 );

 document
  .querySelectorAll(
   '[data-kanban-call-dial-popup]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();

      const telHref =
       button.dataset.telHref
       || button.getAttribute(
        'href'
       )
       || '';

      /*
       * Open the CRM popup FIRST.
       * Then invoke the operating-system
       * telephone handler.
       *
       * On mobile the dialer may come to the
       * foreground. When the employee returns
       * to the browser, the follow-up popup
       * remains open.
       */
      const popupOpened =
       prepareCallPopup(
        button
       );

      if (!popupOpened) {
       if (telHref) {
        window.location.href =
         telHref;
       }

       return;
      }

      if (telHref) {
       window.setTimeout(
        () => {
         window.location.href =
          telHref;
        },
        180
       );
      }
     },
     true
    );

    button.addEventListener(
     'dragstart',
     (event) => {
      event.preventDefault();
      event.stopPropagation();
     }
    );
   }
  );

 window.addEventListener(
  'message',
  (event) => {
   if (
    event.origin
    !== window.location.origin
   ) {
    return;
   }

   if (
    event.data?.type
    === 'crm-kanban-followup-saved'
   ) {
    activeCallPopup = false;
   }
  }
 );
})();
</script>
<!-- CRM KANBAN CALL DIALER AND POPUP END -->


<!-- CRM KANBAN CARD ACTION POPUPS V2 START -->
<script>
(() => {
 const modal =
  document.getElementById(
   'crmKanbanActionModal'
  );

 const frame =
  document.getElementById(
   'crmKanbanActionFrame'
  );

 const closeButton =
  document.getElementById(
   'crmKanbanActionClose'
  );

 const title =
  document.getElementById(
   'crmKanbanActionTitle'
  );

 const description =
  document.getElementById(
   'crmKanbanActionDescription'
  );

 if (
  !modal
  || !frame
 ) {
  return;
 }

 let popupMode = '';

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

  popupMode = '';
 };

 const openPopup = (
  url,
  heading,
  subheading,
  mode
 ) => {
  popupMode = mode;

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

 /*
  * Follow-up popup uses the compact popup
  * version of the existing follow-up page.
  *
  * The generic follow-up button is NOT a
  * drag/drop operation, so the status selector
  * must remain editable.
  */
 frame.addEventListener(
  'load',
  () => {
   if (
    popupMode !== 'followup'
   ) {
    return;
   }

   try {
    const doc =
     frame.contentDocument;

    if (!doc) {
     return;
    }

    const statusSelect =
     doc.getElementById(
      'lead_status_id'
     );

    if (statusSelect) {
     statusSelect.disabled = false;

     const hiddenStatus =
      doc.querySelector(
       'input[type="hidden"]'
       + '[name="lead_status_id"]'
      );

     hiddenStatus?.remove();

     const field =
      statusSelect.closest(
       '.field'
      );

     const helper =
      field?.querySelector(
       'small'
      );

     if (helper) {
      helper.textContent =
       @json(__('crm.select_status_then_save_hint'));
    }
   } catch (error) {
    console.error(
     'Unable to prepare follow-up popup.',
     error
    );
   }
  }
 );

 document
  .querySelectorAll(
   '[data-kanban-followup-popup]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopPropagation();

      const href =
       button.getAttribute(
        'href'
       );

      if (!href) {
       return;
      }

      const card =
       button.closest(
        '[data-kanban-lead]'
       );

      const leadName =
       card?.dataset
        .kanbanLeadName
       || @json(__('crm.client'));

      const statusName =
       card?.dataset
        .currentStatusName
       || '';

      const url = new URL(
       href,
       window.location.href
      );

      url.searchParams.set(
       'kanban_popup',
       '1'
      );

      url.searchParams.delete(
       'target_status_id'
      );

      openPopup(
       url.toString(),
       @json(__('crm.log_followup_for_name'))
        .replace(':name', leadName),
       statusName
        ? (
         @json(__('crm.current_status_prefix'))
         + statusName
         + ' — '
         + @json(__('crm.log_followup_hint'))
        )
        : @json(__('crm.log_followup_hint')),
       'followup'
      );
     }
    );

    button.addEventListener(
     'dragstart',
     (event) => {
      event.preventDefault();
      event.stopPropagation();
     }
    );
   }
  );

 /*
  * Customer details use a separate iframe
  * from the call/drag popup, therefore the
  * existing call and drag behavior remains
  * completely isolated.
  */
 document
  .querySelectorAll(
   '[data-kanban-customer-popup]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopPropagation();

      const href =
       button.getAttribute(
        'href'
       );

      if (!href) {
       return;
      }

      const card =
       button.closest(
        '[data-kanban-lead]'
       );

      const leadName =
       card?.dataset
        .kanbanLeadName
       || @json(__('crm.client'));

      const statusName =
       card?.dataset
        .currentStatusName
       || '';

      openPopup(
       new URL(
        href,
        window.location.href
       ).toString(),
       @json(__('crm.view_lead_details_for'))
        .replace(':name', leadName),
       statusName
        ? (
         @json(__('crm.current_status_prefix'))
         + statusName
        )
        : @json(__('crm.view_lead_details_hint')),
       'customer'
      );
     }
    );

    button.addEventListener(
     'dragstart',
     (event) => {
      event.preventDefault();
      event.stopPropagation();
     }
    );
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
})();
</script>
<!-- CRM KANBAN CARD ACTION POPUPS V2 END -->


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

 document
  .querySelectorAll(
   '[data-kanban-create-popup]'
  )
  .forEach(
   (button) => {
    button.addEventListener(
     'click',
     (event) => {
      event.preventDefault();
      event.stopPropagation();

      const href =
       button.getAttribute(
        'href'
       );

      if (!href) {
       return;
      }

      openPopup(
       new URL(
        href,
        window.location.href
       ).toString(),
       @json(__('crm.add_lead')),
       @json(__('crm.add_new_lead_kanban_desc')),
       'create'
      );
     }
    );

    button.addEventListener(
     'dragstart',
     (event) => {
      event.preventDefault();
      event.stopPropagation();
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


<!-- CRM KANBAN PAGE SIZE 15 V7 JS START -->
<script>
(() => {
 const PAGE_SIZE = 15;

 const getPanelCards = (panel) =>
  Array.from(
   panel.children
  ).filter(
   (element) =>
    element.classList.contains(
     'kanban-card'
    )
  );

 const setupPanel = (panel) => {
  const cards =
   getPanelCards(panel);

  /*
   * 15 or fewer:
   * show normally, no button needed.
   */
  if (
   cards.length
   <= PAGE_SIZE
  ) {
   return;
  }

  let visibleCount =
   PAGE_SIZE;

  const wrap =
   document.createElement(
    'div'
   );

  wrap.className =
   'kanban-more-wrap';

  wrap.setAttribute(
   'data-kanban-more-wrap',
   ''
  );

  const button =
   document.createElement(
    'button'
   );

  button.type =
   'button';

  button.className =
   'kanban-more-btn';

  button.setAttribute(
   'data-kanban-more',
   ''
  );

  const render = () => {
   cards.forEach(
    (card, index) => {
     const visible =
      index < visibleCount;

     card.hidden =
      !visible;

     if (visible) {
      card.removeAttribute(
       'aria-hidden'
      );
     } else {
      card.setAttribute(
       'aria-hidden',
       'true'
      );
     }
    }
   );

   const remaining =
    cards.length
     - visibleCount;

   if (remaining <= 0) {
    wrap.hidden = true;
    return;
   }

   wrap.hidden = false;

   const nextBatch =
    Math.min(
     PAGE_SIZE,
     remaining
    );

   button.textContent =
    @json(__('crm.more_items'))
    + ' ('
    + nextBatch
    + ')';
  };

  button.addEventListener(
   'click',
   () => {
    visibleCount =
     Math.min(
      visibleCount
       + PAGE_SIZE,
      cards.length
     );

    render();
   }
  );

  wrap.appendChild(
   button
  );

  panel.appendChild(
   wrap
  );

  render();
 };

 document
  .querySelectorAll(
   '.kanban-scope-panel'
  )
  .forEach(
   setupPanel
  );
})();
</script>
<!-- CRM KANBAN PAGE SIZE 15 V7 JS END -->

</body>
</html>

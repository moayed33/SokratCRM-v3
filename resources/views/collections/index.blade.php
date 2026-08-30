@extends('leads.transfer-layout')

@section('title', __('crm.collections'))
@section('page-title', __('crm.collections'))

@section('top-actions')
 @can('collections.methods.manage')
  <a class="btn soft" href="{{ route('v2.collections.methods.index') }}">
   <i class="bi bi-credit-card"></i>
   {{ __('crm.manage_instant_methods') }}
  </a>
 @endcan
 <a class="btn soft" href="{{ route('dashboard') }}">
  <i class="bi bi-grid"></i>
  {{ __('crm.dashboard') }}
 </a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
 .collection-page { --collect-ink:#172033; --collect-muted:#667085; --collect-line:#e4e7ec; --collect-surface:#fff; --collect-accent:#0f766e; }
 .collection-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
 .collection-kpi { display:block; padding:18px; border:1px solid var(--collect-line); border-radius:12px; background:var(--collect-surface); color:inherit; text-decoration:none; }
 .collection-kpi span { display:block; color:var(--collect-muted); font-size:12px; margin-bottom:8px; }
 .collection-kpi strong { font-size:26px; color:var(--collect-ink); }
 .collection-kpi:hover { border-color:#99d5cf; box-shadow:0 8px 24px rgba(15,118,110,.08); }
 .collection-filter { display:grid; grid-template-columns:minmax(220px,1fr) 200px auto; gap:10px; padding:14px; margin-bottom:16px; background:var(--collect-surface); border:1px solid var(--collect-line); border-radius:12px; }
 .collection-filter input,.collection-filter select { width:100%; min-height:42px; border:1px solid var(--collect-line); border-radius:7px; padding:0 12px; background:var(--collect-surface); color:var(--collect-ink); }
 .collection-list { display:grid; gap:10px; }
 .collection-row { display:grid; grid-template-columns:minmax(180px,1.4fr) minmax(110px,.8fr) minmax(130px,.8fr) minmax(130px,.7fr) auto auto; align-items:center; gap:14px; padding:16px; border:1px solid var(--collect-line); border-radius:12px; background:var(--collect-surface); }
 .collection-row h3 { margin:0 0 4px; font-size:15px; color:var(--collect-ink); }
 .collection-row p,.collection-cell span { margin:0; font-size:12px; color:var(--collect-muted); }
 .collection-cell strong { display:block; color:var(--collect-ink); font-size:13px; margin-bottom:3px; }
 .collection-status { display:inline-flex; width:max-content; padding:5px 9px; border-radius:999px; background:#ecfdf5; color:#087b68; font-size:11px; font-weight:800; }
 .collection-status.is-closed { background:#f2f4f7; color:#667085; }
 .collection-status.is-failed { background:#fff4e5; color:#b54708; }
 .collection-overdue { color:#c4323b!important; }
 .collection-empty { padding:50px 20px; text-align:center; border:1px dashed var(--collect-line); border-radius:12px; color:var(--collect-muted); background:var(--collect-surface); }
 .collection-quick-icons { display:inline-flex; align-items:center; gap:6px; }
 .collection-icon-btn { width:36px; height:36px; border-radius:8px; display:grid; place-items:center; border:1px solid var(--collect-line); color:var(--collect-ink); text-decoration:none; font-size:14px; transition:background .15s; }
 .collection-icon-btn:hover { background:#f1f5f9; }
 .collection-icon-btn.call { color:#0284c7; background:#f0f9ff; border-color:#bae6fd; }
 .collection-icon-btn.whatsapp { color:#16a34a; background:#f0fdf4; border-color:#bbf7d0; }
 .collection-icon-btn.maps { color:#d97706; background:#fffbeb; border-color:#fde68a; }
 html.dark-mode .collection-page { --collect-ink:#f4f4f5; --collect-muted:#a1a1aa; --collect-line:rgba(255,255,255,.1); --collect-surface:rgba(24,24,27,.72); }
 html.dark-mode .collection-status { background:rgba(16,185,129,.16)!important; color:#6ee7b7!important; border:1px solid rgba(16,185,129,.3)!important; }
 html.dark-mode .collection-status.is-closed { background:rgba(255,255,255,.08)!important; color:#a1a1aa!important; border:1px solid rgba(255,255,255,.14)!important; }
 html.dark-mode .collection-status.is-failed { background:rgba(245,158,11,.16)!important; color:#fcd34d!important; border:1px solid rgba(245,158,11,.3)!important; }
 html.dark-mode .collection-icon-btn { background:#27272a; border-color:#3f3f46; color:#e4e4e7; }
 html.dark-mode .collection-icon-btn.call { color:#38bdf8; background:rgba(56,189,248,.14); border-color:rgba(56,189,248,.3); }
 html.dark-mode .collection-icon-btn.whatsapp { color:#4ade80; background:rgba(74,222,128,.14); border-color:rgba(74,222,128,.3); }
 html.dark-mode .collection-icon-btn.maps { color:#fbbf24; background:rgba(251,191,36,.14); border-color:rgba(251,191,36,.3); }
 html.crm-monochrome .collection-status { border-radius:4px; background:#f5f5f5; color:#171717; border:1px solid #d4d4d4; }
 html.crm-monochrome.dark-mode .collection-status { background:#27272a!important; color:#f4f4f5!important; border:1px solid #525252!important; }
 @media(max-width:992px){ .collection-kpis{grid-template-columns:repeat(2,1fr)} .collection-row{grid-template-columns:1fr 1fr;gap:12px} .collection-row>div:first-child{grid-column:1/-1} }
 @media(max-width:620px){ .collection-kpis{grid-template-columns:1fr 1fr} .collection-filter{grid-template-columns:1fr} .collection-row{grid-template-columns:1fr;gap:10px} }
</style>
@endpush

@section('content')
<div class="collection-page">
 @if (session('success'))
  <div class="alert success">{{ session('success') }}</div>
 @endif

 <div class="collection-kpis">
  <a class="collection-kpi" href="{{ route('v2.collections.index', ['scope' => 'open']) }}"><span>{{ __('crm.collection_open') }}</span><strong>{{ number_format($summary['open']) }}</strong></a>
  <a class="collection-kpi" href="{{ route('v2.collections.index', ['scope' => 'overdue']) }}"><span>{{ __('crm.collection_overdue') }}</span><strong>{{ number_format($summary['overdue']) }}</strong></a>
  <a class="collection-kpi" href="{{ route('v2.collections.index', ['scope' => 'today']) }}"><span>{{ __('crm.collection_today') }}</span><strong>{{ number_format($summary['today']) }}</strong></a>
  <a class="collection-kpi" href="{{ route('v2.collections.index', ['status' => 'collected']) }}"><span>{{ __('crm.collection_status_collected') }}</span><strong>{{ number_format($summary['collected']) }}</strong></a>
 </div>

 <form class="collection-filter" method="GET" action="{{ route('v2.collections.index') }}">
  <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="{{ __('crm.collection_search_placeholder') }}">
  <select name="status">
   <option value="">{{ __('crm.all_statuses') }}</option>
   @foreach ($statuses as $value => $label)
    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
   @endforeach
  </select>
  <button class="btn primary" type="submit"><i class="bi bi-funnel"></i>{{ __('crm.filter') }}</button>
 </form>

 <div class="collection-list">
  @forelse ($collectionCases as $case)
   @php
    $isClosed = in_array($case->status, ['collected', 'cancelled'], true);
    $isOverdue = !$isClosed && $case->due_at->isPast();
   @endphp
   @php
    $phoneClean = preg_replace('/[^0-9+]/', '', (string)($case->lead?->phone ?? ''));
    $mapQuery = urlencode($case->collection_address . ($case->lead?->governorate ? ' ' . $case->lead->governorate : ''));
   @endphp
   <article class="collection-row">
    <div>
     <h3>{{ $case->lead?->name ?: __('crm.unnamed_lead') }}</h3>
     <p>
      @if($case->collection_address)
       <i class="bi bi-geo-alt"></i> {{ $case->collection_address }} @if($case->lead?->governorate) ({{ $case->lead->governorate }}) @endif ·
      @endif
      <i class="bi bi-telephone"></i> {{ $case->lead?->phone ?: '—' }}
      @if($case->branch) · {{ $case->branch->name }} @endif
     </p>
    </div>
    <div class="collection-cell"><span>{{ __('crm.donation_value') }}</span><strong>{{ number_format((float) $case->expected_amount, 2) }}</strong></div>
    <div class="collection-cell"><span>{{ __('crm.collection_due_at') }}</span><strong class="{{ $isOverdue ? 'collection-overdue' : '' }}">{{ $case->due_at->translatedFormat('d M Y, H:i') }}</strong></div>
    <div class="collection-cell">
     <span>{{ __('crm.assigned_collector') }}</span>
     <strong>{{ $case->assignedCollector?->name ?: __('crm.unassigned') }}</strong>
     @if($case->assignedCollector?->collection_zone)
      <small style="display:block;color:var(--collect-muted);font-size:11px"><i class="bi bi-geo-fill"></i> {{ $case->assignedCollector->collection_zone }}</small>
     @endif
    </div>
    <div>
     <span class="collection-status {{ $isClosed ? 'is-closed' : '' }} {{ $case->status === 'failed' ? 'is-failed' : '' }}">{{ $statuses[$case->status] ?? $case->status }}</span>
    </div>
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
     <div class="collection-quick-icons">
      @if($phoneClean)
       <a href="sip:{{ $phoneClean }}" class="collection-icon-btn call" title="{{ __('crm.call_via_microsip') }}"><i class="bi bi-telephone-fill"></i></a>
       <a href="https://wa.me/{{ $phoneClean }}" target="_blank" rel="noopener" class="collection-icon-btn whatsapp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
      @endif
      @if($case->collection_address)
       <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener" class="collection-icon-btn maps" title="{{ __('crm.open_maps_navigation') }}"><i class="bi bi-geo-alt-fill"></i></a>
      @endif
     </div>
     <a class="btn soft small" href="{{ route('v2.collections.show', $case) }}">{{ __('crm.view_details') }} <i class="bi bi-arrow-left-short"></i></a>
    </div>
   </article>
  @empty
   <div class="collection-empty"><i class="bi bi-inbox"></i><p>{{ __('crm.no_collection_cases') }}</p></div>
  @endforelse
 </div>

 <div style="margin-top:18px">{{ $collectionCases->links() }}</div>
</div>
@endsection

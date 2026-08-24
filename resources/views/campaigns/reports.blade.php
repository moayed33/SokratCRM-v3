@extends('leads.transfer-layout')

@section('title', __('crm.campaign_reports'))
@section('page-title', __('crm.campaign_reports'))
@section('page-description', __('crm.campaign_reports_subtitle'))

@section('top-actions')
 <a class="btn soft" href="{{ route('v2.campaigns.index') }}">
  {{ __('crm.view_campaigns') }}
 </a>
@endsection

@push('styles')
<style>
 .campaign-report{display:grid;gap:18px}
 .campaign-report ::selection{background:#dc263726;color:var(--dark)}
 .campaign-report-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;padding:26px 28px;border-radius:16px;background:#182033;color:#fff;box-shadow:0 18px 38px #17203320}
 .campaign-report-intro h2{max-width:650px;margin:0;font-size:clamp(22px,2.7vw,34px);letter-spacing:-.025em;line-height:1.18;text-wrap:balance}
 .campaign-report-intro p{max-width:720px;margin:10px 0 0;color:#d7dce5;font-size:13px;line-height:1.9}
 .campaign-report-scope{min-width:190px;padding:12px 14px;border:1px solid #ffffff24;border-radius:12px;background:#ffffff0d}
 .campaign-report-scope-item+.campaign-report-scope-item{margin-top:10px;padding-top:10px;border-top:1px solid #ffffff1f}
 .campaign-report-scope span,.campaign-report-scope strong{display:block}
 .campaign-report-scope span{color:#b9c1cf;font-size:10px;font-weight:900}
 .campaign-report-scope strong{overflow:hidden;margin-top:6px;font-size:13px;text-overflow:ellipsis;white-space:nowrap}
 .campaign-selector-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}
 .campaign-selector-head h2{margin:0;color:#202a3c;font-size:18px;line-height:1.35}
 .campaign-selector-head p{max-width:720px;margin:5px 0 0;color:var(--muted);font-size:11px;line-height:1.7}
 .campaign-selector-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(225px,1fr));gap:12px;margin-top:13px}
 .campaign-selector-card{position:relative;min-width:0;display:grid;grid-template-rows:auto 1fr auto;gap:14px;min-height:174px;padding:17px;border:1px solid transparent;border-radius:14px;background:var(--card);color:inherit;text-decoration:none;box-shadow:0 10px 28px #17203310;transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease}
 .campaign-selector-card:hover{transform:translateY(-2px);border-color:#e7a4ac;box-shadow:0 16px 34px #17203318}
 .campaign-selector-card:focus-visible{outline:3px solid #dc263738;outline-offset:3px}
 .campaign-selector-card.is-active{border-color:#dc2637;box-shadow:0 13px 32px #dc263718}
 .campaign-selector-card.is-all{background:#202a3c;color:#fff}
 .campaign-selector-card.is-all.is-active{border-color:#f18c98}
 .campaign-selector-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
 .campaign-selector-icon{width:42px;height:42px;display:grid;place-items:center;flex:0 0 42px;overflow:hidden;border-radius:11px;background:#fff0f2;color:#c91f31;font-size:18px}
 .campaign-selector-icon img{width:100%;height:100%;display:block;object-fit:cover}
 .campaign-selector-card.is-all .campaign-selector-icon{background:#ffffff17;color:#fff}
 .campaign-selector-state{padding:5px 8px;border-radius:999px;background:#eef2f7;color:#596477;font-size:9px;font-weight:900;white-space:nowrap}
 .campaign-selector-state.is-active{background:#eaf8f1;color:#148657}
 .campaign-selector-state.is-ended{background:#f2f3f5;color:#687284}
 .campaign-selector-state.is-upcoming{background:#fff6dc;color:#9a6500}
 .campaign-selector-card h3{overflow:hidden;margin:0;color:#253047;font-size:15px;line-height:1.45;text-overflow:ellipsis;white-space:nowrap}
 .campaign-selector-card.is-all h3{color:#fff}
 .campaign-selector-card p{margin:5px 0 0;color:#7e899a;font-size:10px;line-height:1.65}
 .campaign-selector-card.is-all p{color:#cbd2de}
 .campaign-selector-open{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:11px;border-top:1px solid var(--line);color:#bd1f30;font-size:10px;font-weight:900}
 .campaign-selector-card.is-all .campaign-selector-open{border-color:#ffffff1f;color:#fff}
 .campaign-report-filters{padding:18px 20px;border:1px solid var(--line);border-radius:16px;background:var(--card);box-shadow:var(--shadow)}
 .campaign-report-filters-head{margin-bottom:15px;padding-bottom:13px;border-bottom:1px solid var(--line)}
 .campaign-report-filters-head h3{margin:0;color:#202a3c;font-size:15px;line-height:1.4}
 .campaign-report-filters-head p{margin:5px 0 0;color:var(--muted);font-size:10px;line-height:1.7}
 .campaign-report-filter-grid{display:grid;grid-template-columns:minmax(190px,1.15fr) minmax(170px,1fr) repeat(2,minmax(150px,.8fr)) auto;align-items:end;gap:11px}
 .campaign-report-field{display:grid;gap:7px;min-width:0}
 .campaign-report-field label{color:#5e697a;font-size:11px;font-weight:900}
 .campaign-report-field select,.campaign-report-field input{width:100%;height:44px;padding:0 12px;border:1px solid #d8dee8;border-radius:10px;background:#fff;color:#263247;font-size:12px;font-weight:800;outline:none}
 .campaign-report-field select:focus,.campaign-report-field input:focus{border-color:#dc2637;box-shadow:0 0 0 3px #dc263718}
 .campaign-report-field input:disabled{cursor:not-allowed;opacity:.48}
 .campaign-report-actions{display:flex;gap:7px}
 .campaign-report-actions .btn{height:44px;white-space:nowrap}
 .campaign-report-errors{margin:0 0 14px;padding:11px 14px;border:1px solid #f3bec4;border-radius:10px;background:#fff1f3;color:#a51828;font-size:12px;font-weight:800}
 .campaign-report-definition{margin:12px 2px 0;color:var(--muted);font-size:10px;line-height:1.7}
 .campaign-metric-strip{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));overflow:hidden;border-radius:16px;background:var(--card);box-shadow:0 14px 35px #17203312}
 .campaign-metric{position:relative;min-width:0;padding:21px 22px}
 .campaign-metric+.campaign-metric{border-inline-start:1px solid var(--line)}
 .campaign-metric span,.campaign-metric strong,.campaign-metric small{display:block}
 .campaign-metric span{color:#647084;font-size:11px;font-weight:900}
 .campaign-metric strong{margin:9px 0 5px;color:#1c2638;font-size:30px;font-variant-numeric:tabular-nums;letter-spacing:-.025em;line-height:1}
 .campaign-metric small{min-height:31px;color:#8a94a4;font-size:10px;line-height:1.55}
 .campaign-metric.is-conversion{background:#edf9f3}
 .campaign-metric.is-conversion strong{color:#148657}
 .campaign-metric.is-cost{background:#fff8e8}
 .campaign-metric.is-cost strong{color:#b36b00;font-size:24px}
 .campaign-report-charts{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(320px,1fr);gap:18px}
 .campaign-report-panel{min-width:0;padding:22px;border-radius:16px;background:var(--card);box-shadow:var(--shadow)}
 .campaign-report-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:17px;padding-bottom:14px;border-bottom:1px solid var(--line)}
 .campaign-report-panel h3{margin:0;color:#202a3c;font-size:16px;line-height:1.35}
 .campaign-report-panel-head p{max-width:630px;margin:6px 0 0;color:var(--muted);font-size:11px;line-height:1.7}
 .campaign-report-chart-wrap{position:relative;height:330px}
 .campaign-report-chart-wrap.is-donut{height:250px}
 .campaign-report-empty{height:100%;display:grid;place-items:center;padding:24px;color:var(--muted);text-align:center;font-size:12px;font-weight:800;line-height:1.8}
 .campaign-status-list{display:grid;gap:7px;margin-top:13px}
 .campaign-status-row{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:9px;padding:8px 0}
 .campaign-status-dot{width:9px;height:9px;border-radius:50%;background:var(--status-color)}
 .campaign-status-name{overflow:hidden;color:#4c586b;font-size:11px;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
 .campaign-status-value{color:#283449;font-size:11px;font-weight:900;font-variant-numeric:tabular-nums}
 .campaign-snapshot-note{margin:15px 0 0;padding-top:13px;border-top:1px solid var(--line);color:#667286;font-size:10px;line-height:1.75}
 .campaign-report-sr-only{position:absolute;width:1px;height:1px;overflow:hidden;margin:-1px;padding:0;border:0;clip:rect(0,0,0,0)}
 @media(max-width:1100px){.campaign-report-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.campaign-report-actions{grid-column:1/-1}.campaign-report-charts{grid-template-columns:1fr}.campaign-report-chart-wrap.is-donut{height:280px}}
 @media(max-width:760px){.campaign-report-intro{align-items:stretch;flex-direction:column;padding:22px}.campaign-report-scope{min-width:0}.campaign-selector-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.campaign-metric-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.campaign-metric+.campaign-metric{border-inline-start:0}.campaign-metric:nth-child(even){border-inline-start:1px solid var(--line)}.campaign-metric:nth-child(n+3){border-top:1px solid var(--line)}.campaign-report-chart-wrap{height:290px}}
 @media(max-width:560px){.campaign-selector-head{align-items:flex-start;flex-direction:column}.campaign-selector-grid{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;padding:2px 2px 12px}.campaign-selector-card{min-width:min(82vw,280px);scroll-snap-align:start}.campaign-report-filter-grid{grid-template-columns:1fr}.campaign-report-actions{grid-column:auto;display:grid;grid-template-columns:1fr auto}.campaign-report-actions .btn{justify-content:center}.campaign-metric-strip{grid-template-columns:1fr}.campaign-metric:nth-child(n){border-top:1px solid var(--line);border-inline-start:0}.campaign-metric:first-child{border-top:0}.campaign-report-panel{padding:18px}.campaign-report-chart-wrap{height:260px}.campaign-report-chart-wrap.is-donut{height:230px}}
 html.dark-mode .campaign-report-intro{background:#09090b;box-shadow:0 18px 38px #0006}
 html.dark-mode .campaign-report-filters,html.dark-mode .campaign-metric-strip,html.dark-mode .campaign-report-panel{background:var(--card);border-color:var(--line)}
 html.dark-mode .campaign-report-filters-head h3{color:#f4f4f5}
 html.dark-mode .campaign-report-field label,html.dark-mode .campaign-status-name,html.dark-mode .campaign-snapshot-note{color:#a1a1aa}
 html.dark-mode .campaign-report-field select,html.dark-mode .campaign-report-field input{border-color:#3f3f46;background:#18181b;color:#f4f4f5}
 html.dark-mode .campaign-metric span,html.dark-mode .campaign-metric small{color:#a1a1aa}
 html.dark-mode .campaign-metric strong,html.dark-mode .campaign-report-panel h3,html.dark-mode .campaign-status-value{color:#f4f4f5}
 html.dark-mode .campaign-metric.is-conversion{background:#123126}
 html.dark-mode .campaign-metric.is-conversion strong{color:#5ee0a5}
 html.dark-mode .campaign-metric.is-cost{background:#34260f}
 html.dark-mode .campaign-metric.is-cost strong{color:#f8bd58}
 html.dark-mode .campaign-selector-head h2,html.dark-mode .campaign-selector-card h3{color:#f4f4f5}
 html.dark-mode .campaign-selector-card{background:#18181b;box-shadow:0 12px 30px #0005}
 html.dark-mode .campaign-selector-card.is-all{background:#09090b}
 html.dark-mode .campaign-selector-card p{color:#a1a1aa}
 html.dark-mode .campaign-selector-open{border-color:var(--line);color:#f87171}
 html.dark-mode .campaign-selector-state{background:#27272a;color:#d4d4d8}
 html.dark-mode .campaign-selector-state.is-active{background:#123126;color:#5ee0a5}
 html.dark-mode .campaign-selector-state.is-upcoming{background:#382b11;color:#f8bd58}
</style>
@endpush

@section('content')
<div class="campaign-report" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
 <section class="campaign-report-intro" aria-labelledby="campaignReportHeading">
  <div>
   <h2 id="campaignReportHeading">{{ __('crm.campaign_report_heading') }}</h2>
   <p>{{ __('crm.campaign_report_intro') }}</p>
  </div>
  <div class="campaign-report-scope">
   <div class="campaign-report-scope-item">
    <span>{{ __('crm.campaign_filter') }}</span>
    <strong>{{ $selectedCampaign?->name ?? __('crm.showing_all_campaigns') }}</strong>
   </div>
   <div class="campaign-report-scope-item">
    <span>{{ __('crm.responsible_employee') }}</span>
    <strong>{{ $selectedEmployee?->name ?? __('crm.all_employees') }}</strong>
   </div>
  </div>
 </section>

 <form class="campaign-report-filters" id="campaignReportFilters" method="GET" action="{{ route('v2.campaigns.reports') }}">
  <header class="campaign-report-filters-head">
   <h3>{{ __('crm.filter_campaign_report') }}</h3>
   <p>{{ __('crm.filter_campaign_report_desc') }}</p>
  </header>

  @if ($errors->any())
   <div class="campaign-report-errors" role="alert">
    {{ $errors->first() }}
   </div>
  @endif

  @if ($filters['campaign_id'] !== null)
   <input type="hidden" name="campaign_id" value="{{ $filters['campaign_id'] }}">
  @endif

  <div class="campaign-report-filter-grid">
   <div class="campaign-report-field">
    <label for="reportEmployee">{{ __('crm.responsible_employee') }}</label>
    <select id="reportEmployee" name="employee_id">
     <option value="">{{ __('crm.all_employees') }}</option>
     @foreach ($employees as $employee)
      <option value="{{ $employee->id }}" @selected($filters['employee_id'] === $employee->id)>
       {{ $employee->name }}
      </option>
     @endforeach
    </select>
   </div>

   <div class="campaign-report-field">
    <label for="reportPeriod">{{ __('crm.report_period') }}</label>
    <select id="reportPeriod" name="period">
     <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
     <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
     <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
     <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
     <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
     <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
    </select>
   </div>

   <div class="campaign-report-field">
    <label for="reportFrom">{{ __('crm.from_date') }}</label>
    <input id="reportFrom" name="from" type="date" value="{{ $filters['from'] }}">
   </div>

   <div class="campaign-report-field">
    <label for="reportTo">{{ __('crm.to_date') }}</label>
    <input id="reportTo" name="to" type="date" value="{{ $filters['to'] }}">
   </div>

   <div class="campaign-report-actions">
    <button class="btn primary" type="submit">{{ __('crm.apply_filters') }}</button>
    <a class="btn soft" href="{{ route('v2.campaigns.reports') }}" title="{{ __('crm.reset_filters') }}" aria-label="{{ __('crm.reset_filters') }}">
     <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
    </a>
   </div>
  </div>
  <p class="campaign-report-definition">
   {{ __('crm.campaign_report_definition') }}
   @if ($selectedEmployee)
    {{ __('crm.campaign_report_employee_definition', ['employee' => $selectedEmployee->name]) }}
   @endif
  </p>
 </form>

 @php
  $campaignCardQuery = ['period' => $filters['period']];
  if ($filters['period'] === 'custom') {
   $campaignCardQuery['from'] = $filters['from'];
   $campaignCardQuery['to'] = $filters['to'];
  }
 @endphp
 <section class="campaign-selector" aria-labelledby="campaignSelectorHeading">
  <header class="campaign-selector-head">
   <div>
    <h2 id="campaignSelectorHeading">{{ __('crm.choose_campaign_report') }}</h2>
    <p>{{ __('crm.choose_campaign_report_desc') }}</p>
   </div>
  </header>
  <div class="campaign-selector-grid">
   <a class="campaign-selector-card is-all {{ $selectedCampaign === null ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', $campaignCardQuery) }}" @if ($selectedCampaign === null) aria-current="page" @endif>
    <div class="campaign-selector-top">
     <span class="campaign-selector-icon"><i class="bi bi-collection" aria-hidden="true"></i></span>
     <span class="campaign-selector-state">{{ __('crm.all') }}</span>
    </div>
    <div>
     <h3>{{ __('crm.all_campaigns') }}</h3>
     <p>{{ __('crm.all_campaigns_report_desc') }}</p>
     <p>{{ __('crm.campaigns_available_count', ['count' => number_format($campaigns->count())]) }}</p>
    </div>
    <span class="campaign-selector-open">
     <span>{{ __('crm.view_campaign_report') }}</span>
     <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
    </span>
   </a>

   @foreach ($campaigns as $campaign)
    @php
     $campaignState = $campaign->ends_at->isPast()
      ? 'ended'
      : ($campaign->starts_at->isFuture() ? 'upcoming' : 'active');
    @endphp
    <a class="campaign-selector-card {{ $selectedCampaign?->id === $campaign->id ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', array_merge($campaignCardQuery, ['campaign_id' => $campaign->id])) }}" @if ($selectedCampaign?->id === $campaign->id) aria-current="page" @endif>
     <div class="campaign-selector-top">
      <span class="campaign-selector-icon">
       @if ($campaign->image_path)
        <img src="{{ asset('storage/'.$campaign->image_path) }}" alt="">
       @else
        <i class="bi bi-megaphone" aria-hidden="true"></i>
       @endif
      </span>
      <span class="campaign-selector-state is-{{ $campaignState }}">
       {{ $campaignState === 'active' ? __('crm.active_now') : __($campaignState === 'ended' ? 'crm.ended' : 'crm.upcoming') }}
      </span>
     </div>
     <div>
      <h3 title="{{ $campaign->name }}">{{ $campaign->name }}</h3>
      <p>{{ $campaign->starts_at->format('Y-m-d') }} - {{ $campaign->ends_at->format('Y-m-d') }}</p>
     </div>
     <span class="campaign-selector-open">
      <span>{{ __('crm.view_campaign_report') }}</span>
      <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
     </span>
    </a>
   @endforeach
  </div>
 </section>

 <section class="campaign-metric-strip" aria-label="{{ __('crm.campaign_reports') }}">
  <article class="campaign-metric">
   <span>{{ __('crm.campaigns_created') }}</span>
   <strong>{{ number_format($metrics['created_campaigns']) }}</strong>
   <small>{{ __('crm.campaigns_created_help') }}</small>
  </article>
  <article class="campaign-metric">
   <span>{{ __('crm.campaigns_ended') }}</span>
   <strong>{{ number_format($metrics['ended_campaigns']) }}</strong>
   <small>{{ __('crm.campaigns_ended_help') }}</small>
  </article>
  <article class="campaign-metric">
   <span>{{ __('crm.current_campaign_leads') }}</span>
   <strong>{{ number_format($metrics['current_leads']) }}</strong>
   <small>{{ __('crm.current_campaign_leads_help') }}</small>
  </article>
  <article class="campaign-metric is-cost">
   <span>{{ __('crm.campaign_lead_cost') }}</span>
   <strong>{{ number_format($metrics['lead_cost'], 2) }} {{ __('crm.pound') }}</strong>
   <small>{{ __('crm.campaign_lead_cost_help', ['cost' => number_format($metrics['total_campaign_cost'], 2), 'leads' => number_format($metrics['current_leads'])]) }}</small>
  </article>
  <article class="campaign-metric is-conversion">
   <span>{{ __('crm.current_campaign_donor_share') }}</span>
   <strong>{{ number_format($metrics['conversion_rate'], 1) }}%</strong>
   <small>{{ number_format($metrics['donor_leads']) }} {{ __('crm.donor') }}</small>
  </article>
 </section>

 <section class="campaign-report-charts">
  <article class="campaign-report-panel">
   <header class="campaign-report-panel-head">
    <div>
     <h3>{{ __('crm.campaign_performance_timeline') }}</h3>
     <p>{{ __('crm.campaign_timeline_desc') }}</p>
    </div>
   </header>
   <div class="campaign-report-chart-wrap">
    @if (collect($timeline)->sum('created') + collect($timeline)->sum('ended') > 0)
     <canvas id="campaignTimelineChart" role="img" aria-label="{{ __('crm.campaign_performance_timeline') }}"></canvas>
    @else
     <div class="campaign-report-empty">{{ __('crm.no_campaign_activity') }}</div>
    @endif
   </div>
   <table class="campaign-report-sr-only">
    <caption>{{ __('crm.campaign_performance_timeline') }}</caption>
    <thead><tr><th>{{ __('crm.period') }}</th><th>{{ __('crm.created_campaigns_chart') }}</th><th>{{ __('crm.ended_campaigns_chart') }}</th></tr></thead>
    <tbody>
     @foreach ($timeline as $point)
      <tr><td>{{ $point['label'] }}</td><td>{{ $point['created'] }}</td><td>{{ $point['ended'] }}</td></tr>
     @endforeach
    </tbody>
   </table>
  </article>

  <article class="campaign-report-panel">
   <header class="campaign-report-panel-head">
    <div>
     <h3>{{ __('crm.campaign_status_distribution') }}</h3>
     <p>{{ __('crm.campaign_status_distribution_desc') }}</p>
    </div>
   </header>
   <div class="campaign-report-chart-wrap is-donut">
    @if ($metrics['current_leads'] > 0)
     <canvas id="campaignStatusChart" role="img" aria-label="{{ __('crm.campaign_status_distribution') }}"></canvas>
    @else
     <div class="campaign-report-empty">{{ __('crm.no_campaign_leads') }}</div>
    @endif
   </div>
   @if ($metrics['current_leads'] > 0)
    <div class="campaign-status-list">
     @foreach ($statusDistribution as $status)
      <div class="campaign-status-row">
       <span class="campaign-status-dot" style="--status-color:{{ $status['color'] }}" aria-hidden="true"></span>
       <span class="campaign-status-name">{{ $status['label'] }}</span>
       <span class="campaign-status-value">{{ number_format($status['count']) }} · {{ number_format($status['percentage'], 1) }}%</span>
      </div>
     @endforeach
    </div>
   @endif
   <p class="campaign-snapshot-note">
    {{ __('crm.current_snapshot_notice', ['donors' => number_format($metrics['donor_leads']), 'total' => number_format($metrics['current_leads'])]) }}
   </p>
  </article>
 </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
 (() => {
  const period = document.getElementById('reportPeriod');
  const from = document.getElementById('reportFrom');
  const to = document.getElementById('reportTo');

  const syncCustomDates = () => {
   const custom = period?.value === 'custom';
   if (from) from.disabled = !custom;
   if (to) to.disabled = !custom;
  };

  period?.addEventListener('change', () => {
   syncCustomDates();
   if (period.value === 'custom') from?.focus();
  });
  syncCustomDates();

  if (typeof Chart === 'undefined') {
   document.querySelectorAll('#campaignTimelineChart, #campaignStatusChart').forEach((canvas) => {
    const fallback = document.createElement('div');
    fallback.className = 'campaign-report-empty';
    fallback.textContent = @json(__('crm.campaign_chart_unavailable'));
    canvas.replaceWith(fallback);
   });
   return;
  }

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const dark = document.documentElement.classList.contains('dark-mode');
  const textColor = dark ? '#a1a1aa' : '#64748b';
  const gridColor = dark ? 'rgba(255,255,255,.08)' : 'rgba(226,232,240,.8)';
  Chart.defaults.color = textColor;
  Chart.defaults.font.family = 'Tajawal, Tahoma, Arial, sans-serif';

  const timelineCanvas = document.getElementById('campaignTimelineChart');
  if (timelineCanvas) {
   const timeline = @json($timeline);
   new Chart(timelineCanvas, {
    type: 'bar',
    data: {
     labels: timeline.map(point => point.label),
     datasets: [
      {
       label: @json(__('crm.created_campaigns_chart')),
       data: timeline.map(point => point.created),
       backgroundColor: '#3478f6',
       borderRadius: 6,
       maxBarThickness: 30
      },
      {
       label: @json(__('crm.ended_campaigns_chart')),
       data: timeline.map(point => point.ended),
       backgroundColor: '#dc2637',
       borderRadius: 6,
       maxBarThickness: 30
      }
     ]
    },
    options: {
     responsive: true,
     maintainAspectRatio: false,
     animation: reducedMotion ? false : {duration: 560, easing: 'easeOutQuart'},
     plugins: {
      legend: {position: 'bottom', rtl: @json(app()->getLocale() === 'ar'), labels: {usePointStyle: true, padding: 18}}
     },
     scales: {
      x: {grid: {display: false}, ticks: {maxRotation: 0, autoSkip: true}},
      y: {beginAtZero: true, position: @json(app()->getLocale() === 'ar' ? 'right' : 'left'), grid: {color: gridColor}, ticks: {precision: 0}}
     }
    }
   });
  }

  const statusCanvas = document.getElementById('campaignStatusChart');
  if (statusCanvas) {
   const statuses = @json($statusDistribution);
   new Chart(statusCanvas, {
    type: 'doughnut',
    data: {
     labels: statuses.map(status => status.label),
     datasets: [{
      data: statuses.map(status => status.count),
      backgroundColor: statuses.map(status => status.color),
      borderColor: dark ? '#18181b' : '#ffffff',
      borderWidth: 3,
      hoverOffset: 5
     }]
    },
    options: {
     responsive: true,
     maintainAspectRatio: false,
     cutout: '68%',
     animation: reducedMotion ? false : {duration: 620, easing: 'easeOutQuart', animateRotate: true},
     plugins: {
      legend: {display: false},
      tooltip: {
       callbacks: {
        label(context) {
         const status = statuses[context.dataIndex];
         return ` ${status.label}: ${status.count} (${status.percentage}%)`;
        }
       }
      }
     }
    }
   });
  }
 })();
</script>
@endpush

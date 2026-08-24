@extends('leads.transfer-layout')

@section('title', $campaign->name)
@section('page-title', $campaign->name)
@section('page-description', __('crm.campaign_show_subtitle'))

@section('top-actions')
 @if ($canManage)
  @can('leads.create')
   <a class="btn primary" href="{{ route('v2.leads.create', ['campaign_id' => $campaign->id]) }}">
    ＋ {{ __('crm.add_lead') }}
   </a>
  @endcan
  <a class="btn soft" href="{{ route('v2.campaigns.edit', $campaign) }}">
   {{ __('crm.edit_campaign') }}
  </a>
  @can('leads.import')
   <a class="btn primary" href="{{ route('v2.leads.import', ['campaign' => $campaign->id]) }}">
    ↑ {{ __('crm.import_leads') }}
   </a>
  @endcan
 @endif
 <a class="btn soft" href="{{ route('v2.campaigns.index') }}">{{ __('crm.all_campaigns') }}</a>
@endsection

@push('styles')
<style>
 .transfer-content{width:calc(100% - 12px);max-width:none;margin:20px 6px 45px}
 .transfer-top-actions .btn.primary,.campaign-assignment .btn.primary{border-color:var(--red);background:linear-gradient(135deg,#e83243,#c91d2e);box-shadow:0 10px 24px #dc26372c}
 .campaign-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:175px;padding:28px 30px}
 .campaign-hero-copy{min-width:0;display:flex;align-items:center;gap:16px}
 .campaign-hero-image{width:84px;height:84px;display:block;flex:0 0 84px;object-fit:cover;border:2px solid #ffffff40;border-radius:20px;background:#fff;box-shadow:0 12px 30px #0003}
 .campaign-hero h2{margin:9px 0 8px;font-size:clamp(25px,4vw,36px)}
 .campaign-hero-count{min-width:145px;min-height:110px;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:15px;border:1px solid #ffffff20;border-radius:18px;background:#ffffff0d;text-align:center}
 .campaign-hero-count strong{font-size:40px;font-weight:900}
 .campaign-hero-count span{margin-top:5px;color:#cdd2dc;font-size:10px;font-weight:900}
 .campaign-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;padding:18px}
 .campaign-stat{padding:14px;border:1px solid var(--line);border-radius:13px;background:#fafbfc}
 .campaign-stat span{display:block;color:var(--muted);font-size:10px;font-weight:900}
 .campaign-stat strong{display:block;margin-top:7px;color:var(--dark);font-size:14px;overflow-wrap:anywhere}
 .campaign-team{display:flex;flex-wrap:wrap;gap:6px;padding:0 18px 18px}
 .campaign-team span{display:inline-flex;padding:6px 9px;border-radius:999px;background:#eef3fa;color:#42516a;font-size:10px;font-weight:900}
 .campaign-filter-card{padding:17px}
 .campaign-filter-card h3{margin:0 0 5px;font-size:14px}
 .campaign-filter-card p{margin:0 0 14px;color:var(--muted);font-size:10px}
 .campaign-filter{display:grid;grid-template-columns:minmax(230px,420px);align-items:end;gap:10px}
 .campaign-filter label{display:block;margin:0 3px 6px;color:#757f91;font-size:10px;font-weight:900}
 .campaign-filter select{width:100%;height:44px;padding:0 11px;border:1px solid #dfe3ea;border-radius:10px;background:#fafbfc;color:#3f4b5e;font-size:12px;font-weight:800;outline:none}
 .campaign-filter select:focus{border-color:#e98692;background:#fff;box-shadow:0 0 0 3px #dc263710}
 .campaign-status-section{padding:17px}
 .campaign-status-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
 .campaign-status-head h3{margin:0;font-size:14px}
 .campaign-status-head p{margin:5px 0 0;color:var(--muted);font-size:10px}
 .campaign-all-statuses{padding:7px 10px;border-radius:9px;background:#f1f3f6;color:#687386;text-decoration:none;font-size:10px;font-weight:900;transition:.2s}
 .campaign-all-statuses.active{background:#fff0f2;color:var(--red)}
 html.dark-mode .campaign-all-statuses{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;color:var(--text-muted,#a1a1aa)!important;font-weight:700!important}
 html.dark-mode .campaign-all-statuses:hover{background:rgba(255,255,255,.12)!important;border-color:rgba(255,255,255,.2)!important;color:var(--text-primary,#f4f4f5)!important}
 html.dark-mode .campaign-all-statuses.active{background:rgba(239,68,68,.15)!important;border:1px solid rgba(239,68,68,.4)!important;color:#f87171!important;font-weight:800!important;box-shadow:0 4px 15px rgba(239,68,68,.2)!important}
 .campaign-status-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(125px,1fr));gap:8px}
 .campaign-status-card{--status-color:#64748b;min-width:0;min-height:92px;display:flex;flex-direction:column;justify-content:space-between;padding:11px;border:1px solid var(--line);border-radius:13px;background:#fafbfc;color:inherit;text-decoration:none;transition:.2s}
 .campaign-status-card:hover,.campaign-status-card.selected{border-color:var(--status-color);transform:translateY(-2px);box-shadow:0 10px 25px #17203310}
 .campaign-status-card.selected{background:#fff}
 html.dark-mode .campaign-status-card{background:var(--bg-card,rgba(24,24,27,.75))!important;backdrop-filter:var(--glass-blur,blur(16px))!important;-webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;box-shadow:0 4px 18px rgba(0,0,0,.25)!important;color:var(--text-primary,#f4f4f5)!important}
 html.dark-mode .campaign-status-card:hover{background:rgba(39,39,42,.75)!important;border-color:var(--status-color,rgba(255,255,255,.25))!important;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.35),0 0 0 1px color-mix(in srgb,var(--status-color) 40%,transparent)!important}
 html.dark-mode .campaign-status-card.selected{background:color-mix(in srgb,var(--status-color) 12%,var(--bg-card,rgba(24,24,27,.85)))!important;border-color:var(--status-color)!important;box-shadow:0 8px 28px rgba(0,0,0,.4),0 0 0 1px var(--status-color),inset 0 1px 0 rgba(255,255,255,.1)!important;color:#ffffff!important}
 .campaign-status-name{display:flex;align-items:center;gap:6px;min-width:0}
 .campaign-status-name i{width:8px;height:8px;flex:0 0 8px;border-radius:50%;background:var(--status-color)}
 html.dark-mode .campaign-status-name i{box-shadow:0 0 8px color-mix(in srgb,var(--status-color) 80%,transparent)}
 .campaign-status-name strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px}
 html.dark-mode .campaign-status-name strong{color:var(--text-primary,#f4f4f5)!important;font-weight:700}
 html.dark-mode .campaign-status-card.selected .campaign-status-name strong{color:#ffffff!important}
 .campaign-status-card b{color:var(--status-color);font-size:24px;font-weight:900}
 html.dark-mode .campaign-status-card b{text-shadow:0 0 12px color-mix(in srgb,var(--status-color) 25%,transparent)}
 .campaign-status-card small{color:var(--muted);font-size:9px;font-weight:700}
 html.dark-mode .campaign-status-card small{color:var(--text-muted,#a1a1aa)!important;font-weight:600}
 html.dark-mode .campaign-status-card.selected small{color:rgba(255,255,255,.75)!important}
 .campaign-results{overflow:hidden}
 .campaign-results .card-head{padding:17px 20px}
 .campaign-result-count{padding:7px 10px;border-radius:9px;background:#f1f3f6;color:#667184;font-size:10px;font-weight:900}
 .campaign-note{margin:16px 18px 0}
 .campaign-assignment{display:flex;align-items:end;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);background:#fafbfc}
 .campaign-assignment .field{min-width:240px;max-width:390px;flex:1}
 .campaign-table-wrap{overflow-x:auto}
 .campaign-table{width:100%;min-width:1120px;border-collapse:collapse}
 .campaign-table th{padding:13px 14px;border-bottom:1px solid var(--line);background:#fafbfc;color:#657185;text-align:start;font-size:11px;font-weight:900;white-space:nowrap}
 .campaign-table td{padding:14px;border-bottom:1px solid #eef0f4;color:#3f4b5e;font-size:13px;font-weight:800;line-height:1.65;vertical-align:middle}
 .campaign-table tbody tr{transition:background .18s}
 .campaign-table tbody tr:hover{background:#fff9fa}
 .campaign-checkbox{width:17px;height:17px;accent-color:var(--red)}
 .campaign-customer{display:flex;align-items:center;gap:9px;color:inherit;text-decoration:none}
 .campaign-customer-avatar{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border-radius:11px;background:var(--dark);color:#fff;font-weight:900}
 .campaign-customer strong,.campaign-customer small{display:block}
 .campaign-customer strong{color:var(--dark);font-size:13px;font-weight:900}
 .campaign-customer small{max-width:190px;margin-top:4px;overflow:hidden;text-overflow:ellipsis;color:#667286;font-size:11px;font-weight:800;white-space:nowrap}
 .campaign-contact{display:grid;gap:5px}
 .campaign-contact a{color:#566174;text-decoration:none}
 .campaign-contact a:hover{color:var(--red)}
 .campaign-status{--status-color:#64748b;display:inline-flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--status-color);font-size:11px;font-weight:900;white-space:nowrap}
 .campaign-status-dot{width:8px;height:8px;border-radius:50%;background:var(--status-color)}
 .campaign-stage{display:block;margin-top:5px;color:#667286;font-size:11px;font-weight:800}
 .campaign-follow-up{display:inline-flex;padding:7px 9px;border-radius:9px;background:#f3f5f8;color:#536075;font-weight:800;white-space:nowrap}
 .campaign-muted{color:#748094;font-weight:800}
 .lead-actions-cell{min-width:365px}
 .lead-actions{display:flex;align-items:center;gap:6px;white-space:nowrap}
 .lead-action{min-height:36px;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#556174;text-decoration:none;font-size:11px;font-weight:900;cursor:pointer;transition:.18s}
 .lead-action:hover{transform:translateY(-1px);box-shadow:0 7px 17px #17203312}
 .lead-action.call-action{border-color:#b8cceb;background:#f3f7fd;color:#275a9c}
 .lead-action.followup-action{border-color:#e2c993;background:#fff9ed;color:#8d6515}
 .lead-action.whatsapp-action{border-color:#a9dbbd;background:#effaf3;color:#167744}
 .lead-action.quotation-action{border-color:#b8cceb;background:#f3f7fd;color:#275a9c}
 .lead-action.is-disabled{opacity:.42;cursor:not-allowed;transform:none;box-shadow:none}
 .lead-more{position:relative;flex:0 0 auto}
 .lead-more summary{width:36px;height:36px;display:grid;place-items:center;padding:0;border:1px solid var(--line);border-radius:9px;background:#fff;color:#596477;list-style:none;cursor:pointer;font-size:20px;font-weight:900;line-height:1}
 .lead-more summary::-webkit-details-marker{display:none}
 .lead-more summary:hover,.lead-more[open] summary{border-color:#d9a4ab;background:#fff4f5;color:var(--red)}
 .lead-menu{position:fixed;z-index:1000;width:165px;padding:6px;border:1px solid var(--line);border-radius:11px;background:#fff;box-shadow:0 17px 38px #17203328}
 .lead-menu a,.lead-menu button{width:100%;min-height:39px;display:flex;align-items:center;gap:8px;padding:7px 10px;border:0;border-radius:8px;background:transparent;color:#525e70;text-decoration:none;text-align:start;font-size:12px;font-weight:900;cursor:pointer}
 .lead-menu a:hover,.lead-menu button:hover{background:#f4f5f7}
 .lead-menu form{margin:4px 0 0;padding-top:4px;border-top:1px solid var(--line)}
 .lead-menu .delete-action{color:#c52233}
 .lead-menu .delete-action:hover{background:#fff0f2}
 .campaign-empty{min-height:280px;display:flex;align-items:center;justify-content:center;flex-direction:column;padding:35px;color:var(--muted);text-align:center}
 .campaign-empty-icon{width:68px;height:68px;display:grid;place-items:center;border-radius:20px;background:#f2f4f7;color:#8d96a5;font-size:27px}
 .campaign-empty h3{margin:16px 0 7px;color:var(--dark);font-size:16px}
 .campaign-empty p{max-width:480px;margin:0;line-height:1.8;font-size:11px}
 .campaign-pagination{padding:15px;border-top:1px solid var(--line)}
 @media(max-width:950px){.campaign-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.campaign-status-grid{grid-template-columns:repeat(4,minmax(115px,1fr))}}
 @media(max-width:700px){.campaign-status-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
 @media(max-width:700px){.campaign-hero{align-items:flex-start;flex-direction:column}.campaign-hero-copy{align-items:flex-start}.campaign-hero-image{width:68px;height:68px;flex-basis:68px;border-radius:16px}.campaign-hero-count{width:100%;min-height:90px}.campaign-summary{grid-template-columns:1fr}.campaign-filter{grid-template-columns:1fr}.campaign-filter .btn{width:100%}.campaign-assignment{align-items:stretch;flex-direction:column}.campaign-assignment .field{width:100%;max-width:none;min-width:0}.campaign-assignment .btn{width:100%}}
</style>
@endpush

@section('content')
@php
 $assigneeLabel = $showUnassigned
  ? __('crm.unassigned_leads')
  : $selectedAssignee->name;
@endphp
<section class="transfer-card">
 <div class="transfer-hero campaign-hero">
  <div class="campaign-hero-copy">
   @if ($campaign->image_path)
    <img class="campaign-hero-image" src="{{ asset('storage/'.$campaign->image_path) }}" alt="{{ $campaign->name }}">
   @endif
   <div>
    <small>{{ __('crm.manage_campaign_leads') }}</small>
    <h2>{{ $campaign->name }}</h2>
    <p>
     {{ $canManage
       ? __('crm.campaign_all_users_view_hint')
       : __('crm.campaign_own_leads_hint') }}
   </div>
  </div>
  <div class="campaign-hero-count">
   <strong>{{ number_format($leads->total()) }}</strong>
   <span>{{ $showUnassigned ? __('crm.unassigned_lead_count_label') : __('crm.assigned_lead_to_count_label', ['name' => $selectedAssignee->name]) }}</span>
  </div>
 </div>

 <div class="campaign-summary">
  <div class="campaign-stat">
   <span>{{ __('crm.campaign_start') }}</span>
   <strong>{{ $campaign->starts_at->format('Y-m-d H:i') }}</strong>
  </div>
  <div class="campaign-stat">
   <span>{{ __('crm.campaign_end') }}</span>
   <strong>{{ $campaign->ends_at->format('Y-m-d H:i') }}</strong>
  </div>
  <div class="campaign-stat">
   <span>{{ __('crm.campaign_cost') }}</span>
   <strong>{{ number_format((float) $campaign->cost, 2) }} {{ __('crm.currency_egp') }}</strong>
  </div>
  <div class="campaign-stat">
   <span>{{ __('crm.created_by') }}</span>
   <strong>{{ $campaign->creator?->name ?: '—' }}</strong>
  </div>
 </div>

 <div class="campaign-team">
  @foreach ($campaign->users as $campaignUser)
   <span>{{ $campaignUser->name }}</span>
  @endforeach
 </div>
</section>

@if ($canManage)
<section class="transfer-card campaign-filter-card">
 <h3>{{ __('crm.filter_leads_by_user') }}</h3>
 <p>{{ __('crm.campaign_filter_notice') }}</p>
 <form class="campaign-filter" id="campaignFilters" method="GET" action="{{ route('v2.campaigns.show', $campaign) }}">
   <div>
    <label for="assigned_user_id">{{ __('crm.responsible_user') }}</label>
    <select class="campaign-live-filter" id="assigned_user_id" name="assigned_user_id">
     @foreach ($filterUsers as $filterUser)
      <option value="{{ $filterUser->id }}" @selected(! $showUnassigned && $selectedAssignee->is($filterUser))>
       {{ $filterUser->name }}{{ $filterUser->is(auth()->user()) ? ' ' . __('crm.my_account') : '' }}
       ({{ number_format($assignmentCounts->get((string) $filterUser->id, 0)) }})
      </option>
     @endforeach
     <option value="unassigned" @selected($showUnassigned)>
      {{ __('crm.unassigned_to_any_user', ['count' => number_format($assignmentCounts->get('unassigned', 0))]) }}
     </option>
    </select>
   </div>
  @if ($selectedStage)
   <input type="hidden" name="stage" value="{{ $selectedStage->id }}">
  @elseif ($selectedStatus)
   <input type="hidden" name="status" value="{{ $selectedStatus->code }}">
  @endif
 </form>
</section>
@endif

<section class="transfer-card campaign-status-section">
 <header class="campaign-status-head">
  <div>
   <h3>{{ __('crm.customer_stages') }}</h3>
   <p>{{ __('crm.campaign_stage_filter_notice') }}</p>
  </div>
  <a
   class="campaign-all-statuses {{ $selectedStage === null && $selectedStatus === null ? 'active' : '' }}"
   href="{{ route('v2.campaigns.show', array_filter([
    'campaign' => $campaign,
    'assigned_user_id' => $canManage ? ($showUnassigned ? 'unassigned' : $selectedAssignee->id) : null,
   ])) }}"
  >
   {{ __('crm.all_stages') }}
  </a>
 </header>
 <div class="campaign-status-grid">
  @foreach ($pipelineStages as $stage)
   @php
    $cardColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $stage->color)
     ? $stage->color
     : '#64748b';
    $isSelected = ($selectedStage?->id === $stage->id) || ($selectedStatus && $selectedStatus->pipeline_stage_id === $stage->id);
   @endphp
   <a
    class="campaign-status-card {{ $isSelected ? 'selected' : '' }}"
    href="{{ route('v2.campaigns.show', array_filter([
     'campaign' => $campaign,
     'assigned_user_id' => $canManage ? ($showUnassigned ? 'unassigned' : $selectedAssignee->id) : null,
     'stage' => $stage->id,
    ])) }}"
    style="--status-color:{{ $cardColor }}"
   >
    <span class="campaign-status-name">
     <i style="background:{{ $cardColor }}"></i>
     <strong>{{ (app()->getLocale() === 'en' && !empty($stage->name_en)) ? $stage->name_en : $stage->name_ar }}</strong>
    </span>
    <b>{{ number_format($stage->campaign_leads_count) }}</b>
    <small>{{ $stage->isPrimary() ? __('crm.stage_type_primary') : __('crm.stage_type_additional') }}</small>
   </a>
  @endforeach
 </div>
</section>

<section class="transfer-card campaign-results">
 <header class="card-head">
  <div>
   <h3>{{ $assigneeLabel }}</h3>
   <p>
    @if ($selectedStage)
     {{ __('crm.stage_label_prefix', ['name' => (app()->getLocale() === 'en' && !empty($selectedStage->name_en) ? $selectedStage->name_en : $selectedStage->name_ar)]) }}
    @elseif ($selectedStatus)
     {{ __('crm.status_label_prefix', ['name' => (app()->getLocale() === 'en' && !empty($selectedStatus->name_en) ? $selectedStatus->name_en : $selectedStatus->name_ar)]) }}
    @else
     {{ __('crm.all_stages') }}
    @endif
   </p>
  </div>
  <span class="campaign-result-count">{{ __('crm.results_count', ['count' => number_format($leads->total())]) }}</span>
 </header>

 @if (session('success'))
  <div class="notice success campaign-note" role="status">{{ session('success') }}</div>
 @endif

 @if ($leads->isEmpty())
  <div class="campaign-empty">
   <span class="campaign-empty-icon" aria-hidden="true">♙</span>
   <h3>{{ __('crm.no_matching_results') }}</h3>
   <p>{{ __('crm.try_other_filter') }}</p>
  </div>
 @else
  @if ($canManage && $assignableUsers->isNotEmpty())
   <form id="campaignAssignmentForm" method="POST" action="{{ route('v2.campaigns.leads.assign', $campaign) }}">
    @csrf
    @method('PATCH')
    <div class="campaign-assignment">
     <div class="field">
      <label for="target_user_id">{{ __('crm.assign_selected_to') }}</label>
      <select class="control" id="target_user_id" name="target_user_id" required>
       <option value="">{{ __('crm.select_user') }}</option>
       @foreach ($assignableUsers as $campaignUser)
        <option value="{{ $campaignUser->id }}">{{ $campaignUser->name }}</option>
       @endforeach
      </select>
     </div>
     <button class="btn primary" type="submit">{{ __('crm.assign_selected') }}</button>
    </div>
   </form>
   @endif

  <div class="campaign-table-wrap">
   <table class="campaign-table">
    <thead>
     <tr>
      @if ($canManage && $assignableUsers->isNotEmpty())
       <th><input class="campaign-checkbox" id="selectAllCampaignLeads" type="checkbox" aria-label="{{ __('crm.select_all') }}"></th>
      @endif
      <th>{{ __('crm.client') }}</th>
      <th>{{ __('crm.contact_information') }}</th>
      <th>{{ __('crm.company_and_source') }}</th>
      <th>{{ __('crm.current_stage') }}</th>
      <th>{{ __('crm.assigned_employee') }}</th>
      <th>{{ __('crm.next_followup') }}</th>
      <th>{{ __('crm.registration_date') }}</th>
      <th>{{ __('crm.actions') }}</th>
     </tr>
    </thead>
    <tbody>
     @foreach ($leads as $lead)
      @php
       $statusColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $lead->status?->color)
        ? $lead->status->color
        : '#64748b';
      @endphp
      <tr>
       @if ($canManage && $assignableUsers->isNotEmpty())
        <td>
         <input class="campaign-checkbox campaign-lead-check" type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" form="campaignAssignmentForm" aria-label="{{ __('crm.select_lead_aria', ['name' => $lead->name]) }}">
        </td>
       @endif
       <td>
        @can('leads.view')
         <a class="campaign-customer" href="{{ route('v2.leads.show', $lead) }}">
        @else
         <span class="campaign-customer">
        @endcan
          <span class="campaign-customer-avatar">{{ mb_substr((string) $lead->name, 0, 1) }}</span>
          <span>
           <strong>{{ $lead->name }}</strong>
           <small>{{ $lead->email ?: __('crm.no_email_short') }}</small>
          </span>
        @can('leads.view')
         </a>
        @else
         </span>
        @endcan
       </td>
       <td>
        <div class="campaign-contact">
         @if ($lead->phone)
          <a href="tel:{{ $lead->phone }}" dir="ltr">{{ $lead->phone }}</a>
         @else
          <span class="campaign-muted">{{ __('crm.no_phone_short') }}</span>
         @endif
         @if ($lead->email)
          <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
         @endif
        </div>
       </td>
       <td>
        <strong>{{ $lead->company_name ?: __('crm.no_company') }}</strong>
        <span class="campaign-stage">{{ __('crm.source_prefix', ['source' => $lead->source ?: __('crm.unspecified')]) }}</span>
       </td>
       <td>
        <span class="campaign-status" style="--status-color:{{ $statusColor }}">
         <i class="campaign-status-dot"></i>
         {{ (app()->getLocale() === 'en' && !empty($lead->status?->name_en)) ? $lead->status?->name_en : ($lead->status?->name_ar ?? __('crm.without_status')) }}
        </span>
       <span class="campaign-stage">{{ (app()->getLocale() === 'en' && !empty($lead->status?->stage?->name_en)) ? $lead->status?->stage?->name_en : ($lead->status?->stage?->name_ar ?? __('crm.without_stage')) }}</span>
       </td>
       <td>{{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}</td>
       <td>
        @if ($lead->next_follow_up_at)
         <span class="campaign-follow-up">{{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}</span>
        @else
         <span class="campaign-muted">{{ __('crm.no_scheduled_date') }}</span>
        @endif
       </td>
       <td>{{ $lead->created_at?->format('d/m/Y') ?? '—' }}</td>
       <td class="lead-actions-cell">
        @php
         $phoneDigits = preg_replace('/\D+/', '', (string) $lead->phone) ?? '';
         $callPhone = preg_match('/^[0-9]{2,20}$/', $phoneDigits) ? $phoneDigits : null;
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

         $quotationPath = trim((string) $lead->quotation_file_path);
         $hasQuotation = $quotationPath !== ''
          && str_starts_with($quotationPath, 'crm-v2/quotation-files/')
          && ! str_contains($quotationPath, '..')
          && \Illuminate\Support\Facades\Storage::disk('local')->exists($quotationPath);
        @endphp
        <div class="lead-actions">
         @can('leads.followups.view')
          @if ($callPhone)
           <a class="lead-action call-action js-call-followup" href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}" target="_blank" rel="noopener noreferrer" data-call-href="callto:{{ $callPhone }}" title="{{ __('crm.open_microsip_and_followup') }}">☎ {{ __('crm.call') }}</a>
          @else
           <span class="lead-action call-action is-disabled" aria-disabled="true" title="{{ __('crm.phone_invalid_for_call') }}">☎ {{ __('crm.call') }}</span>
          @endif
          <a class="lead-action followup-action" href="{{ route('v2.leads.followups.index', $lead) }}" title="{{ __('crm.log_new_lead_followup_title') }}">◷ {{ __('crm.log_followup') }}</a>
         @endcan

         @if ($whatsappPhone)
          <a class="lead-action whatsapp-action" href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener noreferrer" title="{{ __('crm.open_whatsapp') }}">◉ {{ __('crm.phone_type_whatsapp') }}</a>
         @else
          <span class="lead-action whatsapp-action is-disabled" aria-disabled="true" title="{{ __('crm.invalid_whatsapp_number') }}">◉ {{ __('crm.phone_type_whatsapp') }}</span>
         @endif

         @if ($hasQuotation && auth()->user()->can('quotations.view'))
          <a class="lead-action quotation-action" href="{{ route('v2.leads.quotation.preview', $lead) }}" target="_blank" rel="noopener noreferrer">👁 {{ __('crm.preview_quotation') }}</a>
         @endif

         @canany(['leads.update', 'leads.delete'])
          <details class="lead-more">
           <summary title="{{ __('crm.more_actions') }}">⋮</summary>
           <div class="lead-menu">
            @can('leads.update')
             <a href="{{ route('v2.leads.edit', $lead) }}">✎ {{ __('crm.edit') }}</a>
            @endcan
            @can('leads.delete')
             <form class="js-delete-lead-form" method="POST" action="{{ route('v2.leads.destroy', $lead) }}" data-lead-name="{{ $lead->name }}">
              @csrf
              @method('DELETE')
              <button class="delete-action" type="submit">♲ {{ __('crm.delete') }}</button>
             </form>
            @endcan
           </div>
          </details>
         @endcanany
        </div>
       </td>
      </tr>
     @endforeach
    </tbody>
   </table>
  </div>

  @if ($leads->hasPages())
   <div class="campaign-pagination">{{ $leads->links() }}</div>
  @endif
 @endif
</section>
@endsection

@push('scripts')
<script>
 (() => {
  const filters = document.getElementById('campaignFilters');
  const selectAll = document.getElementById('selectAllCampaignLeads');
  const checks = [...document.querySelectorAll('.campaign-lead-check')];

  document.querySelectorAll('.campaign-live-filter').forEach((filter) => {
   filter.addEventListener('change', () => filters?.requestSubmit());
  });

  selectAll?.addEventListener('change', () => {
   checks.forEach((checkbox) => {
    checkbox.checked = selectAll.checked;
   });
  });

  const closeLeadMenus = (except = null) => {
   document.querySelectorAll('.lead-more[open]').forEach((details) => {
    if (details !== except) details.open = false;
   });
  };

  const positionLeadMenu = (details) => {
   const summary = details.querySelector('summary');
   const menu = details.querySelector('.lead-menu');
   if (!summary || !menu) return;
   const rect = summary.getBoundingClientRect();
   const left = Math.max(8, Math.min(rect.right - 165, window.innerWidth - 173));
   const top = rect.bottom + 103 > window.innerHeight
    ? Math.max(8, rect.top - 103)
    : rect.bottom + 7;
   menu.style.left = Math.round(left) + 'px';
   menu.style.top = Math.round(top) + 'px';
  };

  document.querySelectorAll('.lead-more').forEach((details) => {
   details.addEventListener('toggle', () => {
    if (!details.open) return;
    closeLeadMenus(details);
    positionLeadMenu(details);
   });
  });

  document.querySelectorAll('.js-delete-lead-form').forEach((form) => {
   form.addEventListener('submit', (event) => {
    if (!window.confirm(@json(__('crm.confirm_delete_lead_warning')).replace(':name', form.dataset.leadName || @json(__('crm.client')))) {
     event.preventDefault();
    }
   });
  });

  document.addEventListener('click', (event) => {
   document.querySelectorAll('.lead-more[open]').forEach((details) => {
    if (!details.contains(event.target)) details.open = false;
   });
  });

  document.querySelectorAll('.js-call-followup').forEach((link) => {
   link.addEventListener('click', () => {
    if (link.dataset.callHref) {
     window.setTimeout(() => { window.location.href = link.dataset.callHref; }, 120);
    }
   });
  });

  window.addEventListener('resize', () => closeLeadMenus());
  window.addEventListener('scroll', () => closeLeadMenus(), true);
 })();
</script>
@endpush

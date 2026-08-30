@extends('leads.transfer-layout')

@section('title', __('crm.campaigns'))
@section('page-title', __('crm.campaigns'))

@section('top-actions')
 @can('campaigns.create')
  <a class="btn primary" href="{{ route('v2.campaigns.create') }}">
   {{ __('crm.new_campaign') }}
  </a>
 @endcan
@endsection

@push('styles')
<style>
 .campaign-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(330px,1fr));
  gap:18px;
 }
 .campaign-filters{
  display:grid;
  grid-template-columns:minmax(220px,1.5fr) repeat(2,minmax(170px,1fr));
  gap:12px;
  margin-bottom:20px;
  padding:16px 18px;
  border:1px solid var(--line);
  border-radius:16px;
  background:var(--card);
  box-shadow:var(--shadow);
 }
 .campaign-filter-field label{
  display:block;
  margin:0 3px 6px;
  color:var(--muted);
  font-size:11px;
  font-weight:800;
 }
 .campaign-filter-field input,
 .campaign-filter-field select{
  width:100%;
  height:42px;
  padding:0 12px;
  border:1px solid var(--line);
  border-radius:10px;
  background:var(--bg);
  color:var(--dark);
  font-size:13px;
  font-weight:700;
  outline:none;
  transition:border-color .15s, box-shadow .15s;
 }
 .campaign-filter-field input:focus,
 .campaign-filter-field select:focus{
  border-color:var(--red);
  background:var(--card);
  box-shadow:0 0 0 3px rgba(220,38,55,.1);
 }

 .campaign-card{
  position:relative;
  display:flex;
  flex-direction:column;
  padding:20px;
  border:1px solid var(--line);
  border-radius:18px;
  background:var(--card);
  color:inherit;
  box-shadow:0 4px 18px rgba(15,23,42,.03);
  transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
 }
 .campaign-card:hover{
  transform:translateY(-3px);
  border-color:rgba(220,38,55,.35);
  box-shadow:0 14px 34px rgba(15,23,42,.08);
 }

 .campaign-card-header{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
 }
 .campaign-card-brand{
  display:flex;
  align-items:center;
  gap:12px;
  min-width:0;
  flex:1;
 }
 .campaign-card-thumb,
 .campaign-card-icon{
  width:48px;
  height:48px;
  border-radius:14px;
  flex-shrink:0;
  display:grid;
  place-items:center;
 }
 .campaign-card-thumb{
  object-fit:cover;
  border:1px solid var(--line);
  background:var(--bg);
 }
 .campaign-card-icon{
  background:rgba(220,38,55,.08);
  border:1px solid rgba(220,38,55,.18);
  color:var(--red);
  font-size:20px;
 }

 .campaign-card-tags{
  display:flex;
  flex-direction:column;
  gap:4px;
  min-width:0;
 }
 .campaign-status-pill{
  display:inline-flex;
  align-items:center;
  gap:5px;
  padding:3px 8px;
  border-radius:999px;
  font-size:11px;
  font-weight:800;
  width:fit-content;
 }
 .campaign-status-pill.is-active{
  background:#eaf9ef;
  color:#15803d;
  border:1px solid #bbf7d0;
 }
 .campaign-status-pill.is-upcoming{
  background:#fffbeb;
  color:#b45309;
  border:1px solid #fde68a;
 }
 .campaign-status-pill.is-ended{
  background:#f1f5f9;
  color:#64748b;
  border:1px solid #e2e8f0;
 }
 .status-pulse-dot{
  width:6px;
  height:6px;
  border-radius:50%;
  background:#16a34a;
  display:inline-block;
  box-shadow:0 0 0 2px rgba(22,163,74,.2);
 }

 .campaign-leads-pill{
  display:inline-flex;
  align-items:center;
  gap:5px;
  font-size:11px;
  font-weight:700;
  color:var(--muted);
 }

 .campaign-card-menu{
  position:relative;
  z-index:15;
  margin:0;
 }
 .campaign-card-menu summary{
  width:32px;
  height:32px;
  display:grid;
  place-items:center;
  border:1px solid var(--line);
  border-radius:9px;
  background:var(--card);
  color:var(--muted);
  list-style:none;
  cursor:pointer;
  font-size:18px;
  font-weight:900;
  line-height:1;
  transition:all .15s ease;
 }
 .campaign-card-menu summary::-webkit-details-marker{display:none}
 .campaign-card-menu summary:hover,
 .campaign-card-menu[open] summary{
  border-color:var(--red);
  background:rgba(220,38,55,.08);
  color:var(--red);
 }
 .campaign-card-dropdown{
  position:absolute;
  top:calc(100% + 4px);
  inset-inline-end:0;
  inset-inline-start:auto;
  width:165px;
  padding:6px;
  border:1px solid var(--line);
  border-radius:12px;
  background:var(--card);
  box-shadow:0 14px 34px rgba(15,23,42,.15);
  z-index:25;
 }
 .campaign-card-dropdown a,
 .campaign-card-dropdown button{
  width:100%;
  min-height:36px;
  display:flex;
  align-items:center;
  gap:8px;
  padding:7px 10px;
  border:0;
  border-radius:8px;
  background:transparent;
  color:var(--dark);
  text-decoration:none;
  text-align:start;
  font-size:12px;
  font-weight:800;
  cursor:pointer;
  transition:background .12s ease;
 }
 .campaign-card-dropdown a:hover,
 .campaign-card-dropdown button:hover{
  background:var(--bg);
 }
 .campaign-card-dropdown form{
  margin:4px 0 0;
  padding-top:4px;
  border-top:1px solid var(--line);
 }
 .campaign-card-dropdown .delete-action{
  color:#dc2626;
 }
 .campaign-card-dropdown .delete-action:hover{
  background:rgba(220,38,55,.08);
 }

 .campaign-card-link{
  display:flex;
  flex:1;
  flex-direction:column;
  text-decoration:none;
  color:inherit;
 }
 .campaign-card-main{
  flex:1;
  display:flex;
  flex-direction:column;
 }
 .campaign-card h2{
  margin:0 0 6px;
  font-size:16px;
  font-weight:800;
  line-height:1.4;
  color:var(--dark);
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  text-overflow:ellipsis;
  word-break:break-word;
 }
 .campaign-card-meta{
  display:flex;
  align-items:center;
  gap:6px;
  color:var(--muted);
  font-size:11px;
  font-weight:700;
  margin-bottom:14px;
  font-variant-numeric:tabular-nums;
  flex-wrap:wrap;
 }
 .campaign-card-meta .meta-arrow{
  color:var(--muted);
  opacity:.6;
 }

 .campaign-card-stats{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:8px;
  margin-bottom:14px;
 }
 .campaign-card .campaign-stat{
  padding:10px 12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:var(--bg);
  display:flex;
  flex-direction:column;
  gap:3px;
 }
 .campaign-card .campaign-stat span{
  display:flex;
  align-items:center;
  gap:5px;
  color:var(--muted);
  font-size:10px;
  font-weight:800;
 }
 .campaign-card .campaign-stat strong{
  display:block;
  color:var(--dark);
  font-size:14px;
  font-weight:900;
  font-variant-numeric:tabular-nums;
 }
 .campaign-card .campaign-stat strong small{
  font-size:10px;
  font-weight:700;
  color:var(--muted);
 }

 .campaign-card-users{
  display:flex;
  flex-wrap:wrap;
  gap:5px;
  margin-top:auto;
  padding-bottom:12px;
 }
 .campaign-user-chip{
  display:inline-flex;
  align-items:center;
  padding:4px 9px;
  border-radius:999px;
  background:var(--bg);
  border:1px solid var(--line);
  color:var(--dark);
  font-size:10px;
  font-weight:800;
  max-width:160px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
 }
 .campaign-user-chip.is-more{
  background:rgba(220,38,55,.08);
  border-color:rgba(220,38,55,.2);
  color:var(--red);
 }

 .campaign-card-open{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding-top:12px;
  border-top:1px solid var(--line);
  color:var(--red);
  font-size:12px;
  font-weight:800;
  margin-top:auto;
 }
 .campaign-card-open .open-icon{
  font-size:14px;
  transition:transform .18s ease;
 }
 .campaign-card:hover .campaign-card-open .open-icon{
  transform:translateX(4px);
 }
 html[dir="rtl"] .campaign-card:hover .campaign-card-open .open-icon{
  transform:translateX(-4px);
 }

 .campaign-empty{
  padding:60px 18px;
  border:1px dashed var(--line);
  border-radius:16px;
  background:var(--card);
  color:var(--muted);
  text-align:center;
  font-weight:800;
 }
 .campaign-mobile-create{display:none;margin-bottom:14px}
 .campaign-pager{margin-top:18px}
 @media(max-width:1100px){.campaign-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
 @media(max-width:800px){.campaign-filters{grid-template-columns:1fr}}
 @media(max-width:700px){.campaign-grid{grid-template-columns:1fr}.campaign-mobile-create{display:inline-flex}}

 /* Dark Mode */
 html.dark-mode .campaign-filters{
  background:#141416!important;
  border-color:rgba(255,255,255,.08)!important;
 }
 html.dark-mode .campaign-filter-field label{
  color:#a1a1aa!important;
 }
 html.dark-mode .campaign-filter-field input,
 html.dark-mode .campaign-filter-field select{
  background:#18181b!important;
  border-color:rgba(255,255,255,.14)!important;
  color:#f4f4f5!important;
 }
 html.dark-mode .campaign-filter-field select option{
  background:#18181b!important;
  color:#f4f4f5!important;
 }
 html.dark-mode .campaign-card{
  background:#141416!important;
  border-color:rgba(255,255,255,.08)!important;
  box-shadow:0 4px 20px rgba(0,0,0,.35)!important;
 }
 html.dark-mode .campaign-card:hover{
  border-color:rgba(239,68,68,.4)!important;
  box-shadow:0 14px 34px rgba(0,0,0,.5)!important;
 }
 html.dark-mode .campaign-card-thumb{
  border-color:rgba(255,255,255,.1)!important;
  background:#18181b!important;
 }
 html.dark-mode .campaign-card-icon{
  background:rgba(239,68,68,.15)!important;
  border-color:rgba(239,68,68,.3)!important;
  color:#f87171!important;
 }
 html.dark-mode .campaign-status-pill.is-active{
  background:rgba(34,197,94,.15)!important;
  border-color:rgba(34,197,94,.3)!important;
  color:#4ade80!important;
 }
 html.dark-mode .campaign-status-pill.is-upcoming{
  background:rgba(245,158,11,.15)!important;
  border-color:rgba(245,158,11,.3)!important;
  color:#fbbf24!important;
 }
 html.dark-mode .campaign-status-pill.is-ended{
  background:rgba(255,255,255,.08)!important;
  border-color:rgba(255,255,255,.1)!important;
  color:#a1a1aa!important;
 }
 html.dark-mode .campaign-card h2{
  color:#f4f4f5!important;
 }
 html.dark-mode .campaign-card-meta{
  color:#a1a1aa!important;
 }
 html.dark-mode .campaign-card .campaign-stat{
  background:rgba(255,255,255,.03)!important;
  border-color:rgba(255,255,255,.08)!important;
 }
 html.dark-mode .campaign-card .campaign-stat span{
  color:#a1a1aa!important;
 }
 html.dark-mode .campaign-card .campaign-stat strong{
  color:#f4f4f5!important;
 }
 html.dark-mode .campaign-card .campaign-stat strong small{
  color:#a1a1aa!important;
 }
 html.dark-mode .campaign-user-chip{
  background:rgba(255,255,255,.05)!important;
  border-color:rgba(255,255,255,.08)!important;
  color:#e4e4e7!important;
 }
 html.dark-mode .campaign-user-chip.is-more{
  background:rgba(239,68,68,.15)!important;
  border-color:rgba(239,68,68,.3)!important;
  color:#f87171!important;
 }
 html.dark-mode .campaign-card-menu summary{
  background:#18181b!important;
  border-color:rgba(255,255,255,.14)!important;
  color:#cbd5e1!important;
 }
 html.dark-mode .campaign-card-dropdown{
  background:#18181b!important;
  border-color:rgba(255,255,255,.1)!important;
  box-shadow:0 14px 34px rgba(0,0,0,.5)!important;
 }
 html.dark-mode .campaign-card-dropdown a,
 html.dark-mode .campaign-card-dropdown button{
  color:#e4e4e7!important;
 }
 html.dark-mode .campaign-card-dropdown a:hover,
 html.dark-mode .campaign-card-dropdown button:hover{
  background:rgba(255,255,255,.08)!important;
 }
 html.dark-mode .campaign-card-open{
  border-color:rgba(255,255,255,.08)!important;
  color:#f87171!important;
 }
 html.dark-mode .campaign-empty{
  background:#141416!important;
  border-color:rgba(255,255,255,.1)!important;
  color:#a1a1aa!important;
 }
</style>
@endpush

@section('content')
 @can('campaigns.create')
  <a class="btn primary campaign-mobile-create" href="{{ route('v2.campaigns.create') }}">
   {{ __('crm.new_campaign') }}
  </a>
 @endcan

 <form class="campaign-filters" id="campaignFilters" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" method="GET" action="{{ route('v2.campaigns.index') }}">
  <div class="campaign-filter-field">
   <label for="campaignSearch">{{ __('crm.campaign_name_search') }}</label>
   <input id="campaignSearch" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('crm.campaign_name_placeholder') }}">
  </div>
  <div class="campaign-filter-field">
   <label for="campaignState">{{ __('crm.campaign_status') }}</label>
   <select class="campaign-live-filter" id="campaignState" name="state">
    <option value="">{{ __('crm.all_states') }}</option>
    <option value="active" @selected($filters['state'] === 'active')>{{ __('crm.active_now') }}</option>
    <option value="upcoming" @selected($filters['state'] === 'upcoming')>{{ __('crm.upcoming') }}</option>
    <option value="ended" @selected($filters['state'] === 'ended')>{{ __('crm.ended') }}</option>
   </select>
  </div>
  <div class="campaign-filter-field">
   <label for="campaignUser">{{ __('crm.campaign_user') }}</label>
   <select class="campaign-live-filter" id="campaignUser" name="user_id">
    <option value="">{{ __('crm.all_users') }}</option>
    @foreach ($filterUsers as $filterUser)
     <option value="{{ $filterUser->id }}" @selected($filters['user_id'] === $filterUser->id)>{{ $filterUser->name }}</option>
    @endforeach
   </select>
  </div>
 </form>

 @if ($campaigns->isNotEmpty())
  <section class="campaign-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    @foreach ($campaigns as $campaign)
     @php
      $isUpcoming = $campaign->starts_at > now();
      $isEnded = $campaign->ends_at < now();
      $isActive = ! $isUpcoming && ! $isEnded;
     @endphp
     <article class="campaign-card">
      <div class="campaign-card-header">
       <div class="campaign-card-brand">
        @if ($campaign->image_path)
         <img src="{{ asset('storage/'.$campaign->image_path) }}" alt="{{ $campaign->name }}" class="campaign-card-thumb">
        @else
         <span class="campaign-card-icon" aria-hidden="true">
          <i class="bi bi-megaphone-fill"></i>
         </span>
        @endif
        <div class="campaign-card-tags">
         @if ($isActive)
          <span class="campaign-status-pill is-active"><i class="status-pulse-dot"></i> {{ __('crm.active_now') }}</span>
         @elseif ($isUpcoming)
          <span class="campaign-status-pill is-upcoming"><i class="bi bi-clock"></i> {{ __('crm.upcoming') }}</span>
         @else
          <span class="campaign-status-pill is-ended">{{ __('crm.ended') }}</span>
         @endif
         <span class="campaign-leads-pill">
          <i class="bi bi-people-fill"></i> {{ number_format($campaign->leads_count) }} {{ __('crm.lead_unit') }}
         </span>
        </div>
       </div>

       @if ($campaign->can_manage)
        <details class="campaign-card-menu">
         <summary title="{{ __('crm.campaign_actions') }}" aria-label="{{ __('crm.campaign_actions') }} {{ $campaign->name }}">⋮</summary>
         <div class="campaign-card-dropdown">
          <a href="{{ route('v2.campaigns.edit', $campaign) }}"><i class="bi bi-pencil-square"></i> {{ __('crm.edit_campaign') }}</a>
          <form class="js-delete-campaign-form" method="POST" action="{{ route('v2.campaigns.destroy', $campaign) }}" data-campaign-name="{{ $campaign->name }}">
           @csrf
           @method('DELETE')
           <button class="delete-action" type="submit"><i class="bi bi-trash"></i> {{ __('crm.delete_campaign') }}</button>
          </form>
         </div>
        </details>
       @endif
      </div>

      <a class="campaign-card-link" href="{{ route('v2.campaigns.show', $campaign) }}">
       <div class="campaign-card-main">
        <h2>{{ $campaign->name }}</h2>
        <div class="campaign-card-meta">
         <i class="bi bi-calendar3"></i>
         <span>{{ $campaign->starts_at->format('Y-m-d H:i') }}</span>
         <span class="meta-arrow">{{ app()->getLocale() === 'en' ? '→' : '←' }}</span>
         <span>{{ $campaign->ends_at->format('Y-m-d H:i') }}</span>
        </div>

        <div class="campaign-card-stats">
         <div class="campaign-stat">
          <span><i class="bi bi-cash-stack"></i> {{ __('crm.cost') }}</span>
          <strong>{{ number_format((float) $campaign->cost, 2) }} <small>{{ __('crm.pound') }}</small></strong>
         </div>
         <div class="campaign-stat">
          <span><i class="bi bi-person-badge"></i> {{ __('crm.campaign_team') }}</span>
          <strong>{{ number_format($campaign->users->count()) }} <small>{{ __('crm.user_unit') }}</small></strong>
         </div>
        </div>

        @if ($campaign->users->isNotEmpty())
         <div class="campaign-card-users">
          @foreach ($campaign->users->take(4) as $user)
           <span class="campaign-user-chip" title="{{ $user->name }}">{{ $user->name }}</span>
          @endforeach
          @if ($campaign->users->count() > 4)
           <span class="campaign-user-chip is-more">+{{ $campaign->users->count() - 4 }}</span>
          @endif
         </div>
        @endif
       </div>

       <div class="campaign-card-open">
        <span>{{ __('crm.open_campaign') }}</span>
        <i class="bi {{ app()->getLocale() === 'en' ? 'bi-arrow-right' : 'bi-arrow-left' }} open-icon"></i>
       </div>
      </a>
     </article>
    @endforeach
  </section>

  @if ($campaigns->hasPages())
   <div class="campaign-pager">{{ $campaigns->links() }}</div>
  @endif
 @else
  <div class="campaign-empty">
   {{ __('crm.no_campaigns_available') }}
  </div>
 @endif
@endsection

@push('scripts')
<script>
 (() => {
  const filters = document.getElementById('campaignFilters');
  const search = document.getElementById('campaignSearch');
  const menus = [...document.querySelectorAll('.campaign-card-menu')];

  document.querySelectorAll('.campaign-live-filter').forEach((filter) => {
   filter.addEventListener('change', () => filters?.requestSubmit());
  });

  let searchTimer;
  search?.addEventListener('input', () => {
   window.clearTimeout(searchTimer);
   searchTimer = window.setTimeout(() => filters?.requestSubmit(), 350);
  });

  menus.forEach((menu) => {
   menu.addEventListener('toggle', () => {
    if (!menu.open) return;
    menus.forEach((other) => {
     if (other !== menu) other.open = false;
    });
   });
  });

  document.querySelectorAll('.js-delete-campaign-form').forEach((form) => {
   form.addEventListener('submit', (event) => {
    const name = form.dataset.campaignName || @json(__('crm.this_campaign'));
    const confirmed = window.confirm(
     @json(__('crm.confirm_delete_campaign_prefix')) + name + @json(__('crm.confirm_delete_campaign_suffix'))
     
    );

    if (!confirmed) event.preventDefault();
   });
  });

  document.addEventListener('click', (event) => {
   menus.forEach((menu) => {
    if (!menu.contains(event.target)) menu.open = false;
   });
  });
 })();
</script>
@endpush

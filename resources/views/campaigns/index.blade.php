@extends('leads.transfer-layout')

@section('title', __('crm.campaigns'))
@section('page-title', __('crm.campaigns'))
@section('page-description', __('crm.campaign_page_subtitle'))

@section('top-actions')
 @can('campaigns.create')
  <a class="btn primary" href="{{ route('v2.campaigns.create') }}">
   {{ __('crm.new_campaign') }}
  </a>
 @endcan
@endsection

@push('styles')
<style>
 .campaign-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
 .campaign-filters{display:grid;grid-template-columns:minmax(220px,1.5fr) repeat(2,minmax(170px,1fr));gap:10px;margin-bottom:16px;padding:17px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:var(--shadow)}
 .campaign-filter-field label{display:block;margin:0 3px 6px;color:#657185;font-size:10px;font-weight:900}
 .campaign-filter-field input,.campaign-filter-field select{width:100%;height:43px;padding:0 11px;border:1px solid #dfe3ea;border-radius:10px;background:#fafbfc;color:#3f4b5e;font-size:12px;font-weight:800;outline:none}
 .campaign-filter-field input:focus,.campaign-filter-field select:focus{border-color:#e98692;background:#fff;box-shadow:0 0 0 3px #dc263710}
 .campaign-card{position:relative;min-height:245px;display:flex;flex-direction:column;padding:20px;border:1px solid var(--line);border-radius:16px;background:#fff;color:inherit;box-shadow:var(--shadow);transition:transform .18s,border-color .18s,box-shadow .18s}
 .campaign-card:hover{transform:translateY(-3px);border-color:#ef9ba5;box-shadow:0 18px 42px #17203317}
 .campaign-card-link{display:flex;flex:1;flex-direction:column;padding:0;color:inherit;text-decoration:none}
 .campaign-card-top{min-height:43px;display:flex;align-items:center;justify-content:space-between;gap:12px}
 .campaign-card-icon{width:43px;height:43px;display:grid;place-items:center;flex:0 0 43px;border-radius:12px;background:#fff0f2;color:var(--red);font-size:20px}
 .campaign-card-icon.has-image{overflow:hidden;padding:0;background:#f2f4f7}
 .campaign-card-icon img{width:100%;height:100%;display:block;object-fit:cover}
 .campaign-card h2{margin:14px 0 6px;font-size:19px}
 .campaign-card-meta{color:var(--muted);font-size:11px;line-height:1.8}
 .campaign-card-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:16px 0}
 .campaign-stat{padding:10px;border-radius:10px;background:#f7f9fc}
 .campaign-stat span,.campaign-stat strong{display:block}
 .campaign-stat span{color:var(--muted);font-size:9px;font-weight:900}
 .campaign-stat strong{margin-top:4px;font-size:14px}
 .campaign-card-users{display:flex;flex-wrap:wrap;gap:5px;margin-top:auto}
 .campaign-user-chip{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef3fa;color:#42516a;font-size:10px;font-weight:900}
 .campaign-card-open{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:13px;border-top:1px solid #edf0f4;color:var(--red);font-size:11px;font-weight:900}
 .campaign-card-menu{position:relative;z-index:15;display:inline-flex!important;align-items:center!important;margin:0!important}
 .campaign-card-menu summary{width:34px;height:34px;display:grid;place-items:center;margin:0!important;border:1px solid var(--line);border-radius:9px;background:#fff;color:#596477;list-style:none;cursor:pointer;font-size:20px;font-weight:900;line-height:1;box-shadow:0 5px 14px #17203310}
 .campaign-card-menu summary::-webkit-details-marker{display:none}
 .campaign-card-menu summary:hover,.campaign-card-menu[open] summary{border-color:#d9a4ab;background:#fff4f5;color:var(--red)}
 .campaign-card-dropdown{position:absolute;top:calc(100% + 4px);inset-inline-end:0;inset-inline-start:auto;width:150px;padding:6px;border:1px solid var(--line);border-radius:11px;background:#fff;box-shadow:0 17px 38px #17203328;z-index:20}
 .campaign-card-dropdown a,.campaign-card-dropdown button{width:100%;min-height:39px;display:flex;align-items:center;gap:8px;padding:7px 10px;border:0;border-radius:8px;background:transparent;color:#525e70;text-decoration:none;text-align:start;font-size:12px;font-weight:900;cursor:pointer}
 .campaign-card-dropdown a:hover,.campaign-card-dropdown button:hover{background:#f4f5f7}
 .campaign-card-dropdown form{margin:4px 0 0;padding-top:4px;border-top:1px solid var(--line)}
 .campaign-card-dropdown .delete-action{color:#c52233}
 .campaign-card-dropdown .delete-action:hover{background:#fff0f2}
 .campaign-empty{padding:60px 18px;border:1px dashed var(--line);border-radius:16px;background:#fff;color:var(--muted);text-align:center;font-weight:900}
 .campaign-mobile-create{display:none;margin-bottom:14px}
 .campaign-pager{margin-top:18px}
 @media(max-width:1100px){.campaign-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
 @media(max-width:800px){.campaign-filters{grid-template-columns:1fr}}
 @media(max-width:700px){.campaign-grid{grid-template-columns:1fr}.campaign-mobile-create{display:inline-flex}}

 html.dark-mode .campaign-filters, html.dark .campaign-filters { background:#0d0d0d!important; border-color:#1f1f1f!important; }
 html.dark-mode .campaign-filter-field label, html.dark .campaign-filter-field label { color:#94a3b8!important; }
 html.dark-mode .campaign-filter-field input, html.dark-mode .campaign-filter-field select, html.dark .campaign-filter-field input, html.dark .campaign-filter-field select { background:#1a1a1a!important; border-color:#333!important; color:#fff!important; }
 html.dark-mode .campaign-filter-field select option, html.dark .campaign-filter-field select option { background:#18181b!important; color:#f4f4f5!important; }
 html.dark-mode .campaign-card, html.dark .campaign-card { background:#0d0d0d!important; border-color:#1f1f1f!important; color:#f4f4f5!important; }
 html.dark-mode .campaign-card h2, html.dark .campaign-card h2 { color:#ffffff!important; }
 html.dark-mode .campaign-card-meta, html.dark .campaign-card-meta { color:#94a3b8!important; }
 html.dark-mode .campaign-stat, html.dark .campaign-stat { background:#1a1a1a!important; border:1px solid #2a2a2a!important; }
 html.dark-mode .campaign-stat span, html.dark .campaign-stat span { color:#94a3b8!important; }
 html.dark-mode .campaign-stat strong, html.dark .campaign-stat strong { color:#e2e8f0!important; }
 html.dark-mode .campaign-user-chip, html.dark .campaign-user-chip { background:#1a1a1a!important; border:1px solid #2a2a2a!important; color:#e2e8f0!important; }
 html.dark-mode .campaign-card-menu summary, html.dark .campaign-card-menu summary { background:#1a1a1a!important; border-color:#2a2a2a!important; color:#cbd5e1!important; }
 html.dark-mode .campaign-card-dropdown, html.dark .campaign-card-dropdown { background:#1a1a1a!important; border-color:#2a2a2a!important; }
 html.dark-mode .campaign-card-dropdown a, html.dark-mode .campaign-card-dropdown button, html.dark .campaign-card-dropdown a, html.dark .campaign-card-dropdown button { color:#e2e8f0!important; }
 html.dark-mode .campaign-card-dropdown a:hover, html.dark-mode .campaign-card-dropdown button:hover, html.dark .campaign-card-dropdown a:hover, html.dark .campaign-card-dropdown button:hover { background:#262626!important; }
 html.dark-mode .campaign-empty, html.dark .campaign-empty { background:#0d0d0d!important; border-color:#1f1f1f!important; color:#94a3b8!important; }


 .w-14{width:3.5rem!important;height:3.5rem!important;max-width:3.5rem!important;max-height:3.5rem!important}
 .h-14{height:3.5rem!important;max-height:3.5rem!important}
 .object-cover{object-fit:cover!important}
 .flex-shrink-0{flex-shrink:0!important}
 .campaign-card img{width:56px!important;height:56px!important;max-width:56px!important;max-height:56px!important;object-fit:cover!important;border-radius:12px!important;display:block!important}

</style>
@endpush

@section('content')
 @can('campaigns.create')
  <a class="btn primary campaign-mobile-create" href="{{ route('v2.campaigns.create') }}">
   {{ __('crm.new_campaign') }}
  </a>
 @endcan

 <form class="campaign-filters bg-white dark:bg-[#0d0d0d] border border-transparent dark:border-[#1f1f1f]" id="campaignFilters" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" method="GET" action="{{ route('v2.campaigns.index') }}">
  <div class="campaign-filter-field">
   <label for="campaignSearch" class="dark:text-slate-400">{{ __('crm.campaign_name_search') }}</label>
   <input id="campaignSearch" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('crm.campaign_name_placeholder') }}" class="dark:bg-[#1a1a1a] dark:text-white dark:border-[#333]">
  </div>
  <div class="campaign-filter-field">
   <label for="campaignState" class="dark:text-slate-400">{{ __('crm.campaign_status') }}</label>
   <select class="campaign-live-filter dark:bg-[#1a1a1a] dark:text-white dark:border-[#333]" id="campaignState" name="state">
    <option value="">{{ __('crm.all_states') }}</option>
    <option value="active" @selected($filters['state'] === 'active')>{{ __('crm.active_now') }}</option>
    <option value="upcoming" @selected($filters['state'] === 'upcoming')>{{ __('crm.upcoming') }}</option>
    <option value="ended" @selected($filters['state'] === 'ended')>{{ __('crm.ended') }}</option>
   </select>
  </div>
  <div class="campaign-filter-field">
   <label for="campaignUser" class="dark:text-slate-400">{{ __('crm.campaign_user') }}</label>
   <select class="campaign-live-filter dark:bg-[#1a1a1a] dark:text-white dark:border-[#333]" id="campaignUser" name="user_id">
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
     <article class="campaign-card bg-white dark:bg-[#0d0d0d] border border-slate-100 dark:border-[#1f1f1f] p-5">
      <div class="flex justify-between items-start w-full mb-4">
       <div class="flex-shrink-0">
        @if ($campaign->image_path)
         <img src="{{ asset('storage/'.$campaign->image_path) }}" alt="{{ $campaign->name }}" class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-[#1f1f1f]">
        @else
         <span class="campaign-card-icon w-14 h-14 rounded-xl overflow-hidden border border-slate-200 dark:border-[#1f1f1f] flex items-center justify-center font-bold text-lg text-red-500 bg-red-50 dark:bg-[#1a1a1a]" aria-hidden="true">
          ◈
         </span>
        @endif
       </div>

       <div class="flex flex-row items-center gap-2">
        <span class="badge valid">{{ number_format($campaign->leads_count) }} {{ __('crm.lead_unit') }}</span>

        @if ($campaign->can_manage)
         <details class="campaign-card-menu relative inline-flex items-center m-0">
          <summary title="{{ __('crm.campaign_actions') }}" aria-label="{{ __('crm.campaign_actions') }} {{ $campaign->name }}">⋮</summary>
          <div class="campaign-card-dropdown absolute top-full end-0 mt-1">
           <a href="{{ route('v2.campaigns.edit', $campaign) }}">{{ __('crm.edit_campaign') }}</a>
           <form class="js-delete-campaign-form" method="POST" action="{{ route('v2.campaigns.destroy', $campaign) }}" data-campaign-name="{{ $campaign->name }}">
            @csrf
            @method('DELETE')
            <button class="delete-action" type="submit">{{ __('crm.delete_campaign') }}</button>
           </form>
          </div>
         </details>
        @endif
       </div>
      </div>

      <a class="campaign-card-link" href="{{ route('v2.campaigns.show', $campaign) }}">

     <h2 class="text-slate-900 dark:text-white">{{ $campaign->name }}</h2>
     <div class="campaign-card-meta text-slate-500 dark:text-slate-400">
      {{ $campaign->starts_at->format('Y-m-d H:i') }}
      ←
      {{ $campaign->ends_at->format('Y-m-d H:i') }}
     </div>

     <div class="campaign-card-stats">
      <div class="campaign-stat bg-slate-50 dark:bg-[#1a1a1a] border border-transparent dark:border-[#2a2a2a]">
       <span class="dark:text-slate-400">{{ __('crm.cost') }}</span>
       <strong class="dark:text-slate-200">{{ number_format((float) $campaign->cost, 2) }} {{ __('crm.pound') }}</strong>
      </div>
      <div class="campaign-stat bg-slate-50 dark:bg-[#1a1a1a] border border-transparent dark:border-[#2a2a2a]">
       <span class="dark:text-slate-400">{{ __('crm.campaign_team') }}</span>
       <strong class="dark:text-slate-200">{{ number_format($campaign->users->count()) }} {{ __('crm.user_unit') }}</strong>
      </div>
     </div>

     <div class="campaign-card-users">
      @foreach ($campaign->users->take(4) as $user)
       <span class="campaign-user-chip dark:bg-[#1a1a1a] dark:text-slate-200 dark:border dark:border-[#2a2a2a]">{{ $user->name }}</span>
      @endforeach
      @if ($campaign->users->count() > 4)
       <span class="campaign-user-chip dark:bg-[#1a1a1a] dark:text-slate-200 dark:border dark:border-[#2a2a2a]">+{{ $campaign->users->count() - 4 }}</span>
      @endif
     </div>

      <div class="campaign-card-open">
      <span>{{ __('crm.open_campaign') }}</span>
      <span aria-hidden="true">{{ app()->getLocale() === 'en' ? '→' : '←' }}</span>
      </div>
      </a>
     </article>
   @endforeach
  </section>

  @if ($campaigns->hasPages())
   <div class="campaign-pager">{{ $campaigns->links() }}</div>
  @endif
 @else
  <div class="campaign-empty bg-white dark:bg-[#0d0d0d] border border-dashed border-slate-200 dark:border-[#1f1f1f] text-slate-500 dark:text-slate-400">
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

@php
    $crmSidebarDashboardActive = request()->routeIs(
        'dashboard'
    );

    $crmSidebarLeadsActive = request()->routeIs(
            'v2.leads',
            'v2.leads.*'
        ) && ! request()->routeIs('v2.leads.kanban');

    $crmSidebarKanbanActive = request()->routeIs(
        'v2.leads.kanban'
    );

    $crmSidebarTasksActive = request()->routeIs(
        'v2.followups',
        'v2.tasks.*'
    );

    $crmSidebarCampaignsActive = request()->routeIs(
        'v2.campaigns.*'
    );

    $crmSidebarCollectionsActive = request()->routeIs(
        'v2.collections.*'
    );
    $crmSidebarSettingsActive = request()->routeIs(
        'v2.settings',
        'v2.settings.*'
    );

    $crmSidebarLeadCount = isset($totalLeads)
        ? (int) $totalLeads
        : 0;

    $crmSidebarTaskCount = isset($totalTasks)
        ? (int) $totalTasks
        : 0;
@endphp

{{-- CRM SHARED SIDEBAR ASSET V1 START --}}
@once
<script>
 (() => {
  const root = document.documentElement;
  try {
   if (localStorage.getItem('sokrat.crm.sidebar.collapsed') === '1') {
    root.classList.add('crm-sidebar-collapsed');
   }
   const savedTheme = localStorage.getItem('sokrat.crm.theme');
   const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
   const themes = ['color-light', 'color-dark', 'mono-light', 'mono-dark'];
   const theme = themes.includes(savedTheme)
    ? savedTheme
    : (savedTheme === 'light'
      ? 'color-light'
      : (savedTheme === 'dark' ? 'color-dark' : (prefersDark ? 'color-dark' : 'color-light')));
   root.classList.toggle('dark-mode', theme.endsWith('-dark'));
   root.classList.toggle('crm-monochrome', theme.startsWith('mono-'));
   root.dataset.theme = theme;
   root.dataset.palette = theme.startsWith('mono-') ? 'monochrome' : 'colorful';
  } catch (error) {}
 })();
</script>
@endonce
@once
<link
 rel="stylesheet"
 href="{{ asset('crm-sidebar-shared.css') . '?v=crm-theme-matrix-v4' }}"
>
@endonce
@once
<link
 rel="stylesheet"
 href="{{ asset('css/tajawal.css') }}?v=1.0.0"
>
@endonce
@once
<style>
  .crm-side, .side, #crmSidebar, aside.crm-side {
    -ms-overflow-style: none !important;
    scrollbar-width: none !important;
  }
  .crm-side::-webkit-scrollbar, .side::-webkit-scrollbar, #crmSidebar::-webkit-scrollbar, aside.crm-side::-webkit-scrollbar {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    background: transparent !important;
  }
</style>
@endonce
{{-- CRM SHARED SIDEBAR ASSET V1 END --}}

<aside class="crm-side side" id="crmSidebar">
 <a
  class="crm-side-brand brand"
  href="{{ route('dashboard') }}"
 >
  <img
   class="logo"
   src="{{ asset('images/sokrat-pro-tech.png') }}"
   alt="Sokrat PRO"
  >

  <strong>SokratCRM</strong>
 </a>
 <button
  class="crm-sidebar-collapse-btn flex items-center justify-center"
  id="crmSidebarCollapseBtn"
  type="button"
  aria-label="{{ __('crm.collapse_sidebar') }}"
  aria-pressed="false"
  title="{{ __('crm.collapse_sidebar') }}"
 >
  <i class="bi bi-chevron-double-right ltr:rotate-180" aria-hidden="true"></i>
 </button>


 <nav class="crm-side-nav nav">
  @can('dashboard.view')
  <a
   class="crm-link link {{ $crmSidebarDashboardActive ? 'active' : '' }}"
   href="{{ route('dashboard') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-house-add"></i></span>
   <span class="crm-label label">{{ __('crm.dashboard') }}</span>
  </a>
  @endcan

  @if(Gate::allows('leads.view') || Gate::allows('create', App\Models\Lead::class) || Gate::allows('leads.import') || Gate::allows('leads.export'))
  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarLeadsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmLeadsMenu"
    data-menu="crmLeadsMenu"
    aria-expanded="{{ $crmSidebarLeadsActive ? 'true' : 'false' }}"
    aria-controls="crmLeadsMenu"
   >
    <span class="crm-ico ico"><i class="bi bi-people"></i></span>

    <span class="crm-label label">
     {{ __('crm.leads') }}
    </span>

    <span class="crm-count count">
     {{ number_format($crmSidebarLeadCount) }}
    </span>

    <span class="crm-arrow arrow"><i class="bi bi-chevron-down"></i></span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarLeadsActive ? 'open' : '' }}"
    id="crmLeadsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      @can('leads.view')
      <a
       class="{{ request()->routeIs('v2.leads') ? 'active' : '' }}"
       href="{{ route('v2.leads') }}"
      >
       {{ __('crm.view_leads') }}
      </a>
      @endcan

      @can('create', App\Models\Lead::class)
      <a
       class="{{ request()->routeIs('v2.leads.create') ? 'active' : '' }}"
       href="{{ route('v2.leads.create') }}"
      >
       {{ __('crm.add_lead') }}
      </a>
      @endcan

      @can('leads.import')
      <a
       class="{{ request()->routeIs('v2.leads.import') ? 'active' : '' }}"
       href="{{ route('v2.leads.import') }}"
      >
       {{ __('crm.import_leads') }}
      </a>
      @endcan

      @can('leads.export')
      <a
       class="{{ request()->routeIs('v2.leads.export') ? 'active' : '' }}"
       href="{{ route('v2.leads.export') }}"
      >
       {{ __('crm.export_leads') }}
      </a>
      @endcan
     </nav>
    </div>
   </div>
  </div>
  @endif

  @can('leads.view')
  <a
   class="crm-link link {{ $crmSidebarKanbanActive ? 'active' : '' }}"
   href="{{ route('v2.leads.kanban') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-kanban"></i></span>
   <span class="crm-label label">{{ __('crm.kanban_board_title') ?? __('crm.kanban_view') }}</span>
  </a>
  @endcan

  @can('tasks.view')
  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarTasksActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmTasksMenu"
    data-menu="crmTasksMenu"
    aria-expanded="{{ $crmSidebarTasksActive ? 'true' : 'false' }}"
    aria-controls="crmTasksMenu"
   >
    <span class="crm-ico ico"><i class="bi bi-list-task"></i></span>

    <span class="crm-label label">
     {{ __('crm.tasks_and_followups') }}
    </span>

    <span class="crm-count count">{{ number_format($crmSidebarTaskCount) }}</span>
    <span class="crm-arrow arrow"><i class="bi bi-chevron-down"></i></span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarTasksActive ? 'open' : '' }}"
    id="crmTasksMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.tasks.daily', 'v2.tasks.upcoming', 'v2.followups.scope') && !request()->filled('stage_id') ? 'active' : '' }}"
       href="{{ route('v2.tasks.daily') }}"
      >
       {{ __('crm.daily_tasks') }}
      </a>
      @foreach (($sidebarPipelineStages ?? []) as $sidebarStage)
       <a
        class="crm-task-status-link {{ request()->routeIs('v2.tasks.daily') && (string) request('stage_id') === (string) $sidebarStage->id ? 'active' : '' }}"
        href="{{ route('v2.tasks.daily', ['stage_id' => $sidebarStage->id]) }}"
       >
        {{ $sidebarStage->localizedName() }}
       </a>
      @endforeach
     </nav>
    </div>
   </div>
  </div>
  @endcan

  @if(Gate::allows('campaigns.view') || Gate::allows('campaigns.create') || Gate::allows('campaigns.reports'))
  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarCampaignsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmCampaignsMenu"
    data-menu="crmCampaignsMenu"
    aria-expanded="{{ $crmSidebarCampaignsActive ? 'true' : 'false' }}"
    aria-controls="crmCampaignsMenu"
   >
    <span class="crm-ico ico"><i class="bi bi-shop-window"></i></span>

    <span class="crm-label label">
     {{ __('crm.campaigns') }}
    </span>

    <span class="crm-arrow arrow"><i class="bi bi-chevron-down"></i></span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarCampaignsActive ? 'open' : '' }}"
    id="crmCampaignsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      @can('campaigns.view')
      <a
       class="{{ request()->routeIs('v2.campaigns.index') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.index') }}"
      >
       {{ __('crm.view_campaigns') }}
      </a>
      @endcan

      @can('campaigns.create')
      <a
       class="{{ request()->routeIs('v2.campaigns.create') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.create') }}"
      >
       {{ __('crm.add_campaign') }}
      </a>
      @endcan

      @can('campaigns.reports')
      <a
       class="{{ request()->routeIs('v2.campaigns.reports') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.reports') }}"
      >
       {{ __('crm.campaign_reports') }}
      </a>
      @endcan
     </nav>
    </div>
   </div>
  </div>
  @endif

  @can('collections.view')
  <a
   class="crm-link link {{ $crmSidebarCollectionsActive ? 'active' : '' }}"
   href="{{ route('v2.collections.index') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-cash-stack"></i></span>
   <span class="crm-label label">{{ __('crm.collections') }}</span>
  </a>
  @endcan

  @can('calendar.view')
  <a
   class="crm-link link {{ request()->routeIs('v2.calendar.*') ? 'active' : '' }}"
   href="{{ route('v2.calendar.index') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-calendar3"></i></span>
   <span class="crm-label label">{{ __('crm.calendar_and_events') }}</span>
  </a>
  @endcan

  @can('reports.employees.view')
  <a
   class="crm-link link {{ request()->routeIs('v2.reports.employees*') ? 'active' : '' }}"
   href="{{ route('v2.reports.employees') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-person-lines-fill"></i></span>
   <span class="crm-label label">{{ __('crm.employee_analytics') }}</span>
  </a>
  @endcan

  @if(app(\App\Services\VoipService::class)->isConnected())
  @can('voip.live_panel')
  <a
   class="crm-link link {{ request()->routeIs('v2.voip.live') ? 'active' : '' }}"
   href="{{ route('v2.voip.live') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-telephone-inbound"></i></span>
   <span class="crm-label label">{{ __('crm.call_center_monitoring') }}</span>
  </a>
  @endcan
  @endif

  @can('settings.access')
  <a
   class="crm-link link {{ $crmSidebarSettingsActive ? 'active' : '' }}"
   href="{{ route('v2.settings') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-toggles"></i></span>
   <span class="crm-label label">{{ __('crm.settings') }}</span>
  </a>
  @endcan
 </nav>

</aside>

@once
@include('notifications._center')
@endonce


@once
<script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
@endonce

<!-- CRM TASK SIDEBAR ACTIVE STATUS START -->
<style>
 .crm-task-status-link.active{
  color:var(--red)!important;
  background:#fff0f2!important;
  border-color:#f2c4ca!important;
  font-weight:bold!important
 }

 .crm-task-status-link.active:hover{
  color:var(--red)!important;
  background:#ffe8ec!important
 }
 html[dir="ltr"] .crm-sidebar-collapse-btn i,
 html:not([dir="rtl"]) .crm-sidebar-collapse-btn i,
 .ltr\:rotate-180 {
  transform: rotate(180deg);
 }
 html[dir="rtl"] .crm-sidebar-collapse-btn i {
  transform: none;
 }
</style>
<!-- CRM TASK SIDEBAR ACTIVE STATUS END -->

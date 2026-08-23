@php
    $crmSidebarDashboardActive = request()->routeIs(
        'dashboard'
    );

    $crmSidebarLeadsActive = request()->routeIs(
        'v2.leads',
        'v2.leads.*'
    );

    $crmSidebarTasksActive = request()->routeIs(
        'v2.followups',
        'v2.tasks.*'
    );

    $crmSidebarCampaignsActive = request()->routeIs(
        'v2.campaigns.*'
    );

    $crmSidebarQuotationsActive = request()->routeIs(
        'v2.quotations.*'
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
   root.classList.toggle(
    'dark-mode',
    savedTheme === 'dark' || (savedTheme !== 'light' && prefersDark)
   );
  } catch (error) {}
 })();
</script>
@endonce
@once
<link
 rel="stylesheet"
 href="{{ asset('crm-sidebar-shared.css') . '?v=' . time() }}"
>
@endonce
@once
<link
 rel="stylesheet"
 href="{{ asset('css/tajawal.css') }}?v=1.0.0"
>
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

  <span>
   <strong>SokratCRM</strong>
   <small>{{ __('crm.crm_subtitle') }}</small>
  </span>
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

 <p class="crm-side-caption caption">
  {{ __('crm.main_menu') }}
 </p>

 <nav class="crm-side-nav nav">
  <a
   class="crm-link link {{ $crmSidebarDashboardActive ? 'active' : '' }}"
   href="{{ route('dashboard') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-house-add"></i></span>
   <span class="crm-label label">{{ __('crm.dashboard') }}</span>
  </a>

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

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarLeadsActive ? 'open' : '' }}"
    id="crmLeadsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.leads') ? 'active' : '' }}"
       href="{{ route('v2.leads') }}"
      >
       {{ __('crm.view_leads') }}
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.create') ? 'active' : '' }}"
       href="{{ route('v2.leads.create') }}"
      >
       {{ __('crm.add_lead') }}
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.import') ? 'active' : '' }}"
       href="{{ route('v2.leads.import') }}"
      >
       {{ __('crm.import_leads') }}
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.export') ? 'active' : '' }}"
       href="{{ route('v2.leads.export') }}"
      >
       {{ __('crm.export_leads') }}
      </a>
     </nav>
    </div>
   </div>
  </div>

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
    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarTasksActive ? 'open' : '' }}"
    id="crmTasksMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.tasks.daily', 'v2.tasks.upcoming', 'v2.followups.scope') ? 'active' : '' }}"
       href="{{ route('v2.tasks.daily') }}"
      >
       {{ __('crm.daily_tasks') }}
      </a>
      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'new' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'new']) }}"
      >
       {{ __('crm.status_new') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'no-answer' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'no-answer']) }}"
      >
       {{ __('crm.status_no_answer') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'interested' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'interested']) }}"
      >
       {{ __('crm.status_interested') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'not-interested' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'not-interested']) }}"
      >
       {{ __('crm.status_not_interested') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'meeting' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'meeting']) }}"
      >
       {{ __('crm.status_meeting') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'quotation' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'quotation']) }}"
      >
       {{ __('crm.status_quotation') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'discussion' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'discussion']) }}"
      >
       {{ __('crm.status_discussion') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'contract-closing' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'contract-closing']) }}"
      >
       {{ __('crm.status_contract_closing') }}
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'execution' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'execution']) }}"
      >
       {{ __('crm.status_execution') }}
      </a>
     </nav>
    </div>
   </div>
  </div>

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

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarCampaignsActive ? 'open' : '' }}"
    id="crmCampaignsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.campaigns.index') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.index') }}"
      >
       {{ __('crm.view_campaigns') }}
      </a>

      <a
       class="{{ request()->routeIs('v2.campaigns.create') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.create') }}"
      >
       {{ __('crm.add_campaign') }}
      </a>

      <a
       class="{{ request()->routeIs('v2.campaigns.reports') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.reports') }}"
      >
       {{ __('crm.campaign_reports') }}
      </a>
     </nav>
    </div>
   </div>
  </div>

  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarQuotationsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmQuotationsMenu"
    data-menu="crmQuotationsMenu"
    aria-expanded="{{ $crmSidebarQuotationsActive ? 'true' : 'false' }}"
    aria-controls="crmQuotationsMenu"
   >
    <span class="crm-ico ico"><i class="bi bi-file-earmark-text"></i></span>

    <span class="crm-label label">
     {{ __('crm.price_quotation') }}
    </span>

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarQuotationsActive ? 'open' : '' }}"
    id="crmQuotationsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.quotations.create') ? 'active' : '' }}"
       href="{{ route('v2.quotations.create') }}"
      >
       {{ __('crm.create_quotation') }}
      </a>

      <a
       class="{{
        request()->routeIs(
         'v2.quotations.index',
         'v2.quotations.show'
        )
         ? 'active'
         : ''
       }}"
       href="{{ route('v2.quotations.index') }}"
      >
       {{ __('crm.quotations') }}
      </a>
     </nav>
    </div>
   </div>
  </div>

  @can('calendar.view')
  <a
   class="crm-link link {{ request()->routeIs('v2.calendar.*') ? 'active' : '' }}"
   href="{{ route('v2.calendar.index') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-calendar3"></i></span>
   <span class="crm-label label">{{ __('crm.calendar_and_events') }}</span>
  </a>
  @endcan

  @if(app(\App\Services\VoipService::class)->isConfigured())
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

  <a
   class="crm-link link {{ $crmSidebarSettingsActive ? 'active' : '' }}"
   href="{{ route('v2.settings') }}"
  >
   <span class="crm-ico ico"><i class="bi bi-toggles"></i></span>
   <span class="crm-label label">{{ __('crm.settings') }}</span>
  </a>
 </nav>

</aside>

@once
@include('notifications._center')
@endonce


@once
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}?v=crm-sidebar-collapse-v1"></script>
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

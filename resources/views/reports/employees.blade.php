<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('crm.employee_analytics') }} - SokratCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --red: #dc2637;
            --dark: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --card: #fff;
            --font-primary: 'Tajawal', system-ui, -apple-system, sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; background: var(--bg); color: var(--dark); font-family: var(--font-primary); }
        .crm-app { display: flex; min-height: 100vh; }
        .crm-main { flex: 1; min-width: 0; padding: 20px 28px 60px; text-align: start; }
        
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; flex-wrap: wrap; }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar h1 { margin: 0; font-size: 22px; font-weight: 900; color: var(--dark); display: flex; align-items: center; gap: 8px; }
        .topbar p { margin: 2px 0 0; color: var(--muted); font-size: 12px; }
        .top-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-inline-start: auto; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; min-height: 36px; padding: 0 12px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--dark); font-weight: 800; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 12px; transition: all .15s ease; }
        .btn:hover { background: var(--bg); transform: translateY(-1px); }
        .btn.primary { background: var(--red); border-color: var(--red); color: #fff; }
        .btn.primary:hover { background: #b81829; border-color: #b81829; color: #fff; }
        .btn.soft { background: var(--bg); border-color: var(--line); color: var(--dark); }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            background: rgba(220,38,55,0.08);
            color: var(--red);
            border: 1px solid rgba(220,38,55,0.2);
        }

        /* Compact Filter Bar */
        .filter-bar-compact {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .filter-bar-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-presets { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .preset-pill {
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--bg);
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s ease;
        }
        .preset-pill:hover, .preset-pill.active {
            background: var(--card);
            border-color: var(--red);
            color: var(--red);
        }

        .filter-inputs-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-control {
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--card);
            color: var(--dark);
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            outline: none;
            transition: border-color .15s;
            flex: 1 1 125px;
            min-width: 110px;
        }
        .filter-control.search-field {
            flex: 2 1 190px;
            min-width: 150px;
        }
        .filter-control:focus { border-color: var(--red); box-shadow: 0 0 0 2px rgba(220,38,55,0.12); }
        .filter-actions-group { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

        /* Metrics Summary Strip */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .metric-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .metric-card span { color: var(--muted); font-size: 11px; font-weight: 800; }
        .metric-card strong { font-size: 20px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }

        /* Visual Analytics Grid */
        .analytics-grid-two {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .analytics-full-width {
            margin-bottom: 18px;
        }
        .analytics-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .analytics-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }
        .analytics-card-head h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .analytics-card-head p {
            margin: 2px 0 0;
            font-size: 11px;
            color: var(--muted);
        }

        /* Table Card */
        .table-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .table-wrap { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; min-width: 950px; }
        th, td { padding: 11px 12px; text-align: start; font-size: 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { color: var(--muted); font-weight: 800; font-size: 11px; background: var(--bg); }
        tr:last-child td { border-bottom: 0; }
        td strong { font-weight: 800; color: var(--dark); }

        .staff-identity { display: flex; align-items: center; gap: 8px; }
        .staff-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(220,38,55,0.08);
            color: var(--red);
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .staff-meta strong { display: block; font-size: 12px; }
        .staff-meta small { color: var(--muted); font-size: 10px; }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 7px; border-radius: 6px; font-size: 10px; font-weight: 800; }
        .badge.online { background: #dcfce7; color: #15803d; }
        .badge.incall { background: #fee2e2; color: #b91c1c; }
        .badge.offline { background: #f1f5f9; color: #64748b; }

        .channel-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 800;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            transition: all .15s;
        }
        .btn-action:hover {
            border-color: var(--red);
            color: var(--red);
            transform: translateY(-1px);
        }

        .pagination-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 14px;
            margin-top: 10px;
            border-top: 1px solid var(--line);
            flex-wrap: wrap;
        }
        .pagination-info { font-size: 11px; font-weight: 700; color: var(--muted); }

        /* High-Contrast Dark Mode */
        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f8fafc !important; }
        html.dark-mode .filter-bar-compact,
        html.dark-mode .metric-card,
        html.dark-mode .analytics-card,
        html.dark-mode .table-card {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #f8fafc !important;
        }
        html.dark-mode .filter-control {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.12) !important;
            color: #f8fafc !important;
        }
        html.dark-mode th {
            background: #1f1f23 !important;
            color: #a1a1aa !important;
            border-bottom-color: rgba(255,255,255,0.08) !important;
        }
        html.dark-mode td {
            border-bottom-color: rgba(255,255,255,0.08) !important;
            color: #e2e8f0 !important;
        }
        html.dark-mode td strong { color: #f8fafc !important; }
        html.dark-mode .btn-action { background: #27272a; border-color: rgba(255,255,255,0.1); color: #f8fafc; }
        html.dark-mode .preset-pill { background: #27272a; color: #94a3b8; border-color: rgba(255,255,255,0.08); }

        @media(max-width: 1024px) {
            .analytics-grid-two { grid-template-columns: 1fr; }
        }
        @media(max-width: 768px) {
            .crm-main { padding: 14px; }
            .filter-inputs-row { grid-template-columns: 1fr 1fr; }
            .filter-actions-group { grid-column: 1 / -1; }
            .filter-actions-group .btn { width: 100%; }
        }
    </style>
</head>
<body>
@include('partials.page-loader')
<div class="crm-app">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <h1>
                        <i class="bi bi-person-lines-fill" style="color: var(--red);"></i>
                        {{ __('crm.employee_analytics') }}
                    </h1>
                </div>
            </div>
            <div class="top-actions">
                @if($viewer->isSuperAdmin())
                    <span class="role-pill">
                        <i class="bi bi-shield-check"></i> Super Admin
                    </span>
                @elseif($viewer->isTeamLeader())
                    <span class="role-pill">
                        <i class="bi bi-people-fill"></i> Team Leader
                    </span>
                @elseif($viewer->isCollectionManager())
                    <span class="role-pill">
                        <i class="bi bi-cash-stack"></i> Collection Manager
                    </span>
                @endif
                <a href="{{ route('dashboard') }}" class="btn soft">
                    {{ __('crm.back_to_dashboard') }}
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        <!-- Compact Filter Bar -->
        <section class="filter-bar-compact">
            <div class="filter-bar-top">
                <span style="font-size: 12px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-sliders" style="color: var(--red);"></i> {{ __('crm.filter_campaign_report') ?? 'تصفية النطاق' }}
                </span>
                <div class="filter-presets" role="group" aria-label="Date presets">
                    <button type="button" class="preset-pill {{ $filters['period'] === 'today' ? 'active' : '' }}" data-range="today">{{ __('crm.today') }}</button>
                    <button type="button" class="preset-pill {{ $filters['period'] === 'week' ? 'active' : '' }}" data-range="week">{{ __('crm.this_week') }}</button>
                    <button type="button" class="preset-pill {{ $filters['period'] === 'month' ? 'active' : '' }}" data-range="month">{{ __('crm.this_month') }}</button>
                    <button type="button" class="preset-pill {{ $filters['period'] === 'year' ? 'active' : '' }}" data-range="year">{{ __('crm.this_year') }}</button>
                    <button type="button" class="preset-pill {{ $filters['period'] === 'all' ? 'active' : '' }}" data-range="all">{{ __('crm.all_periods') }}</button>
                </div>
            </div>

            <form class="filter-inputs-row" method="GET" action="{{ route('v2.reports.employees') }}" id="employeeAnalyticsFilterForm">
                <select id="periodSelect" name="period" class="filter-control" style="max-width: 140px;">
                    <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
                    <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
                    <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
                    <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
                    <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
                    <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
                </select>

                <input id="fromDate" name="from" type="date" value="{{ $filters['from'] }}" class="filter-control" title="{{ __('crm.from_date') }}" style="max-width: 135px;">
                <input id="toDate" name="to" type="date" value="{{ $filters['to'] }}" class="filter-control" title="{{ __('crm.to_date') }}" style="max-width: 135px;">

                @if($viewer->isSuperAdmin() && $branches->isNotEmpty())
                    <select id="branchSelect" name="branch_id" class="filter-control" style="max-width: 140px;">
                        <option value="">{{ __('crm.all_branches') ?? 'كل الفروع' }}</option>
                        @foreach ($branches as $br)
                            <option value="{{ $br->id }}" @selected((string) $filters['branch_id'] === (string) $br->id)>
                                {{ $br->name_ar }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @if(!empty($groups) && $groups->isNotEmpty())
                    <select id="groupSelect" name="group_id" class="filter-control" style="max-width: 140px;">
                        <option value="">{{ app()->getLocale() === 'en' ? 'All Roles' : 'كل المجموعات' }}</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}" @selected((string) $filters['group_id'] === (string) $g->id)>
                                {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select id="statusFilter" name="status" class="filter-control" style="max-width: 120px;">
                    <option value="all" @selected($filters['status'] === 'all')>{{ __('crm.all') }}</option>
                    <option value="online" @selected($filters['status'] === 'online')>{{ __('crm.online') }}</option>
                    <option value="incall" @selected($filters['status'] === 'incall')>{{ __('crm.in_call') }}</option>
                    <option value="offline" @selected($filters['status'] === 'offline')>{{ __('crm.offline') }}</option>
                </select>

                <input id="searchQuery" name="q" type="text" value="{{ $filters['q'] }}" class="filter-control search-field" placeholder="{{ app()->getLocale() === 'en' ? 'Employee or extension...' : 'اسم الموظف أو التحويلة...' }}">

                <div class="filter-actions-group">
                    <button class="btn primary" type="submit" style="height: 36px;">
                        <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                    </button>
                    <a href="{{ route('v2.reports.employees') }}" class="btn soft" title="{{ __('crm.reset_filters') }}" style="height: 36px; width: 36px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </section>

        <!-- Summary Metrics Strip -->
        <section class="metrics-strip">
            <div class="metric-card">
                <span>{{ __('crm.supervised_employees') }}</span>
                <strong>{{ number_format($summary['total_employees']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.online_extensions') }}</span>
                <strong style="color: #16a34a;">{{ number_format($summary['online_extensions']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.managed_leads') }}</span>
                <strong>{{ number_format($summary['total_leads']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.followups_logged') }}</span>
                <strong style="color: #0284c7;">{{ number_format($summary['total_followups']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.converted_donors') }}</span>
                <strong style="color: #16a34a;">{{ number_format($summary['total_donors']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.total_generated_value') }}</span>
                <strong style="color: var(--red);">{{ number_format($summary['grand_total_value'], 2) }} <small style="font-size: 10px;">{{ __('crm.currency_egp') }}</small></strong>
            </div>
        </section>

        <!-- Visual Charts Grid Row 1 (Operations & Channels) -->
        <section class="analytics-grid-two">
            <div class="analytics-card">
                <div class="analytics-card-head">
                    <div>
                        <h2><i class="bi bi-bar-chart-fill" style="color: var(--red);"></i> {{ __('crm.staff_performance_ranking') }}</h2>
                        <p>{{ app()->getLocale() === 'en' ? 'Follow-ups, Converted Donors, Donations & Collections' : 'مقارنة المتابعات، المتبرعين المحولين، التبرعات، والتحصيلات للموظفين' }}</p>
                    </div>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="staffRankingChart"></canvas>
                </div>
            </div>

            <div class="analytics-card">
                <div class="analytics-card-head">
                    <div>
                        <h2><i class="bi bi-pie-chart-fill" style="color: #0284c7;"></i> {{ __('crm.staff_activity_mix') }}</h2>
                        <p>{{ app()->getLocale() === 'en' ? 'Communication channels mix and workload share' : 'توزيع قنوات التواصل وأعباء العمل' }}</p>
                    </div>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="activityMixChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Visual Charts Grid Row 2 (Revenue & Value Generated) -->
        <section class="analytics-card analytics-full-width">
            <div class="analytics-card-head">
                <div>
                    <h2><i class="bi bi-cash-stack" style="color: #16a34a;"></i> {{ app()->getLocale() === 'en' ? 'Financial Revenue & Value Generated by Staff' : 'القيمة المالية المحققة للمؤسسة حسب الموظف (تبرعات وتحصيلات)' }}</h2>
                    <p>{{ app()->getLocale() === 'en' ? 'Top performers ranked by total revenue output in EGP' : 'ترتيب الموظفين حسب القيمة المالية المحصلة والمستلمة بالجنيه المصري' }}</p>
                </div>
            </div>
            <div style="position: relative; height: 240px;">
                <canvas id="staffRevenueChart"></canvas>
            </div>
        </section>

        <!-- Detailed Employee Table -->
        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('crm.staff_member') ?? 'الموظف' }}</th>
                            <th>{{ __('crm.direct_manager_or_team_leader') }}</th>
                            <th>{{ __('crm.branch') }}</th>
                            <th>{{ __('crm.extension_short') }}</th>
                            <th>{{ __('crm.assigned_leads') ?? 'العملاء' }}</th>
                            <th>{{ __('crm.followups_logged') }}</th>
                            <th>{{ __('crm.converted_donors') }}</th>
                            <th>{{ __('crm.donations_amount') }}</th>
                            <th>{{ __('crm.collections_amount') }}</th>
                            <th>{{ __('crm.total_generated_value') }}</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffStats as $staff)
                            <tr>
                                <td>
                                    <div class="staff-identity">
                                        <div class="staff-avatar">{{ mb_substr($staff['name'], 0, 2) }}</div>
                                        <div class="staff-meta">
                                            <a href="{{ route('v2.reports.employees.show', $staff['user']) }}" style="color: inherit; text-decoration: none;">
                                                <strong>{{ $staff['name'] }}</strong>
                                            </a>
                                            <small>{{ $staff['username'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($staff['manager'])
                                        <span style="font-weight: 700;">{{ $staff['manager']->name }}</span>
                                    @else
                                        <span style="color: var(--muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $staff['branch']?->name_ar ?? '—' }}
                                </td>
                                <td>
                                    @if($staff['extension'])
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="font-family: monospace; font-weight: 800;">{{ $staff['extension'] }}</span>
                                            @if(!empty($staff['voip_info']['in_call']))
                                                <span class="badge incall">In Call</span>
                                            @elseif(!empty($staff['voip_info']['online']))
                                                <span class="badge online">Online</span>
                                            @else
                                                <span class="badge offline">Offline</span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color: var(--muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($staff['assigned_leads']) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($staff['total_followups']) }}</strong>
                                    <div style="display: flex; gap: 6px; margin-top: 3px;">
                                        <span class="channel-chip"><i class="bi bi-telephone-fill" style="color:#0284c7;"></i> {{ $staff['calls_count'] }}</span>
                                        <span class="channel-chip"><i class="bi bi-people-fill" style="color:#8b5cf6;"></i> {{ $staff['meetings_count'] }}</span>
                                        <span class="channel-chip"><i class="bi bi-chat-dots-fill" style="color:#16a34a;"></i> {{ $staff['whatsapp_count'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: #16a34a;">{{ number_format($staff['converted_donors']) }}</strong>
                                </td>
                                <td>
                                    <strong>{{ number_format($staff['donations_amount'], 2) }}</strong>
                                    <small style="display: block; color: var(--muted); font-size: 11px;">({{ $staff['donations_count'] }} {{ __('crm.donations_count') ?? 'تبرع' }})</small>
                                </td>
                                <td>
                                    <strong>{{ number_format($staff['collections_amount'], 2) }}</strong>
                                    <small style="display: block; color: var(--muted); font-size: 11px;">({{ $staff['collections_count'] }} {{ __('crm.cases') ?? 'حالة' }})</small>
                                </td>
                                <td>
                                    <strong style="color: var(--red); font-size: 13px;">{{ number_format($staff['total_value'], 2) }}</strong>
                                    <small style="font-size: 10px; color: var(--muted);">{{ __('crm.currency_egp') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('v2.reports.employees.show', $staff['user']) }}" class="btn-action" title="{{ __('crm.view_details') }}">
                                        <i class="bi bi-person-bounding-box"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 36px; color: var(--muted);">
                                    <i class="bi bi-people" style="font-size: 32px; display: block; margin-bottom: 6px;"></i>
                                    <p style="margin: 0;">{{ __('crm.no_matching_employees_found') ?? 'لا يوجد موظفون مطابقون لمعايير البحث والتصفية.' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($staffStats->hasPages())
                <div class="pagination-toolbar">
                    <div class="pagination-info">
                        {{ __('crm.showing_items_of_total', ['from' => $staffStats->firstItem() ?? 0, 'to' => $staffStats->lastItem() ?? 0, 'total' => $staffStats->total()]) }}
                    </div>
                    <div class="pagination-links">
                        {{ $staffStats->links() }}
                    </div>
                </div>
            @endif
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.classList.contains('dark-mode');
        const textColor = isDark ? '#f8fafc' : '#0f172a';
        const mutedColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

        // Quick Preset Buttons
        document.querySelectorAll('.preset-pill').forEach(function(pill) {
            pill.addEventListener('click', function() {
                const range = this.getAttribute('data-range');
                const periodSelect = document.getElementById('periodSelect');
                if (periodSelect) periodSelect.value = range;
                const fromInput = document.getElementById('fromDate');
                const toInput = document.getElementById('toDate');
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const fmt = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());

                if (range === 'today') {
                    fromInput.value = fmt(now);
                    toInput.value = fmt(now);
                } else if (range === 'week') {
                    const d = new Date();
                    d.setDate(now.getDate() - 7);
                    fromInput.value = fmt(d);
                    toInput.value = fmt(now);
                } else if (range === 'month') {
                    fromInput.value = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                    toInput.value = fmt(now);
                } else if (range === 'year') {
                    fromInput.value = fmt(new Date(now.getFullYear(), 0, 1));
                    toInput.value = fmt(now);
                } else if (range === 'all') {
                    fromInput.value = '';
                    toInput.value = '';
                }
                document.getElementById('employeeAnalyticsFilterForm').submit();
            });
        });

        // 1. Staff Operational Performance Chart
        const rankingCanvas = document.getElementById('staffRankingChart');
        if (rankingCanvas) {
            const chartData = @json($chartData);
            new Chart(rankingCanvas, {
                type: 'bar',
                data: {
                    labels: chartData.labels || [],
                    datasets: [
                        {
                            label: @json(__('crm.followups_logged')),
                            data: chartData.followups || [],
                            backgroundColor: '#0284c7',
                            borderRadius: 4,
                        },
                        {
                            label: @json(__('crm.converted_donors')),
                            data: chartData.donors || [],
                            backgroundColor: '#16a34a',
                            borderRadius: 4,
                        },
                        {
                            label: @json(__('crm.donations_amount') . ' (' . __('crm.donations_count') . ')'),
                            data: chartData.donations || [],
                            backgroundColor: '#dc2637',
                            borderRadius: 4,
                        },
                        {
                            label: @json(__('crm.collections_amount') . ' (' . __('crm.cases') . ')'),
                            data: chartData.collections || [],
                            backgroundColor: '#8b5cf6',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, font: { family: 'Tajawal', size: 12, weight: '700' }, padding: 12 }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#fff',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyColor: '#e2e8f0',
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor, font: { family: 'Tajawal', size: 11, weight: '700' } },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: { color: textColor, font: { family: 'Tajawal', size: 12, weight: '800' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Activity Mix Doughnut Chart
        const mixCanvas = document.getElementById('activityMixChart');
        if (mixCanvas) {
            const channels = @json($channelsData);
            const totalActivity = (channels.calls || 0) + (channels.meetings || 0) + (channels.whatsapp || 0);
            new Chart(mixCanvas, {
                type: 'doughnut',
                data: {
                    labels: [
                        `Calls (${channels.calls || 0})`,
                        `Meetings (${channels.meetings || 0})`,
                        `WhatsApp (${channels.whatsapp || 0})`
                    ],
                    datasets: [{
                        data: [channels.calls || 0, channels.meetings || 0, channels.whatsapp || 0],
                        backgroundColor: ['#0284c7', '#8b5cf6', '#16a34a'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, font: { family: 'Tajawal', size: 12, weight: '700' }, padding: 12 }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#fff',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyColor: '#e2e8f0',
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => {
                                    const val = Number(ctx.raw || 0);
                                    const pct = totalActivity > 0 ? ((val / totalActivity) * 100).toFixed(1) : 0;
                                    return ` ${ctx.label}: ${val} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Staff Revenue / Financial Output Chart
        const revenueCanvas = document.getElementById('staffRevenueChart');
        if (revenueCanvas) {
            const finData = @json($financialChartData);
            new Chart(revenueCanvas, {
                type: 'bar',
                data: {
                    labels: finData.labels || [],
                    datasets: [
                        {
                            label: @json(__('crm.donations_amount') . ' (EGP)'),
                            data: finData.donations || [],
                            backgroundColor: '#16a34a',
                            borderRadius: 4,
                        },
                        {
                            label: @json(__('crm.collections_amount') . ' (EGP)'),
                            data: finData.collections || [],
                            backgroundColor: '#8b5cf6',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, font: { family: 'Tajawal', size: 12, weight: '700' }, padding: 12 }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#fff',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyColor: '#e2e8f0',
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ` ${ctx.dataset.label}: ${Number(ctx.raw || 0).toLocaleString()} EGP`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor,
                                font: { family: 'Tajawal', size: 11, weight: '700' },
                                callback: (val) => Number(val).toLocaleString() + ' EGP'
                            },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: { color: textColor, font: { family: 'Tajawal', size: 12, weight: '800' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

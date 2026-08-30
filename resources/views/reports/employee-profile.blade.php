<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $employee->name }} - {{ __('crm.employee_analytics') }} - SokratCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --red: #dc2637;
            --dark: #182033;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --card: #fff;
            --font-primary: 'Tajawal', system-ui, -apple-system, sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-width: 320px; background: var(--bg); color: var(--dark); font-family: var(--font-primary); }
        .crm-app { display: flex; min-height: 100vh; }
        .crm-main { flex: 1; min-width: 0; padding: 24px 32px 60px; text-align: start; }
        
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 24px; flex-wrap: wrap; }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .topbar h1 { margin: 0; font-size: 24px; font-weight: 900; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .topbar p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-inline-start: auto; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 0 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); color: var(--dark); font-weight: 800; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 13px; transition: all .15s ease; }
        .btn:hover { background: var(--bg); transform: translateY(-1px); }
        .btn.primary { background: var(--red); border-color: var(--red); color: #fff; }
        .btn.primary:hover { background: #b81829; border-color: #b81829; color: #fff; }
        .btn.soft { background: var(--bg); border-color: var(--line); color: var(--dark); }

        /* Employee Identity Card */
        .profile-hero-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }
        .profile-avatar-large {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(220,38,55,0.08);
            color: var(--red);
            font-size: 24px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(220,38,55,0.2);
            flex-shrink: 0;
            position: relative;
        }
        .profile-hero-info h2 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 900;
            color: var(--dark);
        }
        .profile-meta-tags {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .profile-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 6px;
            background: var(--bg);
            border: 1px solid var(--line);
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
        }

        /* Filter Card */
        .filter-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }
        .filter-form-grid {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
        }
        .filter-field { display: flex; flex-direction: column; gap: 6px; }
        .filter-field label { font-size: 12px; font-weight: 800; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .filter-control {
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            color: var(--dark);
            font: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .15s;
        }
        .filter-control:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(220,38,55,0.12); }

        /* Metrics Strip */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        }
        .metric-card span { color: var(--muted); font-size: 12px; font-weight: 700; }
        .metric-card strong { font-size: 22px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }

        /* Charts Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .analytics-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }
        .analytics-card h2 {
            margin: 0 0 16px;
            font-size: 16px;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Tabs Section */
        .tabs-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }
        .nav-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 14px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--line);
            overflow-x: auto;
        }
        .nav-tab-btn {
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .nav-tab-btn:hover {
            background: var(--bg);
            color: var(--dark);
        }
        .nav-tab-btn.active {
            background: rgba(220,38,55,0.08);
            border-color: rgba(220,38,55,0.2);
            color: var(--red);
        }

        table { width: 100%; border-collapse: collapse; min-width: 750px; }
        th, td { padding: 12px 14px; text-align: start; font-size: 13px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { color: var(--muted); font-weight: 800; font-size: 12px; background: var(--bg); }
        tr:last-child td { border-bottom: 0; }
        td strong { font-weight: 800; color: var(--dark); }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; }
        .badge.active { background: #dcfce7; color: #15803d; }
        .badge.inactive { background: #fee2e2; color: #b91c1c; }

        /* Dark Mode */
        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f4f4f5;
            --muted: #a1a1aa;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f4f4f5 !important; }
        html.dark-mode .profile-hero-card,
        html.dark-mode .filter-card,
        html.dark-mode .metric-card,
        html.dark-mode .analytics-card,
        html.dark-mode .tabs-card {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode .filter-control {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.12) !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode th {
            background: #1f1f23 !important;
            color: #a1a1aa !important;
            border-bottom-color: rgba(255,255,255,0.08) !important;
        }
        html.dark-mode td {
            border-bottom-color: rgba(255,255,255,0.08) !important;
            color: #d4d4d8 !important;
        }
        html.dark-mode td strong { color: #f4f4f5 !important; }
        html.dark-mode .profile-tag { background: #27272a; border-color: rgba(255,255,255,0.1); color: #a1a1aa; }

        @media(max-width: 1024px) {
            .analytics-grid { grid-template-columns: 1fr; }
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
                        <a href="{{ route('v2.reports.employees') }}" style="color: inherit; text-decoration: none; opacity: 0.7;">
                            <i class="bi bi-person-lines-fill"></i> {{ __('crm.employee_analytics') }}
                        </a>
                        <span style="opacity: 0.4; margin: 0 4px;">/</span>
                        <span>{{ $employee->name }}</span>
                    </h1>
                    <p>{{ $employee->username }} &middot; {{ $employee->branch?->name_ar ?? __('crm.no_branch') }}</p>
                </div>
            </div>
            <div class="top-actions">
                @can('users.update')
                    <a href="{{ route('v2.settings.users.edit', $employee) }}" class="btn soft">
                        <i class="bi bi-pencil-square"></i> {{ __('crm.edit_user') }}
                    </a>
                @endcan
                <a href="{{ route('v2.reports.employees') }}" class="btn soft">
                    <i class="bi bi-arrow-right"></i> {{ __('crm.back') }}
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        <!-- Employee Credentials & Details Hero Card -->
        <section class="profile-hero-card">
            <div class="profile-hero-left">
                <div class="profile-avatar-large">
                    {{ mb_substr($employee->name, 0, 2) }}
                </div>
                <div class="profile-hero-info">
                    <h2>{{ $employee->name }}</h2>
                    <div style="font-size: 13px; color: var(--muted); font-weight: 700;">
                        <span><i class="bi bi-person"></i> {{ $employee->username }}</span>
                        @if($employee->email)
                            <span style="margin: 0 8px;">&middot;</span>
                            <span><i class="bi bi-envelope"></i> {{ $employee->email }}</span>
                        @endif
                        @if($employee->mobile_phone)
                            <span style="margin: 0 8px;">&middot;</span>
                            <span dir="ltr"><i class="bi bi-phone"></i> {{ $employee->mobile_phone }}</span>
                        @endif
                    </div>

                    <div class="profile-meta-tags">
                        @if($employee->branch)
                            <span class="profile-tag">
                                <i class="bi bi-buildings"></i> {{ $employee->branch->name_ar }}
                            </span>
                        @endif

                        @if($employee->manager)
                            <span class="profile-tag">
                                <i class="bi bi-person-badge"></i> {{ __('crm.direct_manager_or_team_leader') }}: <strong>{{ $employee->manager->name }}</strong>
                            </span>
                        @endif

                        @if($employee->collection_zone)
                            <span class="profile-tag">
                                <i class="bi bi-geo-alt"></i> {{ $employee->collection_zone }}
                            </span>
                        @endif

                        @if($employee->voip_extension)
                            <span class="profile-tag" style="font-family: monospace;">
                                <i class="bi bi-telephone"></i> Ext: {{ $employee->voip_extension }}
                                @if(!empty($voipInfo['in_call']))
                                    <span class="badge incall" style="margin-inline-start:4px;">In Call</span>
                                @elseif(!empty($voipInfo['online']))
                                    <span class="badge online" style="margin-inline-start:4px;">Online</span>
                                @else
                                    <span class="badge offline" style="margin-inline-start:4px;">Offline</span>
                                @endif
                            </span>
                        @endif

                        @foreach($employee->groups as $grp)
                            <span class="profile-tag" style="color: var(--dark); font-weight: 800;">
                                <i class="bi bi-shield"></i> {{ $grp->name }}
                            </span>
                        @endforeach

                        <span class="badge {{ $employee->is_active ? 'active' : 'inactive' }}">
                            {{ $employee->is_active ? __('crm.active') : __('crm.inactive') }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Period Filter -->
        <section class="filter-card">
            <form class="filter-form-grid" method="GET" action="{{ route('v2.reports.employees.show', $employee) }}">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="filter-field">
                    <label for="periodSelect"><i class="bi bi-clock-history"></i> {{ __('crm.report_period') }}</label>
                    <select id="periodSelect" name="period" class="filter-control">
                        <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
                        <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
                        <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
                        <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
                        <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
                        <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="fromDate"><i class="bi bi-calendar-event"></i> {{ __('crm.from_date') }}</label>
                    <input id="fromDate" name="from" type="date" value="{{ $filters['from'] }}" class="filter-control">
                </div>

                <div class="filter-field">
                    <label for="toDate"><i class="bi bi-calendar-event"></i> {{ __('crm.to_date') }}</label>
                    <input id="toDate" name="to" type="date" value="{{ $filters['to'] }}" class="filter-control">
                </div>

                <button class="btn primary" type="submit" style="height: 40px;">
                    <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                </button>
            </form>
        </section>

        <!-- KPI Metrics Strip -->
        <section class="metrics-strip">
            <div class="metric-card">
                <span>{{ __('crm.managed_leads') }}</span>
                <strong>{{ number_format($kpis['assigned_leads']) }}</strong>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.followups_logged') }}</span>
                <strong style="color: #0284c7;">{{ number_format($kpis['total_followups']) }}</strong>
                <small style="font-size: 11px; color: var(--muted);">📞 {{ $kpis['calls_count'] }} | 🤝 {{ $kpis['meetings_count'] }} | 💬 {{ $kpis['whatsapp_count'] }}</small>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.converted_donors') }}</span>
                <strong style="color: #16a34a;">{{ number_format($kpis['converted_donors']) }}</strong>
                <small style="font-size: 11px; color: var(--muted);">{{ $kpis['conversion_rate'] }}% {{ __('crm.conversion_rate') ?? 'نسبة التحويل' }}</small>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.donations_amount') }}</span>
                <strong style="color: #16a34a;">{{ number_format($kpis['donations_amount'], 2) }}</strong>
                <small style="font-size: 11px; color: var(--muted);">({{ $kpis['donations_count'] }} {{ __('crm.donations_count') ?? 'تبرع' }})</small>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.collections_amount') }}</span>
                <strong style="color: #8b5cf6;">{{ number_format($kpis['collected_amount'], 2) }}</strong>
                <small style="font-size: 11px; color: var(--muted);">({{ $kpis['completed_cases'] }} {{ __('crm.cases') ?? 'حالة' }})</small>
            </div>
            <div class="metric-card">
                <span>{{ __('crm.total_generated_value') }}</span>
                <strong style="color: var(--red);">{{ number_format($kpis['total_generated_value'], 2) }} <small style="font-size: 11px;">{{ __('crm.currency_egp') }}</small></strong>
            </div>
        </section>

        <!-- Visual Charts Grid -->
        <section class="analytics-grid">
            <div class="analytics-card">
                <h2><i class="bi bi-graph-up" style="color: #0284c7;"></i> {{ __('crm.activity_trend') ?? 'نشاط الموظف اليومي (آخر 7 أيام)' }}</h2>
                <div style="position: relative; height: 240px;">
                    <canvas id="employeeTimelineChart"></canvas>
                </div>
            </div>

            <div class="analytics-card">
                <h2><i class="bi bi-pie-chart-fill" style="color: #8b5cf6;"></i> {{ __('crm.staff_activity_mix') }}</h2>
                <div style="position: relative; height: 240px;">
                    <canvas id="employeeChannelsChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Detailed Tabs Section -->
        <section class="tabs-card">
            <div class="nav-tabs">
                <a href="{{ route('v2.reports.employees.show', [$employee, 'tab' => 'overview', 'period' => $filters['period'], 'from' => $filters['from'], 'to' => $filters['to']]) }}"
                   class="nav-tab-btn {{ $activeTab === 'overview' || $activeTab === 'leads' ? 'active' : '' }}">
                    <i class="bi bi-people"></i> {{ __('crm.assigned_leads') ?? 'العملاء المسندون' }} ({{ $assignedLeads->total() }})
                </a>
                <a href="{{ route('v2.reports.employees.show', [$employee, 'tab' => 'followups', 'period' => $filters['period'], 'from' => $filters['from'], 'to' => $filters['to']]) }}"
                   class="nav-tab-btn {{ $activeTab === 'followups' ? 'active' : '' }}">
                    <i class="bi bi-list-task"></i> {{ __('crm.followups_logged') }} ({{ $recentFollowups->total() }})
                </a>
                <a href="{{ route('v2.reports.employees.show', [$employee, 'tab' => 'donations', 'period' => $filters['period'], 'from' => $filters['from'], 'to' => $filters['to']]) }}"
                   class="nav-tab-btn {{ $activeTab === 'donations' ? 'active' : '' }}">
                    <i class="bi bi-heart"></i> {{ __('crm.donations_amount') }} ({{ $recentDonations->total() }})
                </a>
                <a href="{{ route('v2.reports.employees.show', [$employee, 'tab' => 'collections', 'period' => $filters['period'], 'from' => $filters['from'], 'to' => $filters['to']]) }}"
                   class="nav-tab-btn {{ $activeTab === 'collections' ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i> {{ __('crm.collections_amount') }} ({{ $recentCollections->total() }})
                </a>
            </div>

            @if($activeTab === 'overview' || $activeTab === 'leads')
                <!-- Leads Table -->
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.lead_name') }}</th>
                                <th>{{ __('crm.phone') }}</th>
                                <th>{{ __('crm.stage') }}</th>
                                <th>{{ __('crm.donation_value') }}</th>
                                <th>{{ __('crm.created_at') }}</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignedLeads as $lead)
                                <tr>
                                    <td>
                                        <strong>{{ $lead->name }}</strong>
                                        @if($lead->company_name)
                                            <small style="display: block; color: var(--muted);">{{ $lead->company_name }}</small>
                                        @endif
                                    </td>
                                    <td dir="ltr" style="font-weight: 700;">{{ $lead->phone }}</td>
                                    <td>
                                        <span class="badge" style="background: {{ $lead->status?->stage?->color ?: '#3478f6' }}18; color: {{ $lead->status?->stage?->color ?: '#3478f6' }};">
                                            {{ $lead->status?->stage?->name_ar ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($lead->donation_value)
                                            <strong>{{ number_format((float)$lead->donation_value, 2) }}</strong> <small>{{ __('crm.currency_egp') }}</small>
                                        @else
                                            <span style="color: var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $lead->created_at?->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('v2.leads.show', $lead) }}" class="btn-action" title="{{ __('crm.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: var(--muted);">
                                        {{ __('crm.no_leads_data') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($assignedLeads->hasPages())
                    <div style="margin-top: 16px;">{{ $assignedLeads->links() }}</div>
                @endif
            @elseif($activeTab === 'followups')
                <!-- Followups Table -->
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.date') }}</th>
                                <th>{{ __('crm.lead_name') }}</th>
                                <th>{{ __('crm.channel') ?? 'نوع التواصل' }}</th>
                                <th>{{ __('crm.outcome_notes') ?? 'النتيجة والملاحظات' }}</th>
                                <th>{{ __('crm.next_followup') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentFollowups as $fu)
                                <tr>
                                    <td style="white-space: nowrap;">{{ $fu->followed_up_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($fu->lead)
                                            <a href="{{ route('v2.leads.show', $fu->lead) }}" style="color: inherit; text-decoration: none;">
                                                <strong>{{ $fu->lead->name }}</strong>
                                            </a>
                                        @else
                                            <span style="color: var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fu->communication_type === 'call')
                                            <span class="badge" style="background:#e0f2fe; color:#0369a1;"><i class="bi bi-telephone"></i> Call</span>
                                        @elseif($fu->communication_type === 'meeting')
                                            <span class="badge" style="background:#f3e8ff; color:#7e22ce;"><i class="bi bi-people"></i> Meeting</span>
                                        @elseif($fu->communication_type === 'whatsapp')
                                            <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="bi bi-chat-dots"></i> WhatsApp</span>
                                        @else
                                            <span class="badge" style="background:#f1f5f9; color:#475569;">{{ $fu->communication_type }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $fu->outcome }}</td>
                                    <td>
                                        @if($fu->next_follow_up_at)
                                            <span class="badge" style="background: #f8fafc; border: 1px solid var(--line);">
                                                {{ $fu->next_follow_up_at->format('Y-m-d H:i') }}
                                            </span>
                                        @else
                                            <span style="color: var(--muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--muted);">
                                        {{ __('crm.no_timeline_records_yet') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentFollowups->hasPages())
                    <div style="margin-top: 16px;">{{ $recentFollowups->links() }}</div>
                @endif
            @elseif($activeTab === 'donations')
                <!-- Donations Table -->
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.date') }}</th>
                                <th>{{ __('crm.donor_name') ?? 'المتبرع' }}</th>
                                <th>{{ __('crm.donation_type') }}</th>
                                <th>{{ __('crm.donation_cycle') }}</th>
                                <th>{{ __('crm.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDonations as $don)
                                <tr>
                                    <td>{{ $don->donated_at?->format('Y-m-d') }}</td>
                                    <td>
                                        @if($don->lead)
                                            <a href="{{ route('v2.leads.show', $don->lead) }}" style="color: inherit; text-decoration: none;">
                                                <strong>{{ $don->lead->name }}</strong>
                                            </a>
                                        @else
                                            <span style="color: var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $don->donation_type }}</td>
                                    <td>{{ __('crm.donation_cycle_' . $don->cycle) }}</td>
                                    <td>
                                        <strong style="color: #16a34a;">{{ number_format((float)$don->amount, 2) }}</strong> <small>{{ __('crm.currency_egp') }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--muted);">
                                        {{ __('crm.no_donations_recorded') ?? 'لا توجد تبرعات مسجلة في هذا النطاق.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentDonations->hasPages())
                    <div style="margin-top: 16px;">{{ $recentDonations->links() }}</div>
                @endif
            @elseif($activeTab === 'collections')
                <!-- Collections Table -->
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.due_date') ?? 'موعد التحصيل' }}</th>
                                <th>{{ __('crm.donor_name') ?? 'المتبرع' }}</th>
                                <th>{{ __('crm.amount') }}</th>
                                <th>{{ __('crm.status') }}</th>
                                <th>{{ __('crm.address') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCollections as $col)
                                <tr>
                                    <td>{{ $col->due_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($col->lead)
                                            <a href="{{ route('v2.leads.show', $col->lead) }}" style="color: inherit; text-decoration: none;">
                                                <strong>{{ $col->lead->name }}</strong>
                                            </a>
                                        @else
                                            <span style="color: var(--muted);">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format((float)$col->expected_amount, 2) }}</strong> <small>{{ __('crm.currency_egp') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $col->status === 'completed' ? 'active' : '' }}">
                                            {{ $col->status }}
                                        </span>
                                    </td>
                                    <td>{{ $col->collection_address }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: var(--muted);">
                                        {{ __('crm.no_collection_cases') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentCollections->hasPages())
                    <div style="margin-top: 16px;">{{ $recentCollections->links() }}</div>
                @endif
            @endif
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.classList.contains('dark-mode');
        const textColor = isDark ? '#f4f4f5' : '#182033';
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

        // 1. Timeline Chart
        const timelineCanvas = document.getElementById('employeeTimelineChart');
        if (timelineCanvas) {
            const chartData = @json($timelineChart);
            new Chart(timelineCanvas, {
                type: 'line',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: @json(__('crm.followups_logged')),
                        data: chartData.data || [],
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2,132,199,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, color: textColor }, grid: { color: gridColor } },
                        x: { ticks: { color: textColor, font: { family: 'Tajawal', weight: 'bold' } }, grid: { display: false } }
                    }
                }
            });
        }

        // 2. Channels Chart
        const channelsCanvas = document.getElementById('employeeChannelsChart');
        if (channelsCanvas) {
            const channels = @json($channelsChart);
            new Chart(channelsCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Calls', 'Meetings', 'WhatsApp'],
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
                        legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Tajawal', weight: 'bold' } } }
                    }
                }
            });
        }
    });
</script>
<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

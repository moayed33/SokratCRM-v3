<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('crm.voip_team_report') }} — SokratCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
    <style>
        :root {
            --red: #dc2637;
            --ink: #172033;
            --dark: #172033;
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
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .topbar h1 { margin: 0; font-size: 24px; font-weight: 900; color: var(--dark); }
        .topbar p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-inline-start: auto; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 0 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); color: var(--dark); font-weight: 800; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 13px; transition: all .15s ease; }
        .topbar .btn, .top-actions .btn { height: 42px; min-height: 42px; box-sizing: border-box; }
        .btn:hover { background: var(--bg); transform: translateY(-1px); }
        .btn.primary { background: var(--red); border-color: var(--red); color: #fff; }
        .btn.primary:hover { background: #b81829; border-color: #b81829; color: #fff; }
        .btn.soft { background: var(--bg); border-color: var(--line); color: var(--dark); }

        .panel { background: transparent !important; border: 0 !important; border-radius: 16px; margin-bottom: 24px; box-shadow: none !important; }
        .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; background: transparent !important; border: 0 !important; }
        .panel-head h2 { margin: 0; font-size: 18px; font-weight: 900; color: var(--dark); display: flex; align-items: center; gap: 8px; }

        /* Filter Form */
        .filter-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 16px; }
        .filter-card-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding-bottom: 12px; border-bottom: 1px solid var(--line); }
        .filter-card-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 900; color: var(--dark); }
        .filter-card-title i { color: var(--red); font-size: 15px; }
        .filter-presets { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .preset-pill { padding: 5px 12px; border-radius: 20px; border: 1px solid var(--line); background: var(--bg); color: var(--muted); font-size: 11px; font-weight: 800; cursor: pointer; font-family: inherit; transition: all .15s ease; }
        .preset-pill:hover { background: var(--card); border-color: var(--red); color: var(--red); }
        .filter-form-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) auto; gap: 16px; align-items: flex-end; }
        .filter-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .filter-field label { font-size: 12px; font-weight: 800; color: var(--muted); display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .filter-field label i { font-size: 13px; opacity: 0.85; }
        .filter-control { width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); color: var(--dark); font: inherit; font-size: 13px; outline: none; transition: border-color .15s, box-shadow .15s; }
        .filter-control:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(220,38,55,0.12); }
        .filter-actions { display: flex; align-items: center; gap: 8px; height: 42px; }
        .filter-actions .btn { height: 42px; min-height: 42px; }
        .filter-actions .btn.primary { padding: 0 18px; }
        /* Metrics Summary Strip */
        .metrics-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .metric-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; display: flex; flex-direction: column; gap: 4px; box-shadow: 0 4px 14px rgba(0,0,0,0.03); }
        .metric-card span { color: var(--muted); font-size: 12px; font-weight: 700; }
        .metric-card strong { font-size: 24px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }

        /* Two-Column Analytics Grid */
        .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .analytics-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        .analytics-card h2 { margin: 0 0 16px; font-size: 16px; font-weight: 900; color: var(--dark); display: flex; align-items: center; gap: 8px; }

        /* Ranking rows */
        .rank-list { display: flex; flex-direction: column; gap: 12px; }
        .rank-row { display: grid; grid-template-columns: 150px 1fr 60px; gap: 12px; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
        .rank-row:last-child { border-bottom: 0; }
        .rank-row strong { font-weight: 800; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rank-row small { display: block; color: var(--muted); font-size: 11px; }
        .rank-track { height: 8px; background: var(--line); border-radius: 999px; overflow: hidden; }
        .rank-fill { display: block; height: 100%; background: var(--red); border-radius: inherit; transition: width .3s ease; }
        .rank-value { font-weight: 900; color: var(--dark); text-align: end; font-variant-numeric: tabular-nums; }

        /* Table view */
        .table-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); overflow: hidden; }
        .table-wrap { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 13px 14px; text-align: start; font-size: 13px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { color: var(--muted); font-weight: 800; font-size: 12px; background: var(--bg); }
        tr:last-child td { border-bottom: 0; }
        td strong { font-weight: 800; color: var(--dark); }

        .banner-box { padding: 16px 20px; border-radius: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .banner-box.error { background: rgba(220,38,55,0.08); border: 1px solid rgba(220,38,55,0.25); color: var(--dark); }
        .banner-box.notice { background: rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.25); color: var(--dark); }

        /* Dark Mode */
        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f4f4f5;
            --muted: #a1a1aa;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f4f4f5 !important; }
        html.dark-mode .filter-card,
        html.dark-mode .metric-card,
        html.dark-mode .analytics-card,
        html.dark-mode .table-card {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #f4f4f5 !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4) !important;
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
        html.dark-mode td strong {
            color: #f4f4f5 !important;
        }
        html.dark-mode .rank-row strong {
            color: #f4f4f5 !important;
        }
        html.dark-mode .rank-value {
            color: #f4f4f5 !important;
        }
        html.dark-mode .rank-track {
            background: rgba(255,255,255,0.1) !important;
        }
        html.dark-mode .btn {
            background: #27272a !important;
            border-color: rgba(255,255,255,0.12) !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode .btn.primary {
            background: var(--red) !important;
            border-color: var(--red) !important;
            color: #fff !important;
        }

        /* Monochrome Mode */
        html.crm-monochrome:not(.dark-mode) .metric-card,
        html.crm-monochrome:not(.dark-mode) .analytics-card,
        html.crm-monochrome:not(.dark-mode) .table-card {
            background: #fff !important;
            border-color: #e5e5e5 !important;
            box-shadow: none !important;
        }
        html.crm-monochrome:not(.dark-mode) .rank-fill {
            background: #171717 !important;
        }
        html.crm-monochrome.dark-mode .metric-card,
        html.crm-monochrome.dark-mode .analytics-card,
        html.crm-monochrome.dark-mode .table-card {
            background: #1c1c1c !important;
            border-color: #333 !important;
            box-shadow: none !important;
        }
        html.crm-monochrome.dark-mode .rank-fill {
            background: #f5f5f5 !important;
        }

        @media(max-width: 1100px) {
            .analytics-grid { grid-template-columns: 1fr; }
            .filter-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-actions { grid-column: 1 / -1; justify-content: flex-end; }
        }
        @media(max-width: 768px) {
            .crm-main { padding: 16px; }
            .filter-form-grid { grid-template-columns: 1fr; }
            .filter-actions { grid-column: 1 / -1; flex-direction: column; }
            .filter-actions .btn { width: 100%; }
            .filter-presets { width: 100%; justify-content: flex-start; }
            .rank-row { grid-template-columns: 120px 1fr 50px; }
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
                    <h1>{{ __('crm.voip_team_report') }}</h1>
                    <p>{{ __('crm.voip_team_report_hint') }}</p>
                </div>
            </div>
            <div class="top-actions">
                @can('voip.live_panel')
                    <a href="{{ route('v2.voip.live') }}" class="btn soft">
                        <i class="bi bi-broadcast"></i>
                        {{ __('crm.live_panel') ?? 'المراقبة المباشرة' }}
                    </a>
                @endcan
                @can('voip.settings')
                    <a href="{{ route('v2.settings.voip') }}" class="btn soft">
                        <i class="bi bi-gear-fill"></i>
                        {{ __('crm.voip_settings') ?? 'إعدادات السنترال' }}
                    </a>
                @endcan
                @include('partials.profile-dropdown')
            </div>
        </header>

        {{-- Filter Panel --}}
        <section class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <i class="bi bi-funnel"></i>
                    <span>{{ __('crm.filter_campaign_report') ?? 'تصفية التقرير' }}</span>
                </div>
                <div class="filter-presets" role="group" aria-label="Date presets">
                    <button type="button" class="preset-pill" data-range="today">{{ __('crm.today') }}</button>
                    <button type="button" class="preset-pill" data-range="week">{{ __('crm.this_week') }}</button>
                    <button type="button" class="preset-pill" data-range="month">{{ __('crm.this_month') }}</button>
                    <button type="button" class="preset-pill" data-range="30days">30 {{ __('crm.days') ?? 'يوم' }}</button>
                </div>
            </div>

            <form class="filter-form-grid" method="GET" action="{{ route('v2.reports.voip') }}" id="voipReportFilterForm">
                <div class="filter-field">
                    <label for="fromDate"><i class="bi bi-calendar-event"></i> {{ __('crm.from_date') }}</label>
                    <input id="fromDate" name="from_date" type="date" value="{{ $filters['from_date'] ?? '' }}" class="filter-control">
                </div>
                <div class="filter-field">
                    <label for="toDate"><i class="bi bi-calendar-event"></i> {{ __('crm.to_date') }}</label>
                    <input id="toDate" name="to_date" type="date" value="{{ $filters['to_date'] ?? '' }}" class="filter-control">
                </div>
                <div class="filter-field">
                    <label for="directionSelect"><i class="bi bi-telephone-inbound"></i> {{ __('crm.direction') }}</label>
                    <select id="directionSelect" name="direction" class="filter-control">
                        <option value="">{{ __('crm.all_directions') }}</option>
                        <option value="incoming" @selected(($filters['direction'] ?? '') === 'incoming')>{{ __('crm.incoming') }} ↙</option>
                        <option value="outgoing" @selected(($filters['direction'] ?? '') === 'outgoing')>{{ __('crm.outgoing') }} ↗</option>
                        <option value="internal" @selected(($filters['direction'] ?? '') === 'internal')>{{ __('crm.internal') }} ↔</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="statusSelect"><i class="bi bi-check2-circle"></i> {{ __('crm.call_status') }}</label>
                    <select id="statusSelect" name="status" class="filter-control">
                        <option value="">{{ __('crm.all_call_statuses') }}</option>
                        @foreach (['answered', 'missed', 'failed', 'busy', 'no_answer'] as $stKey)
                            <option value="{{ $stKey }}" @selected(($filters['status'] ?? '') === $stKey)>{{ __('crm.status_'.$stKey) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn primary" type="submit">
                        <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                    </button>
                    @if(!empty($filters))
                        <a href="{{ route('v2.reports.voip') }}" class="btn soft" title="{{ __('crm.reset_filters') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        {{-- Disconnected Notice or Error Alert --}}
        @if(empty($isConfigured) || empty($isConnected))
            <div class="banner-box error">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="bi bi-telephone-x-fill" style="font-size: 24px; color: var(--red);"></i>
                    <div>
                        <strong style="display: block; font-size: 14px;">{{ app()->getLocale() === 'en' ? 'VoIP PBX Server Disconnected' : 'سيرفر السنترال غير متصل' }}</strong>
                        <small style="color: var(--muted); font-size: 12px;">{{ app()->getLocale() === 'en' ? 'Please pair with your PBX server in Settings to view live call performance reports.' : 'يرجى ربط السنترال في صفحة الإعدادات لعرض تقارير أداء المكالمات.' }}</small>
                    </div>
                </div>
                @can('voip.settings')
                    <a href="{{ route('v2.settings.voip') }}" class="btn primary">
                        <i class="bi bi-gear-fill"></i>
                        {{ app()->getLocale() === 'en' ? 'VoIP Settings' : 'إعدادات السنترال' }}
                    </a>
                @endcan
            </div>
        @elseif(!empty($error))
            <div class="banner-box error">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 22px; color: var(--red);"></i>
                    <span>{{ $error }}</span>
                </div>
                @can('voip.settings')
                    <a href="{{ route('v2.settings.voip') }}" class="btn soft">
                        <i class="bi bi-gear-fill"></i>
                        {{ __('crm.voip_settings') ?? 'الإعدادات' }}
                    </a>
                @endcan
            </div>
        @endif

        {{-- Main Report Content --}}
        @if($report)
            @php
                $s = $report['summary'];
                $usersList = $report['users'] ?? [];
                $maxCalls = max(1, ...array_column($usersList ?: [['total_calls' => 1]], 'total_calls'));
            @endphp

            {{-- 5-Metric Strip --}}
            <section class="metrics-strip">
                <div class="metric-card">
                    <span>{{ __('crm.team_extensions') }}</span>
                    <strong>{{ count($usersList) }}</strong>
                </div>
                <div class="metric-card">
                    <span>{{ __('crm.total_calls') }}</span>
                    <strong>{{ number_format((int) ($s['total_calls'] ?? 0)) }}</strong>
                </div>
                <div class="metric-card">
                    <span>{{ __('crm.answered_calls') }}</span>
                    <strong style="color:#16a34a">{{ number_format((int) ($s['answered_calls'] ?? 0)) }}</strong>
                </div>
                <div class="metric-card">
                    <span>{{ __('crm.answer_rate') }}</span>
                    <strong style="color:var(--red)">{{ number_format((float) ($s['answer_rate_percent'] ?? 0), 1) }}%</strong>
                </div>
                <div class="metric-card">
                    <span>{{ __('crm.total_talk_time') }}</span>
                    <strong>{{ floor(((int) ($s['total_talk_seconds'] ?? 0)) / 60) }} {{ __('crm.minutes_short') }}</strong>
                </div>
            </section>

            {{-- 2-Column Analytics Grid --}}
            <div class="analytics-grid">
                {{-- Ranking List --}}
                <section class="analytics-card">
                    <h2><i class="bi bi-bar-chart"></i> {{ __('crm.employee_call_comparison') }}</h2>
                    <div class="rank-list">
                        @forelse($usersList as $row)
                            <div class="rank-row">
                                <div>
                                    <strong>{{ $row['user']->name }}</strong>
                                    <small>{{ __('crm.extension_short') }}: {{ $row['extension'] }}</small>
                                </div>
                                <div class="rank-track">
                                    <span class="rank-fill" style="width: {{ ($row['total_calls'] / $maxCalls) * 100 }}%"></span>
                                </div>
                                <span class="rank-value">{{ number_format($row['total_calls']) }}</span>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 24px 0; color: var(--muted);">
                                {{ __('crm.no_extension_statistics') }}
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Chart --}}
                <section class="analytics-card">
                    <h2><i class="bi bi-graph-up"></i> {{ __('crm.calls_vs_answered') }}</h2>
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="teamCallsChart"></canvas>
                    </div>
                </section>
            </div>

            {{-- Performance Details Table --}}
            <section class="table-card">
                <h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 900; color: var(--dark); display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-table"></i> {{ __('crm.employee_performance_details') }}
                </h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.employee') }}</th>
                                <th>{{ __('crm.extension_short') }}</th>
                                <th>{{ __('crm.total_calls') }}</th>
                                <th>{{ __('crm.answered_calls') }}</th>
                                <th>{{ __('crm.missed_calls') }}</th>
                                <th>{{ __('crm.answer_rate') }}</th>
                                <th>{{ __('crm.total_talk_time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usersList as $row)
                                <tr>
                                    <td>
                                        @can('users.update')
                                            <a href="{{ route('v2.settings.users.edit', $row['user']) }}">
                                                <strong>{{ $row['user']->name }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $row['user']->name }}</strong>
                                        @endcan
                                    </td>
                                    <td><span dir="ltr">{{ $row['extension'] }}</span></td>
                                    <td><strong>{{ number_format($row['total_calls']) }}</strong></td>
                                    <td style="color:#16a34a"><strong>{{ number_format($row['answered_calls']) }}</strong></td>
                                    <td style="color:#dc2637"><strong>{{ number_format($row['missed_calls']) }}</strong></td>
                                    <td><strong>{{ number_format($row['answer_rate_percent'], 1) }}%</strong></td>
                                    <td><strong>{{ floor($row['total_talk_seconds'] / 60) }} {{ __('crm.minutes_short') }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">
                                        {{ __('crm.no_extension_statistics') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
            <script>
                (() => {
                    const data = @json($report['charts']['employees'] ?? []);
                    const canvas = document.getElementById('teamCallsChart');
                    if (!canvas || !window.Chart || !data.labels || data.labels.length === 0) return;

                    const isDark = document.documentElement.classList.contains('dark-mode');
                    const textColor = isDark ? '#a1a1aa' : '#64748b';
                    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : '#f1f5f9';

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: @json(__('crm.total_calls')),
                                    data: data.calls || [],
                                    backgroundColor: '#dc2637',
                                    borderRadius: 6,
                                },
                                {
                                    label: @json(__('crm.answered_calls')),
                                    data: data.answered || [],
                                    backgroundColor: '#16a34a',
                                    borderRadius: 6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: textColor, font: { family: 'Tajawal', weight: 'bold' } }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, color: textColor },
                                    grid: { color: gridColor }
                                },
                                x: {
                                    ticks: { color: textColor, font: { family: 'Tajawal', weight: 'bold' } },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                })();
            </script>
        @else
            <section class="table-card" style="text-align: center; padding: 40px 20px;">
                <i class="bi bi-bar-chart-line" style="font-size: 40px; color: var(--muted); display: inline-block; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px; color: var(--dark);">{{ __('crm.no_extension_statistics') }}</h3>
                <p style="color: var(--muted); margin: 0;">{{ __('crm.no_voip_data_range_desc') ?? 'لا توجد بيانات مكالمات مسجلة ضمن النطاق الزمني أو معايير التصفية المحددة.' }}</p>
            </section>
        @endif
    </main>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.preset-pill').forEach(function(pill) {
            pill.addEventListener('click', function() {
                var range = this.getAttribute('data-range');
                var fromInput = document.getElementById('fromDate');
                var toInput = document.getElementById('toDate');
                var now = new Date();
                var pad = function(num) { return String(num).padStart(2, '0'); };
                var format = function(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

                var toStr = format(now);
                var fromDate = new Date();

                if (range === 'today') {
                    fromDate = new Date();
                } else if (range === 'week') {
                    fromDate.setDate(now.getDate() - 7);
                } else if (range === 'month') {
                    fromDate = new Date(now.getFullYear(), now.getMonth(), 1);
                } else if (range === '30days') {
                    fromDate.setDate(now.getDate() - 30);
                }

                fromInput.value = format(fromDate);
                toInput.value = toStr;
                document.getElementById('voipReportFilterForm').submit();
            });
        });
    });
</script>
<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

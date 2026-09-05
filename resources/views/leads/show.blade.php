<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $lead->name }} — {{ __('crm.lead_details') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v5">
    <style>
        *{box-sizing:border-box}
        :root{--red:#dc2637;--dark:#182033;--muted:#7e899b;--line:#e4e8ef;--bg:#f4f6f9;--card:#fff;--blue:#3478f6}
        body{margin:0;background:var(--bg);color:var(--dark);font-family:var(--font-primary)}
        .crm-app{min-height:100vh;display:flex}
        .crm-main{min-width:0;flex:1;padding:24px 30px 60px}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap}
        .topbar-left{display:flex;align-items:center;gap:14px}
        .topbar h1{margin:0;font-size:24px;font-weight:900;color:var(--dark)}
        .topbar p{margin:4px 0 0;color:var(--muted);font-size:13px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--dark);font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:13px}
        .topbar .btn{height:42px;min-height:42px;box-sizing:border-box}
        .topbar-left .btn.small,.topbar .btn.small{width:42px;height:42px;min-height:42px;padding:0;display:inline-grid;place-items:center;border-radius:10px}
        .btn.primary{background:var(--red);border-color:var(--red);color:#fff}
        .btn.soft{background:var(--card);color:var(--dark)}
        .btn.small{min-height:32px;padding:0 10px;font-size:12px}
        .btn.success{background:#16a34a;border-color:#16a34a;color:#fff}
        .badge{display:inline-flex;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:900;background:#eef2f7;color:#536078}
        .badge.active{background:#e7f8ed;color:#18733a}
        .badge.inactive{background:#fff0f1;color:#b42332}
        .badge.system{background:#fff5d9;color:#8a6100}

        /* Lead Profile Header Card */
        .lead-header-card{background:transparent !important;border:0 !important;border-radius:16px;padding:12px 0 20px;margin-bottom:12px;box-shadow:none !important}
        .lead-header-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
        .lead-identity{display:flex;align-items:center;gap:16px}
        .lead-avatar{width:56px;height:56px;border-radius:16px;background:#fef2f2;color:var(--red);display:grid;place-items:center;font-size:24px;font-weight:900}
        .lead-names h2{margin:0;font-size:20px;font-weight:900;color:var(--dark)}
        .lead-names p{margin:4px 0 0;color:var(--muted);font-size:13px;display:flex;align-items:center;gap:8px}

        /* Enhanced Lead Stage Indicator Card */
        .lead-stage-card {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 8px 18px 8px 10px;
            background: color-mix(in srgb, var(--stage-color, #3478f6) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--stage-color, #3478f6) 28%, var(--line, #e2e8f0));
            border-radius: 999px;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--stage-color, #3478f6) 12%, transparent);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .lead-stage-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px color-mix(in srgb, var(--stage-color, #3478f6) 22%, transparent);
            border-color: var(--stage-color, #3478f6);
        }
        .stage-icon-halo {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--stage-color, #3478f6), color-mix(in srgb, var(--stage-color, #3478f6) 80%, #000));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--stage-color, #3478f6) 45%, transparent);
        }
        .stage-pulse {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 2px solid var(--stage-color, #3478f6);
            opacity: 0.4;
            animation: stagePulseAnim 2.4s infinite ease-out;
        }
        @keyframes stagePulseAnim {
            0% { transform: scale(0.92); opacity: 0.6; }
            50% { transform: scale(1.18); opacity: 0.08; }
            100% { transform: scale(0.92); opacity: 0.6; }
        }
        .stage-text-group {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
            text-align: start;
        }
        .stage-caption {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted, #64748b);
        }
        .stage-title {
            font-size: 14px;
            font-weight: 900;
            color: var(--dark, #0f172a);
        }
        html.dark-mode .lead-stage-card {
            background: color-mix(in srgb, var(--stage-color, #3478f6) 14%, rgba(255,255,255,0.02));
            border-color: color-mix(in srgb, var(--stage-color, #3478f6) 38%, rgba(255,255,255,0.12));
            box-shadow: 0 4px 18px rgba(0,0,0,0.35);
        }
        html.dark-mode .stage-title {
            color: #f8fafc;
        }
        html.dark-mode .stage-caption {
            color: #94a3b8;
        }
        html.crm-monochrome .lead-stage-card {
            --stage-color: #262626 !important;
            background: #f5f5f5 !important;
            border-color: #d4d4d4 !important;
            box-shadow: none !important;
        }
        html.crm-monochrome.dark-mode .lead-stage-card {
            --stage-color: #e5e5e5 !important;
            background: #1f1f1f !important;
            border-color: #404040 !important;
            box-shadow: none !important;
        }
        html.crm-monochrome.dark-mode .stage-icon-halo {
            background: #e5e5e5 !important;
            color: #171717 !important;
        }
        .metrics-bar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:20px;padding-top:20px;border-top:1px solid var(--line)}
        .metric-box{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:12px 14px}
        .metric-box span{display:block;color:var(--muted);font-size:11px;font-weight:800;margin-bottom:4px}
        .metric-box b{display:block;font-size:15px;font-weight:900;color:var(--dark)}

        /* Two Columns Layout */
        .details-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:20px}
        .panel{background:transparent !important;border:0 !important;border-radius:16px;padding:16px 0;margin-bottom:20px;box-shadow:none !important}
        .panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;background:transparent !important;border:0 !important}
        .panel-head h2{margin:0;font-size:16px;font-weight:900;color:var(--dark);display:flex;align-items:center;gap:8px}

        .info-list{display:grid;gap:10px}
        .info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--bg);border:1px solid var(--line);border-radius:10px;font-size:13px}
        .info-row.is-primary-phone{background:rgba(22,163,74,0.08);border:1px solid rgba(22,163,74,0.25)}
        .info-label{color:var(--muted);font-weight:700}
        .info-value{font-weight:800;color:var(--dark);text-align:end}
        /* Timeline Styles */
        .timeline{position:relative;padding-inline-start:24px;margin-top:10px}
        .timeline::before{
            content:'';position:absolute;top:0;bottom:0;inset-inline-start:7px;
            width:2px;background:var(--line);
        }
        .timeline-item{position:relative;margin-bottom:20px}
        .timeline-dot{
            position:absolute;inset-inline-start:-24px;top:4px;
            width:16px;height:16px;border-radius:50%;background:var(--red);border:3px solid #fff;
            box-shadow:0 0 0 2px var(--line);
        }
        .timeline-card{
            background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:14px 16px;
        }
        .timeline-head{
            display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;
        }
        .timeline-date{color:var(--muted);font-size:12px;font-weight:700}
        .timeline-employee{font-weight:800;color:var(--dark);font-size:13px}
        .timeline-body{color:#334155;font-size:13px;line-height:1.6}
        .donation-history{display:grid;gap:12px}.donation-entry{padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--bg)}.donation-entry-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px}.donation-entry-head strong{font-size:15px;color:#15803d}.donation-entry-meta{display:flex;flex-wrap:wrap;gap:8px 14px;color:var(--muted);font-size:11px;font-weight:700}.donation-entry-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:11px;padding-top:10px;border-top:1px dashed var(--line)}
        .call-filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:10px;align-items:end;margin-bottom:16px}.call-filters label{display:block;color:var(--muted);font-size:11px;font-weight:800;margin-bottom:5px}.call-filters input,.call-filters select{width:100%;border:1px solid var(--line);border-radius:9px;padding:9px 10px;background:var(--card);color:var(--dark)}
        .call-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:16px}.call-metric{padding:12px;background:var(--bg);border-radius:12px}.call-metric span{display:block;color:var(--muted);font-size:11px;font-weight:800}.call-metric strong{display:block;margin-top:5px;font-size:20px;font-variant-numeric:tabular-nums}
        .call-bars{display:grid;gap:9px;padding:14px;background:var(--bg);border-radius:12px;margin-bottom:16px}.call-bar{display:grid;grid-template-columns:78px 1fr 34px;gap:8px;align-items:center;font-size:12px;font-weight:800}.call-bar-track{height:7px;background:var(--line);border-radius:999px;overflow:hidden}.call-bar-fill{display:block;height:100%;border-radius:inherit}
        .call-list{display:grid;gap:9px}.call-row{display:grid;grid-template-columns:minmax(120px,.9fr) minmax(120px,1fr) 90px 100px;gap:12px;align-items:center;padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--card);transition:border-color .15s}.call-row small{display:block;color:var(--muted);margin-top:3px}.call-state{text-align:center;padding:26px;color:var(--muted);font-weight:700}.call-state i{display:block;font-size:26px;margin-bottom:7px}
        .crm-audio-player{grid-column:1/-1;display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg);border:1px solid var(--line);border-radius:12px;margin-top:4px;transition:border-color .15s}
        .crm-audio-player.is-playing{border-color:var(--red)}
        .audio-play-btn{width:32px;height:32px;flex:0 0 32px;border-radius:50%;background:var(--red);color:#fff;border:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:transform .15s,background .15s;padding:0}
        .audio-play-btn:hover{background:#b81829;transform:scale(1.06)}
        .audio-track-wrap{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
        .audio-seek{width:100%;height:6px;-webkit-appearance:none;appearance:none;background:var(--line);border-radius:999px;outline:none;cursor:pointer;margin:0}
        .audio-seek::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:12px;height:12px;border-radius:50%;background:var(--red);cursor:pointer;transition:transform .1s}
        .audio-seek::-webkit-slider-thumb:hover{transform:scale(1.25)}
        .audio-seek::-moz-range-thumb{width:12px;height:12px;border-radius:50%;background:var(--red);border:0;cursor:pointer}
        .audio-time-row{display:flex;justify-content:space-between;font-size:10px;font-weight:700;color:var(--muted);font-variant-numeric:tabular-nums;font-family:monospace}
        .audio-speed-btn{min-width:32px;height:26px;padding:0 5px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--dark);font-size:11px;font-weight:800;cursor:pointer;transition:all .15s}
        .audio-speed-btn:hover{border-color:var(--red);color:var(--red)}
        .audio-download-btn{width:26px;height:26px;flex:0 0 26px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--muted);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:12px;transition:all .15s}
        .audio-download-btn:hover{border-color:var(--dark);color:var(--dark)}
        .call-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 4px 4px;margin-top:6px;border-top:1px solid var(--line);flex-wrap:wrap}
        .call-pagination-info{font-size:12px;font-weight:700;color:var(--muted)}
        .call-pagination-controls{display:flex;align-items:center;gap:6px}
        .btn-call-page{min-width:30px;height:30px;padding:0 8px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--dark);font-size:12px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s}
        .btn-call-page:hover:not(:disabled){background:var(--bg);border-color:var(--dark)}
        .btn-call-page.active{background:var(--red)!important;border-color:var(--red)!important;color:#fff!important}
        .btn-call-page:disabled{opacity:0.35;cursor:not-allowed}
        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f4f4f5;
            --muted: #a1a1aa;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f4f4f5 !important; }
        html.dark-mode .info-row { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .info-row.is-primary-phone { background: rgba(34,197,94,0.12) !important; border-color: rgba(34,197,94,0.3) !important; }
        html.dark-mode .info-row strong, html.dark-mode .info-value { color: #f4f4f5 !important; }
        html.dark-mode .metric-box { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .timeline-card { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .timeline-body { color: #d4d4d8; }
        html.dark-mode .donation-entry { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .btn.soft { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: #f4f4f5; }
        html.dark-mode .btn.soft:hover { background: rgba(255,255,255,0.12); color: #fff; }
        html.dark-mode .badge { background: rgba(255,255,255,0.08); color: #d4d4d8; }
        html.dark-mode .badge.active { background: rgba(34,197,94,0.18); color: #4ade80; }
        html.dark-mode .call-metric { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); }
        html.dark-mode .call-bars { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); }
        html.dark-mode .call-filters input, html.dark-mode .call-filters select { background: #18181b; border-color: rgba(255,255,255,0.12); color: #f4f4f5; }
        html.dark-mode .call-row { background: #18181b; border-color: rgba(255,255,255,0.08); }
        html.dark-mode .crm-audio-player { background: #27272a; border-color: rgba(255,255,255,0.1); }
        html.dark-mode .audio-speed-btn, html.dark-mode .audio-download-btn, html.dark-mode .btn-call-page { background: #18181b; border-color: rgba(255,255,255,0.12); color: #f4f4f5; }
        html.dark-mode .audio-seek { background: rgba(255,255,255,0.12); }

        @media(max-width:1024px){
            .details-grid{grid-template-columns:1fr}
            .metrics-bar{grid-template-columns:repeat(2,1fr)}
        }
        @media(max-width:768px){
            .crm-main{padding:16px}
            .metrics-bar{grid-template-columns:1fr}
            .call-filters,.call-metrics{grid-template-columns:1fr 1fr}.call-row{grid-template-columns:1fr 1fr}
        }
    </style>
</head>
<body>
<div class="crm-app">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        <header class="topbar">
            <div class="topbar-left">
                <a href="{{ route('v2.leads', $backQuery) }}" class="btn soft small" title="{{ __('crm.back_to_leads_list') }}">
                    <i class="bi bi-arrow-right rtl:rotate-180"></i>
                </a>
                <div>
                    <h1>{{ __('crm.lead_details') }}</h1>
                    <p><span>{{ __('crm.lead_code_label') }}: #{{ $lead->id }}</span> <span style="margin:0 6px">•</span> <span>{{ __('crm.registered_at') }}: {{ $lead->created_at->format('Y-m-d') }}</span></p>
                </div>
            </div>
            <div class="top-actions">
                @can('update', $lead)
                    <a href="{{ route('v2.leads.edit', $lead) }}" class="btn soft">
                        <i class="bi bi-pencil-square"></i> {{ __('crm.edit_data') }}
                    </a>
                @endcan
                @can('createFollowup', $lead)
                    <a href="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}" class="btn success" style="background:#16a34a;border-color:#16a34a;color:#fff;"
                       data-transition-popup="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}"
                       data-lead-name="{{ $lead->name }}">
                        <i class="bi bi-heart-fill"></i> {{ __('crm.record_donation') }}
                    </a>
                    <a href="{{ route('v2.leads.followups.index', $lead) }}" class="btn primary"
                       data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
                       data-lead-name="{{ $lead->name }}"
                       @if ($callPhone) data-voice-dial="{{ $callPhone }}" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name }}" @endif>
                        <i class="bi bi-telephone-outbound"></i> {{ __('crm.log_new_followup') }}
                    </a>
                @endcan
                @include('partials.profile-dropdown')
            </div>
        </header>

        @if (!empty($isCrossBranch))
            <div class="notice info" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 14px 18px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="bi bi-info-circle-fill" style="color: #2563eb; font-size: 22px;"></i>
                    <div>
                        <strong style="color: #1e40af; font-size: 14px; display: block;">{{ __('crm.cross_branch_lead_notice') ?? 'هذا العميل مسجل في فرع آخر' }}: {{ $lead->branch?->name_ar ?? $lead->branch?->name ?? 'فرع آخر' }}</strong>
                        <p style="margin: 3px 0 0; font-size: 12px; color: #3b82f6;">
                            {{ __('crm.cross_branch_lead_description') ?? 'يتم عرض البيانات الأساسية (الاسم، رقم الهاتف، والفرع) وسجل المتابعات الكامل في وضع القراءة فقط.' }}
                        </p>
                    </div>
                </div>
                <span class="badge-branch" style="font-weight: 800; font-size: 12px; padding: 6px 14px; border-radius: 8px;">
                    <i class="bi bi-geo-alt-fill"></i> {{ $lead->branch?->name_ar ?? $lead->branch?->name ?? 'فرع آخر' }}
                </span>
            </div>
        @endif
        <!-- PROFILE HEADER CARD -->
        <section class="lead-header-card">
            <div class="lead-header-top">
                <div class="lead-identity">
                    <div class="lead-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="lead-names">
                        <h2>{{ $lead->name }}</h2>
                        <p>
                            @if ($lead->branch)
                                <span><i class="bi bi-geo-alt"></i> {{ $lead->branch->name }}</span>
                                <span>•</span>
                            @endif
                            @if ($lead->subregion)
                                <span><i class="bi bi-geo-alt"></i> {{ $lead->subregion->full_name }}</span>
                                <span>•</span>
                            @elseif ($lead->governorate)
                                <span><i class="bi bi-geo-alt"></i> {{ $lead->governorate }}</span>
                                <span>•</span>
                            @endif
                            <span><i class="bi bi-person-badge"></i> {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?? __('crm.unassigned') }}</span>
                        </p>
                    </div>
                </div>
                <div>
                    @php
                        $stageCode = strtolower((string) ($lead->status?->code ?? $lead->status?->stage?->code ?? ''));
                        $stageIcon = match(true) {
                            str_contains($stageCode, 'donor') => 'bi-heart-fill',
                            str_contains($stageCode, 'not') || str_contains($stageCode, 'reject') => 'bi-slash-circle-fill',
                            str_contains($stageCode, 'follow') || str_contains($stageCode, 'no_answer') => 'bi-arrow-repeat',
                            str_contains($stageCode, 'new') => 'bi-stars',
                            default => 'bi-diagram-3-fill',
                        };
                        $stageName = $lead->status?->localizedName() ?? ($lead->status?->stage?->localizedName() ?? __('crm.no_status'));
                    @endphp
                    <div class="lead-stage-card" style="--stage-color: {{ $statusColor }};">
                        <div class="stage-icon-halo">
                            <span class="stage-pulse"></span>
                            <i class="bi {{ $stageIcon }}"></i>
                        </div>
                        <div class="stage-text-group">
                            <span class="stage-caption">{{ __('crm.current_stage') }}</span>
                            <span class="stage-title">{{ $stageName }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="metrics-bar">
                <div class="metric-box">
                    <span>{{ __('crm.donation_type') }}</span>
                    <b>{{ $lead->donation_type ?? __('crm.not_specified_yet') }}</b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.donation_cycle') }}</span>
                    <b>{{ $lead->donation_cycle ? (app('translator')->has('crm.donation_cycle_'.$lead->donation_cycle) ? __('crm.donation_cycle_'.$lead->donation_cycle) : $lead->donation_cycle) : '—' }}</b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.donation_value') }}</span>
                    <b style="color:#16a34a">{{ $lead->donation_value ? number_format((float) $lead->donation_value, 2) . ' ' . __('crm.currency_egp') : '—' }}</b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.next_followup') }}</span>
                    <b>
                        @if ($lead->next_follow_up_at)
                            {{ $lead->next_follow_up_at->format('Y-m-d H:i') }}
                        @else
                            {{ __('crm.no_followup_scheduled') }}
                        @endif
                    </b>
                </div>
            </div>
        </section>

        <div class="details-grid">
            <!-- LEFT COLUMN: PROFILE, CONTACTS & FINANCIAL RECORDS -->
            <div>
                <!-- CUSTOMER CORE INFO -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-info-circle"></i> {{ __('crm.customer_donor_data') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.full_name') }}</span>
                            <span class="info-value">{{ $lead->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.branch_col') }}</span>
                            <span class="info-value">{{ $lead->branch?->name ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.address') }}</span>
                            <span class="info-value">
                                {{ $lead->address ?: '' }}
                                @if($lead->subregion)
                                    ({{ $lead->subregion->full_name }})
                                @elseif($lead->governorate)
                                    ({{ $lead->governorate }})
                                @endif
                                @if(!$lead->address && !$lead->subregion && !$lead->governorate)
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_value_with_currency') }}</span>
                            <span class="info-value" style="color:#16a34a">
                                {{ $lead->donation_value ? number_format((float) $lead->donation_value, 2) . ' ' . __('crm.currency_egp') : '—' }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.donation_purpose_label') }}</span>
                            <span class="info-value">{{ $lead->donation_purpose ?? __('crm.general_purpose') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.last_contact_date') }}</span>
                            <span class="info-value">{{ $lead->contact_date ? $lead->contact_date->format('Y-m-d') : '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.lead_source') }}</span>
                            <span class="info-value">{{ $lead->source ?? __('crm.direct_source') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.registration_date') }}</span>
                            <span class="info-value">{{ $lead->created_at ? $lead->created_at->format('Y-m-d H:i') : '—' }}</span>
                        </div>
                        @if ($lead->creator)
                            <div class="info-row">
                                <span class="info-label">{{ __('crm.registered_by') }}</span>
                                <span class="info-value">{{ $lead->creator->name }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- PHONE NUMBERS -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-telephone"></i> {{ __('crm.lead_phone_numbers') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row is-primary-phone">
                            <div>
                                <span class="badge active" style="margin-inline-end:6px">{{ __('crm.primary_badge') }}</span>
                                <strong dir="ltr">{{ $lead->display_phone }}</strong>
                            </div>
                            <div style="display:flex;gap:6px">
                                @if ($callPhone)
                                    <a href="#" class="btn small soft" data-voice-dial="{{ $callPhone }}" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name }}" title="{{ __('crm.call') ?? 'اتصال' }}">
                                        <i class="bi bi-telephone-fill" style="color:#dc2637"></i>
                                    </a>
                                    <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener" class="btn small success" title="{{ __('crm.whatsapp_chat') }}">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @foreach ($lead->additionalPhones as $addPhone)
                            @php
                                $rawDigits = preg_replace('/\D+/', '', $addPhone->phone);
                            @endphp
                            <div class="info-row">
                                <div>
                                    <span class="badge" style="margin-inline-end:6px">{{ $addPhone->label ?: __('crm.phone_type_extra') }}</span>
                                    <span dir="ltr" style="font-weight:700">{{ $addPhone->display_phone }}</span>
                                </div>
                                <div style="display:flex;gap:6px">
                                    <a href="#" class="btn small soft" data-voice-dial="{{ $rawDigits }}" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name }}" title="{{ __('crm.call') ?? 'اتصال' }}">
                                        <i class="bi bi-telephone-fill" style="color:#dc2637"></i>
                                    </a>
                                    <a href="https://wa.me/{{ $rawDigits }}" target="_blank" rel="noopener" class="btn small success" title="{{ __('crm.whatsapp_chat') }}">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        @if ($lead->additionalPhones->isEmpty())
                            <small style="color:var(--muted); text-align:center; padding:8px 0; display:block">
                                {{ __('crm.no_extra_phones_registered') }}
                            </small>
                        @endif
                    </div>
                </section>

                <!-- DONATIONS & COLLECTIONS -->
                @if($lead->collectionCases->isNotEmpty())
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-cash-stack"></i> {{ __('crm.collection_history') }}</h2>
                            <span class="badge">{{ $lead->collectionCases->count() }}</span>
                        </div>
                        <div class="donation-history">
                            @foreach($lead->collectionCases as $collectionCase)
                                <article class="donation-entry">
                                    <div class="donation-entry-head">
                                        <div>
                                            <strong>{{ number_format((float)$collectionCase->expected_amount, 2) }} {{ __('crm.currency_egp') }}</strong>
                                            <div style="font-size:12px;font-weight:800;margin-top:4px">{{ $collectionCase->donation_type }}</div>
                                        </div>
                                        <span class="badge {{ $collectionCase->status === 'collected' ? 'active' : '' }}">{{ __('crm.collection_status_'.$collectionCase->status) }}</span>
                                    </div>
                                    <div class="donation-entry-meta">
                                        <span><i class="bi bi-calendar3"></i> {{ $collectionCase->due_at?->format('Y-m-d H:i') ?? '—' }}</span>
                                        <span><i class="bi bi-person-check"></i> {{ $collectionCase->assignedCollector?->name ?? __('crm.unassigned') }}</span>
                                    </div>
                                    @can('view', $collectionCase)
                                        <div class="donation-entry-actions">
                                            <a class="btn small soft" href="{{ route('v2.collections.show', $collectionCase) }}"><i class="bi bi-eye"></i>{{ __('crm.view_details') }}</a>
                                        </div>
                                    @endcan
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-receipt"></i> {{ __('crm.donation_history') }}</h2>
                        <span class="badge">{{ $lead->donations->count() }}</span>
                    </div>
                    @if ($lead->donations->isNotEmpty())
                        <div class="donation-history">
                            @foreach ($lead->donations as $donation)
                                <article class="donation-entry">
                                    <div class="donation-entry-head">
                                        <div>
                                            <strong>{{ number_format((float) $donation->amount, 2) }} {{ __('crm.currency_egp') }}</strong>
                                            <div style="font-size:12px;font-weight:800;margin-top:4px">
                                                {{ app()->getLocale() === 'en' && filled($donation->donationType?->name_en) ? $donation->donationType->name_en : $donation->donation_type }}
                                            </div>
                                            @if($donation->donation_way !== 'legacy')
                                                <small style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;color:var(--muted);margin-top:4px">
                                                    <span>{{ __('crm.donation_way_'.$donation->donation_way) }}</span>
                                                    @if($donation->instantDonationMethod)
                                                        <span>· {{ $donation->instantDonationMethod->localizedName() }}</span>
                                                    @endif
                                                    @if($donation->instant_donation_account)
                                                        <span class="badge" style="font-size:10px;padding:2px 6px;background:rgba(52,120,246,0.08);color:#2563eb;border:1px solid rgba(52,120,246,0.2);">
                                                            <i class="bi bi-wallet2"></i> {{ $donation->instant_donation_account }}
                                                        </span>
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                        <span class="badge active">{{ app('translator')->has('crm.donation_cycle_'.$donation->cycle) ? __('crm.donation_cycle_'.$donation->cycle) : $donation->cycle }}</span>
                                    </div>
                                    <div class="donation-entry-meta">
                                        <span><i class="bi bi-calendar3"></i> {{ $donation->donated_at?->format('Y-m-d H:i') ?? '—' }}</span>
                                        <span><i class="bi bi-person-check"></i> {{ $donation->recordedBy?->name ?? __('crm.system') }}</span>
                                    </div>
                                    @if ($donation->receipt_path)
                                        <div class="donation-entry-actions">
                                            <a class="btn small soft" href="{{ route('v2.leads.donations.receipt.preview', [$lead, $donation]) }}" target="_blank" rel="noopener">
                                                <i class="bi bi-eye"></i> {{ __('crm.preview_receipt') }}
                                            </a>
                                            <a class="btn small soft" href="{{ route('v2.leads.donations.receipt.download', [$lead, $donation]) }}">
                                                <i class="bi bi-download"></i> {{ __('crm.download_receipt') }}
                                            </a>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center;padding:24px 16px;color:var(--muted);background:var(--bg);border-radius:12px">
                            <i class="bi bi-receipt-cutoff" style="display:block;font-size:26px;margin-bottom:7px"></i>
                            {{ __('crm.no_donations_recorded') }}
                        </div>
                    @endif
                </section>

            </div>

            <!-- RIGHT COLUMN: COMMUNICATIONS, VOIP, CUSTOM FIELDS & TIMELINE -->
            <div>
                <!-- LATEST RESPONSE DETAILS -->
                @if ($lead->response_details)
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-chat-quote"></i> {{ __('crm.latest_response_notes') }}</h2>
                        </div>
                        <div style="background:var(--bg); border:1px solid var(--line); border-radius:10px; padding:14px 16px; line-height:1.7; color:var(--dark); white-space:pre-line;">{{ $lead->response_details }}</div>
                    </section>
                @endif

                @can('voip.view')
                    <section class="panel" id="leadCallInsights" data-endpoint="{{ route('v2.leads.calls', $lead) }}">
                        <div class="panel-head">
                            <div><h2><i class="bi bi-soundwave"></i> {{ __('crm.lead_call_insights') }}</h2><small style="color:var(--muted)">{{ __('crm.lead_call_insights_hint') }}</small></div>
                            <span class="badge">{{ __('crm.live_from_voip') }}</span>
                        </div>
                        <form class="call-filters" id="leadCallFilters">
                            <div><label for="callStartDate">{{ __('crm.from_date') }}</label><input id="callStartDate" name="start_date" type="date"></div>
                            <div><label for="callEndDate">{{ __('crm.to_date') }}</label><input id="callEndDate" name="end_date" type="date"></div>
                            <div><label for="callDirection">{{ __('crm.direction') }}</label><select id="callDirection" name="direction"><option value="">{{ __('crm.all_directions') }}</option><option value="inbound">{{ __('crm.incoming') }}</option><option value="outbound">{{ __('crm.outgoing') }}</option><option value="internal">{{ __('crm.internal') }}</option></select></div>
                            <button class="btn soft" type="submit"><i class="bi bi-funnel"></i> {{ __('crm.apply_filter') }}</button>
                        </form>
                        <div class="call-metrics" hidden data-call-metrics><div class="call-metric"><span>{{ __('crm.total_calls') }}</span><strong data-metric="total_calls">0</strong></div><div class="call-metric"><span>{{ __('crm.answer_rate') }}</span><strong data-metric="answer_rate_percent">0%</strong></div><div class="call-metric"><span>{{ __('crm.total_talk_time') }}</span><strong data-metric="total_talk_seconds">0:00</strong></div><div class="call-metric"><span>{{ __('crm.missed_calls') }}</span><strong data-metric="missed_calls">0</strong></div></div>
                        <div class="call-bars" hidden data-call-bars>@foreach ([['inbound', __('crm.incoming'), '#16a34a'], ['outbound', __('crm.outgoing'), '#0284c7'], ['internal', __('crm.internal'), '#64748b']] as [$key, $label, $color])<div class="call-bar"><span>{{ $label }}</span><span class="call-bar-track"><span class="call-bar-fill" data-direction="{{ $key }}" style="width:0;background:{{ $color }}"></span></span><b data-direction-count="{{ $key }}">0</b></div>@endforeach</div>
                        <div class="call-state" data-call-state><i class="bi bi-arrow-repeat"></i>{{ __('crm.loading_call_history') }}</div>
                        <div class="call-list" hidden data-call-list></div>
                        <div class="call-pagination" hidden data-call-pagination></div>
                    </section>
                @endcan

                <!-- STAGE-SPECIFIC DATA & AUDIT -->
                @if (!empty($stageHistoryGroups) && $stageHistoryGroups->isNotEmpty())
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-ui-checks"></i> {{ __('crm.stage_history_section_title') }}</h2>
                        </div>
                        <div class="info-list" style="display:flex; flex-direction:column; gap:10px;">
                            @foreach ($stageHistoryGroups as $group)
                                <div style="background:var(--bg); border:1px solid var(--line); border-radius:10px; padding:12px;">
                                    <div style="display:flex; justify-content:space-between; width:100%; align-items:center; margin-bottom:8px;">
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $group['stage']?->color ?? '#64748b' }};"></span>
                                            <strong>{{ $group['stage']?->localizedName() ?? '—' }}</strong>
                                        </div>
                                        <small style="color:var(--muted); font-size:11px;">
                                            <i class="bi bi-person"></i> {{ $group['actor'] }} • {{ $group['date']->format('Y-m-d H:i') }}
                                        </small>
                                    </div>
                                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:6px 12px; width:100%; font-size:12px;">
                                        @foreach ($group['values'] as $val)
                                            <div>
                                                <span style="color:var(--muted)">{{ $val['label'] }}:</span>
                                                <strong style="color:var(--dark)">{{ $val['value'] }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- CONFIGURABLE ADDITIONAL DATA -->
                @php
                    $profileFields = \App\Support\LeadFieldSchema::customFields();
                    $leadCustomValues = is_array($lead->custom_fields) ? $lead->custom_fields : [];
                @endphp
                @if ($profileFields->isNotEmpty())
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-grid-3x3-gap"></i> {{ __('crm.lf_additional_data') }}</h2>
                        </div>
                        <div class="info-list">
                            @foreach ($profileFields as $profileField)
                                @php
                                    $profileValue = $leadCustomValues[$profileField->key] ?? null;
                                    $hasValue = ! ($profileValue === null || $profileValue === '' || $profileValue === []);
                                @endphp
                                <div class="info-row">
                                    <span class="info-label">
                                        {{ $profileField->label() }}@if ($profileField->is_required) <span style="color:#dc2637">*</span> @endif
                                    </span>
                                    <span class="info-value" style="max-width:60%;text-align:end">
                                        {{ $hasValue ? \App\Support\LeadFieldSchema::formatValue($profileField, $profileValue) : '—' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- CUSTOMER ACTIVITY TIMELINE -->
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2><i class="bi bi-clock-history"></i> {{ __('crm.lead_activity_timeline') }}</h2>
                            <small style="color:var(--muted)">{{ __('crm.timeline_subtitle') }}</small>
                        </div>
                        @can('createFollowup', $lead)
                            <a
                             href="{{ route('v2.leads.followups.index', $lead) }}"
                             class="btn primary small"
                             data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
                             data-lead-name="{{ $lead->name }}"
                            >
                                <i class="bi bi-plus-lg"></i> {{ __('crm.add_followup') }}
                            </a>
                        @endcan
                    </div>

                    @if ($timelineEvents->isNotEmpty())
                        <div class="timeline">
                            @foreach ($timelineEvents as $event)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-card">
                                        <div class="timeline-head">
                                            <div>
                                                <span class="timeline-employee">
                                                    <i class="bi bi-person"></i> {{ $event['employee'] }}
                                                </span>
                                                @if ($event['type'] === 'followup')
                                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; margin-inline-start:6px">
                                                        <i class="bi bi-telephone"></i> {{ __('crm.followup') }}
                                                    </span>
                                                @else
                                                    <span class="badge" style="background:#fef3c7; color:#92400e; margin-inline-start:6px">
                                                        <i class="bi bi-arrow-left-right"></i> {{ __('crm.status_change') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="timeline-date">
                                                {{ $event['timestamp'] ? $event['timestamp']->format('Y-m-d H:i') : '—' }}
                                            </span>
                                        </div>

                                        @if ($event['from_status'] && $event['to_status'] && $event['from_status'] !== $event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status_changed_to') }} <span style="color:#64748b">{{ $event['from_status'] }}</span> {{ app()->getLocale() === 'ar' ? '←' : '→' }} <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @elseif ($event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status') }}: <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @endif

                                        @if ($event['details'])
                                            <div class="timeline-body">
                                                {{ $event['details'] }}
                                            </div>
                                        @endif

                                        @if (!empty($event['stage_values']))
                                            <div style="margin-top:10px; padding:10px 12px; background:var(--bg); border:1px solid var(--line); border-radius:8px;">
                                                <span style="display:block; font-size:11px; font-weight:800; color:#64748b; margin-bottom:6px;">
                                                    <i class="bi bi-ui-checks"></i> {{ __('crm.stage_fields_section_title') }}
                                                </span>
                                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:6px 12px;">
                                                    @foreach ($event['stage_values'] as $sVal)
                                                        <div style="font-size:12px;">
                                                            <span style="color:var(--muted)">{{ $sVal['label'] }}:</span>
                                                            <strong style="color:var(--dark)">{{ $sVal['value'] }}</strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($event['next_follow_up'])
                                            <div style="margin-top:10px; padding-top:8px; border-top:1px dashed var(--line); font-size:12px; color:var(--muted)">
                                                <i class="bi bi-calendar-event"></i> {{ __('crm.next_followup_at_label') }} <strong>{{ $event['next_follow_up']->format('Y-m-d H:i') }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center; padding:30px 20px; color:var(--muted); background:var(--bg); border-radius:12px;">
                            <i class="bi bi-chat-square-dots" style="font-size:30px; display:block; margin-bottom:8px"></i>
                            <p style="margin:0; font-weight:700">{{ __('crm.no_timeline_records_yet') }}</p>
                            @can('createFollowup', $lead)
                                <a
                                 href="{{ route('v2.leads.followups.index', $lead) }}"
                                 class="btn primary small"
                                 style="margin-top:12px;"
                                 data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
                                 data-lead-name="{{ $lead->name }}"
                                >
                                    {{ __('crm.log_first_followup_now') }}
                                </a>
                            @endcan
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </main>
</div>
@can('voip.view')
<script>
    (() => {
        const panel = document.getElementById('leadCallInsights');
        if (!panel) return;

        const form = document.getElementById('leadCallFilters');
        const state = panel.querySelector('[data-call-state]');
        const list = panel.querySelector('[data-call-list]');
        const pagination = panel.querySelector('[data-call-pagination]');
        const metrics = panel.querySelector('[data-call-metrics]');
        const bars = panel.querySelector('[data-call-bars]');
        const copy = {
            loading: @json(__('crm.loading_call_history')),
            empty: @json(__('crm.no_calls_match_filters')),
            error: @json(__('crm.pbx_server_error')),
            unassigned: @json(__('crm.unassigned')),
            extension: @json(__('crm.extension_short')),
            recording: @json(__('crm.call_recording')),
            directions: {
                inbound: @json(__('crm.incoming')),
                outbound: @json(__('crm.outgoing')),
                internal: @json(__('crm.internal')),
                unknown: @json(__('crm.unknown')),
            },
            statuses: {
                answered: @json(__('crm.status_answered')),
                missed: @json(__('crm.status_missed')),
                failed: @json(__('crm.status_failed')),
                busy: @json(__('crm.status_busy')),
                no_answer: @json(__('crm.status_no_answer')),
                unknown: @json(__('crm.status_unknown')),
            },
            prev: @json(__('crm.previous')),
            next: @json(__('crm.next')),
            showing: @json(app()->getLocale() === 'en' ? 'Showing :from - :to of :total calls' : 'عرض :from - :to من أصل :total مكالمة'),
            download: @json(app()->getLocale() === 'en' ? 'Download Recording' : 'تحميل التسجيل الصوتي'),
            speed: @json(app()->getLocale() === 'en' ? 'Playback Speed' : 'سرعة التشغيل'),
        };
        const showState = (message, icon = 'bi-info-circle') => {
            const symbol = document.createElement('i');
            symbol.className = `bi ${icon}`;
            state.replaceChildren(symbol, document.createTextNode(message));
            state.hidden = false;
        };

        const formatDuration = (seconds) => {
            const total = Math.max(0, Number(seconds) || 0);
            return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
        };

        let allCalls = [];
        let currentPage = 1;
        const pageSize = 5;
        let activeAudio = null;

        const createAudioPlayer = (recordingUrl, callDurationSec) => {
            const player = document.createElement('div');
            player.className = 'crm-audio-player';

            const audio = document.createElement('audio');
            audio.preload = 'none';
            audio.src = recordingUrl;

            const playBtn = document.createElement('button');
            playBtn.type = 'button';
            playBtn.className = 'audio-play-btn';
            playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
            playBtn.title = 'Play';

            const trackWrap = document.createElement('div');
            trackWrap.className = 'audio-track-wrap';

            const seek = document.createElement('input');
            seek.type = 'range';
            seek.className = 'audio-seek';
            seek.min = '0';
            seek.max = '100';
            seek.value = '0';
            seek.step = '0.1';

            const timeRow = document.createElement('div');
            timeRow.className = 'audio-time-row';

            const currTime = document.createElement('span');
            currTime.textContent = '0:00';

            const totalTime = document.createElement('span');
            totalTime.textContent = formatDuration(callDurationSec);

            timeRow.append(currTime, totalTime);
            trackWrap.append(seek, timeRow);

            const speedBtn = document.createElement('button');
            speedBtn.type = 'button';
            speedBtn.className = 'audio-speed-btn';
            speedBtn.textContent = '1x';
            speedBtn.title = copy.speed;

            const speeds = [1, 1.25, 1.5, 2];
            let speedIdx = 0;
            speedBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                speedIdx = (speedIdx + 1) % speeds.length;
                const spd = speeds[speedIdx];
                audio.playbackRate = spd;
                speedBtn.textContent = `${spd}x`;
            });

            const downloadBtn = document.createElement('a');
            downloadBtn.href = recordingUrl;
            downloadBtn.download = '';
            downloadBtn.className = 'audio-download-btn';
            downloadBtn.title = copy.download;
            downloadBtn.innerHTML = '<i class="bi bi-download"></i>';

            playBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (audio.paused) {
                    if (activeAudio && activeAudio !== audio) {
                        activeAudio.pause();
                    }
                    activeAudio = audio;
                    audio.play();
                } else {
                    audio.pause();
                }
            });

            audio.addEventListener('play', () => {
                playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                playBtn.title = 'Pause';
                player.classList.add('is-playing');
            });

            audio.addEventListener('pause', () => {
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                playBtn.title = 'Play';
                player.classList.remove('is-playing');
            });

            audio.addEventListener('ended', () => {
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                player.classList.remove('is-playing');
                seek.value = '0';
                currTime.textContent = '0:00';
            });

            audio.addEventListener('loadedmetadata', () => {
                if (audio.duration && !isNaN(audio.duration)) {
                    totalTime.textContent = formatDuration(audio.duration);
                }
            });

            audio.addEventListener('timeupdate', () => {
                if (!isNaN(audio.duration) && audio.duration > 0) {
                    const pct = (audio.currentTime / audio.duration) * 100;
                    seek.value = String(pct);
                }
                currTime.textContent = formatDuration(audio.currentTime);
            });

            seek.addEventListener('input', () => {
                if (!isNaN(audio.duration) && audio.duration > 0) {
                    audio.currentTime = (Number(seek.value) / 100) * audio.duration;
                }
            });

            player.append(audio, playBtn, trackWrap, speedBtn, downloadBtn);
            return player;
        };

        const appendCall = (call) => {
            const row = document.createElement('article');
            row.className = 'call-row';

            const identity = document.createElement('div');
            const date = document.createElement('strong');
            date.dir = 'ltr';
            date.textContent = call.call_date || '—';
            const direction = document.createElement('small');
            direction.textContent = copy.directions[call.direction] || copy.directions.unknown;
            identity.append(date, direction);

            const employee = document.createElement('div');
            const employeeName = document.createElement('strong');
            employeeName.textContent = call.crm_user?.name || call.agent_name || copy.unassigned;
            const extension = document.createElement('small');
            extension.textContent = call.agent_extension ? `${copy.extension} ${call.agent_extension}` : '—';
            employee.append(employeeName, extension);

            const duration = document.createElement('strong');
            duration.dir = 'ltr';
            duration.textContent = call.duration_formatted || formatDuration(call.duration_seconds);

            const status = document.createElement('span');
            status.className = 'badge';
            status.textContent = copy.statuses[call.disposition] || call.disposition || copy.statuses.unknown;

            row.append(identity, employee, duration, status);
            if (call.recording_url) {
                const player = createAudioPlayer(call.recording_url, Number(call.duration_seconds) || 0);
                row.append(player);
            }
            list.append(row);
        };

        const renderPagination = () => {
            if (allCalls.length <= pageSize) {
                pagination.hidden = true;
                return;
            }

            const totalPages = Math.ceil(allCalls.length / pageSize);
            currentPage = Math.min(Math.max(1, currentPage), totalPages);

            const from = (currentPage - 1) * pageSize + 1;
            const to = Math.min(currentPage * pageSize, allCalls.length);

            pagination.replaceChildren();

            const info = document.createElement('div');
            info.className = 'call-pagination-info';
            info.textContent = copy.showing
                .replace(':from', from.toLocaleString())
                .replace(':to', to.toLocaleString())
                .replace(':total', allCalls.length.toLocaleString());

            const controls = document.createElement('div');
            controls.className = 'call-pagination-controls';

            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'btn-call-page';
            prevBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
            prevBtn.title = copy.prev;
            prevBtn.disabled = currentPage <= 1;
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderCallsPage();
                }
            });
            controls.append(prevBtn);

            for (let i = 1; i <= totalPages; i++) {
                if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== totalPages) {
                    continue;
                }
                const pageBtn = document.createElement('button');
                pageBtn.type = 'button';
                pageBtn.className = `btn-call-page ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = String(i);
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    renderCallsPage();
                });
                controls.append(pageBtn);
            }

            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'btn-call-page';
            nextBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
            nextBtn.title = copy.next;
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderCallsPage();
                }
            });
            controls.append(nextBtn);

            pagination.append(info, controls);
            pagination.hidden = false;
        };

        const renderCallsPage = () => {
            if (activeAudio) {
                activeAudio.pause();
                activeAudio = null;
            }
            list.replaceChildren();
            const start = (currentPage - 1) * pageSize;
            const pagedCalls = allCalls.slice(start, start + pageSize);
            pagedCalls.forEach(appendCall);
            renderPagination();
        };

        const render = (payload) => {
            const summary = payload.summary || {};
            const directions = payload.charts?.directions || {};
            allCalls = Array.isArray(payload.calls) ? payload.calls : [];
            currentPage = 1;

            panel.querySelector('[data-metric="total_calls"]').textContent = Number(summary.total_calls || 0).toLocaleString();
            panel.querySelector('[data-metric="answer_rate_percent"]').textContent = `${Number(summary.answer_rate_percent || 0).toFixed(1)}%`;
            panel.querySelector('[data-metric="total_talk_seconds"]').textContent = formatDuration(summary.total_talk_seconds);
            panel.querySelector('[data-metric="missed_calls"]').textContent = Number(summary.missed_calls || 0).toLocaleString();

            const maximum = Math.max(1, ...['inbound', 'outbound', 'internal'].map((key) => Number(directions[key] || 0)));
            ['inbound', 'outbound', 'internal'].forEach((key) => {
                const count = Number(directions[key] || 0);
                panel.querySelector(`[data-direction="${key}"]`).style.width = `${(count / maximum) * 100}%`;
                panel.querySelector(`[data-direction-count="${key}"]`).textContent = count.toLocaleString();
            });

            metrics.hidden = false;
            bars.hidden = false;

            if (allCalls.length === 0) {
                list.hidden = true;
                pagination.hidden = true;
                showState(copy.empty);
            } else {
                state.hidden = true;
                list.hidden = false;
                renderCallsPage();
            }
        };

        const loadCalls = async () => {
            showState(copy.loading, 'bi-arrow-repeat');
            list.hidden = true;
            pagination.hidden = true;
            metrics.hidden = true;
            bars.hidden = true;

            const query = new URLSearchParams();
            new FormData(form).forEach((value, key) => {
                if (value) query.set(key, String(value));
            });
            query.set('limit', '100');

            try {
                const response = await fetch(`${panel.dataset.endpoint}?${query}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || payload.success === false) throw new Error(payload.error || copy.error);
                render(payload);
            } catch (error) {
                showState(error instanceof Error ? error.message : copy.error, 'bi-exclamation-triangle');
            }
        };
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            loadCalls();
        });
        loadCalls();
    })();
</script>
@endcan
<script src="{{ asset('crm-sidebar.js') }}"></script>
@include('partials.transition-popup')

</body>
</html>

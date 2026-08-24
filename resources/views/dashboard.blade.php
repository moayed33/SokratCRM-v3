<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SokratCRM — {{ __('crm.dashboard') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<style>
:root {
    --red: #dc2637;
    --red-hover: #b81829;
    --dark: #182033;
    --text: #475569;
    --muted: #64748b;
    --line: #e2e8f0;
    --bg: #f8fafc;
    --card: #ffffff;
    --shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    --radius: 16px;
}
html.dark-mode {
    --dark: #f1f5f9;
    --text: #cbd5e1;
    --muted: #94a3b8;
    --line: #334155;
    --bg: #0f172a;
    --card: #1e293b;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
* { box-sizing: border-box; }
body {
    margin: 0;
    min-width: 320px;
    background: var(--bg);
    color: var(--dark);
    font-family: var(--font-primary);
    font-size: 14px;
}
button, input, select { font: inherit; }
a { color: inherit; }
.app { display: flex; min-height: 100vh; }
.main { flex: 1; min-width: 0; padding: 24px 32px 60px; }

/* Topbar */
.topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.topbar h1 { margin: 0; font-size: 24px; font-weight: 900; }
.topbar p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
.topbar-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.btn-leads-pill {
    display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 16px;
    border-radius: 999px; border: 1px solid var(--line); background: var(--card);
    color: var(--dark); font-size: 13px; font-weight: 800; text-decoration: none;
    cursor: pointer; transition: all .2s cubic-bezier(.4, 0, .2, 1);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.btn-leads-pill i { font-size: 15px; color: var(--red); }
.btn-leads-pill:hover, .btn-leads-pill:focus-visible {
    background: #fff5f6; border-color: rgba(220, 38, 55, 0.4); color: var(--red);
    transform: translateY(-1px); box-shadow: 0 4px 14px rgba(220, 38, 55, 0.12);
}
html.dark-mode .btn-leads-pill {
    background: var(--bg-card, rgba(24, 24, 27, .75)) !important;
    border-color: var(--line, rgba(255, 255, 255, .1)) !important;
    color: var(--text-primary, #f4f4f5) !important;
    box-shadow: var(--shadow-glass) !important;
}
html.dark-mode .btn-leads-pill:hover, html.dark-mode .btn-leads-pill:focus-visible {
    background: rgba(239, 68, 68, .18) !important;
    border-color: rgba(239, 68, 68, .45) !important;
    color: #f87171 !important;
}
@media (max-width: 768px) {
    .topbar { flex-direction: column; align-items: stretch; gap: 14px; }
    .topbar-actions { width: 100%; justify-content: flex-start; gap: 8px; }
}
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    min-height: 40px; padding: 0 16px; border: 1px solid var(--line); border-radius: 10px;
    background: var(--card); color: var(--dark); font-weight: 700; cursor: pointer; text-decoration: none;
    transition: .15s; font-size: 13px;
}
.btn:hover { border-color: #cbd5e1; background: #f1f5f9; }
.btn.primary { background: var(--red); border-color: var(--red); color: #fff; box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25); }
.btn.primary:hover { background: var(--red-hover); border-color: var(--red-hover); }
.btn.soft { background: #f1f5f9; border-color: transparent; }
.btn.small { min-height: 32px; padding: 0 10px; font-size: 12px; }

/* Hero Banner & Animated Journey */
.hero {
    position: relative;
    overflow: hidden;
    min-height: 180px;
    padding: 28px 32px;
    border-radius: 20px;
    background: linear-gradient(125deg, #131a29 0%, #1e283d 50%, #26334d 100%);
    color: #fff;
    box-shadow: 0 20px 48px rgba(19, 26, 41, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 24px;
}
.hero-bg-ambient {
    position: absolute; inset: 0; pointer-events: none; overflow: hidden; z-index: 1;
}
.hero-glow-orb {
    position: absolute; border-radius: 50%; filter: blur(50px); opacity: 0.18; pointer-events: none;
}
.hero-glow-red {
    width: 220px; height: 220px; top: -60px; inset-inline-start: -40px;
    background: radial-gradient(circle, #dc2637 0%, transparent 70%);
}
.hero-glow-green {
    width: 260px; height: 260px; bottom: -80px; inset-inline-end: 60px;
    background: radial-gradient(circle, #22c55e 0%, transparent 70%);
}
.hero-main-content { position: relative; z-index: 10; max-width: 520px; }
.hero-subheading { display: block; color: #94a3b8; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
.hero-heading { margin: 0 0 18px; font-size: 24px; font-weight: 900; line-height: 1.3; }
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.hero-cta-btn {
    animation: ctaReadyGlow 8s ease-in-out infinite;
}
@keyframes ctaReadyGlow {
    0%, 75%, 100% { box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25); transform: translateY(0); }
    85% { box-shadow: 0 0 22px rgba(220, 38, 55, 0.6), 0 0 35px rgba(220, 38, 55, 0.3); transform: translateY(-1px); }
    92% { box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25); transform: translateY(0); }
}

/* Desktop & Tablet Animated 4-Card Pipeline Journey (Section 10) */
.hero-pipeline-wrap {
    position: absolute;
    inset-inline-end: 32px;
    top: 50%;
    transform: translateY(-50%);
    width: 440px;
    height: 120px;
    pointer-events: none;
    z-index: 5;
}
.pipeline-svg {
    position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible;
}
.pipeline-stream {
    stroke-dasharray: 8 160;
    animation: streamFlow 8s linear infinite;
}
@keyframes streamFlow {
    0% { stroke-dashoffset: 0; }
    100% { stroke-dashoffset: -340; }
}
.pipeline-pulse-dot {
    position: absolute; width: 9px; height: 9px; border-radius: 50%; background: #ffffff;
    box-shadow: 0 0 10px #38bdf8, 0 0 20px #22c55e;
    offset-path: path('M 390,28 C 340,28 340,88 290,88 C 240,88 240,28 190,28 C 140,28 140,88 50,88');
    animation: pulseDotTravel 8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    pointer-events: none; z-index: 10;
}
@keyframes pulseDotTravel {
    0% { offset-distance: 0%; opacity: 0; transform: scale(0.5); }
    4% { opacity: 1; transform: scale(1); }
    70% { offset-distance: 100%; opacity: 1; transform: scale(1.4); }
    76% { offset-distance: 100%; opacity: 0; transform: scale(1.8); }
    100% { offset-distance: 100%; opacity: 0; transform: scale(0.5); }
}

.pipeline-card {
    position: absolute; transform: translate(-50%, -50%);
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 12px;
    background: rgba(22, 30, 46, 0.75); border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    color: #e2e8f0; font-size: 12px; font-weight: 800; white-space: nowrap;
    animation-duration: 8s; animation-iteration-count: infinite; animation-timing-function: ease-in-out;
}
.pipeline-card-icon { font-size: 13px; display: inline-flex; align-items: center; }

/* Card 1: جديد */
.pipeline-card.stage-1 { animation-name: stage1Anim; }
.pipeline-card.stage-1 .pipeline-card-icon { color: #38bdf8; }
@keyframes stage1Anim {
    0%, 3% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); background: rgba(22,30,46,0.75); }
    6%, 22% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); border-color: rgba(56,189,248,0.6); background: rgba(56,189,248,0.18); box-shadow: 0 0 16px rgba(56,189,248,0.3); }
    26%, 85% { opacity: 0.85; transform: translate(-50%, -50%) scale(1); border-color: rgba(56,189,248,0.25); background: rgba(22,30,46,0.75); }
    90%, 100% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); }
}

/* Card 2: تواصل */
.pipeline-card.stage-2 { animation-name: stage2Anim; }
.pipeline-card.stage-2 .pipeline-card-icon { color: #818cf8; }
@keyframes stage2Anim {
    0%, 22% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); background: rgba(22,30,46,0.75); }
    25%, 44% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); border-color: rgba(129,140,248,0.6); background: rgba(129,140,248,0.18); box-shadow: 0 0 16px rgba(129,140,248,0.3); }
    48%, 85% { opacity: 0.85; transform: translate(-50%, -50%) scale(1); border-color: rgba(129,140,248,0.25); background: rgba(22,30,46,0.75); }
    90%, 100% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); }
}

/* Card 3: متابعة */
.pipeline-card.stage-3 { animation-name: stage3Anim; }
.pipeline-card.stage-3 .pipeline-card-icon { color: #fbbf24; }
@keyframes stage3Anim {
    0%, 44% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); background: rgba(22,30,46,0.75); }
    47%, 66% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); border-color: rgba(251,191,36,0.6); background: rgba(251,191,36,0.18); box-shadow: 0 0 16px rgba(251,191,36,0.3); }
    70%, 85% { opacity: 0.85; transform: translate(-50%, -50%) scale(1); border-color: rgba(251,191,36,0.25); background: rgba(22,30,46,0.75); }
    90%, 100% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); }
}

/* Card 4: متبرع ✓ (Celebration / Success Glow) */
.pipeline-card.stage-4 { animation-name: stage4Anim; }
.pipeline-card.stage-4 .pipeline-card-icon { color: #22c55e; }
.stage-success-ring {
    position: absolute; inset: -3px; border-radius: 14px; border: 1px solid rgba(34, 197, 94, 0.8);
    opacity: 0; pointer-events: none; animation: successRingPulse 8s ease-out infinite;
}
@keyframes successRingPulse {
    0%, 66% { opacity: 0; transform: scale(0.9); }
    68% { opacity: 0.85; transform: scale(1); }
    78% { opacity: 0; transform: scale(1.3); }
    100% { opacity: 0; transform: scale(1.3); }
}
@keyframes stage4Anim {
    0%, 65% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); background: rgba(22,30,46,0.75); }
    68%, 88% { opacity: 1; transform: translate(-50%, -50%) scale(1.08); border-color: rgba(34,197,94,0.7); background: rgba(34,197,94,0.2); box-shadow: 0 0 24px rgba(34,197,94,0.4); }
    92%, 96% { opacity: 0.85; transform: translate(-50%, -50%) scale(1); border-color: rgba(34,197,94,0.3); background: rgba(22,30,46,0.75); }
    100% { opacity: 0.7; transform: translate(-50%, -50%) scale(0.96); border-color: rgba(255,255,255,0.1); }
}

/* Mobile Journey */
.hero-mobile-pipeline {
    display: none; align-items: center; gap: 8px; margin-top: 18px;
    padding: 8px 12px; border-radius: 12px; background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; max-width: 100%; flex-wrap: wrap;
}
.mobile-pipe-card {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 8px;
    background: rgba(22, 30, 46, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); font-size: 11px; font-weight: 700;
}

/* Filters Panel */
.filters-panel {
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
    padding: 16px 20px; box-shadow: var(--shadow); margin-bottom: 24px;
}
.filters-form {
    display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) auto; gap: 12px; align-items: end;
}
.filter-group label { display: block; font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 6px; }
.filter-group input, .filter-group select {
    width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 8px 12px;
    background: var(--card); color: var(--dark); outline: none; transition: .15s; font-size: 13px;
}
.filter-group input:focus, .filter-group select:focus {
    border-color: var(--red); box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.1);
}

/* KPI Summary Cards Grid (Section 6 & 7) */
.kpi-grid {
    display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px;
}
.kpi-card {
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
    padding: 16px 18px; box-shadow: var(--shadow); display: flex; align-items: center; justify-content: space-between;
    text-decoration: none; color: inherit; transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease; min-width: 0;
}
.kpi-card:hover { transform: translateY(-3px); border-color: var(--card-accent, #cbd5e1); box-shadow: 0 14px 28px rgba(0,0,0,0.06); }
.kpi-info span { display: block; font-size: 12px; color: var(--muted); font-weight: 700; }
.kpi-info b { display: block; font-size: 20px; font-weight: 900; margin-top: 4px; color: var(--dark); }
.kpi-icon-box {
    width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; font-size: 18px; flex-shrink: 0;
    margin-inline-start: 8px;
}
/* Dynamic Pipeline Stages Section (Section 5 & 8) */
.stages-panel {
    background: var(--card); border: 1px solid var(--line); border-radius: var(--radius);
    padding: 22px 24px; box-shadow: var(--shadow); margin-bottom: 24px;
}
.panel-head {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;
    padding-bottom: 14px; border-bottom: 1px solid var(--line);
}
.panel-head h2 { margin: 0; font-size: 17px; font-weight: 900; display: flex; align-items: center; gap: 8px; }
.panel-head p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }

.stages-grid {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px;
}
.stage-summary-box {
    position: relative; padding: 18px; border-radius: 14px; border: 1px solid var(--line);
    border-top: 4px solid var(--stage-color, #3478f6);
    background: linear-gradient(180deg, color-mix(in srgb, var(--stage-color, #3478f6) 6%, #ffffff), #ffffff 45%);
    transition: .2s; text-decoration: none; color: inherit; display: block;
}
.stage-summary-box:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0,0,0,0.06); }
.stage-box-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.stage-box-title { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 900; }
.stage-box-count { font-size: 24px; font-weight: 900; color: var(--stage-color, #3478f6); }
.stage-box-percentage { font-size: 12px; font-weight: 700; color: var(--muted); margin-bottom: 12px; display: block; }
.stage-statuses-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.stage-status-chip {
    display: inline-flex; align-items: center; justify-content: space-between; gap: 6px;
    padding: 4px 9px; border-radius: 8px; background: #f8fafc; border: 1px solid var(--line);
    font-size: 11px; font-weight: 700; color: #475569;
}
.stage-status-chip b { color: var(--stage-color, #3478f6); }

/* Charts Layout (Section 11) */
.charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.85fr) minmax(0, 1.15fr);
    gap: 18px;
    margin-bottom: 24px;
    width: 100%;
    max-width: 100%;
}
.chart-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow);
    min-width: 0;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}
.chart-wrap-responsive {
    position: relative;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    height: 280px;
    min-height: 280px;
}

/* Bottom Grid */
.bottom-grid {
    display: grid; grid-template-columns: 2fr 1fr; gap: 18px;
}
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 600px; }
th, td { text-align: start; padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: middle; }
th { background: #f8fafc; color: var(--muted); font-size: 12px; font-weight: 800; }
tr:hover td { background: #fafbfd; }

.badge {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 999px;
    font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569;
}

.quick-actions-list { display: grid; gap: 10px; }
.quick-action-link {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px;
    background: #f8fafc; border: 1px solid var(--line); text-decoration: none; font-weight: 800;
    transition: .15s; font-size: 13px;
}
.quick-action-link:hover { background: #fff1f3; border-color: #fecdd3; color: var(--red); transform: translateX(-3px); }
.quick-action-link i { font-size: 17px; }

@media (max-width: 1250px) {
    .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .filters-form { grid-template-columns: repeat(3, 1fr); }
    .bottom-grid { grid-template-columns: 1fr; }
}
@media (max-width: 992px) {
    .charts-grid { grid-template-columns: 1fr; }
}
@media (max-width: 1100px) {
    .stages-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
    .hero-pipeline-wrap { display: none !important; }
    .hero-mobile-pipeline { display: inline-flex; }
    .stages-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .filters-form { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .main { padding: 16px; }
    .stages-grid { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
@include('partials.page-loader')
<div class="app">
    @include('partials.crm-sidebar')

    <main class="main">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <h1>{{ __('crm.dashboard') }}</h1>
                    <p id="currentLiveDate">{{ __('crm.dashboard_live_tracking_subtitle') }}</p>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="{{ route('v2.leads') }}" class="btn-leads-pill" title="{{ __('crm.all_leads') }}">
                    <i class="bi bi-people"></i>
                    <span>{{ __('crm.all_leads') }}</span>
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        <!-- 1. HERO BANNER: 4-CARD ANIMATED DONOR JOURNEY (Sections 9 & 10) -->
        <section class="hero" id="crmHeroBanner">
            <div class="hero-bg-ambient" aria-hidden="true">
                <div class="hero-glow-orb hero-glow-red"></div>
                <div class="hero-glow-orb hero-glow-green"></div>
            </div>

            <!-- 4-Card Floating Animation (Desktop/Tablet) -->
            <div class="hero-pipeline-wrap" id="heroPipelineWrap" aria-label="{{ __('crm.donor_journey_pipeline') }}">
                <svg class="pipeline-svg" viewBox="0 0 440 120" fill="none">
                    <defs>
                        <linearGradient id="donorPipelineGrad" x1="100%" y1="0%" x2="0%" y2="0%">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.4"/>
                            <stop offset="35%" stop-color="#818cf8" stop-opacity="0.4"/>
                            <stop offset="70%" stop-color="#fbbf24" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="#22c55e" stop-opacity="0.6"/>
                        </linearGradient>
                    </defs>
                    <path d="M 390,28 C 340,28 340,88 290,88 C 240,88 240,28 190,28 C 140,28 140,88 50,88" stroke="rgba(255,255,255,0.08)" stroke-width="2" stroke-dasharray="4 4"/>
                    <path class="pipeline-stream" d="M 390,28 C 340,28 340,88 290,88 C 240,88 240,28 190,28 C 140,28 140,88 50,88" stroke="url(#donorPipelineGrad)" stroke-width="2"/>
                </svg>

                <div class="pipeline-pulse-dot" aria-hidden="true"></div>

                <!-- 4 Animated Cards -->
                <div class="pipeline-card stage-1" style="left: 390px; top: 28px;">
                    <span class="pipeline-card-icon"><i class="bi bi-person-plus-fill"></i></span>
                    <span>{{ __('crm.status_new') }}</span>
                </div>

                <div class="pipeline-card stage-2" style="left: 290px; top: 88px;">
                    <span class="pipeline-card-icon"><i class="bi bi-telephone-outbound-fill"></i></span>
                    <span>{{ __('crm.contact') }}</span>
                </div>

                <div class="pipeline-card stage-3" style="left: 190px; top: 28px;">
                    <span class="pipeline-card-icon"><i class="bi bi-arrow-repeat"></i></span>
                    <span>{{ __('crm.followup') }}</span>
                </div>

                <div class="pipeline-card stage-4" style="left: 50px; top: 88px;">
                    <span class="pipeline-card-icon"><i class="bi bi-patch-check-fill"></i></span>
                    <span>{{ __('crm.donor') }} ✓</span>
                    <span class="stage-success-ring"></span>
                </div>
            </div>

            <!-- Content -->
            <div class="hero-main-content">
                <small class="hero-subheading">{{ __('crm.welcome_dashboard_title') }}</small>
                <h2 class="hero-heading">{{ __('crm.manage_donors_journey_heading') }}</h2>
                <div class="hero-actions">
                    @can('leads.create')
                        <a class="btn primary hero-cta-btn" href="{{ route('v2.leads.create') }}">
                            <i class="bi bi-plus-lg"></i> {{ __('crm.add_lead') }}
                        </a>
                    @endcan
                    @can('tasks.view')
                        <a class="btn soft" href="{{ route('v2.tasks.daily') }}" style="background:rgba(255,255,255,0.1); color:#fff; border-color:rgba(255,255,255,0.2)">
                            <i class="bi bi-calendar-check"></i> {{ __('crm.daily_tasks_followups') }}
                        </a>
                    @endcan
                </div>

                <!-- Mobile 4-Step Journey -->
                <div class="hero-mobile-pipeline">
                    <div class="mobile-pipe-card"><i class="bi bi-person-plus-fill" style="color:#38bdf8"></i> {{ __('crm.status_new') }}</div>
                    <span style="color:rgba(255,255,255,0.3)">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                    <div class="mobile-pipe-card"><i class="bi bi-telephone-outbound-fill" style="color:#818cf8"></i> {{ __('crm.contact') }}</div>
                    <span style="color:rgba(255,255,255,0.3)">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                    <div class="mobile-pipe-card"><i class="bi bi-arrow-repeat" style="color:#fbbf24"></i> {{ __('crm.followup') }}</div>
                    <span style="color:rgba(255,255,255,0.3)">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span>
                    <div class="mobile-pipe-card" style="border-color:#22c55e; background:rgba(34,197,94,0.18)"><i class="bi bi-patch-check-fill" style="color:#22c55e"></i> {{ __('crm.donor') }} ✓</div>
                </div>
            </div>
        </section>

        <!-- 2. ADVANCED DONOR FILTERS (Section 12) -->
        <section class="filters-panel">
            <form method="GET" action="{{ route('dashboard') }}" class="filters-form">
                <!-- Employee Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.assigned_employee') }}</label>
                    <select name="employee" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_employees') }}</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp }}" {{ $filters['employee'] === $emp ? 'selected' : '' }}>{{ $emp }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter (Dynamic from DB) -->
                <div class="filter-group">
                    <label>{{ __('crm.lead_status') }}</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_statuses') }}</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->code }}" {{ ($filters['status'] ?? '') === $st->code || (string) ($filters['status'] ?? '') === (string) $st->id ? 'selected' : '' }}>
                                {{ $st->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Type Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.donation_type') }}</label>
                    <select name="donation_type" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_types') }}</option>
                        @foreach ($donationTypes as $dType)
                            <option value="{{ $dType->name_ar }}" {{ $filters['donation_type'] === $dType->name_ar ? 'selected' : '' }}>
                                {{ (app()->getLocale() === 'en' && !empty($dType->name_en)) ? $dType->name_en : $dType->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Donation Cycle Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.donation_cycle') }}</label>
                    <select name="donation_cycle" onchange="this.form.submit()">
                        <option value="">{{ __('crm.all_donation_cycles') }}</option>
                        @foreach ($donationCycles as $cKey => $cLabel)
                            <option value="{{ $cKey }}" {{ $filters['donation_cycle'] === $cKey ? 'selected' : '' }}>
                                {{ __('crm.donation_cycle_' . $cKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Period Filter -->
                <div class="filter-group">
                    <label>{{ __('crm.period') }}</label>
                    <select name="period" onchange="this.form.submit()">
                        <option value="all" {{ $filters['period'] === 'all' ? 'selected' : '' }}>{{ __('crm.all_periods') }}</option>
                        <option value="today" {{ $filters['period'] === 'today' ? 'selected' : '' }}>{{ __('crm.today') }}</option>
                        <option value="week" {{ $filters['period'] === 'week' ? 'selected' : '' }}>{{ __('crm.this_week') }}</option>
                        <option value="month" {{ $filters['period'] === 'month' ? 'selected' : '' }}>{{ __('crm.this_month') }}</option>
                        <option value="year" {{ $filters['period'] === 'year' ? 'selected' : '' }}>{{ __('crm.this_year') }}</option>
                    </select>
                </div>

                <!-- Submit / Reset Actions -->
                <div style="display:flex; gap:6px;">
                    <button type="submit" class="btn primary small" style="min-height:38px">
                        {{ __('crm.filter') }}
                    </button>
                    @if (($filters['employee'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['stage'] ?? '') !== '' || ($filters['donation_type'] ?? '') !== '' || ($filters['donation_cycle'] ?? '') !== '' || ($filters['period'] ?? 'all') !== 'all')
                        <a href="{{ route('dashboard') }}" class="btn soft small" style="min-height:38px" title="{{ __('crm.reset') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- 3. KPI METRICS GRID (Sections 6, 7 & 23) -->
        <section class="kpi-grid">
            <!-- Total Customers -->
            <a href="{{ route('v2.leads') }}" class="kpi-card" style="--card-accent: #3478f6;">
                <div class="kpi-info">
                    <span>{{ __('crm.total_leads') }}</span>
                    <b>{{ number_format($totalCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(52,120,246,0.1); color:#3478f6;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </a>

            <!-- New Stage Customers -->
            <a href="{{ route('v2.leads', ['stage' => $stages->firstWhere('code', 'new')?->id ?? 'new']) }}" class="kpi-card" style="--card-accent: #0284c7;">
                <div class="kpi-info">
                    <span>{{ __('crm.status_new') }}</span>
                    <b>{{ number_format($newCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(56,189,248,0.12); color:#0284c7;">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
            </a>

            <!-- Confirmed Donors -->
            <a href="{{ route('v2.leads', ['stage' => $stages->firstWhere('code', 'donor')?->id ?? 'donor']) }}" class="kpi-card" style="--card-accent: #16a34a;">
                <div class="kpi-info">
                    <span>{{ __('crm.donor') }}</span>
                    <b>{{ number_format($donorCustomersCount) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(22,163,74,0.12); color:#16a34a;">
                    <i class="bi bi-heart-fill"></i>
                </div>
            </a>

            <!-- Donor Conversion Rate (Section 7) -->
            <div class="kpi-card" style="cursor:default; --card-accent: #16a34a;">
                <div class="kpi-info">
                    <span>{{ __('crm.conversion_rate_to_donor') }}</span>
                    <b style="color:#16a34a">{{ $donorConversionRate }}%</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(22,163,74,0.1); color:#16a34a;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>

            <!-- Total Donation Value -->
            <div class="kpi-card" style="cursor:default; --card-accent: #d97706;">
                <div class="kpi-info">
                    <span>{{ __('crm.total_donations_value') }}</span>
                    <b style="font-size:17px">{{ number_format($totalDonationValue, 2) }} <small style="font-size:11px; font-weight:normal">{{ __('crm.currency_egp') }}</small></b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(245,158,11,0.1); color:#d97706;">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>

            <!-- Today Follow-ups -->
            <a href="{{ route('v2.tasks.daily', ['scope' => 'today']) }}" class="kpi-card" style="--card-accent: #d97706;">
                <div class="kpi-info">
                    <span>{{ __('crm.today_followups') }}</span>
                    <b style="color:{{ $followupCounts['today'] > 0 ? '#d97706' : 'inherit' }}">{{ number_format($followupCounts['today']) }}</b>
                </div>
                <div class="kpi-icon-box" style="background:rgba(245,158,11,0.1); color:#d97706;">
                    <i class="bi bi-telephone-inbound-fill"></i>
                </div>
            </a>
        </section>

        <!-- 4. DYNAMIC PIPELINE STATUSES SUMMARY -->
        <section class="stages-panel">
            <div class="panel-head">
                <div>
                    <h2><i class="bi bi-diagram-3"></i> {{ __('crm.donor_journey_pipeline_title') }}</h2>
                    <p>{{ __('crm.pipeline_stages_desc') }}</p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:12px; padding:6px 12px;">
                        {{ $pipelineStages->count() }} {{ __('crm.active_stages') }}
                    </span>
                    <a href="{{ route('v2.leads.kanban') }}" class="btn small soft" title="{{ __('crm.view_interactive_board') }}">
                        <i class="bi bi-kanban"></i> {{ __('crm.kanban') }}
                    </a>
                </div>
            </div>

            <div class="stages-grid">
                @foreach ($pipelineStages as $stage)
                    <a href="{{ route('v2.leads', ['stage' => $stage->id]) }}" class="stage-summary-box" style="--stage-color: {{ $stage->color ?: '#3478f6' }};">
                        <div class="stage-box-top">
                            <div class="stage-box-title">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:6px; background:{{ $stage->color ?: '#3478f6' }}18; color:{{ $stage->color ?: '#3478f6' }}; font-size:13px;">
                                    <i class="bi {{ $stage->icon ? (str_starts_with($stage->icon, 'bi-') ? $stage->icon : 'bi-' . $stage->icon) : 'bi-app-indicator' }}"></i>
                                </span>
                                <span>{{ $stage->localizedName() }}</span>
                            </div>
                            <span class="stage-box-count">{{ number_format($stage->leads_count) }}</span>
                        </div>

                        <span class="stage-box-percentage">
                            {{ $stage->percentage }}% {{ __('crm.of_total_leads') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- 5. CHARTS ROW (Section 11) -->
        <section class="charts-grid">
            <!-- Chart 2: Activity Over Time -->
            <article class="chart-card">
                <div class="panel-head" style="margin-bottom:12px; padding-bottom:10px;">
                    <div>
                        <h2><i class="bi bi-activity"></i> {{ __('crm.activity_trend_title') }}</h2>
                        <p>{{ __('crm.activity_trend_desc') }}</p>
                    </div>
                </div>
                <div class="chart-wrap-responsive">
                    <canvas id="activityTrendChart" role="img" aria-label="{{ __('crm.activity_trend_title') }}"></canvas>
                </div>
            </article>

            <!-- Chart 1: Customer Stage Distribution (Donut Chart) -->
            <article class="chart-card">
                <div class="panel-head" style="margin-bottom:12px; padding-bottom:10px;">
                    <div>
                        <h2><i class="bi bi-pie-chart"></i> {{ __('crm.stage_distribution_title') }}</h2>
                        <p>{{ __('crm.stage_distribution_desc') }}</p>
                    </div>
                </div>
                <div class="chart-wrap-responsive">
                    <canvas id="stageDonutChart" role="img" aria-label="{{ __('crm.stage_distribution_title') }}"></canvas>
                </div>
            </article>
        </section>

        <!-- 6. BOTTOM ROW: LATEST FOLLOW-UPS & QUICK SHORTCUTS -->
        <section class="bottom-grid">
            <article class="chart-card">
                <div class="panel-head">
                    <div>
                        <h2><i class="bi bi-clock-history"></i> {{ __('crm.latest_followups_calls') }}</h2>
                        <p>{{ __('crm.latest_activities_desc') }}</p>
                    </div>
                    <a href="{{ route('v2.tasks.daily') }}" class="btn small soft">
                        {{ __('crm.view_all') }}
                    </a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.client') }}</th>
                                <th>{{ __('crm.followup_type') }}</th>
                                <th>{{ __('crm.employee') }}</th>
                                <th>{{ __('crm.datetime') }}</th>
                                <th>{{ __('crm.stage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestFollowups as $followup)
                                <tr>
                                    <td>
                                        @if ($followup->lead)
                                            <a href="{{ route('v2.leads.show', $followup->lead) }}" style="font-weight:900; text-decoration:none; color:var(--dark)">
                                                {{ $followup->lead->name }}
                                            </a>
                                        @else
                                            <span style="color:var(--muted)">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge">
                                            {{ $communicationLabels[$followup->communication_type] ?? $followup->communication_type }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $followup->employee_name ?: ($followup->user?->name ?? '—') }}</strong></td>
                                    <td><small>{{ $followup->followed_up_at ? $followup->followed_up_at->format('Y-m-d H:i') : '—' }}</small></td>
                                    <td>
                                        @php
                                            $stgName = $followup->toStatus?->stage?->name_ar ?? $followup->lead?->status?->stage?->name_ar ?? $followup->lead?->status?->name_ar ?? '—';
                                            $stgColor = $followup->toStatus?->stage?->color ?? $followup->lead?->status?->stage?->color ?? '#3478f6';
                                        @endphp
                                        <span class="badge" style="background:{{ $stgColor }}15; color:{{ $stgColor }}; border:1px solid {{ $stgColor }}30">
                                            {{ $stgName }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:24px; color:var(--muted)">
                                        {{ __('crm.no_followups_recorded') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <!-- Quick Actions Panel -->
            <article class="chart-card">
                <div class="panel-head">
                    <div>
                        <h2><i class="bi bi-lightning-charge"></i> {{ __('crm.quick_actions_title') }}</h2>
                        <p>{{ __('crm.quick_actions_desc') }}</p>
                    </div>
                </div>

                <div class="quick-actions-list">
                    @can('leads.create')
                        <a href="{{ route('v2.leads.create') }}" class="quick-action-link">
                            <i class="bi bi-person-plus-fill" style="color:#0284c7"></i>
                            <span>{{ __('crm.add_new_donor_lead') }}</span>
                        </a>
                    @endcan
                    @can('tasks.view')
                        <a href="{{ route('v2.tasks.daily') }}" class="quick-action-link">
                            <i class="bi bi-calendar-check-fill" style="color:#d97706"></i>
                            <span>{{ __('crm.today_followups_tasks') }}</span>
                        </a>
                    @endcan
                    <a href="{{ route('v2.leads.kanban') }}" class="quick-action-link">
                        <i class="bi bi-kanban-fill" style="color:#7e22ce"></i>
                        <span>{{ __('crm.leads_kanban_board') }}</span>
                    </a>
                    @can('leads.export')
                        <a href="{{ route('v2.leads.export') }}" class="quick-action-link">
                            <i class="bi bi-file-earmark-arrow-down-fill" style="color:#16a34a"></i>
                            <span>{{ __('crm.export_donors_data') }}</span>
                        </a>
                    @endcan
                    <a href="{{ route('v2.settings.stages.index') }}" class="quick-action-link">
                        <i class="bi bi-sliders" style="color:#475569"></i>
                        <span>{{ __('crm.donor_stages_settings') }}</span>
                    </a>
                </div>
            </article>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'Tajawal, Tahoma, Arial, sans-serif';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const chartAnimation = (duration, stagger) => {
        if (prefersReducedMotion) {
            return false;
        }

        return {
            duration,
            easing: 'easeOutQuart',
            delay(context) {
                if (context.type !== 'data' || context.mode === 'resize') {
                    return 0;
                }

                const dataDelay = context.dataIndex * stagger;
                const datasetDelay = context.datasetIndex * 70;
                return Math.min(dataDelay + datasetDelay, 210);
            }
        };
    };

    const animateChartWhenVisible = (canvas, config) => {
        const mountChart = () => {
            if (canvas.dataset.chartMounted === 'true') {
                return;
            }

            canvas.dataset.chartMounted = 'true';
            new Chart(canvas.getContext('2d'), config);
        };

        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            mountChart();
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            if (!entries.some(entry => entry.isIntersecting)) {
                return;
            }

            observer.disconnect();
            mountChart();
        }, {
            threshold: 0.2,
            rootMargin: '0px 0px -8% 0px'
        });

        observer.observe(canvas);
    };

    // 1. Chart 1 — Stage Donut Chart (Section 11)
    const donutCtx = document.getElementById('stageDonutChart');
    if (donutCtx) {
        const stageLabels = {!! json_encode($stageDistribution['labels']) !!};
        const stageData = {!! json_encode($stageDistribution['data']) !!};
        const stageColors = {!! json_encode($stageDistribution['colors']) !!};

        animateChartWhenVisible(donutCtx, {
            type: 'doughnut',
            data: {
                labels: stageLabels,
                datasets: [{
                    data: stageData,
                    backgroundColor: stageColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                animation: prefersReducedMotion ? false : {
                    ...chartAnimation(720, 24),
                    animateRotate: true,
                    animateScale: true
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
                        labels: {
                            color: '#64748b',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 10,
                            boxWidth: 8,
                            font: { size: 11, weight: '700' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Chart 2 — Monthly Trend Activity Chart (Section 11)
    const trendCtx = document.getElementById('activityTrendChart');
    if (trendCtx) {
        const monthsData = {!! json_encode($monthsTrend) !!};
        const labels = monthsData.map(m => m.label);
        const newCounts = monthsData.map(m => m.new_count);
        const donorCounts = monthsData.map(m => m.donor_count);
        const donationVals = monthsData.map(m => m.donation_value);

        animateChartWhenVisible(trendCtx, {
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'line',
                        label: @json(__('crm.donations_value_egp')),
                        data: donationVals,
                        yAxisID: 'y1',
                        borderColor: '#f59e0b',
                        backgroundColor: '#f59e0b',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        tension: 0.25,
                    },
                    {
                        type: 'bar',
                        label: @json(__('crm.new_leads_chart')),
                        data: newCounts,
                        yAxisID: 'y',
                        backgroundColor: '#38bdf8',
                        borderRadius: 6,
                        barThickness: 14,
                    },
                    {
                        type: 'bar',
                        label: @json(__('crm.donors_chart')),
                        data: donorCounts,
                        yAxisID: 'y',
                        backgroundColor: '#22c55e',
                        borderRadius: 6,
                        barThickness: 14,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: chartAnimation(680, 28),
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }},
                        labels: {
                            color: '#64748b',
                            usePointStyle: true,
                            padding: 14,
                            font: { size: 12, weight: '700' }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: '{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}',
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: { color: '#64748b', precision: 0 }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: '{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#d97706' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                }
            }
        });
    }

    // Set Live Date
    try {
        const dateEl = document.getElementById('currentLiveDate');
        if (dateEl) {
            const currentLocale = @json(app()->getLocale() === 'ar' ? 'ar-EG' : 'en-US');
            const liveSubtitle = @json(__('crm.dashboard_live_tracking_subtitle'));
            const formatted = new Intl.DateTimeFormat(currentLocale, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }).format(new Date());
            dateEl.textContent = `${formatted} — ${liveSubtitle}`;
        }
    } catch (e) {}
});
</script>
</body>
</html>

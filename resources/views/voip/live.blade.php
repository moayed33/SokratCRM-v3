<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('crm.live_pbx_title') }} — SokratCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
    <style>
        :root {
            --red: #dc2637;
            --green: #16a34a;
            --blue: #2563eb;
            --amber: #d97706;
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

        .live-pulse-dot { width: 12px; height: 12px; border-radius: 50%; background: #dc2637; display: inline-block; box-shadow: 0 0 0 4px rgba(220,38,55,0.25); flex-shrink: 0; animation: pbxPulse 1.8s infinite; }
        @keyframes pbxPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 2px rgba(220,38,55,0.4); }
            50% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(220,38,55,0.1); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 2px rgba(220,38,55,0.4); }
        }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 0 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); color: var(--dark); font-weight: 800; text-decoration: none; cursor: pointer; font-family: inherit; font-size: 13px; transition: all .15s ease; }
        .topbar .btn, .top-actions .btn { height: 42px; min-height: 42px; box-sizing: border-box; }
        .btn:hover { background: var(--bg); transform: translateY(-1px); }
        .btn.primary { background: var(--red); border-color: var(--red); color: #fff; }
        .btn.primary:hover { background: #b81829; border-color: #b81829; color: #fff; }
        .btn.soft { background: var(--bg); border-color: var(--line); color: var(--dark); }

        .panel { background: transparent !important; border: 0 !important; border-radius: 16px; margin-bottom: 24px; box-shadow: none !important; }
        .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; background: transparent !important; border: 0 !important; }

        /* PBX Metrics Strip */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: grid; place-items: center; font-size: 22px; flex-shrink: 0; }
        .stat-icon.total { background: #eff6ff; color: #2563eb; }
        .stat-icon.online { background: #f0fdf4; color: #16a34a; }
        .stat-icon.calls { background: #fff1f2; color: #dc2637; }
        .stat-icon.offline { background: #f1f5f9; color: #64748b; }
        .stat-details { display: flex; flex-direction: column; gap: 4px; }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 800; }
        .stat-value { font-size: 26px; font-weight: 900; color: var(--dark); font-variant-numeric: tabular-nums; }

        /* Filter & Search Bar */
        .filter-search-bar { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        .filter-buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-btn { background: var(--bg); border: 1px solid var(--line); padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 800; color: var(--muted); cursor: pointer; transition: all 0.2s ease; text-align: center; }
        .filter-btn:hover { background: #e2e8f0; color: var(--dark); }
        .filter-btn.active { background: var(--dark); color: #fff; border-color: var(--dark); }

        .search-box { position: relative; flex: 1 1 280px; max-width: 380px; }
        .search-box i { position: absolute; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 15px; }
        [dir="rtl"] .search-box i { right: 14px; }
        [dir="ltr"] .search-box i { left: 14px; }
        .search-input { width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 10px; font-size: 13px; outline: none; transition: border-color 0.2s ease; background: var(--card); color: var(--dark); font: inherit; }
        [dir="rtl"] .search-input { padding: 0 38px 0 14px; }
        [dir="ltr"] .search-input { padding: 0 14px 0 38px; }
        .search-input:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(220,38,55,0.12); }

        /* PBX Extensions Cards Grid */
        .extensions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
        .ext-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; gap: 16px; transition: transform .18s, box-shadow .18s, border-color .18s; box-shadow: 0 4px 16px rgba(0,0,0,0.03); }
        .ext-card:hover { transform: translateY(-2px); border-color: #cbd5e1; box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        
        .ext-header { display: grid; grid-template-columns: minmax(0, 1fr) max-content; align-items: start; gap: 12px; }
        .ext-user-info { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .ext-avatar { width: 44px; height: 44px; border-radius: 14px; background: #e0e7ff; color: #3730a3; display: grid; place-items: center; font-weight: 900; font-size: 16px; flex-shrink: 0; }
        .ext-meta { min-width: 0; flex: 1; overflow: hidden; }
        .ext-name { display: block; min-width: 0; width: 100%; font-weight: 900; font-size: 15px; color: var(--dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ext-number { display: block; min-width: 0; font-size: 12px; color: var(--muted); font-weight: 700; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .status-badge { position: static; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; padding: 5px 12px; border-radius: 999px; font-size: 11px; font-weight: 900; line-height: 1.2; white-space: nowrap; justify-self: end; flex-shrink: 0; }
        .status-badge.online { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-badge.incall { background: #fef2f2; color: #dc2637; border: 1px solid #fecdd3; animation: callPulse 1.6s infinite; }
        .status-badge.offline { background: var(--bg); color: var(--muted); border: 1px solid var(--line); }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .status-badge.online .status-dot { background: #16a34a; }
        .status-badge.incall .status-dot { background: #dc2637; }
        .status-badge.offline .status-dot { background: #94a3b8; }
        @keyframes callPulse {
            0% { box-shadow: 0 0 0 0 rgba(220,38,55,0.4); }
            70% { box-shadow: 0 0 0 6px rgba(220,38,55,0); }
            100% { box-shadow: 0 0 0 0 rgba(220,38,55,0); }
        }

        .ext-details { display: flex; flex-direction: column; gap: 8px; background: var(--bg); padding: 12px 14px; border-radius: 12px; font-size: 13px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; color: var(--muted); }
        .detail-row strong { color: var(--dark); font-weight: 800; font-size: 12px; }


        .banner-box { padding: 16px 20px; border-radius: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .banner-box.error { background: rgba(220,38,55,0.08); border: 1px solid rgba(220,38,55,0.25); color: var(--dark); }

        /* Dark Mode */
        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f4f4f5;
            --muted: #a1a1aa;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f4f4f5 !important; }
        html.dark-mode .stat-card,
        html.dark-mode .filter-search-bar,
        html.dark-mode .ext-card {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #f4f4f5 !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4) !important;
        }
        html.dark-mode .stat-icon.total { background: rgba(37,99,235,0.18) !important; color: #60a5fa !important; }
        html.dark-mode .stat-icon.online { background: rgba(22,163,74,0.18) !important; color: #4ade80 !important; }
        html.dark-mode .stat-icon.calls { background: rgba(220,38,55,0.18) !important; color: #f87171 !important; }
        html.dark-mode .stat-icon.offline { background: rgba(255,255,255,0.05) !important; color: #a1a1aa !important; }
        html.dark-mode .ext-details,
        html.dark-mode .filter-btn,
        html.dark-mode .btn {
            background: #27272a !important;
            border-color: rgba(255,255,255,0.1) !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode .filter-btn.active {
            background: #dc2637 !important;
            border-color: #dc2637 !important;
            color: #fff !important;
        }
        html.dark-mode .search-input {
            background: #18181b !important;
            border-color: rgba(255,255,255,0.12) !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode .ext-avatar {
            background: #27272a !important;
            color: #f4f4f5 !important;
        }
        html.dark-mode .status-badge.online {
            background: rgba(34,197,94,0.16) !important;
            border-color: rgba(34,197,94,0.35) !important;
            color: #4ade80 !important;
        }
        html.dark-mode .status-badge.incall {
            background: rgba(220,38,55,0.18) !important;
            border-color: rgba(220,38,55,0.4) !important;
            color: #f87171 !important;
        }
        html.dark-mode .status-badge.offline {
            background: #27272a !important;
            border-color: rgba(255,255,255,0.08) !important;
            color: #a1a1aa !important;
        }

        /* Monochrome Mode */
        html.crm-monochrome:not(.dark-mode) .stat-card,
        html.crm-monochrome:not(.dark-mode) .filter-search-bar,
        html.crm-monochrome:not(.dark-mode) .ext-card {
            background: #fff !important;
            border-color: #e5e5e5 !important;
            box-shadow: none !important;
        }
        html.crm-monochrome.dark-mode .stat-card,
        html.crm-monochrome.dark-mode .filter-search-bar,
        html.crm-monochrome.dark-mode .ext-card {
            background: #1c1c1c !important;
            border-color: #333 !important;
            box-shadow: none !important;
        }

        @media(max-width: 768px) {
            .crm-main { padding: 16px; }
            .filter-search-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: none; }
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
                <span class="live-pulse-dot"></span>
                <div>
                    <h1>{{ app()->getLocale() === 'en' ? 'Live PBX Monitor' : 'لوحة المراقبة المباشرة للسنترال' }}</h1>
                    <p>{{ app()->getLocale() === 'en' ? 'Real-time VoIP PBX extensions and live telephony status' : 'مراقبة فورية لحالة تحويلات السنترال والخطوط والمكالمات الجارية' }}</p>
                </div>
            </div>
            <div class="top-actions">
                @can('reports.view')
                    @can('voip.view')
                        <a href="{{ route('v2.reports.voip') }}" class="btn soft">
                            <i class="bi bi-bar-chart-line"></i>
                            {{ __('crm.voip_team_report') }}
                        </a>
                    @endcan
                @endcan
                <span class="btn soft" style="cursor: default;">
                    <i class="bi bi-person-badge"></i>
                    {{ app()->getLocale() === 'en' ? 'System Admin' : 'مدير النظام' }}
                </span>
                <a href="{{ route('dashboard') }}" class="btn soft">
                    {{ __('crm.back_to_dashboard') }}
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        <div class="monitor-container">
            @if(empty($isConfigured))
                <div class="banner-box error">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="bi bi-telephone-x-fill" style="font-size: 24px; color: var(--red);"></i>
                        <div>
                            <strong style="display: block; font-size: 14px;">{{ app()->getLocale() === 'en' ? 'VoIP PBX Server Disconnected' : 'سيرفر السنترال غير متصل' }}</strong>
                            <small style="color: var(--muted); font-size: 12px;">{{ app()->getLocale() === 'en' ? 'Showing local extension mappings. Pair with PBX server in settings for live call streaming.' : 'يتم عرض التحويلات المسجلة محلياً. يرجى ربط السنترال لمتابعة المكالمات الحية.' }}</small>
                        </div>
                    </div>
                    @can('voip.settings')
                        <a href="{{ route('v2.settings.voip') }}" class="btn primary">
                            <i class="bi bi-gear-fill"></i>
                            {{ app()->getLocale() === 'en' ? 'VoIP Settings' : 'إعدادات السنترال' }}
                        </a>
                    @endcan
                </div>
            @elseif(!empty($errorMessage))
                <div class="banner-box error">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 22px; color: var(--red);"></i>
                        <span>{{ $errorMessage }}</span>
                    </div>
                </div>
            @endif

            <!-- 4-Stat Metric Strip -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total"><i class="bi bi-telephone"></i></div>
                    <div class="stat-details">
                        <span class="stat-label">{{ app()->getLocale() === 'en' ? 'Total Extensions' : 'إجمالي التحويلات' }}</span>
                        <span class="stat-value" id="countTotal">{{ $counts['total'] ?? $extensions->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon online"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-details">
                        <span class="stat-label">{{ app()->getLocale() === 'en' ? 'Online Lines' : 'الخطوط المتصلة' }}</span>
                        <span class="stat-value" id="countOnline">{{ $counts['online'] ?? $extensions->where('online', true)->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon calls"><i class="bi bi-telephone-outbound-fill"></i></div>
                    <div class="stat-details">
                        <span class="stat-label">{{ app()->getLocale() === 'en' ? 'Active Calls' : 'المكالمات الجارية' }}</span>
                        <span class="stat-value" id="countInCall">{{ $counts['in_call'] ?? $extensions->where('in_call', true)->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon offline"><i class="bi bi-telephone-x"></i></div>
                    <div class="stat-details">
                        <span class="stat-label">{{ app()->getLocale() === 'en' ? 'Offline' : 'غير متصل' }}</span>
                        <span class="stat-value" id="countOffline">{{ $counts['offline'] ?? $extensions->where('online', false)->where('in_call', false)->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="filter-search-bar">
                <div class="filter-buttons" id="liveFilterButtons">
                    <button type="button" class="filter-btn active" data-filter="all">
                        {{ app()->getLocale() === 'en' ? 'All' : 'الكل' }} (<span id="filterCountAll">{{ $counts['total'] ?? $extensions->count() }}</span>)
                    </button>
                    <button type="button" class="filter-btn" data-filter="online">
                        {{ app()->getLocale() === 'en' ? 'Online' : 'متصل' }} (<span id="filterCountOnline">{{ $counts['online'] ?? $extensions->where('online', true)->count() }}</span>)
                    </button>
                    <button type="button" class="filter-btn" data-filter="incall">
                        {{ app()->getLocale() === 'en' ? 'In Call' : 'في مكالمة' }} (<span id="filterCountInCall">{{ $counts['in_call'] ?? $extensions->where('in_call', true)->count() }}</span>)
                    </button>
                    <button type="button" class="filter-btn" data-filter="offline">
                        {{ app()->getLocale() === 'en' ? 'Offline' : 'غير متصل' }} (<span id="filterCountOffline">{{ $counts['offline'] ?? $extensions->where('online', false)->where('in_call', false)->count() }}</span>)
                    </button>
                </div>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="liveSearchInput" class="search-input" placeholder="{{ app()->getLocale() === 'en' ? 'Search by name or extension number...' : 'بحث بالاسم أو رقم التحويلة...' }}">
                </div>
            </div>

            <!-- Extensions Cards Grid -->
            <div class="extensions-grid" id="liveExtensionsGrid">
                @include('voip.partials.extension-grid', ['extensions' => $extensions])
            </div>
        </div>

        <script>
            (() => {
                const searchInput = document.getElementById('liveSearchInput');
                const filterButtons = document.querySelectorAll('#liveFilterButtons .filter-btn');
                const grid = document.getElementById('liveExtensionsGrid');

                let activeFilter = 'all';
                let searchQuery = '';

                const filterCards = () => {
                    if (!grid) return;
                    const cards = grid.querySelectorAll('.ext-card');
                    cards.forEach((card) => {
                        const state = card.dataset.state;
                        const text = (card.dataset.search || '').toLowerCase();
                        const matchesFilter = activeFilter === 'all' || state === activeFilter;
                        const matchesSearch = !searchQuery || text.includes(searchQuery);

                        card.style.display = (matchesFilter && matchesSearch) ? 'flex' : 'none';
                    });
                };

                filterButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        filterButtons.forEach((b) => b.classList.remove('active'));
                        btn.classList.add('active');
                        activeFilter = btn.dataset.filter || 'all';
                        filterCards();
                    });
                });

                searchInput?.addEventListener('input', (e) => {
                    searchQuery = (e.target.value || '').trim().toLowerCase();
                    filterCards();
                });

                const updateMetrics = (counts) => {
                    if (!counts) return;
                    const countTotal = document.getElementById('countTotal');
                    const countOnline = document.getElementById('countOnline');
                    const countInCall = document.getElementById('countInCall');
                    const countOffline = document.getElementById('countOffline');
                    const filterCountAll = document.getElementById('filterCountAll');
                    const filterCountOnline = document.getElementById('filterCountOnline');
                    const filterCountInCall = document.getElementById('filterCountInCall');
                    const filterCountOffline = document.getElementById('filterCountOffline');

                    if (countTotal) countTotal.textContent = counts.total;
                    if (countOnline) countOnline.textContent = counts.online;
                    if (countInCall) countInCall.textContent = counts.in_call;
                    if (countOffline) countOffline.textContent = counts.offline;
                    if (filterCountAll) filterCountAll.textContent = counts.total;
                    if (filterCountOnline) filterCountOnline.textContent = counts.online;
                    if (filterCountInCall) filterCountInCall.textContent = counts.in_call;
                    if (filterCountOffline) filterCountOffline.textContent = counts.offline;
                };

                let isPolling = false;
                const pollLive = async () => {
                    if (document.hidden || isPolling || !grid) return;
                    isPolling = true;
                    try {
                        const res = await fetch('{{ route('v2.voip.live.data') }}', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && typeof data.html === 'string') {
                                grid.innerHTML = data.html;
                                updateMetrics(data.counts);
                                filterCards();
                            }
                        }
                    } catch (e) {
                        // Silently retry on next tick
                    } finally {
                        isPolling = false;
                    }
                };

                const pollInterval = setInterval(pollLive, 3500);
                window.addEventListener('beforeunload', () => clearInterval(pollInterval));
            })();
        </script>
    </main>
</div>
<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

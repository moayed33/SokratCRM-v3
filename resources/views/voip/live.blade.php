<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('crm.live_pbx_title') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        :root {
            --red: #dc2637;
            --green: #16a34a;
            --blue: #2563eb;
            --amber: #d97706;
            --dark: #182033;
            --muted: #7e899b;
            --line: #e4e8ef;
            --bg: #f4f6f9;
            --card: #fff;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--dark); font-family: var(--font-primary, system-ui, -apple-system, sans-serif); }
        .crm-app { min-height: 100vh; display: flex; }
        .crm-main { flex: 1; min-width: 0; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; text-align: start; }
        .top-bar { background: #fff; border-bottom: 1px solid var(--line); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; text-align: start; }
        .top-bar-info { display: flex; align-items: center; gap: 12px; text-align: start; }
        .top-bar h1 { font-size: 20px; margin: 0; display: flex; align-items: center; gap: 10px; text-align: start; }
        .top-bar p { margin: 4px 0 0; color: var(--muted); font-size: 13px; text-align: start; }
        .live-dot { width: 10px; height: 10px; border-radius: 50%; background: #dc2637; display: inline-block; box-shadow: 0 0 0 3px rgba(220,38,55,0.2); flex-shrink: 0; }
        .top-bar-actions { display: flex; align-items: center; gap: 12px; margin-inline-start: auto; }
        .role-badge { background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .role-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: #4f46e5; }
        .btn-back { background: #fff; border: 1px solid var(--line); padding: 8px 14px; border-radius: 10px; text-decoration: none; color: var(--dark); font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }

        .monitor-container { padding: 24px; display: flex; flex-direction: column; gap: 24px; max-width: 1400px; margin: 0 auto; width: 100%; text-align: start; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .stat-card { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 20px; display: flex; align-items: center; gap: 16px; text-align: start; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .stat-icon.total { background: #eff6ff; color: #2563eb; }
        .stat-icon.online { background: #f0fdf4; color: #16a34a; }
        .stat-icon.calls { background: #fff1f2; color: #dc2637; }
        .stat-details { display: flex; flex-direction: column; gap: 4px; text-align: start; }
        .stat-label { color: var(--muted); font-size: 13px; font-weight: 500; text-align: start; }
        .stat-value { font-size: 24px; font-weight: 700; color: var(--dark); text-align: start; }

        .filter-search-bar { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; text-align: start; }
        .filter-buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; text-align: start; }
        .filter-btn { background: #f8fafc; border: 1px solid var(--line); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s ease; text-align: center; }
        .filter-btn:hover { background: #f1f5f9; color: var(--dark); }
        .filter-btn.active { background: var(--dark); color: #fff; border-color: var(--dark); }

        .search-box { position: relative; flex: 1; max-width: 360px; min-width: 240px; }
        .search-input { width: 100%; padding: 9px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; outline: none; transition: border-color 0.2s ease; text-align: start; }
        .search-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        .extensions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; text-align: start; }
        .ext-card { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 20px; display: flex; flex-direction: column; gap: 16px; transition: box-shadow 0.2s ease, border-color 0.2s ease; text-align: start; }
        .ext-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
        .ext-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; text-align: start; }
        .ext-user-info { display: flex; align-items: center; gap: 12px; text-align: start; }
        .ext-avatar { width: 42px; height: 42px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .ext-meta { display: flex; flex-direction: column; gap: 2px; text-align: start; }
        .ext-name { font-weight: 700; font-size: 15px; color: var(--dark); text-align: start; }
        .ext-number { font-size: 13px; color: var(--muted); text-align: start; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-inline-start: auto; }
        .status-badge.online { background: #f0fdf4; color: #16a34a; }
        .status-badge.incall { background: #fff1f2; color: #dc2637; }
        .status-badge.offline { background: #f8fafc; color: #94a3b8; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .status-badge.online .status-dot { background: #16a34a; }
        .status-badge.incall .status-dot { background: #dc2637; animation: pulse 1.5s infinite; }
        .status-badge.offline .status-dot { background: #94a3b8; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.4; }
            100% { opacity: 1; }
        }

        .ext-details { display: flex; flex-direction: column; gap: 8px; background: #f8fafc; padding: 12px; border-radius: 10px; font-size: 13px; text-align: start; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; color: var(--muted); text-align: start; }
        .detail-row strong { color: var(--dark); }

        .call-actions-wrap { border-top: 1px solid var(--line); padding-top: 14px; margin-top: 4px; display: flex; flex-direction: column; gap: 10px; text-align: start; }
        .call-actions-title { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; text-align: start; }
        .call-actions-btns { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .action-btn { padding: 7px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid var(--line); background: #fff; color: var(--dark); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.15s ease; text-align: center; }
        .action-btn:hover { background: #f1f5f9; }
        .action-btn.hangup { background: #fff1f2; color: #dc2637; border-color: #fecdd3; }
        .action-btn.hangup:hover { background: #ffe4e6; }

        .iframe-wrap { flex: 1; width: 100%; height: 100%; background: #f8fafc; border: 0; }
        iframe { width: 100%; height: 100%; border: 0; }
        .error-card { margin: 40px auto; max-width: 500px; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 30px; text-align: center; }
        .error-card h2 { color: var(--red); margin-top: 0; }
    </style>
</head>
<body>
@include('partials.page-loader')
<div class="crm-app">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        <header class="top-bar">
            <div class="top-bar-info">
                <h1>
                    <span class="live-dot"></span>
                    <span>{{ __('لوحة المراقبة المباشرة للسنترال') }}</span>
                </h1>
                <p>{{ __('مراقبة فورية للتحويلات والمكالمات في السنترال') }}</p>
            </div>
            <div class="top-bar-actions">
                <span class="role-badge">
                    <span class="role-badge-dot"></span>
                    {{ __('مدير النظام') }}
                </span>
                <a href="{{ route('dashboard') }}" class="btn-back">
                    {{ __('crm.back_to_dashboard') }}
                </a>
                @include('partials.profile-dropdown')
            </div>
        </header>

        @php
            $currentLocale = app()->getLocale();
            $localizedEmbedUrl = !empty($embedUrl) ? preg_replace('/([?&])lang=[^&]*/', '$1lang=' . $currentLocale, $embedUrl) : null;
        @endphp

        @if(!empty($localizedEmbedUrl))
            <div class="iframe-wrap">
                <iframe src="{{ $localizedEmbedUrl }}" allow="microphone; autoplay" title="{{ __('لوحة المراقبة المباشرة للسنترال') }}"></iframe>
            </div>
        @else
            <div class="monitor-container">
                <!-- Stat Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">📞</div>
                        <div class="stat-details">
                            <span class="stat-label">{{ __('إجمالي التحويلات') }}</span>
                            <span class="stat-value">12</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon online">🟢</div>
                        <div class="stat-details">
                            <span class="stat-label">{{ __('الخطوط المتصلة') }}</span>
                            <span class="stat-value">8</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon calls">🔴</div>
                        <div class="stat-details">
                            <span class="stat-label">{{ __('المكالمات الجارية') }}</span>
                            <span class="stat-value">3</span>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="filter-search-bar">
                    <div class="filter-buttons">
                        <button type="button" class="filter-btn active">{{ __('الكل') }}</button>
                        <button type="button" class="filter-btn">{{ __('متصل') }}</button>
                        <button type="button" class="filter-btn">{{ __('في مكالمة') }}</button>
                        <button type="button" class="filter-btn">{{ __('غير متصل') }}</button>
                    </div>
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="{{ __('بحث بالاسم أو رقم التحويلة...') }}">
                    </div>
                </div>

                <!-- Extensions Cards Grid -->
                <div class="extensions-grid">
                    <!-- Extension Card 1: Active Call -->
                    <div class="ext-card">
                        <div class="ext-header">
                            <div class="ext-user-info">
                                <div class="ext-avatar">أحمد</div>
                                <div class="ext-meta">
                                    <span class="ext-name">أحمد محمود</span>
                                    <span class="ext-number">{{ __('التحويلة :') }} 101</span>
                                </div>
                            </div>
                            <span class="status-badge incall">
                                <span class="status-dot"></span>
                                {{ __('في مكالمة') }}
                            </span>
                        </div>
                        <div class="ext-details">
                            <div class="detail-row">
                                <span>{{ __('مدة المكالمة') }}:</span>
                                <strong>02:45</strong>
                            </div>
                            <div class="detail-row">
                                <span>{{ __('الطرف الآخر') }}:</span>
                                <strong dir="ltr">+201012345678</strong>
                            </div>
                        </div>
                        <div class="call-actions-wrap">
                            <h4 class="call-actions-title">{{ __('إجراءات المكالمة') }}</h4>
                            <div class="call-actions-btns">
                                <button type="button" class="action-btn">🎧 {{ __('استماع') }}</button>
                                <button type="button" class="action-btn">💬 {{ __('همس') }}</button>
                                <button type="button" class="action-btn">📢 {{ __('تدخل') }}</button>
                                <button type="button" class="action-btn hangup">⏹ {{ __('إنهاء المكالمة') }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Extension Card 2: Online -->
                    <div class="ext-card">
                        <div class="ext-header">
                            <div class="ext-user-info">
                                <div class="ext-avatar">سارة</div>
                                <div class="ext-meta">
                                    <span class="ext-name">سارة علي</span>
                                    <span class="ext-number">{{ __('التحويلة :') }} 102</span>
                                </div>
                            </div>
                            <span class="status-badge online">
                                <span class="status-dot"></span>
                                {{ __('متصل') }}
                            </span>
                        </div>
                        <div class="ext-details">
                            <div class="detail-row">
                                <span>{{ __('حالة الخط') }}:</span>
                                <strong>{{ __('جاهز لاستقبال المكالمات') }}</strong>
                            </div>
                        </div>
                        <div class="call-actions-wrap">
                            <h4 class="call-actions-title">{{ __('إجراءات المكالمة') }}</h4>
                            <div class="call-actions-btns">
                                <button type="button" class="action-btn" disabled style="opacity:0.5;cursor:not-allowed">🎧 {{ __('استماع') }}</button>
                                <button type="button" class="action-btn" disabled style="opacity:0.5;cursor:not-allowed">💬 {{ __('همس') }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Extension Card 3: Offline -->
                    <div class="ext-card">
                        <div class="ext-header">
                            <div class="ext-user-info">
                                <div class="ext-avatar">محمد</div>
                                <div class="ext-meta">
                                    <span class="ext-name">محمد حسن</span>
                                    <span class="ext-number">{{ __('التحويلة :') }} 103</span>
                                </div>
                            </div>
                            <span class="status-badge offline">
                                <span class="status-dot"></span>
                                {{ __('غير متصل') }}
                            </span>
                        </div>
                        <div class="ext-details">
                            <div class="detail-row">
                                <span>{{ __('حالة الخط') }}:</span>
                                <strong>{{ __('غير متصل بالسيرفر') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
</body>
</html>

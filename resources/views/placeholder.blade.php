<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>SokratCRM — {{ $title ?? __('crm.app_name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-theme-matrix-v4">
    <style>
        *{box-sizing:border-box}
        body{
            margin:0;
            min-width:320px;
            background:var(--bg,#f8f9fa);
            color:var(--text-main,#111827);
            font-family:var(--font-primary)
        }
        a{color:inherit}
        .placeholder-main{
            flex:1;
            min-width:0;
            padding:24px clamp(20px,2.4vw,32px) 56px;
            animation:v2FadeSlideIn .55s ease-out both
        }
        .placeholder-topbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            margin-bottom:24px;
            flex-wrap:wrap
        }
        .placeholder-topbar h1{margin:0;font-size:24px;font-weight:900;color:var(--dark,var(--text-main))}
        .placeholder-topbar p{margin:4px 0 0;color:var(--muted);font-size:13px}
        .top-card,.empty-card{
            background:var(--card,#fff);
            border:1px solid var(--border-color,#e5e7eb);
            border-radius:14px;
            box-shadow:var(--shadow-card,0 12px 28px rgba(15,23,42,.05));
            padding:28px;
            margin-bottom:20px;
            animation:v2FadeSlideIn .62s ease-out both
        }
        .top-card h2{margin:0 0 8px;font-size:30px;color:var(--dark,var(--text-main))}
        .top-card p{margin:0;color:var(--muted)}
        .btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            min-height:46px;
            padding:0 18px;
            border-radius:11px;
            border:1px solid var(--border-color,#e5e7eb);
            background:var(--bg-input,#fff);
            color:var(--text-main,#111827);
            text-decoration:none;
            font-weight:800
        }
        .empty-card{
            min-height:260px;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:12px;
            color:var(--muted);
            font-weight:800
        }
        .empty-card i{font-size:44px;color:var(--red,#dc2637);opacity:.5}
        @keyframes v2FadeSlideIn{
            from{opacity:0;transform:translateY(14px)}
            to{opacity:1;transform:translateY(0)}
        }
    </style>
</head>
<body>
@include('partials.page-loader')
<div class="app">
    @include('partials.crm-sidebar')

    <main class="placeholder-main">
        <header class="placeholder-topbar">
            <div class="topbar-left">
                <div>
                    <h1>{{ $title ?? 'SokratCRM' }}</h1>
                    <p>{{ __('crm.placeholder_version_notice') }}</p>
                </div>
            </div>
            <div class="top-actions">
                @include('partials.profile-dropdown')
            </div>
        </header>

        <section class="empty-card">
            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            <span>{{ __('crm.placeholder_future_build_notice') }}</span>
        </section>
    </main>
</div>

<script src="{{ asset('crm-sidebar.js') }}"></script>
</body>
</html>

<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>SokratCRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @once
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    @endonce
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--font-primary);
            background: #f3f6fb;
            color: #111827;
        }
        .layout {
            display: flex;
            min-height: 100vh;
            flex-direction: row-reverse;
        }
        .sidebar {
            width: 260px;
            min-width: 260px;
            background: #fff;
            border-inline-end: 1px solid #e5e7eb;
            padding: 24px 18px;
        }
        .brand { text-align: center; margin-bottom: 28px; }
        .brand h1 { color: #e11d2e; font-size: 30px; font-weight: 900; margin: 0; }
        .brand span { color: #7b8794; font-size: 13px; }
        .main {
            flex: 1;
            padding: 28px;
            animation: v2FadeSlideIn .55s ease-out both;
        }
        .top-card, .empty-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(15,23,42,.05);
            padding: 28px;
            margin-bottom: 20px;
            animation: v2FadeSlideIn .62s ease-out both;
        }
        .top-card h2 { margin: 0 0 8px; font-size: 30px; }
        .top-card p { margin: 0; color: #64748b; }
        .btn {
            display: inline-flex;
            align-items: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 11px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #111827;
            text-decoration: none;
            font-weight: 800;
        }
        .empty-card {
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-weight: 800;
        }
        @keyframes v2FadeSlideIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
@include('partials.page-loader')
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <h1>SokratCRM</h1>
            <span>لوحة التحكم</span>
        </div>
        <a class="btn" href="{{ route("dashboard") }}">العودة للوحة التحكم</a>
    </aside>
    <main class="main">
        <section class="top-card">
            <h2>{{ $title ?? 'SokratCRM' }}</h2>
            <p>هذه الصفحة داخل نسخة Laravel v2 ولن تفتح النظام القديم.</p>
        </section>
        <section class="empty-card">
            سيتم بناء هذه الصفحة في Laravel v2 لاحقاً.
        </section>
    </main>
</div>

<!-- CODEX V2 SIDEBAR ICON RIGHT TEXT CENTER NUMBER LEFT START -->
<style>
/* Force sidebar item layout:
   icon on right, text in middle, number/badge on left */
aside.sidebar .side-link,
aside.sidebar .side-toggle,
aside.sidebar .side-submenu a,
.sidebar .side-link,
.sidebar .side-toggle,
.sidebar .side-submenu a {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    !important;
    min-height: 54px !important;
    padding-inline-start: 58px !important;
    padding-inline-end: 70px !important;
    gap: 0 !important;
}

aside.sidebar .side-icon,
aside.sidebar .mini-icon,
.sidebar .side-icon,
.sidebar .mini-icon {
    position: absolute !important;
    right: 14px !important;
    left: auto !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    margin: 0 !important;
    order: 0 !important;
    flex: 0 0 34px !important;
}

aside.sidebar .side-link > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow),
aside.sidebar .side-toggle > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow),
aside.sidebar .side-submenu a > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow),
.sidebar .side-link > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow),
.sidebar .side-toggle > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow),
.sidebar .side-submenu a > span:not(.side-icon):not(.mini-icon):not(.badge):not(.side-arrow):not(.arrow):not(.chevron):not(.dropdown-arrow) {
    display: block !important;
    width: 100% !important;
    text-align: center !important;
    margin: 0 auto !important;
}

aside.sidebar .badge,
.sidebar .badge {
    position: absolute !important;
    left: 42px !important;
    right: auto !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    margin: 0 !important;
}

aside.sidebar .side-arrow,
aside.sidebar .arrow,
aside.sidebar .chevron,
aside.sidebar .dropdown-arrow,
.sidebar .side-arrow,
.sidebar .arrow,
.sidebar .chevron,
.sidebar .dropdown-arrow {
    position: absolute !important;
    left: 16px !important;
    right: auto !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    margin: 0 !important;
}

/* Submenu keeps same rhythm but slightly smaller */
aside.sidebar .side-submenu a,
.sidebar .side-submenu a {
    min-height: 48px !important;
    padding-inline-start: 54px !important;
    padding-inline-end: 28px !important;
}
</style>
<!-- CODEX V2 SIDEBAR ICON RIGHT TEXT CENTER NUMBER LEFT END -->

</body>
</html>

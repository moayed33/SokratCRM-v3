<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('crm.settings')) - SokratCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') . '?v=' . time() }}">
    <style>
        *{box-sizing:border-box}
        :root{--red:#dc2637;--ink:#172033;--muted:#697386;--line:#e5e9f0;--bg:#f4f6fa}
        body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--font-primary)}
        .settings-shell{min-height:100vh;display:flex;flex-direction:row}
        .settings-main{flex:1;min-width:0;padding:24px 30px 50px}
        .settings-top{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}
        .settings-heading{display:flex;align-items:center;gap:12px}.settings-menu{display:none;width:42px;height:42px;border:1px solid var(--line);border-radius:11px;background:#fff;color:var(--ink);font-size:20px;cursor:pointer}
        .settings-top h1{font-size:28px;margin:0 0 5px}.settings-top p{margin:0;color:var(--muted)}
        .settings-user-tools{display:flex;align-items:center;gap:10px}
        .current-user{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--line);padding:8px 10px;border-radius:13px}
        .current-user .avatar{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:#fff0f1;color:var(--red);font-weight:900}
        .current-user strong,.current-user small{display:block}.current-user small{color:var(--muted);margin-top:3px}
        .logout{border:0;background:transparent;color:var(--red);font-weight:900;cursor:pointer;padding:8px}
        .settings-tabs{display:flex;gap:8px;flex-wrap:wrap;background:#fff;border:1px solid var(--line);padding:8px;border-radius:15px;margin-bottom:22px}
        .settings-tabs a{color:#495367;text-decoration:none;font-weight:800;padding:11px 16px;border-radius:10px}
        .settings-tabs a.active,.settings-tabs a:hover{background:#fff0f1;color:var(--red)}
        .panel{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.04);padding:22px;margin-bottom:18px}
        .panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.panel-head h2{margin:0;font-size:21px}.panel-head p{margin:5px 0 0;color:var(--muted)}
        .grid{display:grid;gap:16px}.stats-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.stat-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}.stat-card b{display:block;font-size:30px;margin-top:10px}.stat-card span{color:var(--muted);font-weight:700}
        .action-card{text-decoration:none;color:inherit}.action-card:hover{border-color:#f3aab1;transform:translateY(-1px)}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:40px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--ink);font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit}
        .btn.primary{background:var(--red);border-color:var(--red);color:#fff}.btn.danger{color:#b42332;border-color:#f1bbc1}.btn.soft{background:#f7f8fa}.btn.small{min-height:34px;padding:0 10px;font-size:13px}
        .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.search{display:flex;gap:8px;flex:1}.search input{min-width:220px;flex:1}
        input,textarea,select{width:100%;border:1px solid #dbe1e9;border-radius:10px;padding:11px 12px;background:#fff;color:var(--ink);font:inherit;outline:none}input:focus,textarea:focus,select:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(220,38,55,.08)}textarea{min-height:100px;resize:vertical}
        label{display:block;font-weight:800;margin-bottom:7px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field.full{grid-column:1/-1}.hint{color:var(--muted);font-size:13px;margin-top:6px}
        .checkbox-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.check-card{display:flex;gap:9px;align-items:flex-start;border:1px solid var(--line);border-radius:11px;padding:12px}.check-card input{width:auto;margin-top:3px}.check-card strong,.check-card small{display:block}.check-card small{color:var(--muted);margin-top:3px}
        .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:760px}th,td{text-align:start;padding:13px 11px;border-bottom:1px solid #edf0f4;vertical-align:middle}th{color:var(--muted);font-size:13px;background:#fafbfc}.actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.actions form{margin:0}
        .badge{display:inline-flex;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:900;background:#eef2f7;color:#536078}.badge.active{background:#e7f8ed;color:#18733a}.badge.inactive{background:#fff0f1;color:#b42332}.badge.system{background:#fff5d9;color:#8a6100}
        .flash{border-radius:12px;padding:13px 15px;margin-bottom:16px;font-weight:800}.flash.success{background:#eaf9ef;color:#176b36;border:1px solid #bce8c9}.flash.error{background:#fff0f1;color:#a92030;border:1px solid #f2bbc1}.flash ul{margin:6px 0 0;padding-inline-start:20px}
        .pagination{margin-top:18px}.pagination nav>div:first-child{display:none}.pagination nav>div:last-child{display:flex;justify-content:space-between;align-items:center;gap:12px}.pagination a,.pagination span{font-size:13px}
        .permission-table th,.permission-table td{text-align:center;white-space:nowrap}.permission-table th:first-child,.permission-table td:first-child{text-align:start;position:sticky;right:0;background:#fff;z-index:1}.permission-table .module-row td{background:#f6f8fb!important;color:#4b5668;font-weight:900;text-align:start}.permission-table input{width:18px;height:18px;accent-color:var(--red)}
        @media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}.checkbox-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:900px){.settings-shell{display:block}.settings-main{width:100%;padding:18px}.settings-menu{display:inline-grid;place-items:center}.form-grid,.stats-grid,.checkbox-grid{grid-template-columns:1fr}.settings-top{align-items:flex-start;flex-direction:column}.settings-user-tools{width:100%}.current-user{flex:1;justify-content:space-between}}
    </style>
    @stack('head')
</head>
<body>
@include('partials.page-loader')
<div class="settings-shell">
    @include('partials.crm-sidebar')
    <button class="crm-overlay" id="settingsSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>
    <main class="settings-main">
        <header class="settings-top">
            <div class="settings-heading">
                <button class="settings-menu" id="settingsSidebarMenu" type="button" aria-label="{{ __('crm.open_menu') }}" aria-controls="crmSidebar" aria-expanded="false">☰</button>
                <div>
                    <h1>@yield('heading', __('crm.settings'))</h1>
                    <p>@yield('subheading', __('crm.manage_access_from_one_place'))</p>
                </div>
            </div>
            <div class="settings-user-tools">
                @include('partials.profile-dropdown')
            </div>
        </header>

        <nav class="settings-tabs">
            <a class="{{ request()->routeIs('v2.settings') ? 'active' : '' }}" href="{{ route('v2.settings') }}">{{ __('crm.overview') }}</a>
            <a class="{{ request()->routeIs('v2.settings.stages.*') ? 'active' : '' }}" href="{{ route('v2.settings.stages.index') }}">{{ __('crm.custom_pipeline_stages') }}</a>
            <a class="{{ request()->routeIs('v2.settings.fields.*') ? 'active' : '' }}" href="{{ route('v2.settings.fields.index') }}">{{ __('crm.lead_fields') }}</a>
            @can('branches.view')
                <a class="{{ request()->routeIs('v2.settings.branches.*') ? 'active' : '' }}" href="{{ route('v2.settings.branches.index') }}">{{ __('crm.branches') }}</a>
            @endcan
            @can('users.view')
                <a class="{{ request()->routeIs('v2.settings.users.*') ? 'active' : '' }}" href="{{ route('v2.settings.users.index') }}">{{ __('crm.users') }}</a>
            @endcan
            @can('groups.view')
                <a class="{{ request()->routeIs('v2.settings.groups.*') ? 'active' : '' }}" href="{{ route('v2.settings.groups.index') }}">{{ __('crm.groups') }}</a>
                <a class="{{ request()->routeIs('v2.settings.permissions.*') ? 'active' : '' }}" href="{{ route('v2.settings.permissions.index') }}">{{ __('crm.permissions') }}</a>
            @endcan
            @can('notifications.manage')
                <a class="{{ request()->routeIs('v2.settings.notifications.*') ? 'active' : '' }}" href="{{ route('v2.settings.notifications.index') }}">{{ __('crm.notifications') }}</a>
            @endcan
            @can('voip.settings')
                <a class="{{ request()->routeIs('v2.settings.voip') ? 'active' : '' }}" href="{{ route('v2.settings.voip') }}">{{ __('crm.voip_link') }}</a>
            @endcan
        </nav>

        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif
        @if (!empty($errors) && $errors->any())
            <div class="flash error">
                {{ __('crm.save_failed') }}
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
<script>
(() => {
    const menu = document.getElementById('settingsSidebarMenu');
    const overlay = document.getElementById('settingsSidebarOverlay');
    const close = () => {
        document.body.classList.remove('crm-side-open');
        menu?.setAttribute('aria-expanded', 'false');
    };
    menu?.addEventListener('click', () => {
        const open = document.body.classList.toggle('crm-side-open');
        menu.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    overlay?.addEventListener('click', close);
})();
</script>
@stack('scripts')
</body>
</html>

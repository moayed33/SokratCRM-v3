<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('crm.app_name') }} - {{ __('crm.login') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @once
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    @endonce
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-primary);
            background: #f3f5f9;
            color: #111827;
            display: grid;
            place-items: center;
        }
        .login-card {
            width: min(420px, calc(100vw - 32px));
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
        }
        .brand {
            text-align: center;
            margin-bottom: 26px;
        }
        .brand h1 {
            margin: 0;
            color: #e11d2e;
            font-size: 34px;
            font-weight: 900;
        }
        .brand p {
            margin: 8px 0 0;
            color: #8a94a6;
            font-size: 14px;
        }
        label {
            display: block;
            margin: 16px 0 8px;
            font-weight: 700;
            color: #374151;
        }
        input {
            width: 100%;
            height: 48px;
            border: 1px solid #d9e0ea;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            outline: none;
        }
        input:focus {
            border-color: #e11d2e;
            box-shadow: 0 0 0 4px rgba(225, 29, 46, .08);
        }
        button {
            width: 100%;
            margin-top: 24px;
            height: 50px;
            border: 0;
            border-radius: 12px;
            background: #e11d2e;
            color: #fff;
            font-weight: 900;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(225, 29, 46, .25);
        }
        .error {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 14px;
            font-weight: 700;
        }
    </style>
</head>
<body>
@include('partials.page-loader')
    <form class="login-card" method="post" action="{{ route('login.post') }}">
        @csrf
        <div class="brand">
            <h1>SokratCRM</h1>
            <p>{{ __('crm.crm_subtitle') }}</p>
        </div>

        @if ($errors->any())
            <div class="error">{{ __('crm.invalid_credentials') }}</div>
        @endif

        <label for="username">{{ __('crm.login_identifier_label') }}</label>
        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" placeholder="{{ __('crm.login_identifier_placeholder') }}" required autofocus>

        <label for="password">{{ __('crm.password') }}</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button type="submit">{{ __('crm.login') }}</button>
</body>
</html>

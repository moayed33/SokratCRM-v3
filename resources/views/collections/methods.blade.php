@extends('leads.transfer-layout')

@section('title', __('crm.manage_instant_methods'))
@section('page-title', __('crm.manage_instant_methods'))
@section('page-description', __('crm.manage_instant_methods_desc'))

@section('top-actions')
    <a class="btn soft" href="{{ route('v2.collections.index') }}">
        <i class="bi bi-arrow-right"></i> {{ __('crm.back_to_collections') }}
    </a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .payment-options-grid {
        display: grid;
        grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
        gap: 24px;
        align-items: start;
    }

    .config-card {
        background: var(--card, #fff);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
    }

    .config-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--line, #e2e8f0);
    }

    .config-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-size: 16px;
        font-weight: 900;
        color: var(--dark, #1e293b);
    }

    .config-card-title i {
        color: var(--red, #dc2637);
        font-size: 18px;
    }

    .config-form {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 12px;
        font-weight: 800;
        color: var(--muted, #64748b);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-group .req {
        color: var(--red, #dc2637);
    }

    .form-control {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 10px;
        background: var(--card, #fff);
        color: var(--dark, #1e293b);
        font: inherit;
        font-size: 13px;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }

    .form-control:focus {
        border-color: var(--red, #dc2637);
        box-shadow: 0 0 0 3px rgba(220,38,55,0.12);
    }

    .form-hint {
        font-size: 11px;
        color: var(--muted, #94a3b8);
        margin-top: 2px;
    }

    .methods-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .method-item-card {
        background: var(--bg, #f8fafc);
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 14px;
        padding: 16px 18px;
        transition: border-color .15s;
    }

    .method-item-card:hover {
        border-color: rgba(220,38,55,0.3);
    }

    .method-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--line, #e2e8f0);
    }

    .method-item-identity {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .method-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(220,38,55,0.08);
        color: var(--red, #dc2637);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .method-title-text strong {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: var(--dark, #1e293b);
    }

    .method-code-badge {
        display: inline-block;
        font-family: monospace;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 6px;
        background: rgba(100,116,139,0.1);
        color: var(--muted, #64748b);
        margin-top: 2px;
    }

    .method-item-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .method-item-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr 90px auto auto;
        gap: 10px;
        align-items: flex-end;
    }
    .method-accounts-section {
        padding-top: 10px;
        border-top: 1px dashed var(--line, #e2e8f0);
    }
    html.dark-mode .method-accounts-section {
        border-top-color: rgba(255, 255, 255, 0.08) !important;
    }

    .toggle-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 42px;
        cursor: pointer;
        user-select: none;
    }

    .toggle-wrap input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--red, #dc2637);
    }

    .toggle-wrap span {
        font-size: 12px;
        font-weight: 800;
        color: var(--dark, #1e293b);
    }

    /* Dark Mode */
    html.dark-mode .config-card {
        background: #18181b !important;
        border-color: rgba(255,255,255,0.08) !important;
        color: #f4f4f5 !important;
    }
    html.dark-mode .method-item-card {
        background: #27272a !important;
        border-color: rgba(255,255,255,0.08) !important;
    }
    html.dark-mode .form-control {
        background: #18181b !important;
        border-color: rgba(255,255,255,0.12) !important;
        color: #f4f4f5 !important;
    }
    html.dark-mode .config-card-head,
    html.dark-mode .method-item-header {
        border-color: rgba(255,255,255,0.08) !important;
    }
    html.dark-mode .method-title-text strong,
    html.dark-mode .toggle-wrap span {
        color: #f4f4f5 !important;
    }
    html.dark-mode .method-code-badge {
        background: rgba(255,255,255,0.08) !important;
        color: #a1a1aa !important;
    }
    html.dark-mode .method-icon-box {
        background: rgba(220,38,55,0.18) !important;
        color: #f87171 !important;
    }

    @media(max-width: 1024px) {
        .payment-options-grid {
            grid-template-columns: 1fr;
        }
    }
    @media(max-width: 768px) {
        .method-item-form-row {
            grid-template-columns: 1fr 1fr;
        }
        .method-item-form-row .form-group:first-child,
        .method-item-form-row .form-group:nth-child(2) {
            grid-column: span 2;
        }
        .method-item-form-row .toggle-wrap,
        .method-item-form-row .btn {
            grid-column: span 1;
        }
</style>
@endpush

@section('content')
<div class="payment-options-grid">
    <!-- 1. Add New Payment Option Card -->
    <section class="config-card">
        <div class="config-card-head">
            <h2 class="config-card-title">
                <i class="bi bi-plus-circle-fill"></i>
                {{ __('crm.add_instant_method') }}
            </h2>
        </div>

        @if(session('success'))
            <div class="alert success" style="margin-bottom: 16px;">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert danger" style="margin-bottom: 16px;">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}
            </div>
        @endif

        <form class="config-form" method="POST" action="{{ route('v2.collections.methods.store') }}">
            @csrf
            <div class="form-group">
                <label for="newMethodCode">
                    <i class="bi bi-code-slash"></i>
                    {{ __('crm.method_code') }} <span class="req">*</span>
                </label>
                <input
                    id="newMethodCode"
                    name="code"
                    value="{{ old('code') }}"
                    dir="ltr"
                    required
                    pattern="[a-z][a-z0-9_]*"
                    class="form-control"
                    placeholder="e.g. vodafone_cash, instapay"
                >
                <span class="form-hint">{{ __('crm.method_code_hint') ?? 'Unique slug: lowercase letters, numbers, and underscores.' }}</span>
            </div>

            <div class="form-group">
                <label for="newMethodNameAr">
                    <i class="bi bi-translate"></i>
                    {{ __('crm.arabic_name') }} <span class="req">*</span>
                </label>
                <input
                    id="newMethodNameAr"
                    name="name_ar"
                    value="{{ old('name_ar') }}"
                    required
                    class="form-control"
                    placeholder="{{ app()->getLocale() === 'en' ? 'Arabic Name' : 'مثال: فودافون كاش، إنستاباي' }}"
                >
            </div>

            <div class="form-group">
                <label for="newMethodNameEn">
                    <i class="bi bi-globe"></i>
                    {{ __('crm.english_name') }}
                </label>
                <input
                    id="newMethodNameEn"
                    name="name_en"
                    value="{{ old('name_en') }}"
                    class="form-control"
                    placeholder="e.g. Vodafone Cash, InstaPay"
                >
            </div>

            <div class="form-group">
                <label for="newMethodPosition">
                    <i class="bi bi-sort-numeric-down"></i>
                    {{ __('crm.position') }}
                </label>
                <input
                    id="newMethodPosition"
                    name="position"
                    type="number"
                    value="{{ old('position', 0) }}"
                    min="0"
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="newMethodAccounts">
                    {{ __('crm.method_accounts_and_numbers') }}
                </label>
                <textarea
                    id="newMethodAccounts"
                    name="accounts"
                    rows="3"
                    class="form-control"
                    placeholder="{{ __('crm.method_accounts_placeholder') }}"
                    style="width: 100%; font-family: inherit; font-size: 12px; resize: vertical;"
                >{{ old('accounts') }}</textarea>
                <span class="form-hint">{{ __('crm.method_accounts_hint') }}</span>
            </div>

            <button class="btn primary" type="submit" style="min-height: 42px; width: 100%; justify-content: center; font-weight: 800;">
                <i class="bi bi-plus-lg"></i> {{ __('crm.add') }}
            </button>
        </form>
    </section>

    <!-- 2. Configured Payment Options List Card -->
    <section class="config-card">
        <div class="config-card-head">
            <h2 class="config-card-title">
                <i class="bi bi-credit-card-2-front-fill"></i>
                {{ __('crm.instant_donation_method') }}
            </h2>
            <span class="badge" style="font-size: 12px; padding: 4px 10px;">
                {{ $methods->count() }}
            </span>
        </div>

        <div class="methods-list">
            @forelse($methods as $method)
                <div class="method-item-card">
                    <div class="method-item-header">
                        <div class="method-item-identity">
                            <div class="method-icon-box">
                                <i class="bi bi-credit-card-2-front"></i>
                            </div>
                            <div class="method-title-text">
                                <strong>{{ $method->name_ar }}</strong>
                                <span class="method-code-badge">{{ $method->code }}</span>
                            </div>
                        </div>
                        <span class="badge {{ $method->is_active ? 'active' : '' }}">
                            {{ $method->is_active ? __('crm.active') : __('crm.inactive') }}
                        </span>
                    </div>

                    <form class="method-item-form" method="POST" action="{{ route('v2.collections.methods.update', $method) }}">
                        @csrf
                        @method('PATCH')

                        <div class="method-item-form-row">
                            <div class="form-group">
                                <label><i class="bi bi-translate"></i> {{ __('crm.arabic_name') }}</label>
                                <input name="name_ar" value="{{ $method->name_ar }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label><i class="bi bi-globe"></i> {{ __('crm.english_name') }}</label>
                                <input name="name_en" value="{{ $method->name_en }}" class="form-control" placeholder="{{ $method->name_ar }}">
                            </div>

                            <div class="form-group">
                                <label><i class="bi bi-sort-numeric-down"></i> {{ __('crm.position') }}</label>
                                <input name="position" type="number" value="{{ $method->position }}" min="0" required class="form-control">
                            </div>

                            <label class="toggle-wrap" title="{{ __('crm.active') }}">
                                <input type="checkbox" name="is_active" value="1" @checked($method->is_active)>
                                <span>{{ __('crm.active') }}</span>
                            </label>

                            <button class="btn soft" type="submit" title="{{ __('crm.save') }}" style="height: 42px; padding: 0 14px; min-width: 42px;">
                                <i class="bi bi-check2"></i> <span class="hide-mobile">{{ __('crm.save') }}</span>
                            </button>
                        </div>

                        <div class="method-accounts-section">
                            <label style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; font-weight: 800; margin-bottom: 6px;">
                                <span><i class="bi bi-wallet2" style="color: var(--red, #dc2637)"></i> {{ __('crm.method_accounts_and_numbers') }}</span>
                                <small style="color: var(--muted); font-weight: 600;">{{ __('crm.one_account_per_line') }}</small>
                            </label>
                            <textarea
                                name="accounts"
                                class="form-control"
                                rows="2"
                                placeholder="{{ __('crm.method_accounts_placeholder') }}"
                                style="width: 100%; font-family: inherit; font-size: 12px; resize: vertical;"
                            >{{ is_array($method->accounts) ? implode("\n", $method->accounts) : '' }}</textarea>

                            @if(!empty($method->accounts) && is_array($method->accounts))
                                <div class="method-accounts-preview" style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                    @foreach($method->accounts as $acc)
                                        <span class="badge" style="font-size: 11px; padding: 3px 8px; background: rgba(52, 120, 246, 0.08); color: #2563eb; border: 1px solid rgba(52, 120, 246, 0.2);">
                                            <i class="bi bi-phone" style="font-size: 10px;"></i> {{ $acc }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            @empty
                <div style="text-align: center; padding: 36px 16px; color: var(--muted, #64748b);">
                    <i class="bi bi-credit-card" style="font-size: 38px; display: inline-block; margin-bottom: 8px;"></i>
                    <p style="margin: 0; font-size: 13px;">{{ __('crm.no_instant_donation_methods_configured') ?? 'No payment methods configured yet.' }}</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection

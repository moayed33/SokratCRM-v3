@once
<style>
    .crm-topbar-user {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        position: relative;
        z-index: 10000;
        vertical-align: middle;
    }

    /* Top bar notification trigger button */
    .crm-topbar-notification-btn {
        position: relative;
        width: 42px;
        height: 42px;
        display: inline-grid;
        place-items: center;
        flex: 0 0 42px;
        border: 1px solid var(--line, #e7e9ef);
        border-radius: 14px;
        background: var(--card, #fff);
        color: var(--dark, #182033);
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
        transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
        text-decoration: none;
        padding: 0;
    }

    .crm-topbar-notification-btn:hover,
    .crm-topbar-notification-btn:focus-visible {
        background: #fff5f6;
        border-color: rgba(220, 38, 55, 0.35);
        color: var(--red, #dc2637);
        transform: translateY(-1px);
    }

    .crm-topbar-notification-btn .crm-notification-badge {
        position: absolute;
        top: -4px;
        inset-inline-end: -4px;
        min-width: 20px;
        height: 20px;
        display: inline-grid;
        place-items: center;
        padding: 0 5px;
        border-radius: 999px;
        background: var(--red, #dc2637);
        color: #fff;
        font-size: 10px;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        box-shadow: 0 2px 6px rgba(220, 38, 55, .4), 0 0 0 2px var(--card, #fff);
        line-height: 1;
    }

    .crm-topbar-notification-btn .crm-notification-badge[hidden] {
        display: none;
    }

    html.dark-mode .crm-topbar-notification-btn {
        background: var(--bg-card, rgba(24, 24, 27, .75)) !important;
        border-color: var(--line, rgba(255, 255, 255, .1)) !important;
        color: var(--text-primary, #f4f4f5) !important;
        box-shadow: var(--shadow-glass) !important;
    }

    html.dark-mode .crm-topbar-notification-btn:hover,
    html.dark-mode .crm-topbar-notification-btn:focus-visible {
        background: rgba(239, 68, 68, .18) !important;
        border-color: rgba(239, 68, 68, .45) !important;
        color: #f87171 !important;
    }

    html.dark-mode .crm-topbar-notification-btn .crm-notification-badge {
        box-shadow: 0 2px 6px rgba(220, 38, 55, .4), 0 0 0 2px #18181b;
    }

    /* Sleek Branch Pill Switcher */
    .crm-branch-pill-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        z-index: 10000;
        vertical-align: middle;
    }

    .crm-branch-pill-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 42px;
        padding: 0 16px;
        border: 1px solid var(--line, #e7e9ef);
        border-radius: 999px;
        background: var(--card, #fff);
        color: var(--dark, #182033);
        cursor: pointer;
        font-weight: 800;
        font-size: 13px;
        transition: all .2s cubic-bezier(.4, 0, .2, 1);
        font-family: inherit;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        text-decoration: none;
    }

    .crm-branch-pill-trigger:hover,
    .crm-branch-pill-trigger:focus-visible {
        background: #fff5f6;
        border-color: rgba(220, 38, 55, 0.4);
        color: var(--red, #dc2637);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(220, 38, 55, 0.12);
    }

    .crm-branch-pill-icon {
        display: inline-grid;
        place-items: center;
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background: rgba(220, 38, 55, 0.08);
        color: var(--red, #dc2637);
        font-size: 13px;
        flex-shrink: 0;
    }

    .crm-branch-pill-name {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 13px;
        line-height: 1;
    }

    .crm-branch-pill-chevron {
        font-size: 11px;
        opacity: 0.6;
        transition: transform .2s ease;
    }

    .crm-branch-pill-wrapper.is-open .crm-branch-pill-chevron {
        transform: rotate(180deg);
    }

    .crm-branch-pill-dropdown {
        position: absolute !important;
        inset-block-start: calc(100% + 8px) !important;
        inset-inline-end: 0 !important;
        inset-inline-start: auto !important;
        width: min(260px, calc(100vw - 24px)) !important;
        background: #fff !important;
        border: 1px solid var(--line, #e7e9ef) !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 36px rgba(15, 23, 42, 0.12) !important;
        padding: 6px !important;
        z-index: 10005 !important;
        overflow: hidden;
    }

    .crm-branch-pill-dropdown[hidden] {
        display: none !important;
    }

    .crm-branch-pill-dropdown-header {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 11px;
        font-weight: 800;
        color: var(--muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .crm-branch-pill-dropdown-body {
        max-height: 280px;
        overflow-y: auto;
        padding: 4px 0;
    }

    .crm-branch-pill-option {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--dark, #182033);
        font-weight: 700;
        cursor: pointer;
        text-align: start;
        font-size: 13px;
        font-family: inherit;
        transition: all .15s ease;
        text-decoration: none;
    }

    .crm-branch-pill-option:hover {
        background: #f8fafc;
        color: var(--red, #dc2637);
    }

    .crm-branch-pill-option.is-active {
        background: rgba(220, 38, 55, 0.08) !important;
        color: var(--red, #dc2637) !important;
        font-weight: 900;
    }

    .crm-branch-pill-opt-text {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .crm-branch-pill-check {
        font-size: 16px;
        color: var(--red, #dc2637);
        font-weight: 900;
    }

    .crm-branch-pill-static {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 42px;
        padding: 0 16px;
        border-radius: 999px;
        background: rgba(220, 38, 55, 0.06);
        border: 1px solid rgba(220, 38, 55, 0.18);
        color: var(--red, #dc2637);
        font-size: 13px;
        font-weight: 800;
    }

    /* Dark Mode */
    html.dark-mode .crm-branch-pill-trigger {
        background: var(--bg-card, rgba(24, 24, 27, .75)) !important;
        border-color: var(--line, rgba(255, 255, 255, .1)) !important;
        color: var(--text-primary, #f4f4f5) !important;
        box-shadow: var(--shadow-glass) !important;
    }

    html.dark-mode .crm-branch-pill-trigger:hover,
    html.dark-mode .crm-branch-pill-trigger:focus-visible {
        background: rgba(239, 68, 68, .18) !important;
        border-color: rgba(239, 68, 68, .45) !important;
        color: #f87171 !important;
    }

    html.dark-mode .crm-branch-pill-dropdown {
        background: rgba(9, 9, 11, 0.98) !important;
        background-color: #09090b !important;
        border-color: var(--border-color, rgba(255, 255, 255, 0.12)) !important;
        box-shadow: var(--shadow-dropdown) !important;
    }

    html.dark-mode .crm-branch-pill-dropdown-header {
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        color: #a1a1aa !important;
    }

    html.dark-mode .crm-branch-pill-option {
        color: #f4f4f5 !important;
    }

    html.dark-mode .crm-branch-pill-option:hover {
        background: rgba(255, 255, 255, .06) !important;
        color: #f87171 !important;
    }

    html.dark-mode .crm-branch-pill-static {
        background: rgba(239, 68, 68, 0.14) !important;
        border-color: rgba(239, 68, 68, 0.3) !important;
        color: #f87171 !important;
    }

    /* Profile trigger standardized height */
    .crm-profile-trigger {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-inline-size: 160px !important;
        height: 42px !important;
        min-height: 42px !important;
        max-height: 42px !important;
        padding: 0 12px !important;
        border: 1px solid var(--line, #e7e9ef) !important;
        border-radius: 12px !important;
        background: var(--card, #fff) !important;
        color: var(--dark, #182033) !important;
        cursor: pointer;
        font: inherit;
        text-align: start;
        box-sizing: border-box !important;
        transition: background .2s ease, border-color .2s ease, transform .2s ease;
    }

    .crm-profile-trigger .crm-profile-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 28px !important;
        width: 28px !important;
        height: 28px !important;
        inline-size: 28px !important;
        block-size: 28px !important;
        border-radius: 8px !important;
        background: #fff0f1;
        color: var(--red, #dc2637);
        font-weight: 900;
        font-size: 13px !important;
    }

    .crm-profile-trigger .crm-profile-details strong {
        font-size: 12px !important;
        line-height: 1.2 !important;
        font-weight: 800;
    }

    .crm-profile-trigger .crm-profile-details small {
        font-size: 10px !important;
        line-height: 1 !important;
        margin-top: 1px;
        color: var(--muted, #8b94a5);
    }

    /* Call Profile Navigation Link */
    .crm-profile-call-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        inline-size: 100%;
        min-block-size: 38px;
        height: 38px;
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 10px;
        background: var(--bg, #f8fafc);
        color: var(--dark, #334155);
        font: inherit;
        font-weight: 800;
        font-size: 13px;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
        box-sizing: border-box;
        margin-bottom: 6px;
    }

    .crm-profile-call-link:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: var(--red, #dc2637);
    }

    html.dark-mode .crm-profile-call-link {
        background: var(--bg-input, #27272a) !important;
        border-color: var(--line, rgba(255, 255, 255, 0.12)) !important;
        color: var(--text-primary, #f4f4f5) !important;
    }

    html.dark-mode .crm-profile-call-link:hover {
        background: rgba(255, 255, 255, 0.12) !important;
        border-color: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }

    html.crm-monochrome .crm-profile-call-link {
        border-radius: 4px;
        border-color: #d4d4d4;
        background: #f5f5f5;
        color: #171717;
    }

    html.crm-monochrome .crm-profile-call-link:hover {
        background: #ededed;
    }

    html.crm-monochrome.dark-mode .crm-profile-call-link {
        background: #292929 !important;
        border-color: #525252 !important;
        color: #f5f5f5 !important;
    }

    /* Profile Logout Button */
    .crm-profile-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        inline-size: 100%;
        min-block-size: 38px;
        height: 38px;
        border: 1px solid #f1bbc1;
        border-radius: 10px;
        background: #fff0f1;
        color: #b42332;
        font: inherit;
        font-weight: 800;
        font-size: 13px;
        cursor: pointer;
        transition: all .15s ease;
        box-sizing: border-box;
    }

    .crm-profile-logout:hover {
        background: #ffe4e7;
        border-color: #fca5a5;
        color: #9f1239;
    }

    html.dark-mode .crm-profile-logout {
        background: rgba(220, 38, 55, 0.14) !important;
        border-color: rgba(220, 38, 55, 0.35) !important;
        color: #fca5a5 !important;
    }

    html.dark-mode .crm-profile-logout:hover {
        background: rgba(220, 38, 55, 0.25) !important;
        border-color: rgba(220, 38, 55, 0.55) !important;
        color: #ffffff !important;
    }

    html.crm-monochrome .crm-profile-logout {
        border-radius: 4px;
        border-color: #d4d4d4;
        background: #f5f5f5;
        color: #171717;
    }

    html.crm-monochrome .crm-profile-logout:hover {
        background: #ededed;
    }

    html.crm-monochrome.dark-mode .crm-profile-logout {
        background: #292929 !important;
        border-color: #525252 !important;
        color: #f5f5f5 !important;
    }

    /* Keep the profile menu anchored to its trigger and above page content. */
    .crm-profile,
    .crm-profile:has([data-crm-profile-dropdown]) {
        position: relative !important;
        z-index: 10000 !important;
        overflow: visible !important;
    }

    .crm-profile-dropdown {
        position: absolute !important;
        inset-block-start: calc(100% + 8px) !important;
        inset-inline-end: 0 !important;
        inset-inline-start: auto !important;
        display: block;
        width: min(330px, calc(100vw - 24px)) !important;
        max-width: calc(100vw - 24px) !important;
        max-height: min(560px, calc(100vh - 24px));
        overflow-y: auto;
        z-index: 10001 !important;
    }

    .crm-profile-dropdown[hidden] {
        display: none !important;
    }

    html.dark-mode .crm-profile-dropdown {
        background: rgba(9, 9, 11, 0.97) !important;
        background-color: #09090b !important;
        border-color: var(--border-color) !important;
        box-shadow: var(--shadow-dropdown) !important;
    }

    .top:has(.crm-profile),
    .top:has(.crm-topbar-user),
    .topbar:has(.crm-profile),
    .topbar:has(.crm-topbar-user),
    .transfer-topbar,
    .transfer-topbar:has(.crm-profile),
    .transfer-topbar:has(.crm-topbar-user),
    .settings-top:has(.crm-profile),
    .settings-top:has(.crm-topbar-user) {
        position: relative !important;
        z-index: 9999 !important;
        overflow: visible !important;
    }

    body.crm-notification-open .top,
    body.crm-notification-open .topbar,
    body.crm-notification-open .settings-top,
    body.crm-notification-open .transfer-topbar,
    body.crm-notification-open .crm-profile,
    body.crm-notification-open .crm-topbar-user {
        z-index: 0 !important;
    }

    html.crm-monochrome .crm-topbar-notification-btn,
    html.crm-monochrome .crm-branch-pill-trigger,
    html.crm-monochrome .crm-branch-pill-static {
        border-radius: 4px;
        box-shadow: none;
    }

    html.crm-monochrome .crm-topbar-notification-btn:hover,
    html.crm-monochrome .crm-topbar-notification-btn:focus-visible,
    html.crm-monochrome .crm-branch-pill-trigger:hover,
    html.crm-monochrome .crm-branch-pill-trigger:focus-visible {
        background: #f5f5f5 !important;
        border-color: #a3a3a3 !important;
        color: #171717 !important;
        box-shadow: none !important;
        transform: none;
    }

    html.crm-monochrome .crm-topbar-notification-btn .crm-notification-badge {
        background: #171717 !important;
        box-shadow: 0 0 0 2px var(--card, #fff);
    }

    html.crm-monochrome .crm-branch-pill-icon {
        border-radius: 3px;
        background: #ededed;
        color: #404040;
    }

    html.crm-monochrome .crm-branch-pill-dropdown {
        border-radius: 5px !important;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .08) !important;
    }

    html.crm-monochrome .crm-branch-pill-option { border-radius: 3px; }
    html.crm-monochrome .crm-branch-pill-option:hover,
    html.crm-monochrome .crm-branch-pill-option.is-active {
        background: #ededed !important;
        color: #171717 !important;
    }
    html.crm-monochrome .crm-branch-pill-check { color: #171717; }
    html.crm-monochrome .crm-branch-pill-static {
        background: #f5f5f5;
        border-color: #d4d4d4;
        color: #404040;
    }

    html.crm-monochrome.dark-mode .crm-topbar-notification-btn:hover,
    html.crm-monochrome.dark-mode .crm-topbar-notification-btn:focus-visible,
    html.crm-monochrome.dark-mode .crm-branch-pill-trigger:hover,
    html.crm-monochrome.dark-mode .crm-branch-pill-trigger:focus-visible,
    html.crm-monochrome.dark-mode .crm-branch-pill-option:hover,
    html.crm-monochrome.dark-mode .crm-branch-pill-option.is-active,
    html.crm-monochrome.dark-mode .crm-branch-pill-static {
        background: #292929 !important;
        border-color: #525252 !important;
        color: #f5f5f5 !important;
    }

    html.crm-monochrome.dark-mode .crm-branch-pill-icon {
        background: #333;
        color: #f5f5f5;
    }
</style>
@endonce
<div class="crm-topbar-user" data-crm-user-tools>
    @if (auth()->check())
        @if (auth()->user()->isSuperAdmin() && empty($hideBranchSwitcher) && !request()->routeIs('v2.campaigns.create', 'v2.campaigns.edit'))
            @php
                $currentBranchId = \App\Support\BranchContext::getCurrentBranchId();
                $allActiveBranches = \App\Models\Branch::where('is_active', true)->orderBy('name_ar')->get();
                $activeBranchLabel = \App\Support\BranchContext::getActiveBranchLabel();
            @endphp
            <div class="crm-branch-pill-wrapper" data-crm-branch-switcher>
                <button
                    class="crm-branch-pill-trigger"
                    type="button"
                    data-crm-branch-trigger
                    aria-expanded="false"
                    title="{{ __('crm.branch') }}: {{ $activeBranchLabel }}"
                >
                    <span class="crm-branch-pill-icon"><i class="bi bi-buildings"></i></span>
                    <span class="crm-branch-pill-name">{{ $activeBranchLabel }}</span>
                    <i class="bi bi-chevron-down crm-branch-pill-chevron"></i>
                </button>

                <div
                    class="crm-branch-pill-dropdown"
                    data-crm-branch-dropdown
                    hidden
                >
                    <div class="crm-branch-pill-dropdown-header">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>{{ __('crm.switch_branch') }}</span>
                    </div>
                    <div class="crm-branch-pill-dropdown-body">
                        <form method="POST" action="{{ route('v2.branch.switch') }}">
                            @csrf
                            <button
                                type="submit"
                                name="branch_id"
                                value="all"
                                class="crm-branch-pill-option {{ $currentBranchId === null ? 'is-active' : '' }}"
                            >
                                <div class="crm-branch-pill-opt-text">
                                    <i class="bi bi-globe"></i>
                                    <span>{{ __('crm.all_branches') }}</span>
                                </div>
                                @if ($currentBranchId === null)
                                    <i class="bi bi-check2 crm-branch-pill-check"></i>
                                @endif
                            </button>

                            @foreach ($allActiveBranches as $b)
                                <button
                                    type="submit"
                                    name="branch_id"
                                    value="{{ $b->id }}"
                                    class="crm-branch-pill-option {{ $currentBranchId === $b->id ? 'is-active' : '' }}"
                                >
                                    <div class="crm-branch-pill-opt-text">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>{{ $b->name_ar }}</span>
                                    </div>
                                    @if ($currentBranchId === $b->id)
                                        <i class="bi bi-check2 crm-branch-pill-check"></i>
                                    @endif
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
            </div>
        @elseif (auth()->user()->branch && empty($hideBranchSwitcher) && !request()->routeIs('v2.campaigns.create', 'v2.campaigns.edit'))
            <div class="crm-branch-pill-static" title="{{ __('crm.assigned_branch') }}: {{ auth()->user()->branch->name_ar }}">
                <span class="crm-branch-pill-icon"><i class="bi bi-buildings"></i></span>
                <span class="crm-branch-pill-name">{{ auth()->user()->branch->name_ar }}</span>
            </div>
        @endif
    @endif
    <button
        class="crm-notification-trigger crm-topbar-notification-btn"
        id="crmNotificationTrigger"
        type="button"
        data-label="{{ __('crm.notifications') }}"
        aria-label="{{ __('crm.notifications') }}"
        aria-haspopup="dialog"
        aria-controls="crmNotificationDrawer"
    >
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span class="crm-notification-badge" id="crmNotificationBadge" hidden>0</span>
    </button>

    <div class="crm-profile" data-crm-profile>
    <button
        class="crm-profile-trigger"
        type="button"
        aria-controls="crmProfileDropdown"
        aria-expanded="false"
        data-crm-profile-trigger
    >
        <span class="crm-profile-avatar" aria-hidden="true">
            {{ mb_substr((string) (auth()->user()?->name ?? 'U'), 0, 1) }}
        </span>
        <span class="crm-profile-details">
            <strong>{{ auth()->user()?->name ?? __('crm.user') }}</strong>
            <small>{{ auth()->user()?->groups?->pluck('name')->join(__('crm.list_separator')) ?? '' }}</small>
        </span>
        <i class="bi bi-chevron-down crm-profile-chevron" aria-hidden="true"></i>
    </button>

    <div
        class="crm-profile-dropdown"
        id="crmProfileDropdown"
        data-crm-profile-dropdown
        hidden
    >
        <div class="crm-profile-identity">
            <span class="crm-profile-avatar" aria-hidden="true">
                {{ mb_substr((string) (auth()->user()?->name ?? 'U'), 0, 1) }}
            </span>
            <div>
                <strong>{{ auth()->user()?->name ?? __('crm.user') }}</strong>
                <small>{{ auth()->user()?->groups?->pluck('name')->join(__('crm.list_separator')) ?? '' }}</small>
            </div>
        </div>

        <div class="crm-profile-section">
            <span class="crm-profile-label">
                <i class="bi bi-translate" aria-hidden="true"></i>
                {{ __('crm.language') }}
            </span>
            <div class="crm-profile-locale" role="group" aria-label="{{ __('crm.switch_language') }}">
                <a
                    class="crm-profile-locale-option {{ app()->getLocale() === 'ar' ? 'active' : '' }}"
                    href="{{ route('v2.lang.switch', ['locale' => 'ar']) }}"
                    data-profile-locale="ar"
                    @if (app()->getLocale() === 'ar') aria-current="page" @endif
                >
                    {{ __('crm.arabic') }}
                </a>
                <a
                    class="crm-profile-locale-option {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                    href="{{ route('v2.lang.switch', ['locale' => 'en']) }}"
                    data-profile-locale="en"
                    @if (app()->getLocale() === 'en') aria-current="page" @endif
                >
                    {{ __('crm.english') }}
                </a>
            </div>
        </div>

        <div class="crm-profile-section">
            <span class="crm-profile-label">
                <i class="bi bi-circle-half" aria-hidden="true"></i>
                {{ __('crm.theme') }}
            </span>
            <div class="crm-profile-themes" role="group" aria-label="{{ __('crm.theme') }}">
                <button type="button" class="crm-profile-theme" data-profile-theme="color-light">
                    <i class="bi bi-palette" aria-hidden="true"></i>
                    <span>{{ __('crm.colorful_light_mode') }}</span>
                </button>
                <button type="button" class="crm-profile-theme" data-profile-theme="color-dark">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i>
                    <span>{{ __('crm.colorful_dark_mode') }}</span>
                </button>
                <button type="button" class="crm-profile-theme" data-profile-theme="mono-light">
                    <i class="bi bi-circle" aria-hidden="true"></i>
                    <span>{{ __('crm.monotone_light_mode') }}</span>
                </button>
                <button type="button" class="crm-profile-theme" data-profile-theme="mono-dark">
                    <i class="bi bi-circle-half" aria-hidden="true"></i>
                    <span>{{ __('crm.monotone_dark_mode') }}</span>
                </button>
            </div>
        </div>

        @can('voip.view')
            <a href="{{ route('v2.voip.profile') }}" class="crm-profile-call-link">
                <i class="bi bi-telephone-inbound" aria-hidden="true"></i>
                <span>{{ __('crm.my_call_profile') }}</span>
            </a>
        @endcan

        <form method="POST" action="{{ route('logout') }}" class="crm-profile-logout-form">
            @csrf
            <button class="crm-profile-logout" type="submit">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                {{ __('crm.logout') }}
            </button>
        </form>
    </div>
</div>
</div>

@once
<script>
    (() => {
        const root = document.documentElement;
        const storageKey = 'sokrat.crm.theme';
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
        const themes = ['color-light', 'color-dark', 'mono-light', 'mono-dark'];

        const readTheme = () => {
            try {
                const saved = localStorage.getItem(storageKey);
                if (themes.includes(saved)) return saved;
                if (saved === 'light') return 'color-light';
                if (saved === 'dark') return 'color-dark';
                return systemTheme.matches ? 'color-dark' : 'color-light';
            } catch (error) {
                return systemTheme.matches ? 'color-dark' : 'color-light';
            }
        };

        const applyTheme = (theme, persist = false) => {
            const selectedTheme = themes.includes(theme) ? theme : 'color-light';
            const isDark = selectedTheme.endsWith('-dark');
            const isMonochrome = selectedTheme.startsWith('mono-');
            root.classList.toggle('dark-mode', isDark);
            root.classList.toggle('crm-monochrome', isMonochrome);
            root.dataset.theme = selectedTheme;
            root.dataset.palette = isMonochrome ? 'monochrome' : 'colorful';

            document.querySelectorAll('[data-profile-theme]').forEach((button) => {
                const selected = button.dataset.profileTheme === selectedTheme;
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            if (persist) {
                try {
                    localStorage.setItem(storageKey, selectedTheme);
                } catch (error) {}
            }

            window.dispatchEvent(new CustomEvent('crm:theme-changed', {
                detail: { theme: selectedTheme, isDark, isMonochrome },
            }));
        };

        const closeMenus = () => {
            document.querySelectorAll('[data-crm-profile]').forEach((profile) => {
                const trigger = profile.querySelector('[data-crm-profile-trigger]');
                const dropdown = profile.querySelector('[data-crm-profile-dropdown]');
                trigger?.setAttribute('aria-expanded', 'false');
                if (dropdown) dropdown.hidden = true;
                profile.classList.remove('is-open');
            });

            document.querySelectorAll('[data-crm-branch-switcher]').forEach((switcher) => {
                const trigger = switcher.querySelector('[data-crm-branch-trigger]');
                const dropdown = switcher.querySelector('[data-crm-branch-dropdown]');
                trigger?.setAttribute('aria-expanded', 'false');
                if (dropdown) dropdown.hidden = true;
                switcher.classList.remove('is-open');
            });
        };

        const bindProfiles = () => {
            document.querySelectorAll('[data-crm-profile]').forEach((profile) => {
                const trigger = profile.querySelector('[data-crm-profile-trigger]');
                const dropdown = profile.querySelector('[data-crm-profile-dropdown]');
                if (!trigger || !dropdown || profile.dataset.bound === '1') return;

                profile.dataset.bound = '1';
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const open = trigger.getAttribute('aria-expanded') === 'true';
                    closeMenus();
                    trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
                    dropdown.hidden = open;
                    profile.classList.toggle('is-open', !open);
                });

                dropdown.addEventListener('click', (event) => event.stopPropagation());
                profile.querySelectorAll('[data-profile-theme]').forEach((button) => {
                    button.addEventListener('click', () => applyTheme(button.dataset.profileTheme, true));
                });
            });

            document.querySelectorAll('[data-crm-branch-switcher]').forEach((switcher) => {
                const trigger = switcher.querySelector('[data-crm-branch-trigger]');
                const dropdown = switcher.querySelector('[data-crm-branch-dropdown]');
                if (!trigger || !dropdown || switcher.dataset.bound === '1') return;

                switcher.dataset.bound = '1';
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const open = trigger.getAttribute('aria-expanded') === 'true';
                    closeMenus();
                    trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
                    dropdown.hidden = open;
                    switcher.classList.toggle('is-open', !open);
                });

                dropdown.addEventListener('click', (event) => event.stopPropagation());
            });

            applyTheme(readTheme());
        };
        applyTheme(readTheme());

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindProfiles, { once: true });
        } else {
            bindProfiles();
        }

        document.addEventListener('click', closeMenus);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenus();
        });
    })();
</script>
@endonce

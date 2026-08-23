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
</style>
@endonce
<div class="crm-topbar-user" data-crm-user-tools>
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
            {{ mb_substr((string) auth()->user()->name, 0, 1) }}
        </span>
        <span class="crm-profile-details">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->groups->pluck('name')->join(__('crm.list_separator')) }}</small>
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
                {{ mb_substr((string) auth()->user()->name, 0, 1) }}
            </span>
            <div>
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->groups->pluck('name')->join(__('crm.list_separator')) }}</small>
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
                <button type="button" class="crm-profile-theme" data-profile-theme="light">
                    <i class="bi bi-sun" aria-hidden="true"></i>
                    <span>{{ __('crm.light_mode') }}</span>
                </button>
                <button type="button" class="crm-profile-theme" data-profile-theme="dark">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i>
                    <span>{{ __('crm.dark_mode') }}</span>
                </button>
                <button type="button" class="crm-profile-theme" data-profile-theme="system">
                    <i class="bi bi-display" aria-hidden="true"></i>
                    <span>{{ __('crm.system_mode') }}</span>
                </button>
            </div>
        </div>

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

        const readTheme = () => {
            try {
                return ['light', 'dark', 'system'].includes(localStorage.getItem(storageKey))
                    ? localStorage.getItem(storageKey)
                    : 'system';
            } catch (error) {
                return 'system';
            }
        };

        const applyTheme = (theme, persist = false) => {
            const isDark = theme === 'dark' || (theme === 'system' && systemTheme.matches);
            root.classList.toggle('dark-mode', isDark);
            root.dataset.theme = theme;

            document.querySelectorAll('[data-profile-theme]').forEach((button) => {
                const selected = button.dataset.profileTheme === theme;
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            if (persist) {
                try {
                    localStorage.setItem(storageKey, theme);
                } catch (error) {}
            }
        };

        const closeMenus = () => {
            document.querySelectorAll('[data-crm-profile]').forEach((profile) => {
                const trigger = profile.querySelector('[data-crm-profile-trigger]');
                const dropdown = profile.querySelector('[data-crm-profile-dropdown]');
                trigger?.setAttribute('aria-expanded', 'false');
                if (dropdown) dropdown.hidden = true;
                profile.classList.remove('is-open');
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

            applyTheme(readTheme());
        };

        applyTheme(readTheme());
        systemTheme.addEventListener('change', () => {
            if (readTheme() === 'system') applyTheme('system');
        });

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

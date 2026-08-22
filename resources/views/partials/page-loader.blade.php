@once
<style>
#crm-page-loader {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: grid;
    place-items: center;
    overflow: hidden;
    background: radial-gradient(circle at 50% 38%, #ffffff 0%, #ffffff 18%, #f7f8fb 55%, #eef0f5 100%);
    opacity: 1;
    visibility: visible;
    transition: opacity 0.25s ease, visibility 0.25s ease;
    animation: crmLoaderAutomaticHide 0.3s ease 1.2s forwards;
}

html.dark-mode #crm-page-loader {
    background: radial-gradient(circle at 50% 38%, #1f293d 0%, #151c2b 55%, #0f141f 100%) !important;
}

#crm-page-loader.crm-page-loader--hide {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

.crm-loading,
.crm-loading body {
    overflow: hidden;
}

.crm-loader-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.crm-loader-logo-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    display: grid;
    place-items: center;
    border: 1px solid #e3e6ed;
    border-radius: 36px;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(24, 32, 51, 0.12), 0 4px 16px rgba(24, 32, 51, 0.06);
    animation: crmLogoEntrance 0.6s cubic-bezier(0.2, 0.9, 0.25, 1.2) both;
}

html.dark-mode .crm-loader-logo-wrap {
    background: #182033 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 26px 70px rgba(0, 0, 0, 0.5), 0 5px 18px rgba(0, 0, 0, 0.3) !important;
}

.crm-loader-logo-wrap::before,
.crm-loader-logo-wrap::after {
    content: "";
    position: absolute;
    border-radius: 42px;
    pointer-events: none;
}

.crm-loader-logo-wrap::before {
    inset: -10px;
    border: 2px solid rgba(220, 38, 55, 0.2);
    animation: crmLoaderRing 1.4s ease-out infinite;
}

.crm-loader-logo-wrap::after {
    inset: 0;
    overflow: hidden;
    background: linear-gradient(115deg, transparent 20%, rgba(255,255,255,0) 36%, rgba(255,255,255,0.7) 49%, rgba(255,255,255,0) 62%, transparent 80%);
    transform: translateX(130%);
    animation: crmLoaderShine 1.4s ease-in-out infinite;
}

.crm-loader-logo {
    position: relative;
    z-index: 2;
    width: 105px;
    height: 105px;
    display: block;
    object-fit: contain;
    animation: crmLogoFloat 1.4s ease-in-out infinite;
}

.crm-loader-brand {
    margin-top: 20px;
    color: #dc2637;
    font-size: 26px;
    font-weight: 900;
    font-family: var(--font-primary, system-ui, sans-serif);
    letter-spacing: 0.3px;
    animation: crmLoaderText 0.5s ease 0.15s both;
}

.crm-loader-subtitle {
    margin-top: 6px;
    color: #778194;
    font-size: 12px;
    font-weight: 700;
    font-family: var(--font-primary, system-ui, sans-serif);
    animation: crmLoaderText 0.5s ease 0.25s both;
}

html.dark-mode .crm-loader-subtitle {
    color: #94a3b8 !important;
}

.crm-loader-line {
    position: relative;
    width: 180px;
    height: 4px;
    margin-top: 18px;
    overflow: hidden;
    border-radius: 99px;
    background: #e5e8ee;
}

html.dark-mode .crm-loader-line {
    background: #334155 !important;
}

.crm-loader-line::after {
    content: "";
    position: absolute;
    top: 0;
    inset-inline-start: -42%;
    width: 42%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #dc2637, #f26a77);
    animation: crmLoaderProgress 0.9s ease-in-out infinite;
}

.crm-loader-dots {
    display: flex;
    gap: 6px;
    margin-top: 10px;
    direction: ltr;
}

.crm-loader-dots span {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #dc2637;
    animation: crmLoaderDot 0.9s ease-in-out infinite;
}

.crm-loader-dots span:nth-child(2) {
    animation-delay: 0.14s;
}

.crm-loader-dots span:nth-child(3) {
    animation-delay: 0.28s;
}

@keyframes crmLogoEntrance {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.8) rotate(-4deg);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes crmLogoFloat {
    0%, 100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-5px) scale(1.02);
    }
}

@keyframes crmLoaderRing {
    0% {
        opacity: 0.8;
        transform: scale(0.88);
    }
    75%, 100% {
        opacity: 0;
        transform: scale(1.14);
    }
}

@keyframes crmLoaderShine {
    0%, 28% {
        transform: translateX(130%);
    }
    72%, 100% {
        transform: translateX(-130%);
    }
}

@keyframes crmLoaderText {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes crmLoaderProgress {
    0% {
        inset-inline-start: -42%;
    }
    100% {
        inset-inline-start: 100%;
    }
}

@keyframes crmLoaderDot {
    0%, 100% {
        opacity: 0.3;
        transform: translateY(0);
    }
    50% {
        opacity: 1;
        transform: translateY(-4px);
    }
}

@keyframes crmLoaderAutomaticHide {
    to {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
}

@media (max-width: 600px) {
    .crm-loader-logo-wrap {
        width: 120px;
        height: 120px;
        border-radius: 30px;
    }
    .crm-loader-logo {
        width: 90px;
        height: 90px;
    }
    .crm-loader-brand {
        font-size: 22px;
    }
}

@media (prefers-reduced-motion: reduce) {
    #crm-page-loader,
    .crm-loader-logo-wrap,
    .crm-loader-logo,
    .crm-loader-logo-wrap::before,
    .crm-loader-logo-wrap::after,
    .crm-loader-brand,
    .crm-loader-subtitle,
    .crm-loader-line::after,
    .crm-loader-dots span {
        animation: none !important;
    }
    #crm-page-loader {
        animation: crmLoaderAutomaticHide 0.2s linear 0.4s forwards !important;
    }
}
</style>

<div
    id="crm-page-loader"
    role="status"
    aria-live="polite"
    aria-label="{{ __('crm.loading_sokrat_crm') }}"
>
    <div class="crm-loader-content">
        <div class="crm-loader-logo-wrap">
            <img
                class="crm-loader-logo"
                src="{{ asset('images/sokrat-pro-tech.png') }}"
                alt="Sokrat PRO"
            >
        </div>

        <div class="crm-loader-brand">SokratCRM</div>
        <div class="crm-loader-subtitle">{{ __('crm.preloader_preparing_workspace') }}</div>

        <div class="crm-loader-line" aria-hidden="true"></div>

        <div class="crm-loader-dots" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<noscript>
    <style>
        #crm-page-loader {
            display: none !important;
        }
    </style>
</noscript>

<script>
(() => {
    const loader = document.getElementById('crm-page-loader');
    if (!loader) return;

    document.documentElement.classList.add('crm-loading');

    const minimumVisibleTime = 450;
    const startedAt = Date.now();
    let hideScheduled = false;

    const hideLoader = () => {
        if (hideScheduled) return;
        hideScheduled = true;

        const elapsed = Date.now() - startedAt;
        const remaining = Math.max(0, minimumVisibleTime - elapsed);

        window.setTimeout(() => {
            loader.classList.add('crm-page-loader--hide');
            document.documentElement.classList.remove('crm-loading');

            window.setTimeout(() => {
                try { loader.remove(); } catch (e) {}
            }, 300);
        }, remaining);
    };

    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader, { once: true });
        document.addEventListener('DOMContentLoaded', () => {
            window.setTimeout(hideLoader, 150);
        }, { once: true });
    }

    // Safety fallback auto-dismiss (never lock screen)
    window.setTimeout(hideLoader, 1200);
})();
</script>
@endonce

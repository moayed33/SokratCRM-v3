<style>
#crm-page-loader{
    position:fixed;
    inset:0;
    z-index:999999;
    display:grid;
    place-items:center;
    overflow:hidden;
    background:
        radial-gradient(circle at 50% 38%,#ffffff 0,#ffffff 18%,#f7f8fb 55%,#eef0f5 100%);
    opacity:1;
    visibility:visible;
    transition:
        opacity .38s ease,
        visibility .38s ease;
    animation:crmLoaderAutomaticHide .38s ease 2s forwards
}

#crm-page-loader.crm-page-loader--hide{
    opacity:0;
    visibility:hidden;
    pointer-events:none
}

.crm-loading,
.crm-loading body{
    overflow:hidden
}

.crm-loader-content{
    position:relative;
    z-index:2;
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center
}

.crm-loader-logo-wrap{
    position:relative;
    width:154px;
    height:154px;
    display:grid;
    place-items:center;
    border:1px solid #e3e6ed;
    border-radius:38px;
    background:#fff;
    box-shadow:
        0 26px 70px #1820331f,
        0 5px 18px #1820330f;
    animation:crmLogoEntrance .7s cubic-bezier(.2,.9,.25,1.2) both
}

.crm-loader-logo-wrap::before,
.crm-loader-logo-wrap::after{
    content:"";
    position:absolute;
    border-radius:44px;
    pointer-events:none
}

.crm-loader-logo-wrap::before{
    inset:-12px;
    border:2px solid #dc26372b;
    animation:crmLoaderRing 1.45s ease-out infinite
}

.crm-loader-logo-wrap::after{
    inset:0;
    overflow:hidden;
    background:
        linear-gradient(
            115deg,
            transparent 20%,
            #ffffff00 36%,
            #ffffffb8 49%,
            #ffffff00 62%,
            transparent 80%
        );
    transform:translateX(130%);
    animation:crmLoaderShine 1.45s ease-in-out infinite
}

.crm-loader-logo{
    position:relative;
    z-index:2;
    width:118px;
    height:118px;
    display:block;
    object-fit:contain;
    animation:crmLogoFloat 1.45s ease-in-out infinite
}

.crm-loader-brand{
    margin-top:22px;
    color:#dc2637;
    font:900 28px var(--font-primary);
    letter-spacing:.3px;
    animation:crmLoaderText .55s ease .2s both
}

.crm-loader-subtitle{
    margin-top:7px;
    color:#778194;
    font:700 12px var(--font-primary);
    animation:crmLoaderText .55s ease .3s both
}

.crm-loader-line{
    position:relative;
    width:190px;
    height:4px;
    margin-top:20px;
    overflow:hidden;
    border-radius:99px;
    background:#e5e8ee
}

.crm-loader-line::after{
    content:"";
    position:absolute;
    top:0;
    right:-42%;
    width:42%;
    height:100%;
    border-radius:inherit;
    background:linear-gradient(90deg,#dc2637,#f26a77);
    animation:crmLoaderProgress 1s ease-in-out infinite
}

.crm-loader-dots{
    display:flex;
    gap:6px;
    margin-top:12px;
    direction:ltr
}

.crm-loader-dots span{
    width:6px;
    height:6px;
    border-radius:50%;
    background:#dc2637;
    animation:crmLoaderDot .9s ease-in-out infinite
}

.crm-loader-dots span:nth-child(2){
    animation-delay:.14s
}

.crm-loader-dots span:nth-child(3){
    animation-delay:.28s
}

@keyframes crmLogoEntrance{
    from{
        opacity:0;
        transform:translateY(22px) scale(.78) rotate(-4deg)
    }
    to{
        opacity:1;
        transform:none
    }
}

@keyframes crmLogoFloat{
    0%,100%{
        transform:translateY(0) scale(1)
    }
    50%{
        transform:translateY(-6px) scale(1.025)
    }
}

@keyframes crmLoaderRing{
    0%{
        opacity:.8;
        transform:scale(.88)
    }
    75%,100%{
        opacity:0;
        transform:scale(1.14)
    }
}

@keyframes crmLoaderShine{
    0%,28%{
        transform:translateX(130%)
    }
    72%,100%{
        transform:translateX(-130%)
    }
}

@keyframes crmLoaderText{
    from{
        opacity:0;
        transform:translateY(9px)
    }
    to{
        opacity:1;
        transform:none
    }
}

@keyframes crmLoaderProgress{
    0%{
        right:-42%
    }
    100%{
        right:100%
    }
}

@keyframes crmLoaderDot{
    0%,100%{
        opacity:.3;
        transform:translateY(0)
    }
    50%{
        opacity:1;
        transform:translateY(-5px)
    }
}

@keyframes crmLoaderAutomaticHide{
    to{
        opacity:0;
        visibility:hidden;
        pointer-events:none
    }
}

@media(max-width:600px){
    .crm-loader-logo-wrap{
        width:132px;
        height:132px;
        border-radius:32px
    }

    .crm-loader-logo{
        width:101px;
        height:101px
    }

    .crm-loader-brand{
        font-size:24px
    }
}

@media(prefers-reduced-motion:reduce){
    #crm-page-loader,
    .crm-loader-logo-wrap,
    .crm-loader-logo,
    .crm-loader-logo-wrap::before,
    .crm-loader-logo-wrap::after,
    .crm-loader-brand,
    .crm-loader-subtitle,
    .crm-loader-line::after,
    .crm-loader-dots span{
        animation:none!important
    }

    #crm-page-loader{
        animation:crmLoaderAutomaticHide .2s linear .45s forwards!important
    }
}
</style>

<div
    id="crm-page-loader"
    role="status"
    aria-live="polite"
    aria-label="جاري تحميل SokratCRM"
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
        <div class="crm-loader-subtitle">جاري تجهيز مساحة العمل</div>

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
        #crm-page-loader{
            display:none!important
        }
    </style>
</noscript>

<script>
(() => {
    const loader = document.getElementById('crm-page-loader');

    if (!loader) {
        return;
    }

    document.documentElement.classList.add('crm-loading');

    const minimumVisibleTime = 850;
    const startedAt = Date.now();
    let hideScheduled = false;

    const hideLoader = () => {
        if (hideScheduled) {
            return;
        }

        hideScheduled = true;

        const elapsed = Date.now() - startedAt;
        const remaining = Math.max(
            0,
            minimumVisibleTime - elapsed
        );

        window.setTimeout(() => {
            loader.classList.add('crm-page-loader--hide');
            document.documentElement.classList.remove('crm-loading');

            window.setTimeout(() => {
                loader.remove();
            }, 450);
        }, remaining);
    };

    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader, {
            once:true
        });
    }

    window.setTimeout(hideLoader, 1800);
})();
</script>

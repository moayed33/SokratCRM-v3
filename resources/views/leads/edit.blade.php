<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.leads') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <style>
  :root{
   --red:#dc2637;
   --dark:#182033;
   --muted:#818b9c;
   --line:#e5e8ee;
   --bg:#f5f6f9;
   --card:#fff;
   --shadow:0 15px 42px #17203310
  }

  html.dark-mode{
   --dark:#f4f4f5;
   --text:#a1a1aa;
   --muted:#a1a1aa;
   --line:rgba(255,255,255,.08);
   --bg:#121214;
   --card:rgba(24,24,27,.75);
   --shadow:0 10px 30px rgba(0,0,0,.5)
  }

  *{box-sizing:border-box}

  body{
   margin:0;
   min-width:320px;
   background:
    radial-gradient(circle at 8% 0,#dc26370d,transparent 28rem),
    var(--bg);
   color:var(--dark);
   font-family:var(--font-primary);
   font-size:15px
  }

  button,input,select,textarea{font:inherit}
  a{color:inherit}

  .crm-app{
   display:flex;
   min-height:100vh
  }

  .crm-side{
   position:sticky;
   top:0;
   flex:0 0 288px;
   width:288px;
   height:100vh;
   overflow:auto;
   padding:24px 17px;
   border-inline-end:1px solid var(--line);
   background:#fff;
   z-index:80
  }

  .crm-side-brand{
   display:flex;
   align-items:center;
   gap:11px;
   padding:4px 8px 20px;
   margin-bottom:17px;
   border-bottom:1px solid var(--line);
   text-decoration:none
  }

  .crm-side-brand .logo{
   width:58px;
   height:58px;
   flex:0 0 58px;
   object-fit:contain
  }

  .crm-side-brand strong{
   display:block;
   color:var(--red);
   font:900 22px var(--font-primary)
  }

  .crm-side-brand small{
   display:block;
   margin-top:5px;
   color:var(--muted);
   font-size:12px
  }

  .crm-side-caption{
   margin:0 10px 10px;
   color:#9aa2b0;
   font-size:13px;
   font-weight:bold
  }

  .crm-side-nav{
   display:grid;
   gap:6px
  }

  .crm-link,
  .crm-toggle{
   width:100%;
   min-height:49px;
   display:flex;
   align-items:center;
   gap:10px;
   padding:8px 10px;
   border:1px solid transparent;
   border-radius:13px;
   background:transparent;
   color:#566175;
   text-decoration:none;
   text-align:start;
   cursor:pointer;
   transition:.2s
  }

  .crm-link:hover,
  .crm-toggle:hover{
   color:var(--red);
   background:#fff5f6;
   transform:translateX(-2px)
  }

  .crm-link.active,
  .crm-toggle.active{
   color:#fff;
   background:linear-gradient(135deg,#e83243,#c91d2e);
   box-shadow:0 11px 25px #dc263737
  }

  .crm-ico{
   width:32px;
   height:32px;
   flex:0 0 32px;
   display:grid;
   place-items:center;
   border-radius:10px;
   background:#f0f2f6;
   font-size:17px
  }

  .crm-link.active .crm-ico,
  .crm-toggle.active .crm-ico{
   background:#ffffff2b
  }

  .crm-label{
   flex:1;
   font-size:16px;
   font-weight:800
  }

  .crm-count{
   min-width:24px;
   height:24px;
   display:grid;
   place-items:center;
   padding:0 6px;
   border-radius:99px;
   background:#eef0f4;
   color:#7e8796;
   font:800 11px var(--font-primary)
  }

  .crm-toggle.active .crm-count{
   color:#fff;
   background:#ffffff2b
  }

  .crm-arrow{
   color:#a2a9b5;
   transition:.2s
  }

  .crm-toggle[aria-expanded=true] .crm-arrow{
   transform:rotate(180deg)
  }

  .crm-sub{
   display:grid;
   grid-template-rows:0fr;
   transition:.22s
  }

  .crm-sub.open{grid-template-rows:1fr}

  .crm-sub-inner{
   min-height:0;
   overflow:hidden
  }

  .crm-sub nav{
   display:grid;
   gap:2px;
   margin:3px 28px 7px 0;
   padding-inline-start:14px;
   border-inline-start:1px solid var(--line)
  }

  .crm-sub a{
   padding:8px 10px;
   border-radius:8px;
   color:#788294;
   text-decoration:none;
   font-size:14px;
   font-weight:bold
  }

  .crm-sub a:hover,
  .crm-sub a.active{
   color:var(--red);
   background:#fff2f4
  }

  .crm-main{
   min-width:0;
   flex:1;
   width:calc(100% - 288px)
  }

  .shell{
   width:calc(100% - 28px);
   max-width:1500px;
   margin:0 auto;
   padding:16px 0 48px
  }

  .topbar{
   min-height:72px;
   display:flex;
   align-items:center;
   gap:13px;
   padding:12px 15px;
   margin-bottom:16px;
   border:1px solid var(--line);
   border-radius:17px;
   background:#ffffffed;
   box-shadow:var(--shadow)
  }

  .menu-button{
   display:none;
   width:42px;
   height:42px;
   border:1px solid var(--line);
   border-radius:11px;
   background:#fff;
   cursor:pointer;
   font-size:20px
  }

  .optional{
   margin-inline-start:5px;
   color:var(--muted);
   font-size:11px;
   font-weight:normal
  }

  .page-title{flex:1}

  .page-title h1{
   margin:0;
   font-size:24px
  }

  .page-title p{
   margin:6px 0 0;
   color:var(--muted);
   font-size:13px
  }

  .user-menu{
   position:relative;
   flex:0 0 auto
  }

  .user-chip{
   display:flex;
   align-items:center;
   gap:8px;
   padding:5px 7px;
   border:1px solid transparent;
   border-radius:13px;
   background:transparent;
   color:inherit;
   text-align:start;
   cursor:pointer;
   transition:.2s
  }

  .user-chip:hover,
  .user-chip[aria-expanded=true]{
   border-color:var(--line);
   background:#fff
  }

  .avatar{
   width:42px;
   height:42px;
   display:grid;
   place-items:center;
   flex:0 0 42px;
   border-radius:12px;
   background:var(--dark);
   color:#fff;
   font-weight:900
  }

  .user-details{
   display:block;
   min-width:90px
  }

  .user-chip strong{
   display:block;
   font-size:13px
  }

  .user-chip small{
   display:block;
   margin-top:3px;
   color:var(--muted);
   font-size:11px
  }

  .user-dropdown{
   position:absolute;
   top:calc(100% + 8px);
   left:0;
   z-index:120;
   width:190px;
   padding:7px;
   border:1px solid var(--line);
   border-radius:13px;
   background:#fff;
   box-shadow:0 18px 45px #17203325
  }

  .user-dropdown[hidden]{
   display:none
  }

  .user-dropdown form{
   margin:0
  }

  .user-dropdown-action{
   width:100%;
   min-height:42px;
   display:flex;
   align-items:center;
   justify-content:center;
   padding:8px 12px;
   border:0;
   border-radius:9px;
   background:#fff0f2;
   color:var(--red);
   font-weight:900;
   cursor:pointer
  }

  .user-dropdown-action:hover{
   background:#ffe5e8
  }

  .form-card{
   overflow:hidden;
   border:1px solid var(--line);
   border-radius:20px;
   background:var(--card);
   box-shadow:var(--shadow)
  }
  html.dark-mode .form-card{
   background:var(--bg-card,rgba(24,24,27,.75))!important;
   backdrop-filter:var(--glass-blur,blur(16px))!important;
   -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
   border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
   box-shadow:var(--shadow-card,0 10px 30px rgba(0,0,0,.5))!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .form-hero{
   padding:26px;
   background:linear-gradient(120deg,#171f31,#30394c);
   color:#fff
  }
  html.dark-mode .form-hero{
   background:linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02))!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important
  }

  .form-hero-head{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:24px
  }

  .form-hero-copy{
   min-width:0;
   flex:1
  }

  .form-hero-back{
   min-height:48px;
   flex:0 0 auto;
   padding-inline:18px;
   border-color:#ffffff55;
   background:#fff;
   color:var(--dark);
   box-shadow:none;
   font-size:14px;
   white-space:nowrap
  }

  .form-hero-back:hover{
   border-color:#fff;
   background:#fff5f6;
   color:var(--red)
  }
  html.dark-mode .form-hero-back{
   background:rgba(255,255,255,.08)!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:#f4f4f5!important;
   font-weight:700!important
  }
  html.dark-mode .form-hero-back:hover{
   background:rgba(239,68,68,.15)!important;
   border-color:rgba(239,68,68,.4)!important;
   color:#f87171!important
  }

  .form-hero small{
   color:#f2a3ac;
   font-weight:bold
  }
  html.dark-mode .form-hero small{color:#f87171!important}

  .form-hero h2{
   margin:9px 0 6px;
   font-size:26px
  }
  html.dark-mode .form-hero h2{color:#ffffff!important}

  .form-hero p{
   max-width:760px;
   margin:0;
   color:#cbd0da;
   line-height:1.8;
   font-size:13px
  }
  html.dark-mode .form-hero p{color:#d4d4d8!important}

  .form-body{
   display:grid;
   gap:18px;
   padding:20px
  }

  .form-section{
   padding:18px;
   border:1px solid var(--line);
   border-radius:16px;
   background:#fff
  }
  html.dark-mode .form-section{
   background:rgba(255,255,255,.03)!important;
   border:1px solid rgba(255,255,255,.07)!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .section-head{
   margin-bottom:15px
  }

  .section-head h3{
   margin:0;
   font-size:18px
  }
  html.dark-mode .section-head h3{
   color:#f4f4f5!important;
   font-weight:800!important
  }

  .section-head p{
   margin:5px 0 0;
   color:var(--muted);
   font-size:12px
  }
  html.dark-mode .section-head p{
   color:var(--text-muted,#a1a1aa)!important
  }

  .fields{
   display:grid;
   grid-template-columns:repeat(3,minmax(0,1fr));
   gap:13px
  }

  .field{min-width:0}
  .field.full{grid-column:1/-1}

  .field label{
   display:block;
   margin:0 3px 7px;
   color:#667184;
   font-size:13px;
   font-weight:bold
  }
  html.dark-mode .field label{
   color:#e4e4e7!important;
   font-weight:700!important
  }

  .required{color:var(--red)}
  html.dark-mode .required{color:#ef4444!important}
  html.dark-mode .optional{color:var(--text-muted,#a1a1aa)!important}

  .field input,
  .field select,
  .field textarea{
   width:100%;
   border:1px solid #dfe3ea;
   border-radius:11px;
   background:#fafbfc;
   color:#404b5e;
   outline:none
  }
  html.dark-mode .field input,
  html.dark-mode .field select,
  html.dark-mode .field textarea{
   background:var(--bg-input,rgba(39,39,42,.65))!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:#f4f4f5!important
  }

  .field input,
  .field select{
   height:46px;
   padding:0 12px
  }

  .field textarea{
   min-height:110px;
   padding:12px;
   resize:vertical;
   line-height:1.7
  }

  .field input:focus,
  .field select:focus,
  .field textarea:focus{
   border-color:#e97d88;
   background:#fff;
   box-shadow:0 0 0 4px #dc263710
  }
  html.dark-mode .field input:focus,
  html.dark-mode .field select:focus,
  html.dark-mode .field textarea:focus{
   border-color:rgba(239,68,68,.6)!important;
   background:rgba(39,39,42,.95)!important;
   box-shadow:0 0 0 3px rgba(239,68,68,.2)!important;
   outline:none!important
  }
  html.dark-mode .field select option{
   background:#18181b!important;
   color:#f4f4f5!important
  }

  .field input[readonly]{
   background:#f0f2f5;
   cursor:not-allowed
  }
  html.dark-mode .field input[readonly]{
   background:rgba(255,255,255,.04)!important;
   border-color:rgba(255,255,255,.08)!important;
   color:#a1a1aa!important
  }

  .help{
   display:block;
   margin-top:6px;
   color:#8a93a2;
   font-size:11px;
   line-height:1.6
  }
  html.dark-mode .help{
   color:var(--text-muted,#a1a1aa)!important
  }

  .conditional{
   border-color:#f0c8cd;
   background:#fffafb
  }
  html.dark-mode .conditional{
   background:rgba(239,68,68,.05)!important;
   border-color:rgba(239,68,68,.2)!important
  }

  .reveal-panel:not(.is-hidden){
   animation:
    crmPanelReveal
    .3s
    cubic-bezier(.22,.8,.3,1)
    both
  }

  @keyframes crmPanelReveal{
   from{
    opacity:0;
    transform:translateY(-10px) scale(.993)
   }

   to{
    opacity:1;
    transform:translateY(0) scale(1)
   }
  }

  .quotation-card{
   position:relative;
   overflow:hidden;
   border-color:#edb3bb;
   background:
    radial-gradient(
     circle at 8% 5%,
     #dc263714,
     transparent 18rem
    ),
    linear-gradient(145deg,#fff,#fff8f9);
   box-shadow:0 18px 42px #dc263710
  }
  html.dark-mode .quotation-card{
   background:radial-gradient(circle at 8% 5%,rgba(239,68,68,.08),transparent 18rem),rgba(24,24,27,.8)!important;
   border-color:rgba(239,68,68,.3)!important
  }

  .quotation-card::before{
   content:"";
   position:absolute;
   top:0;
   right:0;
   left:0;
   height:4px;
   background:
    linear-gradient(
     90deg,
     #bd1728,
     #f05b6a,
     #bd1728
    )
  }

  .quotation-section-head{
   position:relative;
   padding:15px 64px 15px 15px;
   border:1px solid #f0d1d6;
   border-radius:14px;
   background:#fff
  }
  html.dark-mode .quotation-section-head{
   background:rgba(255,255,255,.04)!important;
   border-color:rgba(239,68,68,.25)!important
  }

  .quotation-section-head::before{
   content:"▣";
   position:absolute;
   top:50%;
   right:15px;
   width:38px;
   height:38px;
   display:grid;
   place-items:center;
   border-radius:11px;
   background:
    linear-gradient(135deg,#e83243,#bd1728);
   color:#fff;
   box-shadow:0 9px 20px #dc26372d;
   transform:translateY(-50%);
   font-size:18px
  }

  .quotation-section-head h3{
   color:#9f2331
  }
  html.dark-mode .quotation-section-head h3{
   color:#f87171!important
  }

  .quotation-select{
   border-color:#e7bcc2!important;
   background:#fff!important;
   font-weight:800
  }
  html.dark-mode .quotation-select{
   background:rgba(39,39,42,.65)!important;
   border-color:rgba(239,68,68,.35)!important;
   color:#f4f4f5!important
  }

  .quotation-file{
   height:auto!important;
   min-height:66px;
   padding:8px!important;
   border:1px dashed #dfa2aa!important;
   background:#fff8f9!important;
   cursor:pointer
  }
  .quotation-file:hover,
  .quotation-file:focus{
   border-color:var(--red)!important;
   background:#fff1f3!important
  }
  html.dark-mode .quotation-file{
   background:rgba(239,68,68,.06)!important;
   border-color:rgba(239,68,68,.35)!important;
   color:#f4f4f5!important
  }
  html.dark-mode .quotation-file:hover,
  html.dark-mode .quotation-file:focus{
   border-color:#ef4444!important;
   background:rgba(239,68,68,.12)!important
  }

  .quotation-file::file-selector-button{
   min-height:43px;
   margin-inline-end:12px;
   padding:8px 15px;
   border:0;
   border-radius:9px;
   background:
    linear-gradient(135deg,#e83243,#bd1728);
   color:#fff;
   font-weight:900;
   cursor:pointer
  }

  .quotation-file-help{
   min-height:34px;
   display:flex;
   align-items:center;
   margin-top:8px;
   padding:7px 10px;
   border-radius:9px;
   background:#fff;
   color:#7b8595
  }
  html.dark-mode .quotation-file-help{
   background:rgba(255,255,255,.04)!important;
   color:var(--text-muted,#a1a1aa)!important
  }
  .quotation-file-help.has-file{
   background:#edf9f2;
   color:#28784c;
   font-weight:800
  }
  html.dark-mode .quotation-file-help.has-file{
   background:rgba(34,197,94,.15)!important;
   color:#4ade80!important
  }

  .quotation-detail-panel{
   margin-top:14px!important;
   padding:16px;
   border:1px solid #eadcdf;
   border-radius:14px;
   background:#fff;
   box-shadow:0 8px 22px #17203308
  }
  html.dark-mode .quotation-detail-panel{
   background:rgba(255,255,255,.03)!important;
   border-color:rgba(255,255,255,.08)!important
  }

  .future-action{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:12px;
   padding:15px;
   border:1px dashed #e7aab2;
   border-radius:13px;
   background:#fff7f8
  }
  html.dark-mode .future-action{
   background:rgba(239,68,68,.06)!important;
   border-color:rgba(239,68,68,.3)!important
  }
  .future-action strong{
   display:block;
   margin-bottom:5px
  }
  html.dark-mode .future-action strong{
   color:#f4f4f5!important
  }
  .future-action span{
   color:var(--muted);
   font-size:12px
  }
  html.dark-mode .future-action span{
   color:var(--text-muted,#a1a1aa)!important
  }

  .btn{
   min-height:44px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:8px 17px;
   border:1px solid transparent;
   border-radius:11px;
   background:linear-gradient(135deg,#e83243,#c91d2e);
   color:#fff;
   text-decoration:none;
   font-size:13px;
   font-weight:900;
   cursor:pointer;
   box-shadow:0 10px 24px #dc26372c
  }

  .btn.secondary{
   border-color:var(--line);
   background:#f6f7f9;
   color:#657083;
   box-shadow:none
  }
  html.dark-mode .btn.secondary{
   background:rgba(255,255,255,.08)!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:#f4f4f5!important;
   font-weight:700!important
  }
  html.dark-mode .btn.secondary:hover{
   background:rgba(255,255,255,.16)!important;
   border-color:rgba(255,255,255,.25)!important;
   color:#ffffff!important
  }

  .btn.future{
   border-color:#efb8bf;
   background:#fff;
   color:var(--red);
   box-shadow:none
  }
  html.dark-mode .btn.future{
   background:rgba(239,68,68,.15)!important;
   border-color:rgba(239,68,68,.4)!important;
   color:#f87171!important
  }
  html.dark-mode .btn.future:hover{
   background:rgba(239,68,68,.25)!important;
   color:#fca5a5!important
  }

  .form-actions{
   display:flex;
   justify-content:flex-end;
   gap:9px
  }

  .error-summary{
   padding:14px 17px;
   border:1px solid #efb6bd;
   border-radius:13px;
   background:#fff3f4;
   color:#9b2431
  }
  html.dark-mode .error-summary{
   background:rgba(239,68,68,.12)!important;
   border-color:rgba(239,68,68,.35)!important;
   color:#fca5a5!important
  }

  .is-hidden{display:none!important}

  .crm-overlay{
   display:none;
   position:fixed;
   inset:0;
   border:0;
   background:#11182770;
   z-index:70
  }

  @media(max-width:1000px){
   .crm-app{display:block}

   .crm-side{
    position:fixed;
    right:0;
    width:min(288px,calc(100vw - 45px));
    transform:translateX(105%);
    transition:.25s;
    box-shadow:-20px 0 55px #17203325
   }

   body.crm-side-open{overflow:hidden}
   body.crm-side-open .crm-side{transform:none}
   body.crm-side-open .crm-overlay{display:block}

   .crm-main{width:100%}
   .menu-button{display:block}

   .fields{
    grid-template-columns:repeat(2,minmax(0,1fr))
   }
  }

  @media(max-width:650px){
   .shell{width:calc(100% - 18px)}

   .page-title p,
   .user-details{
    display:none
   }

   .user-chip{
    padding:3px
   }

   .user-dropdown{
    width:170px
   }

   .form-hero-head{
    align-items:stretch;
    flex-direction:column
   }

   .form-hero-back{
    width:100%
   }

   .fields{grid-template-columns:1fr}
   .field.full{grid-column:auto}

   .form-hero,
   .form-body,
   .form-section{
    padding:16px
   }

   .future-action{
    align-items:stretch;
    flex-direction:column
   }

   .form-actions{
    flex-direction:column
   }

   .form-actions .btn{
    width:100%
   }
  }
 
  .quotation-preview-link{
   width:max-content;
   min-height:40px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   gap:7px;
   margin-top:10px;
   padding:8px 14px;
   border:1px solid #b8cceb;
   border-radius:10px;
   background:#f3f7fd;
   color:#275a9c;
   text-decoration:none;
   font-size:12px;
   font-weight:900;
   transition:.18s
  }

  .quotation-preview-link:hover{
   transform:translateY(-1px);
   border-color:#8eacd5;
   background:#eaf2fc;
   box-shadow:0 8px 18px #17203314
  }

  .quotation-preview-link:focus-visible{
   outline:3px solid #a9c4e8;
   outline-offset:2px
  }

</style>
</head>

<body>
<button
 class="crm-overlay"
 id="crmSidebarOverlay"
 type="button"
 aria-label="{{ __('crm.close_menu') }}"
></button>

<div class="crm-app">
 @include('partials.crm-sidebar')

 <main class="crm-main">
  <div class="shell">
   <header class="topbar">
    <button
     class="menu-button"
     id="crmMenuButton"
     type="button"
     aria-controls="crmSidebar"
     aria-expanded="false"
    >
     ☰
    </button>

    <div class="page-title">
     <h1>تعديل بيانات العميل</h1>
     <p>
      {{ __('crm.enter_lead_basics') }}
     </p>
    </div>

    @include('partials.profile-dropdown')
   </header>

   <article class="form-card">
    <div class="form-hero">
     <div class="form-hero-head">
      <div class="form-hero-copy">       <small>تعديل عميل حالي</small>
       <h2>تعديل بيانات العميل</h2>
       <p>
         قم بمراجعة وتحديث بيانات
         {{ $lead->name }} ثم احفظ التعديلات.
        </p>
      </div>

      <a
       class="btn form-hero-back"
       href="{{ route('v2.leads') }}"
      >
       {{ __('crm.back_to_leads') }}
      </a>
     </div>
    </div>

    <form
     class="form-body"
     method="POST"
     action="{{ route('v2.leads.update', $lead) }}"
     enctype="multipart/form-data"
    >
     @csrf
     @method('PATCH')

     @if ($errors->any())
      <div class="error-summary">
       <strong>{{ __('crm.review_errors') }}</strong>

       <ul>
        @foreach ($errors->all() as $error)
         <li>{{ $error }}</li>
        @endforeach
       </ul>
      </div>
     @endif

     <section class="form-section">
      <div class="section-head">
       <h3>{{ __('crm.basic_data') }}</h3>
       <p>{{ __('crm.basic_data_required') }}</p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="firstName">
         {{ __('crm.first_name') }}
         <span class="required">*</span>
        </label>

        <input
         id="firstName"
         type="text"
         name="first_name"
         value="{{ old('first_name', $lead->first_name ?: $lead->name) }}"
         maxlength="75"
         required
        >
       </div>

       <div class="field">
        <label for="lastName">
         {{ __('crm.last_name') }}
         <span class="optional">{{ __('crm.optional') }}</span>
        </label>

        <input
         id="lastName"
         type="text"
         name="last_name"
         value="{{ old('last_name', $lead->last_name) }}"
         maxlength="75"
        >
       </div>

       <div class="field">
        <label for="phone">
         {{ __('crm.phone_number') }}
         <span class="required">*</span>
        </label>

        <input
         id="phone"
         type="tel"
         name="phone"
         value="{{ old('phone', $lead->phone) }}"
         maxlength="50"
         required
        >
       </div>

       <div class="field">
        <label for="source">
         {{ __('crm.source') }}
         <span class="required">*</span>
        </label>

        <input
         id="source"
         type="text"
         name="source"
         value="{{ old('source', $lead->source) }}"
         maxlength="100"
         list="sourceOptions"
         required
        >

        <datalist id="sourceOptions">
         @foreach ($sources as $source)
          <option value="{{ $source }}"></option>
         @endforeach
        </datalist>
       </div>

       <div class="field">
        <label for="assignedUserId">
         {{ __('crm.responsible_employee') }}
         <span class="required">*</span>
        </label>

        @if ($canAssignLead)
         <select
          id="assignedUserId"
          name="assigned_user_id"
         >
          @if ($lead->assigned_user_id === null)
           <option
            value=""
            @selected(old('assigned_user_id', '') === '')
           >
            غير مسند (بدون تغيير)
           </option>
          @endif
          @foreach ($assignableUsers as $assignableUser)
           <option
            value="{{ $assignableUser->id }}"
            @selected(
             (int) old(
              'assigned_user_id',
              $lead->assigned_user_id
             ) === (int) $assignableUser->id
            )
           >
            {{ $assignableUser->name }}
            @if ($assignableUser->username)
             ({{ $assignableUser->username }})
            @endif
           </option>
          @endforeach
         </select>

         <span class="help">
          تظهر فقط حسابات المجموعات المسموح لك بالإسناد إليها.
         </span>
        @else
         <input
          id="assignedUserId"
          type="text"
          value="{{ $assignedEmployee }}"
          readonly
         >

         <span class="help">
          تحتاج إلى صلاحية الإسناد لتغيير الموظف المسؤول.
         </span>
        @endif
       </div>

       <div class="field">
        <label for="leadStatus">
         حالة العميل
         <span class="required">*</span>
        </label>

        <select
         id="leadStatus"
         name="lead_status_id"
         disabled
         aria-disabled="true"
         required
        >
         <option value="">{{ __('crm.select_lead_status') }}</option>

         @foreach ($statuses as $status)
          <option
           value="{{ $status->id }}"
           data-code="{{ $status->code }}"
           @selected((int) $lead->lead_status_id === (int) $status->id)
          >
           {{ $status->name_ar }}
          </option>
         @endforeach
        </select>

         <span class="help status-locked-help">
          الحالة والمرحلة مثبتتان بعد إنشاء العميل.
          تغيير الحالة يتم فقط من شاشة تسجيل المتابعة.
         </span>
       </div>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel is-hidden"
      id="businessDetailsSection"
     >
      <div class="section-head">
       <h3>{{ __('crm.company_lead_data') }}</h3>
       <p>
        تظهر في حالات مهتم، لم يرد، مقابلة، عرض سعر،
        مناقشة، تقفيل عقد وتنفيذ.
       </p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="companyName">{{ __('crm.company_name') }}</label>
        <input
         id="companyName"
         type="text"
         name="company_name"
         value="{{ old('company_name', $lead->company_name) }}"
         maxlength="150"
        >
       </div>

       <div class="field">
        <label for="activity">{{ __('crm.activity') }}</label>
        <input
         id="activity"
         type="text"
         name="activity"
         value="{{ old('activity', $lead->activity) }}"
         maxlength="150"
        >
       </div>

       <div class="field">
        <label for="governorate">{{ __('crm.governorate') }}</label>
        <input
         id="governorate"
         type="text"
         name="governorate"
         value="{{ old('governorate', $lead->governorate) }}"
         maxlength="100"
        >
       </div>

       <div class="field full">
        <label for="address">{{ __('crm.address') }}</label>
        <input
         id="address"
         type="text"
         name="address"
         value="{{ old('address', $lead->address) }}"
         maxlength="255"
        >
       </div>

       <div class="field">
        <label for="usersCount">{{ __('crm.user_count') }}</label>
        <input
         id="usersCount"
         type="number"
         name="users_count"
         value="{{ old('users_count', $lead->users_count) }}"
         min="0"
         max="1000000"
        >
       </div>

       <div class="field">
        <label for="branchesCount">{{ __('crm.branch_count') }}</label>
        <input
         id="branchesCount"
         type="number"
         name="branches_count"
         value="{{ old('branches_count', $lead->branches_count) }}"
         min="0"
         max="1000000"
        >
       </div>

       <div class="field">
        <label for="jobTitle">{{ __('crm.job_title') }}</label>
        <input
         id="jobTitle"
         type="text"
         name="job_title"
         value="{{ old('job_title', $lead->job_title) }}"
         maxlength="150"
        >
       </div>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel is-hidden"
      id="noAnswerSection"
     >
      <div class="future-action">
       <div>
        <strong>{{ __('crm.record_followup') }}</strong>
        <span>
         سيتم ربط الزر بنظام المتابعات في المرحلة القادمة.
        </span>
       </div>

       <button
        class="btn future"
        id="followUpButton"
        type="button"
       >
        ＋ تسجيل متابعة
       </button>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel is-hidden"
      id="notInterestedSection"
     >
      <div class="section-head">
       <h3>{{ __('crm.not_interested_reason') }}</h3>
       <p>{{ __('crm.reason_required') }}</p>
      </div>

      <div class="field">
       <label for="disinterestReason">
        {{ __('crm.not_interested_reason') }}
        <span class="required">*</span>
       </label>

       <textarea
        id="disinterestReason"
        name="disinterest_reason"
        maxlength="5000"
       >{{ old('disinterest_reason', $lead->disinterest_reason) }}</textarea>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel quotation-card is-hidden"
      id="quotationSection"
     >
      <div class="section-head quotation-section-head">
       <h3>{{ __('crm.quotation_data') }}</h3>
       <p>
        تظهر هذه البيانات في مراحل عرض سعر، مناقشة،
        تقفيل عقد وتنفيذ.
       </p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="solutionType">
         {{ __('crm.system_type') }}
         <span class="required">*</span>
        </label>

        <select
         class="quotation-select"
         id="solutionType"
         name="solution_type"
        >
         <option value="">{{ __('crm.select_system_type') }}</option>

         <option
          value="call_center"
          @selected(old('solution_type', $lead->solution_type) === 'call_center')
         >
          Call Center
         </option>

         <option
          value="erp"
          @selected(old('solution_type', $lead->solution_type) === 'erp')
         >
          ERP
         </option>
        </select>
       </div>

       <div class="field">
        <label for="quotationFile">
         ملف عرض السعر

         @if ($hasQuotationFile)
          <span class="optional">
           (اختياري — يوجد ملف حالي)
          </span>
         @else
          <span class="required">*</span>
         @endif
        </label>

        <input
         class="quotation-file"
         id="quotationFile"
         type="file"
         name="quotation_file"
         accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
        >

        <span
         class="help quotation-file-help{{ $hasQuotationFile ? ' has-file' : '' }}"
         id="quotationFileHelp"
         aria-live="polite"
        >
         {{ $quotationFileHelpText }}
        </span>

        @if ($hasQuotationFile)
         <a
          class="quotation-preview-link"
          href="{{ route('v2.leads.quotation.preview', $lead) }}"
          target="_blank"
          rel="noopener noreferrer"
          title="معاينة ملف عرض السعر: {{ $quotationFileName }}"
         >
          <span aria-hidden="true">👁</span>
          {{ __('crm.preview_quotation') }}
         </a>
        @endif
       </div>
      </div>

      <div
       class="fields quotation-detail-panel reveal-panel is-hidden"
       id="callCenterFields"
      >
       <div class="field">
        <label for="linesCount">
         {{ __('crm.line_count') }}
         <span class="required">*</span>
        </label>

        <input
         id="linesCount"
         type="number"
         name="lines_count"
         value="{{ old('lines_count', $lead->lines_count) }}"
         min="1"
         max="1000000"
        >
       </div>

       <div class="field full">
        <label for="extensions">
         {{ __('crm.accessories') }}
         <span class="required">*</span>
        </label>

        <textarea
         id="extensions"
         name="extensions"
         maxlength="5000"
         placeholder="{{ __('crm.accessories_placeholder') }}"
        >{{ old('extensions', $lead->extensions) }}</textarea>
       </div>
      </div>

      <div
       class="fields quotation-detail-panel reveal-panel is-hidden"
       id="erpFields"
      >
       <div class="field full">
        <label for="departments">
         {{ __('crm.departments') }}
         <span class="required">*</span>
        </label>

        <textarea
         id="departments"
         name="departments"
         maxlength="5000"
         placeholder="{{ __('crm.departments_placeholder') }}"
        >{{ old('departments', $lead->departments) }}</textarea>
       </div>
      </div>
     </section>

     <div class="form-actions">
      <a
       class="btn secondary"
       href="{{ route('v2.leads') }}"
      >
       {{ __('crm.cancel') }}
      </a>

      <button class="btn" type="submit">
       {{ __('crm.save_changes') }}
      </button>
     </div>
    </form>
   </article>
  </div>
 </main></div>

<script>
 (() => {
  const body = document.body;
  const menuButton =
   document.getElementById('crmMenuButton');

  const overlay =
   document.getElementById('crmSidebarOverlay');

  const userMenuButton =
   document.getElementById('userMenuButton');

  const userMenuDropdown =
   document.getElementById('userMenuDropdown');

  const setUserMenuOpen = (open) => {
   userMenuButton?.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );

   if (userMenuDropdown) {
    userMenuDropdown.hidden = !open;
   }
  };

  const setSidebarOpen = (open) => {
   body.classList.toggle('crm-side-open', open);

   menuButton?.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );
  };

  menuButton?.addEventListener('click', () => {
   setSidebarOpen(
    !body.classList.contains('crm-side-open')
   );
  });

  overlay?.addEventListener('click', () => {
   setSidebarOpen(false);
  });

  userMenuButton?.addEventListener(
   'click',
   (event) => {
    event.stopPropagation();

    const open =
     userMenuButton.getAttribute('aria-expanded')
     !== 'true';

    setUserMenuOpen(open);
   }
  );

  userMenuDropdown?.addEventListener(
   'click',
   (event) => {
    event.stopPropagation();
   }
  );

  document.addEventListener('click', () => {
   setUserMenuOpen(false);
  });

  document.addEventListener('keydown', (event) => {
   if (event.key === 'Escape') {
    setSidebarOpen(false);
    setUserMenuOpen(false);
   }
  });

  document
   .querySelectorAll('.crm-toggle')
   .forEach((button) => {
    button.addEventListener('click', () => {
     const menu = document.getElementById(
      button.dataset.crmMenu
     );

     if (!menu) {
      return;
     }

     const open =
      button.getAttribute('aria-expanded') !== 'true';

     button.setAttribute(
      'aria-expanded',
      open ? 'true' : 'false'
     );

     menu.classList.toggle('open', open);
    });
   });

  const statusSelect =
   document.getElementById('leadStatus');

  const businessSection =
   document.getElementById('businessDetailsSection');

  const noAnswerSection =
   document.getElementById('noAnswerSection');

  const notInterestedSection =
   document.getElementById('notInterestedSection');

  const quotationSection =
   document.getElementById('quotationSection');

  const solutionType =
   document.getElementById('solutionType');

  const callCenterFields =
   document.getElementById('callCenterFields');

  const erpFields =
   document.getElementById('erpFields');

  const disinterestReason =
   document.getElementById('disinterestReason');

  const quotationFile =
   document.getElementById('quotationFile');

  const hasExistingQuotationFile =
   @json($hasQuotationFile);

  const quotationFileDefaultHelp =
   @json($quotationFileHelpText);

  const linesCount =
   document.getElementById('linesCount');

  const extensions =
   document.getElementById('extensions');

  const departments =
   document.getElementById('departments');

  const quotationStageCodes = [
   'quotation',
   'discussion',
   'contract_closed',
   'execution'
  ];

  const selectedStatusCode = () => {
   const option =
    statusSelect.options[statusSelect.selectedIndex];

   return option?.dataset?.code || '';
  };

  const updateSolutionFields = () => {
   const quotationActive =
    quotationStageCodes.includes(
     selectedStatusCode()
    );

   const type = quotationActive
    ? solutionType.value
    : '';

   const callCenterActive =
    type === 'call_center';

   const erpActive =
    type === 'erp';

   callCenterFields.classList.toggle(
    'is-hidden',
    !callCenterActive
   );

   erpFields.classList.toggle(
    'is-hidden',
    !erpActive
   );

   linesCount.required = callCenterActive;
   extensions.required = callCenterActive;
   departments.required = erpActive;
  };

  const updateStatusSections = () => {
   const code = selectedStatusCode();

   const businessActive = [
    'interested',
    'no_answer',
    'meeting',
    'quotation',
    'discussion',
    'contract_closed',
    'execution'
   ].includes(code);

   const noAnswerActive =
    code === 'no_answer';

   const notInterestedActive =
    code === 'not_interested';

   const quotationActive =
    quotationStageCodes.includes(code);

   businessSection.classList.toggle(
    'is-hidden',
    !businessActive
   );

   noAnswerSection.classList.toggle(
    'is-hidden',
    !noAnswerActive
   );

   notInterestedSection.classList.toggle(
    'is-hidden',
    !notInterestedActive
   );

   quotationSection.classList.toggle(
    'is-hidden',
    !quotationActive
   );

   disinterestReason.required =
    notInterestedActive;

   solutionType.required =
    quotationActive;

   quotationFile.required =
    quotationActive
    && !hasExistingQuotationFile;

   updateSolutionFields();
  };

  statusSelect.addEventListener(
   'change',
   updateStatusSections
  );

  solutionType.addEventListener(
   'change',
   updateSolutionFields
  );

  const quotationFileHelp =
   document.getElementById('quotationFileHelp');

  quotationFile?.addEventListener(
   'change',
   () => {
    const file = quotationFile.files?.[0];

    quotationFileHelp?.classList.toggle(
     'has-file',
     Boolean(file)
     || hasExistingQuotationFile
    );

    if (quotationFileHelp) {
     quotationFileHelp.textContent = file
      ? 'تم اختيار الملف: ' + file.name
      : quotationFileDefaultHelp;
    }
   }
  );

  document
   .getElementById('followUpButton')
   .addEventListener('click', () => {
    window.alert(
     'سيتم تفعيل تسجيل المتابعة في المرحلة القادمة.'
    );
   });

  updateStatusSections();
 })();
</script>
</body>
</html>

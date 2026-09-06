<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >
 <title>@yield('title') | CRM v2</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

 <style>
  *{box-sizing:border-box}

  :root{
   --red:#dc2637;
   --dark:#182033;
   --muted:#7e899a;
   --line:#e4e8ef;
   --bg:#f5f6f9;
   --card:#fff;
   --shadow:0 14px 35px #1720330d;
   --blue:#3478f6;
   --green:#169a64;
   --orange:#e59b16;
   --purple:#7b61df
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

  body{
   margin:0;
   background:var(--bg);
   color:var(--dark);
   font-family:var(--font-primary)
  }

  button,
  input,
  select{
   font:inherit
  }

  .transfer-layout{
   min-height:100vh;
   display:flex;
   }

  .transfer-sidebar{
   width:265px;
   min-width:265px;
   min-height:100vh;
   background:#fff;
   border-inline-end:1px solid var(--line);
   z-index:40
  }

  .transfer-sidebar>*
  {
   min-height:100vh
  }

  .transfer-main{
   min-width:0;
   flex:1
  }

  .transfer-topbar{
   position:relative;
   z-index:50;
   min-height:82px;
   display:flex;
   align-items:center;
   gap:16px;
   padding:15px 27px;
   border-bottom:1px solid var(--line);
   background:#fff
  }

  .transfer-menu{
   width:42px;
   height:42px;
   display:none;
   align-items:center;
   justify-content:center;
   border:1px solid var(--line);
   border-radius:10px;
   background:#fff;
   cursor:pointer;
   font-size:20px
  }

  .transfer-page-title{
   min-width:0;
   flex:1
  }

  .transfer-page-title h1{
   margin:0;
   font-size:24px
  }

  .transfer-page-title p{
   margin:6px 0 0;
   color:var(--muted);
   font-size:12px;
   line-height:1.8
  }

  .transfer-top-actions{
   display:flex;
   align-items:center;
   flex-wrap:wrap;
   gap:8px
  }

  .transfer-content{
   width:min(1320px,calc(100% - 36px));
   margin:24px auto 45px
  }

  .transfer-card{
   overflow:hidden;
   margin-bottom:20px;
   border:1px solid var(--line);
   border-radius:18px;
   background:var(--card);
   box-shadow:var(--shadow)
  }
  html.dark-mode .transfer-card{
   background:var(--bg-card,rgba(24,24,27,.75))!important;
   backdrop-filter:var(--glass-blur,blur(16px))!important;
   -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
   border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
   box-shadow:var(--shadow-card,0 10px 30px rgba(0,0,0,.5))!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .transfer-hero{
   padding:25px;
   background:
    linear-gradient(
     120deg,
     #171f31,
     #30394c
    );
   color:#fff
  }
  html.dark-mode .transfer-hero{
   background:linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02))!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important;
   color:#fff!important
  }
  html.dark-mode .transfer-hero small{color:#f87171!important}
  html.dark-mode .transfer-hero h2{color:#ffffff!important}
  html.dark-mode .transfer-hero p{color:#d4d4d8!important}

  .transfer-hero small{
   color:#f2a3ac;
   font-weight:900
  }

  .transfer-hero h2{
   margin:8px 0 7px;
   font-size:25px
  }

  .transfer-hero p{
   max-width:850px;
   margin:0;
   color:#cbd0da;
   font-size:13px;
   line-height:1.9
  }

  .card-head{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:15px;
   padding:18px 21px;
   border-bottom:1px solid var(--line)
  }
  html.dark-mode .card-head{
   border-bottom:1px solid rgba(255,255,255,.08)!important
  }
  html.dark-mode .card-head h3{color:var(--text-primary,#f4f4f5)!important}
  html.dark-mode .card-head p{color:var(--text-muted,#a1a1aa)!important}
  html.dark-mode .card-body{color:var(--text-primary,#f4f4f5)!important}
  html.dark-mode .transfer-topbar{
   background:var(--bg-card,rgba(24,24,27,.75))!important;
   backdrop-filter:var(--glass-blur,blur(16px))!important;
   -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important;
   color:var(--text-primary,#f4f4f5)!important
  }
  html.dark-mode .transfer-page-title h1{color:var(--text-primary,#f4f4f5)!important}
  html.dark-mode .transfer-page-title p{color:var(--text-muted,#a1a1aa)!important}
  .card-body{
   padding:21px
  }

  .grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:15px
  }

  .grid.three{
   grid-template-columns:
    repeat(3,minmax(0,1fr))
  }

  .field{
   display:grid;
   gap:7px
  }

  .field.full{
   grid-column:1/-1
  }

  .field label{
   font-size:12px;
   font-weight:900
  }
  html.dark-mode .field label{
   color:#e4e4e7!important
  }

  .control{
   width:100%;
   min-height:44px;
   padding:10px 12px;
   border:1px solid #d8dee8;
   border-radius:10px;
   background:#fff;
   color:var(--dark);
   outline:none
  }
  html.dark-mode .control{
   background:var(--bg-input,rgba(39,39,42,.65))!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .control:focus{
   border-color:#8badde;
   box-shadow:0 0 0 3px #3478f617
  }
  html.dark-mode .control:focus{
   border-color:rgba(239,68,68,.6)!important;
   background:rgba(39,39,42,.95)!important;
   box-shadow:0 0 0 3px rgba(239,68,68,.2)!important
  }
  html.dark-mode input[type="file"].control{
   background:rgba(255,255,255,.04)!important;
   border:1px dashed rgba(255,255,255,.2)!important;
   color:var(--text-primary,#f4f4f5)!important;
   cursor:pointer
  }
  html.dark-mode input[type="file"].control:hover,
  html.dark-mode input[type="file"].control:focus{
   border-color:rgba(239,68,68,.6)!important;
   background:rgba(239,68,68,.05)!important
  }
  html.dark-mode input[type="file"].control::file-selector-button{
   background:linear-gradient(135deg,#e83243,#bd1728)!important;
   color:#ffffff!important;
   border:none!important;
   border-radius:8px!important;
   padding:6px 14px!important;
   font-weight:800!important;
   cursor:pointer!important
  }

  .help{
   color:var(--muted);
   font-size:10px;
   line-height:1.8
  }
  html.dark-mode .help{
   color:var(--text-muted,#a1a1aa)!important
  }

  .btn{
   min-height:42px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   gap:7px;
   padding:8px 14px;
   border:1px solid #d8dee8;
   border-radius:10px;
   background:#fff;
   color:#243247;
   text-decoration:none;
   font-weight:900;
   font-size:12px;
   cursor:pointer;
   transition:.16s
  }

  .btn:hover{
   transform:translateY(-1px);
   box-shadow:0 7px 17px #17203312
  }

  .btn.primary{
   border-color:#2f70c8;
   background:#3478f6;
   color:#fff
  }

  .btn.success{
   border-color:#168957;
   background:#169a64;
   color:#fff
  }

  .btn.soft{
   background:#f8fafc
  }
  html.dark-mode .btn.soft{
   background:rgba(255,255,255,.08)!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:var(--text-primary,#f4f4f5)!important;
   font-weight:700!important
  }
  html.dark-mode .btn.soft:hover{
   background:rgba(255,255,255,.16)!important;
   border-color:rgba(255,255,255,.25)!important;
   color:#ffffff!important
  }
  html.dark-mode .btn.primary{
   background:linear-gradient(135deg,#e83243,#c91d2e)!important;
   border-color:#ef4444!important;
   color:#ffffff!important;
   font-weight:800!important;
   box-shadow:0 4px 16px rgba(239,68,68,.35)!important
  }
  html.dark-mode .btn.success{
   background:linear-gradient(135deg,#169a64,#137e51)!important;
   border-color:#16a34a!important;
   color:#ffffff!important;
   font-weight:800!important
  }

  .actions{
   display:flex;
   justify-content:flex-end;
   flex-wrap:wrap;
   gap:9px;
   margin-top:18px
  }

  .notice{
   margin-bottom:17px;
   padding:13px 15px;
   border-radius:11px;
   font-size:12px;
   line-height:1.8;
   font-weight:800
  }

  .notice.success{
   border:1px solid #a9d9bb;
   background:#eefaf2;
   color:#146238
  }
  html.dark-mode .notice.success{
   background:rgba(34,197,94,.12)!important;
   border:1px solid rgba(34,197,94,.3)!important;
   color:#86efac!important
  }

  .notice.error{
   border:1px solid #efbcbc;
   background:#fff2f2;
   color:#a62d2d
  }
  html.dark-mode .notice.error{
   background:rgba(239,68,68,.12)!important;
   border:1px solid rgba(239,68,68,.35)!important;
   color:#fca5a5!important
  }

  .notice.info{
   border:1px solid #bcd2ef;
   background:#f1f7ff;
   color:#2b5e9d
  }
  html.dark-mode .notice.info{
   background:rgba(59,130,246,.12)!important;
   border:1px solid rgba(59,130,246,.3)!important;
   color:#93c5fd!important
  }
  .error-list{
   margin:7px 0 0;
   padding-inline-start:20px
  }

  .stat-grid{
   display:grid;
   grid-template-columns:
    repeat(4,minmax(0,1fr));
   gap:12px;
   margin-bottom:18px
  }

  .stat{
   padding:15px;
   border:1px solid var(--line);
   border-radius:13px;
   background:#fff
  }
  html.dark-mode .stat{
   background:rgba(255,255,255,.04)!important;
   border:1px solid rgba(255,255,255,.08)!important;
   color:var(--text-primary,#f4f4f5)!important
  }
  html.dark-mode .stat span{
   color:var(--text-muted,#a1a1aa)!important
  }
  html.dark-mode .stat strong{
   color:var(--text-primary,#f4f4f5)!important
  }

  .stat span{
   display:block;
   color:var(--muted);
   font-size:10px;
   font-weight:900
  }

  .stat strong{
   display:block;
   margin-top:6px;
   font-size:23px
  }

  .status-guide{
   display:grid;
   grid-template-columns:
    repeat(3,minmax(0,1fr));
   gap:9px
  }

  .status-guide-item{
   padding:10px 12px;
   border:1px solid var(--line);
   border-radius:10px;
   background:#fafbfc
  }
  html.dark-mode .status-guide-item{
   background:rgba(255,255,255,.04)!important;
   border:1px solid rgba(255,255,255,.08)!important
  }
  html.dark-mode .status-guide-item strong{
   color:var(--text-primary,#f4f4f5)!important
  }
  html.dark-mode .status-guide-item small{
   color:var(--text-muted,#a1a1aa)!important
  }

  .status-guide-item strong{
   display:block;
   font-size:12px
  }

  .status-guide-item small{
   display:block;
   margin-top:4px;
   color:var(--muted);
   font-size:10px
  }

  .table-wrap{
   overflow:auto;
   border:1px solid var(--line);
   border-radius:13px
  }
  html.dark-mode .table-wrap{
   border-color:rgba(255,255,255,.08)!important
  }

  table{
   width:100%;
   min-width:920px;
   border-collapse:collapse;
   background:#fff
  }
  html.dark-mode table{
   background:transparent!important
  }

  th,
  td{
   padding:11px 10px;
   border-bottom:1px solid #edf0f4;
   text-align:start;
   vertical-align:top;
   font-size:11px
  }
  html.dark-mode td{
   border-bottom:1px solid rgba(255,255,255,.07)!important;
   color:#d4d4d8!important
  }

  th{
   position:sticky;
   top:0;
   z-index:2;
   background:#f7f9fc;
   color:#526076;
   font-weight:900
  }
  html.dark-mode th{
   background:rgba(255,255,255,.06)!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important;
   color:var(--text-muted,#a1a1aa)!important
  }

  .row-errors,
  .row-warnings{
   margin:0;
   padding-inline-start:17px;
   line-height:1.8
  }

  .row-errors{color:#a62d2d}
  html.dark-mode .row-errors{color:#f87171!important}
  .row-warnings{color:#9a6700}
  html.dark-mode .row-warnings{color:#fbbf24!important}

  .badge{
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:5px 8px;
   border-radius:999px;
   font-size:10px;
   font-weight:900
  }
  html.dark-mode .badge.valid{
   background:rgba(34,197,94,.15)!important;
   border:1px solid rgba(34,197,94,.3)!important;
   color:#4ade80!important
  }
  html.dark-mode .badge.duplicate{
   background:rgba(245,158,11,.15)!important;
   border:1px solid rgba(245,158,11,.3)!important;
   color:#fbbf24!important
  }
  html.dark-mode .badge.error{
   background:rgba(239,68,68,.15)!important;
   border:1px solid rgba(239,68,68,.3)!important;
   color:#f87171!important
  }


  .checkbox-grid{
   display:grid;
   grid-template-columns:
    repeat(4,minmax(0,1fr));
   gap:9px
  }

  .check-item{
   min-height:42px;
   display:flex;
   align-items:center;
   gap:8px;
   padding:9px 11px;
   border:1px solid var(--line);
   border-radius:9px;
   background:#fafbfc;
   font-size:11px;
   font-weight:800
  }

  .check-item input{
   width:16px;
   height:16px;
   accent-color:#3478f6
  }

  .transfer-overlay{
   display:none
  }

  @media(max-width:1050px){
   .grid.three{
    grid-template-columns:
     repeat(2,minmax(0,1fr))
   }

   .checkbox-grid{
    grid-template-columns:
     repeat(3,minmax(0,1fr))
   }
  }

  @media(max-width:900px){
   .transfer-sidebar{
    position:fixed;
    top:0;
    right:0;
    bottom:0;
    transform:translateX(105%);
    transition:.2s;
    box-shadow:-18px 0 42px #17203325
   }

   body.transfer-side-open
   .transfer-sidebar{
    transform:translateX(0)
   }

   .transfer-menu{
    display:inline-flex
   }

   .transfer-overlay{
    position:fixed;
    inset:0;
    z-index:30;
    background:#11182755
   }

   body.transfer-side-open
   .transfer-overlay{
    display:block
   }
  }

  @media(max-width:700px){
   .transfer-content{
    width:calc(100% - 18px);
    margin-top:12px
   }

   .transfer-topbar{
    padding:12px
   }

   .transfer-page-title p{
    display:none
   }

   .grid,
   .grid.three,
   .stat-grid,
   .status-guide,
   .checkbox-grid{
    grid-template-columns:1fr
   }

   .field.full{
    grid-column:auto
   }

   .transfer-top-actions{
    display:none
   }

   .card-body{
    padding:16px
   }
   .transfer-hero,
   .hero,
   .form-hero{
    display:none!important
   }
  }
 

  /* CRM TRANSFER SIDEBAR SYNC START */

  /*
   * The shared CRM sidebar partial already
   * renders <aside class="crm-side">.
   * The old transfer wrapper must not create
   * another sidebar-sized box around it.
   */
  .transfer-sidebar{
   display:contents!important
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
   color:inherit;
   text-decoration:none
  }

  .crm-side-brand .logo{
   width:58px;
   height:58px;
   flex:0 0 58px;
   display:block;
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
   gap:5px
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
   font-size:16px;
   font-weight:bold;
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
   border-color:#cf2031;
   background:
    linear-gradient(
     135deg,
     #e83243,
     #c91d2e
    );
   color:#fff;
   box-shadow:
    0 12px 27px #dc26372c
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
   min-width:0;
   flex:1
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
   font-size:11px;
   transition:.2s
  }

  .crm-toggle.active .crm-arrow{
   color:#fff
  }

  .crm-toggle[
   aria-expanded="true"
  ] .crm-arrow{
   transform:rotate(180deg)
  }

  .crm-sub{
   display:grid;
   grid-template-rows:0fr;
   transition:.22s
  }

  .crm-sub.open{
   grid-template-rows:1fr
  }

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

  .crm-sub a:hover{
   color:var(--red);
   background:#fff2f4
  }

  .crm-sub a.active{
   color:var(--red);
   background:#fff0f2
  }

  /*
   * Keep the transfer content as the flexible
   * main area beside the real 288px CRM sidebar.
   */
  .transfer-layout{
   display:flex;
   min-height:100vh;
   }

  .transfer-main{
   min-width:0;
   flex:1 1 auto;
   width:calc(100% - 288px);
   }

  @media(max-width:900px){
   .transfer-layout{
    display:block
   }

   .crm-side{
    position:fixed;
    right:0;
    top:0;
    bottom:0;
    width:min(
     288px,
     calc(100vw - 45px)
    );
    max-width:none;
    transform:translateX(105%);
    transition:.25s;
    box-shadow:
     -20px 0 55px #17203325
   }

   .transfer-main{
    width:100%;
    min-height:100vh
   }

   body.crm-side-open{
    overflow:hidden
   }

   body.crm-side-open .crm-side{
    transform:none
   }

   body.crm-side-open
   .transfer-overlay{
    display:block
   }

   .transfer-overlay{
    position:fixed;
    inset:0;
    z-index:70;
    display:none;
    background:#12182788;
    backdrop-filter:blur(2px)
   }

   .transfer-menu{
    display:inline-flex
   }
  }

  @media(max-width:520px){
   .crm-side-brand small,
   .crm-side-caption{
    font-size:12px
   }

   .crm-sub a{
    font-size:13px
   }
  }

  /* CRM TRANSFER SIDEBAR SYNC END */


/* ── ARABIC RTL SCROLLBAR ON LEFT + RED & THICKER SCROLLBAR ── */
html[dir="rtl"],
html[lang="ar"],
body[dir="rtl"],
[dir="rtl"] {
  direction: rtl !important;
}

html[dir="rtl"] body,
html[lang="ar"] body,
[dir="rtl"] .transfer-layout,
[dir="rtl"] .transfer-main,
[dir="rtl"] .transfer-content,
[dir="rtl"] .table-wrap {
  direction: rtl !important;
}

/* Red & Thicker Scrollbar */
html,
body,
* {
  scrollbar-color: #dc2637 transparent !important;
  scrollbar-width: auto !important;
}

*::-webkit-scrollbar,
::-webkit-scrollbar {
  width: 10px !important;
  height: 10px !important;
}

*::-webkit-scrollbar-track,
::-webkit-scrollbar-track {
  background: transparent !important;
}

*::-webkit-scrollbar-thumb,
::-webkit-scrollbar-thumb {
  background-color: #dc2637 !important;
  border-radius: 9999px !important;
  border: 2px solid transparent !important;
  background-clip: padding-box !important;
}

*::-webkit-scrollbar-thumb:hover,
::-webkit-scrollbar-thumb:hover {
  background-color: #b91c1c !important;
  background-clip: padding-box !important;
}

*::-webkit-scrollbar-thumb:active,
::-webkit-scrollbar-thumb:active {
  background-color: #7f1d1d !important;
  background-clip: padding-box !important;
}

.crm-select-options-list {
  max-height: 240px;
  overflow-y: auto;
  scrollbar-width: thin !important;
  scrollbar-color: rgba(148, 163, 184, 0.4) transparent !important;
  padding-inline-start: 4px !important;
}

.crm-select-options-list::-webkit-scrollbar {
  width: 5px !important;
  height: 5px !important;
}

.crm-select-options-list::-webkit-scrollbar-track {
  background: transparent !important;
}

.crm-select-options-list::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.4) !important;
  border-radius: 9999px !important;
  border: none !important;
}

.crm-select-options-list::-webkit-scrollbar-thumb:hover {
  background-color: rgba(148, 163, 184, 0.7) !important;
}

</style>

 @stack('styles')
</head>
<body>

 {{-- CRM CONDITIONAL PAGE LOADER START --}}
@unless (
 trim(
  $__env->yieldContent(
   'disable-page-loader'
  )
 ) === '1'
)
 @include('partials.page-loader')
@endunless
{{-- CRM CONDITIONAL PAGE LOADER END --}}

 <div class="transfer-layout">
  <div
   class="transfer-sidebar"
   id="transferSidebar"
  >
   @include('partials.crm-sidebar')
  </div>

  <div
   class="transfer-overlay"
   id="transferOverlay"
  ></div>

  <main class="transfer-main">
   <header class="transfer-topbar relative z-50">
     <div class="transfer-page-title">
      <h1 data-ar-label="@yield('page-title-ar')">@yield('page-title')</h1>
      @hasSection('page-description')
      <p>@yield('page-description')</p>
      @endif
     </div>

    <div class="transfer-top-actions">
     @yield('top-actions')
    </div>
    @include('partials.profile-dropdown')
   </header>

   <div class="transfer-content">
    @if (session('success'))
     <div class="notice success">
      {{ session('success') }}
     </div>
    @endif

    @if ($errors->any())
     <div class="notice error">
      <strong>
       {{ __('crm.review_errors') }}
      </strong>

      <ul class="error-list">
       @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
       @endforeach
      </ul>
     </div>
    @endif

    @yield('content')
   </div>
  </main>
 </div>

 <script>
  (() => {
   const body = document.body;

   const menu =
    document.getElementById(
     'transferMenuButton'
    );

   const overlay =
    document.getElementById(
     'transferOverlay'
    );

   const setOpen = (open) => {
    body.classList.toggle(
     'crm-side-open',
     open
    );

    menu?.setAttribute(
     'aria-expanded',
     open ? 'true' : 'false'
    );
   };

   menu?.addEventListener(
    'click',
    () => {
     setOpen(
      !body.classList.contains(
       'crm-side-open'
      )
     );
    }
   );

   overlay?.addEventListener(
    'click',
    () => setOpen(false)
   );

   /*
    * crmSidebarMenuToggleHandler
    * Same submenu behaviour used by
    * the working leads screens.
    */
   document
    .querySelectorAll(
     '.crm-toggle'
    )
    .forEach((button) => {
     button.addEventListener(
      'click',
      () => {
       const submenu =
        document.getElementById(
         button.dataset.crmMenu
         || button.dataset.menu
         || ''
        );

       if (!submenu) {
        return;
       }

       const open =
        button.getAttribute(
         'aria-expanded'
        ) !== 'true';

       button.setAttribute(
        'aria-expanded',
        open ? 'true' : 'false'
       );

       submenu.classList.toggle(
        'open',
        open
       );
      }
     );
    });

   document
    .querySelectorAll(
     '.crm-side a'
    )
    .forEach((link) => {
     link.addEventListener(
      'click',
      () => {
       if (
        window.innerWidth <= 900
       ) {
        setOpen(false);
       }
      }
     );
    });

   document.addEventListener(
    'keydown',
    (event) => {
     if (event.key === 'Escape') {
      setOpen(false);
     }
    }
   );
  })();
 </script>

 @include('partials.transition-popup')

 @stack('scripts')
 <script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
</body>
</html>

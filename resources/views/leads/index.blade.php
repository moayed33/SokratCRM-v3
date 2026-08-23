<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.view_leads') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<style>
:root{
 --red:#dc2637;
 --red-dark:#b81829;
 --dark:#182033;
 --text:#4b5568;
 --muted:#8b94a5;
 --line:#e5e8ef;
 --bg:#f5f6f9;
 --card:#fff;
 --shadow:0 14px 38px #17203312
}
*{box-sizing:border-box}
body{
 margin:0;
 min-width:320px;
 background:
  radial-gradient(circle at 7% 0,#dc26370d,transparent 28rem),
  var(--bg);
 color:var(--dark);
 font-family:var(--font-primary);
 font-size:15px
}
button,input,select{font:inherit}
a{color:inherit}
.shell{
 width:calc(100% - 12px);
 max-width:none;
 margin:0 6px;
 padding:20px 0 45px
}
.topbar{
 min-height:76px;
 display:flex;
 align-items:center;
 gap:18px;
 padding:12px 17px;
 border:1px solid var(--line);
 border-radius:18px;
 background:#fffffff2;
 box-shadow:var(--shadow)
}
.brand{
 display:flex;
 align-items:center;
 gap:11px;
 text-decoration:none
}
.logo{
 width:54px;
 height:54px;
 display:block;
 object-fit:contain
}
.brand-copy strong{
 display:block;
 color:var(--red);
 font:900 20px var(--font-primary)
}
.brand-copy small{
 display:block;
 margin-top:4px;
 color:var(--muted);
 font-size:9px
}
.page-title{
 min-width:0;
 flex:1;
 padding-inline-start:14px;
 border-inline-start:1px solid var(--line)
}
.page-title h1{
 margin:0;
 font-size:20px
}
.page-title p{
 margin:5px 0 0;
 color:var(--muted);
 font-size:9px
}
.top-actions{
 display:flex;
 align-items:center;
 flex-wrap:wrap;
 gap:8px
}
.btn{
 min-height:42px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 gap:7px;
 padding:8px 15px;
 border:1px solid transparent;
 border-radius:11px;
 background:linear-gradient(135deg,#e83243,#c91d2e);
 color:#fff;
 text-decoration:none;
 font-size:10px;
 font-weight:900;
 cursor:pointer;
 box-shadow:0 10px 24px #dc26372c;
 transition:.2s
}
.btn:hover{
 transform:translateY(-2px);
 background:linear-gradient(135deg,#cf2334,#a91625)
}
.btn.secondary{
 border-color:var(--line);
 background:#fff;
 color:#596477;
 box-shadow:none
}
.btn.dark{
 background:var(--dark);
 box-shadow:none
}
.user-chip{
 min-height:42px;
 display:flex;
 align-items:center;
 gap:8px;
 padding:6px 10px;
 border:1px solid var(--line);
 border-radius:11px;
 background:#fff
}
.user-avatar{
 width:30px;
 height:30px;
 display:grid;
 place-items:center;
 border-radius:9px;
 background:var(--dark);
 color:#fff;
 font-weight:900
}
.user-chip strong{
 display:block;
 max-width:100px;
 overflow:hidden;
 text-overflow:ellipsis;
 font-size:10px
}
.user-chip small{
 display:block;
 margin-top:3px;
 color:var(--muted);
 font-size:8px
}
.hero{
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:25px;
 min-height:170px;
 margin-top:16px;
 padding:28px 30px;
 border-radius:22px;
 background:linear-gradient(120deg,#171f31,#30394c);
 color:#fff;
 box-shadow:0 22px 50px #1720332d
}
.eyebrow{
 color:#f3a6af;
 font-size:10px;
 font-weight:bold
}
.hero h2{
 margin:10px 0 8px;
 font-size:clamp(25px,4vw,38px)
}
.hero p{
 max-width:680px;
 margin:0;
 color:#c9ced8;
 font-size:11px;
 line-height:1.8
}
.hero-count{
 min-width:150px;
 min-height:112px;
 display:flex;
 flex-direction:column;
 align-items:center;
 justify-content:center;
 padding:15px;
 border:1px solid #ffffff1f;
 border-radius:18px;
 background:#ffffff0d
}
.hero-count strong{
 font:900 42px var(--font-primary)
}
.hero-count span{
 margin-top:7px;
 color:#cdd2dc;
 font-size:9px;
 font-weight:bold
}
.section{
 margin-top:16px;
 padding:17px;
 border:1px solid var(--line);
 border-radius:18px;
 background:var(--card);
 box-shadow:var(--shadow)
}
.section-head{
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:15px;
 margin-bottom:13px
}
.section-head h2{
 margin:0;
 font-size:14px
}
.section-head p{
 margin:5px 0 0;
 color:var(--muted);
 font-size:9px
}
.all-statuses{
 padding:7px 10px;
 border-radius:9px;
 background:#f1f3f6;
 color:#687386;
 text-decoration:none;
 font-size:9px;
 font-weight:bold;
 transition:.2s
}
.all-statuses:hover{
 background:#e4e8ef;
 color:#334155
}
.all-statuses.active{
 background:#fff0f2;
 color:var(--red)
}
html.dark-mode .all-statuses{
 background:rgba(255,255,255,.06)!important;
 border:1px solid rgba(255,255,255,.1)!important;
 color:var(--text-muted,#a1a1aa)!important;
 font-weight:700!important
}
html.dark-mode .all-statuses:hover{
 background:rgba(255,255,255,.12)!important;
 border-color:rgba(255,255,255,.2)!important;
 color:var(--text-primary,#f4f4f5)!important
}
html.dark-mode .all-statuses.active{
 background:rgba(239,68,68,.15)!important;
 border:1px solid rgba(239,68,68,.4)!important;
 color:#f87171!important;
 font-weight:800!important;
 box-shadow:0 4px 15px rgba(239,68,68,.2)!important
}
.status-grid{
 display:grid;
 grid-template-columns:repeat(9,minmax(115px,1fr));
 gap:8px
}
.status-card{
 --status-color:#64748b;
 min-width:0;
 min-height:86px;
 display:flex;
 flex-direction:column;
 justify-content:space-between;
 padding:11px;
 border:1px solid var(--line);
 border-radius:13px;
 background:#fafbfc;
 color:inherit;
 text-decoration:none;
 transition:.2s
}
.status-card:hover,
.status-card.selected{
 border-color:var(--status-color);
 transform:translateY(-2px);
 box-shadow:0 10px 25px #17203310
}
.status-card.selected{
 background:#fff
}
html.dark-mode .status-card{
 background:var(--bg-card,rgba(24,24,27,.75))!important;
 backdrop-filter:var(--glass-blur,blur(16px))!important;
 -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
 border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
 box-shadow:0 4px 18px rgba(0,0,0,.25)!important;
 color:var(--text-primary,#f4f4f5)!important
}
html.dark-mode .status-card:hover{
 background:rgba(39,39,42,.75)!important;
 border-color:var(--status-color,rgba(255,255,255,.25))!important;
 transform:translateY(-2px);
 box-shadow:0 8px 24px rgba(0,0,0,.35),0 0 0 1px color-mix(in srgb,var(--status-color) 40%,transparent)!important
}
html.dark-mode .status-card.selected{
 background:color-mix(in srgb,var(--status-color) 12%,var(--bg-card,rgba(24,24,27,.85)))!important;
 border-color:var(--status-color)!important;
 box-shadow:0 8px 28px rgba(0,0,0,.4),0 0 0 1px var(--status-color),inset 0 1px 0 rgba(255,255,255,.1)!important;
 color:#ffffff!important
}
.status-name{
 display:flex;
 align-items:center;
 gap:6px;
 min-width:0
}
.status-dot{
 width:8px;
 height:8px;
 flex:0 0 8px;
 border-radius:50%;
 background:var(--status-color)
}
html.dark-mode .status-dot{
 box-shadow:0 0 8px color-mix(in srgb,var(--status-color) 80%,transparent)
}
.status-name strong{
 overflow:hidden;
 text-overflow:ellipsis;
 white-space:nowrap;
 font-size:10px
}
html.dark-mode .status-name strong{
 color:var(--text-primary,#f4f4f5)!important;
 font-weight:700
}
html.dark-mode .status-card.selected .status-name strong{
 color:#ffffff!important
}
.status-card b{
 color:var(--status-color);
 font:900 23px var(--font-primary)
}
html.dark-mode .status-card b{
 text-shadow:0 0 12px color-mix(in srgb,var(--status-color) 25%,transparent)
}
.status-card small{
 color:var(--muted);
 font-size:8px
}
html.dark-mode .status-card small{
 color:var(--text-muted,#a1a1aa)!important;
 font-weight:600
}
html.dark-mode .status-card.selected small{
 color:rgba(255,255,255,.75)!important
}
.filters{
 display:grid;
 grid-template-columns:minmax(220px,1.6fr) repeat(4,minmax(145px,1fr)) auto;
 align-items:end;
 gap:10px;
 background:transparent;
 border:none;
 box-shadow:none;
 padding:0
}
html.dark-mode .filters{
 background:transparent!important;
 border:none!important;
 box-shadow:none!important;
 padding:0!important
}
.field label{
 display:block;
 margin:0 3px 6px;
 color:#757f91;
 font-size:9px;
 font-weight:bold
}
html.dark-mode .field label{
 color:var(--text-muted,#a1a1aa)!important
}
.field input,
.field select{
 width:100%;
 height:42px;
 padding:0 11px;
 border:1px solid #dfe3ea;
 border-radius:10px;
 background:#fafbfc;
 color:#4f5b6e;
 font-size:10px;
 outline:none;
 transition:.2s
}
.field input:focus,
.field select:focus{
 border-color:#e98692;
 background:#fff;
 box-shadow:0 0 0 3px #dc263710
}
html.dark-mode .field input,
html.dark-mode .field select{
 background:var(--bg-input,rgba(39,39,42,.65))!important;
 border:1px solid rgba(255,255,255,.14)!important;
 color:var(--text-primary,#f4f4f5)!important
}
html.dark-mode .field input:focus,
html.dark-mode .field select:focus{
 border-color:rgba(239,68,68,.6)!important;
 background:rgba(39,39,42,.95)!important;
 box-shadow:0 0 0 3px rgba(239,68,68,.2)!important
}
html.dark-mode .field select option{
 background:#18181b!important;
 color:#f4f4f5!important
}
.filter-buttons{
 display:flex;
 gap:7px
}
.filter-buttons .btn{
 min-width:78px;
 padding-inline:12px
}
html.dark-mode .filter-buttons .btn.secondary{
 background:rgba(255,255,255,.08)!important;
 border:1px solid rgba(255,255,255,.14)!important;
 color:#f4f4f5!important;
 font-weight:700!important
}
html.dark-mode .filter-buttons .btn.secondary:hover{
 background:rgba(255,255,255,.16)!important;
 border-color:rgba(255,255,255,.28)!important;
 color:#ffffff!important
}
.results{
 overflow:hidden;
 padding:0
}
.results-head{
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:15px;
 padding:17px;
 border-bottom:1px solid var(--line)
}
.results-head h2{
 margin:0;
 font-size:14px
}
html.dark-mode .results-head h2{
 color:var(--text-primary,#f4f4f5)!important
}
.results-head p{
 margin:5px 0 0;
 color:var(--muted);
 font-size:9px
}
html.dark-mode .results-head p{
 color:var(--text-muted,#a1a1aa)!important
}
.result-count{
 padding:7px 10px;
 border-radius:9px;
 background:#f1f3f6;
 color:#667184;
 font:800 9px var(--font-primary)
}
html.dark-mode .result-count{
 background:rgba(255,255,255,.08)!important;
 border:1px solid rgba(255,255,255,.12)!important;
 color:var(--text-primary,#f4f4f5)!important;
 font-weight:800!important
}
.table-wrap{
 overflow-x:auto
}
table{
 width:100%;
 min-width:1490px;
 border-collapse:collapse
}
th{
 padding:12px 14px;
 border-bottom:1px solid var(--line);
 background:#fafbfc;
 color:#8992a1;
 text-align:start;
 font-size:8px;
 white-space:nowrap
}
html.dark-mode th{
 background:rgba(255,255,255,.04)!important;
 border-bottom:1px solid rgba(255,255,255,.08)!important;
 color:var(--text-muted,#a1a1aa)!important;
 font-weight:700!important
}
td{
 padding:13px 14px;
 border-bottom:1px solid #eef0f4;
 color:#596477;
 font-size:9px;
 vertical-align:middle
}
html.dark-mode td{
 background:transparent!important;
 border-bottom:1px solid rgba(255,255,255,.07)!important;
 color:#d4d4d8!important
}
tbody tr{
 transition:.18s
}
tbody tr:hover{
 background:#fff9fa
}
html.dark-mode tbody tr:hover{
 background:rgba(255,255,255,.04)!important
}
.customer{
 display:flex;
 align-items:center;
 gap:9px
}
.customer-avatar{
 width:37px;
 height:37px;
 flex:0 0 37px;
 display:grid;
 place-items:center;
 border-radius:11px;
 background:var(--dark);
 color:#fff;
 font-weight:900
}
.customer strong{
 display:block;
 color:var(--dark);
 font-size:10px
}
html.dark-mode .customer strong{
 color:var(--text-primary,#f4f4f5)!important
}
.customer small{
 display:block;
 max-width:190px;
 margin-top:4px;
 overflow:hidden;
 text-overflow:ellipsis;
 color:var(--muted);
 font-size:8px;
 white-space:nowrap
}
html.dark-mode .customer small{
 color:var(--text-muted,#a1a1aa)!important
}
html.dark-mode .customer-avatar{
 background:rgba(255,255,255,.12)!important;
 color:#f4f4f5!important;
 border:1px solid rgba(255,255,255,.14)!important
}
html.dark-mode .contact a{
 color:#d4d4d8!important
}
html.dark-mode .contact a:hover{
 color:#f87171!important
}
html.dark-mode .stage-name{
 color:var(--text-muted,#a1a1aa)!important
}
html.dark-mode .status-badge{
 background:color-mix(in srgb,var(--status-color,#64748b) 16%,rgba(24,24,27,.85))!important;
 border:1px solid color-mix(in srgb,var(--status-color,#64748b) 40%,transparent)!important;
 color:#ffffff!important;
 font-weight:700!important
}
html.dark-mode .follow-up{
 background:rgba(245,158,11,.14)!important;
 border:1px solid rgba(245,158,11,.35)!important;
 color:#fbbf24!important;
 font-weight:600!important
}
.contact{
 display:grid;
 gap:5px
}
.contact a{
 color:#566174;
 text-decoration:none
}
.contact a:hover{
 color:var(--red)
}

.lead-actions-cell{
 min-width:365px
}

.lead-actions{
 display:flex;
 align-items:center;
 gap:6px;
 white-space:nowrap
}

.lead-action{
 min-height:36px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 gap:5px;
 padding:6px 10px;
 border:1px solid var(--line);
 border-radius:9px;
 background:#fff;
 color:#556174;
 text-decoration:none;
 font-size:11px;
 font-weight:900;
 cursor:pointer;
 transition:.18s
}

.lead-action:hover{
 transform:translateY(-1px);
 box-shadow:0 7px 17px #17203312
}

.lead-action.call-action{
 border-color:#b8cceb;
 background:#f3f7fd;
 color:#275a9c
}
html.dark-mode .lead-action{
 font-weight:700!important;
 border-radius:9px!important;
 text-shadow:none!important;
 transition:all .18s ease!important
}
html.dark-mode .lead-action.call-action{
 background:rgba(59,130,246,.15)!important;
 border:1px solid rgba(59,130,246,.35)!important;
 color:#60a5fa!important
}
html.dark-mode .lead-action.call-action:hover{
 background:rgba(59,130,246,.25)!important;
 border-color:rgba(59,130,246,.55)!important;
 color:#93c5fd!important;
 transform:translateY(-1px);
 box-shadow:0 4px 14px rgba(59,130,246,.25)!important
}
.lead-action.followup-action{
 border-color:#e2c993;
 background:#fff9ed;
 color:#8d6515
}
html.dark-mode .lead-action.followup-action{
 background:rgba(245,158,11,.15)!important;
 border:1px solid rgba(245,158,11,.35)!important;
 color:#fbbf24!important
}
html.dark-mode .lead-action.followup-action:hover{
 background:rgba(245,158,11,.25)!important;
 border-color:rgba(245,158,11,.55)!important;
 color:#fcd34d!important;
 transform:translateY(-1px);
 box-shadow:0 4px 14px rgba(245,158,11,.25)!important
}
.lead-action.whatsapp-action{
 border-color:#a9dbbd;
 background:#effaf3;
 color:#167744
}
html.dark-mode .lead-action.whatsapp-action{
 background:rgba(34,197,94,.15)!important;
 border:1px solid rgba(34,197,94,.35)!important;
 color:#4ade80!important
}
html.dark-mode .lead-action.whatsapp-action:hover{
 background:rgba(34,197,94,.25)!important;
 border-color:rgba(34,197,94,.55)!important;
 color:#86efac!important;
 transform:translateY(-1px);
 box-shadow:0 4px 14px rgba(34,197,94,.25)!important
}
.lead-action.is-disabled{
 opacity:.42;
 cursor:not-allowed;
 transform:none;
 box-shadow:none
}
html.dark-mode .lead-action.is-disabled{
 background:rgba(255,255,255,.04)!important;
 border:1px solid rgba(255,255,255,.08)!important;
 color:#71717a!important;
 opacity:.45!important;
 cursor:not-allowed!important;
 transform:none!important;
 box-shadow:none!important
}
.lead-more{
 position:relative;
 flex:0 0 auto
}
.lead-more summary{
 width:36px;
 height:36px;
 display:grid;
 place-items:center;
 padding:0;
 border:1px solid var(--line);
 border-radius:9px;
 background:#fff;
 color:#596477;
 list-style:none;
 cursor:pointer;
 font-size:20px;
 font-weight:900;
 line-height:1
}
.lead-more summary::-webkit-details-marker{
 display:none
}
.lead-more summary:hover,
.lead-more[open] summary{
 border-color:#d9a4ab;
 background:#fff4f5;
 color:var(--red)
}
html.dark-mode .lead-more summary{
 background:rgba(255,255,255,.08)!important;
 border:1px solid rgba(255,255,255,.14)!important;
 color:#f4f4f5!important
}
html.dark-mode .lead-more summary:hover,
html.dark-mode .lead-more[open] summary{
 background:rgba(239,68,68,.15)!important;
 border-color:rgba(239,68,68,.4)!important;
 color:#f87171!important
}
.lead-menu{
 position:fixed;
 z-index:1000;
 width:165px;
 padding:6px;
 border:1px solid var(--line);
 border-radius:11px;
 background:#fff;
 box-shadow:0 17px 38px #17203328
}
html.dark-mode .lead-menu{
 background:#18181b!important;
 border:1px solid rgba(255,255,255,.12)!important;
 box-shadow:0 20px 50px rgba(0,0,0,.7)!important
}
.lead-menu a,
.lead-menu button{
 width:100%;
 min-height:39px;
 display:flex;
 align-items:center;
 gap:8px;
 padding:7px 10px;
 border:0;
 border-radius:8px;
 background:transparent;
 color:#525e70;
 text-decoration:none;
 text-align:start;
 font-size:12px;
 font-weight:900;
 cursor:pointer
}
.lead-menu a:hover,
.lead-menu button:hover{
 background:#f4f5f7
}
html.dark-mode .lead-menu a,
html.dark-mode .lead-menu button{
 color:#f4f4f5!important
}
html.dark-mode .lead-menu a:hover,
html.dark-mode .lead-menu button:hover{
 background:rgba(255,255,255,.08)!important;
 color:#ffffff!important
}
.lead-menu form{
 margin:4px 0 0;
 padding-top:4px;
 border-top:1px solid var(--line)
}
html.dark-mode .lead-menu form{
 border-top:1px solid rgba(255,255,255,.08)!important
}
.lead-menu .delete-action{
 color:#c52233
}
.lead-menu .delete-action:hover{
 background:#fff0f2
}
html.dark-mode .lead-menu .delete-action{
 color:#f87171!important
}
html.dark-mode .lead-menu .delete-action:hover{
 background:rgba(239,68,68,.15)!important
}
.flash-success{
 display:flex;
 align-items:center;
 gap:10px;
 margin-bottom:14px;
 padding:13px 15px;
 border:1px solid #a9d8bd;
 border-radius:12px;
 background:#effaf3;
 color:#237448;
 font-size:13px;
 font-weight:900
}

.flash-success::before{
 content:"✓";
 width:27px;
 height:27px;
 display:grid;
 place-items:center;
 flex:0 0 27px;
 border-radius:50%;
 background:#d9f2e3;
 color:#197043
}

@media(max-width:800px){
 .lead-actions-cell{
  min-width:345px
 }

 .lead-action{
  padding-inline:8px
 }
}
.muted{
 color:var(--muted)
}
.status-badge{
 --status-color:#64748b;
 display:inline-flex;
 align-items:center;
 gap:6px;
 padding:7px 9px;
 border:1px solid var(--line);
 border-radius:9px;
 background:#fff;
 color:var(--status-color);
 font-size:9px;
 font-weight:900;
 white-space:nowrap
}
.stage-name{
 display:block;
 margin-top:5px;
 color:var(--muted);
 font-size:8px
}
.follow-up{
 display:inline-flex;
 align-items:center;
 padding:7px 9px;
 border-radius:9px;
 background:#f3f5f8;
 color:#657083;
 white-space:nowrap
}
.empty-state{
 min-height:330px;
 display:flex;
 flex-direction:column;
 align-items:center;
 justify-content:center;
 padding:35px;
 text-align:center
}
.empty-icon{
 width:68px;
 height:68px;
 display:grid;
 place-items:center;
 border-radius:20px;
 background:#f2f4f7;
 color:#8d96a5;
 font-size:27px
}
.empty-state h3{
 margin:16px 0 7px;
 font-size:15px
}
.empty-state p{
 max-width:480px;
 margin:0 0 16px;
 color:var(--muted);
 font-size:10px;
 line-height:1.8
}
.pagination{
 display:flex;
 align-items:center;
 justify-content:center;
 gap:10px;
 padding:15px;
 border-top:1px solid var(--line)
}
.pagination a,
.pagination span{
 min-height:36px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 padding:7px 12px;
 border:1px solid var(--line);
 border-radius:9px;
 background:#fff;
 color:#657083;
 text-decoration:none;
 font-size:9px;
 font-weight:bold
}
.pagination .disabled{
 opacity:.45
}
.pagination .page-info{
 border:0;
 background:transparent
}
@media(max-width:1250px){
 .status-grid{grid-template-columns:repeat(5,1fr)}
 .filters{grid-template-columns:repeat(3,1fr)}
 .filter-buttons{grid-column:span 3}
}
@media(max-width:800px){
 .shell{width:calc(100% - 12px);margin:0 6px;padding-top:10px}
 .topbar{align-items:flex-start;flex-wrap:wrap}
 .page-title{order:3;width:100%;flex-basis:100%;padding:12px 0 0;border:0;border-top:1px solid var(--line)}
 .top-actions{margin-inline-start:auto}
 .user-chip{display:none}
 .hero{align-items:flex-start;flex-direction:column}
 .hero-count{width:100%;min-height:90px}
 .status-grid{grid-template-columns:repeat(3,1fr)}
 .filters{grid-template-columns:1fr 1fr}
 .filter-buttons{grid-column:span 2}
}
@media(max-width:520px){
 .brand-copy{display:none}
 .top-actions{width:100%}
 .top-actions .btn{flex:1}
 .hero{padding:23px 19px}
 .status-grid{grid-template-columns:1fr 1fr}
 .filters{grid-template-columns:1fr}
 .filter-buttons{grid-column:auto}
 .filter-buttons .btn{flex:1}
}
@media(prefers-reduced-motion:reduce){
 *{transition:none!important}
}

/* CRM leads page sidebar */
.crm-app{
 display:flex;
 min-height:100vh;
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
 height:58px
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
 font-size:10px
}
.crm-side-caption{
 margin:0 10px 10px;
 color:#9aa2b0;
 font-size:10px;
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
 background:linear-gradient(135deg,#e83243,#c91d2e);
 color:#fff;
 box-shadow:0 12px 27px #dc26372c
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
 font:800 10px var(--font-primary)
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
.crm-toggle[aria-expanded=true] .crm-arrow{
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
 font-size:11px;
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
.crm-main{
 flex:1 1 auto;
 width:calc(100% - 288px);
 min-width:0;
}
.crm-overlay{
 display:none
}
.crm-menu-button{
 display:none;
 width:42px;
 height:42px;
 flex:0 0 42px;
 place-items:center;
 border:1px solid var(--line);
 border-radius:11px;
 background:#fff;
 color:var(--dark);
 font-size:20px;
 cursor:pointer
}
.topbar>.brand{
 display:none
}
@media(max-width:900px){
 .crm-app{
  display:block
 }
 .crm-side{
  position:fixed;
  right:0;
  top:0;
  width:min(288px,calc(100vw - 45px));
  max-width:none;
  transform:translateX(105%);
  transition:.25s;
  box-shadow:-20px 0 55px #17203325
 }
 .crm-main{
  width:100%;
  min-height:100vh
 }
 .crm-overlay{
  position:fixed;
  inset:0;
  display:none;
  background:#12182788;
  backdrop-filter:blur(2px);
  z-index:70
 }
 body.crm-side-open{
  overflow:hidden
 }
 body.crm-side-open .crm-side{
  transform:none
 }
 body.crm-side-open .crm-overlay{
  display:block
 }
 .crm-menu-button{
  display:grid
 }
 .topbar>.brand{
  display:flex
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


/* LEADS READABILITY FONT SCALE START */
body{
 font-size:16px
}
.page-title h1{
 font-size:23px
}
.page-title p{
 font-size:12px
}
.btn{
 font-size:13px
}
.user-chip strong{
 font-size:12px
}
.user-chip small{
 font-size:10px
}
.eyebrow{
 font-size:12px
}
.hero p{
 font-size:14px
}
.hero-count span{
 font-size:12px
}
.section-head h2,
.results-head h2{
 font-size:17px
}
.section-head p,
.results-head p{
 font-size:12px
}
.all-statuses{
 font-size:12px
}
.status-name strong{
 font-size:13px
}
.status-card small{
 font-size:11px
}
.field label{
 font-size:12px
}
.field input,
.field select{
 font-size:13px
}
.result-count{
 font-size:12px
}
th{
 font-size:12px
}
td{
 font-size:13px
}
.customer strong{
 font-size:14px
}
.customer small{
 font-size:11px
}
.status-badge{
 font-size:12px
}
.stage-name{
 font-size:11px
}
.follow-up{
 font-size:12px
}
.empty-state h3{
 font-size:18px
}
.empty-state p{
 font-size:13px
}
.pagination a,
.pagination span{
 font-size:12px
}
.crm-side-brand small{
 font-size:12px
}
.crm-side-caption{
 font-size:13px
}
.crm-link,
.crm-toggle{
 font-size:16px
}
.crm-sub a{
 font-size:14px
}
.crm-count{
 font-size:11px
}
/* LEADS READABILITY FONT SCALE END */


.bulk-actions-bar{
 min-height:58px;
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:14px;
 margin:0 14px 14px;
 padding:10px 13px;
 border:1px solid #b7cdea;
 border-radius:12px;
 background:#f2f7fd;
 box-shadow:0 8px 22px #1720330c
}

.bulk-actions-bar[hidden]{
 display:none!important
}

.bulk-actions-summary{
 display:flex;
 align-items:center;
 gap:9px;
 color:#365c8e;
 font-size:12px;
 font-weight:800
}

.bulk-actions-summary strong{
 color:#174f92;
 font-size:15px
}

.bulk-actions-icon{
 width:29px;
 height:29px;
 display:grid;
 place-items:center;
 border-radius:50%;
 background:#dceafb;
 color:#1d5caa;
 font-size:14px;
 font-weight:900
}

.bulk-actions-buttons{
 display:flex;
 align-items:center;
 gap:8px
}

.bulk-export-button,
.bulk-clear-button{
 min-height:38px;
 display:inline-flex;
 align-items:center;
 justify-content:center;
 padding:7px 13px;
 border-radius:9px;
 font-family:inherit;
 font-size:11px;
 font-weight:900;
 cursor:pointer;
 transition:.18s
}

.bulk-export-button{
 border:1px solid #79b38e;
 background:#eaf8ef;
 color:#126837
}

.bulk-export-button:hover:not(:disabled){
 transform:translateY(-1px);
 background:#ddf3e5;
 box-shadow:0 7px 17px #17203312
}

.bulk-export-button:disabled{
 opacity:.45;
 cursor:not-allowed
}

.bulk-clear-button{
 border:1px solid #d5dce6;
 background:#fff;
 color:#687487
}

.bulk-clear-button:hover{
 border-color:#b9c4d2;
 background:#f7f8fa
}

.bulk-export-button:focus-visible,
.bulk-clear-button:focus-visible,
.lead-select-checkbox:focus-visible,
.lead-select-all:focus-visible{
 outline:3px solid #a9c4e8;
 outline-offset:2px
}

.bulk-export-error{
 margin:0 14px 12px;
 padding:10px 13px;
 border:1px solid #efb9b9;
 border-radius:10px;
 background:#fff1f1;
 color:#a62d2d;
 font-size:11px;
 font-weight:800
}

.select-column{
 width:48px;
 min-width:48px;
 padding-inline:10px!important;
 text-align:center
}

.lead-select-checkbox,
.lead-select-all{
 width:17px;
 height:17px;
 margin:0;
 accent-color:#2c66ad;
 cursor:pointer
}

.lead-row.is-selected{
 background:#f1f7ff
}

.lead-row.is-selected:hover{
 background:#eaf3ff
}

@media(max-width:700px){
 .bulk-actions-bar{
  align-items:stretch;
  flex-direction:column
 }

 .bulk-actions-buttons{
  width:100%
 }

 .bulk-export-button,
 .bulk-clear-button{
  flex:1
 }
}


.customer-link{
 width:max-content;
 max-width:100%;
 padding:5px;
 margin:-5px;
 border-radius:11px;
 color:inherit;
 text-decoration:none;
 transition:.18s
}

.customer-link:hover{
 background:#edf4fd;
 transform:translateY(-1px)
}

.customer-link:hover strong{
 color:#245f9f
}

.customer-link:focus-visible{
 outline:3px solid #a9c4e8;
 outline-offset:2px
}


.quotation-action{
 border-color:#9cbde7;
 background:#edf5ff;
 color:#245f9f
}

.quotation-action:hover{
 border-color:#759fd3;
 background:#e1eeff;
 color:#174f91
}

</style>
</head>

<body>
@include('partials.page-loader')

<div class="crm-app">
 <div
  class="crm-overlay"
  id="crmSidebarOverlay"
  aria-hidden="true"
 ></div>

 @include('partials.crm-sidebar')

 <main class="crm-main">
  <div class="shell">

 <header class="topbar">
  <button
   class="crm-menu-button"
   id="crmMenuButton"
   type="button"
   aria-label="{{ __('crm.open_main_menu') }}"
   aria-controls="crmSidebar"
   aria-expanded="false"
  >
   ☰
  </button>
  <a class="brand" href="{{ route('dashboard') }}">
   <img
    class="logo"
    src="{{ asset('images/sokrat-pro-tech.png') }}"
    alt="Sokrat Pro Tech"
   >
   <span class="brand-copy">
    <strong>SokratCRM</strong>
    <small>{{ __('crm.crm_system') }}</small>
   </span>
  </a>

  <div class="page-title">
   <h1>{{ __('crm.view_leads') }}</h1>
   <p>{{ __('crm.leads_page_subtitle') }}</p>
  </div>

  <div class="top-actions">
   @can('leads.create')
   <a class="btn" href="{{ route('v2.leads.create') }}">
    ＋ {{ __('crm.add_lead') }}
   </a>
   @endcan
  </div>

  @include('partials.profile-dropdown')
 </header>

 <section class="hero">
  <div>
   <span class="eyebrow">{{ __('crm.lead_management') }}</span>
   <h2>{{ __('crm.all_leads_one_place') }}</h2>
   <p>
    {{ __('ابحث وفلتر العملاء حسب الحالة والمصدر والموظف وموعد المتابعة، مع عرض المرحلة الحالية وبيانات التواصل الخاصة بكل عميل.') }}
   </p>
  </div>

  <div class="hero-count">
   <strong>{{ number_format($totalLeads) }}</strong>
   <span>{{ __('crm.total_leads') }}</span>
  </div>
 </section>

 <section class="section">
  <header class="section-head">
   <div>
    <h2>{{ __('crm.lead_statuses') }}</h2>
    <p>{{ __('crm.choose_status_to_view') }}</p>
   </div>

   <a
    class="all-statuses {{ $filters['status'] === '' ? 'active' : '' }}"
    href="{{ route('v2.leads', $queryWithoutStatus) }}"
   >
    {{ __('crm.all_states') }}
   </a>
  </header>

  <div class="status-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
   @foreach ($statuses as $status)
    @php
     $statusColor = preg_match(
      '/^#[0-9a-fA-F]{6}$/',
      (string) $status->color
     )
      ? $status->color
      : '#64748b';

     $statusQuery = $filters['status'] === $status->code
      ? $queryWithoutStatus
      : array_merge(
       $queryWithoutStatus,
       ['status' => $status->code]
      );
    @endphp

    <a
     class="status-card {{ $filters['status'] === $status->code ? 'selected' : '' }}"
     href="{{ route('v2.leads', $statusQuery) }}"
     style="--status-color:{{ $statusColor }}"
    >
     <span class="status-name">
      <i class="status-dot"></i>
      <strong>{{ __($status->name_ar) }}</strong>
     </span>

     <b>{{ number_format($status->leads_count) }}</b>

     <small>
      {{ $status->stage?->name_ar ? __($status->stage->name_ar) : __('بدون مرحلة') }}
     </small>
    </a>
   @endforeach
  </div>
 </section>

 <section class="section">
  <form
   class="filters"
   id="leadFilters"
   dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
   method="GET"
   action="{{ route('v2.leads') }}"
  >
   <div class="field">
    <label for="leadSearch">{{ __('البحث') }}</label>
    <input
     id="leadSearch"
     type="search"
     name="q"
     value="{{ $filters['q'] }}"
     placeholder="{{ __('crm.lead_search_placeholder') }}"
    >
   </div>

   <div class="field">
    <label for="leadStatus">{{ __('crm.status') }}</label>
    <select id="leadStatus" name="status">
     <option value="">{{ __('crm.all_states') }}</option>

     @foreach ($statuses as $status)
      <option
       value="{{ $status->code }}"
       @selected($filters['status'] === $status->code)
      >
       {{ $status->name_ar }}
      </option>
     @endforeach
    </select>
   </div>

   <div class="field">
    <label for="leadSource">{{ __('crm.source') }}</label>
    <select id="leadSource" name="source">
     <option value="">{{ __('crm.all_sources') }}</option>

     @foreach ($sources as $source)
      <option
       value="{{ $source }}"
       @selected($filters['source'] === $source)
      >
       {{ $source }}
      </option>
     @endforeach
    </select>
   </div>

   <div class="field">
    <label for="leadEmployee">{{ __('الموظف') }}</label>
    <select id="leadEmployee" name="employee">
     <option value="">{{ __('كل الموظفين') }}</option>

     @foreach ($employees as $employee)
      <option
       value="{{ $employee }}"
       @selected($filters['employee'] === $employee)
      >
       {{ $employee }}
      </option>
     @endforeach
    </select>
   </div>

   <div class="field">
    <label for="leadFollowUp">{{ __('crm.next_followup') }}</label>
    <select id="leadFollowUp" name="follow_up">
     <option value="">{{ __('crm.all_appointments') }}</option>
     <option value="today" @selected($filters['follow_up'] === 'today')>
      {{ __('اليوم') }}
     </option>
     <option value="upcoming" @selected($filters['follow_up'] === 'upcoming')>
      {{ __('crm.upcoming') }}
     </option>
     <option value="overdue" @selected($filters['follow_up'] === 'overdue')>
      {{ __('crm.overdue') }}
     </option>
     <option value="none" @selected($filters['follow_up'] === 'none')>
      {{ __('crm.no_date') }}
     </option>
    </select>
   </div>

   <div class="field">
    <label for="leadSort">{{ __('crm.sort') }}</label>
    <select id="leadSort" name="sort">
     <option value="latest" @selected($filters['sort'] === 'latest')>
      {{ __('crm.newest_first') }}
     </option>
     <option value="oldest" @selected($filters['sort'] === 'oldest')>
      {{ __('crm.oldest_first') }}
     </option>
     <option value="name" @selected($filters['sort'] === 'name')>
      {{ __('crm.by_name') }}
     </option>
     <option value="followup" @selected($filters['sort'] === 'followup')>
      {{ __('crm.by_followup') }}
     </option>
    </select>
   </div>

   <div class="filter-buttons">
    <button class="btn" type="submit">
     {{ __('crm.apply') }}
    </button>

    <a class="btn secondary" href="{{ route('v2.leads') }}">
     {{ __('crm.reset') }}
    </a>
   </div>
  </form>
 </section>

 <section class="section results">
  @if (session('success'))
   <div
    class="flash-success"
    role="status"
   >
    {{ session('success') }}
   </div>
  @endif

  <header class="results-head">
   <div>
    <h2>{{ __('crm.lead_list') }}</h2>
    <p>
     {{ __('crm.leads_per_page') }}
    </p>
   </div>

   <span class="result-count">
    {{ number_format($leads->total()) }} {{ __('نتيجة') }}
   </span>
  </header>

  @if ($leads->isEmpty())
   <div class="empty-state">
    <span class="empty-icon">♙</span>

    <h3>
     {{ $activeQuery === [] ? __('لا يوجد عملاء حتى الآن') : __('لا توجد نتائج مطابقة') }}
    </h3>

    <p>
     @if ($activeQuery === [])
      {{ __('لم تتم إضافة أي عميل إلى قاعدة CRM الجديدة حتى الآن.') }}
      {{ __('استخدم زر إضافة عميل للبدء.') }}
     @else
      {{ __('جرّب تغيير كلمات البحث أو إزالة بعض الفلاتر الحالية.') }}
     @endif
    </p>

    @if ($activeQuery === [])
     @can('leads.create')
      <a class="btn" href="{{ route('v2.leads.create') }}">
       {{ __('crm.add_first_lead') }}
      </a>
     @endcan
    @else
     <a class="btn secondary" href="{{ route('v2.leads') }}">
      {{ __('crm.clear_all_filters') }}
     </a>
    @endif
   </div>
  @else
   @can('leads.export')
   @if (
    $errors->has('lead_ids')
    || $errors->has('lead_ids.*')
   )
    <div
     class="bulk-export-error"
     role="alert"
    >
     {{
      $errors->first('lead_ids')
      ?: $errors->first('lead_ids.*')
     }}
    </div>
   @endif

   <form
    class="bulk-actions-bar"
    id="bulkActionsBar"
    method="POST"
    action="{{ route('v2.leads.export-selected') }}"
    hidden
   >
    @csrf

    <div class="bulk-actions-summary">
     <span class="bulk-actions-icon" aria-hidden="true">
      ✓
     </span>

     <span>
      {{ __('crm.selected') }}
      <strong id="selectedLeadsCount">0</strong>
      {{ __('crm.lead_unit') }}
     </span>
    </div>

    <div class="bulk-actions-buttons">
     <button
      class="bulk-clear-button"
      id="bulkClearSelection"
      type="button"
     >
      {{ __('crm.deselect') }}
     </button>

     <button
      class="bulk-export-button"
      id="bulkExportButton"
      type="submit"
      disabled
     >
      {{ __('crm.export_excel') }}
     </button>
    </div>
   </form>
   @endcan

   <div class="table-wrap">
    <table>
     <thead>
      <tr>
       @can('leads.export')
       <th class="select-column">
        <input
         class="lead-select-all"
         id="selectAllLeads"
         type="checkbox"
         aria-label="{{ __('crm.select_visible_leads') }}"
         title="{{ __('crm.select_all_current_page') }}"
        >
       </th>
       @endcan

       <th>{{ __('crm.client') }}</th>
       <th>{{ __('crm.contact_data') }}</th>
       <th>{{ __('crm.company_source') }}</th>
       <th>{{ __('crm.current_status') }}</th>
       <th>{{ __('crm.responsible_employee') }}</th>
       <th>{{ __('crm.next_followup') }}</th>
       <th>{{ __('crm.created_date') }}</th>
       <th>{{ __('crm.actions') }}</th>
      </tr>
     </thead>

     <tbody>
      @foreach ($leads as $lead)
       @php
        $leadStatusColor = preg_match(
         '/^#[0-9a-fA-F]{6}$/',
         (string) $lead->status?->color
        )
         ? $lead->status->color
         : '#64748b';
       
         $leadPhoneRaw = trim(
          (string) $lead->phone
         );

         $leadPhoneDigits = preg_replace(
          '/\D+/',
          '',
          $leadPhoneRaw
         ) ?? '';

         $callPhone = preg_match(
          '/^[0-9]{2,20}$/',
          $leadPhoneDigits
         ) === 1
          ? $leadPhoneDigits
          : null;

         $whatsappPhone = null;

         if (
          str_starts_with(
           $leadPhoneDigits,
           '0020'
          )
         ) {
          $whatsappPhone = substr(
           $leadPhoneDigits,
           2
          );
         } elseif (
          preg_match(
           '/^01[0125][0-9]{8}$/',
           $leadPhoneDigits
          ) === 1
         ) {
          $whatsappPhone =
           '20'.substr($leadPhoneDigits, 1);
         } elseif (
          preg_match(
           '/^20[0-9]{10}$/',
           $leadPhoneDigits
          ) === 1
         ) {
          $whatsappPhone =
           $leadPhoneDigits;
         } elseif (
          preg_match(
           '/^[1-9][0-9]{7,14}$/',
           $leadPhoneDigits
          ) === 1
         ) {
          $whatsappPhone =
           $leadPhoneDigits;
         }
        @endphp

       <tr class="lead-row">
        @can('leads.export')
        <td class="select-column">
         <input
          class="lead-select-checkbox"
          type="checkbox"
          name="lead_ids[]"
          value="{{ $lead->id }}"
          form="bulkActionsBar"
          aria-label="{{ __('تحديد العميل') }} {{ $lead->name }}"
          autocomplete="off"
         >
        </td>
        @endcan

        <td>
         <a
          class="customer customer-link"
          href="{{ route(
           'v2.leads.show',
           array_merge(
            request()->query(),
            ['lead' => $lead->id]
           )
          ) }}"
          title="{{ __('عرض بيانات العميل') }} {{ $lead->name }}"
         >
          <span class="customer-avatar">
           {{ mb_substr((string) $lead->name, 0, 1) }}
          </span>

          <span>
           <strong>{{ $lead->name }}</strong>
           <small>
            {{ $lead->email ?: __('crm.no_email') }}
           </small>
          </span>
         </a>
        </td>

        <td>
         <div class="contact">
          @if ($lead->phone)
           <a href="tel:{{ $lead->phone }}">
            {{ $lead->phone }}
           </a>
          @else
           <span class="muted">{{ __('crm.no_phone') }}</span>
          @endif

          @if ($lead->email)
           <a href="mailto:{{ $lead->email }}">
            {{ $lead->email }}
           </a>
          @endif
         </div>
        </td>

        <td>
         <strong>
          {{ $lead->company_name ?: __('crm.no_company') }}
         </strong>
         <span class="stage-name">
          {{ __('المصدر:') }} {{ $lead->source ? __($lead->source) : __('غير محدد') }}
         </span>
        </td>

        <td>
         <span
          class="status-badge"
          style="--status-color:{{ $leadStatusColor }}"
         >
          <i class="status-dot"></i>
          {{ $lead->status?->name_ar ? __($lead->status->name_ar) : __('crm.no_status') }}
         </span>

         <span class="stage-name">
          {{ $lead->status?->stage?->name_ar ? __($lead->status->stage->name_ar) : __('بدون مرحلة') }}
         </span>
        </td>

        <td>
         {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
        </td>

        <td>
         @if ($lead->next_follow_up_at)
          <span class="follow-up">
           {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
          </span>
         @else
          <span class="muted">{{ __('crm.no_date') }}</span>
         @endif
        </td>

        <td>
         {{ $lead->created_at?->format('d/m/Y') ?? '—' }}
        </td>
       <td class="lead-actions-cell">
         <div class="lead-actions">
          @if ($callPhone)
           <a
            class="lead-action call-action js-call-followup"
            href="{{ route(
             'v2.leads.followups.index',
             [
              'lead' => $lead,
              'channel' => 'call',
             ]
            ) }}"
            target="_blank"
            rel="noopener noreferrer"
            data-call-href="callto:{{ $callPhone }}"
            title="{{ __('crm.open_microsip_followup') }}"
           >
            {{ __('crm.call_action') }}
           </a>
          @else
           <span
            class="lead-action call-action is-disabled"
            aria-disabled="true"
            title="{{ __('crm.invalid_call_number') }}"
           >
            {{ __('crm.call_action') }}
           </span>
          @endif

          <a
           class="lead-action followup-action"
           href="{{ route(
            'v2.leads.followups.index',
            $lead
           ) }}"
           title="{{ __('crm.new_followup_for_lead') }}"
          >
           {{ __('crm.log_followup') }}
          </a>

          @if ($whatsappPhone)
           <a
            class="lead-action whatsapp-action"
            href="https://wa.me/{{ $whatsappPhone }}"
            target="_blank"
            rel="noopener noreferrer"
            title="{{ __('crm.open_whatsapp') }}"
           >
            {{ __('crm.whatsapp') }}
           </a>
          @else
           <span
            class="lead-action whatsapp-action is-disabled"
            aria-disabled="true"
            title="{{ __('crm.invalid_whatsapp_number') }}"
           >
            {{ __('crm.whatsapp') }}
           </span>
          @endif

          @php
           $listQuotationPath = trim(
            (string) $lead->quotation_file_path
           );

           $hasListQuotationFile = false;

           $listQuotationPathIsSafe = (
            $listQuotationPath !== ''
            && str_starts_with(
             $listQuotationPath,
             'crm-v2/quotation-files/'
            )
            && !str_contains(
             $listQuotationPath,
             '..'
            )
            && !str_starts_with(
             $listQuotationPath,
             '/'
            )
           );

           if ($listQuotationPathIsSafe) {
            try {
             $listQuotationDisk =
              \Illuminate\Support\Facades\Storage::disk(
               'local'
              );

             if (
              $listQuotationDisk->exists(
               $listQuotationPath
              )
             ) {
              $listQuotationDirectory = realpath(
               $listQuotationDisk->path(
                'crm-v2/quotation-files'
               )
              );

              $listQuotationAbsolutePath = realpath(
               $listQuotationDisk->path(
                $listQuotationPath
               )
              );

              $hasListQuotationFile = (
               $listQuotationDirectory !== false
               && $listQuotationAbsolutePath !== false
               && str_starts_with(
                $listQuotationAbsolutePath,
                $listQuotationDirectory
                 .DIRECTORY_SEPARATOR
               )
              );
             }
            } catch (\Throwable) {
             $hasListQuotationFile = false;
            }
           }
          @endphp

          @if ($hasListQuotationFile && auth()->user()->can('quotations.view'))
           <a
            class="lead-action quotation-action"
            href="{{ route(
             'v2.leads.quotation.preview',
             $lead
            ) }}"
            target="_blank"
            rel="noopener noreferrer"
            title="{{ __('crm.preview_lead_quotation') }}"
           >
            👁 {{ __('معاينة عرض السعر') }}
           </a>
          @endif

          @canany(['leads.update', 'leads.delete'])
          <details class="lead-more">
           <summary title="{{ __('crm.more_actions') }}">
            ⋮
           </summary>

           <div class="lead-menu">
            @can('leads.update')
            <a
             href="{{ route('v2.leads.edit', $lead) }}"
            >
             {{ __('crm.edit_short') }}
            </a>
            @endcan

            @can('leads.delete')
            <form
             class="js-delete-lead-form"
             method="POST"
             action="{{ route('v2.leads.destroy', $lead) }}"
             data-lead-name="{{ $lead->name }}"
            >
             @csrf
             @method('DELETE')

             <button
              class="delete-action"
              type="submit"
             >
              {{ __('crm.delete_short') }}
             </button>
            </form>
            @endcan
           </div>
          </details>
          @endcanany
         </div>
        </td>
       </tr>
      @endforeach
     </tbody>
    </table>
   </div>

   @if ($leads->hasPages())
    <nav class="pagination" aria-label="{{ __('crm.lead_pages') }}">
     @if ($leads->onFirstPage())
      <span class="disabled">{{ __('crm.previous') }}</span>
     @else
      <a href="{{ $leads->previousPageUrl() }}">{{ __('crm.previous') }}</a>
     @endif

     <span class="page-info">
      {{ __('صفحة') }} {{ $leads->currentPage() }}
      {{ __('من') }} {{ $leads->lastPage() }}
     </span>

     @if ($leads->hasMorePages())
      <a href="{{ $leads->nextPageUrl() }}">{{ __('crm.next') }}</a>
     @else
      <span class="disabled">{{ __('crm.next') }}</span>
     @endif
    </nav>
   @endif
  @endif
 </section>

</div>
 </main>
</div>

<script>
(() => {
 const body = document.body;
 const menuButton = document.getElementById('crmMenuButton');
 const overlay = document.getElementById('crmSidebarOverlay');

 const setSidebarOpen = (open) => {
  body.classList.toggle('crm-side-open', open);

  if (menuButton) {
   menuButton.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );
  }
 };

 document.querySelectorAll('.crm-toggle').forEach((button) => {
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

 if (menuButton) {
  menuButton.addEventListener('click', () => {
   setSidebarOpen(
    !body.classList.contains('crm-side-open')
   );
  });
 }

 if (overlay) {
  overlay.addEventListener('click', () => {
   setSidebarOpen(false);
  });
 }

 document
  .querySelectorAll('.crm-side a')
  .forEach((link) => {
   link.addEventListener('click', () => {
    if (window.innerWidth <= 900) {
     setSidebarOpen(false);
    }
   });
  });

 document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
   setSidebarOpen(false);
  }
 });

  const closeLeadMenus = (
   exceptDetails = null
  ) => {
   document
    .querySelectorAll('.lead-more[open]')
    .forEach((details) => {
     if (details !== exceptDetails) {
      details.open = false;
     }
    });
  };

  const positionLeadMenu = (details) => {
   const summary = details.querySelector('summary');
   const menu = details.querySelector('.lead-menu');

   if (!summary || !menu) {
    return;
   }

   const rect = summary.getBoundingClientRect();
   const menuWidth = 165;
   const menuHeight = 96;
   const padding = 8;

   let left = rect.right - menuWidth;

   left = Math.max(
    padding,
    Math.min(
     left,
     window.innerWidth
      - menuWidth
      - padding
    )
   );

   let top = rect.bottom + 7;

   if (
    top + menuHeight
    > window.innerHeight - padding
   ) {
    top = Math.max(
     padding,
     rect.top - menuHeight - 7
    );
   }

   menu.style.top =
    Math.round(top) + 'px';

   menu.style.left =
    Math.round(left) + 'px';
  };

  document
   .querySelectorAll('.lead-more')
   .forEach((details) => {
    details.addEventListener(
     'toggle',
     () => {
      if (!details.open) {
       return;
      }

      closeLeadMenus(details);
      positionLeadMenu(details);
     }
    );
   });

  document
   .querySelectorAll('.js-followup-button')
   .forEach((button) => {
    button.addEventListener(
     'click',
     () => {
      const leadName =
       button.dataset.leadName || '{{ __('العميل') }}';

      window.alert(
       '{{ __('سيتم تفعيل تسجيل المتابعة للعميل ') }}'
       + leadName
       + '{{ __(' بعد تجهيز صفحة المتابعات.') }}'
      );
     }
    );
   });

  document
   .querySelectorAll('.js-delete-lead-form')
   .forEach((form) => {
    form.addEventListener(
     'submit',
     (event) => {
      const leadName =
       form.dataset.leadName || '{{ __('هذا العميل') }}';

      const confirmed = window.confirm(
       '{{ __('هل أنت متأكد من حذف ') }}'
       + leadName
       + '?\n\n'
       + '{{ __('الحذف نهائي ولا يمكن التراجع عنه.') }}'
      );

      if (!confirmed) {
       event.preventDefault();
      }
     }
    );
   });

  document.addEventListener(
   'click',
   (event) => {
    document
     .querySelectorAll('.lead-more[open]')
     .forEach((details) => {
      if (!details.contains(event.target)) {
       details.open = false;
      }
     });
   }
  );

  window.addEventListener(
   'resize',
   () => {
    closeLeadMenus();
   }
  );

  window.addEventListener(
   'scroll',
   () => {
    closeLeadMenus();
   },
   true
  );

  document.addEventListener(
   'keydown',
   (event) => {
    if (event.key === 'Escape') {
     closeLeadMenus();
    }
   }
  );

  const bulkActionsBar =
   document.getElementById('bulkActionsBar');

  const selectAllLeads =
   document.getElementById('selectAllLeads');

  const selectedLeadsCount =
   document.getElementById('selectedLeadsCount');

  const bulkExportButton =
   document.getElementById('bulkExportButton');

  const bulkClearSelection =
   document.getElementById('bulkClearSelection');

  const leadSelectionCheckboxes = Array.from(
   document.querySelectorAll(
    '.lead-select-checkbox'
   )
  );

  const updateBulkSelection = () => {
   const selectedCheckboxes =
    leadSelectionCheckboxes.filter(
     (checkbox) => checkbox.checked
    );

   const selectedCount =
    selectedCheckboxes.length;

   if (selectedLeadsCount) {
    selectedLeadsCount.textContent =
     String(selectedCount);
   }

   if (bulkActionsBar) {
    bulkActionsBar.hidden =
     selectedCount === 0;
   }

   if (bulkExportButton) {
    bulkExportButton.disabled =
     selectedCount === 0;
   }

   if (selectAllLeads) {
    const totalCount =
     leadSelectionCheckboxes.length;

    selectAllLeads.checked =
     totalCount > 0
     && selectedCount === totalCount;

    selectAllLeads.indeterminate =
     selectedCount > 0
     && selectedCount < totalCount;
   }

   leadSelectionCheckboxes.forEach(
    (checkbox) => {
     const row = checkbox.closest(
      '.lead-row'
     );

     row?.classList.toggle(
      'is-selected',
      checkbox.checked
     );
    }
   );
  };

  selectAllLeads?.addEventListener(
   'change',
   () => {
    leadSelectionCheckboxes.forEach(
     (checkbox) => {
      checkbox.checked =
       selectAllLeads.checked;
     }
    );

    updateBulkSelection();
   }
  );

  leadSelectionCheckboxes.forEach(
   (checkbox) => {
    checkbox.addEventListener(
     'change',
     updateBulkSelection
    );
   }
  );

  bulkClearSelection?.addEventListener(
   'click',
   () => {
    leadSelectionCheckboxes.forEach(
     (checkbox) => {
      checkbox.checked = false;
     }
    );

    updateBulkSelection();
   }
  );

  bulkActionsBar?.addEventListener(
   'submit',
   (event) => {
    const hasSelection =
     leadSelectionCheckboxes.some(
      (checkbox) => checkbox.checked
     );

    if (!hasSelection) {
     event.preventDefault();

     window.alert(
      '{{ __('اختر عميلًا واحدًا على الأقل قبل التصدير.') }}'
     );

     updateBulkSelection();
    }
   }
  );

  updateBulkSelection();

})();
</script>

 <script>
 (() => {
  document
   .querySelectorAll('.js-call-followup')
   .forEach((link) => {
    link.addEventListener(
     'click',
     () => {
      const callHref =
       link.dataset.callHref;

      if (!callHref) {
       return;
      }

      window.setTimeout(
       () => {
        window.location.href =
         callHref;
       },
       120
      );
     }
    );
   });
 })();
 </script>
</body>
</html>

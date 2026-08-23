<!doctype html>
 <html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SokratCRM — {{ __('لوحة التحكم') }}</title>
<style>
:root{--red:#dc2637;--dark:#182033;--text:#4b5568;--muted:#8b94a5;--line:#e7e9ef;--bg:#f5f6f9;--card:#fff;--shadow:0 12px 35px #1720330d}*{box-sizing:border-box}body{margin:0;min-width:320px;background:radial-gradient(circle at 8% 0,#dc26370c,transparent 25rem),var(--bg);color:var(--dark);font-family:Tahoma,Arial,sans-serif}button,input,select{font:inherit}a{color:inherit}.app{display:grid;grid-template-columns:minmax(0,1fr) 270px;min-height:100vh}.side{grid-column:2;position:sticky;top:0;height:100vh;overflow:auto;padding:24px 17px;background:#fff;border-inline-start:1px solid var(--line);z-index:20}.brand{display:flex;align-items:center;gap:11px;padding:4px 8px 20px;margin-bottom:17px;border-bottom:1px solid var(--line);text-decoration:none}.logo{width:58px;height:58px;display:block;flex:0 0 58px;object-fit:contain}.brand strong{display:block;color:var(--red);font:900 22px Arial}.brand small{display:block;margin-top:5px;color:var(--muted);font-size:10px}.caption{margin:0 12px 9px;color:#a0a7b4;font-size:10px;font-weight:bold}.nav{display:grid;gap:6px}.link,.toggle{width:100%;min-height:49px;display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid transparent;border-radius:13px;background:transparent;color:#566175;text-decoration:none;text-align:right;cursor:pointer;transition:.2s}.link:hover,.toggle:hover{color:var(--red);background:#fff5f6;transform:translateX(-2px)}.link.active{color:#fff;background:linear-gradient(135deg,#e83243,#c91d2e);box-shadow:0 11px 25px #dc263737}.ico{width:32px;height:32px;flex:0 0 32px;display:grid;place-items:center;border-radius:10px;background:#f0f2f6;font-size:17px}.active .ico{background:#ffffff2b}.label{flex:1;font-size:13px;font-weight:800}.count{min-width:24px;height:24px;display:grid;place-items:center;padding:0 6px;border-radius:99px;background:#eef0f4;color:#7e8796;font:800 10px Arial}.active .count{color:#fff;background:#ffffff2b}.arrow{font-size:11px;color:#a2a9b5;transition:.2s}.toggle[aria-expanded=true] .arrow{transform:rotate(180deg)}.sub{display:grid;grid-template-rows:0fr;transition:.22s}.sub.open{grid-template-rows:1fr}.sub>div{min-height:0;overflow:hidden}.sub nav{display:grid;gap:2px;margin:3px 28px 7px 0;padding-inline-end:14px;border-inline-end:1px solid var(--line)}.sub a{padding:8px 10px;border-radius:8px;color:#788294;text-decoration:none;font-size:11px;font-weight:bold}.sub a:hover{color:var(--red);background:#fff2f4}.mode{margin-top:20px;padding:12px;border:1px solid #f2d9dd;border-radius:13px;background:#fff8f9;color:#9a4b55;font-size:10px;line-height:1.7}.dot{display:inline-block;width:8px;height:8px;margin-inline-start:6px;border-radius:50%;background:#eca51d;box-shadow:0 0 0 4px #eca51d1f}.main{grid-column:1;min-width:0;padding:20px clamp(15px,2.5vw,34px) 38px}.top{min-height:70px;display:flex;align-items:center;gap:13px;padding:12px 15px;margin-bottom:18px;border:1px solid var(--line);border-radius:17px;background:#ffffffed;box-shadow:var(--shadow)}.menu{display:none;width:40px;height:40px;border:1px solid var(--line);border-radius:11px;background:#fff;font-size:20px}.title{flex:1}.title h1{margin:0;font-size:23px}.title p{margin:5px 0 0;color:var(--muted);font-size:10px}.offline{padding:9px 11px;border:1px solid #efd59f;border-radius:10px;background:#fff9ed;color:#9b6810;font-size:10px;font-weight:bold;white-space:nowrap}.bell{position:relative;width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--line);border-radius:11px;background:#fff}.bell b{position:absolute;top:-5px;left:-5px;min-width:18px;height:18px;display:grid;place-items:center;border:2px solid #fff;border-radius:99px;background:var(--red);color:#fff;font:800 8px Arial}.user{display:flex;align-items:center;gap:8px}.avatar{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:var(--dark);color:#fff;font-weight:900}.user strong{display:block;max-width:105px;overflow:hidden;text-overflow:ellipsis;font-size:11px}.user small{color:var(--muted);font-size:8px}.logout{width:32px;height:32px;border:0;border-radius:9px;background:#fff0f2;color:var(--red);cursor:pointer}.hero{position:relative;overflow:hidden;min-height:180px;padding:30px;border-radius:22px;background:linear-gradient(120deg,#171f31,#30394c);color:#fff;box-shadow:0 20px 48px #1720332e}.hero:after{display:none}.hero small{color:#f4aab2;font-weight:bold}.hero h2{max-width:620px;margin:11px 0 8px;font-size:clamp(24px,3vw,35px)}.hero p{max-width:620px;margin:0;color:#c5cad4;font-size:11px;line-height:1.8}.actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center;padding:8px 15px;border:0;border-radius:10px;background:var(--red);color:#fff;text-decoration:none;font-size:10px;font-weight:900;cursor:pointer;box-shadow:0 10px 22px #dc263738}.btn.ghost{border:1px solid #ffffff2b;background:#ffffff12;box-shadow:none}.filters{display:flex;align-items:end;flex-wrap:wrap;gap:10px;padding:14px;margin-top:16px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:var(--shadow)}.field{min-width:145px;flex:1}.field label{display:block;margin:0 3px 6px;color:#7e8797;font-size:9px;font-weight:bold}.field select,.field input{width:100%;height:40px;padding:0 10px;border:1px solid #dfe3ea;border-radius:10px;background:#fafbfc;color:#556074;font-size:10px;outline:none}.filter-actions{display:flex;gap:7px}.filter-actions .btn{height:40px;min-width:78px}.btn.light{border:1px solid var(--line);background:#f6f7f9;color:#697386;box-shadow:none}.heading{display:flex;align-items:end;justify-content:space-between;margin:23px 2px 12px}.heading h2{margin:0;font-size:16px}.heading p{margin:5px 0 0;color:var(--muted);font-size:9px}.heading a{color:var(--red);font-size:9px;font-weight:bold;text-decoration:none}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.stat{--color:#3478f6;min-height:120px;padding:15px;border:1px solid var(--line);border-radius:16px;background:#fff;box-shadow:var(--shadow);transition:.2s}.stat:hover{transform:translateY(-4px);box-shadow:0 18px 42px #17203317}.stat.green{--color:#169a64}.stat.red{--color:#dc2637}.stat.orange{--color:#e59b16}.stat.purple{--color:#7b61df}.stat .sico{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:color-mix(in srgb,var(--color) 10%,white);color:var(--color);font-size:17px}.stat strong{display:inline-block;margin-top:12px;font:900 30px Arial}.stat small{margin-inline-end:4px;color:#9ba3b0;font-size:8px}.stat p{margin:6px 0 0;color:#596477;font-size:10px;font-weight:bold}.grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(270px,.65fr);gap:13px;margin-top:13px}.panel{min-width:0;padding:17px;border:1px solid var(--line);border-radius:17px;background:#fff;box-shadow:var(--shadow)}.panel-head{display:flex;align-items:center;justify-content:space-between;padding-bottom:13px;border-bottom:1px solid #eff1f4}.panel-head h3{margin:0;font-size:12px}.panel-head p{margin:4px 0 0;color:var(--muted);font-size:8px}.badge{padding:6px 8px;border-radius:8px;background:#f2f4f7;color:#818998;font:800 8px Arial}.chart{height:225px;position:relative;display:flex;align-items:end;gap:12px;padding:30px 14px 30px;direction:ltr;background:repeating-linear-gradient(to bottom,transparent 0,transparent 53px,#eef0f4 54px)}.bar{height:3px;flex:1;border-radius:5px;background:var(--red);position:relative}.bar span{position:absolute;top:13px;inset-inline-start:50%;transform:translateX(-50%);color:#8d96a5;font-size:8px}.empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;z-index:2}.empty i{width:45px;height:45px;display:grid;place-items:center;margin-bottom:8px;border-radius:13px;background:#f3f5f7;color:#9ba4b2;font-style:normal;font-size:19px}.empty strong{color:#687385;font-size:10px}.empty p{max-width:270px;margin:6px 0 0;color:#9ba3b1;font-size:8px;line-height:1.7}.dist{display:grid;gap:16px;padding-top:18px}.dist div div{display:flex;justify-content:space-between;color:#606b7d;font-size:9px;font-weight:bold}.dist b{color:#929aa7;font:800 9px Arial}.progress{height:6px!important;display:block!important;margin-top:7px;border-radius:99px;background:#f0f2f5}.progress span{width:0}.bottom{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:13px;margin-top:13px}.table{width:100%;border-collapse:collapse}.table th{padding:11px;border-bottom:1px solid var(--line);color:#8c95a4;font-size:8px;text-align:right}.table .empty{position:static;min-height:170px}.quick{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding-top:14px}.quick a{min-height:76px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;border:1px solid var(--line);border-radius:12px;background:#fafbfc;color:#697386;text-decoration:none;font-size:8px;font-weight:bold;transition:.2s}.quick a:hover{color:var(--red);border-color:#f2bdc4;background:#fff4f5;transform:translateY(-2px)}.quick i{font-style:normal;font-size:18px}.overlay{display:none;position:fixed;inset:0;border:0;background:#11182770;z-index:15}.toast{position:fixed;inset-inline-start:20px;bottom:20px;z-index:50;padding:12px 15px;border:1px solid #dbece3;border-radius:12px;background:#fff;color:#47705a;box-shadow:0 15px 40px #17203326;font-size:9px;font-weight:bold;opacity:0;visibility:hidden;transform:translateY(12px);transition:.2s}.toast.show{opacity:1;visibility:visible;transform:none}@media(max-width:1120px){.stats{grid-template-columns:repeat(2,1fr)}.hero:after{display:none}}@media(max-width:900px){.app{display:block}.side{position:fixed;inset-inline-end:0;width:min(270px,calc(100vw - 45px));transform:translateX(105%);transition:.25s}.side-open{overflow:hidden}.side-open .side{transform:none}.side-open .overlay{display:block}.main{padding:13px}.menu{display:block}.offline{display:none}.grid,.bottom{grid-template-columns:1fr}}@media(max-width:600px){.title p,.user div{display:none}.hero{padding:24px 20px}.stats{gap:8px}.stat{padding:13px}.field{flex-basis:100%}.filter-actions{width:100%}.filter-actions .btn{flex:1}.bell{display:none}}@media(max-width:390px){.stats{grid-template-columns:1fr}}@media(prefers-reduced-motion:reduce){*{transition:none!important}}

/* Dashboard follow-ups and meetings popup controls */
.dashboard-launchers{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-top:13px}
.dashboard-launch{--launch-color:#dc2637;width:100%;min-height:92px;display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--line);border-radius:17px;background:#fff;color:var(--dark);text-align:right;cursor:pointer;box-shadow:var(--shadow);transition:.2s}
.dashboard-launch:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--launch-color) 35%,var(--line));box-shadow:0 18px 42px #17203317}
.dashboard-launch.meetings{--launch-color:#7b61df}


.dashboard-launch-icon{width:50px;height:50px;flex:0 0 50px;display:grid;place-items:center;border-radius:14px;background:color-mix(in srgb,var(--launch-color) 11%,white);color:var(--launch-color);font-size:23px}
.dashboard-launch-text{flex:1}
.dashboard-launch-text strong{display:block;font-size:16px}
.dashboard-launch-text small{display:block;margin-top:6px;color:var(--muted);font-size:11px;line-height:1.6}
.dashboard-launch-arrow{color:var(--launch-color);font-size:20px;font-weight:900}
.crm-modal[hidden]{display:none}
.crm-modal{position:fixed;inset:0;z-index:100;display:grid;place-items:center;padding:20px}
.crm-modal-backdrop{position:absolute;inset:0;border:0;background:#11182799;backdrop-filter:blur(3px);cursor:pointer}
.crm-dialog{position:relative;width:min(520px,100%);max-height:calc(100vh - 40px);overflow:auto;padding:20px;border:1px solid #ffffff33;border-radius:22px;background:#fff;box-shadow:0 30px 90px #1118274d}
.crm-dialog-head{display:flex;align-items:center;gap:12px;padding-bottom:15px;border-bottom:1px solid var(--line)}
.crm-dialog-title{flex:1}
.crm-dialog-title h3{margin:0;font-size:19px}
.crm-dialog-title p{margin:6px 0 0;color:var(--muted);font-size:11px}
.crm-modal-close{width:38px;height:38px;display:grid;place-items:center;border:1px solid var(--line);border-radius:11px;background:#f8f9fb;color:#6d7686;font-size:20px;cursor:pointer}
.crm-modal-close:hover{color:var(--red);background:#fff1f3}
.crm-choice-list{display:grid;gap:10px;padding-top:16px}
.crm-choice{display:flex;align-items:center;gap:12px;min-height:72px;padding:13px;border:1px solid var(--line);border-radius:14px;background:#fafbfc;color:var(--dark);text-decoration:none;transition:.2s}
.crm-choice:hover{border-color:#efb8bf;background:#fff5f6;transform:translateX(-3px)}
.crm-choice-icon{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;border-radius:12px;background:#fff0f2;color:var(--red);font-size:18px}
.crm-choice.meeting-choice .crm-choice-icon{background:#f2efff;color:#7b61df}
.crm-choice-text{flex:1}
.crm-choice-text strong{display:block;font-size:14px}
.crm-choice-text small{display:block;margin-top:5px;color:var(--muted);font-size:10px}
.crm-choice-count{min-width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:#eef0f4;color:#687385;font:900 14px Arial}
body.crm-modal-open{overflow:hidden}
@media(max-width:600px){.dashboard-launchers{grid-template-columns:1fr}.crm-modal{padding:12px}.crm-dialog{padding:16px;border-radius:18px}.dashboard-launch{min-height:82px}}


/* Dashboard sales pipeline */
.pipeline-head{align-items:flex-start;gap:12px}
.pipeline-summary{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:7px}
.pipeline-success{background:#ecf8f2;color:#168158}

.sales-pipeline{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;padding-top:16px}
.pipeline-stage{--stage-color:#3478f6;position:relative;min-width:0;min-height:155px;padding:14px;border:1px solid var(--line);border-top:4px solid var(--stage-color);border-radius:15px;background:linear-gradient(180deg,color-mix(in srgb,var(--stage-color) 7%,white),#fff 42%);transition:.2s}
.pipeline-stage:hover{transform:translateY(-3px);box-shadow:0 14px 32px #17203312}
.pipeline-stage.interest{--stage-color:#169a64}
.pipeline-stage.negotiation{--stage-color:#e59b16}
.pipeline-stage.closing{--stage-color:#7b61df}
.pipeline-stage-top{display:flex;align-items:center;gap:9px}
.pipeline-step{width:31px;height:31px;flex:0 0 31px;display:grid;place-items:center;border-radius:10px;background:var(--stage-color);color:#fff;font:900 13px Arial}
.pipeline-stage-name{min-width:0;flex:1}
.pipeline-stage-name strong{display:block;font-size:13px}
.pipeline-stage-name small{display:block;margin-top:4px;color:var(--muted);font-size:9px;line-height:1.5}
.pipeline-stage-total{color:var(--stage-color);font:900 24px Arial}
.pipeline-statuses{display:grid;gap:6px;margin-top:14px}
.pipeline-statuses span{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 9px;border:1px solid #eceef2;border-radius:9px;background:#fff;color:#5d687a;font-size:10px;font-weight:bold}
.pipeline-statuses b{color:var(--stage-color);font:900 11px Arial}




.pipeline-kanban-btn{display:flex;align-items:center;gap:13px;min-height:76px;margin-top:14px;padding:14px 17px;border:1px solid #30394c;border-radius:15px;background:linear-gradient(135deg,#182033,#30394c);color:#fff;text-decoration:none;box-shadow:0 15px 32px #18203324;transition:.2s}
.pipeline-kanban-btn:hover{transform:translateY(-3px);border-color:#dc2637;background:linear-gradient(135deg,#dc2637,#bd1f2f);box-shadow:0 18px 38px #dc26372b}
.pipeline-kanban-icon{width:46px;height:46px;flex:0 0 46px;display:grid;place-items:center;border-radius:13px;background:#ffffff17;color:#fff;font-size:22px}
.pipeline-kanban-copy{min-width:0;flex:1}
.pipeline-kanban-copy strong{display:block;font-size:15px}
.pipeline-kanban-copy small{display:block;margin-top:6px;color:#cbd0da;font-size:10px;line-height:1.6}
.pipeline-kanban-arrow{font-size:21px;font-weight:900}
@media(max-width:600px){.pipeline-kanban-btn{min-height:70px;padding:12px}.pipeline-kanban-icon{width:42px;height:42px;flex-basis:42px}}
@media(max-width:1250px){.sales-pipeline{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.sales-pipeline{grid-template-columns:1fr}.pipeline-summary{width:100%;justify-content:flex-start}.pipeline-head{flex-wrap:wrap}}

/* V2 RTL placement and readable typography fix */
body{font-size:15px}
.app{display:flex;flex-direction:row;align-items:flex-start;min-height:100vh}
.side{grid-column:auto;order:0;flex:0 0 288px;width:288px;}
.main{grid-column:auto;order:1;flex:1 1 auto;width:calc(100% - 288px);min-height:100vh;}
.brand small,.caption{font-size:12px}.label{font-size:15px}.sub a{font-size:13px}.mode{font-size:12px}
.title h1{font-size:28px}.title p{font-size:13px}.offline{font-size:12px}
.user strong{font-size:13px}.user small{font-size:10px}.hero small{font-size:13px}.hero p{font-size:14px}
.btn{font-size:13px}.field label{font-size:12px}.field select,.field input{font-size:13px}
.heading h2{font-size:20px}.heading p,.heading a{font-size:12px}
.stat p{font-size:13px}.stat small{font-size:11px}.stat strong{font-size:34px}
.panel-head h3{font-size:16px}.panel-head p{font-size:11px}.badge{font-size:11px}
.empty strong{font-size:13px}.empty p{font-size:11px}.dist div div{font-size:12px}.dist b{font-size:11px}
.table th{font-size:11px}.quick a{font-size:12px}.toast{font-size:12px}
@media(max-width:900px){.app{display:block}.side{width:min(288px,calc(100vw - 45px))}.main{width:100%;min-height:100vh}}

/* CRM LIVE DASHBOARD DATA STYLES */
.table td{
 padding:12px 11px;
 border-bottom:1px solid #f0f2f5;
 color:#566175;
 font-size:12px;
 line-height:1.55;
 vertical-align:middle
}
.table tbody tr:last-child td{
 border-bottom:0
}
.progress>span{
 min-width:0;
 transition:.2s
}
/* CRM LIVE DASHBOARD END */


/* CRM DASHBOARD STATUS CARD LINK DESIGN START */

.dashboard-status-link{
 color:inherit;
 text-decoration:none;
 cursor:pointer
}

.dashboard-status-link:hover{
 text-decoration:none
}

.dashboard-status-link:focus-visible{
 outline:3px solid
  color-mix(
   in srgb,
   var(--color) 38%,
   transparent
  );
 outline-offset:3px
}

.dashboard-status-link .sico{
 transition:.18s
}

.dashboard-status-link:hover .sico{
 transform:scale(1.06)
}

/* CRM DASHBOARD STATUS CARD LINK DESIGN END */

</style>
</head>
<body>
@include('partials.page-loader')
<div class="app">
@include('partials.crm-sidebar')
<button class="overlay" id="overlay" type="button" aria-label="{{ __('إغلاق القائمة') }}"></button>
 <main class="main">
  <header class="top">
   <button class="menu" id="menu" type="button">☰</button>
   <div class="title"><h1>{{ __('crm.dashboard') }}</h1><p id="date">{{ __('نظرة عامة على أداء فريق المبيعات') }}</p></div>
  @include('partials.profile-dropdown')
  </header>
  <section class="hero relative overflow-hidden">
   <div class="absolute opacity-10 z-0 pointer-events-none inset-y-0 end-0 flex items-center justify-center pe-12">
    <div class="w-28 h-28 rounded-full border-[17px] border-white/10 flex items-center justify-center font-black text-4xl text-white select-none">CRM</div>
   </div>
   <div class="relative z-10">
    <small>{{ __('crm.workspace_welcome_small') }}</small>
    <h2>{{ __('crm.workspace_welcome_title') }}</h2>
    <div class="actions">
     @can('leads.create')
      <a class="btn" href="{{ route('v2.leads.create') }}">＋ {{ __('crm.add_lead') }}</a>
     @endcan
     @can('tasks.view')
      <a class="btn ghost" href="{{ route('v2.tasks.daily') }}">{{ __('crm.daily_tasks') }}</a>
     @endcan
    </div>
   </div>
  </section>
  <!-- CRM LIVE DASHBOARD START -->
 <form
  class="filters"
  id="filters"
  method="GET"
  action="{{ route('dashboard') }}"
 >
  <div class="field">
   <label>{{ __('الموظف') }}</label>
   <select name="employee">
    <option value="">
     {{ __('جميع الموظفين') }}
    </option>

    @foreach ($employees as $employee)
     <option
      value="{{ $employee }}"
      @selected(
       $filters['employee']
       === $employee
      )
     >
      {{ $employee }}
     </option>
    @endforeach
   </select>
  </div>

  <div class="field">
   <label>{{ __('الفترة') }}</label>
   <select name="period">
    <option
     value="all"
     @selected(
      $filters['period']
      === 'all'
     )
    >
     {{ __('كل الفترات') }}
    </option>

    <option
     value="today"
     @selected(
      $filters['period']
      === 'today'
     )
    >
     {{ __('اليوم') }}
    </option>

    <option
     value="week"
     @selected(
      $filters['period']
      === 'week'
     )
    >
     {{ __('هذا الأسبوع') }}
    </option>

    <option
     value="month"
     @selected(
      $filters['period']
      === 'month'
     )
    >
     {{ __('هذا الشهر') }}
    </option>
   </select>
  </div>

  <div class="field">
   <label>{{ __('من تاريخ إنشاء العميل') }}</label>
   <input
    type="date"
    name="from"
    value="{{ $filters['from'] }}"
   >
  </div>

  <div class="field">
   <label>{{ __('إلى تاريخ إنشاء العميل') }}</label>
   <input
    type="date"
    name="to"
    value="{{ $filters['to'] }}"
   >
  </div>

  <div class="filter-actions">
   <button class="btn" type="submit">
    {{ __('تطبيق') }}
   </button>

   <a
    class="btn light"
    href="{{ route('dashboard') }}"
   >
    {{ __('إعادة ضبط') }}
   </a>
  </div>
 </form>

 <div class="heading"><div><h2>{{ __('ملخص الأداء') }}</h2><p>{{ __('البيانات المعروضة حية مباشرة من CRM v2') }}</p></div><a href="{{ route('v2.reports.leads') }}">{{ __('عرض التقارير') }} {{ app()->getLocale() === 'en' ? '→' : '←' }}</a></div>
 
<style>
    /* KPI Grid Layout */
    .kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem; }
    @media (min-width: 768px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(5, 1fr); } }

    /* KPI Card Button Styling */
    .kpi-card {
        background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 1rem;
        padding: 1rem; display: flex; align-items: center; justify-content: space-between;
        text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.2s ease-in-out;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        border-color: #3478F6;
    }
    
    .kpi-info { display: flex; flex-direction: column; }
    .kpi-title { font-size: 0.75rem; color: #64748b; margin-bottom: 0.3rem; font-weight: 700; }
    .kpi-value { font-size: 1.25rem; font-weight: 900; color: #1e293b; line-height: 1; }
    .kpi-icon-box {
        width: 38px; height: 38px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }

    /* Dark Mode Overrides */
    .dark-mode .kpi-card, html.dark .kpi-card { background-color: #0d0d0d; border-color: #1f1f1f; }
    .dark-mode .kpi-title { color: #94a3b8; }
    .dark-mode .kpi-value { color: #f8fafc; }
    .dark-mode .kpi-card:hover { border-color: #00d2ff; box-shadow: 0 4px 15px rgba(0, 210, 255, 0.15); }
    
    /* Default Light Mode for Charts */
     .custom-chart-card {
         background-color: #ffffff;
         border-radius: 0.75rem;
         border: 1px solid #e5e7eb;
         box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
         padding: 1.5rem;
         display: flex; flex-direction: column; width: 100%;
         transition: all 0.3s ease;
         /* Flexbox overflow fix for Chart.js */
         overflow: hidden;
         min-width: 0;
     }
    .custom-chart-header {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
    .custom-chart-title { 
        font-size: 1.25rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 1px;
    }

    /* Dark Mode Overrides for Charts */
    .dark-mode .custom-chart-card,
    html.dark .custom-chart-card {
        background-color: #0d0d0d !important;
        border-color: #1f1f1f !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .custom-chart-header,
    html.dark .custom-chart-header {
        border-bottom-color: #1f1f1f !important;
    }
    .dark-mode .custom-chart-title,
    html.dark .custom-chart-title { 
        color: #ffffff !important;
    }
    .custom-charts-wrapper {
        display: flex; flex-direction: column; gap: 1.5rem; width: 100%; margin-bottom: 2rem;
    }
    @media (min-width: 1024px) {
        .custom-charts-wrapper { flex-direction: row; }
        .chart-line-card { flex: 1; }
        .chart-polar-card { flex: 1; }
    }
</style>

 <div class="kpi-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
     <a href="{{ route('v2.leads') }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('إجمالي العملاء') }}</span><span class="kpi-value">{{ number_format($totalLeads ?? 0) }}</span></div>
         <div class="kpi-icon-box" style="background: rgba(52,120,246,0.1); color: #3478F6;"><i class="bi bi-people-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'new']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('جديد') }}</span><span class="kpi-value">28</span></div>
         <div class="kpi-icon-box" style="background: rgba(52,120,246,0.1); color: #3478F6;"><i class="bi bi-person-fill-add"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'no_answer']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('لم يرد') }}</span><span class="kpi-value">20</span></div>
         <div class="kpi-icon-box" style="background: rgba(245,166,35,0.1); color: #f5a623;"><i class="bi bi-telephone-x-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'interested']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('مهتم') }}</span><span class="kpi-value">16</span></div>
         <div class="kpi-icon-box" style="background: rgba(25,185,133,0.1); color: #19b985;"><i class="bi bi-hand-thumbs-up-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'meeting']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('مقابلة') }}</span><span class="kpi-value">16</span></div>
         <div class="kpi-icon-box" style="background: rgba(139,92,246,0.1); color: #8b5cf6;"><i class="bi bi-calendar-event-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'quotation']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('عرض سعر') }}</span><span class="kpi-value">5</span></div>
         <div class="kpi-icon-box" style="background: rgba(242,184,75,0.1); color: #f2b84b;"><i class="bi bi-file-earmark-text-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'discussion']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('مناقشة') }}</span><span class="kpi-value">6</span></div>
         <div class="kpi-icon-box" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-chat-dots-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'execution']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('تنفيذ') }}</span><span class="kpi-value">5</span></div>
         <div class="kpi-icon-box" style="background: rgba(20,184,166,0.1); color: #14b8a6;"><i class="bi bi-gear-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'not_interested']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('غير مهتم') }}</span><span class="kpi-value">5</span></div>
         <div class="kpi-icon-box" style="background: rgba(239,77,90,0.1); color: #ef4d5a;"><i class="bi bi-hand-thumbs-down-fill"></i></div>
     </a>
     <a href="{{ route('v2.leads', ['status' => 'contract_closed']) }}" class="kpi-card">
         <div class="kpi-info"><span class="kpi-title">{{ __('تقفيل عقد') }}</span><span class="kpi-value">5</span></div>
         <div class="kpi-icon-box" style="background: rgba(25,185,133,0.1); color: #19b985;"><i class="bi bi-check-circle-fill"></i></div>
     </a>
 </div>
 
 <div class="custom-charts-wrapper" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
     <div class="custom-chart-card chart-line-card">
         <div class="custom-chart-header">
             <h3 class="custom-chart-title">{{ __('مؤشر حركة العملاء') }}</h3>
            <p class="custom-chart-sub" style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">{{ __('الأداء بمرور الوقت') }}</p>
         </div>
         <div style="position: relative; width: 100%; height: 320px;">
             <canvas id="performanceChart"></canvas>
         </div>
     </div>
     <div class="custom-chart-card chart-polar-card">
        <div class="custom-chart-header">
             <h3 class="custom-chart-title">{{ __('توزيع حالات العملاء') }}</h3>
            <p class="custom-chart-sub" style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">{{ __('مزيج الحالات للفترة المحددة') }}</p>
        </div>
        <div style="position: relative; width: 100%; display: flex; align-items: center; justify-content: center; flex: 1; min-height: 320px;">
            <canvas id="statusPolarChart"></canvas>
        </div>
    </div>
</div>

 

 <section class="grid">
  <article class="panel sales-pipeline-panel">
   <header class="panel-head pipeline-head">
    <div>
     <h3>{{ __('مسار المبيعات') }}</h3>
     <p>
      {{ __('تقدم العملاء من أول تواصل حتى التنفيذ') }}
     </p>
    </div>

    <div class="pipeline-summary">
     <span class="badge">
      {{ __('إجمالي العملاء:') }}
      {{ number_format($totalLeads) }}
     </span>

     <span class="badge pipeline-success">
      {{ __('إتمام العقود:') }}
      {{ number_format(
       $contractRate,
       1
      ) }}%
     </span>
    </div>
   </header>

   <div class="sales-pipeline">
    @foreach ($stageCards as $stage)
     <section
      class="pipeline-stage {{ $stage['class'] }}"
      style="
       --stage-color:
        {{ $stage['color'] }};
      "
     >
      <div class="pipeline-stage-top">
       <span class="pipeline-step">
        {{ $stage['position'] }}
       </span>

       <div class="pipeline-stage-name">
        <strong>
         {{ __($stage['name']) }}
        </strong>

        <small>
         {{ __($stage['description']) }}
        </small>
       </div>

       <b class="pipeline-stage-total">
        {{ number_format(
         $stage['total']
        ) }}
       </b>
      </div>

      <div class="pipeline-statuses">
       @foreach (
        $stage['statuses']
        as $stageStatus
       )
        <span>
         {{ __($stageStatus['name']) }}

         <b>
          {{ number_format(
           $stageStatus['count']
          ) }}
         </b>
        </span>
       @endforeach
      </div>
     </section>
    @endforeach
   </div>

   <a
    class="pipeline-kanban-btn"
    href="{{ route('v2.leads.kanban') }}"
   >
    <span class="pipeline-kanban-icon">
     ▦
    </span>

    <span class="pipeline-kanban-copy">
     <strong>Kanban View</strong>

     <small>
      {{ __('عرض العملاء الحقيقيين مقسمين حسب حالات العمل التسع') }}
     </small>
    </span>

    <span class="pipeline-kanban-arrow">
     ←
    </span>
   </a>
  </article>
  <article class="panel">
   <header class="panel-head">
    <div>
     <h3>{{ __('توزيع العملاء') }}</h3>
     <p>{{ __('حسب حالة العميل الحالية') }}</p>
    </div>

    <span class="badge">
     {{ number_format($totalLeads) }}
     {{ __('عميل') }}
    </span>
   </header>

   <div class="dist">
    @foreach ($distribution as $item)
     <div>
      <div>
       <span>
        {{ __($item['name']) }}
       </span>

       <b>
        {{ number_format(
         $item['count']
        ) }}
        —
        {{ number_format(
         $item['percentage'],
         1
        ) }}%
       </b>
      </div>

      <span class="progress">
       <span
        style="
         width:
          {{ $item['percentage'] }}%;
         height:100%;
         display:block;
         border-radius:inherit;
         background:
          {{ $item['color'] }};
        "
       ></span>
      </span>
     </div>
    @endforeach
   </div>
  </article>
 </section>
 <section class="bottom">
  <article class="panel">
   <header class="panel-head">
    <div>
     <h3>{{ __('أحدث المتابعات') }}</h3>
     <p>
      {{ __('آخر نشاط مسجل بواسطة الفريق') }}
     </p>
    </div>

    <span class="badge">
     {{ number_format(
      $latestFollowups->count()
     ) }}
     {{ __('متابعة') }}
    </span>
   </header>

   <div style="overflow:auto">
    <table class="table">
     <thead>
      <tr>
       <th>{{ __('العميل') }}</th>
       <th>{{ __('نوع المتابعة') }}</th>
       <th>{{ __('الموظف') }}</th>
       <th>{{ __('التاريخ') }}</th>
       <th>{{ __('الحالة') }}</th>
      </tr>
     </thead>

     <tbody>
      @forelse (
       $latestFollowups
       as $followup
      )
       <tr>
        <td>
         @if ($followup->lead)
          <a
           href="{{ route(
            'v2.leads.show',
            $followup->lead
           ) }}"
           style="
            color:#253146;
            font-weight:900;
            text-decoration:none;
           "
          >
           {{ $followup->lead->name }}
          </a>
         @else
          {{ __('عميل غير متاح') }}
         @endif
        </td>

        <td>
         {{
          $communicationLabels[
           $followup
            ->communication_type
          ]
          ?? $followup
            ->communication_type
          ?? '—'
         }}
        </td>

        <td>
         {{
          $followup->employee_name
          ?: '—'
         }}
        </td>

        <td>
         {{
          $followup
           ->followed_up_at
           ?->format(
            'd/m/Y H:i'
           )
          ?? '—'
         }}
        </td>

        <td>
         {{
          $followup->toStatus?->name_ar
           ? __($followup->toStatus->name_ar)
           : ($followup->lead?->status?->name_ar
              ? __($followup->lead->status->name_ar)
              : '—')
         }}
        </td>
       </tr>
      @empty
       <tr>
        <td colspan="5">
         <div class="empty">
          <i>☷</i>

          <strong>
           {{ __('لا توجد متابعات حالياً') }}
          </strong>

          <p>
           {{ __('ستظهر أحدث المتابعات هنا فور تسجيلها.') }}
          </p>
         </div>
        </td>
       </tr>
      @endforelse
     </tbody>
    </table>
   </div>
  </article>
   <article class="panel">
    <header class="panel-head"><div><h3>{{ __('crm.quick_actions') }}</h3><p>{{ __('crm.daily_shortcuts') }}</p></div></header>
    <div class="quick">
     @can('leads.create')<a href="{{ route('v2.leads.create') }}"><i class="bi bi-person-plus"></i>{{ __('crm.add_lead_short') }}</a>@endcan
     @can('tasks.view')<a href="{{ route('v2.tasks.daily') }}"><i class="bi bi-list-check"></i>{{ __('crm.daily_tasks') }}</a>@endcan
     @can('leads.import')<a href="{{ route('v2.leads.import') }}"><i class="bi bi-box-arrow-in-down"></i>{{ __('crm.import_leads') }}</a>@endcan
     @can('leads.export')<a href="{{ route('v2.leads.export') }}"><i class="bi bi-box-arrow-up"></i>{{ __('crm.export_leads') }}</a>@endcan
    </div>
   </article>
 </section>
</main>
</div>

<!-- CRM DASHBOARD FOLLOWUP MEETING UI REMOVED -->
<div class="toast" id="toast"><span class="dot" style="background:#169a64"></span>{{ __('يتم عرض البيانات الحية من CRM v2.') }}</div>
<script>
(()=>{const b=document.body,m=document.getElementById('menu'),o=document.getElementById('overlay'),t=document.getElementById('toast');document.querySelectorAll('.toggle').forEach(x=>x.onclick=()=>{let e=document.getElementById(x.dataset.menu),v=x.getAttribute('aria-expanded')!=='true';x.setAttribute('aria-expanded',v);e.classList.toggle('open',v)});m.onclick=()=>b.classList.toggle('side-open');o.onclick=()=>b.classList.remove('side-open');document.onkeydown=e=>{if(e.key==='Escape')b.classList.remove('side-open')};try{document.getElementById('date').textContent=new Intl.DateTimeFormat('{{ app()->getLocale() === 'en' ? 'en-US' : 'ar-EG' }}',{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(new Date())+' — {{ __('نظرة عامة على أداء فريق المبيعات') }}'}catch(e){}})();
</script>

<script>
(() => {
    let activeModal = null;
    let previousFocus = null;

    const closeModal = () => {
        if (!activeModal) {
            return;
        }

        activeModal.hidden = true;
        document.body.classList.remove('crm-modal-open');

        if (previousFocus) {
            previousFocus.focus();
        }

        activeModal = null;
        previousFocus = null;
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(
                button.dataset.modalOpen
            );

            if (!modal) {
                return;
            }

            previousFocus = button;
            activeModal = modal;
            modal.hidden = false;
            document.body.classList.add('crm-modal-open');

            const closeButton = modal.querySelector(
                '.crm-modal-close'
            );

            if (closeButton) {
                closeButton.focus();
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeModal) {
            closeModal();
        }
    });
})();
</script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = '#888';
    Chart.defaults.font.family = 'Cairo, sans-serif';

    const perfCtx = document.getElementById('performanceChart');
    if(perfCtx) {
        new Chart(perfCtx.getContext('2d'), {
            data: {
                labels: ['{{ __('14 أغسطس') }}', '{{ __('15 أغسطس') }}', '{{ __('16 أغسطس') }}', '{{ __('17 أغسطس') }}'],
                datasets: [
                    { type: 'line', label: '{{ __('الإجمالي') }}', data: [5, 8, 12, 16], borderColor: '#ff4d4d', backgroundColor: '#ff4d4d', borderWidth: 2, pointRadius: 5, pointHoverRadius: 7, tension: 0 },
                    { type: 'bar', label: '{{ __('عملاء جدد') }}', data: [3, 5, 8, 10], backgroundColor: '#00d2ff', barThickness: 6, borderRadius: 10 },
                    { type: 'bar', label: '{{ __('تم التنفيذ') }}', data: [2, 3, 4, 6], backgroundColor: '#ff4d4d', barThickness: 6, borderRadius: 10 }
                ]
            },
            options: { 
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', rtl: true, labels: { color: '#64748b', usePointStyle: true, boxWidth: 8 } } },
                scales: {
                    y: { beginAtZero: true, max: 20, grid: { color: 'rgba(128, 128, 128, 0.2)', borderDash: [5, 5], drawBorder: false }, ticks: { color: '#64748b' } },
                    x: { grid: { display: false }, ticks: { color: '#64748b' } }
                }
            }
        });
    }

    const polarCtx = document.getElementById('statusPolarChart');
    if(polarCtx) {
        new Chart(polarCtx.getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: ['{{ __('تم التنفيذ') }}', '{{ __('مهتم') }}', '{{ __('لم يتم الرد') }}', '{{ __('غير مهتم') }}'],
                datasets: [{
                    data: [15, 30, 25, 10],
                    backgroundColor: ['#1abc9c', '#00d2ff', '#f5a623', '#ff4d4d'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, layout: { padding: 10 },
                plugins: { 
                    legend: { position: 'bottom', rtl: true, labels: { color: '#64748b', usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: function(context) { return context.label + ': ' + context.raw; } } }
                },
                scales: { 
                    r: { grid: { display: false }, angleLines: { display: false }, ticks: { display: false } } 
                }
            }
        });
    }
});
</script>
</body>

</html>

<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >

 <title>
  {{ __('crm.app_name') }} — {{ __('crm.followup_for_lead', ['name' => $lead->name]) }}
 </title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

 <style>
  :root{
   --red:#dc2637;
   --dark:#182033;
   --muted:#7e899b;
   --line:#e4e8ef;
   --bg:#f4f6f9;
   --card:#fff;
   --blue:#2c66ad;
   --shadow:0 18px 45px #17203310
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

  *{
   box-sizing:border-box
  }

  body{
   margin:0;
   background:var(--bg);
   color:var(--dark);
   font-family:var(--font-primary)
  }

  button,
  input,
  select,
  textarea{
   font-family:inherit
  }

  .crm-app{
   min-height:100vh;
   display:flex;
   }

  .crm-main{
   min-width:0;
   flex:1
  }

  .crm-side{
   position:sticky;
   top:0;
   flex:0 0 288px;
   width:288px;
   min-width:288px;
   height:100vh;
   overflow:auto;
   padding:24px 17px;
   border-inline-end:1px solid var(--line);
   background:#fff;
   z-index:80
  }

  .crm-side-overlay{
   display:none
  }

  .brand,
  .crm-side-brand{
   display:flex;
   align-items:center;
   gap:11px;
   text-decoration:none
  }

  .crm-side-brand{
   padding:4px 8px 20px;
   margin-bottom:17px;
   border-bottom:1px solid var(--line)
  }

  .logo,
  .crm-side-brand .logo{
   width:58px;
   height:58px;
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
   font-size:13px;
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
   background:linear-gradient(
    135deg,
    #e83243,
    #c91d2e
   );
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

  .crm-toggle[aria-expanded="true"]
  .crm-arrow{
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

  .crm-sub a:hover,
  .crm-sub a.active{
   color:var(--red);
   background:#fff0f2
  }

  .topbar{
   min-height:76px;
   display:flex;
   align-items:center;
   gap:15px;
   position:sticky;
   top:0;
   z-index:30;
   padding:13px 24px;
   border-bottom:1px solid var(--line);
   background:#fff
  }

  .menu-button{
   width:42px;
   height:42px;
   display:none;
   place-items:center;
   border:1px solid var(--line);
   border-radius:11px;
   background:#fff;
   color:var(--dark);
   font-size:20px;
   cursor:pointer
  }

  .page-title{
   min-width:0;
   flex:1
  }

  .page-title h1{
   margin:0;
   font-size:22px
  }

  .page-title p{
   margin:5px 0 0;
   color:var(--muted);
   font-size:12px
  }

  .user-tools{
   display:flex;
   align-items:center;
   gap:9px
  }

  .user-chip{
   min-height:40px;
   display:flex;
   align-items:center;
   padding:7px 12px;
   border:1px solid var(--line);
   border-radius:11px;
   background:#fafbfc;
   color:#586477;
   font-size:12px;
   font-weight:800
  }

  .logout-button{
   min-height:40px;
   padding:7px 12px;
   border:1px solid #efc3c7;
   border-radius:10px;
   background:#fff5f6;
   color:#b62231;
   font-size:11px;
   font-weight:900;
   cursor:pointer
  }

  .shell{
   width:min(1420px,calc(100% - 38px));
   margin:23px auto 45px
  }

  .client-card{
   overflow:hidden;
   margin-bottom:18px;
   border:1px solid var(--line);
   border-radius:19px;
   background:var(--card);
   box-shadow:var(--shadow)
  }
  html.dark-mode .client-card{
   background:var(--bg-card,rgba(24,24,27,.75))!important;
   border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
   backdrop-filter:var(--glass-blur,blur(16px))!important;
   -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
   box-shadow:var(--shadow-card,0 10px 30px rgba(0,0,0,.5))!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .client-head{
   display:flex;
   align-items:center;
   gap:17px;
   padding:22px;
   background:linear-gradient(
    125deg,
    #171f31,
    #303b50
   );
   color:#fff
  }
  html.dark-mode .client-head{
   background:linear-gradient(135deg,rgba(255,255,255,.06),rgba(255,255,255,.02))!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important
  }

  .client-avatar{
   width:66px;
   height:66px;
   display:grid;
   place-items:center;
   flex:0 0 66px;
   border:3px solid #ffffff55;
   border-radius:21px;
   background:#fff;
   color:var(--red);
   font-size:28px;
   font-weight:900
  }
  html.dark-mode .client-avatar{
   background:rgba(255,255,255,.12)!important;
   border-color:rgba(255,255,255,.2)!important;
   color:#f87171!important
  }

  .client-copy{
   min-width:0;
   flex:1
  }

  .client-copy small{
   color:#f0a1aa;
   font-size:10px;
   font-weight:900
  }
  html.dark-mode .client-copy small{color:#f87171!important}

  .client-copy h2{
   margin:7px 0 6px;
   font-size:24px
  }
  html.dark-mode .client-copy h2{color:#ffffff!important}

  .client-copy p{
   margin:0;
   color:#cbd2de;
   font-size:12px
  }
  html.dark-mode .client-copy p{color:#d4d4d8!important}

  .client-actions{
   display:flex;
   flex-wrap:wrap;
   gap:8px
  }

  .header-action{
   min-height:41px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:8px 13px;
   border:1px solid #ffffff55;
   border-radius:10px;
   background:#ffffff12;
   color:#fff;
   text-decoration:none;
   font-size:11px;
   font-weight:900
  }
  html.dark-mode .header-action{
   background:rgba(255,255,255,.08)!important;
   border-color:rgba(255,255,255,.14)!important;
   color:#f4f4f5!important
  }
  html.dark-mode .header-action.primary{
   background:linear-gradient(135deg,#e83243,#c91d2e)!important;
   border-color:#ef4444!important;
   color:#ffffff!important
  }
  .header-action{
   min-height:41px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:8px 13px;
   border:1px solid #ffffff55;
   border-radius:10px;
   background:#ffffff12;
   color:#fff;
   text-decoration:none;
   font-size:11px;
   font-weight:900
  }

  .header-action.primary{
   border-color:#fff;
   background:#fff;
   color:#273149
  }

  .client-data{
   display:grid;
   grid-template-columns:
    repeat(4,minmax(0,1fr));
   gap:1px;
   background:var(--line)
  }

  .client-data-item{
   min-width:0;
   padding:15px;
   background:#fff
  }
  html.dark-mode .client-data{
   background:rgba(255,255,255,.08)!important
  }
  html.dark-mode .client-data-item{
   background:rgba(24,24,27,.9)!important
  }

  .client-data-item small{
   display:block;
   margin-bottom:6px;
   color:#9099a8;
   font-size:9px;
   font-weight:800
  }
  html.dark-mode .client-data-item small{
   color:var(--text-muted,#a1a1aa)!important
  }

  .client-data-item strong{
   display:block;
   color:#3c4658;
   font-size:12px;
   line-height:1.7;
   overflow-wrap:anywhere
  }
  html.dark-mode .client-data-item strong{
   color:var(--text-primary,#f4f4f5)!important
  }

  .workspace{
   display:grid;
   grid-template-columns:
    minmax(0,1.05fr)
    minmax(340px,.95fr);
   gap:18px;
   align-items:start
  }

  .panel{
   overflow:hidden;
   border:1px solid var(--line);
   border-radius:17px;
   background:transparent;
   box-shadow:var(--shadow)
  }
  html.dark-mode .panel{
   background:transparent!important;
   border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
   backdrop-filter:none!important;
   -webkit-backdrop-filter:none!important;
   box-shadow:none!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .panel-head{
   padding:17px 19px;
   border-bottom:1px solid var(--line);
   background:transparent
  }
  html.dark-mode .panel-head{
   background:transparent!important;
   border-bottom:1px solid rgba(255,255,255,.08)!important
  }
  html.dark-mode .panel-head h3{color:#f4f4f5!important;font-weight:800!important}
  html.dark-mode .panel-head p{color:var(--text-muted,#a1a1aa)!important}
  html.dark-mode .panel-body{color:var(--text-primary,#f4f4f5)!important}
   margin-bottom:15px;
   padding:13px 15px;
   border:1px solid #a9d8bd;
   border-radius:11px;
   background:#effaf3;
   color:#237448;
   font-size:12px;
   font-weight:900
  }

  .error-box{
   margin-bottom:15px;
   padding:13px 15px;
   border:1px solid #efb7bd;
   border-radius:11px;
   background:#fff3f4;
   color:#a92735;
   font-size:11px;
   line-height:1.8
  }

  .form-grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:15px
  }

  .field{
   min-width:0
  }

  .field.full{
   grid-column:1/-1
  }

  .field label{
   display:block;
   margin-bottom:7px;
   color:#4f5b6f;
   font-size:11px;
   font-weight:900
  }
  html.dark-mode .field label{
   color:#e4e4e7!important;
   font-weight:700!important
  }

  .field small{
   display:block;
   margin-top:6px;
   color:#929baa;
   font-size:9px;
   line-height:1.7
  }
  html.dark-mode .field small{
   color:var(--text-muted,#a1a1aa)!important
  }

  .control{
   width:100%;
   min-height:45px;
   padding:10px 12px;
   border:1px solid #dbe0e8;
   border-radius:11px;
   background:#fff;
   color:#303b4f;
   font-size:12px;
   outline:none
  }
  html.dark-mode .control{
   background:var(--bg-input,rgba(39,39,42,.65))!important;
   border:1px solid rgba(255,255,255,.14)!important;
   color:#f4f4f5!important
  }

  .control:focus{
   border-color:#8eacd5;
   box-shadow:0 0 0 3px #2c66ad13
  }
  html.dark-mode .control:focus{
   border-color:rgba(239,68,68,.6)!important;
   background:rgba(39,39,42,.95)!important;
   box-shadow:0 0 0 3px rgba(239,68,68,.2)!important
  }
  html.dark-mode .control option{
   background:#18181b!important;
   color:#f4f4f5!important
  }

  textarea.control{
   min-height:145px;
   resize:vertical;
   line-height:1.8
  }

  .employee-display{
   min-height:45px;
   display:flex;
   align-items:center;
   padding:10px 12px;
   border:1px dashed #cbd3df;
   border-radius:11px;
   background:#f8f9fb;
   color:#48556a;
   font-size:12px;
   font-weight:900
  }
  html.dark-mode .employee-display{
   background:rgba(255,255,255,.04)!important;
   border-color:rgba(255,255,255,.15)!important;
   color:#f4f4f5!important
  }
  .submit-row{
   display:flex;
   justify-content:flex-end;
   margin-top:17px
  }

  .submit-button{
   min-height:46px;
   padding:9px 20px;
   border:1px solid #c91d2e;
   border-radius:11px;
   background:linear-gradient(
    135deg,
    #e83243,
    #c91d2e
   );
   color:#fff;
   font-size:12px;
   font-weight:900;
   cursor:pointer;
   box-shadow:0 11px 24px #dc26372a
  }

  .timeline{
   display:grid;
   gap:12px
  }

  .timeline-item{
   position:relative;
   padding:15px;
   border:1px solid var(--line);
   border-radius:13px;
   background:#fff
  }
  html.dark-mode .timeline-item{
   background:rgba(255,255,255,.03)!important;
   border:1px solid rgba(255,255,255,.08)!important;
   border-radius:13px!important
  }

  .timeline-top{
   display:flex;
   align-items:flex-start;
   justify-content:space-between;
   gap:12px;
   margin-bottom:11px
  }

  .timeline-employee{
   color:#283449;
   font-size:12px;
   font-weight:900
  }
  html.dark-mode .timeline-employee{
   color:#f4f4f5!important;
   font-weight:800!important
  }

  .timeline-date{
   color:#929baa;
   font-size:9px;
   white-space:nowrap
  }
  html.dark-mode .timeline-date{
   color:var(--text-muted,#a1a1aa)!important
  }

  .timeline-badges{
   display:flex;
   flex-wrap:wrap;
   gap:6px;
   margin-bottom:10px
  }

  .timeline-badge{
   display:inline-flex;
   align-items:center;
   padding:5px 8px;
   border-radius:999px;
   background:#f0f3f8;
   color:#59677d;
   font-size:9px;
   font-weight:900
  }
  html.dark-mode .timeline-badge{
   background:rgba(255,255,255,.08)!important;
   color:#d4d4d8!important;
   border:1px solid rgba(255,255,255,.1)!important
  }

  .timeline-badge.channel{
   background:#eef5fd;
   color:#285e9d
  }
  html.dark-mode .timeline-badge.channel{
   background:rgba(59,130,246,.15)!important;
   color:#60a5fa!important;
   border:1px solid rgba(59,130,246,.3)!important
  }

  .timeline-outcome{
   margin:0;
   color:#4d596d;
   font-size:11px;
   line-height:1.9;
   white-space:pre-wrap;
   overflow-wrap:anywhere
  }
  html.dark-mode .timeline-outcome{
   color:#e4e4e7!important
  }

  .timeline-next{
   margin-top:10px;
   padding-top:9px;
   border-top:1px solid #eef1f5;
   color:#7c8798;
   font-size:9px
  }
  html.dark-mode .timeline-next{
   border-top-color:rgba(255,255,255,.08)!important;
   color:var(--text-muted,#a1a1aa)!important
  }

  .empty-state{
   padding:32px 18px;
   border:1px dashed #d4dae3;
   border-radius:13px;
   background:#fafbfc;
   color:#7e8999;
   text-align:center;
   font-size:11px;
   line-height:1.9
  }
  html.dark-mode .empty-state{
   background:rgba(255,255,255,.02)!important;
   border-color:rgba(255,255,255,.1)!important;
   color:var(--text-muted,#a1a1aa)!important
  }

  @media(max-width:1100px){
   .crm-side{
    position:fixed;
    right:0;
    top:0;
    width:min(288px,calc(100vw - 45px));
    min-width:0;
    max-width:none;
    transform:translateX(105%);
    transition:.25s;
    box-shadow:-20px 0 55px #17203325
   }

   body.crm-side-open .crm-side{
    transform:none
   }

   .crm-side-overlay{
    position:fixed;
    inset:0;
    z-index:70;
    border:0;
    background:#11182788
   }

   body.crm-side-open
   .crm-side-overlay{
    display:block
   }

   .menu-button{
    display:grid
   }

   .workspace{
    grid-template-columns:1fr
   }
  }

  @media(max-width:800px){
   .shell{
    width:calc(100% - 18px);
    margin-top:12px
   }

   .topbar{
    padding:11px
   }

   .user-chip{
    display:none
   }

   .client-head{
    align-items:flex-start;
    flex-wrap:wrap
   }

   .client-actions{
    width:100%
   }

   .client-data{
    grid-template-columns:
     repeat(2,minmax(0,1fr))
   }
  }

  @media(max-width:560px){
   .page-title p{
    display:none
   }

   .client-data,
   .form-grid{
    grid-template-columns:1fr
   }

   .field.full{
    grid-column:auto
   }

   .client-actions{
    flex-direction:column
   }

   .header-action{
    width:100%
   }

   .timeline-top{
    flex-direction:column
   }
  }
 
  /* FOLLOW-UP EDITABLE STAGE DATA START */

  .followup-stage-editor{
   grid-column:1/-1;
   overflow:hidden;
   margin:4px 0;
   border:1px solid var(--line, #dfe5ed);
   border-radius:15px;
   background:var(--card, #fff)
  }

  .followup-stage-editor.is-hidden,
  .followup-detail-panel.is-hidden{
   display:none!important
  }

  .followup-editor-head{
   padding:16px 18px;
   border-bottom:1px solid var(--line, #e7eaf0);
   background:var(--bg, #f8fafc)
  }

  .followup-editor-head h3{
   margin:0;
   color:var(--dark, #29364b);
   font-size:18px
  }

  .followup-editor-head p{
   margin:6px 0 0;
   color:var(--muted, #7d8899);
   font-size:12px;
   line-height:1.8
  }

  .dynamic-stage-fields-block{
   margin-top:16px;
   border:1px solid var(--line, #e2e8f0);
   border-radius:12px;
   background:var(--bg, #f8fafc);
   padding:16px
  }

  .dynamic-stage-fields-block .followup-editor-head{
   margin-bottom:12px;
   padding:0 0 10px 0;
   border-bottom:1px solid var(--line, #e2e8f0);
   background:transparent
  }

  .dynamic-stage-fields-block .followup-editor-head h3{
   font-size:15px;
   margin:0;
   color:var(--dark, #1e293b);
   display:flex;
   align-items:center;
   gap:8px
  }

  .dynamic-stage-fields-block .followup-editor-head h3 i{
   color:#4f46e5
  }

  html.dark-mode .followup-stage-editor{
   background:rgba(255,255,255,0.03)!important;
   border-color:rgba(255,255,255,0.08)!important;
   color:#f4f4f5!important
  }

  html.dark-mode .followup-editor-head{
   background:rgba(255,255,255,0.02)!important;
   border-bottom-color:rgba(255,255,255,0.08)!important
  }

  html.dark-mode .followup-editor-head h3{
   color:#f4f4f5!important
  }

  html.dark-mode .followup-editor-head p{
   color:var(--text-muted, #a1a1aa)!important
  }

  html.dark-mode .dynamic-stage-fields-block{
   background:rgba(255,255,255,0.03)!important;
   border-color:rgba(255,255,255,0.08)!important
  }

  html.dark-mode .dynamic-stage-fields-block .followup-editor-head{
   border-bottom-color:rgba(255,255,255,0.08)!important;
   background:transparent!important
  }

  html.dark-mode .dynamic-stage-fields-block .followup-editor-head h3{
   color:#f4f4f5!important
  }

  html.dark-mode .dynamic-stage-fields-block .followup-editor-head h3 i{
   color:#818cf8!important
  }

  .followup-editor-grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:14px;
   padding:17px
  }

  .donation-schedule-note{
   grid-column:1/-1;
   padding:13px 14px;
   border:1px solid #b9ddc7;
   border-radius:11px;
   background:#f0faf4;
   color:#22653e;
   font-size:11px;
   font-weight:800;
   line-height:1.8
  }

  html.dark-mode .donation-schedule-note{
   border-color:rgba(74,222,128,.3)!important;
   background:rgba(34,197,94,.1)!important;
   color:#86efac!important
  }

  .followup-detail-panel{
   margin:0 17px 17px;
   padding:16px;
   border:1px solid #e5e9f0;
   border-radius:12px;
   background:#fafbfc
  }

  .followup-stage-editor
  .field.full{
   grid-column:1/-1
  }

  .followup-stage-editor textarea.control{
   min-height:105px;
   resize:vertical
  }

  .followup-file-help{
   display:block;
   margin-top:7px;
   color:#808b9c;
   font-size:11px;
   line-height:1.7
  }

  .timeline-field-changes{
   margin-top:13px;
   padding:13px;
   border:1px solid #dce6f2;
   border-radius:11px;
   background:#f7faff
  }

  .timeline-field-changes>strong{
   display:block;
   margin-bottom:9px;
   color:#31465f;
   font-size:13px
  }

  .timeline-change-list{
   display:grid;
   gap:8px;
   margin:0;
   padding:0;
   list-style:none
  }

  .timeline-change-item{
   display:grid;
   gap:5px;
   padding:9px 10px;
   border-radius:9px;
   background:#fff
  }

  .timeline-change-label{
   color:#69778b;
   font-size:12px;
   font-weight:900
  }

  .timeline-change-values{
   display:flex;
   align-items:center;
   flex-wrap:wrap;
   gap:7px;
   font-size:13px
  }

  .timeline-change-old{
   color:#a05961;
   text-decoration:line-through
  }

  .timeline-change-new{
   color:#247047;
   font-weight:900
  }

  html.dark-mode .followup-detail-panel{
   background:rgba(255,255,255,0.02)!important;
   border-color:rgba(255,255,255,0.08)!important
  }

  html.dark-mode .timeline-field-changes{
   background:rgba(255,255,255,0.03)!important;
   border-color:rgba(255,255,255,0.08)!important
  }

  html.dark-mode .timeline-field-changes>strong{
   color:#f4f4f5!important
  }

  html.dark-mode .timeline-change-item{
   background:rgba(24,24,27,0.85)!important;
   border:1px solid rgba(255,255,255,0.08)!important
  }

  html.dark-mode .timeline-change-label{
   color:#a1a1aa!important
  }

  html.dark-mode .timeline-change-old{
   color:#f87171!important
  }

  html.dark-mode .timeline-change-new{
   color:#4ade80!important
  }

  .latest-followups-panel .panel-head{
   padding:20px 21px
  }

  .latest-followups-panel .panel-head h3{
   font-size:23px
  }

  .latest-followups-panel .panel-head p{
   font-size:14px;
   line-height:1.7
  }

  .latest-followups-panel .panel-body{
   padding:21px
  }

  .latest-followups-panel .timeline{
   gap:15px
  }

  .latest-followups-panel .timeline-item{
   padding:18px;
   border-radius:15px
  }

  .latest-followups-panel
  .timeline-employee{
   font-size:17px
  }

  .latest-followups-panel
  .timeline-date{
   font-size:13px
  }

  .latest-followups-panel
  .timeline-badge{
   padding:7px 10px;
   font-size:13px
  }

  .latest-followups-panel
  .timeline-outcome{
   font-size:16px;
   line-height:2
  }

  .latest-followups-panel
  .timeline-next{
   font-size:13px;
   line-height:1.8
  }

  .latest-followups-panel
  .empty-state{
   font-size:15px
  }

  @media(max-width:650px){
   .followup-editor-grid{
    grid-template-columns:1fr
   }

   .followup-stage-editor
   .field.full{
    grid-column:auto
   }
  }

  /* FOLLOW-UP EDITABLE STAGE DATA END */


  /* FOLLOW-UP STAGE COLOR START */

  .latest-followups-panel .timeline-item{
   border-inline-start:
    4px solid
    var(--timeline-stage-color,#64748b)
  }

  .timeline-stage-badge{
   position:relative;
   padding-inline-start:22px!important;
   border-color:
    var(--timeline-stage-color,#64748b)!important;
   color:
    var(--timeline-stage-color,#64748b)!important;
   background:#fff!important
  }

  .timeline-stage-badge::before{
   content:"";
   position:absolute;
   right:8px;
   top:50%;
   width:8px;
   height:8px;
   border-radius:50%;
   background:
    var(--timeline-stage-color,#64748b);
   transform:translateY(-50%)
  }

  /* FOLLOW-UP STAGE COLOR END */

  /* Monochrome Theme Overrides for Followups */
  html.crm-monochrome .client-avatar { background: #ededed !important; color: #171717 !important; }
  html.crm-monochrome.dark-mode .client-avatar { background: #262626 !important; color: #f5f5f5 !important; }
  html.crm-monochrome .timeline-stage-badge { border-color: #d4d4d4 !important; color: #171717 !important; background: #fff !important; }
  html.crm-monochrome.dark-mode .timeline-stage-badge { border-color: #404040 !important; color: #f5f5f5 !important; background: #262626 !important; }
  html.crm-monochrome .timeline-stage-badge::before { background: #171717 !important; }
  html.crm-monochrome.dark-mode .timeline-stage-badge::before { background: #f5f5f5 !important; }
  html.crm-monochrome .task-status-pill, html.crm-monochrome .badge.active, html.crm-monochrome .badge { background: #ededed !important; border-color: #d4d4d4 !important; color: #171717 !important; }
  html.crm-monochrome.dark-mode .task-status-pill, html.crm-monochrome.dark-mode .badge.active, html.crm-monochrome.dark-mode .badge { background: #262626 !important; border-color: #404040 !important; color: #f5f5f5 !important; }


/* CRM KANBAN POPUP MODE START */

body.kanban-followup-popup{
 background:var(--bg, #f5f6f8)
}

html.dark-mode body.kanban-followup-popup{
 background:#09090b!important
}

html.dark-mode body.kanban-followup-popup .client-card{
 background:#18181b!important;
 border-color:rgba(255,255,255,0.08)!important
}

html.dark-mode body.kanban-followup-popup .client-data{
 background:#18181b!important
}

html.dark-mode body.kanban-followup-popup #lead_status_id:disabled{
 background:#27272a!important;
 color:#a1a1aa!important
}

html.crm-monochrome:not(.dark-mode) body.kanban-followup-popup{
 background:#fafafa!important
}

html.crm-monochrome.dark-mode body.kanban-followup-popup{
 background:#111111!important
}

body.kanban-followup-popup .crm-side,
body.kanban-followup-popup .crm-side-overlay,
body.kanban-followup-popup .topbar,
body.kanban-followup-popup
 .latest-followups-panel{
 display:none!important
}

body.kanban-followup-popup .crm-app{
 display:block!important;
 min-height:auto!important
}

body.kanban-followup-popup .crm-main{
 width:100%!important;
 min-width:0!important;
 margin:0!important
}

body.kanban-followup-popup .shell{
 width:100%!important;
 max-width:none!important;
 margin:0!important;
 padding:14px!important
}

body.kanban-followup-popup .workspace{
 display:block!important
}

body.kanban-followup-popup .client-card{
 margin-bottom:12px!important;
 border-radius:14px!important;
 box-shadow:none!important
}

body.kanban-followup-popup .client-head{
 min-height:auto!important;
 padding:14px 16px!important
}

body.kanban-followup-popup .client-avatar{
 width:48px!important;
 height:48px!important;
 flex-basis:48px!important;
 border-radius:14px!important;
 font-size:21px!important
}

body.kanban-followup-popup .client-copy h2{
 margin:4px 0!important;
 font-size:19px!important
}

body.kanban-followup-popup .client-actions{
 display:none!important
}

body.kanban-followup-popup .client-data{
 grid-template-columns:
  repeat(4,minmax(0,1fr))!important
}

body.kanban-followup-popup .panel{
 border-radius:14px!important;
 box-shadow:none!important
}

body.kanban-followup-popup
 #lead_status_id:disabled{
 opacity:1!important;
 background:#f2f4f7!important;
 color:#39465a!important;
 cursor:not-allowed
}

@media(max-width:760px){
 body.kanban-followup-popup
  .client-data{
  grid-template-columns:
   repeat(2,minmax(0,1fr))!important
 }
}

/* CRM KANBAN POPUP MODE END */

/* Screenshot Dropzone & Clipboard Paste Styles */
.receipt-dropzone {
 position: relative;
 border: 2px dashed #cbd5e1;
 border-radius: 12px;
 background: #f8fafc;
 padding: 14px;
 text-align: center;
 cursor: pointer;
 transition: border-color 0.2s, background-color 0.2s;
 outline: none;
}
.receipt-dropzone:hover,
.receipt-dropzone:focus-visible,
.receipt-dropzone.is-dragover {
 border-color: #3478f6;
 background: #eff6ff;
}
.receipt-dropzone-prompt {
 display: flex;
 flex-direction: column;
 align-items: center;
 gap: 6px;
}
.receipt-dropzone-icon {
 width: 40px;
 height: 40px;
 display: grid;
 place-items: center;
 border-radius: 50%;
 background: #e0f2fe;
 color: #0284c7;
 font-size: 18px;
}
.receipt-dropzone-text strong {
 display: block;
 color: #334155;
 font-size: 12px;
 font-weight: 800;
}
.receipt-dropzone-text span {
 display: block;
 color: #64748b;
 font-size: 10px;
 margin-top: 2px;
}
.receipt-preview-box {
 display: flex;
 align-items: center;
 gap: 12px;
 padding: 8px 10px;
 background: #ffffff;
 border: 1px solid #e2e8f0;
 border-radius: 10px;
 text-align: start;
}
.receipt-preview-thumb-wrap {
 width: 50px;
 height: 50px;
 border-radius: 8px;
 overflow: hidden;
 background: #f1f5f9;
 flex: 0 0 50px;
 border: 1px solid #e2e8f0;
}
.receipt-preview-thumb-wrap img {
 width: 100%;
 height: 100%;
 object-fit: cover;
 display: block;
}
.receipt-preview-details {
 flex: 1;
 min-width: 0;
 display: flex;
 flex-direction: column;
 gap: 2px;
}
.receipt-preview-filename {
 font-size: 11px;
 font-weight: 800;
 color: #1e293b;
 white-space: nowrap;
 overflow: hidden;
 text-overflow: ellipsis;
}
.receipt-preview-filesize {
 font-size: 10px;
 color: #64748b;
}
.receipt-preview-badge {
 display: inline-flex;
 align-items: center;
 gap: 4px;
 font-size: 10px;
 font-weight: 700;
 color: #16a34a;
}
.receipt-preview-remove {
 width: 32px;
 height: 32px;
 display: grid;
 place-items: center;
 border: 1px solid #fecaca;
 border-radius: 8px;
 background: #fef2f2;
 color: #dc2626;
 cursor: pointer;
 transition: background-color 0.15s;
}
.receipt-preview-remove:hover {
 background: #fee2e2;
}


/* ── Outcome Action Bar ── */
.outcome-bar-label {
 font-weight: 700;
 font-size: 14px;
 color: #334155;
 margin-bottom: 8px;
}
.outcome-bar {
 display: grid;
 grid-template-columns: 1fr 1fr;
 gap: 10px;
}
@media (max-width: 600px) {
 .outcome-bar {
  grid-template-columns: 1fr;
 }
}
.outcome-btn {
 display: flex;
 align-items: center;
 justify-content: center;
 gap: 8px;
 min-height: 56px;
 border-radius: 14px;
 border: 2px solid #e2e8f0;
 background: #fff;
 font-size: 15px;
 font-weight: 700;
 cursor: pointer;
 transition: border-color .2s, background .2s, transform .15s;
 padding: 8px 12px;
 line-height: 1.3;
}
.outcome-btn:hover {
 transform: scale(1.02);
}
.outcome-btn:disabled {
 opacity: .45;
 cursor: not-allowed;
 transform: none;
}
.outcome-btn i {
 font-size: 20px;
}
.outcome-btn--donated {
 color: #16a34a;
}
.outcome-btn--donated:hover,
.outcome-btn--donated.active {
 border-color: #16a34a;
 background: #f0fdf4;
}
.outcome-btn--no-answer {
 color: #d97706;
}
.outcome-btn--no-answer:hover,
.outcome-btn--no-answer.active {
 border-color: #d97706;
 background: #fffbeb;
}
.outcome-btn--followup-later {
 color: #2563eb;
}
.outcome-btn--followup-later:hover,
.outcome-btn--followup-later.active {
 border-color: #2563eb;
 background: #eff6ff;
}
.outcome-btn--not-interested {
 color: #dc2626;
}
.outcome-btn--not-interested:hover,
.outcome-btn--not-interested.active {
 border-color: #dc2626;
 background: #fef2f2;
}
.outcome-btn.active {
 transform: scale(1.03);
 box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

/* ── Preset Chips ── */
.chip-group {
 display: flex;
 flex-wrap: wrap;
 gap: 6px;
 margin-top: 8px;
}
.chip-group-label {
 font-size: 12px;
 font-weight: 600;
 color: #64748b;
 width: 100%;
 margin-bottom: 2px;
}
.chip {
 display: inline-flex;
 align-items: center;
 gap: 4px;
 padding: 6px 14px;
 border-radius: 20px;
 border: 1px solid #e2e8f0;
 background: #f8fafc;
 font-size: 13px;
 font-weight: 600;
 cursor: pointer;
 transition: background .15s, border-color .15s;
 color: #334155;
}
.chip:hover {
 background: #e2e8f0;
 border-color: #94a3b8;
}
.chip--reason:hover {
 background: #fef2f2;
 border-color: #fca5a5;
 color: #dc2626;
}
.chip--callback:hover {
 background: #eff6ff;
 border-color: #93c5fd;
 color: #2563eb;
}

/* ── Dark Mode ── */
html.dark-mode .outcome-bar-label {
 color: #cbd5e1;
}
html.dark-mode .outcome-btn {
 background: #1e293b;
 border-color: #334155;
 color: #e2e8f0;
}
html.dark-mode .outcome-btn--donated:hover,
html.dark-mode .outcome-btn--donated.active {
 background: #052e16;
 border-color: #16a34a;
 color: #4ade80;
}
html.dark-mode .outcome-btn--no-answer:hover,
html.dark-mode .outcome-btn--no-answer.active {
 background: #451a03;
 border-color: #d97706;
 color: #fbbf24;
}
html.dark-mode .outcome-btn--followup-later:hover,
html.dark-mode .outcome-btn--followup-later.active {
 background: #172554;
 border-color: #2563eb;
 color: #60a5fa;
}
html.dark-mode .outcome-btn--not-interested:hover,
html.dark-mode .outcome-btn--not-interested.active {
 background: #450a0a;
 border-color: #dc2626;
 color: #f87171;
}
html.dark-mode .chip {
 background: #1e293b;
 border-color: #334155;
 color: #cbd5e1;
}
html.dark-mode .chip:hover {
 background: #334155;
 border-color: #64748b;
}
html.dark-mode .chip--reason:hover {
 background: #450a0a;
 border-color: #f87171;
 color: #f87171;
}
html.dark-mode .chip--callback:hover {
 background: #172554;
 border-color: #60a5fa;
 color: #60a5fa;
}
html.dark-mode .chip-group-label {
 color: #94a3b8;
}
</style>
</head>

<body
 @if (
  request()->boolean(
   'kanban_popup'
  )
 )
  class="kanban-followup-popup"
 @endif
>
 @unless (
  request()->boolean(
   'kanban_popup'
  )
 )
  @include('partials.page-loader')
 @endunless

 <div class="crm-app">
  @include('partials.crm-sidebar')

  <button
   class="crm-side-overlay"
   id="crmSidebarOverlay"
   type="button"
   aria-label="{{ __('crm.close_menu') }}"
  ></button>

  <main class="crm-main">
   <header class="topbar">
    <button
     class="menu-button"
     id="crmMenuButton"
     type="button"
     aria-controls="crmSidebar"
     aria-expanded="false"
     aria-label="{{ __('crm.open_menu') }}"
    >
     ☰
    </button>

    <div class="page-title">
     <h1>{{ __('crm.record_lead_followup') }}</h1>
     <p>
      {{ __('crm.followup_subtitle') }}
     </p>
    </div>

     @include('partials.profile-dropdown')
   </header>

   @php
       $currentStage = $lead->status?->stage;
       $currentStageName = $currentStage?->localizedName() ?? $currentStage?->name_ar ?? $lead->status?->name_ar ?? '----';
       $assignedEmployeeName = $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned');

       $donationTypeName = $lead->donation_type ?? $lead->donationTypeRel?->name_ar;
       $donationCycleName = $lead->donation_cycle ? (__('crm.donor_followup_' . $lead->donation_cycle) ?: $lead->donation_cycle) : null;

       $totalDonationAmount = $lead->relationLoaded('donations')
           ? $lead->donations->sum('amount')
           : $lead->donations()->sum('amount');
       if (!$totalDonationAmount && $lead->donation_value) {
           $totalDonationAmount = (float) $lead->donation_value;
       }

       $lastDonation = $lead->relationLoaded('donations')
           ? $lead->donations->sortByDesc('donated_at')->first()
           : $lead->donations()->orderByDesc('donated_at')->first();

       $hasDonationInfo = !empty($donationTypeName) || !empty($donationCycleName) || !empty($totalDonationAmount) || !empty($lastDonation);
       $isCompany = !empty($lead->company_name) && (!empty($lead->users_count) || !empty($lead->branches_count));
   @endphp

   <div class="shell">
    <section class="client-card">
     <div class="client-head">
      <div
       class="client-avatar"
       aria-hidden="true"
      >
       {{ mb_substr((string) $lead->name, 0, 1) }}
      </div>

      <div class="client-copy">
       <small>
        {{ __('crm.lead_id_badge', ['id' => $lead->id]) }}
       </small>

       <h2>{{ $lead->name }}</h2>

       <p>
        {{ __('crm.customer_donor_information') }}
        @if ($lead->source)
         — {{ __('crm.source') }}: {{ $lead->source }}
        @endif
       </p>
      </div>

      <div class="client-actions">
       <a
        class="header-action primary"
        href="{{ route('v2.leads') }}"
       >
        {{ __('crm.back_to_leads_short') }}
       </a>

       <a
        class="header-action"
        href="{{ route(
         'v2.leads.show',
         $lead
        ) }}"
       >
        {{ __('crm.view_lead_data') }}
       </a>

       @if ($callPhone)
        <a
         class="header-action"
         href="callto:{{ $callPhone }}"
        >
         {{ __('crm.call_action') }}
        </a>
       @endif
      </div>
     </div>

     <div class="client-data">
      <div class="client-data-item">
       <small>{{ __('crm.name') }}</small>
       <strong>{{ $lead->name }}</strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.phone') }}</small>
       <strong>{{ $lead->phone ?: '----' }}</strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.governorate') }}</small>
       <strong>{{ $lead->governorate ?: '----' }}</strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.address') }}</small>
       <strong>{{ $lead->address ?: '----' }}</strong>
      </div>

      @if ($hasDonationInfo)
       <div class="client-data-item">
        <small>{{ __('crm.donation_type') }}</small>
        <strong>{{ $donationTypeName ?: '----' }}</strong>
       </div>

       <div class="client-data-item">
        <small>{{ __('crm.donation_cycle') }}</small>
        <strong>{{ $donationCycleName ?: '----' }}</strong>
       </div>

       <div class="client-data-item">
        <small>{{ __('crm.total_donations') }}</small>
        <strong>{{ $totalDonationAmount ? number_format((float) $totalDonationAmount, 2) . ' ' . __('crm.currency_egp') : '----' }}</strong>
       </div>

       <div class="client-data-item">
        <small>{{ __('crm.last_donation') }}</small>
        <strong>
         @if ($lastDonation)
          {{ $lastDonation->donated_at ? $lastDonation->donated_at->format('d/m/Y') : $lastDonation->created_at->format('d/m/Y') }}
          ({{ number_format((float) $lastDonation->amount, 2) }} {{ __('crm.currency_egp') }})
         @elseif ($lead->donation_value)
          {{ number_format((float) $lead->donation_value, 2) }} {{ __('crm.currency_egp') }}
         @else
          ----
         @endif
        </strong>
       </div>
      @endif

      <div class="client-data-item">
       <small>{{ __('crm.responsible_employee') }}</small>
       <strong>{{ $assignedEmployeeName }}</strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.current_stage') }}</small>
       <strong>{{ $currentStageName }}</strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.next_followup') }}</small>
       <strong>
        {{
         $lead->next_follow_up_at
          ?->format('d/m/Y - h:i A')
         ?: __('crm.without_date')
        }}
       </strong>
      </div>

      @if ($lead->email)
       <div class="client-data-item">
        <small>{{ __('crm.email') }}</small>
        <strong>{{ $lead->email }}</strong>
       </div>
      @endif
     </div>
    </section>

    @if (session('success'))
     <div class="flash-success">
      {{ session('success') }}
     </div>
    @endif

    @if ($errors->any())
     <div class="error-box">
      <strong>
       {{ __('crm.review_followup_data') }}
      </strong>

      <ul>
       @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
       @endforeach
      </ul>
     </div>
    @endif

    <div class="workspace">
     @can('leads.followups.create')
     <section class="panel">
      <header class="panel-head">
       <h3>{{ __('crm.new_followup_data') }}</h3>
       <p>
        {{ __('crm.registered_employee_label') }}
        {{ $currentEmployee }}
       </p>
      </header>

      <div class="panel-body">
       <form
        method="POST"
         enctype="multipart/form-data"
        action="{{ route(
         'v2.leads.followups.store',
         $lead
        ) }}"
       >
        @csrf


        @if (
         request()->boolean(
          'kanban_popup'
         )
        )
         <input
          type="hidden"
          name="kanban_popup"
          value="1"
         >
        @endif
        @if (request()->filled('context'))
         <input
          type="hidden"
          name="context"
          value="{{ request('context') }}"
         >
        @endif

        @php
         $selectedStatusId = (int) old(
          'lead_status_id',
          $defaultStatusId
           ?? $lead->lead_status_id
         );

         $selectedCommunicationType =
          (string) old(
           'communication_type',
           $defaultCommunicationType
          );

         $statusIdByCode = $statusGroups
          ->flatten(1)
          ->keyBy('code')
          ->map(fn($s) => (int) $s->id);

        @endphp

         <div class="form-grid">
         <div class="field full">
          <label>{{ __('crm.outcome_select_result') }}</label>

          {{-- Hidden select preserves all data-attributes for existing JS --}}
          <select
           class="control"
           id="lead_status_id"
           name="lead_status_id"
           required
           style="display:none"
          >
           @foreach (
            $statusGroups
            as $stageName => $stageStatuses
           )
            <optgroup label="{{ $stageName }}">
             @foreach ($stageStatuses as $status)
              <option
               value="{{ $status->id }}"
               data-stage-id="{{ $status->pipeline_stage_id ?? $status->stage?->id }}"
               data-stage-name="{{ $status->stage?->name_ar ?? '----' }}"
               data-stage-description="{{ $status->stage?->description_ar ?? '----' }}"
               data-stage-code="{{ $status->stage?->code ?? '----' }}"
               data-stage-position="{{ $status->stage?->position ?? '----' }}"
               data-stage-color="{{ $status->stage?->color ?? '#64748b' }}"
               data-status-name="{{ $status->name_ar }}"
               data-status-code="{{ $status->code }}"
               data-status-color="{{ $status->color ?? '#64748b' }}"
               data-terminal="{{ $status->is_terminal ? '1' : '0' }}"
               @selected(
                $selectedStatusId
                === (int) $status->id
               )
              >
               {{ $status->name_ar }}
              </option>
             @endforeach
            </optgroup>
           @endforeach
          </select>


          <div class="outcome-bar" id="outcomeBar">
           <button
            type="button"
            class="outcome-btn outcome-btn--donated"
            data-outcome-status="donor"
            data-outcome-id="{{ $statusIdByCode->get('donor', '') }}"
           >
            <i class="bi bi-heart-fill"></i>
            {{ __('crm.outcome_donated') }}
           </button>

           <button
            type="button"
            class="outcome-btn outcome-btn--no-answer"
            data-outcome-status="no_answer"
            data-outcome-id="{{ $statusIdByCode->get('no_answer', '') }}"
           >
            <i class="bi bi-telephone-x-fill"></i>
            {{ __('crm.outcome_no_answer') }}
           </button>

           <button
            type="button"
            class="outcome-btn outcome-btn--followup-later"
            data-outcome-status="followup_later"
            data-outcome-id="{{ $lead->lead_status_id }}"
           >
            <i class="bi bi-arrow-repeat"></i>
            {{ __('crm.outcome_followup_later') }}
           </button>

           <button
            type="button"
            class="outcome-btn outcome-btn--not-interested"
            data-outcome-status="not_interested"
            data-outcome-id="{{ $statusIdByCode->get('not_interested', '') }}"
           >
            <i class="bi bi-x-circle-fill"></i>
            {{ __('crm.outcome_not_interested') }}
           </button>
          </div>

          <small>
           {{ __('crm.status_sets_stage_hint') }}
          </small>
         </div>

          @if ($manageableCampaigns->isNotEmpty())
           <div class="field">
            <label for="followupCampaign">{{ __('crm.campaign') }}</label>
            <select class="control" id="followupCampaign" name="campaign_id">
             <option value="">{{ __('crm.no_campaign_change') }}</option>
             @foreach ($manageableCampaigns as $campaignOption)
              <option
               value="{{ $campaignOption->id }}"
               data-user-ids="{{ implode(',', $campaignOption->users->modelKeys()) }}"
               @selected(
                (int) old('campaign_id', $currentCampaign?->id)
                 === (int) $campaignOption->id
               )
              >
               {{ $campaignOption->name }}
              </option>
             @endforeach
            </select>
            <small>{{ __('crm.followup_campaign_hint') }}</small>
           </div>

           <div class="field">
            <label for="followupAssignedUser">{{ __('crm.campaign_responsible_employee') }}</label>
            <select class="control" id="followupAssignedUser" name="assigned_user_id">
             <option value="">{{ __('crm.select_responsible_employee') }}</option>
             @foreach ($campaignAssignees as $assignee)
              <option
               value="{{ $assignee->id }}"
               data-user-id="{{ $assignee->id }}"
               @selected(
                (int) old('assigned_user_id', $lead->assigned_user_id)
                 === (int) $assignee->id
               )
              >
               {{ $assignee->name }}
              </option>
             @endforeach
            </select>
            <small>{{ __('crm.campaign_users_assignment_hint') }}</small>
           </div>
          @endif

          @if (!empty($stageFieldsMap))
           @foreach ($stageFieldsMap as $stageId => $fields)
            @if ($fields->isNotEmpty())
             <section
              class="followup-stage-editor is-hidden dynamic-stage-fields-block"
              id="stageFieldsSection_{{ $stageId }}"
              data-stage-id="{{ $stageId }}"
             >
              <header class="followup-editor-head">
               <h3>
                <i class="bi bi-ui-checks"></i> {{ __('crm.stage_fields_section_title') }}
               </h3>
              </header>
              @include('partials.stage-field-inputs', [
               'fields' => $fields,
               'recordValues' => $currentStageValues ?? [],
               'prefix' => 'stage_fields',
               'scope' => 'followup_stage_' . $stageId,
              ])
             </section>
            @endif
           @endforeach
          @endif

          <section
           class="followup-stage-editor is-hidden"
           id="donorDetailsSection"
          >
           <header class="followup-editor-head">
            <div>
             <h3>{{ __('crm.donor_conversion_title') }}</h3>
             <p>{{ __('crm.donor_conversion_desc') }}</p>
            </div>
           </header>

           <div class="followup-editor-grid">
            @if ($lead->status?->code === 'donor')
             <label class="field full donation-record-toggle" for="recordDonation">
              <span>
               <input
                id="recordDonation"
                type="checkbox"
                name="record_donation"
                value="1"
                @checked(old('record_donation'))
               >
               {{ __('crm.record_new_donation') }}
              </span>
              <small>{{ __('crm.record_new_donation_hint') }}</small>
             </label>
            @else
             <input type="hidden" name="record_donation" value="1">
            @endif

            <div class="field full">
             <label for="donationWay">
              {{ __('crm.donation_way') }}
              <span class="required">*</span>
             </label>
             <select class="control" id="donationWay" name="donation_way">
              <option value="">{{ __('crm.select_donation_way') }}</option>
              <option value="instant" @selected(old('donation_way') === 'instant')>
               {{ __('crm.donation_way_instant') }}
              </option>
              <option value="collection" @selected(old('donation_way') === 'collection')>
               {{ __('crm.donation_way_collection') }}
              </option>
             </select>
            </div>

            <div class="field">
             <label for="donationTypeId">
              {{ __('crm.donation_type') }}
              <span class="required">*</span>
             </label>
             <select
              class="control"
              id="donationTypeId"
              name="donation_type_id"
             >
              <option value="">{{ __('crm.select_donation_type') }}</option>
              @foreach ($donationTypes as $donationType)
               <option
                value="{{ $donationType->id }}"
                @selected(
                 (int) old('donation_type_id', $lead->donation_type_id)
                  === (int) $donationType->id
                )
               >
                {{
                 app()->getLocale() === 'en'
                  && filled($donationType->name_en)
                   ? $donationType->name_en
                   : $donationType->name_ar
                }}
               </option>
              @endforeach
             </select>
            </div>

            <div class="field">
             <label for="donationValue">
              {{ __('crm.donation_value_currency') }}
              <span class="required">*</span>
             </label>
             <input
              class="control"
              id="donationValue"
              type="number"
              name="donation_value"
              value="{{ old('donation_value', $lead->status?->code === 'donor' ? null : $lead->donation_value) }}"
              min="0.01"
              step="0.01"
              inputmode="decimal"
             >
            </div>

            <div class="field full">
             <label for="donationCycle">
              {{ __('crm.donation_cycle_frequency') }}
              <span class="required">*</span>
             </label>
             <select
              class="control"
              id="donationCycle"
              name="donation_cycle"
             >
              <option value="">{{ __('crm.select_donation_cycle') }}</option>
              @foreach ($donationCycles as $donationCycle)
               <option
                value="{{ $donationCycle }}"
                @selected(
                 old('donation_cycle', $lead->donation_cycle) === $donationCycle
                )
               >
                {{ __('crm.donation_cycle_'.$donationCycle) }}
               </option>
              @endforeach
             </select>
            </div>

            <!-- CUSTOM PREFERRED SCHEDULE PANEL (المواعيد المناسبة للعميل) -->
            <div class="field full customer-preferred-schedule-card"
                 id="customerPreferredSchedulePanel"
                 style="{{ old('donation_cycle', $lead->donation_cycle) === 'other' ? '' : 'display:none;' }} margin-top:10px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; transition:opacity 180ms ease, transform 180ms ease;">
             <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
              <strong style="display:flex; align-items:center; gap:6px; font-size:13px; color:var(--dark, #1e293b);">
               <i class="bi bi-calendar2-check" style="color:var(--red, #dc2637); font-size:14px;"></i>
               {{ __('crm.customer_preferred_schedule_title') }}
              </strong>
              <small class="hint" style="margin:0; font-size:11px; color:var(--muted, #64748b);">{{ __('crm.customer_preferred_schedule_hint') }}</small>
             </div>

             <div id="preferredScheduleSlotsContainer" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
              <div class="field" style="margin:0;">
               <label for="preferredDonationDate" style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">
                <i class="bi bi-calendar-event"></i> {{ __('crm.preferred_date') }}
                <span class="required" style="color:var(--red); font-weight:bold;">*</span>
               </label>
               <input
                type="date"
                class="control"
                id="preferredDonationDate"
                name="preferred_donation_date"
                value="{{ old('preferred_donation_date', $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d') : '') }}"
                style="width:100%; font-size:13px;"
                {{ old('donation_cycle', $lead->donation_cycle) === 'other' ? 'required' : '' }}
               >
              </div>

              <div class="field" style="margin:0;">
               <label for="preferredDonationTime" style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">
                <i class="bi bi-clock"></i> {{ __('crm.preferred_time') }}
                <span class="optional" style="font-size:10px; color:var(--muted); font-weight:normal;">({{ __('crm.optional') }})</span>
               </label>
               <input
                type="time"
                class="control"
                id="preferredDonationTime"
                name="preferred_donation_time"
                value="{{ old('preferred_donation_time', $lead->next_follow_up_at ? $lead->next_follow_up_at->format('H:i') : '') }}"
                style="width:100%; font-size:13px;"
               >
              </div>

              <div class="field full" style="grid-column:1 / -1; margin:0; margin-top:2px;">
               <label for="preferredDonationNote" style="display:block; font-size:12px; font-weight:700; margin-bottom:4px;">
                <i class="bi bi-chat-left-text"></i> {{ __('crm.preferred_schedule_note') }}
               </label>
               <input
                type="text"
                class="control"
                id="preferredDonationNote"
                name="preferred_donation_note"
                value="{{ old('preferred_donation_note') }}"
                placeholder="{{ __('crm.preferred_schedule_note_placeholder') }}"
                style="width:100%; font-size:13px;"
               >
              </div>
             </div>
            </div>
            <div class="field full" id="instantDonationMethodField">
             <label for="instantDonationMethodId">
              {{ __('crm.instant_donation_method') }}
              <span class="required">*</span>
             </label>
             <select
              class="control"
              id="instantDonationMethodId"
              name="instant_donation_method_id"
             >
              <option value="">{{ __('crm.select_instant_donation_method') }}</option>
              @foreach ($instantDonationMethods as $instantDonationMethod)
               <option
                value="{{ $instantDonationMethod->id }}"
                data-accounts="{{ json_encode($instantDonationMethod->getAccountsList(), JSON_UNESCAPED_UNICODE) }}"
                @selected((int) old('instant_donation_method_id') === (int) $instantDonationMethod->id)
               >
                {{ $instantDonationMethod->localizedName() }}
               </option>
              @endforeach
             </select>
            </div>

            <div class="field full" id="instantDonationAccountField" style="display:none;">
             <label for="instantDonationAccount">
              <i class="bi bi-wallet2" style="color:var(--red, #dc2637)"></i>
              {{ __('crm.instant_donation_account') }}
             </label>
             <select
              class="control"
              id="instantDonationAccount"
              name="instant_donation_account"
             >
              <option value="">{{ __('crm.select_payment_account') }}</option>
             </select>
             <div class="hint" id="instantDonationAccountHint">{{ __('crm.select_payment_account_hint') }}</div>
            </div>

            <div class="field full" id="donationReceiptField">
             <label for="donationReceipt">
              {{ __('crm.donation_receipt') }}
              <span class="optional">{{ __('crm.optional_suffix') }}</span>
             </label>

             <div class="receipt-dropzone" id="receiptDropzone" tabindex="0" role="region" aria-label="{{ __('crm.donation_receipt') }}">
              <input
               id="donationReceipt"
               type="file"
               name="donation_receipt"
               accept="image/png,image/jpeg,image/webp"
               style="display:none;"
              >

              <div class="receipt-dropzone-prompt" id="receiptDropzonePrompt">
               <div class="receipt-dropzone-icon">
                <i class="bi bi-cloud-arrow-up-fill"></i>
               </div>
               <div class="receipt-dropzone-text">
                <strong>{{ __('crm.paste_screenshot_hint') }}</strong>
                <span>{{ __('crm.donation_receipt_hint') }}</span>
               </div>
               <button type="button" class="btn small soft" id="receiptBrowseBtn" style="margin-top:4px;">
                <i class="bi bi-folder2-open"></i> {{ __('crm.choose_option') }}
               </button>
              </div>

              <div class="receipt-preview-box" id="receiptPreviewBox" style="display:none;">
               <div class="receipt-preview-thumb-wrap">
                <img id="receiptPreviewImg" src="" alt="{{ __('crm.donation_receipt') }}">
               </div>
               <div class="receipt-preview-details">
                <span class="receipt-preview-filename" id="receiptPreviewFilename">receipt.png</span>
                <span class="receipt-preview-filesize" id="receiptPreviewFilesize"></span>
                <span class="receipt-preview-badge"><i class="bi bi-check-circle-fill"></i> {{ __('crm.screenshot_pasted') }}</span>
               </div>
               <button type="button" class="receipt-preview-remove" id="receiptPreviewRemove" title="{{ __('crm.remove_receipt') }}" aria-label="{{ __('crm.remove_receipt') }}">
                <i class="bi bi-trash3-fill"></i>
               </button>
              </div>
             </div>
            </div>

            <div class="field collection-input">
             <label for="collectionDueAt">
              {{ __('crm.collection_due_at') }}
              <span class="required">*</span>
             </label>
             <input
              class="control"
              id="collectionDueAt"
              type="datetime-local"
              name="collection_due_at"
              value="{{ old('collection_due_at') }}"
             >
            </div>

            <div class="field collection-input">
             <label for="collectionAddress">
              {{ __('crm.collection_address') }}
              <span class="required">*</span>
             </label>
             <input
              class="control"
              id="collectionAddress"
              type="text"
              name="collection_address"
              value="{{ old('collection_address', $lead->address) }}"
              maxlength="255"
             >
            </div>

            @if ($canAssignCollections)
             <div class="field full collection-input">
              <label for="assignedCollectorUserId">
               {{ __('crm.assigned_collector') }}
               <span class="optional">{{ __('crm.optional_suffix') }}</span>
              </label>
              <select
               class="control"
               id="assignedCollectorUserId"
               name="assigned_collector_user_id"
              >
               <option value="">{{ __('crm.collection_manager_queue') }}</option>
               @foreach ($collectors as $collector)
                <option
                 value="{{ $collector->id }}"
                 @selected((int) old('assigned_collector_user_id') === (int) $collector->id)
                >
                 {{ $collector->name }}
                </option>
               @endforeach
              </select>
             </div>
            @endif

            <div class="field full collection-input">
             <label for="collectionNotes">
              {{ __('crm.collection_notes') }}
              <span class="optional">{{ __('crm.optional_suffix') }}</span>
             </label>
             <textarea
              class="control"
              id="collectionNotes"
              name="collection_notes"
              rows="3"
              maxlength="5000"
             >{{ old('collection_notes') }}</textarea>
            </div>

            <div
             class="donation-schedule-note"
             id="donationScheduleNote"
             aria-live="polite"
            ></div>
           </div>
          </section>

          <!-- FOLLOW-UP EDITABLE STAGE DATA START -->

         <section
          class="followup-stage-editor is-hidden"
          id="businessDetailsSection"
         >
          <header class="followup-editor-head">
           <div>
            <h3>{{ __('crm.customer_donor_information') }}</h3>
           <p>
            {{ __('crm.edit_and_save_followup_hint') }}
           </p>
           </div>
          </header>

          <div class="followup-editor-grid">
           <div class="field">
            <label for="governorate">
             {{ __('crm.governorate') }}
            </label>

            <input
             class="control"
             id="governorate"
             type="text"
             name="governorate"
             value="{{ old(
              'governorate',
              $lead->governorate
             ) }}"
             maxlength="100"
            >
           </div>

           <div class="field full">
            <label for="address">
             {{ __('crm.address') }}
            </label>

            <input
             class="control"
             id="address"
             type="text"
             name="address"
             value="{{ old(
              'address',
              $lead->address
             ) }}"
             maxlength="255"
            >
           </div>

           @if ($isCompany)
            <div class="field">
             <label for="companyName">
              {{ __('crm.company_name') }}
             </label>

             <input
              class="control"
              id="companyName"
              type="text"
              name="company_name"
              value="{{ old(
               'company_name',
               $lead->company_name
              ) }}"
              maxlength="150"
             >
            </div>

            <div class="field">
             <label for="activity">
              {{ __('crm.activity') }}
             </label>

             <input
              class="control"
              id="activity"
              type="text"
              name="activity"
              value="{{ old(
               'activity',
               $lead->activity
              ) }}"
              maxlength="150"
             >
            </div>

            <div class="field">
             <label for="usersCount">
              {{ __('crm.user_count') }}
             </label>

             <input
              class="control"
              id="usersCount"
              type="number"
              name="users_count"
              value="{{ old(
               'users_count',
               $lead->users_count
              ) }}"
              min="0"
              max="1000000"
             >
            </div>

            <div class="field">
             <label for="branchesCount">
              {{ __('crm.branch_count') }}
             </label>

             <input
              class="control"
              id="branchesCount"
              type="number"
              name="branches_count"
              value="{{ old(
               'branches_count',
               $lead->branches_count
              ) }}"
              min="0"
              max="1000000"
             >
            </div>

            <div class="field">
             <label for="jobTitle">
              {{ __('crm.job_title') }}
             </label>

             <input
              class="control"
              id="jobTitle"
              type="text"
              name="job_title"
              value="{{ old(
               'job_title',
               $lead->job_title
              ) }}"
              maxlength="150"
             >
            </div>
           @else
            @if ($lead->company_name)
             <input type="hidden" name="company_name" value="{{ $lead->company_name }}">
            @endif
            @if ($lead->activity)
             <input type="hidden" name="activity" value="{{ $lead->activity }}">
            @endif
            @if ($lead->users_count !== null)
             <input type="hidden" name="users_count" value="{{ $lead->users_count }}">
            @endif
            @if ($lead->branches_count !== null)
             <input type="hidden" name="branches_count" value="{{ $lead->branches_count }}">
            @endif
            @if ($lead->job_title)
             <input type="hidden" name="job_title" value="{{ $lead->job_title }}">
            @endif
           @endif
          </div>
         </section>

         <section
          class="followup-stage-editor is-hidden"
          id="notInterestedSection"
         >
          <header class="followup-editor-head">
           <div>
            <h3>{{ __('crm.not_interested_reason') }}</h3>
           <p>
            {{ __('crm.not_interested_reason_hint') }}
           </p>
           </div>
          </header>

          <div class="followup-editor-grid">
           <div class="field full">
            <label for="disinterestReason">
             {{ __('crm.not_interested_reason') }}
             <span class="required">*</span>
            </label>


            <div class="chip-group" id="rejectionReasonChips">
             <span class="chip-group-label">{{ __('crm.rejection_reasons_label') }}</span>
             <button type="button" class="chip chip--reason" data-reason="{{ __('crm.reason_financial') }}">{{ __('crm.reason_financial') }}</button>
             <button type="button" class="chip chip--reason" data-reason="{{ __('crm.reason_donated_elsewhere') }}">{{ __('crm.reason_donated_elsewhere') }}</button>
             <button type="button" class="chip chip--reason" data-reason="{{ __('crm.reason_amount_high') }}">{{ __('crm.reason_amount_high') }}</button>
             <button type="button" class="chip chip--reason" data-reason="{{ __('crm.reason_not_convinced') }}">{{ __('crm.reason_not_convinced') }}</button>
             <button type="button" class="chip chip--reason" data-reason="__other__">{{ __('crm.reason_other') }}</button>
            </div>

            <textarea
             class="control"
             id="disinterestReason"
             name="disinterest_reason"
             maxlength="5000"
            >{{ old(
             'disinterest_reason',
             $lead->disinterest_reason
            ) }}</textarea>
           </div>
          </div>
         </section>

         <!-- FOLLOW-UP EDITABLE STAGE DATA END -->


         <div class="field">
          <label for="communication_type">
           {{ __('crm.followup_type') }}
          </label>

          <select
           class="control"
           id="communication_type"
           name="communication_type"
           required
          >
           @foreach (
            $communicationTypes
            as $typeCode => $typeLabel
           )
            <option
             value="{{ $typeCode }}"
             @selected(
              $selectedCommunicationType
              === $typeCode
             )
            >
             {{ $typeLabel }}
            </option>
           @endforeach
          </select>
         </div>

         <div class="field">
          <label>{{ __('crm.employee_name_label') }}</label>

          <div class="employee-display">
           {{ $currentEmployee }}
          </div>

          <small>
           {{ __('crm.employee_name_auto_hint') }}
          </small>
         </div>

         <div class="field">
          <label for="next_follow_up_at">
           {{ __('crm.next_followup_date') }}
          </label>

          <input
           class="control"
           id="next_follow_up_at"
           name="next_follow_up_at"
           type="datetime-local"
           value="{{ old(
            'next_follow_up_at',
            $lead->next_follow_up_at
             ?->format('Y-m-d\TH:i')
           ) }}"
          >

          <div class="chip-group" id="callbackPresetChips">
           <span class="chip-group-label">{{ __('crm.callback_presets_label') }}</span>
           <button type="button" class="chip chip--callback" data-callback="2h">{{ __('crm.callback_2h') }}</button>
           <button type="button" class="chip chip--callback" data-callback="tomorrow_am">{{ __('crm.callback_tomorrow_am') }}</button>
           <button type="button" class="chip chip--callback" data-callback="tomorrow_pm">{{ __('crm.callback_tomorrow_pm') }}</button>
           <button type="button" class="chip chip--callback" data-callback="3days">{{ __('crm.callback_3days') }}</button>
          </div>

          <small>
           {{ __('crm.leave_empty_if_no_followup') }}
          </small>
         </div>

         <div class="field full">
          <label for="outcome">
           {{ __('crm.followup_notes') }}
          </label>

          <textarea
           class="control"
           id="outcome"
           name="outcome"
           maxlength="5000"
           
           placeholder="{{ __('crm.followup_notes_placeholder') }}"
          >{{ old('outcome') }}</textarea>
         </div>
        </div>

        <div class="submit-row">
         <button
          class="submit-button"
          type="submit"
         >
          {{ __('crm.save_followup') }}
         </button>
        </div>
       </form>
      </div>
     </section>
     @endcan

     <section class="panel latest-followups-panel">
      <header class="panel-head">
       <h3>{{ __('crm.latest_followups') }}</h3>
       <p>
        {{ __('crm.latest_20_followups') }}
       </p>
      </header>

      <div class="panel-body">
       <div class="timeline">
        @forelse ($followups as $followup)
         @php
          $timelineStageColor = trim(
           (string) (
            $followup->toStatus?->stage?->color
            ?? ''
           )
          );

          if (
           !preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $timelineStageColor
           )
          ) {
           $timelineStageColor = '#64748b';
          }
         @endphp

         <article
          class="timeline-item"
          style="--timeline-stage-color:{{ $timelineStageColor }}"
         >
          <div class="timeline-top">
           <span class="timeline-employee">
            {{ $followup->user?->name ?? $followup->employee_name }}
           </span>

           <time class="timeline-date">
            {{
             $followup->followed_up_at
              ?->format('d/m/Y - h:i A')
             ?? '----'
            }}
           </time>
          </div>

          <div class="timeline-badges">
           <span
            class="timeline-badge channel"
           >
            {{
             $communicationTypes[
              $followup->communication_type
             ]
             ?? $followup->communication_type
            }}
           </span>

           <span class="timeline-badge timeline-stage-badge">
            {{
             $followup->toStatus?->stage
              ?->name_ar
             ?? '----'
            }}
            —
            {{
             $followup->toStatus?->name_ar
             ?? '----'
            }}
           </span>
          </div>

          <p class="timeline-outcome">{{
           $followup->outcome
          }}</p>

           @if (!empty($followup->field_changes))
            <div class="timeline-field-changes">
             <strong>
              {{ __('crm.modifications_by_employee') }}
             </strong>

             <ul class="timeline-change-list">
              @foreach (
               $followup->field_changes
               as $change
              )
               <li class="timeline-change-item">
                <span class="timeline-change-label">
                 {{ $change['label'] ?? __('crm.edit') }}
                </span>

                <div class="timeline-change-values">
                 <span class="timeline-change-old">
                  {{ $change['old'] ?? '----' }}
                 </span>

                 <span aria-hidden="true">
                  {{ app()->getLocale() === 'ar' ? '←' : '→' }}
                 </span>

                 <span class="timeline-change-new">
                  {{ $change['new'] ?? '----' }}
                 </span>
                </div>
               </li>
              @endforeach
             </ul>
            </div>
           @endif

          <div class="timeline-next">
           {{ __('crm.next_followup_at_label') }}
           {{
            $followup->next_follow_up_at
             ?->format('d/m/Y - h:i A')
            ?? '----'
           }}
          </div>
         </article>
        @empty
         <div class="empty-state">
          {{ __('crm.no_followups_for_lead_yet') }}
         </div>
        @endforelse
       </div>
      </div>
     </section>
    </div>
   </div>
  </main>
 </div>

 <script>
 (() => {
  const body = document.body;

  const menuButton =
   document.getElementById('crmMenuButton');

  const overlay =
   document.getElementById(
    'crmSidebarOverlay'
   );

  const setSidebarOpen = (open) => {
   body.classList.toggle(
    'crm-side-open',
    open
   );

   menuButton?.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );
  };

  menuButton?.addEventListener(
   'click',
   () => {
    setSidebarOpen(
     !body.classList.contains(
      'crm-side-open'
     )
    );
   }
  );

  overlay?.addEventListener(
   'click',
   () => setSidebarOpen(false)
  );

  document
   .querySelectorAll('[data-crm-menu]')
   .forEach((toggle) => {
    toggle.addEventListener(
     'click',
     () => {
      const menuId =
       toggle.getAttribute(
        'data-crm-menu'
       );

      const menu = menuId
       ? document.getElementById(menuId)
       : null;

      if (!menu) {
       return;
      }

      const willOpen =
       toggle.getAttribute(
        'aria-expanded'
       ) !== 'true';

      toggle.setAttribute(
       'aria-expanded',
       willOpen ? 'true' : 'false'
      );

      menu.classList.toggle(
       'open',
       willOpen
      );
     }
    );
   });

  document.addEventListener(
   'keydown',
   (event) => {
    if (event.key === 'Escape') {
     setSidebarOpen(false);
    }
   }
  );

  const followupStatusSelect =
   document.getElementById(
    'lead_status_id'
   );

  const followupCampaignSelect =
   document.getElementById('followupCampaign');

  const followupAssignedUserSelect =
   document.getElementById('followupAssignedUser');

  const updateFollowupCampaignUsers = () => {
   if (!followupCampaignSelect || !followupAssignedUserSelect) return;

   const campaignOption = followupCampaignSelect.selectedOptions[0];
   const userIds = new Set(
    (campaignOption?.dataset?.userIds || '').split(',').filter(Boolean)
   );
   const hasCampaign = followupCampaignSelect.value !== '';

   [...followupAssignedUserSelect.options].forEach((option) => {
    if (option.value === '') {
     option.hidden = false;
     option.disabled = hasCampaign;
     return;
    }

    const allowed = hasCampaign && userIds.has(option.dataset.userId || option.value);
    option.hidden = !allowed;
    option.disabled = !allowed;
   });

   const selected = followupAssignedUserSelect.selectedOptions[0];
   if (!hasCampaign) {
    followupAssignedUserSelect.value = '';
    followupAssignedUserSelect.required = false;
    return;
   }

   followupAssignedUserSelect.required = true;

   if (!selected || selected.disabled) {
    const firstAllowed = [...followupAssignedUserSelect.options]
     .find((option) => option.value !== '' && !option.disabled);
    followupAssignedUserSelect.value = firstAllowed?.value || '';
   }
  };

  const followupBusinessSection =
   document.getElementById(
    'businessDetailsSection'
   );

  const followupDonorSection =
   document.getElementById(
    'donorDetailsSection'
   );

  const followupDonationType =
   document.getElementById(
    'donationTypeId'
   );

  const followupDonationWay =
   document.getElementById('donationWay');

  const followupInstantDonationMethod =
   document.getElementById('instantDonationMethodId');

  const followupDonationReceiptField =
   document.getElementById('donationReceiptField');

  const followupInstantDonationMethodField =
   document.getElementById('instantDonationMethodField');


  const followupInstantDonationAccount =
   document.getElementById('instantDonationAccount');

  const followupInstantDonationAccountField =
   document.getElementById('instantDonationAccountField');
  const followupCollectionFields =
   [...document.querySelectorAll('.collection-input')];

  const followupDonationValue =
   document.getElementById(
    'donationValue'
   );

  const followupDonationCycle =
   document.getElementById(
    'donationCycle'
   );

  const followupDonationScheduleNote =
   document.getElementById(
    'donationScheduleNote'
   );

  const followupRecordDonation =
   document.getElementById(
    'recordDonation'
   );

  const followupDonationReceipt =
   document.getElementById(
    'donationReceipt'
   );

  const currentLeadIsDonor =
   @json($lead->status?->code === 'donor');

  const followupNotInterestedSection =
   document.getElementById(
    'notInterestedSection'
   );

  const followupDisinterestReason =
   document.getElementById(
    'disinterestReason'
   );

  const followupBusinessStatuses = [
   'new',
   'no_answer',
   'not_interested'
  ];

  const donationScheduleMessages = {
   '': @json(__('crm.donor_followup_select_cycle')),
   one_time: @json(__('crm.donor_followup_one_time')),
   monthly: @json(__('crm.donor_followup_monthly')),
   quarterly: @json(__('crm.donor_followup_quarterly')),
   semi_annual: @json(__('crm.donor_followup_semi_annual')),
   annual: @json(__('crm.donor_followup_annual')),
   other: @json(__('crm.donor_followup_other'))
  };

  const customerPreferredSchedulePanel =
   document.getElementById(
    'customerPreferredSchedulePanel'
   );

  const preferredDonationDateInput =
   document.getElementById(
    'preferredDonationDate'
   );

  const toggleCustomerPreferredSchedule = () => {
   if (!customerPreferredSchedulePanel || !followupDonationCycle) return;
   const isOther = followupDonationCycle.value === 'other';

   if (isOther) {
    if (customerPreferredSchedulePanel.style.display === 'none') {
     customerPreferredSchedulePanel.style.display = 'block';
     customerPreferredSchedulePanel.style.opacity = '0';
     customerPreferredSchedulePanel.style.transform = 'translateY(-4px)';
     requestAnimationFrame(() => {
      customerPreferredSchedulePanel.style.opacity = '1';
      customerPreferredSchedulePanel.style.transform = 'translateY(0)';
     });
    }
    if (preferredDonationDateInput) {
     preferredDonationDateInput.setAttribute('required', 'required');
     setTimeout(() => preferredDonationDateInput.focus(), 50);
    }
   } else {
    customerPreferredSchedulePanel.style.display = 'none';
    if (preferredDonationDateInput) {
     preferredDonationDateInput.removeAttribute('required');
    }
   }
  };

  const updateDonationScheduleNote = () => {
   if (!followupDonationScheduleNote) return;

   followupDonationScheduleNote.textContent =
    donationScheduleMessages[
     followupDonationCycle?.value || ''
    ] || donationScheduleMessages[''];
  };
  const followupSelectedStatusCode =
   () => {
    const option =
     followupStatusSelect
      ?.selectedOptions?.[0];

    return (
     option?.dataset?.statusCode
     || ''
    );
   };

  const updateFollowupStageEditor =
   () => {
    const code =
     followupSelectedStatusCode();

    const businessActive =
     followupBusinessStatuses.includes(
      code
     );

    const notInterestedActive =
     code === 'not_interested';

    const donorActive =
     code === 'donor';

    const donationActive = donorActive
     && (
      !currentLeadIsDonor
      || followupRecordDonation?.checked
     );

    const instantDonationActive = donationActive
     && followupDonationWay?.value === 'instant';

    const collectionActive = donationActive
     && followupDonationWay?.value === 'collection';

    followupBusinessSection
     ?.classList.toggle(
      'is-hidden',
      !businessActive
     );

    followupNotInterestedSection
     ?.classList.toggle(
      'is-hidden',
      !notInterestedActive
     );

    followupDonorSection
     ?.classList.toggle(
      'is-hidden',
      !donorActive
     );

    [
     followupDonationType,
     followupDonationValue,
     followupDonationCycle,
     followupDonationWay
    ].forEach((field) => {
     if (!field) return;

     field.disabled = !donationActive;
     field.required = donationActive
      && field !== followupDonationReceipt;
     field.setAttribute(
      'aria-required',
      field.required ? 'true' : 'false'
     );
     const container = field.closest('.field');
     if (container) container.hidden = !donationActive;
    });

    if (followupInstantDonationMethod) {
     followupInstantDonationMethod.disabled = !instantDonationActive;
     followupInstantDonationMethod.required = instantDonationActive;
     followupInstantDonationMethod.setAttribute(
      'aria-required',
      instantDonationActive ? 'true' : 'false'
     );
    }

    if (followupDonationReceipt) {
     followupDonationReceipt.disabled = !instantDonationActive;
     followupDonationReceipt.required = false;
    }

    if (followupInstantDonationMethodField) {
     followupInstantDonationMethodField.hidden = !instantDonationActive;
    }

    if (followupDonationReceiptField) {
     followupDonationReceiptField.hidden = !instantDonationActive;
    }

    updateInstantDonationAccounts();

    followupCollectionFields.forEach((container) => {
     container.hidden = !collectionActive;
     container.querySelectorAll('input, select, textarea').forEach((field) => {
      field.disabled = !collectionActive;
      field.required = collectionActive
       && ['collectionDueAt', 'collectionAddress'].includes(field.id);
      field.setAttribute('aria-required', field.required ? 'true' : 'false');
     });
    });

    if (followupDonationScheduleNote) {
     followupDonationScheduleNote.hidden = !instantDonationActive;
    }

    // Dynamic Pipeline Stage Fields Blocks
    const selectedOpt = followupStatusSelect?.selectedOptions?.[0];
    const currentStageId = selectedOpt?.dataset?.stageId || '';

    document.querySelectorAll('.dynamic-stage-fields-block').forEach((block) => {
     const blockStageId = block.getAttribute('data-stage-id');
     const isActiveStage = String(blockStageId) === String(currentStageId);
     block.classList.toggle('is-hidden', !isActiveStage);
     block.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((inp) => {
      inp.disabled = !isActiveStage;
     });
    });

    if (followupDisinterestReason) {
     followupDisinterestReason.required =
      notInterestedActive;
    }

    updateDonationScheduleNote();
   };

  const updateInstantDonationAccounts = (preselectedVal = null) => {
   if (!followupInstantDonationAccount || !followupInstantDonationAccountField) return;

   const selectedOpt = followupInstantDonationMethod?.selectedOptions?.[0];
   let accounts = [];
   if (selectedOpt && selectedOpt.dataset.accounts) {
    try {
     accounts = JSON.parse(selectedOpt.dataset.accounts);
    } catch (e) {
     accounts = [];
    }
   }

   const isInstantActive = !followupInstantDonationMethodField?.hidden && !followupInstantDonationMethod?.disabled;

   if (isInstantActive && Array.isArray(accounts) && accounts.length > 0) {
    const currentSelected = preselectedVal || followupInstantDonationAccount.value || @json(old('instant_donation_account', ''));
    followupInstantDonationAccount.replaceChildren();

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = @json(__('crm.select_payment_account'));
    followupInstantDonationAccount.appendChild(defaultOpt);

    accounts.forEach(acc => {
     const opt = document.createElement('option');
     opt.value = acc;
     opt.textContent = acc;
     if (currentSelected === acc) {
      opt.selected = true;
     }
     followupInstantDonationAccount.appendChild(opt);
    });

    followupInstantDonationAccount.disabled = false;
    followupInstantDonationAccountField.style.display = 'block';
    followupInstantDonationAccountField.hidden = false;
   } else {
    followupInstantDonationAccount.disabled = true;
    followupInstantDonationAccountField.style.display = 'none';
    followupInstantDonationAccountField.hidden = true;
   }
  };

  followupInstantDonationMethod?.addEventListener('change', () => {
   updateInstantDonationAccounts();
  });

  followupStatusSelect
   ?.addEventListener(
    'change',
    updateFollowupStageEditor
   );

  followupCampaignSelect?.addEventListener(
   'change',
   updateFollowupCampaignUsers
  );

  followupDonationCycle
   ?.addEventListener(
    'change',
    () => {
     updateDonationScheduleNote();
     toggleCustomerPreferredSchedule();
    }
   );
  followupDonationWay
   ?.addEventListener(
    'change',
    updateFollowupStageEditor
   );

  followupRecordDonation
   ?.addEventListener(
    'change',
    updateFollowupStageEditor
   );

  updateFollowupCampaignUsers();
  toggleCustomerPreferredSchedule();
  updateFollowupStageEditor();

 })();
 </script>

<!-- OUTCOME BAR + CHIPS JS -->
<script>
(() => {
 const statusSelect = document.getElementById('lead_status_id');
 const outcomeBar = document.getElementById('outcomeBar');
 const nextFollowUpInput = document.getElementById('next_follow_up_at');
 const disinterestTextarea = document.getElementById('disinterestReason');
 const callbackChips = document.getElementById('callbackPresetChips');
 const rejectionChips = document.getElementById('rejectionReasonChips');

 if (!outcomeBar || !statusSelect) return;

 const buttons = outcomeBar.querySelectorAll('.outcome-btn');
 const currentLeadStatusId = @json((int) $lead->lead_status_id);
 const hasExplicitOutcome = @json(
  request()->filled('target_status_id')
  || request()->filled('target_status_code')
  || request()->boolean('make_donation')
  || request()->query('donation') === '1'
 );

 // Activate a button and set the hidden select
 const activateOutcome = (btn) => {
  if (btn.disabled) return;

  buttons.forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const statusCode = btn.dataset.outcomeStatus;
  const targetId = btn.dataset.outcomeId;

  if (statusCode === 'followup_later') {
   // Keep current status - set select to current lead status
   statusSelect.value = String(currentLeadStatusId);
  } else {
   statusSelect.value = String(targetId);
  }

  // Trigger change event for existing JS
  statusSelect.dispatchEvent(new Event('change', { bubbles: true }));
 };

 // Preselect an explicit destination. Generic call/follow-up starts in
 // "follow up later" so the existing stage remains unchanged until the
 // agent deliberately selects another outcome.
 const preActivate = () => {
  buttons.forEach(btn => btn.classList.remove('active'));

  if (!hasExplicitOutcome) {
   outcomeBar
    .querySelector('[data-outcome-status="followup_later"]')
    ?.classList.add('active');
   return;
  }

  const currentVal = statusSelect.value;
  buttons.forEach(btn => {
   if (
    btn.dataset.outcomeStatus !== 'followup_later'
    && String(btn.dataset.outcomeId) === String(currentVal)
   ) {
    btn.classList.add('active');
   }
  });
 };

 buttons.forEach(btn => {
  btn.addEventListener('click', () => activateOutcome(btn));
 });

 preActivate();

 // ── Callback Preset Chips ──
 if (callbackChips && nextFollowUpInput) {
  callbackChips.querySelectorAll('[data-callback]').forEach(chip => {
   chip.addEventListener('click', () => {
    const now = new Date();
    let target;

    switch (chip.dataset.callback) {
     case '2h':
      target = new Date(now.getTime() + 2 * 60 * 60 * 1000);
      break;
     case 'tomorrow_am':
      target = new Date(now);
      target.setDate(target.getDate() + 1);
      target.setHours(10, 0, 0, 0);
      break;
     case 'tomorrow_pm':
      target = new Date(now);
      target.setDate(target.getDate() + 1);
      target.setHours(17, 0, 0, 0);
      break;
     case '3days':
      target = new Date(now.getTime() + 3 * 24 * 60 * 60 * 1000);
      target.setHours(10, 0, 0, 0);
      break;
     default:
      return;
    }

    // Format as YYYY-MM-DDTHH:MM for datetime-local
    const y = target.getFullYear();
    const m = String(target.getMonth() + 1).padStart(2, '0');
    const d = String(target.getDate()).padStart(2, '0');
    const hh = String(target.getHours()).padStart(2, '0');
    const mm = String(target.getMinutes()).padStart(2, '0');
    nextFollowUpInput.value = `${y}-${m}-${d}T${hh}:${mm}`;
    nextFollowUpInput.dispatchEvent(new Event('change', { bubbles: true }));
   });
  });
 }

 // ── Rejection Reason Chips ──
 if (rejectionChips && disinterestTextarea) {
  rejectionChips.querySelectorAll('[data-reason]').forEach(chip => {
   chip.addEventListener('click', () => {
    const reason = chip.dataset.reason;
    if (reason === '__other__') {
     disinterestTextarea.value = '';
     disinterestTextarea.focus();
    } else {
     disinterestTextarea.value = reason;
    }
   });
  });
 }
})();
</script>

@if (
 request()->boolean(
  'kanban_popup'
 )
 && request()->boolean(
  'saved'
 )
)
<script>
window.parent.postMessage(
 {
  type:
   'crm-kanban-followup-saved',
  leadId: {{ (int) $lead->id }},
  context: @json(request('context', 'kanban')),
  @if (session('next_lead_url'))
  nextLeadUrl:
   @json(session('next_lead_url')),
  nextLeadId:
   @json(session('next_lead_id')),
  @endif
 },
 window.location.origin
);
</script>
@endif


<!-- CRM FOLLOWUP CONDITIONAL DATE UI V2 START -->
@php
 $__crmNotInterestedStatus =
  $statusGroups
   ->flatten(1)
   ->firstWhere(
    'code',
    'not_interested'
   );

 $__crmNotInterestedStatusId =
  (int) (
   $__crmNotInterestedStatus?->id
   ?? 0
  );
@endphp

<script>
(() => {
 const statusSelect =
  document.getElementById(
   'lead_status_id'
  );

 const outcomeField =
  document.getElementById(
   'outcome'
  );

 const nextField =
  document.getElementById(
   'next_follow_up_at'
  );

 const notInterestedStatusId =
  Number(
   @json(
    $__crmNotInterestedStatusId
   )
  );

 if (outcomeField) {
  outcomeField.required = false;

  outcomeField.removeAttribute(
   'required'
  );

  outcomeField.setAttribute(
   'aria-required',
   'false'
  );

  const outcomeContainer =
   outcomeField.closest(
    '.field'
   );

  const outcomeLabel =
   outcomeContainer?.querySelector(
    'label'
   );

  if (
   outcomeLabel
   && !outcomeLabel.querySelector(
    '[data-outcome-optional]'
   )
  ) {
   const optional =
    document.createElement(
     'span'
    );

   optional.dataset
    .outcomeOptional = '1';

   optional.textContent =
    @json(__('crm.optional_suffix'));
   outcomeLabel.appendChild(
    optional
   );
  }
 }

 if (
  !nextField
  || !notInterestedStatusId
 ) {
  return;
 }

 const getStatusId = () => {
  if (
   statusSelect
   && statusSelect.value
  ) {
   return Number(
    statusSelect.value
   );
  }

  const hiddenStatus =
   document.querySelector(
    'input[type="hidden"]'
    + '[name="lead_status_id"]'
   );

  return Number(
   hiddenStatus?.value
   || 0
  );
 };

 const syncNextDateRule = () => {
  const isNotInterested =
   getStatusId()
   === notInterestedStatusId;

  nextField.required =
   !isNotInterested;

  nextField.setAttribute(
   'aria-required',
   isNotInterested
    ? 'false'
    : 'true'
  );

  if (isNotInterested) {
   nextField.removeAttribute(
    'required'
   );
  } else {
   nextField.setAttribute(
    'required',
    'required'
   );
  }

  const container =
   nextField.closest(
    '.field'
   );

  if (!container) {
   return;
  }

  let note =
   container.querySelector(
    '[data-next-date-rule-note]'
   );

  if (!note) {
   note =
    document.createElement(
     'small'
    );

   note.dataset
    .nextDateRuleNote = '1';

   container.appendChild(
    note
   );
  }

  note.textContent =
   isNotInterested
    ? @json(__('crm.next_followup_optional_not_interested'))
    : @json(__('crm.next_followup_mandatory_notice'));
 };

 statusSelect?.addEventListener(
  'change',
  syncNextDateRule
 );

 syncNextDateRule();
})();
</script>
<!-- CRM FOLLOWUP CONDITIONAL DATE UI V2 END -->


<!-- CRM NEW EXECUTION NO FOLLOWUP V7 START -->
<script>
(() => {
 const statusSelect =
  document.getElementById(
   'lead_status_id'
  );

 const nextField =
  document.getElementById(
   'next_follow_up_at'
  );

 if (
  !statusSelect
  || !nextField
 ) {
  return;
 }

 const container =
  nextField.closest(
   '.field'
  );

 const excluded =
  new Set([
   'new',
   'not_interested',
   'donor'
  ]);

 const getStatusCode = () => {
  const option =
   statusSelect.options[
    statusSelect.selectedIndex
   ];

  return (
   option?.dataset
    ?.statusCode
   || ''
  );
 };

 const sync = () => {
  const noFutureDate =
   excluded.has(
    getStatusCode()
   );

  if (noFutureDate) {
   nextField.value = '';

   nextField.required =
    false;

   nextField.disabled =
    true;

   nextField.removeAttribute(
    'required'
   );

   nextField.setAttribute(
    'disabled',
    'disabled'
   );

   nextField.setAttribute(
    'aria-required',
    'false'
   );

   if (container) {
    container.style.display =
     'none';
   }

   return;
  }

  nextField.disabled =
   false;

  nextField.required =
   true;

  nextField.removeAttribute(
   'disabled'
  );

  nextField.setAttribute(
   'required',
   'required'
  );

  nextField.setAttribute(
   'aria-required',
   'true'
  );

  if (container) {
   container.style.display =
    '';
  }
 };

 /*
  * Existing V2 listener was registered
  * earlier. This V7 listener runs after it
  * and therefore applies the final rule.
  */
 statusSelect.addEventListener(
  'change',
  sync
 );

 sync();
})();
</script>
<!-- CRM NEW EXECUTION NO FOLLOWUP V7 END -->

<!-- CRM DONATION RECEIPT DROPZONE & CLIPBOARD PASTE JS START -->
<script>
(() => {
 const fileInput = document.getElementById('donationReceipt');
 const dropzone = document.getElementById('receiptDropzone');
 const promptBox = document.getElementById('receiptDropzonePrompt');
 const previewBox = document.getElementById('receiptPreviewBox');
 const previewImg = document.getElementById('receiptPreviewImg');
 const filenameEl = document.getElementById('receiptPreviewFilename');
 const filesizeEl = document.getElementById('receiptPreviewFilesize');
 const removeBtn = document.getElementById('receiptPreviewRemove');
 const browseBtn = document.getElementById('receiptBrowseBtn');

 if (!fileInput || !dropzone) return;

 const formatBytes = (bytes) => {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
 };

 const showPreview = (file) => {
  if (!file || !file.type.startsWith('image/')) return;
  const reader = new FileReader();
  reader.onload = (e) => {
   if (previewImg) previewImg.src = e.target.result;
   if (filenameEl) filenameEl.textContent = file.name || 'screenshot.png';
   if (filesizeEl) filesizeEl.textContent = formatBytes(file.size);
   if (promptBox) promptBox.style.display = 'none';
   if (previewBox) previewBox.style.display = 'flex';
  };
  reader.readAsDataURL(file);
 };

 const clearFile = () => {
  fileInput.value = '';
  if (previewImg) previewImg.src = '';
  if (previewBox) previewBox.style.display = 'none';
  if (promptBox) promptBox.style.display = 'flex';
 };

 const setFile = (file) => {
  const dt = new DataTransfer();
  dt.items.add(file);
  fileInput.files = dt.files;
  showPreview(file);
 };

 fileInput.addEventListener('change', () => {
  if (fileInput.files && fileInput.files[0]) {
   showPreview(fileInput.files[0]);
  } else {
   clearFile();
  }
 });

 if (browseBtn) {
  browseBtn.addEventListener('click', (e) => {
   e.stopPropagation();
   fileInput.click();
  });
 }

 dropzone.addEventListener('click', (e) => {
  if (e.target.closest('#receiptPreviewRemove')) return;
  if (!previewBox || previewBox.style.display === 'none') {
   fileInput.click();
  }
 });

 if (removeBtn) {
  removeBtn.addEventListener('click', (e) => {
   e.stopPropagation();
   clearFile();
  });
 }

 dropzone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropzone.classList.add('is-dragover');
 });

 ['dragleave', 'dragend'].forEach(evt => {
  dropzone.addEventListener(evt, () => dropzone.classList.remove('is-dragover'));
 });

 dropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropzone.classList.remove('is-dragover');
  if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
   const file = e.dataTransfer.files[0];
   if (file.type.startsWith('image/')) {
    setFile(file);
   }
  }
 });

 window.addEventListener('paste', (e) => {
  const items = e.clipboardData?.items;
  if (!items) return;

  for (let i = 0; i < items.length; i++) {
   if (items[i].type.indexOf('image') !== -1) {
    const file = items[i].getAsFile();
    if (file) {
     const ext = file.type.split('/')[1] || 'png';
     const namedFile = new File([file], `screenshot_${Date.now()}.${ext}`, { type: file.type });
     setFile(namedFile);
     dropzone.classList.add('is-dragover');
     window.setTimeout(() => dropzone.classList.remove('is-dragover'), 300);
     break;
    }
   }
  }
 });

 // Scroll to donation section if make_donation is set
 const urlParams = new URLSearchParams(window.location.search);
 if (urlParams.get('make_donation') === '1' || urlParams.get('donation') === '1') {
  window.setTimeout(() => {
   const donorSec = document.getElementById('donorDetailsSection');
   if (donorSec) {
    donorSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const donationValInput = document.getElementById('donationValue');
    donationValInput?.focus();
   }
  }, 300);
 }
})();
</script>
<!-- CRM DONATION RECEIPT DROPZONE & CLIPBOARD PASTE JS END -->

@if (session('success'))
<!-- CRM AUTO-ADVANCE NEXT LEAD START -->
<style>
 .crm-auto-advance-bar{
  position:fixed;
  top:0;
  left:0;
  right:0;
  z-index:9999;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:1rem;
  padding:.75rem 1.5rem;
  background:linear-gradient(135deg,#059669,#10b981);
  color:#fff;
  font-size:.95rem;
  box-shadow:0 4px 20px rgba(5,150,105,.35);
  animation:crm-aab-slideDown .4s ease-out;
 }

 @keyframes crm-aab-slideDown{
  from{transform:translateY(-100%);opacity:0}
  to{transform:translateY(0);opacity:1}
 }

 .crm-auto-advance-bar .crm-aab-msg{
  font-weight:600;
 }

 .crm-auto-advance-bar .crm-aab-next-btn{
  display:inline-flex;
  align-items:center;
  gap:.4rem;
  padding:.45rem 1.2rem;
  background:#fff;
  color:#059669;
  border:none;
  border-radius:8px;
  font-weight:700;
  font-size:.95rem;
  cursor:pointer;
  text-decoration:none;
  transition:background .2s,transform .15s;
 }

 .crm-auto-advance-bar .crm-aab-next-btn:hover{
  background:#ecfdf5;
  transform:scale(1.04);
 }

 .crm-auto-advance-bar .crm-aab-countdown{
  font-size:.85rem;
  opacity:.9;
  animation:crm-aab-pulse 1s ease-in-out infinite;
 }

 @keyframes crm-aab-pulse{
  0%,100%{opacity:.9}
  50%{opacity:.55}
 }

 .crm-auto-advance-bar .crm-aab-stay{
  color:rgba(255,255,255,.8);
  text-decoration:underline;
  cursor:pointer;
  background:none;
  border:none;
  font-size:.85rem;
  padding:0;
 }

 .crm-auto-advance-bar .crm-aab-stay:hover{
  color:#fff;
 }

 .crm-auto-advance-bar .crm-aab-done{
  font-weight:600;
  opacity:.9;
 }
</style>
<script>
(() => {
 const isKanbanPopup =
  new URLSearchParams(window.location.search)
   .get('kanban_popup') === '1';

 /* In kanban popup mode the parent handles navigation
    via postMessage — no in-page bar needed. */
 if (isKanbanPopup) return;

 const nextUrl =
  @json(session('next_lead_url', ''));

 const bar = document.createElement('div');
 bar.className = 'crm-auto-advance-bar';

 if (nextUrl) {
  let seconds = 5;
  let timer = null;

  const msgSpan = document.createElement('span');
  msgSpan.className = 'crm-aab-msg';
  msgSpan.textContent =
   @json(__('crm.followup_saved_next'));

  const nextBtn = document.createElement('a');
  nextBtn.href = nextUrl;
  nextBtn.className = 'crm-aab-next-btn';
  nextBtn.innerHTML =
   '<i class="bi bi-telephone-forward"></i> '
   + @json(__('crm.next_call'))
   + ' \u2190';

  const countdown = document.createElement('span');
  countdown.className = 'crm-aab-countdown';

  const updateCountdown = () => {
   countdown.textContent =
    @json(__('crm.auto_redirect_countdown'))
     .replace(':seconds', seconds);
  };
  updateCountdown();

  const stayBtn = document.createElement('button');
  stayBtn.type = 'button';
  stayBtn.className = 'crm-aab-stay';
  stayBtn.textContent =
   @json(__('crm.stay_here'));

  stayBtn.addEventListener('click', () => {
   if (timer) clearInterval(timer);
   countdown.style.display = 'none';
   stayBtn.style.display = 'none';
  });

  timer = window.setInterval(() => {
   seconds--;
   if (seconds <= 0) {
    clearInterval(timer);
    window.location.href = nextUrl;
    return;
   }
   updateCountdown();
  }, 1000);

  bar.appendChild(msgSpan);
  bar.appendChild(nextBtn);
  bar.appendChild(countdown);
  bar.appendChild(stayBtn);
 } else {
  const doneSpan = document.createElement('span');
  doneSpan.className = 'crm-aab-done';
  doneSpan.innerHTML =
   '<i class="bi bi-check-circle"></i> '
   + @json(__('crm.no_more_leads'));
  bar.appendChild(doneSpan);
 }

 document.body.prepend(bar);
})();
</script>
<!-- CRM AUTO-ADVANCE NEXT LEAD END -->
@endif
<script src="{{ asset('crm-sidebar.js') }}?v={{ filemtime(public_path('crm-sidebar.js')) }}"></script>
</body>
</html>

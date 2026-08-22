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
   background:#fff;
   box-shadow:var(--shadow)
  }
  html.dark-mode .panel{
   background:var(--bg-card,rgba(24,24,27,.75))!important;
   border:var(--border-glass,1px solid rgba(255,255,255,.08))!important;
   backdrop-filter:var(--glass-blur,blur(16px))!important;
   -webkit-backdrop-filter:var(--glass-blur,blur(16px))!important;
   box-shadow:var(--shadow-glass)!important;
   color:var(--text-primary,#f4f4f5)!important
  }

  .panel-head{
   padding:17px 19px;
   border-bottom:1px solid var(--line);
   background:#fafbfc
  }
  html.dark-mode .panel-head{
   background:rgba(255,255,255,.03)!important;
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
   border:1px solid #dfe5ed;
   border-radius:15px;
   background:#fff
  }

  .followup-stage-editor.is-hidden,
  .followup-detail-panel.is-hidden{
   display:none!important
  }

  .followup-editor-head{
   padding:16px 18px;
   border-bottom:1px solid #e7eaf0;
   background:#f8fafc
  }

  .followup-editor-head h3{
   margin:0;
   color:#29364b;
   font-size:18px
  }

  .followup-editor-head p{
   margin:6px 0 0;
   color:#7d8899;
   font-size:12px;
   line-height:1.8
  }

  .followup-editor-grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:14px;
   padding:17px
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

  .followup-quotation-preview{
   display:inline-flex;
   align-items:center;
   margin-top:9px;
   padding:7px 10px;
   border:1px solid #bdd2eb;
   border-radius:9px;
   background:#eef6ff;
   color:#285f9e;
   text-decoration:none;
   font-size:12px;
   font-weight:900
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



/* CRM KANBAN POPUP MODE START */

body.kanban-followup-popup{
 background:#f5f6f8
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
        {{
         $lead->company_name
         ?: '----'
        }}
        —
        {{
         $lead->source
         ?: '----'
        }}
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
       <small>{{ __('crm.phone') }}</small>
       <strong>
        {{ $lead->phone ?: '----' }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.email') }}</small>
       <strong>
        {{ $lead->email ?: '----' }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.activity') }}</small>
       <strong>
        {{ $lead->activity ?: '----' }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.governorate') }}</small>
       <strong>
        {{ $lead->governorate ?: '----' }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.current_status') }}</small>
       <strong>
        {{
         $lead->status?->name_ar
         ?? '----'
        }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.current_stage') }}</small>
       <strong>
        {{
         $lead->status?->stage?->name_ar
         ?? '----'
        }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.responsible_employee') }}</small>
       <strong>
        {{
         $lead->assignedUser?->name
         ?? $lead->assigned_employee
         ?: '----'
        }}
       </strong>
      </div>

      <div class="client-data-item">
       <small>{{ __('crm.next_followup') }}</small>
       <strong>
        {{
         $lead->next_follow_up_at
          ?->format('d/m/Y - h:i A')
         ?? '----'
        }}
       </strong>
      </div>
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
        @endphp

         <div class="form-grid">
         <div class="field">
          <label for="lead_status_id">
           {{ __('crm.status_stage') }}
          </label>

          <select
           class="control"
           id="lead_status_id"
           name="lead_status_id"
           required
           @if (
            request()->boolean(
             'kanban_popup'
            )
           )
            disabled
           @endif
          >
           @foreach (
            $statusGroups
            as $stageName => $stageStatuses
           )
            <optgroup label="{{ $stageName }}">
             @foreach ($stageStatuses as $status)
              <option
               value="{{ $status->id }}"
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

          @if (
           request()->boolean(
            'kanban_popup'
           )
          )
           <input
            type="hidden"
            name="lead_status_id"
            value="{{ $selectedStatusId }}"
           >
          @endif


          

          <small>
           @if (
            request()->boolean(
             'kanban_popup'
            )
           )
            {{ __('crm.status_auto_selected_hint') }}
           @else
            {{ __('crm.status_sets_stage_hint') }}
           @endif
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

          <!-- FOLLOW-UP EDITABLE STAGE DATA START -->

         <section
          class="followup-stage-editor is-hidden"
          id="businessDetailsSection"
         >
          <header class="followup-editor-head">
           <div>
            <h3>{{ __('crm.company_lead_data') }}</h3>
           <p>
            {{ __('crm.edit_and_save_followup_hint') }}
           </p>
           </div>
          </header>

          <div class="followup-editor-grid">
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

         <section
          class="followup-stage-editor is-hidden"
          id="quotationSection"
         >
          <header class="followup-editor-head">
           <div>
            <h3>{{ __('crm.quotation_data') }}</h3>
           <p>
            {{ __('crm.quotation_data_desc') }}
           </p>
           </div>
          </header>

          <div class="followup-editor-grid">
           <div class="field">
            <label for="solutionType">
             {{ __('crm.system_type') }}
             <span class="required">*</span>
            </label>

            <select
             class="control"
             id="solutionType"
             name="solution_type"
            >
             <option value="">
              {{ __('crm.select_system_type') }}
             </option>

             <option
              value="call_center"
              @selected(
               old(
                'solution_type',
                $lead->solution_type
               ) === 'call_center'
              )
             >
              Call Center
             </option>

             <option
              value="erp"
              @selected(
               old(
                'solution_type',
                $lead->solution_type
               ) === 'erp'
              )
             >
              ERP
             </option>
            </select>
           </div>

           <div class="field">
            <label for="quotationFile">
            {{ __('crm.quotation_file_title') }}

            @if ($hasQuotationFile)
             <span class="optional">
              {{ __('crm.existing_file_badge') }}
             </span>
             @else
              <span class="required">*</span>
             @endif
            </label>

            <input
             class="control"
             id="quotationFile"
             type="file"
             name="quotation_file"
             accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
            >

            <small
             class="followup-file-help"
             id="quotationFileHelp"
            >
             {{ $quotationFileHelpText }}
            </small>

            @if ($hasQuotationFile)
             <a
              class="followup-quotation-preview"
              href="{{ route(
               'v2.leads.quotation.preview',
               $lead
              ) }}"
              target="_blank"
              rel="noopener noreferrer"
             >
              👁 {{ __('crm.preview_current_file') }}
             </a>
            @endif
           </div>
          </div>

          <div
           class="followup-editor-grid followup-detail-panel is-hidden"
           id="callCenterFields"
          >
           <div class="field">
            <label for="linesCount">
             {{ __('crm.line_count') }}
             <span class="required">*</span>
            </label>

            <input
             class="control"
             id="linesCount"
             type="number"
             name="lines_count"
             value="{{ old(
              'lines_count',
              $lead->lines_count
             ) }}"
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
             class="control"
             id="extensions"
             name="extensions"
             maxlength="5000"
            >{{ old(
             'extensions',
             $lead->extensions
            ) }}</textarea>
           </div>
          </div>

          <div
           class="followup-editor-grid followup-detail-panel is-hidden"
           id="erpFields"
          >
           <div class="field full">
            <label for="departments">
             {{ __('crm.departments') }}
             <span class="required">*</span>
            </label>

            <textarea
             class="control"
             id="departments"
             name="departments"
             maxlength="5000"
            >{{ old(
             'departments',
             $lead->departments
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

  const followupNotInterestedSection =
   document.getElementById(
    'notInterestedSection'
   );

  const followupQuotationSection =
   document.getElementById(
    'quotationSection'
   );

  const followupSolutionType =
   document.getElementById(
    'solutionType'
   );

  const followupCallCenterFields =
   document.getElementById(
    'callCenterFields'
   );

  const followupErpFields =
   document.getElementById(
    'erpFields'
   );

  const followupDisinterestReason =
   document.getElementById(
    'disinterestReason'
   );

  const followupQuotationFile =
   document.getElementById(
    'quotationFile'
   );

  const followupLinesCount =
   document.getElementById(
    'linesCount'
   );

  const followupExtensions =
   document.getElementById(
    'extensions'
   );

  const followupDepartments =
   document.getElementById(
    'departments'
   );

  const followupHasQuotationFile =
   @json($hasQuotationFile);

  const followupQuotationFileHelp =
   document.getElementById(
    'quotationFileHelp'
   );

  const followupQuotationDefaultHelp =
   @json($quotationFileHelpText);

  const followupQuotationStatuses = [];

  const followupBusinessStatuses = [
   'new',
   'no_answer',
   'not_interested',
   'donor'
  ];

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

  const updateFollowupSolutionFields =
   () => {
    const quotationActive =
     followupQuotationStatuses.includes(
      followupSelectedStatusCode()
     );

    const solution =
     quotationActive
      ? followupSolutionType?.value
      : '';

    const callCenterActive =
     solution === 'call_center';

    const erpActive =
     solution === 'erp';

    followupCallCenterFields
     ?.classList.toggle(
      'is-hidden',
      !callCenterActive
     );

    followupErpFields
     ?.classList.toggle(
      'is-hidden',
      !erpActive
     );

    if (followupLinesCount) {
     followupLinesCount.required =
      callCenterActive;
    }

    if (followupExtensions) {
     followupExtensions.required =
      callCenterActive;
    }

    if (followupDepartments) {
     followupDepartments.required =
      erpActive;
    }
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

    const quotationActive =
     followupQuotationStatuses.includes(
      code
     );

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

    followupQuotationSection
     ?.classList.toggle(
      'is-hidden',
      !quotationActive
     );

    if (followupDisinterestReason) {
     followupDisinterestReason.required =
      notInterestedActive;
    }

    if (followupSolutionType) {
     followupSolutionType.required =
      quotationActive;
    }

    if (followupQuotationFile) {
     followupQuotationFile.required =
      quotationActive
      && !followupHasQuotationFile;
    }

    updateFollowupSolutionFields();
   };

  followupStatusSelect
   ?.addEventListener(
    'change',
    updateFollowupStageEditor
   );

  followupCampaignSelect?.addEventListener(
   'change',
   updateFollowupCampaignUsers
  );

  followupSolutionType
   ?.addEventListener(
    'change',
    updateFollowupSolutionFields
   );

  followupQuotationFile
   ?.addEventListener(
    'change',
    () => {
     const file =
      followupQuotationFile.files?.[0];

     if (followupQuotationFileHelp) {
      followupQuotationFileHelp
       .textContent = file
        ? @json(__('crm.file_selected_prefix'))
          + file.name
        : followupQuotationDefaultHelp;
     }
    }
   );

  updateFollowupCampaignUsers();
  updateFollowupStageEditor();

 })();
 </script>

@if (
 request()->boolean(
  'kanban_popup'
 )
 && request()->boolean(
  'saved'
 )
 && session('success')
)
<script>
window.parent.postMessage(
 {
  type:
   'crm-kanban-followup-saved'
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
   'not_interested'
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

</body>
</html>

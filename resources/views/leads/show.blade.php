<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ $lead->name }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">

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
  input{
   font-family:inherit
  }

  .crm-app{
   min-height:100vh;
   display:flex;
   }

  .crm-side{
   width:270px;
   min-width:270px;
   height:100vh;
   position:sticky;
   top:0;
   z-index:50;
   overflow-y:auto;
   background:#171f31;
   color:#fff
  }

  .crm-side a,
  .crm-side button{
   font-family:inherit
  }

  .crm-side a{
   text-decoration:none
  }

  .crm-main{
   min-width:0;
   flex:1
  }

  .crm-side-overlay{
   display:none
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
   width: calc(100% - 32px);
   max-width: 100%;
   margin:24px auto 45px
  }

  .profile-card{
   overflow:hidden;
   border:1px solid var(--line);
   border-radius:21px;
   background:var(--card);
   box-shadow:var(--shadow)
  }

  .profile-hero{
   padding:27px;
   background:
    radial-gradient(
     circle at 12% 18%,
     #ffffff14,
     transparent 32%
    ),
    linear-gradient(
     125deg,
     #171f31,
     #303b50
    );
   color:#fff
  }

  .profile-main{
   display:flex;
   align-items:center;
   gap:18px
  }

  .profile-avatar{
   width:76px;
   height:76px;
   display:grid;
   place-items:center;
   flex:0 0 76px;
   border:3px solid #ffffff55;
   border-radius:24px;
   background:#fff;
   color:var(--red);
   box-shadow:0 12px 30px #0003;
   font-size:31px;
   font-weight:900
  }

  .profile-copy{
   min-width:0;
   flex:1
  }

  .profile-copy small{
   color:#f1a0aa;
   font-size:11px;
   font-weight:900
  }

  .profile-copy h2{
   margin:8px 0 7px;
   font-size:28px
  }

  .profile-copy p{
   margin:0;
   color:#cbd2de;
   font-size:12px
  }

  .status-badge{
   min-height:38px;
   display:inline-flex;
   align-items:center;
   gap:8px;
   flex:0 0 auto;
   padding:7px 13px;
   border:1px solid #ffffff66;
   border-radius:999px;
   background:#ffffff14;
   color:#fff;
   font-size:12px;
   font-weight:900
  }

  .status-dot{
   width:10px;
   height:10px;
   border-radius:50%;
   box-shadow:0 0 0 4px #ffffff20
  }

  .profile-actions{
   display:flex;
   align-items:center;
   flex-wrap:wrap;
   gap:9px;
   margin-top:22px
  }

  .action-link{
   min-height:42px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   gap:7px;
   padding:8px 14px;
   border:1px solid #ffffff4d;
   border-radius:11px;
   background:#ffffff13;
   color:#fff;
   text-decoration:none;
   font-size:12px;
   font-weight:900;
   transition:.18s
  }

  .action-link:hover{
   transform:translateY(-1px);
   background:#ffffff22
  }

  .action-link.primary{
   border-color:#fff;
   background:#fff;
   color:#263149
  }

  .action-link.edit{
   border-color:#f0aab2;
   background:#dc2637
  }

  .action-link.whatsapp{
   border-color:#6ac58e;
   background:#238a50
  }

  .action-link.quotation{
   border-color:#8ab3e6;
   background:#245d9f
  }

  .details-body{
   padding:21px
  }

  .details-grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:17px
  }

  .details-card{
   overflow:hidden;
   border:1px solid var(--line);
   border-radius:16px;
   background:#fff
  }

  .details-card.full{
   grid-column:1/-1
  }

  .card-head{
   padding:15px 17px;
   border-bottom:1px solid var(--line);
   background:#fafbfc
  }

  .card-head h3{
   margin:0;
   font-size:15px
  }

  .card-head p{
   margin:5px 0 0;
   color:var(--muted);
   font-size:10px
  }

  .card-content{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   padding:7px 17px 14px
  }

  .detail-item{
   min-width:0;
   padding:13px 7px;
   border-bottom:1px solid #eef1f5
  }

  .detail-item.full{
   grid-column:1/-1
  }

  .detail-label{
   display:block;
   margin-bottom:6px;
   color:#8994a5;
   font-size:10px;
   font-weight:800
  }

  .detail-value{
   display:block;
   color:#343e51;
   font-size:13px;
   font-weight:800;
   line-height:1.7;
   overflow-wrap:anywhere
  }

  .detail-value a{
   color:#245f9f;
   text-decoration:none
  }

  .detail-value a:hover{
   text-decoration:underline
  }

  .long-text{
   white-space:pre-wrap;
   font-weight:normal
  }

  .yes-badge,
  .no-badge{
   display:inline-flex;
   align-items:center;
   padding:6px 9px;
   border-radius:999px;
   font-size:11px;
   font-weight:900
  }

  .yes-badge{
   background:#eaf7ef;
   color:#1d7243
  }

  .no-badge{
   background:#f1f2f5;
   color:#737d8d
  }

  .footer-actions{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:12px;
   margin-top:18px
  }

  .footer-link{
   min-height:42px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:8px 15px;
   border:1px solid var(--line);
   border-radius:11px;
   background:#fff;
   color:#596579;
   text-decoration:none;
   font-size:12px;
   font-weight:900
  }

  .footer-link:hover{
   border-color:#b9c5d5;
   background:#fafbfc
  }

  .footer-link.edit{
   border-color:#e7a9af;
   background:#fff2f3;
   color:#b62533
  }

  @media(max-width:1050px){
   .crm-side{
    position:fixed;
    right:0;
    top:0;
    transform:translateX(105%);
    transition:.22s
   }

   body.crm-side-open .crm-side{
    transform:translateX(0)
   }

   .crm-side-overlay{
    position:fixed;
    inset:0;
    z-index:45;
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

   .profile-main{
    align-items:flex-start;
    flex-wrap:wrap
   }

   .details-grid{
    grid-template-columns:1fr
   }

   .details-card.full{
    grid-column:auto
   }
  }

  @media(max-width:560px){
   .page-title p{
    display:none
   }

   .profile-hero{
    padding:18px
   }

   .profile-avatar{
    width:62px;
    height:62px;
    flex-basis:62px;
    border-radius:19px;
    font-size:25px
   }

   .profile-copy h2{
    font-size:21px
   }

   .profile-actions{
    align-items:stretch;
    flex-direction:column
   }

   .action-link{
    width:100%
   }

   .details-body{
    padding:12px
   }

   .card-content{
    grid-template-columns:1fr
   }

   .detail-item.full{
    grid-column:auto
   }

   .footer-actions{
    align-items:stretch;
    flex-direction:column
   }

   .footer-link{
    width:100%
   }
  }

  @media(prefers-reduced-motion:reduce){
   *{
    transition:none!important
   }
  }
 
  /* CRM SHOW SIDEBAR SYNC START */

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

  .crm-side{
   position:sticky;
   top:0;
   right:auto;
   flex:0 0 288px;
   width:288px;
   min-width:288px;
   max-width:288px;
   height:100vh;
   overflow:auto;
   padding:24px 17px;
   border:0;
   border-inline-end:1px solid var(--line);
   background:#fff;
   color:var(--dark);
   transform:none;
   box-shadow:none;
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
   font-family:inherit;
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
   background:
    linear-gradient(
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

  .crm-sub a:hover{
   color:var(--red);
   background:#fff2f4
  }

  .crm-sub a.active{
   color:var(--red);
   background:#fff0f2
  }

  .topbar>.brand{
   display:none
  }

  @media(max-width:1050px){
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

   .topbar>.brand{
    display:flex
   }

   .crm-side-brand small,
   .crm-side-caption{
    font-size:12px
   }

   .crm-sub a{
    font-size:13px
   }
  }

  @media(max-width:560px){
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
  }

  /* CRM SHOW SIDEBAR SYNC END */


  .followup-history-card{
   overflow:hidden
  }

  .followup-history-head{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:14px;
   padding:20px
  }

  .followup-history-head h3{
   font-size:23px
  }

  .followup-history-head p{
   font-size:14px
  }

  .followup-history-link{
   min-height:44px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:9px 14px;
   border:1px solid #e0b1b6;
   border-radius:10px;
   background:#fff3f4;
   color:#b42433;
   text-decoration:none;
   font-size:13px;
   font-weight:900
  }

  .followup-history-link:hover{
   border-color:#ce7f88;
   background:#ffecee
  }

  .followup-history-list{
   display:grid;
   gap:15px;
   padding:19px
  }

  .followup-history-item{
   padding:18px;
   border:1px solid #e1e6ed;
   border-radius:14px;
   background:#fafbfc
  }

  .followup-history-top{
   display:flex;
   align-items:flex-start;
   justify-content:space-between;
   gap:12px;
   margin-bottom:13px
  }

  .followup-history-top>div{
   display:grid;
   gap:6px
  }

  .followup-history-employee{
   color:#29364b;
   font-size:17px
  }

  .followup-history-date{
   color:#8994a5;
   font-size:13px
  }

  .followup-history-channel{
   display:inline-flex;
   align-items:center;
   padding:7px 10px;
   border-radius:999px;
   background:#eaf3fd;
   color:#285e9d;
   font-size:13px;
   font-weight:900
  }

  .followup-history-status{
   display:flex;
   flex-wrap:wrap;
   gap:8px;
   margin-bottom:12px
  }

  .followup-history-status span{
   padding:7px 10px;
   border-radius:9px;
   background:#f0f2f6;
   color:#657185;
   font-size:13px
  }

  .followup-history-status strong{
   color:#364257
  }

  .followup-history-outcome{
   margin:0;
   padding:14px;
   border:1px solid #e8ebf0;
   border-radius:10px;
   background:#fff;
   color:#445166;
   font-size:16px;
   line-height:2;
   white-space:pre-wrap;
   overflow-wrap:anywhere
  }

  .followup-history-next{
   margin-top:12px;
   color:#7c8798;
   font-size:13px
  }

  .followup-history-next strong{
   color:#4c596d
  }

  .followup-history-empty{
   padding:31px 18px;
   border:1px dashed #cfd6df;
   border-radius:12px;
   background:#fafbfc;
   color:#7d8899;
   text-align:center;
   font-size:15px;
   line-height:1.9
  }

  @media(max-width:650px){
   .followup-history-head,
   .followup-history-top{
    align-items:stretch;
    flex-direction:column
   }

   .followup-history-link{
    width:100%
   }

   .followup-history-channel{
    width:max-content
   }
  }


  .followup-field-changes{
   margin-top:13px;
   padding:14px;
   border:1px solid #dce6f2;
   border-radius:11px;
   background:#f7faff
  }

  .followup-field-changes>strong{
   display:block;
   margin-bottom:10px;
   color:#31465f;
   font-size:14px
  }

  .followup-change-list{
   display:grid;
   gap:8px;
   margin:0;
   padding:0;
   list-style:none
  }

  .followup-change-item{
   display:grid;
   gap:6px;
   padding:10px 11px;
   border-radius:9px;
   background:#fff
  }

  .followup-change-label{
   color:#69778b;
   font-size:13px;
   font-weight:900
  }

  .followup-change-values{
   display:flex;
   align-items:center;
   flex-wrap:wrap;
   gap:8px;
   font-size:14px
  }

  .followup-change-old{
   color:#a05961;
   text-decoration:line-through
  }

  .followup-change-new{
   color:#247047;
   font-weight:900
  }

  /* VoIP Call Records Section Theme */
  .voip-card {
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(24, 32, 51, 0.04);
    overflow: hidden;
    margin-top: 24px;
    grid-column: 1 / -1 !important;
    width: 100% !important;
  }
  .voip-card > .card-content {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }
  .voip-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: linear-gradient(135deg, #fafbfc, #f3f5f9);
    border-bottom: 1px solid var(--line);
  }
  .voip-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .voip-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fff0f1;
    color: var(--red);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 2px 8px rgba(220, 38, 55, 0.12);
  }
  .voip-title-text h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--dark);
  }
  .voip-title-text p {
    margin: 3px 0 0;
    color: var(--muted);
    font-size: 12px;
  }
  .voip-refresh-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    border: 1px solid #dbe1e9;
    padding: 7px 14px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 700;
    font-size: 12px;
    color: var(--dark);
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
  }
  .voip-refresh-btn:hover {
    background: #f8fafc;
    border-color: var(--red);
    color: var(--red);
    transform: translateY(-1px);
  }
  .voip-table-wrapper {
    display: block;
    overflow-x: auto;
    overflow-y: hidden;
    width: 100% !important;
    max-width: 100%;
    min-width: 0;
    -webkit-overflow-scrolling: touch;
  }
  .voip-table {
    width: 100% !important;
    min-width: 900px;
    border-collapse: collapse;
    font-size: 13px;
    text-align:start;
    table-layout: auto;
  }
  .voip-table th:nth-child(1), .voip-table td:nth-child(1) { width: 17%; }
  .voip-table th:nth-child(2), .voip-table td:nth-child(2) { width: 11%; }
  .voip-table th:nth-child(3), .voip-table td:nth-child(3) { width: 22%; }
  .voip-table th:nth-child(4), .voip-table td:nth-child(4) { width: 22%; }
  .voip-table th:nth-child(5), .voip-table td:nth-child(5) { width: 9%; }
  .voip-table th:nth-child(6), .voip-table td:nth-child(6) { width: 11%; }
  .voip-table th:nth-child(7), .voip-table td:nth-child(7) { width: 8%; }
  .voip-table th {
    padding: 14px 16px;
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 12px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
  }
  .voip-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #edf0f4;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .voip-table tr:hover td {
    background: #f8fafc;
  }
  .voip-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
  }
  .voip-badge.inbound {
    background: #e7f8ed;
    color: #166534;
    border: 1px solid #bbf7d0;
  }
  .voip-badge.outbound {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
  }
  .voip-badge.internal {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
  }
  .voip-badge.answered {
    background: #e7f8ed;
    color: #166534;
  }
  .voip-badge.no-answer {
    background: #fff0f1;
    color: #b42332;
  }
  .voip-badge.busy {
    background: #fef3c7;
    color: #92400e;
  }
  .voip-agent-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #334155;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    direction: ltr;
  }

  /* Custom Audio Player Component */
  .crm-audio-player {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 30px;
    padding: 4px 12px 4px 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    min-width: 210px;
    direction: ltr;
  }
  .crm-audio-play-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #dc2637, #b91c1c);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 3px 8px rgba(220, 38, 55, 0.3);
    transition: all 0.2s ease;
    flex-shrink: 0;
  }
  .crm-audio-play-btn:hover {
    transform: scale(1.06);
    box-shadow: 0 5px 12px rgba(220, 38, 55, 0.4);
  }
  .crm-audio-play-btn i {
    font-size: 15px;
  }
  .crm-audio-progress-wrap {
    flex: 1;
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    position: relative;
    cursor: pointer;
    overflow: hidden;
  }
  .crm-audio-progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #dc2637, #ef4444);
    border-radius: 10px;
    transition: width 0.1s linear;
  }
  .crm-audio-time {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    font-family: monospace;
    min-width: 35px;
    text-align: center;
  }
  .crm-audio-download-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 12px;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }
  .crm-audio-download-btn:hover {
    color: #dc2637;
    border-color: #dc2637;
    background: #fff0f1;
    transform: scale(1.05);
  }
</style>
</head>

<body>
 @include('partials.page-loader')

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
     <h1>{{ __('crm.view_lead_data') }}</h1>
     <p>
      تفاصيل العميل وحالته وبيانات التواصل
     </p>
    </div>

     @include('partials.profile-dropdown')
   </header>

   <div class="shell">
    <article class="profile-card">
     <div class="profile-hero">
      <div class="profile-main">
       <div
        class="profile-avatar"
        aria-hidden="true"
       >
        {{ mb_substr((string) $lead->name, 0, 1) }}
       </div>

       <div class="profile-copy">
        <small>
         العميل رقم #{{ $lead->id }}
        </small>

        <h2>{{ $lead->name }}</h2>

        <p>
         {{ $lead->company_name ?: 'بدون شركة مسجلة' }}
         —
         {{ $lead->source ?: 'مصدر غير محدد' }}
        </p>
       </div>

       <span class="status-badge">
        <i
         class="status-dot"
         style="background:{{ $statusColor }}"
        ></i>

        {{ $lead->status?->name_ar ?? __('crm.no_status') }}

        @if ($lead->status?->stage?->name_ar)
         —
         {{ $lead->status->stage->name_ar }}
        @endif
       </span>
      </div>

      <div class="profile-actions">
       <a
        class="action-link primary"
        href="{{ route('v2.leads', $backQuery) }}"
       >
        ← رجوع للعملاء
       </a>

       @can('leads.update')
       <a
        class="action-link edit"
        href="{{ route('v2.leads.edit', $lead) }}"
       >
        ✎ تعديل بيانات العميل
       </a>
       @endcan

       @if ($callPhone)
        <a
         class="action-link"
         href="callto:{{ $callPhone }}"
        >
         {{ __('crm.call_action') }}
        </a>
       @endif

       @if ($whatsappPhone)
        <a
         class="action-link whatsapp"
         href="https://wa.me/{{ $whatsappPhone }}"
         target="_blank"
         rel="noopener noreferrer"
        >
         {{ __('crm.whatsapp') }}
        </a>
       @endif

       @if ($hasQuotationFile && auth()->user()->can('quotations.view'))
        <a
         class="action-link quotation"
         href="{{ route(
          'v2.leads.quotation.preview',
          $lead
         ) }}"
         target="_blank"
         rel="noopener noreferrer"
        >
         👁 معاينة عرض السعر
        </a>
       @endif
      </div>
     </div>

     <div class="details-body">
      <div class="details-grid">
       <section class="details-card">
        <header class="card-head">
         <h3>البيانات الأساسية والتواصل</h3>
         <p>الاسم ووسائل الاتصال ومصدر العميل</p>
        </header>

        <div class="card-content">
         <div class="detail-item">
          <span class="detail-label">
           الاسم الأول
          </span>
          <span class="detail-value">
           {{ $lead->first_name ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           الاسم الأخير
          </span>
          <span class="detail-value">
           {{ $lead->last_name ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.phone') }}
          </span>
          <span class="detail-value">
           @if ($lead->phone)
            <a href="tel:{{ $lead->phone }}">
             {{ $lead->phone }}
            </a>
           @else
            ----
           @endif
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.email') }}
          </span>
          <span class="detail-value">
           @if ($lead->email)
            <a href="mailto:{{ $lead->email }}">
             {{ $lead->email }}
            </a>
           @else
            ----
           @endif
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.source') }}
          </span>
          <span class="detail-value">
           {{ $lead->source ?: 'غير محدد' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           رقم العميل
          </span>
          <span class="detail-value">
           #{{ $lead->id }}
          </span>
         </div>
        </div>
       </section>

       <section class="details-card">
        <header class="card-head">
         <h3>الحالة والمسؤولية</h3>
         <p>الحالة والموظف ومواعيد المتابعة</p>
        </header>

        <div class="card-content">
         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.current_status') }}
          </span>
          <span class="detail-value">
           {{ $lead->status?->name_ar ?? __('crm.no_status') }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           المرحلة
          </span>
          <span class="detail-value">
           {{
            $lead->status?->stage?->name_ar
            ?? 'بدون مرحلة'
           }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.responsible_employee') }}
          </span>
          <span class="detail-value">
           {{
            $lead->assignedUser?->name
            ?? $lead->assigned_employee
            ?: __('crm.unassigned')
           }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           أنشأ بواسطة
          </span>
          <span class="detail-value">
           {{ $lead->created_by ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.next_followup') }}
          </span>
          <span class="detail-value">
           {{
            $lead->next_follow_up_at
             ?->format('d/m/Y - h:i A')
            ?? 'بدون موعد'
           }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.created_date') }}
          </span>
          <span class="detail-value">
           {{
            $lead->created_at
             ?->format('d/m/Y - h:i A')
            ?? '----'
           }}
          </span>
         </div>

         <div class="detail-item full">
          <span class="detail-label">
           آخر تحديث
          </span>
          <span class="detail-value">
           {{
            $lead->updated_at
             ?->format('d/m/Y - h:i A')
            ?? '----'
           }}
          </span>
         </div>
        </div>
       </section>

       <section class="details-card full">
        <header class="card-head">
         <h3>بيانات الشركة والنشاط</h3>
         <p>بيانات العمل والموقع وحجم الاستخدام</p>
        </header>

        <div class="card-content">
         <div class="detail-item">
          <span class="detail-label">{{ __('crm.company_name') }}</span>
          <span class="detail-value">
           {{ $lead->company_name ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">{{ __('crm.activity') }}</span>
          <span class="detail-value">
           {{ $lead->activity ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">{{ __('crm.governorate') }}</span>
          <span class="detail-value">
           {{ $lead->governorate ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           المسمى الوظيفي
          </span>
          <span class="detail-value">
           {{ $lead->job_title ?: '----' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.user_count') }}
          </span>
          <span class="detail-value">
           {{
            $lead->users_count === null
             ? '----'
             : number_format($lead->users_count)
           }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">{{ __('crm.branch_count') }}</span>
          <span class="detail-value">
           {{
            $lead->branches_count === null
             ? '----'
             : number_format($lead->branches_count)
           }}
          </span>
         </div>

         <div class="detail-item full">
          <span class="detail-label">{{ __('crm.address') }}</span>
          <span class="detail-value">
           {{ $lead->address ?: '----' }}
          </span>
         </div>
        </div>
       </section>

       <section class="details-card full">
        <header class="card-head">
         <h3>النظام وعرض السعر</h3>
         <p>تفاصيل الحل والملف المرفوع</p>
        </header>

        <div class="card-content">
         <div class="detail-item">
          <span class="detail-label">{{ __('crm.system_type') }}</span>
          <span class="detail-value">
           {{ $solutionTypeLabel }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           عرض السعر مرسل
          </span>

          <span
           class="{{ $lead->quotation_sent ? 'yes-badge' : 'no-badge' }}"
          >
           {{ $lead->quotation_sent ? 'نعم' : 'لا' }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.line_count') }}
          </span>
          <span class="detail-value">
           {{
            $lead->lines_count === null
             ? '----'
             : number_format($lead->lines_count)
           }}
          </span>
         </div>

         <div class="detail-item">
          <span class="detail-label">
           {{ __('crm.quotation_file') }}
          </span>
          <span class="detail-value">
           {{
            $hasQuotationFile
             ? $quotationFileName
             : 'لا يوجد ملف متاح'
           }}
          </span>
         </div>

         <div class="detail-item full">
          <span class="detail-label">{{ __('crm.accessories') }}</span>
          <span class="detail-value long-text">{!!
           $lead->extensions
            ? nl2br(e($lead->extensions))
            : '----'
          !!}</span>
         </div>

         <div class="detail-item full">
          <span class="detail-label">{{ __('crm.departments') }}</span>
          <span class="detail-value long-text">{!!
           $lead->departments
            ? nl2br(e($lead->departments))
            : '----'
          !!}</span>
         </div>
        </div>
       </section>

       <section class="details-card full">
        <header class="card-head">
         <h3>الملاحظات</h3>
         <p>سبب عدم الاهتمام وأي ملاحظات إضافية</p>
        </header>

        <div class="card-content">
         <div class="detail-item full">
          <span class="detail-label">
           {{ __('crm.not_interested_reason') }}
          </span>
          <span class="detail-value long-text">{!!
           $lead->disinterest_reason
            ? nl2br(e($lead->disinterest_reason))
            : '----'
          !!}</span>
         </div>

         <div class="detail-item full">
          <span class="detail-label">الملاحظات</span>
          <span class="detail-value long-text">{!!
           $lead->notes
            ? nl2br(e($lead->notes))
            : '----'
          !!}</span>
         </div>
        </div>
       </section>

       @can('leads.followups.view')
       <section
        class="details-card full followup-history-card"
       >
        <header
         class="card-head followup-history-head"
        >
         <div>
          <h3>{{ __('crm.latest_followups') }}</h3>

          <p>
           أحدث 5 متابعات مسجلة لهذا العميل
          </p>
         </div>

         <a
          class="followup-history-link"
          href="{{ route(
           'v2.leads.followups.index',
           $lead
          ) }}"
         >
          ◷ تسجيل وعرض كل المتابعات
         </a>
        </header>

        <div class="followup-history-list">
         @forelse ($latestFollowups as $followup)
          <article
           class="followup-history-item"
          >
           <div class="followup-history-top">
            <div>
             <strong
              class="followup-history-employee"
             >
              {{ $followup->user?->name ?? $followup->employee_name }}
             </strong>

             <span
              class="followup-history-date"
             >
              {{
               $followup->followed_up_at
                ?->format('d/m/Y - h:i A')
               ?? '----'
              }}
             </span>
            </div>

            <span
             class="followup-history-channel"
            >
             {{
              $followupCommunicationTypes[
               $followup->communication_type
              ]
              ?? $followup->communication_type
             }}
            </span>
           </div>

           <div class="followup-history-status">
            <span>
             {{ __('crm.stage_label') }}
             <strong>
              {{
               $followup->toStatus?->stage
                ?->name_ar
               ?? '----'
              }}
             </strong>
            </span>

            <span>
             {{ __('crm.status_label') }}
             <strong>
              {{
               $followup->toStatus?->name_ar
               ?? '----'
              }}
             </strong>
            </span>
           </div>

           <p class="followup-history-outcome">{{
            $followup->outcome
           }}</p>

           @if (!empty($followup->field_changes))
            <div class="followup-field-changes">
             <strong>
              التعديلات التي قام بها الموظف
             </strong>

             <ul class="followup-change-list">
              @foreach (
               $followup->field_changes
               as $change
              )
               <li class="followup-change-item">
                <span class="followup-change-label">
                 {{ $change['label'] ?? __('crm.edit') }}
                </span>

                <div class="followup-change-values">
                 <span class="followup-change-old">
                  {{ $change['old'] ?? '----' }}
                 </span>

                 <span aria-hidden="true">
                  ←
                 </span>

                 <span class="followup-change-new">
                  {{ $change['new'] ?? '----' }}
                 </span>
                </div>
               </li>
              @endforeach
             </ul>
            </div>
           @endif

           <div class="followup-history-next">
            المتابعة القادمة:
            <strong>
             {{
              $followup->next_follow_up_at
               ?->format('d/m/Y - h:i A')
              ?? '----'
             }}
            </strong>
           </div>
          </article>
         @empty
          <div class="followup-history-empty">
           لا توجد متابعات مسجلة لهذا العميل
           حتى الآن.
          </div>
         @endforelse
        </div>
       </section>
       @endcan
      </div>
       @can('voip.view')
       <section class="details-card full voip-card" id="voipCallHistoryCard" style="margin-top:20px">
        <header class="voip-card-head">
         <div class="voip-title-group">
          <div class="voip-icon-badge">
           <i class="bi bi-telephone-outbound-fill"></i>
          </div>
          <div class="voip-title-text">
           <h3>سجل مكالمات السنترال (VoIP)</h3>
           <p>سجل المكالمات الواردة والصادرة على هذا الرقم ({{ $lead->phone }}) <span id="voipCallCountBadge" class="voip-badge inbound" style="margin-inline-start:6px;display:none"></span></p>
          </div>
         </div>
         <button type="button" id="btnRefreshVoipCalls" class="voip-refresh-btn">
          <i class="bi bi-arrow-clockwise"></i>
          <span>تحديث السجل</span>
         </button>
        </header>

        <div class="card-content" style="padding:0">
         <div id="voipCallsLoading" style="text-align:center;padding:30px;color:var(--muted)">
          <i class="bi bi-arrow-repeat spin" style="font-size:24px;display:inline-block;margin-bottom:8px"></i>
          <div>جاري تحميل سجل المكالمات من السنترال...</div>
         </div>

         <div id="voipCallsError" style="display:none;background:#fff0f1;color:#b42332;border:1px solid #f1bbc1;padding:12px 16px;border-radius:12px;font-weight:bold;margin-bottom:15px">
         </div>

         <div id="voipCallsContainer" class="voip-table-wrapper" style="display:none">
          <table class="voip-table">
           <thead>
            <tr>
             <th><i class="bi bi-clock"></i> {{ __('crm.datetime') }}</th>
             <th><i class="bi bi-arrow-left-right"></i> {{ __('crm.direction') }}</th>
             <th><i class="bi bi-telephone-outbound"></i> المصدر (المتصل)</th>
             <th><i class="bi bi-telephone-inbound"></i> الوجهة (المستقبل)</th>
             <th><i class="bi bi-stopwatch"></i> {{ __('crm.duration') }}</th>
             <th><i class="bi bi-check-circle"></i> {{ __('crm.status') }}</th>
             @can('voip.recordings')
             <th style="text-align:center"><i class="bi bi-mic"></i> التسجيل الصوتي</th>
             @endcan
            </tr>
           </thead>
           <tbody id="voipCallsTableBody">
           </tbody>
          </table>
         </div>
        </div>
       </section>
       @endcan

     </div>
    </article>
   </div>
  </main>
 </div>

 <script>
 (() => {
  const body = document.body;

  const menuButton =
   document.getElementById('crmMenuButton');

  const overlay =
   document.getElementById('crmSidebarOverlay');

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
   () => {
    setSidebarOpen(false);
   }
  );

  document
   .querySelectorAll('.crm-side a')
   .forEach((link) => {
    link.addEventListener(
     'click',
     () => {
      if (window.innerWidth <= 1050) {
       setSidebarOpen(false);
      }
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

  const crmSidebarMenuToggleHandler = (
   toggle
  ) => {
   const menuId = toggle.getAttribute(
    'data-crm-menu'
   );

   if (!menuId) {
    return;
   }

   const menu = document.getElementById(
    menuId
   );

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
  };

  document
   .querySelectorAll(
    '[data-crm-menu]'
   )
   .forEach((toggle) => {
    toggle.addEventListener(
     'click',
     () => {
      crmSidebarMenuToggleHandler(
       toggle
      );
     }
    );
   });

 })();

 (() => {
  const leadId = @json($lead->id);
  const phone = @json($lead->phone);
  const card = document.getElementById('voipCallHistoryCard');
  if (!card || !phone) return;

  const loading = document.getElementById('voipCallsLoading');
  const errorDiv = document.getElementById('voipCallsError');
  const container = document.getElementById('voipCallsContainer');
  const tbody = document.getElementById('voipCallsTableBody');
  const btnRefresh = document.getElementById('btnRefreshVoipCalls');
  const countBadge = document.getElementById('voipCallCountBadge');

  let activeAudio = null;
  let activePlayBtn = null;

  function stopActiveAudio() {
    if (activeAudio) {
      activeAudio.pause();
      if (activePlayBtn) {
        activePlayBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
      }
      activeAudio = null;
      activePlayBtn = null;
    }
  }

  function setupAudioPlayers() {
    tbody.querySelectorAll('.crm-audio-player').forEach(playerEl => {
      const playBtn = playerEl.querySelector('.crm-audio-play-btn');
      const progressWrap = playerEl.querySelector('.crm-audio-progress-wrap');
      const progressBar = playerEl.querySelector('.crm-audio-progress-bar');
      const timeEl = playerEl.querySelector('.crm-audio-time');
      const src = playerEl.dataset.src;

      let audio = playerEl._audioInstance || null;

      function formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '00:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
      }

      playBtn.addEventListener('click', (e) => {
        e.stopPropagation();

        if (activeAudio && activeAudio !== audio) {
          stopActiveAudio();
        }

        if (!audio) {
          audio = new Audio(src);
          playerEl._audioInstance = audio;

          audio.addEventListener('timeupdate', () => {
            if (audio.duration) {
              const pct = (audio.currentTime / audio.duration) * 100;
              progressBar.style.width = pct + '%';
              timeEl.textContent = formatTime(audio.currentTime);
            }
          });

          audio.addEventListener('ended', () => {
            playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
            progressBar.style.width = '0%';
            timeEl.textContent = formatTime(0);
            activeAudio = null;
            activePlayBtn = null;
          });

          audio.addEventListener('error', () => {
            playBtn.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
            timeEl.textContent = 'خطأ';
          });
        }

        if (audio.paused) {
          audio.play().then(() => {
            playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
            activeAudio = audio;
            activePlayBtn = playBtn;
          }).catch(err => {
            console.error('Audio play error:', err);
          });
        } else {
          audio.pause();
          playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
          activeAudio = null;
          activePlayBtn = null;
        }
      });

      progressWrap.addEventListener('click', (e) => {
        if (!audio || !audio.duration) return;
        const rect = progressWrap.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const pct = clickX / rect.width;
        audio.currentTime = pct * audio.duration;
      });
    });
  }

  function fetchCalls() {
    stopActiveAudio();
    loading.style.display = 'block';
    container.style.display = 'none';
    errorDiv.style.display = 'none';

    fetch(`/leads/${leadId}/calls`)
      .then(res => res.json())
      .then(data => {
        loading.style.display = 'none';
        if (!data || data.success === false) {
          errorDiv.textContent = (data && data.error) ? data.error : 'فشل تحميل سجل المكالمات.';
          errorDiv.style.display = 'block';
          return;
        }

        const calls = data.calls || data.data || [];
        if (countBadge) {
          countBadge.textContent = `${calls.length} مكالمة`;
          countBadge.style.display = 'inline-flex';
        }

        if (calls.length === 0) {
          loading.innerHTML = '<i class="bi bi-telephone-x" style="font-size:24px;color:#94a3b8;display:block;margin-bottom:6px"></i> لا توجد مكالمات مسجلة على هذا الرقم.';
          loading.style.display = 'block';
          return;
        }

        tbody.innerHTML = calls.map(c => {
          let dirHtml = '<span class="voip-badge internal"><i class="bi bi-arrow-left-right"></i> مكالمة</span>';
          if (c.direction === 'inbound') {
            dirHtml = '<span class="voip-badge inbound"><i class="bi bi-telephone-inbound-fill"></i> {{ __('crm.incoming') }}</span>';
          } else if (c.direction === 'outbound') {
            dirHtml = '<span class="voip-badge outbound"><i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.outgoing') }}</span>';
          } else if (c.direction === 'internal') {
            dirHtml = '<span class="voip-badge internal"><i class="bi bi-arrow-left-right"></i> {{ __('crm.internal') }}</span>';
          }

          let dispHtml = `<span class="voip-badge answered"><i class="bi bi-check-circle-fill"></i> ${c.disposition || 'تم الرد'}</span>`;
          if (c.disposition === 'NO ANSWER' || c.disposition === 'FAILED' || c.disposition === 'CANCEL') {
            dispHtml = `<span class="voip-badge no-answer"><i class="bi bi-x-circle-fill"></i> ${c.disposition === 'NO ANSWER' ? 'لم يتم الرد' : c.disposition}</span>`;
          } else if (c.disposition === 'BUSY') {
            dispHtml = `<span class="voip-badge busy"><i class="bi bi-dash-circle-fill"></i> مشغول</span>`;
          }

          const srcChip = `<span class="voip-agent-chip"><i class="bi bi-telephone-outbound"></i> ${c.src || '—'}</span>`;
          const dstChip = `<span class="voip-agent-chip"><i class="bi bi-telephone-inbound"></i> ${c.dst || '—'}</span>`;

          const rawDate = c.call_date || c.started_at || '—';
          const formattedDate = rawDate !== '—' ? rawDate.replace('T', ' ').substring(0, 19) : '—';
          const durationStr = c.duration_formatted || (c.duration_seconds !== undefined ? c.duration_seconds + ' ثانية' : (c.billsec ? c.billsec + ' ثانية' : '0 ثانية'));

          let recHtml = '';
          @can('voip.recordings')
          if (c.has_recording && c.media_id) {
            const recUrl = `/voip/recordings/${encodeURIComponent(c.media_id)}`;
            recHtml = `
              <div class="crm-audio-player" data-src="${recUrl}">
                <button type="button" class="crm-audio-play-btn" title="تشغيل التسجيل الصوتي">
                  <i class="bi bi-play-fill"></i>
                </button>
                <div class="crm-audio-progress-wrap" title="الانتقال بالصوت">
                  <div class="crm-audio-progress-bar"></div>
                </div>
                <span class="crm-audio-time">00:00</span>
                <a href="${recUrl}" download class="crm-audio-download-btn" title="تحميل التسجيل">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            `;
          } else {
            recHtml = '<span style="color:#94a3b8;font-size:12px;font-weight:600"><i class="bi bi-mic-mute"></i> لا يوجد تسجيل</span>';
          }
          @endcan

          return `<tr>
            <td style="white-space:nowrap;font-weight:600;color:#334155"><i class="bi bi-calendar3" style="color:#94a3b8;margin-inline-end:4px"></i> ${formattedDate}</td>
            <td>${dirHtml}</td>
            <td>${srcChip}</td>
            <td>${dstChip}</td>
            <td style="font-weight:600;color:#475569">${durationStr}</td>
            <td>${dispHtml}</td>
            @can('voip.recordings')
            <td style="text-align:center">${recHtml}</td>
            @endcan
          </tr>`;
        }).join('');
        container.style.display = 'block';
        setupAudioPlayers();
      })
      .catch(err => {
        loading.style.display = 'none';
        errorDiv.textContent = 'تعذر الاتصال بسيرفر VoIP: ' + err.message;
        errorDiv.style.display = 'block';
      });
  }

  btnRefresh?.addEventListener('click', fetchCalls);
  fetchCalls();
 })();
 </script>
</body>
</html>

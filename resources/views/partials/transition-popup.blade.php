@once
<style>
.crm-transition-overlay{
 position:fixed;
 inset:0;
 z-index:10000;
 display:none;
 align-items:center;
 justify-content:center;
 padding:20px;
 background:rgba(13,19,33,.55);
 backdrop-filter:blur(3px)
}
.crm-transition-overlay.open{
 display:flex
}
.crm-transition-dialog{
 width:min(820px,96vw);
 height:min(780px,90vh);
 display:flex;
 flex-direction:column;
 overflow:hidden;
 border:1px solid #dfe3ea;
 border-radius:17px;
 background:#fff;
 box-shadow:0 30px 90px #11182755
}
.crm-transition-head{
 min-height:62px;
 display:flex;
 align-items:center;
 gap:12px;
 padding:12px 16px;
 border-bottom:1px solid #e8ebf0;
 background:#fff
}
.crm-transition-head div{
 min-width:0;
 flex:1
}
.crm-transition-head h3{
 margin:0;
 color:#28354a;
 font-size:17px
}
.crm-transition-head p{
 margin:5px 0 0;
 color:#858f9f;
 font-size:10px;
 line-height:1.6
}
.crm-transition-close{
 width:38px;
 height:38px;
 display:grid;
 place-items:center;
 flex:0 0 38px;
 border:1px solid #e1e5eb;
 border-radius:10px;
 background:#f8f9fb;
 color:#606b7e;
 font:900 20px var(--font-primary);
 cursor:pointer
}
.crm-transition-frame{
 width:100%;
 min-height:0;
 flex:1;
 border:0;
 background:#f5f6f8
}

/* Dark mode */
html.dark-mode .crm-transition-dialog,
html.crm-monochrome.dark-mode .crm-transition-dialog{
 background:#18181b !important;
 border-color:rgba(255,255,255,.12) !important;
 box-shadow:0 30px 90px rgba(0,0,0,.8) !important
}
html.dark-mode .crm-transition-head,
html.crm-monochrome.dark-mode .crm-transition-head{
 background:#18181b !important;
 border-bottom-color:rgba(255,255,255,.08) !important
}
html.dark-mode .crm-transition-head h3,
html.crm-monochrome.dark-mode .crm-transition-head h3{
 color:#f4f4f5 !important
}
html.dark-mode .crm-transition-head p,
html.crm-monochrome.dark-mode .crm-transition-head p{
 color:#a1a1aa !important
}
html.dark-mode .crm-transition-close,
html.crm-monochrome.dark-mode .crm-transition-close{
 background:#27272a !important;
 border-color:rgba(255,255,255,.12) !important;
 color:#e4e4e7 !important
}
html.dark-mode .crm-transition-close:hover,
html.crm-monochrome.dark-mode .crm-transition-close:hover{
 background:#3f3f46 !important;
 color:#fff !important
}
html.dark-mode .crm-transition-frame,
html.crm-monochrome.dark-mode .crm-transition-frame{
 background:#09090b !important
}

/* Monochrome light */
html.crm-monochrome:not(.dark-mode) .crm-transition-dialog{
 background:#fff !important;
 border-color:#e5e5e5 !important
}
html.crm-monochrome:not(.dark-mode) .crm-transition-head{
 background:#fafafa !important;
 border-bottom-color:#e5e5e5 !important
}
html.crm-monochrome:not(.dark-mode) .crm-transition-head h3{
 color:#171717 !important
}
html.crm-monochrome:not(.dark-mode) .crm-transition-close{
 background:#f5f5f5 !important;
 border-color:#e5e5e5 !important;
 color:#171717 !important
}
html.crm-monochrome:not(.dark-mode) .crm-transition-frame{
 background:#fafafa !important
}

/* Mobile responsive */
@media(max-width:700px){
 .crm-transition-overlay{
  padding:7px
 }
 .crm-transition-dialog{
  width:100%;
  height:96vh;
  border-radius:13px
 }
}
</style>

<div
 class="crm-transition-overlay"
 id="crmTransitionOverlay"
 role="dialog"
 aria-modal="true"
 aria-hidden="true"
 aria-labelledby="crmTransitionTitle"
 aria-describedby="crmTransitionDescription"
>
 <div class="crm-transition-dialog">
  <header class="crm-transition-head">
   <div>
    <h3 id="crmTransitionTitle">{{ __('crm.record_lead_followup') }}</h3>
    <p id="crmTransitionDescription">{{ __('crm.followup_subtitle') }}</p>
   </div>
   <button class="crm-transition-close" id="crmTransitionClose" type="button" aria-label="{{ __('crm.close') }}">&times;</button>
  </header>
  <iframe
   class="crm-transition-frame"
   id="crmTransitionFrame"
   src="about:blank"
   title="{{ __('crm.record_lead_followup') }}"
  ></iframe>
 </div>
</div>

<script>
(() => {
 const overlay = document.getElementById('crmTransitionOverlay');
 const frame = document.getElementById('crmTransitionFrame');
 const closeBtn = document.getElementById('crmTransitionClose');
 const titleEl = document.getElementById('crmTransitionTitle');
 const descEl = document.getElementById('crmTransitionDescription');
 if (!overlay || !frame) return;

 const defaultTitle = titleEl ? titleEl.textContent : '';
 const defaultDescription = descEl ? descEl.textContent : '';
 const followupSavedNext = @json(__('crm.followup_saved_next'));
 const leadMovedToast = @json(__('crm.lead_moved_successfully'));

 let currentContext = '';
 let lastTriggerElement = null;

 const open = (url, heading = '', description = '', context = '') => {
  const u = new URL(url, window.location.origin);
  if (!u.searchParams.has('kanban_popup')) u.searchParams.set('kanban_popup', '1');

  if (context) {
   currentContext = context;
   u.searchParams.set('context', context);
  } else if (u.searchParams.has('context')) {
   currentContext = u.searchParams.get('context');
  } else if (u.searchParams.has('source')) {
   currentContext = u.searchParams.get('source');
  } else if (
   document.body.classList.contains('kanban-page')
   || document.querySelector('.board-shell') !== null
   || document.querySelector('[data-kanban-column]') !== null
   || window.location.pathname.includes('/kanban')
  ) {
   currentContext = 'kanban';
   u.searchParams.set('context', 'kanban');
  } else {
   currentContext = '';
  }

  frame.src = u.toString();
  if (titleEl) titleEl.textContent = heading || defaultTitle;
  if (descEl) descEl.textContent = description || defaultDescription;
  overlay.classList.add('open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  closeBtn?.focus();
 };

 const close = () => {
  overlay.classList.remove('open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  document.body.classList.remove('kanban-modal-open');
  frame.src = 'about:blank';
  if (titleEl) titleEl.textContent = defaultTitle;
  if (descEl) descEl.textContent = defaultDescription;
  currentContext = '';

  if (lastTriggerElement && typeof lastTriggerElement.focus === 'function') {
   try {
    lastTriggerElement.focus();
   } catch (e) {}
   lastTriggerElement = null;
  }
 };

 window.crmTransitionPopup = { open, close };

 closeBtn?.addEventListener('click', close);

 overlay.addEventListener('click', (e) => {
  if (e.target === overlay) close();
 });

 document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && overlay.classList.contains('open')) close();
 });

 document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-transition-popup]');
  if (!trigger) return;
  e.preventDefault();
  lastTriggerElement = trigger;
  const url = trigger.getAttribute('data-transition-popup') || trigger.href;
  const heading = trigger.getAttribute('data-transition-title')
   || trigger.getAttribute('data-lead-name')
   || '';
  const description = trigger.getAttribute('data-transition-description') || '';
  const context = trigger.getAttribute('data-transition-context')
   || (trigger.closest('.board-shell, [data-kanban-column]') ? 'kanban' : '');

  // MicroSIP Integration via tel: protocol
  const rawPhone = trigger.getAttribute('data-voice-dial')
   || (trigger.getAttribute('data-sip-href') ? trigger.getAttribute('data-sip-href').replace(/^sip:|^callto:|^tel:/, '') : '')
   || trigger.getAttribute('data-phone')
   || '';

  if (rawPhone) {
   const cleanPhone = String(rawPhone).replace(/[^0-9+]/g, '');
   if (cleanPhone) {
    // Open MicroSIP via OS tel: protocol
    window.location.href = 'tel:' + cleanPhone;

    /*
    // Sokrat Voice Softphone Launcher (commented out in favor of MicroSIP)
    if (typeof window.sokratVoiceDial === 'function') {
     window.sokratVoiceDial(cleanPhone, heading);
    } else if (typeof window.parent?.sokratVoiceDial === 'function') {
     window.parent.sokratVoiceDial(cleanPhone, heading);
    }
    */
   }
  }

  open(url, heading, description, context);
 });

 window.addEventListener('message', (e) => {
  if (e.origin !== window.location.origin) return;
  if (e.data?.type !== 'crm-kanban-followup-saved') return;

  const isKanban = currentContext === 'kanban'
   || e.data?.context === 'kanban'
   || document.querySelector('.board-shell') !== null
   || document.querySelector('[data-kanban-column]') !== null
   || window.location.pathname.includes('/kanban');

  if (isKanban) {
   close();
   try {
    sessionStorage.setItem('crm_kanban_toast', leadMovedToast);
   } catch (err) {}
   if (typeof window.showKanbanToast === 'function') {
    window.showKanbanToast(leadMovedToast);
   }
   window.location.reload();
   return;
  }

  if (e.data.nextLeadUrl && frame) {
   frame.src = e.data.nextLeadUrl;
   if (titleEl) titleEl.textContent = followupSavedNext;
   return;
  }

  close();
  window.location.reload();
 });

 frame.addEventListener('load', () => {
  try {
   if (!frame.contentWindow || frame.src === 'about:blank' || !frame.src) return;
   const frameUrl = new URL(frame.contentWindow.location.href);
   if (frameUrl.searchParams.get('saved') === '1' && frameUrl.searchParams.get('kanban_popup') === '1') {
    const isKanban = currentContext === 'kanban'
     || frameUrl.searchParams.get('context') === 'kanban'
     || document.querySelector('.board-shell') !== null
     || document.querySelector('[data-kanban-column]') !== null
     || window.location.pathname.includes('/kanban');

    if (isKanban) {
     close();
     try {
      sessionStorage.setItem('crm_kanban_toast', leadMovedToast);
     } catch (err) {}
     if (typeof window.showKanbanToast === 'function') {
      window.showKanbanToast(leadMovedToast);
     }
     window.location.reload();
    }
   }
  } catch (e) {}
 });
})();
</script>
@endonce

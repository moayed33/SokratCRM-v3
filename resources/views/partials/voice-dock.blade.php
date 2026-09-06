@if(Auth::check())
<script>
    window.__crmTelephonyMode = '{{ !empty(Auth::user()->voip_extension) ? "webrtc" : "microsip" }}';
    window.__crmUserExtension = '{{ Auth::user()->voip_extension ?? "" }}';
</script>
@endif
@if(Auth::check() && !empty(Auth::user()->voip_extension) && !request()->boolean('kanban_popup') && !request()->has('kanban_popup') && !request()->boolean('popup') && !request()->has('popup'))
{{-- SOKRAT VOICE FLOATING DOCK & EMBED CONTROLLER --}}
<aside id="sokratVoiceDock" class="sokrat-voice-dock" aria-label="Sokrat Voice">
    <button id="voiceDockToggle" type="button" class="sokrat-voice-pill" title="Sokrat Voice (Ext {{ Auth::user()->voip_extension }})">
        <span class="sokrat-voice-dot" data-voice-status="offline"></span>
        <span class="sokrat-voice-ext">{{ Auth::user()->voip_extension }}</span>
        <span class="sokrat-voice-call-info" hidden>
            <span data-voice-remote class="sokrat-voice-remote"></span>
            <span data-voice-timer class="sokrat-voice-timer">00:00</span>
        </span>
        <span class="sokrat-voice-label"><i class="bi bi-telephone-fill"></i> Voice</span>
    </button>
    <div class="sokrat-voice-quick-actions" hidden>
        <button data-voice-mute type="button" class="sokrat-voice-action-btn" title="{{ __('crm.mute') ?? 'Mute' }}"><i class="bi bi-mic-mute"></i></button>
        <button data-voice-hangup type="button" class="sokrat-voice-action-btn hangup" title="{{ __('crm.end_call') ?? 'End Call' }}"><i class="bi bi-telephone-x-fill"></i></button>
    </div>
</aside>

{{-- EXPANDED SOFTPHONE PANEL (ZERO-SCROLL, ALWAYS BOTTOM-RIGHT) --}}
<div id="sokratVoicePanel" class="sokrat-voice-panel" hidden>
    <div class="sokrat-voice-panel-head">
        <div class="sokrat-voice-panel-title">
            <span class="sokrat-voice-dot-sm" data-voice-status="offline"></span>
            <strong>Sokrat Voice</strong>
            <span class="sokrat-voice-badge-ext">{{ Auth::user()->voip_extension }}</span>
        </div>
        <div class="sokrat-voice-head-actions">
            <button id="voicePanelPopout" type="button" class="sokrat-voice-head-btn" title="Open in Persistent Standalone Window">
                <i class="bi bi-box-arrow-up-right"></i>
            </button>
            <button id="voicePanelReload" type="button" class="sokrat-voice-head-btn" title="Reload Session">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button id="voicePanelClose" type="button" class="sokrat-voice-head-btn" title="{{ __('crm.close') ?? 'Minimize' }}">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="sokrat-voice-frame-wrap">
        <iframe
            id="sokratVoiceFrame"
            allow="microphone *; autoplay *; camera *"
            referrerpolicy="same-origin"
            title="Sokrat Voice Softphone"
        ></iframe>
    </div>
</div>

{{-- INCOMING CALL TOAST NOTIFICATION --}}
<div id="sokratVoiceToast" class="sokrat-voice-toast" hidden>
    <div class="sokrat-voice-toast-ico"><i class="bi bi-telephone-inbound-fill"></i></div>
    <div class="sokrat-voice-toast-content">
        <div class="sokrat-voice-toast-head">
            <strong data-toast-caller class="sokrat-voice-toast-num"></strong>
            <span class="sokrat-voice-toast-badge">{{ __('crm.incoming_call') ?? 'مكالمة واردة' }}</span>
        </div>
        <div class="sokrat-voice-toast-leads" data-toast-leads></div>
    </div>
    <button type="button" class="sokrat-voice-toast-dismiss" id="voiceToastDismiss" title="Dismiss">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
{{-- INCOMING CALL LEAD PROFILE SCREEN-POP MODAL --}}
<div id="sokratLeadScreenPop" class="sokrat-lead-screen-pop" hidden>
    <div class="sokrat-screen-pop-head">
        <div class="sokrat-screen-pop-title">
            <span class="sokrat-screen-pop-pulse"></span>
            <strong>{{ __('crm.incoming_call') ?? 'مكالمة واردة' }}</strong>
            <span class="sokrat-screen-pop-badge-ext">{{ Auth::user()->voip_extension }}</span>
        </div>
        <button type="button" class="sokrat-screen-pop-close" id="voiceScreenPopDismiss" title="{{ __('crm.close') ?? 'إغلاق' }}">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sokrat-screen-pop-body" data-pop-content>
        <!-- Populated dynamically by handleIncomingCall -->
    </div>
</div>

<style>
/* CRITICAL: Hidden attribute MUST override all display declarations */
.sokrat-voice-dock[hidden],
.sokrat-voice-toast[hidden],
.sokrat-lead-screen-pop[hidden],
.sokrat-voice-call-info[hidden],
.sokrat-voice-quick-actions[hidden],
#sokratVoiceToast[hidden],
#sokratLeadScreenPop[hidden] {
    display: none !important;
}

/* Panel hidden state: keep iframe rendered in DOM for active background WebRTC connection without visual footprint */
.sokrat-voice-panel[hidden],
#sokratVoicePanel[hidden] {
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transform: translateY(20px) scale(0.97) !important;
    display: flex !important;
}

/* Suppress voice dock in popups, modals, iframes, and followup screens */
body.kanban-followup-popup .sokrat-voice-dock,
body.kanban-followup-popup .sokrat-voice-panel,
body.kanban-followup-popup .sokrat-voice-toast,
body.kanban-followup-popup .sokrat-lead-screen-pop,
body.is-popup .sokrat-voice-dock,
body.is-popup .sokrat-voice-panel,
html.in-iframe .sokrat-voice-dock,
body.in-iframe .sokrat-voice-dock {
    display: none !important;
}

/* --- MODERN GLASSMORPHIC FLOATING DOCK PILL --- */
.sokrat-voice-dock {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    left: auto !important;
    top: auto !important;
    z-index: 2147483645 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Cairo", sans-serif !important;
    margin: 0 !important;
    padding: 0 !important;
    direction: ltr !important;
    pointer-events: auto !important;
}

.sokrat-voice-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 9px !important;
    padding: 7px 16px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: rgba(18, 18, 23, 0.88) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    color: #f4f4f5 !important;
    font-weight: 800 !important;
    font-size: 12.5px !important;
    font-family: inherit !important;
    cursor: pointer !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.32), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    line-height: 1.4 !important;
    outline: none !important;
}
.sokrat-voice-pill:hover {
    box-shadow: 0 12px 36px rgba(239, 68, 68, 0.28), 0 0 0 1px rgba(239, 68, 68, 0.4) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    transform: translateY(-2px) !important;
}
.sokrat-voice-dot {
    width: 9px !important;
    height: 9px !important;
    border-radius: 50% !important;
    background: #71717a !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
    transition: all 0.2s ease !important;
}
.sokrat-voice-dot[data-voice-status="online"] {
    background: #10b981 !important;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.6) !important;
}
.sokrat-voice-dot[data-voice-status="ringing"] {
    background: #f59e0b !important;
    box-shadow: 0 0 12px rgba(245, 158, 11, 0.8) !important;
    animation: sokrat-voice-pulse 0.6s ease-in-out infinite alternate !important;
}
.sokrat-voice-dot[data-voice-status="incall"] {
    background: #ef4444 !important;
    box-shadow: 0 0 14px rgba(239, 68, 68, 0.9) !important;
    animation: sokrat-voice-pulse 0.8s ease-in-out infinite alternate !important;
}
.sokrat-voice-dot-sm {
    width: 8px !important;
    height: 8px !important;
    border-radius: 50% !important;
    background: #71717a !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
}
.sokrat-voice-dot-sm[data-voice-status="online"] {
    background: #10b981 !important;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.5) !important;
}
.sokrat-voice-dot-sm[data-voice-status="incall"] {
    background: #ef4444 !important;
    box-shadow: 0 0 8px rgba(239, 68, 68, 0.6) !important;
}
.sokrat-voice-ext {
    font-family: monospace !important;
    font-weight: 800 !important;
    background: rgba(255, 255, 255, 0.08) !important;
    color: #e4e4e7 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    padding: 2px 7px !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    letter-spacing: 0.02em !important;
}
.sokrat-voice-label {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    color: #ef4444 !important;
    font-weight: 800 !important;
}
.sokrat-voice-call-info {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
}
.sokrat-voice-remote {
    font-weight: 700 !important;
    max-width: 120px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}
.sokrat-voice-timer {
    font-family: monospace !important;
    font-weight: 900 !important;
    color: #f87171 !important;
    background: rgba(239, 68, 68, 0.15) !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
    padding: 2px 6px !important;
    border-radius: 6px !important;
    font-size: 11px !important;
}
.sokrat-voice-quick-actions {
    display: flex !important;
    align-items: center !important;
    gap: 5px !important;
}
.sokrat-voice-action-btn {
    width: 34px !important;
    height: 34px !important;
    border-radius: 50% !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    background: rgba(18, 18, 23, 0.88) !important;
    backdrop-filter: blur(12px) !important;
    color: #e4e4e7 !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 13.5px !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
    transition: all 0.18s ease !important;
    outline: none !important;
}
.sokrat-voice-action-btn:hover {
    border-color: #ef4444 !important;
    color: #ef4444 !important;
    transform: scale(1.06) !important;
}
.sokrat-voice-action-btn.hangup {
    color: #f87171 !important;
    background: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
}
.sokrat-voice-action-btn.hangup:hover {
    background: #ef4444 !important;
    color: #ffffff !important;
}

/* --- EXPANDED SOFTPHONE PANEL (PREMIUM OLED TECH DESIGN) --- */
.sokrat-voice-panel {
    position: fixed !important;
    bottom: 74px !important;
    right: 20px !important;
    left: auto !important;
    top: auto !important;
    width: 420px !important;
    height: 660px !important;
    max-width: calc(100vw - 40px) !important;
    max-height: calc(100vh - 90px) !important;
    background: #09090d !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 16px !important;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
    z-index: 2147483646 !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    animation: sokrat-voice-slide-up 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    margin: 0 !important;
    padding: 0 !important;
    direction: ltr !important;
    pointer-events: auto !important;
}
.sokrat-voice-panel-head {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 9px 14px !important;
    background: #111116 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #f4f4f5 !important;
    font-size: 12.5px !important;
    user-select: none !important;
    flex-shrink: 0 !important;
}
.sokrat-voice-panel-title {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em !important;
}
.sokrat-voice-badge-ext {
    font-family: monospace !important;
    font-size: 11px !important;
    background: rgba(239, 68, 68, 0.15) !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
    color: #f87171 !important;
    padding: 2px 7px !important;
    border-radius: 6px !important;
    font-weight: 800 !important;
}
.sokrat-voice-head-actions {
    display: flex !important;
    align-items: center !important;
    gap: 5px !important;
}
.sokrat-voice-head-btn {
    background: rgba(255, 255, 255, 0.06) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #a1a1aa !important;
    width: 30px !important;
    height: 30px !important;
    min-height: 30px !important;
    border-radius: 7px !important;
    cursor: pointer !important;
    display: inline-grid !important;
    place-items: center !important;
    font-size: 13px !important;
    transition: all 0.15s ease !important;
}
.sokrat-voice-head-btn:hover {
    background: rgba(255, 255, 255, 0.14) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}
.sokrat-voice-frame-wrap {
    flex: 1 !important;
    width: 100% !important;
    height: 100% !important;
    position: relative !important;
    background: #050507 !important;
}
#sokratVoiceFrame {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
    display: block !important;
}

/* --- REFINED INCOMING CALL TOAST --- */
.sokrat-voice-toast {
    position: fixed !important;
    top: 24px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 2147483647 !important;
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    padding: 12px 18px !important;
    background: rgba(18, 18, 23, 0.95) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    border: 1px solid rgba(16, 185, 129, 0.4) !important;
    border-radius: 14px !important;
    box-shadow: 0 16px 48px rgba(0,0,0,0.4), 0 0 0 1px rgba(16, 185, 129, 0.15) !important;
    min-width: 320px !important;
    max-width: 460px !important;
    width: auto !important;
    height: auto !important;
    color: #f4f4f5 !important;
    animation: sokrat-voice-toast-in 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    margin: 0 !important;
}
.sokrat-voice-toast-ico {
    width: 38px !important;
    height: 38px !important;
    border-radius: 10px !important;
    background: rgba(16, 185, 129, 0.15) !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
    color: #10b981 !important;
    display: inline-grid !important;
    place-items: center !important;
    font-size: 17px !important;
    flex-shrink: 0 !important;
    animation: sokrat-voice-ring 0.8s ease-in-out infinite alternate !important;
}
.sokrat-voice-toast-content {
    flex: 1 !important;
    min-width: 0 !important;
}
.sokrat-voice-toast-head {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin-bottom: 4px !important;
}
.sokrat-voice-toast-num {
    font-size: 15px !important;
    color: #f4f4f5 !important;
    font-family: monospace !important;
    font-weight: 800 !important;
}
.sokrat-voice-toast-badge {
    font-size: 10px !important;
    font-weight: 800 !important;
    background: rgba(16, 185, 129, 0.15) !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
    color: #34d399 !important;
    padding: 2px 7px !important;
    border-radius: 999px !important;
}
.sokrat-voice-toast-leads {
    display: flex !important;
    flex-direction: column !important;
    gap: 4px !important;
}
.sokrat-voice-lead-link {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 4px 10px !important;
    border-radius: 7px !important;
    background: rgba(255, 255, 255, 0.06) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: #e4e4e7 !important;
    font-weight: 700 !important;
    font-size: 12px !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
}
.sokrat-voice-lead-link:hover {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    color: #f87171 !important;
}
.sokrat-voice-lead-link.create {
    color: #34d399 !important;
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: rgba(16, 185, 129, 0.25) !important;
}
.sokrat-voice-toast-dismiss {
    background: none !important;
    border: none !important;
    color: #71717a !important;
    cursor: pointer !important;
    font-size: 16px !important;
    padding: 3px !important;
    display: inline-grid !important;
    place-items: center !important;
    border-radius: 6px !important;
    transition: color 0.15s ease !important;
}
.sokrat-voice-toast-dismiss:hover { color: #f4f4f5 !important; }

/* --- REFINED SOKRAT LEAD PROFILE SCREEN-POP MODAL --- */
.sokrat-lead-screen-pop {
    position: fixed !important;
    top: 24px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 2147483647 !important;
    width: 90% !important;
    max-width: 480px !important;
    background: #111116 !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 16px !important;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
    animation: sokrat-screen-pop-slide 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
    overflow: hidden !important;
    font-family: inherit !important;
    direction: rtl !important;
    color: #f4f4f5 !important;
}

.sokrat-screen-pop-head {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 12px 18px !important;
    background: #181820 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.sokrat-screen-pop-title {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    color: #f4f4f5 !important;
}

.sokrat-screen-pop-badge-ext {
    font-size: 11px !important;
    font-weight: 800 !important;
    background: rgba(239, 68, 68, 0.15) !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
    color: #f87171 !important;
    padding: 2px 7px !important;
    border-radius: 999px !important;
    margin-inline-start: 6px !important;
}

.sokrat-screen-pop-pulse {
    width: 9px !important;
    height: 9px !important;
    border-radius: 50% !important;
    background: #10b981 !important;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7) !important;
    animation: sokrat-pop-pulse 1.2s infinite !important;
}

@keyframes sokrat-voice-pulse { to { opacity: 0.35; transform: scale(0.9); } }
@keyframes sokrat-voice-ring { 0% { transform: rotate(-6deg); } 100% { transform: rotate(6deg); } }
@keyframes sokrat-voice-slide-up { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes sokrat-voice-toast-in { from { opacity: 0; transform: translate(-50%, -16px); } to { opacity: 1; transform: translate(-50%, 0); } }
@keyframes sokrat-pop-pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
@keyframes sokrat-screen-pop-slide {
    from { opacity: 0; transform: translate(-50%, -20px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

.sokrat-screen-pop-close {
    background: none !important;
    border: none !important;
    color: #a1a1aa !important;
    font-size: 15px !important;
    cursor: pointer !important;
    padding: 4px !important;
    display: grid !important;
    place-items: center !important;
    border-radius: 6px !important;
    transition: all 0.15s ease !important;
}
.sokrat-screen-pop-close:hover {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #ef4444 !important;
}

.sokrat-screen-pop-body {
    padding: 16px 18px !important;
}

.sokrat-screen-pop-caller {
    display: flex !important;
    align-items: baseline !important;
    gap: 10px !important;
    margin-bottom: 12px !important;
}

.sokrat-screen-pop-caller .phone-num {
    font-size: 18px !important;
    font-weight: 800 !important;
    font-family: monospace !important;
    color: #f4f4f5 !important;
}

.sokrat-screen-pop-caller .badge-status {
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 2px 8px !important;
    border-radius: 999px !important;
    background: rgba(245, 158, 11, 0.15) !important;
    border: 1px solid rgba(245, 158, 11, 0.3) !important;
    color: #fbbf24 !important;
}

.sokrat-pop-lead-card {
    background: #181820 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px !important;
    padding: 12px 14px !important;
    margin-bottom: 12px !important;
}

.sokrat-pop-lead-name {
    font-size: 16px !important;
    font-weight: 800 !important;
    color: #f4f4f5 !important;
    margin-bottom: 4px !important;
}

.sokrat-pop-lead-meta {
    font-size: 12px !important;
    color: #a1a1aa !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    align-items: center !important;
}

.sokrat-pop-stage-badge {
    background: rgba(59, 130, 246, 0.15) !important;
    border: 1px solid rgba(59, 130, 246, 0.3) !important;
    color: #93c5fd !important;
    padding: 2px 8px !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    font-size: 11px !important;
}

.sokrat-pop-actions {
    display: flex !important;
    gap: 8px !important;
    margin-top: 14px !important;
}

.sokrat-pop-btn {
    flex: 1 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    padding: 9px 14px !important;
    border-radius: 10px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    border: none !important;
}

.sokrat-pop-btn-primary {
    background: #2563eb !important;
    color: #ffffff !important;
}
.sokrat-pop-btn-primary:hover {
    background: #1d4ed8 !important;
    color: #ffffff !important;
}

.sokrat-pop-btn-secondary {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #e4e4e7 !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
}
.sokrat-pop-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15) !important;
}

.sokrat-pop-btn-create {
    background: #10b981 !important;
    color: #ffffff !important;
}
.sokrat-pop-btn-create:hover {
    background: #059669 !important;
    color: #ffffff !important;
}

/* Inline Click-to-Call Buttons */
.btn-dial-inline {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 26px !important;
    height: 26px !important;
    border-radius: 6px !important;
    background: rgba(16, 185, 129, 0.12) !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
    color: #34d399 !important;
    font-size: 12px !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    margin-inline-start: 6px !important;
    vertical-align: middle !important;
    padding: 0 !important;
}
.btn-dial-inline:hover {
    background: #10b981 !important;
    border-color: #059669 !important;
    color: #ffffff !important;
    transform: scale(1.08) !important;
}
</style>

<script>
(function() {
    'use strict';
    window.sokratVoiceDial = function(phone, leadName) {
        if (!phone) return;
        const cleanPhone = String(phone).replace(/[^0-9+]/g, '');
        if (!cleanPhone) return;

        if (typeof window.__sokratVoiceTriggerDial === 'function') {
            window.__sokratVoiceTriggerDial(cleanPhone, leadName);
        } else {
            window.__sokratPendingDial = cleanPhone;
            window.__sokratPendingLeadName = leadName;
            window.dispatchEvent(new CustomEvent('sokrat:voice-dial', { detail: { phone: cleanPhone, leadName } }));
        }
    };

    // Defense-in-depth: Never initialize or display CRM voice dock when page is embedded inside an iframe or popup
    if (window.self !== window.top || window.location.search.includes('kanban_popup=1') || window.location.search.includes('popup=1')) {
        try {
            ['sokratVoiceDock', 'sokratVoicePanel', 'sokratVoiceToast', 'sokratLeadScreenPop'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });
        } catch (_) {}
        return;
    }

    function initSokratVoice() {
        const dock = document.getElementById('sokratVoiceDock');
        if (!dock) return;

        const panel = document.getElementById('sokratVoicePanel');
        const frame = document.getElementById('sokratVoiceFrame');
        const toast = document.getElementById('sokratVoiceToast');
        const popModal = document.getElementById('sokratLeadScreenPop');
        const popDismiss = document.getElementById('voiceScreenPopDismiss');
        const toggle = document.getElementById('voiceDockToggle');
        const closeBtn = document.getElementById('voicePanelClose');
        const reloadBtn = document.getElementById('voicePanelReload');
        const popoutBtn = document.getElementById('voicePanelPopout');
        const toastDismiss = document.getElementById('voiceToastDismiss');
        const statusDots = document.querySelectorAll('[data-voice-status]');
        const callInfo = dock.querySelector('.sokrat-voice-call-info');
        const quickActions = dock.querySelector('.sokrat-voice-quick-actions');
        const remoteEl = dock.querySelector('[data-voice-remote]');
        const timerEl = dock.querySelector('[data-voice-timer]');
        const labelEl = dock.querySelector('.sokrat-voice-label');

        // Always attach to body root
        if (dock.parentElement !== document.body) document.body.appendChild(dock);
        if (panel && panel.parentElement !== document.body) document.body.appendChild(panel);
        if (toast && toast.parentElement !== document.body) document.body.appendChild(toast);
        if (popModal && popModal.parentElement !== document.body) document.body.appendChild(popModal);

        if (popDismiss) {
            popDismiss.addEventListener('click', () => {
                if (popModal) popModal.hidden = true;
            });
        }

        window.sokratQuickNote = function(leadId) {
            const note = prompt('أدخل ملاحظة المكالمة السريعة:\n(Enter quick call note:)');
            if (!note || !note.trim()) return;
            fetch('/leads/' + leadId + '/notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({ note: note.trim() })
            }).then(r => {
                if (r.ok) alert('تم حفظ الملاحظة بنجاح');
                else alert('تعذر حفظ الملاحظة');
            }).catch(() => alert('تعذر الاتصال بالخادم'));
        };

        const SOFTPHONE_URL = '/voip/softphone';
        const LEAD_LOOKUP_URL = '/api/leads/by-phone';
        const VOICE_ORIGIN = window.location.origin;
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Cross-tab / cross-page Broadcast Channel for call sync
        let syncChannel = null;
        try {
            if (typeof BroadcastChannel !== 'undefined') {
                syncChannel = new BroadcastChannel('sokrat_voice_crm_channel');
                syncChannel.onmessage = (e) => handleSyncMessage(e.data);
            }
        } catch (_) {}

        let panelOpen = false;
        let frameLoaded = false;
        let pendingDial = null;
        let currentIncomingCallId = null;
        let voiceState = { inCall: false, registered: false, ready: false, callId: null, timer: null, seconds: 0, remote: '' };

        if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
            console.log('[Sokrat CRM Desktop] Running with persistent background telephony.');
            if (popoutBtn) popoutBtn.style.display = 'none';
            if (reloadBtn) reloadBtn.style.display = 'none';

            window.sokratDesktop.onSoftphoneVisibility((payload) => {
                panelOpen = Boolean(payload?.visible);
            });
            window.sokratDesktop.onRegistrationStatus((payload) => {
                const st = payload?.status;
                if (st === 'REGISTERED' || st === 'online') {
                    setStatus('online');
                    voiceState.registered = true;
                } else {
                    setStatus('offline');
                    voiceState.registered = false;
                }
            });

            window.sokratDesktop.onIncomingCall((payload) => {
                currentIncomingCallId = payload?.callId || null;
                handleIncomingCall(payload?.phone || 'Unknown', currentIncomingCallId);
            });

            window.sokratDesktop.onCallState((payload) => {
                const state = payload?.state;
                if (state === 'confirmed' || state === 'in_call' || state === 'accepted') {
                    voiceState.inCall = true;
                    voiceState.callId = payload.callId;
                    voiceState.remote = payload.phone || '';
                    setStatus('incall');
                    callInfo.hidden = false;
                    quickActions.hidden = false;
                    if (labelEl) labelEl.hidden = true;
                    remoteEl.textContent = voiceState.remote;
                    hideToast();
                    startTimer();
                } else if (state === 'ended' || state === 'failed') {
                    voiceState.inCall = false;
                    voiceState.callId = null;
                    currentIncomingCallId = null;
                    voiceState.remote = '';
                    setStatus(voiceState.registered ? 'online' : 'offline');
                    callInfo.hidden = true;
                    quickActions.hidden = true;
                    if (labelEl) labelEl.hidden = false;
                    hideToast();
                    hideScreenPop();
                    stopTimer();
                } else if (state === 'ringing') {
                    setStatus('ringing');
                }
            });
        }

        function isDarkMode() {
            return document.documentElement.classList.contains('dark-mode') || 
                   (localStorage.getItem('sokrat.crm.theme') || '').includes('dark');
        }

        function syncThemeToFrame() {
            if (frame && frame.contentWindow) {
                frame.contentWindow.postMessage({
                    version: 1,
                    type: 'sokrat.voice.set_theme',
                    payload: { isDark: isDarkMode() }
                }, VOICE_ORIGIN);
            }
        }

        // Watch CRM theme changes
        const themeObserver = new MutationObserver(() => syncThemeToFrame());
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });

        function setStatus(status) {
            statusDots.forEach(dot => dot.dataset.voiceStatus = status);
        }
        function loadFreshSession() {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                frameLoaded = true;
                return;
            }
            const themeParam = isDarkMode() ? 'dark' : 'light';
            frameLoaded = false;
            frame.addEventListener('load', () => {
                frameLoaded = true;
                setTimeout(() => syncThemeToFrame(), 300);
            }, { once: true });
            frame.src = SOFTPHONE_URL + '?theme=' + themeParam + '&t=' + Date.now();
        }

        function expandPanel() {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                panelOpen = true;
                window.sokratDesktop.showSoftphone();
                try { sessionStorage.setItem('sokrat_voice_panel_open', '1'); } catch (_) {}
                return;
            }
            if (!frameLoaded || !frame.src || frame.src === 'about:blank') {
                loadFreshSession();
            }
            panel.removeAttribute('hidden');
            panelOpen = true;
            try { sessionStorage.setItem('sokrat_voice_panel_open', '1'); } catch (_) {}
            setTimeout(() => syncThemeToFrame(), 300);
        }

        function collapsePanel() {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                panelOpen = false;
                window.sokratDesktop.hideSoftphone();
                try { sessionStorage.setItem('sokrat_voice_panel_open', '0'); } catch (_) {}
                return;
            }
            panel.setAttribute('hidden', 'hidden');
            panelOpen = false;
            try { sessionStorage.setItem('sokrat_voice_panel_open', '0'); } catch (_) {}
        }

        toggle.addEventListener('click', () => {
            if (panelOpen) collapsePanel();
            else expandPanel();
        });

        if (closeBtn) closeBtn.addEventListener('click', collapsePanel);

        if (reloadBtn) {
            reloadBtn.addEventListener('click', () => {
                loadFreshSession();
            });
        }

        // Persistent Standalone Window Opener
        if (popoutBtn) {
            popoutBtn.addEventListener('click', () => {
                const width = 460;
                const height = 720;
                const left = window.screen ? Math.max(0, Math.round((window.screen.width - width) / 2)) : 100;
                const top = window.screen ? Math.max(0, Math.round((window.screen.height - height) / 2)) : 60;
                const features = `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=no`;
                const popout = window.open(SOFTPHONE_URL, 'sokratVoicePopout', features);
                if (popout) {
                    popout.focus();
                    collapsePanel();
                }
            });
        }

        if (toastDismiss) {
            toastDismiss.addEventListener('click', () => {
                toast.hidden = true;
            });
        }

        function startTimer(initialSeconds = 0) {
            if (voiceState.timer) clearInterval(voiceState.timer);
            voiceState.seconds = initialSeconds;
            const updateTicker = () => {
                const m = String(Math.floor(voiceState.seconds / 60)).padStart(2, '0');
                const s = String(voiceState.seconds % 60).padStart(2, '0');
                timerEl.textContent = m + ':' + s;
            };
            updateTicker();
            voiceState.timer = setInterval(() => {
                voiceState.seconds++;
                updateTicker();
            }, 1000);
        }

        function stopTimer() {
            if (voiceState.timer) {
                clearInterval(voiceState.timer);
                voiceState.timer = null;
            }
            timerEl.textContent = '00:00';
        }

        // Clear any stale navigation call artifacts
        try { sessionStorage.removeItem('sokrat_voice_active_call'); } catch (_) {}
        function clearActiveCallState() {
            try {
                sessionStorage.removeItem('sokrat_voice_active_call');
                if (syncChannel) {
                    syncChannel.postMessage({ type: 'CALL_ENDED' });
                }
            } catch (_) {}
        }
        function handleSyncMessage(data) {
            if (!data) return;
            if (data.type === 'CALL_ENDED') {
                voiceState.inCall = false;
                setStatus(voiceState.registered ? 'online' : 'offline');
                callInfo.hidden = true;
                quickActions.hidden = true;
                if (labelEl) labelEl.hidden = false;
                stopTimer();
            }
        }

        // Navigation guard when in-call
        window.addEventListener('beforeunload', (e) => {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                return; // Audio persists in Sokrat CRM Desktop without interruption!
            }
            if (voiceState.inCall) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Intercept internal clicks during call to offer popout window
        document.addEventListener('click', (e) => {
            if (!voiceState.inCall) return;
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                return; // Audio persists across CRM page transitions!
            }
            const link = e.target.closest('a[href]');
            if (!link || !link.href || link.href.startsWith('#') || link.href.startsWith('javascript:')) return;
            if (link.target === '_blank') return;
            if (link.origin !== location.origin) return;
            if (link.closest('#sokratLeadScreenPop') || link.closest('#sokratVoiceToast')) return;

            e.preventDefault();
            const choice = confirm('لديك مكالمة نشطة. هل تريد متابعة التنقل في الـ CRM؟\n(You have an active call. Navigating directly might disconnect audio unless opened in persistent window).');
            if (choice) {
                window.location.href = link.href;
            }
        });

        // Incoming Call Notification, Screen-Pop & Lead Match
        function maskPhoneNumber(num) {
            if (!num) return '';
            const clean = String(num).trim();
            if (clean.length < 7) return clean;
            const isPlus = clean.startsWith('+');
            const pfx = isPlus ? 5 : 4;
            const sfx = 3;
            if (clean.length <= (pfx + sfx)) return clean.slice(0, 3) + '****' + clean.slice(-2);
            return clean.slice(0, pfx) + '****' + clean.slice(-sfx);
        }

        async function handleIncomingCall(phone, callId) {
            const displayPhone = maskPhoneNumber(phone);
            setStatus('ringing');
            expandPanel();

            const popModal = document.getElementById('sokratLeadScreenPop');
            if (popModal) {
                const popContent = popModal.querySelector('[data-pop-content]');
                if (popContent) {
                    popContent.innerHTML = `
                        <div class="sokrat-screen-pop-caller">
                            <span class="phone-num">${displayPhone}</span>
                            <span class="badge-status">جاري البحث عن العميل...</span>
                        </div>
                    `;
                }
                popModal.hidden = false;
            }

            const callerEl = toast ? toast.querySelector('[data-toast-caller]') : null;
            const leadsContainer = toast ? toast.querySelector('[data-toast-leads]') : null;
            if (callerEl) callerEl.textContent = displayPhone;
            if (leadsContainer) leadsContainer.replaceChildren();

            try {
                const res = await fetch(LEAD_LOOKUP_URL + '?phone=' + encodeURIComponent(phone), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const data = await res.json();
                if (callId && currentIncomingCallId !== callId) {
                    return; // Call ended or superseded while lookup was in flight
                }
                const leads = data.leads || [];

                if (popModal) {
                    const popContent = popModal.querySelector('[data-pop-content]');
                    if (popContent) {
                        if (leads.length === 0) {
                            popContent.innerHTML = `
                                <div class="sokrat-screen-pop-caller">
                                    <span class="phone-num">${displayPhone}</span>
                                    <span class="badge-status" style="background:#fee2e2;color:#991b1b;">عميل غير مسجل</span>
                                </div>
                                <div class="sokrat-pop-lead-card" style="text-align:center;padding:16px;">
                                    <p style="margin:0 0 10px 0;font-size:13px;color:#64748b;">لا يوجد عميل مسجل بهذا الرقم في النظام</p>
                                    <a href="/leads/create?phone=${encodeURIComponent(phone)}" class="sokrat-pop-btn sokrat-pop-btn-create">
                                        <i class="bi bi-person-plus-fill"></i> إنشاء عميل جديد برقم ${phone}
                                    </a>
                                </div>
                            `;
                        } else {
                            const lead = leads[0];
                            const leadName = lead.name || phone;
                            const company = lead.company ? ` • ${lead.company}` : '';
                            const stageName = lead.stage_name || 'عميل';
                            const employee = lead.assigned_employee ? `المسؤول: ${lead.assigned_employee}` : '';
                            const branchBadge = lead.branch_name ? `<span class="badge-branch" style="background:#eff6ff;color:#2563eb;font-size:11px;font-weight:700;padding:2px 7px;border-radius:6px;border:1px solid #bfdbfe;"><i class="bi bi-geo-alt-fill"></i> ${lead.branch_name}</span>` : '';
                            const statusText = lead.is_other_branch ? `عميل مسجل (${lead.branch_name || 'فرع آخر'})` : 'عميل مسجل';
                            const statusBg = lead.is_other_branch ? '#eff6ff' : '#dcfce7';
                            const statusColor = lead.is_other_branch ? '#1e40af' : '#15803d';

                            popContent.innerHTML = `
                                <div class="sokrat-screen-pop-caller">
                                    <span class="phone-num">${displayPhone}</span>
                                    <span class="badge-status" style="background:${statusBg};color:${statusColor};">${statusText}</span>
                                </div>
                                <div class="sokrat-pop-lead-card">
                                    <div class="sokrat-pop-lead-name">${leadName}${company}</div>
                                    <div class="sokrat-pop-lead-meta" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                        <span class="sokrat-pop-stage-badge">${stageName}</span>
                                        ${branchBadge}
                                        ${employee ? `<span><i class="bi bi-person-badge"></i> ${employee}</span>` : ''}
                                    </div>
                                </div>
                                <div class="sokrat-pop-actions">
                                    <a href="${lead.url}" target="_blank" class="sokrat-pop-btn sokrat-pop-btn-primary">
                                        <i class="bi bi-box-arrow-up-right"></i> عرض ملف العميل وسجل المتابعات
                                    </a>
                                    ${!lead.is_other_branch ? `
                                    <button type="button" class="sokrat-pop-btn sokrat-pop-btn-secondary" onclick="window.sokratQuickNote('${lead.id}')">
                                        <i class="bi bi-pencil-square"></i> إضافة ملاحظة
                                    </button>` : ''}
                                </div>
                            `;
                        }
                    }
                }

                if (leadsContainer) {
                    if (leads.length === 0) {
                        const createBtn = document.createElement('a');
                        createBtn.className = 'sokrat-voice-lead-link create';
                        createBtn.href = '/leads/create?phone=' + encodeURIComponent(phone);
                        createBtn.target = '_self';
                        
                        createBtn.innerHTML = '<i class="bi bi-person-plus-fill"></i> {{ __("crm.add_lead") ?? "إنشاء جهة اتصال جديدة" }}';
                        leadsContainer.appendChild(createBtn);
                    } else {
                        leads.forEach(lead => {
                            const leadLink = document.createElement('a');
                            leadLink.className = 'sokrat-voice-lead-link';
                            leadLink.href = lead.url;
                            leadLink.target = '_self';
                            
                            leadLink.innerHTML = '<i class="bi bi-person-fill"></i> ' + lead.name + ' <small style="color:#64748b;margin-inline-start:auto;">#' + lead.id + '</small>';
                            leadsContainer.appendChild(leadLink);
                        });
                    }
                }
            } catch (err) {
                console.error('[Voice ScreenPop] Lookup error:', err);
            }

            if (toast) toast.hidden = false;

            // Browser push notification if tab is hidden
            if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
                new Notification('Sokrat Voice: ' + phone, {
                    body: 'مكالمة واردة من ' + phone,
                    icon: '/favicon.png',
                    tag: 'sokrat-voice-incoming'
                });
            }
        }

        function hideToast() {
            if (toast) toast.hidden = true;
        }

        function hideScreenPop() {
            const pop = document.getElementById('sokratLeadScreenPop');
            if (pop) pop.hidden = true;
        }

        // postMessage Event Listener from Voice Iframe
        window.addEventListener('message', (e) => {
            if (e.source !== frame?.contentWindow || e.origin !== VOICE_ORIGIN) return;
            const msg = e.data;
            if (!msg || msg.version !== 1 || typeof msg.type !== 'string') return;

            const type = msg.type;
            const payload = msg.payload && typeof msg.payload === 'object' ? msg.payload : {};

            switch (type) {
                case 'sokrat.voice.ready':
                    voiceState.ready = true;
                    syncThemeToFrame();
                    break;

                case 'sokrat.voice.registration': {
                    const st = payload.state;
                    if (st === 'REGISTERED') {
                        setStatus('online');
                        voiceState.registered = true;
                        if (pendingDial && frame && frame.contentWindow) {
                            const phoneToDial = pendingDial;
                            pendingDial = null;
                            setTimeout(() => {
                                frame.contentWindow.postMessage({
                                    version: 1,
                                    type: 'sokrat.voice.dial',
                                    requestId: Date.now().toString(36),
                                    payload: { phone: phoneToDial, autoCall: true }
                                }, VOICE_ORIGIN);
                            }, 350);
                        }
                    } else if (st === 'DISCONNECTED' || st === 'AUTH_FAILED') {
                        setStatus('offline');
                        voiceState.registered = false;
                        if (pendingDial) {
                            showToast(st === 'AUTH_FAILED' ? 'فشل تسجيل الهاتف في السيرفر' : 'الهاتف غير متصل حالياً', 4000);
                            pendingDial = null;
                        }
                    }
                    break;
                }

                case 'sokrat.voice.incoming':
                    currentIncomingCallId = payload.callId || null;
                    handleIncomingCall(payload.phone || 'Unknown', currentIncomingCallId);
                    break;

                case 'sokrat.voice.call_state': {
                    const state = payload.state;
                    if (state === 'confirmed' || state === 'in_call' || state === 'accepted') {
                        voiceState.inCall = true;
                        voiceState.callId = payload.callId;
                        voiceState.remote = payload.phone || '';
                        setStatus('incall');
                        callInfo.hidden = false;
                        quickActions.hidden = false;
                        if (labelEl) labelEl.hidden = true;
                        remoteEl.textContent = voiceState.remote;
                        hideToast();
                        startTimer();
                        saveActiveCallState();
                    } else if (state === 'ended') {
                        voiceState.inCall = false;
                        voiceState.callId = null;
                        currentIncomingCallId = null;
                        voiceState.remote = '';
                        setStatus(voiceState.registered ? 'online' : 'offline');
                        callInfo.hidden = true;
                        quickActions.hidden = true;
                        if (labelEl) labelEl.hidden = false;
                        hideToast();
                        hideScreenPop();
                        stopTimer();
                        clearActiveCallState();
                    } else if (state === 'ringing') {
                        setStatus('ringing');
                    }
                    break;
                }
            }
        });

        window.__sokratVoiceTriggerDial = function(cleanPhone, leadName) {
            expandPanel();
            if (leadName && remoteEl) remoteEl.textContent = leadName;

            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                window.sokratDesktop.dial(cleanPhone, leadName);
                return;
            }

            if (voiceState.registered && frame && frame.contentWindow) {
                frame.contentWindow.postMessage({
                    version: 1,
                    type: 'sokrat.voice.dial',
                    requestId: Date.now().toString(36),
                    payload: { phone: cleanPhone, autoCall: true }
                }, VOICE_ORIGIN);
            } else {
                pendingDial = cleanPhone;
                if (typeof showToast === 'function') {
                    showToast('جاري تجهيز الهاتف والاتصال...', 3500);
                }
            }
        };

        if (window.__sokratPendingDial) {
            window.__sokratVoiceTriggerDial(window.__sokratPendingDial, window.__sokratPendingLeadName);
            window.__sokratPendingDial = null;
            window.__sokratPendingLeadName = null;
        }

        window.addEventListener('sokrat:voice-dial', (e) => {
            if (e.detail?.phone && typeof window.__sokratVoiceTriggerDial === 'function') {
                window.__sokratVoiceTriggerDial(e.detail.phone, e.detail.leadName);
            }
        });
// Handled authoritatively by crm-sidebar.js router

        // Quick Action Relays
        const muteBtn = dock.querySelector('[data-voice-mute]');
        const hangupBtn = dock.querySelector('[data-voice-hangup]');
        if (muteBtn) {
            muteBtn.addEventListener('click', () => {
                if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                    window.sokratDesktop.callAction('toggle_mute', voiceState.callId);
                    return;
                }
                if (frame && frame.contentWindow) {
                    frame.contentWindow.postMessage({ version: 1, type: 'sokrat.voice.toggle_mute' }, VOICE_ORIGIN);
                }
            });
        }
        if (hangupBtn) {
            hangupBtn.addEventListener('click', () => {
                if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                    window.sokratDesktop.callAction('hangup', voiceState.callId);
                    return;
                }
                if (frame && frame.contentWindow) {
                    frame.contentWindow.postMessage({ version: 1, type: 'sokrat.voice.hangup' }, VOICE_ORIGIN);
                }
                // Fallback: If frame was empty or no active call responded within 400ms, force-clear dock in-call state
                setTimeout(() => {
                    if (voiceState.inCall) {
                        voiceState.inCall = false;
                        voiceState.callId = null;
                        voiceState.remote = '';
                        setStatus(voiceState.registered ? 'online' : 'offline');
                        callInfo.hidden = true;
                        quickActions.hidden = true;
                        if (labelEl) labelEl.hidden = false;
                        hideToast();
                        hideScreenPop();
                        stopTimer();
                        clearActiveCallState();
                    }
                }, 400);
            });
        }

        // Auto-reconnect trigger when switching tabs inside CRM
        const handleTabSwitch = () => {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                return;
            }
            if (document.visibilityState === 'visible') {
                if (!frameLoaded || !frame.src || frame.src === 'about:blank') {
                    loadFreshSession();
                } else if (frame && frame.contentWindow && !voiceState.inCall) {
                    frame.contentWindow.postMessage({
                        version: 1,
                        type: 'sokrat.voice.resume'
                    }, VOICE_ORIGIN);
                }
            }
        };
        document.addEventListener('visibilitychange', handleTabSwitch);
        window.addEventListener('focus', handleTabSwitch);

        // Auto-boot softphone session in background: commented out in favor of MicroSIP
        /*
        try {
            if (window.sokratDesktop && window.sokratDesktop.isDesktop) {
                const savedPanelOpen = sessionStorage.getItem('sokrat_voice_panel_open') === '1';
                if (savedPanelOpen) {
                    expandPanel();
                }
            } else {
                const savedPanelOpen = sessionStorage.getItem('sokrat_voice_panel_open') === '1';
                if (savedPanelOpen) {
                    expandPanel();
                } else {
                    loadFreshSession();
                }
            }
        } catch (_) {
            if (!window.sokratDesktop || !window.sokratDesktop.isDesktop) {
                loadFreshSession();
            }
        }
        */
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSokratVoice);
    } else {
        initSokratVoice();
    }
})();
</script>
@endif

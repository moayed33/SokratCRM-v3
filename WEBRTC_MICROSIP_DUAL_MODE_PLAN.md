# SokratCRM v3: Dual-Mode Telephony Architecture (WebRTC & MicroSIP)

## 1. Architecture Overview

SokratCRM operates in two mutually exclusive telephony modes, determined dynamically per authenticated user by the `voip_extension` database field:

```
                           ┌───────────────────────────────┐
                           │      User Authentication      │
                           └───────────────┬───────────────┘
                                           │
                              Has voip_extension assigned?
                                           │
                           ┌───────────────┴───────────────┐
                           ▼                               ▼
                         [NO]                            [YES]
             (e.g., admin, empty ext)             (e.g., test, Ext 150)
                           │                               │
                 ┌─────────┴─────────┐           ┌─────────┴─────────┐
                 │   MicroSIP Mode   │           │   WebRTC Mode     │
                 └─────────┬─────────┘           └─────────┬─────────┘
                           │                               │
         • Floating Pill: HIDDEN                 • Floating Pill: VISIBLE
           (Zero DOM elements, zero iframe,      • Shows Ext "150" & live status dot
            zero ticket polling in background)   • Iframe auto-boots & authenticates
         • Call Buttons:                           directly into Ext 150 via WebRTC
           Trigger OS `tel:<number>`             • Call Buttons:
           (Opens native MicroSIP client)          Dispatch dial directly to embedded
                                                   Sokrat Voice WebRTC softphone
```

---

## 2. Core Behaviors

### Mode A: MicroSIP (User has NO extension assigned)
- **Floating Dock & Pill**: Completely suppressed. Zero WebRTC iframe, zero background polling, zero memory footprint.
- **Click-to-Call Buttons**: Every telephone icon, dial button, followup trigger, and phone link across:
  - Kanban Board (`kanban.blade.php`)
  - Lead Index & Lead Details (`leads/index.blade.php`, `leads/show.blade.php`)
  - Followups (`leads/followups/index.blade.php`)
  - Collections & Escalations (`collections/show.blade.php`, `collections/escalations/show.blade.php`)
  - Task Lists & Statuses (`tasks/status.blade.php`, `tasks/_lead_task_card.blade.php`)
  - Calendar Event Details (`calendar/index.blade.php`)
  Triggers the operating system's registered `tel:<number>` protocol, immediately focusing and dialing via the native desktop **MicroSIP** client.

### Mode B: WebRTC Softphone (User HAS an extension assigned, e.g. Ext 150)
- **Floating Dock & Pill**: Visible in the bottom-right corner showing the assigned extension (e.g. `150`) with an active online/offline status dot.
- **Embedded WebRTC Console**: The floating softphone panel auto-connects to Sokrat Voice over WebRTC on extension 150 using a server-issued session ticket.
- **Click-to-Call Buttons**: Clicking any call button in the CRM dispatches the dial command directly into the embedded Sokrat Voice softphone via `postMessage`, automatically initiating the WebRTC audio session through Asterisk.

---

## 3. Implementation Steps

### Phase 1: User & Database Configuration
1. **Assign Extension to Target User**:
   - Set `voip_extension = '150'` for user `test` (ID 18).
   - Keep `admin` (ID 1) with `voip_extension = NULL` for MicroSIP testing.
2. **User Management Form Audit** (`resources/views/settings/users/_form.blade.php`):
   - Ensure the VoIP extension input allows assigning an extension (enabling WebRTC) or clearing it (enabling MicroSIP).

### Phase 2: Reverse Proxy & WebRTC Infrastructure
1. **Apache VirtualHosts** (`/etc/apache2/sites-available/crm-v3.conf` & `crm-v3-ssl.conf`):
   - Upstream PBX host: `100.110.36.17`
   - Sokrat Voice WebRTC interface: `https://100.110.36.17:8443/`
   - Asterisk SIP WebSocket: `ws://100.110.36.17:8088/ws`
   - Proxy configuration:
     ```apache
     SSLProxyEngine on
     SSLProxyVerify none
     SSLProxyCheckPeerCN off
     SSLProxyCheckPeerName off
     SSLProxyCheckPeerExpire off

     # Sokrat Voice WebRTC Softphone Proxy
     ProxyPreserveHost Off
     ProxyPass /phone/ https://100.110.36.17:8443/
     ProxyPassReverse /phone/ https://100.110.36.17:8443/

     # Asterisk SIP WebSocket Proxy (WSS -> WS)
     RewriteEngine On
     RewriteRule ^/phone$ /phone/ [R=301,L]
     RewriteCond %{HTTP:Upgrade} =websocket [NC]
     RewriteCond %{HTTP:Connection} upgrade [NC]
     RewriteRule ^/ws$ ws://100.110.36.17:8088/ws [P,L]
     ```

### Phase 3: Dynamic View Gating & Universal Router
1. **Blade Gating (`voice-dock.blade.php`)**:
   - Render the floating dock HTML markup only if `!empty(Auth::user()->voip_extension)`.
   - Export context flags to the browser window:
     ```html
     <script>
       window.__crmTelephonyMode = '{{ !empty(Auth::user()->voip_extension) ? "webrtc" : "microsip" }}';
       window.__crmUserExtension = '{{ Auth::user()->voip_extension ?? "" }}';
     </script>
     ```
2. **Universal Click-to-Call Router (`public/crm-sidebar.js`)**:
   - If `window.__crmTelephonyMode === 'webrtc'`:
     - Intercept clicks on `[data-voice-dial]` in capture phase, prevent default, and dispatch to Sokrat Voice (`window.__sokratVoiceTriggerDial`).
   - If `window.__crmTelephonyMode === 'microsip'`:
     - Trigger OS `tel:<number>` protocol to open MicroSIP directly.

---

## 4. Verification Matrix

| Test Scenario | Active User | Expected UI State | Expected Click Action |
|---|---|---|---|
| **No Extension** | `admin` (Ext `NULL`) | Floating pill is **hidden** across all views. | Call button opens **MicroSIP** (`tel:<number>`). |
| **With Extension** | `test` (Ext `150`) | Floating pill **appears** in bottom-right (`Ext 150`). | Call button dials via **Sokrat WebRTC softphone**. |
| **Switching Mode** | Any User | Updating extension in `Settings > Users` immediately flips that user between MicroSIP and WebRTC mode. |

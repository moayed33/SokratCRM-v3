@extends('leads.transfer-layout')

@section('title', __('crm.notification_preferences'))
@section('page-title', __('crm.notification_preferences'))
@section('page-description', __('crm.notification_preferences_description'))

@section('top-actions')
 <a class="btn soft" href="{{ route('dashboard') }}">
  <i class="bi bi-arrow-return-right" aria-hidden="true"></i>
  {{ __('crm.back_to_dashboard') }}
 </a>
@endsection

@push('styles')
<style>
 .notification-preferences{--np-ink:#182033;--np-muted:#697386;--np-line:#e4e8ef;max-width:1080px;margin-inline:auto}
 .np-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:18px;align-items:start}
 .np-panel{overflow:hidden;border:1px solid var(--np-line);border-radius:15px;background:#fff;box-shadow:0 12px 32px rgba(18,32,51,.07)}
 .np-panel+.np-panel{margin-top:18px}
 .np-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:20px 22px;border-bottom:1px solid var(--np-line);background:#fafbfc}
 .np-panel-head h2{margin:0;color:var(--np-ink);font-size:18px}.np-panel-head p{margin:6px 0 0;max-width:68ch;color:var(--np-muted);font-size:12px;line-height:1.7}
 .np-panel-body{padding:22px}.np-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
 .np-field{display:grid;gap:7px}.np-field.full{grid-column:1/-1}.np-field label{color:#354155;font-size:12px;font-weight:800}
 .np-field input,.np-field select{width:100%;min-height:43px;padding:0 12px;border:1px solid #cfd6df;border-radius:10px;background:#fff;color:var(--np-ink);font:inherit;font-size:13px}
 .np-field input:focus,.np-field select:focus{border-color:#dc2637;outline:3px solid rgba(220,38,55,.13)}
 .np-field small{color:var(--np-muted);font-size:10px;line-height:1.55}
 .np-channel-list{display:grid}.np-channel{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:center;padding:15px 0;border-bottom:1px solid #edf0f4}.np-channel:last-child{border-bottom:0}
 .np-channel-main{display:flex;align-items:flex-start;gap:12px}.np-channel-icon{width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;border-radius:10px;background:#f1f3f7;color:#48556a;font-size:16px}
 .np-channel strong,.np-channel small{display:block}.np-channel strong{color:var(--np-ink);font-size:13px}.np-channel small{margin-top:4px;color:var(--np-muted);font-size:10px;line-height:1.55}
 .np-channel-state{display:inline-flex;align-items:center;gap:7px}.np-channel-state>span{padding:3px 8px;border-radius:999px;background:#f1f3f7;color:#64748b;font-size:9px;font-weight:900}.np-channel-state>span.available{background:#eaf7ef;color:#167744}
 .np-switch{position:relative;width:42px;height:24px;display:inline-block}.np-switch input{position:absolute;opacity:0}.np-switch span{position:absolute;inset:0;border-radius:999px;background:#cbd2dc;cursor:pointer;transition:background .18s}.np-switch span:after{content:"";position:absolute;top:3px;inset-inline-start:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 2px 7px rgba(18,32,51,.2);transition:transform .18s}.np-switch input:checked+span{background:#dc2637}.np-switch input:checked+span:after{transform:translateX(18px)}[dir=rtl] .np-switch input:checked+span:after{transform:translateX(-18px)}
 .np-consent{display:flex;align-items:flex-start;gap:9px;margin-top:14px;padding:12px;border:1px solid #ead9b5;border-radius:10px;background:#fffbef;color:#76551a;font-size:11px;line-height:1.65}.np-consent input{margin-top:3px}
 .np-actions{position:sticky;top:18px}.np-summary{padding:20px}.np-summary h2{margin:0 0 14px;font-size:17px}.np-summary-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid #edf0f4;color:var(--np-muted);font-size:11px}.np-summary-row:last-child{border-bottom:0}.np-summary-row strong{color:var(--np-ink)}
 .np-save{width:100%;min-height:44px;margin-top:15px;border:0;border-radius:10px;background:#dc2637;color:#fff;font:inherit;font-size:13px;font-weight:900;cursor:pointer}.np-save:hover{background:#bd1e2e}.np-save:focus-visible,.np-test:focus-visible,.np-push-btn:focus-visible{outline:3px solid rgba(220,38,55,.2);outline-offset:2px}
 .np-test-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px}.np-test{min-height:36px;border:1px solid #d7dde6;border-radius:9px;background:#fff;color:#48556a;font:inherit;font-size:10px;font-weight:800;cursor:pointer}.np-test:disabled{opacity:.45;cursor:not-allowed}.np-test:hover:not(:disabled){border-color:#e7a8af;color:#bd1e2e}
 .np-push-controls{display:flex;align-items:center;flex-wrap:wrap;gap:9px}.np-push-btn{min-height:38px;padding:0 13px;border:1px solid #d7dde6;border-radius:9px;background:#fff;color:#354155;font:inherit;font-size:11px;font-weight:800;cursor:pointer}.np-push-btn.primary{border-color:#dc2637;background:#dc2637;color:#fff}.np-push-btn:disabled{opacity:.45;cursor:not-allowed}.np-push-status{margin-top:10px;color:var(--np-muted);font-size:10px;line-height:1.6}.np-push-status.error{color:#b42331}.np-push-status.success{color:#167744}
 @media(max-width:900px){.np-layout{grid-template-columns:1fr}.np-actions{position:static}.np-fields{grid-template-columns:1fr}}
 @media(max-width:560px){.np-panel-head,.np-panel-body,.np-summary{padding:17px}.np-channel{grid-template-columns:1fr}.np-channel-state{justify-content:space-between}.np-test-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<form class="notification-preferences" method="POST" action="{{ route('v2.notifications.preferences.update') }}">
 @csrf
 @method('PATCH')
 <div class="np-layout">
  <main>
   <section class="np-panel">
    <header class="np-panel-head">
     <div><h2>{{ __('crm.notification_profile') }}</h2><p>{{ __('crm.notification_profile_description') }}</p></div>
     <i class="bi bi-person-gear" aria-hidden="true"></i>
    </header>
    <div class="np-panel-body np-fields">
     <div class="np-field">
      <label for="notificationLocale">{{ __('crm.language') }}</label>
      <select id="notificationLocale" name="locale">
       <option value="ar" @selected(old('locale', auth()->user()->locale) === 'ar')>{{ __('crm.arabic') }}</option>
       <option value="en" @selected(old('locale', auth()->user()->locale) === 'en')>{{ __('crm.english') }}</option>
      </select>
     </div>
     <div class="np-field">
      <label for="notificationTimezone">{{ __('crm.timezone') }}</label>
      <select id="notificationTimezone" name="timezone">
       @foreach($timezones as $timezone)
        <option value="{{ $timezone }}" @selected(old('timezone', auth()->user()->timezone) === $timezone)>{{ $timezone }}</option>
       @endforeach
      </select>
     </div>
     <div class="np-field full">
      <label for="notificationMobile">{{ __('crm.notification_mobile_phone') }}</label>
      <input id="notificationMobile" name="mobile_phone" value="{{ old('mobile_phone', auth()->user()->mobile_phone) }}" dir="ltr" inputmode="tel" placeholder="+201001234567">
      <small>{{ __('crm.notification_mobile_phone_hint') }}</small>
     </div>
    </div>
   </section>

   <section class="np-panel">
    <header class="np-panel-head"><div><h2>{{ __('crm.notification_channels') }}</h2><p>{{ __('crm.notification_channels_description') }}</p></div><i class="bi bi-broadcast" aria-hidden="true"></i></header>
    <div class="np-panel-body np-channel-list">
     @php
      $channelRows = [
       'in_app' => ['database', 'bi-bell', __('crm.notification_channel_in_app'), __('crm.notification_channel_in_app_desc')],
       'mail' => ['mail', 'bi-envelope', __('crm.email'), __('crm.notification_channel_mail_desc')],
       'push' => ['push', 'bi-browser-chrome', __('crm.notification_channel_push'), __('crm.notification_channel_push_desc')],
       'sms' => ['sms', 'bi-chat-square-text', __('crm.notification_channel_sms'), __('crm.notification_channel_sms_desc')],
       'whatsapp' => ['whatsapp', 'bi-whatsapp', __('crm.whatsapp'), __('crm.notification_channel_whatsapp_desc')],
      ];
     @endphp
     @foreach($channelRows as $field => [$configKey, $icon, $label, $description])
      @php $available = (bool)($systemChannels[$configKey] ?? false); $property = $field.'_enabled'; @endphp
      <div class="np-channel">
       <div class="np-channel-main"><span class="np-channel-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span><div><strong>{{ $label }}</strong><small>{{ $description }}</small></div></div>
       <div class="np-channel-state">
        <span class="{{ $available ? 'available' : '' }}">{{ $available ? __('crm.available') : __('crm.not_configured') }}</span>
        <label class="np-switch" title="{{ $label }}">
         <input type="checkbox" name="{{ $property }}" value="1" @checked(old($property, $preference->{$property})) @disabled(!$available)>
         <span></span>
        </label>
       </div>
      </div>
     @endforeach
     <label class="np-consent">
      <input type="checkbox" name="whatsapp_opt_in" value="1" @checked(old('whatsapp_opt_in', auth()->user()->whatsapp_opt_in_at !== null))>
      <span>{{ __('crm.notification_whatsapp_consent') }}</span>
     </label>
    </div>
   </section>

   <section class="np-panel">
    <header class="np-panel-head"><div><h2>{{ __('crm.notification_schedule') }}</h2><p>{{ __('crm.notification_schedule_description') }}</p></div><i class="bi bi-moon-stars" aria-hidden="true"></i></header>
    <div class="np-panel-body np-fields">
     <div class="np-field"><label for="quietStart">{{ __('crm.quiet_hours_start') }}</label><input id="quietStart" type="time" name="quiet_hours_start" value="{{ old('quiet_hours_start', $preference->quiet_hours_start ? substr((string)$preference->quiet_hours_start, 0, 5) : '') }}"></div>
     <div class="np-field"><label for="quietEnd">{{ __('crm.quiet_hours_end') }}</label><input id="quietEnd" type="time" name="quiet_hours_end" value="{{ old('quiet_hours_end', $preference->quiet_hours_end ? substr((string)$preference->quiet_hours_end, 0, 5) : '') }}"></div>
     <div class="np-field"><label for="minimumPriority">{{ __('crm.minimum_external_priority') }}</label><select id="minimumPriority" name="minimum_external_priority">@foreach(['normal','important','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('minimum_external_priority', $preference->minimum_external_priority) === $priority)>{{ __('crm.notification_priority_'.$priority) }}</option>@endforeach</select></div>
     <div class="np-field"><label for="dailyDigest">{{ __('crm.email_delivery') }}</label><select id="dailyDigest" name="daily_email_digest"><option value="0" @selected(!old('daily_email_digest', $preference->daily_email_digest))>{{ __('crm.notification_immediate_email') }}</option><option value="1" @selected((bool)old('daily_email_digest', $preference->daily_email_digest))>{{ __('crm.notification_daily_digest') }}</option></select></div>
    </div>
   </section>

   <section class="np-panel" id="browserPushPanel" data-vapid="{{ $vapidPublicKey }}" data-store-url="{{ route('v2.notifications.push.store') }}" data-destroy-url="{{ route('v2.notifications.push.destroy') }}" data-test-url="{{ route('v2.notifications.preferences.test') }}" data-csrf="{{ csrf_token() }}">
    <header class="np-panel-head"><div><h2>{{ __('crm.browser_push_setup') }}</h2><p>{{ __('crm.browser_push_setup_description') }}</p></div><i class="bi bi-window-stack" aria-hidden="true"></i></header>
    <div class="np-panel-body">
     <div class="np-push-controls"><button class="np-push-btn primary" id="enableBrowserPush" type="button" @disabled(!$systemChannels['push'] || $vapidPublicKey === '')>{{ __('crm.enable_browser_push') }}</button><button class="np-push-btn" id="disableBrowserPush" type="button">{{ __('crm.disable_browser_push') }}</button></div>
     <div class="np-push-status" id="browserPushStatus">{{ trans_choice('crm.browser_push_subscriptions_count', $pushSubscriptionsCount, ['count' => $pushSubscriptionsCount]) }}</div>
    </div>
   </section>
  </main>

  <aside class="np-actions">
   <section class="np-panel np-summary">
    <h2>{{ __('crm.notification_delivery_test') }}</h2>
    <div class="np-summary-row"><span>{{ __('crm.email') }}</span><strong>{{ auth()->user()->email ?: __('crm.not_registered') }}</strong></div>
    <div class="np-summary-row"><span>{{ __('crm.notification_mobile_phone') }}</span><strong dir="ltr">{{ auth()->user()->mobile_phone ?: __('crm.not_registered') }}</strong></div>
    <div class="np-test-grid">
     @foreach(['database','mail','push','sms','whatsapp'] as $channel)
      <button class="np-test" type="button" data-test-channel="{{ $channel }}" @disabled(!($systemChannels[$channel] ?? false))>{{ __('crm.test_channel_'.$channel) }}</button>
     @endforeach
    </div>
    <button class="np-save" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> {{ __('crm.save_preferences') }}</button>
   </section>
  </aside>
 </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
 const panel = document.getElementById('browserPushPanel');
 if (!panel) return;
 const status = document.getElementById('browserPushStatus');
 const csrf = panel.dataset.csrf;
 const request = async (url, method, data) => {
  const response = await fetch(url, {method, credentials:'same-origin', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}, body:JSON.stringify(data || {})});
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.message || @json(__('crm.notification_request_failed')));
  return payload;
 };
 const keyBytes = value => {
  const padding = '='.repeat((4 - value.length % 4) % 4);
  const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
  return Uint8Array.from(atob(base64), char => char.charCodeAt(0));
 };
 const setStatus = (message, type = '') => { status.textContent = message; status.className = 'np-push-status ' + type; };
 document.getElementById('enableBrowserPush').addEventListener('click', async () => {
  try {
   if (!('serviceWorker' in navigator) || !('PushManager' in window)) throw new Error(@json(__('crm.browser_push_not_supported')));
   const permission = await Notification.requestPermission();
   if (permission !== 'granted') throw new Error(@json(__('crm.browser_push_permission_denied')));
   const registration = await navigator.serviceWorker.register('/crm-notification-sw.js');
   const existing = await registration.pushManager.getSubscription();
   const subscription = existing || await registration.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:keyBytes(panel.dataset.vapid)});
   const data = subscription.toJSON();
   data.contentEncoding = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
   await request(panel.dataset.storeUrl, 'POST', data);
   setStatus(@json(__('crm.browser_push_enabled')), 'success');
  } catch (error) { setStatus(error.message, 'error'); }
 });
 document.getElementById('disableBrowserPush').addEventListener('click', async () => {
  try {
   const registration = await navigator.serviceWorker.getRegistration('/crm-notification-sw.js');
   const subscription = registration ? await registration.pushManager.getSubscription() : null;
   if (subscription) { await request(panel.dataset.destroyUrl, 'DELETE', {endpoint:subscription.endpoint}); await subscription.unsubscribe(); }
   setStatus(@json(__('crm.browser_push_disabled')), 'success');
  } catch (error) { setStatus(error.message, 'error'); }
 });
 document.querySelectorAll('[data-test-channel]').forEach(button => button.addEventListener('click', async () => {
  button.disabled = true;
  try { const payload = await request(panel.dataset.testUrl, 'POST', {channel:button.dataset.testChannel}); setStatus(payload.message, 'success'); }
  catch (error) { setStatus(error.message, 'error'); }
  finally { button.disabled = false; }
 }));
})();
</script>
@endpush

@once('crm-notification-center')
<link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v={{ filemtime(public_path('crm-notifications.css')) }}">

<div
 id="crmNotificationCenter"
 class="crm-notification-center"
 data-csrf="{{ csrf_token() }}"
 data-locale="{{ app()->getLocale() }}"
 data-index-url="{{ route('v2.notifications.index') }}"
 data-count-url="{{ route('v2.notifications.unread-count') }}"
 data-read-all-url="{{ route('v2.notifications.read-all') }}"
 data-base-url="{{ url('/notifications') }}"
 data-preferences-url="{{ route('v2.notifications.preferences.edit') }}"
 data-poll-seconds="{{ max(15, (int) config('crm_notifications.poll_seconds', 60)) }}"
 data-label-loading="{{ __('crm.notification_loading') }}"
 data-label-empty="{{ __('crm.notification_empty') }}"
 data-label-error="{{ __('crm.notification_load_error') }}"
 data-label-open="{{ __('crm.notification_open') }}"
 data-label-dismiss="{{ __('crm.notification_dismiss') }}"
 data-label-snooze="{{ __('crm.notification_snooze') }}"
 data-label-snoozed="{{ __('crm.notification_snoozed') }}"
 data-label-load-more="{{ __('crm.load_more') }}"
>
 <div class="crm-notification-backdrop" data-notification-close hidden></div>
 <section
  class="crm-notification-drawer"
  id="crmNotificationDrawer"
  role="dialog"
  aria-modal="true"
  aria-labelledby="crmNotificationTitle"
  aria-hidden="true"
 >
  <header class="crm-notification-head">
   <div>
    <h2 id="crmNotificationTitle">{{ __('crm.notifications') }}</h2>
    <p>{{ __('crm.notification_center_subtitle') }}</p>
   </div>
   <button class="crm-notification-close" type="button" data-notification-close aria-label="{{ __('crm.close') }}">
    <i class="bi bi-x-lg" aria-hidden="true"></i>
   </button>
  </header>

  <div class="crm-notification-controls">
   <div class="crm-notification-tabs" role="tablist" aria-label="{{ __('crm.notification_filter') }}">
    <button class="active" type="button" data-notification-filter="unread" role="tab" aria-selected="true">{{ __('crm.unread') }}</button>
    <button type="button" data-notification-filter="all" role="tab" aria-selected="false">{{ __('crm.all') }}</button>
   </div>
   <button class="crm-notification-read-all" type="button" id="crmNotificationReadAll">{{ __('crm.mark_all_read') }}</button>
  </div>

  <div class="crm-notification-list" id="crmNotificationList" aria-live="polite"></div>

  <footer class="crm-notification-footer">
   <button type="button" id="crmNotificationLoadMore" hidden>{{ __('crm.load_more') }}</button>
   <a href="{{ route('v2.notifications.preferences.edit') }}">
    <i class="bi bi-sliders" aria-hidden="true"></i>
    {{ __('crm.notification_preferences') }}
   </a>
  </footer>
 </section>

 <div class="crm-notification-toast" id="crmNotificationToast" role="status" aria-live="polite" hidden>
  <strong id="crmNotificationToastTitle"></strong>
  <span id="crmNotificationToastBody"></span>
 </div>
</div>
<script>
(() => {
    const el = document.getElementById('crmNotificationCenter');
    if (el && el.parentElement && el.parentElement !== document.body) {
        document.body.appendChild(el);
    }
})();
</script>

<script src="{{ asset('crm-notifications.js') }}?v={{ filemtime(public_path('crm-notifications.js')) }}" defer></script>
@endonce

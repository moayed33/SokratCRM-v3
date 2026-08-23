@extends('settings.layout')

@section('title', __('crm.notification_rules'))
@section('heading', __('crm.notification_rules'))
@section('subheading', __('crm.notification_rules_description'))

@push('head')
<style>
 .notification-health{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.notification-health .stat-card{padding:16px}.notification-health b{font-size:24px}.notification-health .failed b{color:#b42332}
 .rule-name{display:flex;align-items:flex-start;gap:9px}.rule-name i{margin-top:4px;color:#9aa4b2}.rule-name strong,.rule-name small{display:block}.rule-name small{max-width:38ch;margin-top:4px;color:var(--muted);font-size:11px;line-height:1.5}
 .channel-list{display:flex;align-items:center;flex-wrap:wrap;gap:5px}.channel-list .badge{padding:4px 7px;font-size:10px}.channel-list .unavailable{background:#fff0f1;color:#a92030}
 .recipient-summary{color:#566175;font-size:11px;line-height:1.6}.offset{font-variant-numeric:tabular-nums;white-space:nowrap}.rule-actions{display:flex;gap:6px;flex-wrap:wrap}.rule-actions form{margin:0}.rule-actions .btn{min-height:32px;padding:0 9px;font-size:11px}
 .rule-empty{padding:50px 20px;text-align:center}.rule-empty i{display:block;margin-bottom:12px;color:#9aa4b2;font-size:34px}.rule-empty h3{margin:0}.rule-empty p{margin:7px auto 16px;max-width:50ch;color:var(--muted);line-height:1.7}
 @media(max-width:900px){.notification-health{grid-template-columns:repeat(2,1fr)}}
 @media(max-width:540px){.notification-health{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="notification-health">
 @foreach([
  'queued' => __('crm.notification_status_queued'),
  'delivered' => __('crm.notification_status_delivered'),
  'failed' => __('crm.notification_status_failed'),
  'suppressed' => __('crm.notification_status_suppressed'),
 ] as $status => $label)
  <article class="stat-card {{ $status }}"><span>{{ $label }} · {{ __('crm.last_24_hours') }}</span><b>{{ number_format((int)($deliveryStats[$status] ?? 0)) }}</b></article>
 @endforeach
</section>

<section class="panel">
 <div class="panel-head">
  <div><h2>{{ __('crm.active_notification_rules') }}</h2><p>{{ __('crm.notification_rules_table_hint') }}</p></div>
  <a class="btn primary" href="{{ route('v2.settings.notifications.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('crm.add_notification_rule') }}</a>
 </div>

 @if($rules->isEmpty())
  <div class="rule-empty"><i class="bi bi-bell-slash" aria-hidden="true"></i><h3>{{ __('crm.no_notification_rules') }}</h3><p>{{ __('crm.no_notification_rules_description') }}</p><a class="btn primary" href="{{ route('v2.settings.notifications.create') }}">{{ __('crm.add_notification_rule') }}</a></div>
 @else
  <div class="table-wrap">
   <table>
    <thead><tr><th>{{ __('crm.rule') }}</th><th>{{ __('crm.trigger') }}</th><th>{{ __('crm.channels') }}</th><th>{{ __('crm.recipients') }}</th><th>{{ __('crm.status') }}</th><th>{{ __('crm.actions') }}</th></tr></thead>
    <tbody>
     @foreach($rules as $rule)
      <tr>
       <td><div class="rule-name"><i class="bi bi-bell" aria-hidden="true"></i><div><strong>{{ $rule->localizedName() }}</strong><small>{{ __('crm.notification_priority_'.$rule->priority) }} · <span class="offset">{{ __('crm.notification_offset_minutes', ['minutes' => $rule->trigger_offset_minutes]) }}</span></small></div></div></td>
       <td>{{ __('crm.notification_event_'.str_replace('.', '_', $rule->event_key)) }}</td>
       <td><div class="channel-list">@foreach($rule->channels as $channel)<span class="badge {{ ($systemChannels[$channel->channel] ?? false) ? '' : 'unavailable' }}">{{ __('crm.notification_channel_'.$channel->channel) }}</span>@endforeach</div></td>
       <td><div class="recipient-summary">@foreach($rule->recipients->groupBy('recipient_type') as $type => $recipients)<div>{{ __('crm.notification_recipient_'.$type) }}: {{ $recipients->count() }}</div>@endforeach</div></td>
       <td><span class="badge {{ $rule->enabled ? 'active' : 'inactive' }}">{{ $rule->enabled ? __('crm.enabled') : __('crm.disabled') }}</span></td>
       <td><div class="rule-actions">
        <a class="btn small" href="{{ route('v2.settings.notifications.edit', $rule) }}">{{ __('crm.edit') }}</a>
        <form method="POST" action="{{ route('v2.settings.notifications.toggle', $rule) }}">@csrf @method('PATCH')<button class="btn small" type="submit">{{ $rule->enabled ? __('crm.disable') : __('crm.enable') }}</button></form>
        <form method="POST" action="{{ route('v2.settings.notifications.duplicate', $rule) }}">@csrf<button class="btn small" type="submit">{{ __('crm.duplicate') }}</button></form>
        <form method="POST" action="{{ route('v2.settings.notifications.destroy', $rule) }}" onsubmit="return confirm(@js(__('crm.confirm_delete_notification_rule')))">@csrf @method('DELETE')<button class="btn small danger" type="submit">{{ __('crm.delete') }}</button></form>
       </div></td>
      </tr>
     @endforeach
    </tbody>
   </table>
  </div>
 @endif
</section>
@endsection

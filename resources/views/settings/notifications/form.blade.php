@extends('settings.layout')

@section('title', $rule->exists ? __('crm.edit_notification_rule') : __('crm.add_notification_rule'))
@section('heading', $rule->exists ? __('crm.edit_notification_rule') : __('crm.add_notification_rule'))
@section('subheading', __('crm.notification_rule_form_description'))

@push('head')
<style>
 .rule-form-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:18px;align-items:start}.rule-form-layout .panel{padding:0;overflow:hidden}.rule-section{padding:22px;border-bottom:1px solid var(--line)}.rule-section:last-child{border-bottom:0}.rule-section-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:17px}.rule-step{width:28px;height:28px;display:grid;place-items:center;flex:0 0 28px;border-radius:9px;background:#182033;color:#fff;font-size:11px;font-weight:900}.rule-section h2{margin:2px 0 0;font-size:17px}.rule-section p{margin:5px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
 .rule-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.rule-check{display:flex;align-items:flex-start;gap:9px;padding:12px;border:1px solid var(--line);border-radius:10px;background:#fff}.rule-check input{width:auto;margin-top:3px;accent-color:var(--red)}.rule-check strong,.rule-check small{display:block}.rule-check small{margin-top:3px;color:var(--muted);font-size:10px}.rule-check .unavailable{color:#a92030}.rule-recipient-selects{margin-top:14px}.rule-sidebar{position:sticky;top:18px}.rule-preview h3{margin:0 0 15px}.rule-preview dl{margin:0}.rule-preview dl div{padding:10px 0;border-bottom:1px solid #edf0f4}.rule-preview dt{color:var(--muted);font-size:10px;font-weight:800}.rule-preview dd{margin:5px 0 0;color:var(--ink);font-size:12px;font-weight:800;line-height:1.55}.rule-preview-actions{display:grid;gap:8px;margin-top:16px}.rule-preview-actions .btn{width:100%}.enabled-check{display:flex;align-items:center;gap:8px}.enabled-check input{width:auto;accent-color:var(--red)}
 @media(max-width:900px){.rule-form-layout{grid-template-columns:1fr}.rule-sidebar{position:static}}
 @media(max-width:560px){.rule-section{padding:17px}.rule-checks,.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
 $selectedChannels = old('channels', $rule->exists ? $rule->channels->pluck('channel')->all() : ['database']);
 $selectedRecipientTypes = old('recipient_types', $rule->exists ? $rule->recipients->whereIn('recipient_type', ['assigned_user','event_owner'])->pluck('recipient_type')->all() : ['assigned_user']);
 $selectedUsers = array_map('intval', old('user_ids', $rule->exists ? $rule->recipients->where('recipient_type', 'explicit_user')->pluck('recipient_id')->all() : []));
 $selectedGroups = array_map('intval', old('group_ids', $rule->exists ? $rule->recipients->where('recipient_type', 'group')->pluck('recipient_id')->all() : []));
 $selectedStatuses = array_map('intval', old('lead_status_ids', data_get($rule->conditions, 'lead_status_ids', [])));
 $selectedCalendarTypes = old('calendar_types', data_get($rule->conditions, 'calendar_types', []));
@endphp
<form method="POST" action="{{ $rule->exists ? route('v2.settings.notifications.update', $rule) : route('v2.settings.notifications.store') }}" id="notificationRuleForm">
 @csrf
 @if($rule->exists) @method('PUT') @endif
 <div class="rule-form-layout">
  <main class="panel">
   <section class="rule-section">
    <header class="rule-section-head"><span class="rule-step">1</span><div><h2>{{ __('crm.notification_rule_basics') }}</h2><p>{{ __('crm.notification_rule_basics_description') }}</p></div></header>
    <div class="form-grid">
     <div class="field"><label for="ruleNameAr">{{ __('crm.rule_name_ar') }}</label><input id="ruleNameAr" name="name_ar" required maxlength="150" dir="rtl" value="{{ old('name_ar', $rule->name_ar) }}"></div>
     <div class="field"><label for="ruleNameEn">{{ __('crm.rule_name_en') }}</label><input id="ruleNameEn" name="name_en" required maxlength="150" dir="ltr" value="{{ old('name_en', $rule->name_en) }}"></div>
     <div class="field"><label for="ruleEvent">{{ __('crm.trigger_event') }}</label><select id="ruleEvent" name="event_key" required>@foreach($events as $event)<option value="{{ $event }}" @selected(old('event_key', $rule->event_key) === $event)>{{ __('crm.notification_event_'.str_replace('.', '_', $event)) }}</option>@endforeach</select></div>
     <div class="field"><label for="rulePriority">{{ __('crm.priority') }}</label><select id="rulePriority" name="priority" required>@foreach($priorities as $priority)<option value="{{ $priority }}" @selected(old('priority', $rule->priority) === $priority)>{{ __('crm.notification_priority_'.$priority) }}</option>@endforeach</select></div>
     <div class="field"><label for="ruleOffset">{{ __('crm.trigger_offset_minutes') }}</label><input id="ruleOffset" type="number" name="trigger_offset_minutes" min="0" max="10080" required value="{{ old('trigger_offset_minutes', $rule->trigger_offset_minutes) }}"><div class="hint">{{ __('crm.trigger_offset_hint') }}</div></div>
     <div class="field"><label for="ruleEscalation">{{ __('crm.escalation_after_minutes') }}</label><input id="ruleEscalation" type="number" name="escalation_after_minutes" min="0" max="10080" value="{{ old('escalation_after_minutes', $rule->escalation_after_minutes) }}"><div class="hint">{{ __('crm.escalation_hint') }}</div></div>
    </div>
   </section>

   <section class="rule-section">
    <header class="rule-section-head"><span class="rule-step">2</span><div><h2>{{ __('crm.notification_rule_channels') }}</h2><p>{{ __('crm.notification_rule_channels_description') }}</p></div></header>
    <div class="rule-checks">
     @foreach($channels as $channel)
      @php $available = (bool)(config('crm_notifications.channels.'.$channel)); @endphp
      <label class="rule-check"><input type="checkbox" name="channels[]" value="{{ $channel }}" @checked(in_array($channel, $selectedChannels, true))><span><strong>{{ __('crm.notification_channel_'.$channel) }}</strong><small class="{{ $available ? '' : 'unavailable' }}">{{ $available ? __('crm.available') : __('crm.channel_not_configured_warning') }}</small></span></label>
     @endforeach
    </div>
   </section>

   <section class="rule-section">
    <header class="rule-section-head"><span class="rule-step">3</span><div><h2>{{ __('crm.notification_rule_recipients') }}</h2><p>{{ __('crm.notification_rule_recipients_description') }}</p></div></header>
    <div class="rule-checks">
     @foreach(['assigned_user','event_owner'] as $type)
      <label class="rule-check"><input type="checkbox" name="recipient_types[]" value="{{ $type }}" @checked(in_array($type, $selectedRecipientTypes, true))><span><strong>{{ __('crm.notification_recipient_'.$type) }}</strong><small>{{ __('crm.notification_recipient_'.$type.'_desc') }}</small></span></label>
     @endforeach
    </div>
    <div class="form-grid rule-recipient-selects">
     <div class="field"><label for="ruleUsers">{{ __('crm.specific_users') }}</label><select id="ruleUsers" name="user_ids[]" multiple size="5">@foreach($users as $user)<option value="{{ $user->id }}" @selected(in_array($user->id, $selectedUsers, true))>{{ $user->name }}</option>@endforeach</select><div class="hint">{{ __('crm.multi_select_hint') }}</div></div>
     <div class="field"><label for="ruleGroups">{{ __('crm.specific_groups') }}</label><select id="ruleGroups" name="group_ids[]" multiple size="5">@foreach($groups as $group)<option value="{{ $group->id }}" @selected(in_array($group->id, $selectedGroups, true))>{{ $group->name }}</option>@endforeach</select><div class="hint">{{ __('crm.multi_select_hint') }}</div></div>
    </div>
   </section>

   <section class="rule-section">
    <header class="rule-section-head"><span class="rule-step">4</span><div><h2>{{ __('crm.notification_rule_conditions') }}</h2><p>{{ __('crm.notification_rule_conditions_description') }}</p></div></header>
    <div class="form-grid">
     <div class="field"><label for="ruleStatuses">{{ __('crm.lead_statuses') }}</label><select id="ruleStatuses" name="lead_status_ids[]" multiple size="5">@foreach($leadStatuses as $status)<option value="{{ $status->id }}" @selected(in_array($status->id, $selectedStatuses, true))>{{ $status->localizedName() }}</option>@endforeach</select></div>
     <div class="field"><label for="ruleCalendarTypes">{{ __('crm.calendar_event_types') }}</label><select id="ruleCalendarTypes" name="calendar_types[]" multiple size="5">@foreach(['meeting','call','task','reminder'] as $type)<option value="{{ $type }}" @selected(in_array($type, $selectedCalendarTypes, true))>{{ __('crm.calendar_type_'.$type) }}</option>@endforeach</select></div>
    </div>
   </section>
  </main>

  <aside class="rule-sidebar">
   <section class="panel rule-preview">
    <h3>{{ __('crm.rule_summary') }}</h3>
    <dl><div><dt>{{ __('crm.rule') }}</dt><dd id="rulePreviewName">{{ old(app()->getLocale() === 'en' ? 'name_en' : 'name_ar', $rule->localizedName()) ?: __('crm.untitled_rule') }}</dd></div><div><dt>{{ __('crm.trigger') }}</dt><dd id="rulePreviewEvent"></dd></div><div><dt>{{ __('crm.channels') }}</dt><dd id="rulePreviewChannels"></dd></div></dl>
    <label class="enabled-check"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $rule->exists ? $rule->enabled : true))><strong>{{ __('crm.enable_rule_now') }}</strong></label>
    <div class="rule-preview-actions"><button class="btn primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>{{ __('crm.save_rule') }}</button><a class="btn" href="{{ route('v2.settings.notifications.index') }}">{{ __('crm.cancel') }}</a></div>
   </section>
  </aside>
 </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
 const form = document.getElementById('notificationRuleForm');
 const localizedName = form.elements[@json(app()->getLocale() === 'en' ? 'name_en' : 'name_ar')];
 const refresh = () => {
  document.getElementById('rulePreviewName').textContent = localizedName.value || @json(__('crm.untitled_rule'));
  const event = form.elements.event_key;
  document.getElementById('rulePreviewEvent').textContent = event.options[event.selectedIndex]?.text || '';
  const channels = [...form.querySelectorAll('[name="channels[]"]:checked')].map(input => input.closest('label').querySelector('strong').textContent.trim());
  document.getElementById('rulePreviewChannels').textContent = channels.join(@json(__('crm.list_separator'))) || @json(__('crm.none'));
 };
 form.addEventListener('input', refresh);
 refresh();
})();
</script>
@endpush

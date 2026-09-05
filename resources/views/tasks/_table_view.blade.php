<div class="task-table-wrap">
 <table class="task-table">
  <thead>
   <tr>
    <th>{{ __('crm.client') }}</th>
    <th>{{ __('crm.phone') }}</th>
    <th>{{ __('crm.status') }} / {{ __('crm.pipeline_stage') }}</th>
    <th>{{ __('crm.scheduled_for') }}</th>
    <th>{{ __('crm.lead_assigned_to') }}</th>
    <th>{{ __('crm.last_followup_summary') }}</th>
    <th style="text-align: center;">{{ __('crm.quick_actions') }}</th>
   </tr>
  </thead>
  <tbody>
   @foreach ($leads as $lead)
    @php
        $statusColor = $lead->status?->color ?? '#64748b';
        $lastFollowup = $lead->followups->first();
        $rawPhone = preg_replace('/[^0-9+]/', '', (string) $lead->phone);
        $waPhone = preg_replace('/[^0-9]/', '', (string) $lead->phone);

        $tableTimeClass = 'no-date';
        $tableUrgencyText = __('crm.no_date_badge');

        if ($lead->next_follow_up_at) {
            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            if ($lead->next_follow_up_at->lt($todayStart)) {
                $tableTimeClass = 'overdue';
                $tableUrgencyText = __('crm.task_overdue_by', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
            } elseif ($lead->next_follow_up_at->gt($todayEnd)) {
                $tableTimeClass = 'upcoming';
                $tableUrgencyText = __('crm.task_due_upcoming', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
            } else {
                $tableTimeClass = 'today';
                if ($lead->next_follow_up_at->isPast()) {
                    $tableUrgencyText = __('crm.task_overdue_by', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
                } else {
                    $tableUrgencyText = __('crm.task_due_in', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
                }
            }
        }
    @endphp
    <tr data-lead-id="{{ $lead->id }}">
     <td>
      <strong>
       <a href="{{ route('v2.leads.show', $lead) }}" class="task-table-lead-link">
        {{ $lead->name }}
       </a>
      </strong>
      @if ($lead->company_name)
       <small class="task-table-company">{{ $lead->company_name }}</small>
      @endif
     </td>

     <td>
      @if ($lead->phone)
       <a class="task-phone-link" href="tel:{{ $rawPhone }}">{{ $lead->phone }}</a>
      @else
       <span class="task-table-empty-followup">{{ __('crm.not_registered') }}</span>
      @endif
     </td>

     <td>
      <span
       class="task-status-pill"
       style="background: {{ $statusColor }}14; color: {{ $statusColor }}; border-color: {{ $statusColor }}33;"
      >
       {{ $lead->status?->name_ar ?? '-' }}
      </span>
      <small class="task-table-stage">
       {{ $lead->status?->stage?->name_ar ?? '-' }}
      </small>
     </td>

     <td>
      <span class="task-table-date">
       {{ $lead->next_follow_up_at ? $lead->next_follow_up_at->format('d/m/Y - h:i A') : __('crm.without_followup_date') }}
      </span>
      @if ($lead->next_follow_up_at)
       <small style="display: inline-block; margin-top: 2px; padding: 2px 6px; font-size: 10px;" class="task-time-strip {{ $tableTimeClass }}">
        {{ $tableUrgencyText }}
       </small>
      @endif
     </td>

     <td>
      {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
     </td>

     <td style="max-width: 260px;">
      @if ($lastFollowup)
       <div class="task-table-last-time">
        {{ $lastFollowup->followed_up_at ? $lastFollowup->followed_up_at->diffForHumans() : '' }}
        ({{ $lastFollowup->user?->name ?? $lastFollowup->employee_name }})
       </div>
       <div class="task-table-last-outcome" title="{{ $lastFollowup->outcome }}">
        {{ $lastFollowup->outcome }}
       </div>
      @else
       <span class="task-table-empty-followup">
        {{ __('crm.no_previous_followups') }}
       </span>
      @endif
     </td>

     <td>
      <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
       @if ($lead->phone)
        @can('createFollowup', $lead)
         <a
          class="task-btn-icon call"
          href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
          data-transition-popup="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
          data-lead-name="{{ $lead->name }}"
          data-voice-dial="{{ $rawPhone }}"
          title="{{ __('crm.call_and_followup') }}"
         >
          <i class="bi bi-telephone-fill"></i>
         </a>
        @endcan
        <a
         class="task-btn-icon whatsapp"
         href="https://wa.me/{{ $waPhone }}"
         target="_blank"
         rel="noopener noreferrer"
         title="{{ __('crm.quick_whatsapp') }}"
        >
         <i class="bi bi-whatsapp"></i>
        </a>
       @endif
       @can('createFollowup', $lead)
        <a
         class="task-btn-icon donation"
         href="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}"
         data-transition-popup="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}"
         data-lead-name="{{ $lead->name }}"
         title="{{ __('crm.record_donation') }}"
        >
         <i class="bi bi-heart-fill"></i>
        </a>

        <button
         class="task-btn-icon log"
         type="button"
         data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
         data-lead-name="{{ $lead->name }}"
         title="{{ __('crm.quick_log_followup') }}"
        >
         <i class="bi bi-pencil-square"></i>
        </button>
       @endcan

       <button
        class="task-btn-icon reschedule"
        type="button"
        onclick="openRescheduleModal({{ $lead->id }}, '{{ addslashes($lead->name) }}', '{{ $lead->next_follow_up_at ? $lead->next_follow_up_at->toISOString() : '' }}')"
        title="{{ __('crm.reschedule_task') }}"
       >
        <i class="bi bi-calendar-plus"></i>
       </button>

       <a
        class="task-btn-icon view"
        href="{{ route('v2.leads.show', $lead) }}"
        title="{{ __('crm.view_lead') }}"
       >
        <i class="bi bi-eye"></i>
       </a>
      </div>
     </td>
    </tr>
   @endforeach
  </tbody>
 </table>
</div>

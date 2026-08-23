@php
    $statusColor = $lead->status?->color ?? '#64748b';
    $lastFollowup = $lead->followups->first();
    $rawPhone = preg_replace('/[^0-9+]/', '', (string) $lead->phone);
    $waPhone = preg_replace('/[^0-9]/', '', (string) $lead->phone);

    $cardTimeClass = 'no-date';
    $cardUrgencyText = __('crm.no_date_badge');

    if ($lead->next_follow_up_at) {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        if ($lead->next_follow_up_at->lt($todayStart)) {
            $cardTimeClass = 'overdue';
            $cardUrgencyText = __('crm.task_overdue_by', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
        } elseif ($lead->next_follow_up_at->gt($todayEnd)) {
            $cardTimeClass = 'upcoming';
            $cardUrgencyText = __('crm.task_due_upcoming', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
        } else {
            $cardTimeClass = 'today';
            if ($lead->next_follow_up_at->isPast()) {
                $cardUrgencyText = __('crm.task_overdue_by', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
            } else {
                $cardUrgencyText = __('crm.task_due_in', ['time' => $lead->next_follow_up_at->diffForHumans(null, true)]);
            }
        }
    }
@endphp

<article class="task-item-card" data-lead-id="{{ $lead->id }}">
 <div>
  {{-- Card Top: Lead Name, Company, Status --}}
  <div class="task-card-topbar">
   <div class="task-lead-info">
    <div class="task-lead-avatar">
     {{ mb_substr((string) $lead->name, 0, 1) }}
    </div>
    <div class="task-lead-names">
     <strong>
      <a href="{{ route('v2.leads.show', $lead) }}">{{ $lead->name }}</a>
     </strong>
     <small>
      {{ $lead->company_name ?: ($lead->job_title ?: __('crm.no_company')) }}
     </small>
    </div>
   </div>

   <span
    class="task-status-pill"
    style="background: {{ $statusColor }}14; color: {{ $statusColor }}; border-color: {{ $statusColor }}33;"
   >
    {{ $lead->status?->name_ar ?? '-' }}
   </span>
  </div>

  {{-- Scheduled Time & Urgency Strip --}}
  <div class="task-time-strip {{ $cardTimeClass }}">
   <span>
    <i class="bi bi-clock"></i>
    @if ($lead->next_follow_up_at)
     {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
    @else
     {{ __('crm.without_followup_date') }}
    @endif
   </span>

   <span>
    {{ $cardUrgencyText }}
   </span>
  </div>

  {{-- Meta Details Grid --}}
  <div class="task-meta-grid">
   <div class="task-meta-item">
    <span>{{ __('crm.phone') }}</span>
    <strong>
     @if ($lead->phone)
      <a class="task-phone-link" href="tel:{{ $rawPhone }}">{{ $lead->phone }}</a>
     @else
      <span style="color:#94a3b8;">{{ __('crm.not_registered') }}</span>
     @endif
    </strong>
   </div>

   <div class="task-meta-item">
    <span>{{ __('crm.lead_assigned_to') }}</span>
    <strong>
     {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
    </strong>
   </div>

   <div class="task-meta-item">
    <span>{{ __('crm.pipeline_stage') }}</span>
    <strong>{{ $lead->status?->stage?->name_ar ?? '—' }}</strong>
   </div>

   <div class="task-meta-item">
    <span>{{ __('crm.source') }}</span>
    <strong>{{ $lead->source ?: __('crm.not_specified') }}</strong>
   </div>
  </div>

  {{-- Last Contact Context Box --}}
  <div class="task-last-followup">
   <div class="task-last-followup-head">
    <span>
     <i class="bi bi-chat-left-text"></i>
     {{ __('crm.last_followup_summary') }}
     @if ($lastFollowup)
      ({{ $lastFollowup->followed_up_at ? $lastFollowup->followed_up_at->diffForHumans() : '' }})
     @endif
    </span>
    @if ($lastFollowup)
     <span>
      {{ __('crm.last_followup_by', ['name' => $lastFollowup->user?->name ?? $lastFollowup->employee_name]) }}
     </span>
    @endif
   </div>

   @if ($lastFollowup)
    <div class="task-last-followup-note" title="{{ $lastFollowup->outcome }}">
     {{ $lastFollowup->outcome }}
    </div>
   @else
    <div style="color: #94a3b8; font-size: 11px; font-style: italic;">
     {{ __('crm.no_previous_followups') }}
    </div>
   @endif
  </div>
 </div>

 {{-- Action Toolbar --}}
 <div class="task-actions-toolbar">
  @if ($lead->phone)
   <a
    class="task-btn-main"
    href="tel:{{ $rawPhone }}"
    onclick="openQuickFollowupModal({{ $lead->id }}, '{{ addslashes($lead->name) }}', {{ $lead->lead_status_id ?? 'null' }}, 'call');"
    title="{{ __('crm.call_and_followup') }}"
   >
    <i class="bi bi-telephone-fill"></i>
    <span>{{ __('crm.call_and_log') }}</span>
   </a>
  @else
   <button class="task-btn-main" disabled style="opacity: 0.5; cursor: not-allowed;">
    <i class="bi bi-telephone-x"></i>
    <span>{{ __('crm.no_phone_abbr') }}</span>
   </button>
  @endif

  @if ($lead->phone)
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

  <button
   class="task-btn-icon"
   type="button"
   onclick="openQuickFollowupModal({{ $lead->id }}, '{{ addslashes($lead->name) }}', {{ $lead->lead_status_id ?? 'null' }})"
   title="{{ __('crm.quick_log_followup') }}"
  >
   <i class="bi bi-pencil-square"></i>
  </button>

  <button
   class="task-btn-icon"
   type="button"
   onclick="openRescheduleModal({{ $lead->id }}, '{{ addslashes($lead->name) }}', '{{ $lead->next_follow_up_at ? $lead->next_follow_up_at->toISOString() : '' }}')"
   title="{{ __('crm.reschedule_task') }}"
  >
   <i class="bi bi-calendar-plus"></i>
  </button>

  <a
   class="task-btn-icon"
   href="{{ route('v2.leads.show', $lead) }}"
   title="{{ __('crm.view_lead') }}"
  >
   <i class="bi bi-eye"></i>
  </a>
 </div>
</article>

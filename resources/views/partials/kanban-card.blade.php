@php
    $phoneClean = $lead->phone ? preg_replace('/[^0-9+]/', '', (string) $lead->phone) : '';
    $stageColor = $column['stage_color'] ?? ($column['color'] ?? '#3478f6');
    $currentScope = $scope ?? 'today';
@endphp
<article
 class="kanban-card"
 draggable="{{ auth()->user()?->can('leads.followups.create') ? 'true' : 'false' }}"
 data-kanban-lead="{{ $lead->id }}"
 data-kanban-lead-name="{{ $lead->name }}"
 data-kanban-lead-phone="{{ $lead->phone }}"
 data-kanban-lead-company="{{ $lead->company_name ?: $lead->source }}"
 data-kanban-lead-employee="{{ $lead->assignedUser?->name ?? $lead->assigned_employee }}"
 data-kanban-lead-employee-id="{{ $lead->assigned_user_id ?? '' }}"
 data-kanban-lead-scope="{{ $lead->next_follow_up_at ? $currentScope : 'no_date' }}"
 data-kanban-lead-has-date="{{ $lead->next_follow_up_at ? '1' : '0' }}"
 data-current-status-id="{{ $lead->lead_status_id ?? ($column['status_id'] ?? '') }}"
 data-current-status-name="{{ $lead->status?->name_ar ?? ($column['name'] ?? '') }}"
 data-followup-url="{{ route('v2.leads.followups.index', $lead) }}"
 style="--card-stage-color: {{ $stageColor }};"
 data-stage-position="{{ $column['position'] ?? 0 }}"
>
 <div class="kanban-card-head">
  <a class="kanban-card-name" href="{{ route('v2.leads.show', $lead) }}">
   {{ $lead->name }}
  </a>

  <span class="kanban-card-stage">
   {{ $column['stage_name'] ?? ($column['name'] ?? '') }}
  </span>
 </div>

 {{-- Stage progress stepper --}}
 <div class="kanban-stage-stepper">
  @foreach ($kanbanColumns as $stepIdx => $stepCol)
   @if ($stepIdx > 0)
    <span class="kanban-stepper-line{{ ($column['position'] ?? 0) > ($kanbanColumns[$stepIdx - 1]['position'] ?? 0) ? ' completed' : '' }}"></span>
   @endif
   @php
    $stepPos = $stepCol['position'] ?? $stepIdx;
    $curPos = $column['position'] ?? 0;
    $stepClass = $stepPos === $curPos ? 'active' : ($stepPos < $curPos ? 'completed' : '');
    $stepShort = match($stepCol['code']) {
     'new' => 'ج',
     'no_answer' => 'ل',
     'followup_later', 'followup-later' => 'ح',
     'not_interested' => 'غ',
     'donor' => 'م',
     default => mb_substr($stepCol['name'], 0, 1),
    };
   @endphp
   <span class="kanban-stepper-step {{ $stepClass }}">
    <span class="kanban-stepper-dot {{ $stepClass }}"></span>
    <span class="kanban-stepper-label">{{ $stepShort }}</span>
   </span>
  @endforeach
 </div>

 <div class="kanban-card-info">
  <div class="kanban-card-row">
   <span><i class="bi bi-telephone"></i> {{ __('crm.phone') }}</span>

   <strong>
    @if ($lead->phone)
     <a class="kanban-phone" href="tel:{{ $phoneClean }}">
      {{ $lead->phone }}
     </a>
    @else
     {{ __('crm.not_registered') }}
    @endif
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-building"></i> {{ __('crm.company_or_source') }}</span>

   <strong>
    {{ $lead->company_name ?: ($lead->source ?: __('crm.not_specified')) }}
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-person-badge"></i> {{ __('crm.employee') }}</span>

   <strong>
    {{ $lead->assignedUser?->name ?? ($lead->assigned_employee ?: __('crm.unassigned')) }}
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-clock-history"></i> {{ __('crm.followup_date') }}</span>

   <strong>
    {{ $lead->next_follow_up_at?->format('d/m/Y H:i') ?? __('crm.no_date') }}
   </strong>
  </div>
 </div>

 <div class="kanban-card-actions">
  @if ($lead->phone)
   <a
    class="btn call"
    @can('leads.followups.create')
    data-transition-popup="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
    data-transition-context="kanban"
    data-lead-name="{{ $lead->name }}"
    @endcan
    data-voice-dial="{{ $phoneClean }}"
    data-sip-href="sip:{{ $phoneClean }}"
    href="tel:{{ $phoneClean }}"
    draggable="false"
    title="{{ __('crm.call_and_followup') }}"
   >
    <i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.call') }}
   </a>
  @endif

  @can('leads.followups.create')
  <a
   class="btn donation"
   href="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}"
   data-transition-popup="{{ route('v2.leads.followups.index', [$lead, 'make_donation' => 1]) }}"
   data-transition-context="kanban"
   data-lead-name="{{ $lead->name }}"
   title="{{ __('crm.record_donation') }}"
  >
   <i class="bi bi-heart-fill"></i> {{ __('crm.record_donation') }}
  </a>
  @endcan

  <a
   class="btn light"
   href="{{ route('v2.leads.show', $lead) }}"
   draggable="false"
  >
   <i class="bi bi-eye"></i> {{ __('crm.view_lead') }}
  </a>

  @can('leads.followups.create')
  <a
   class="btn light"
   href="{{ route('v2.leads.followups.index', $lead) }}"
   data-transition-popup="{{ route('v2.leads.followups.index', $lead) }}"
   data-transition-context="kanban"
   data-lead-name="{{ $lead->name }}"
   draggable="false"
  >
   <i class="bi bi-plus-circle"></i> {{ __('crm.log_followup') }}
  </a>
  @endcan
 </div>

 {{-- Stage transition buttons --}}
 @can('leads.followups.create')
 <div class="kanban-stage-transitions">
  @php
   $transitionStages = [
    'new' => ['icon' => 'bi-plus-circle', 'color' => '#3478f6'],
    'no_answer' => ['icon' => 'bi-telephone-x', 'color' => '#e59b16'],
    'followup_later' => ['icon' => 'bi-clock-history', 'color' => '#7b61df'],
    'not_interested' => ['icon' => 'bi-x-circle', 'color' => '#dc2637'],
    'donor' => ['icon' => 'bi-heart', 'color' => '#16a34a'],
   ];
  @endphp
  @foreach ($kanbanColumns as $targetCol)
   @if ($targetCol['code'] !== $column['code'])
    <a
     class="kanban-stage-btn"
     style="--btn-stage-color:{{ $transitionStages[$targetCol['code']]['color'] ?? ($targetCol['stage_color'] ?? ($targetCol['color'] ?? '#3478f6')) }}"
     href="{{ route('v2.leads.followups.index', [$lead, 'kanban_popup' => 1, 'target_status_code' => $targetCol['code']]) }}"
     data-transition-popup="{{ route('v2.leads.followups.index', [$lead, 'target_status_code' => $targetCol['code']]) }}"
     data-transition-context="kanban"
     data-lead-name="{{ $lead->name }}"
     data-transition-description="{{ __('crm.move_to_stage') }} {{ $targetCol['name'] }}"
     draggable="false"
     title="{{ __('crm.move_to_stage') }} {{ $targetCol['name'] }}"
    >
     <i class="bi {{ $transitionStages[$targetCol['code']]['icon'] ?? 'bi-arrow-right-circle' }}"></i>
    </a>
   @endif
  @endforeach
 </div>
 @endcan
</article>

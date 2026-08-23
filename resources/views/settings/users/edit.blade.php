@extends('settings.layout')

@section('title', __('crm.edit_user'))
@section('heading', __('crm.edit_user'))
@section('subheading', $managedUser->name.' · '.$managedUser->username)

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.account_groups_data') }}</h2>
            <p>{{ __('crm.last_admin_notice') }}</p>
        </div>
        <span class="badge {{ $managedUser->is_active ? 'active' : 'inactive' }}">
            {{ $managedUser->is_active ? __('crm.active') : __('crm.inactive') }}
        </span>
    </div>
    <form method="POST" action="{{ route('v2.settings.users.update', $managedUser) }}">
        @csrf
        @method('PATCH')
        @include('settings.users._form')
    </form>
</section>

@if (!empty($isVoipConnected) && $managedUser->voip_extension)
<section class="panel" style="margin-top:24px">
    <div class="panel-head" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h2 style="display:flex;align-items:center;gap:8px">
                <span>📞</span>
                <span>{{ __('crm.pbx_call_statistics') }} ({{ __('crm.extension_short') }} {{ $managedUser->voip_extension }})</span>
            </h2>
            <p>{{ __('crm.statistics_for_user') }} {{ $managedUser->name }} {{ __('crm.on_extension') }} {{ $managedUser->voip_extension }}</p>
        </div>
        <span class="badge active" style="font-family:monospace;font-size:13px">{{ __('crm.extension_short') }} {{ $managedUser->voip_extension }}</span>
    </div>

    @php
        $voipFilters = $voipFilters ?? [];
    @endphp
    <form method="GET" action="{{ route('v2.settings.users.edit', $managedUser) }}" class="form-grid" aria-labelledby="voip-filters-heading" style="margin-bottom:20px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px">
        <div class="field full" style="margin-bottom:0">
            <div id="voip-filters-heading" style="font-weight:900;color:#334155">{{ __('crm.voip_filters') }}</div>
            <div class="hint">{{ __('crm.voip_filter_hint') }}</div>
        </div>
        <div class="field">
            <label for="voip_from_date">{{ __('crm.from_date') }}</label>
            <input id="voip_from_date" name="from_date" type="date" dir="ltr" value="{{ $voipFilters['from_date'] ?? '' }}" placeholder="{{ __('crm.date_format_placeholder') }}">
        </div>
        <div class="field">
            <label for="voip_to_date">{{ __('crm.to_date') }}</label>
            <input id="voip_to_date" name="to_date" type="date" dir="ltr" value="{{ $voipFilters['to_date'] ?? '' }}" placeholder="{{ __('crm.date_format_placeholder') }}">
        </div>
        <div class="field">
            <label for="voip_direction">{{ __('crm.direction') }}</label>
            <select id="voip_direction" name="direction">
                <option value="">{{ __('crm.all_directions') }}</option>
                <option value="incoming" @selected(($voipFilters['direction'] ?? '') === 'incoming')>{{ __('crm.incoming') }}</option>
                <option value="outgoing" @selected(($voipFilters['direction'] ?? '') === 'outgoing')>{{ __('crm.outgoing') }}</option>
                <option value="internal" @selected(($voipFilters['direction'] ?? '') === 'internal')>{{ __('crm.internal') }}</option>
            </select>
        </div>
        <div class="field">
            <label for="voip_status">{{ __('crm.call_status') }}</label>
            <select id="voip_status" name="status">
                <option value="">{{ __('crm.all_call_statuses') }}</option>
                <option value="answered" @selected(($voipFilters['status'] ?? '') === 'answered')>{{ __('crm.status_answered') }}</option>
                <option value="missed" @selected(($voipFilters['status'] ?? '') === 'missed')>{{ __('crm.status_missed') }}</option>
                <option value="failed" @selected(($voipFilters['status'] ?? '') === 'failed')>{{ __('crm.status_failed') }}</option>
                <option value="busy" @selected(($voipFilters['status'] ?? '') === 'busy')>{{ __('crm.status_busy') }}</option>
                <option value="no_answer" @selected(($voipFilters['status'] ?? '') === 'no_answer')>{{ __('crm.status_no_answer') }}</option>
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;grid-column:1/-1">
            <button type="submit" class="btn primary">{{ __('crm.apply_filters') }}</button>
            <a class="btn" href="{{ route('v2.settings.users.edit', $managedUser) }}">{{ __('crm.reset_filters') }}</a>
        </div>
    </form>

    @if ($voipStats && isset($voipStats['summary']))
        @php
            $s = $voipStats['summary'];
            $talkMinutes = floor(($s['total_talk_seconds'] ?? 0) / 60);
            $talkSecs = ($s['total_talk_seconds'] ?? 0) % 60;
            $talkFormatted = $talkMinutes > 0 ? $talkMinutes.' '.__('crm.minutes').' '.$talkSecs.' '.__('crm.seconds') : $talkSecs.' '.__('crm.seconds');
        @endphp
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-top:16px">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-align:center">
                <div style="color:#64748b;font-size:12px;font-weight:700">{{ __('crm.total_calls') }}</div>
                <div style="font-size:24px;font-weight:800;color:#1e293b;margin-top:4px">{{ number_format($s['total_calls'] ?? 0) }}</div>
            </div>
            <div style="background:#e7f8ed;border:1px solid #bbf7d0;border-radius:12px;padding:14px;text-align:center">
                <div style="color:#166534;font-size:12px;font-weight:700">{{ __('crm.answered_calls') }}</div>
                <div style="font-size:24px;font-weight:800;color:#14532d;margin-top:4px">{{ number_format($s['answered_calls'] ?? 0) }} ({{ $s['answer_rate_percent'] ?? 0 }}%)</div>
            </div>
            <div style="background:#e0f2fe;border:1px solid #bae6fd;border-radius:12px;padding:14px;text-align:center">
                <div style="color:#0369a1;font-size:12px;font-weight:700">{{ __('crm.outgoing_calls') }}</div>
                <div style="font-size:24px;font-weight:800;color:#0c4a6e;margin-top:4px">{{ number_format($s['outbound_calls'] ?? 0) }}</div>
            </div>
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:14px;text-align:center">
                <div style="color:#92400e;font-size:12px;font-weight:700">{{ __('crm.total_talk_time') }}</div>
                <div style="font-size:18px;font-weight:800;color:#78350f;margin-top:8px">{{ $talkFormatted }}</div>
            </div>
        </div>

        @if (!empty($voipStats['recent_calls']))
            <div style="margin-top:20px">
                <h4 style="margin:0 0 12px;font-size:14px;color:#334155">{{ __('crm.latest_inbound_outbound_calls') }}</h4>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('crm.datetime') }}</th>
                                <th>{{ __('crm.direction') }}</th>
                                <th>{{ __('crm.other_party') }}</th>
                                <th>{{ __('crm.duration') }}</th>
                                <th>{{ __('crm.call_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_slice($voipStats['recent_calls'], 0, 10) as $rc)
                                @php
                                    $direction = strtolower((string) ($rc['direction'] ?? ''));
                                    $isOutbound = in_array($direction, ['outbound', 'outgoing'], true);
                                    $isInbound = in_array($direction, ['inbound', 'incoming'], true);
                                    $other = $isOutbound ? ($rc['customer_number'] ?? '—') : ($rc['customer_number'] ?? $rc['agent_extension'] ?? '—');
                                    $date = isset($rc['started_at']) ? str_replace('T', ' ', substr($rc['started_at'], 0, 19)) : '—';
                                    $status = strtoupper((string) ($rc['disposition'] ?? ''));
                                    $statusLabel = match ($status) {
                                        'ANSWERED' => __('crm.status_answered'),
                                        'MISSED' => __('crm.status_missed'),
                                        'FAILED' => __('crm.status_failed'),
                                        'BUSY' => __('crm.status_busy'),
                                        'NO ANSWER', 'NO_ANSWER' => __('crm.status_no_answer'),
                                        default => $rc['disposition'] ?? '—',
                                    };
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap;direction:ltr;text-align:start">{{ $date }}</td>
                                    <td>
                                        @if($isOutbound)
                                            <span class="badge" style="background:#e0f2fe;color:#0369a1">{{ __('crm.outgoing') }}</span>
                                        @elseif($isInbound)
                                            <span class="badge" style="background:#e7f8ed;color:#166534">{{ __('crm.incoming') }}</span>
                                        @else
                                            <span class="badge" style="background:#f3f4f6;color:#374151">{{ __('crm.internal') }}</span>
                                        @endif
                                    </td>
                                    <td style="font-family:monospace;direction:ltr;text-align:start">{{ $other }}</td>
                                    <td>{{ $rc['duration_seconds'] ?? 0 }} {{ __('crm.seconds') }}</td>
                                    <td>
                                        @if($status === 'ANSWERED')
                                            <span class="badge active">{{ $statusLabel }}</span>
                                        @else
                                            <span class="badge inactive">{{ $statusLabel }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div style="padding:20px;text-align:center;color:#64748b">
                {{ __('crm.no_calls_match_filters') }}
            </div>
        @endif
    @else
        <div style="padding:20px;text-align:center;color:#64748b">
            {{ __('crm.no_extension_statistics') }} {{ $managedUser->voip_extension }}.
        </div>
    @endif
</section>
@endif

@can('users.reset_password')
<section class="panel" style="margin-top:24px">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.change_password') }}</h2>
            <p>{{ __('crm.password_not_displayed') }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('v2.settings.users.password', $managedUser) }}">
        @csrf
        @method('PATCH')
        <div class="form-grid">
            <div class="field">
                <label for="new_password">{{ __('crm.new_password') }}</label>
                <input id="new_password" name="password" type="password" required minlength="10" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="new_password_confirmation">{{ __('crm.confirm_password') }}</label>
                <input id="new_password_confirmation" name="password_confirmation" type="password" required minlength="10" autocomplete="new-password">
            </div>
        </div>
        <button class="btn primary" style="margin-top:18px">{{ __('crm.update_password') }}</button>
    </form>
</section>
@endcan
@endsection

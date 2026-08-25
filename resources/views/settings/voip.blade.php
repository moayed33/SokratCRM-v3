@extends('settings.layout')

@section('title', __('crm.voip_settings'))
@section('heading', __('crm.voip_connection_settings'))
@section('subheading', __('crm.voip_connection_subtitle'))

@section('content')
<section class="grid stats-grid" style="margin-bottom:20px">
    <article class="stat-card">
        <span>{{ __('crm.connection_status') }}</span>
        <b style="font-size:22px">
            @if($isConfigured && isset($health['status']) && $health['status'] === 'ok')
                <span style="color:#16a34a">{{ __('crm.connected_success') }}</span>
            @elseif($isConfigured)
                <span style="color:#dc2637">{{ __('crm.connection_error') }}</span>
            @else
                <span style="color:#6b7280">{{ __('crm.not_paired') }}</span>
            @endif
        </b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.application_version') }}</span>
        <b style="font-size:22px">{{ $health['application_version'] ?? __('crm.unknown') }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.timezone') }}</span>
        <b style="font-size:22px">{{ $health['timezone'] ?? __('crm.unknown') }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.client_id') }}</span>
        <b style="font-size:15px;word-break:break-all;font-family:monospace">{{ $clientId ?: __('crm.none') }}</b>
    </article>
</section>

@if($error)
    <div class="flash error">
        <strong>{{ __('crm.pbx_server_error') }}</strong> {{ $error }}
    </div>
@endif

@if (session('success'))
    <div class="flash" style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="flash error">
        {{ session('error') }}
    </div>
@endif
@unless ($errors->isEmpty())
    <div class="flash error">
        @foreach ($errors->all() as $validationError)
            <div>{{ $validationError }}</div>
        @endforeach
    </div>
@endunless

<section class="panel">
    <div class="panel-head" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h2>{{ __('crm.pairing_data') }}</h2>
            <p>{{ __('crm.pairing_instructions') }}</p>
        </div>
        @if($isConfigured)
            <form method="POST" action="{{ route('v2.settings.voip.disconnect') }}" onsubmit="return confirm(@json(__('crm.confirm_disconnect_voip')))">
                @csrf
                <button type="submit" class="btn danger soft" style="color:#b42332;border-color:#f1bbc1;background:#fff0f1">
                    {{ __('crm.disconnect_pbx') }}
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('v2.settings.voip.pair') }}">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="api_url">{{ __('crm.pbx_server_url') }}</label>
                <input type="url" id="api_url" name="api_url" value="{{ old('api_url', $apiUrl) }}" required placeholder="http://192.168.100.128:8080/api/integrations/crm/v1">
                <div class="hint">{{ __('crm.pbx_url_example') }}</div>
            </div>

            <div class="field">
                <label for="pairing_code">{{ __('crm.pairing_code') }}</label>
                <input type="text" id="pairing_code" name="pairing_code" placeholder="{{ __('crm.pairing_code_placeholder') }}" required style="font-family:monospace;letter-spacing:2px;font-weight:bold">
                <div class="hint">{{ __('crm.pairing_code_validity') }}</div>
            </div>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:flex-end">
            <button type="submit" class="btn primary">
                {{ __('crm.pair_and_test') }}
            </button>
        </div>
    </form>
</section>

@if($capabilities && isset($capabilities['supported']))
<section class="panel" style="margin-top:20px">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.available_capabilities') }}</h2>
            <p>{{ __('crm.capabilities_subtitle') }}</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>{{ __('crm.feature') }}</th>
                    <th>{{ __('crm.availability_status') }}</th>
                    <th>{{ __('crm.granted_permissions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ __('crm.call_history') }}</strong></td>
                    <td><span class="badge {{ !empty($capabilities['supported']['call_history']) ? 'active' : 'inactive' }}">{{ !empty($capabilities['supported']['call_history']) ? __('crm.active_feature') : __('crm.inactive') }}</span></td>
                    <td><code>calls:read</code></td>
                </tr>
                <tr>
                    <td><strong>{{ __('crm.recordings') }}</strong></td>
                    <td><span class="badge {{ !empty($capabilities['supported']['recordings']) ? 'active' : 'inactive' }}">{{ !empty($capabilities['supported']['recordings']) ? __('crm.active_feature') : __('crm.inactive') }}</span></td>
                    <td><code>recordings:read</code></td>
                </tr>
                <tr>
                    <td><strong>{{ __('crm.extension_statistics') }}</strong></td>
                    <td><span class="badge {{ !empty($capabilities['supported']['extension_stats']) ? 'active' : 'inactive' }}">{{ !empty($capabilities['supported']['extension_stats']) ? __('crm.active_feature') : __('crm.inactive') }}</span></td>
                    <td><code>stats:read</code></td>
                </tr>
                <tr>
                    <td><strong>{{ __('crm.live_panel') }}</strong></td>
                    <td><span class="badge {{ !empty($capabilities['supported']['live_panel']) ? 'active' : 'inactive' }}">{{ !empty($capabilities['supported']['live_panel']) ? __('crm.active_feature') : __('crm.inactive') }}</span></td>
                    <td><code>live:read</code></td>
                </tr>
                <tr>
                    <td><strong>{{ __('crm.live_control') }}</strong></td>
                    <td>
                        @if(!empty($capabilities['effective_live_controls']))
                            @foreach($capabilities['effective_live_controls'] as $control)
                                <span class="badge active" style="margin-inline-end:4px">{{ $control }}</span>
                            @endforeach
                        @else
                            <span class="badge system">{{ __('crm.read_only') }}</span>
                        @endif
                    </td>
                    <td><code>live:listen, live:whisper, live:barge, live:hangup</code></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection

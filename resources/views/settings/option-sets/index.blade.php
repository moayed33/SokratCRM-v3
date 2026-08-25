@extends('settings.layout')

@section('title', __('crm.option_sets'))
@section('heading', __('crm.option_sets'))
@section('subheading', __('crm.option_sets_desc'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.os_list_title') }}</h2>
            <p>{{ __('crm.os_list_desc') }}</p>
        </div>
    </div>

    <div class="table-wrap" style="margin-top:6px">
        <table style="min-width:640px">
            <thead>
                <tr>
                    <th>{{ __('crm.os_set_key') }}</th>
                    <th>{{ __('crm.os_options_count') }}</th>
                    <th>{{ __('crm.os_used_in') }}</th>
                    <th style="width:140px">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sets as $set)
                    <tr>
                        <td><code dir="ltr">{{ $set['key'] }}</code></td>
                        <td><span class="badge">{{ number_format($set['count']) }}</span></td>
                        <td><span class="hint">{{ $usageHints[$set['key']] ?? __('crm.os_used_generic') }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="btn small" href="{{ route('v2.settings.option-sets.edit', ['set' => $set['key']]) }}">
                                    <i class="bi bi-pencil"></i> {{ __('crm.edit') }}
                                </a>
                                @unless (in_array($set['key'], \App\Http\Controllers\Settings\OptionSetController::systemSets(), true))
                                    <form method="POST" action="{{ route('v2.settings.option-sets.destroy', ['set' => $set['key']]) }}" onsubmit="return confirm(@json(__('crm.os_confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:#697386;padding:36px">
                            <i class="bi bi-list-ul" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.5"></i>
                            {{ __('crm.os_empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.os_new_set') }}</h2>
            <p>{{ __('crm.os_new_set_desc') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('v2.settings.option-sets.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="setKey">{{ __('crm.os_set_key') }} <span style="color:#dc2637">*</span></label>
                <input type="text" id="setKey" name="set_key" value="{{ old('set_key') }}" required maxlength="50" pattern="[a-z][a-z0-9_]*" dir="ltr" placeholder="shipping_methods" style="text-align:start">
                <div class="hint">{{ __('crm.os_set_key_hint') }}</div>
            </div>
            <div class="field">
                <label for="newOptionsInput">{{ __('crm.lf_options') }} <span style="color:#dc2637">*</span></label>
                <textarea id="newOptionsInput" name="options_input" rows="4" required dir="ltr" style="text-align:start" placeholder="{{ __('crm.lf_options_placeholder') }}">{{ old('options_input') }}</textarea>
                <div class="hint">{{ __('crm.lf_options_hint') }}</div>
            </div>
        </div>
        @error('set_key')
            <div class="hint" style="color:#b42332;font-weight:700">{{ $message }}</div>
        @enderror
        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn primary"><i class="bi bi-plus-lg"></i> {{ __('crm.os_create_set') }}</button>
        </div>
    </form>
</section>
@endsection

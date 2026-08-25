@extends('settings.layout')

@section('title', __('crm.option_sets'))
@section('heading', __('crm.option_sets'))
@section('subheading', $setKey)

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.os_edit_set') }}: <code dir="ltr">{{ $setKey }}</code></h2>
            <p>{{ $usageHint ?? __('crm.lf_options_hint') }}</p>
        </div>
        <a class="btn soft" href="{{ route('v2.settings.option-sets.index') }}">
            <i class="bi bi-arrow-left"></i> {{ __('crm.cancel') }}
        </a>
    </div>

    <form method="POST" action="{{ route('v2.settings.option-sets.update', ['set' => $setKey]) }}">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="field full">
                <label for="optionsInput">{{ __('crm.lf_options') }} <span style="color:#dc2637">*</span></label>
                <textarea id="optionsInput" name="options_input" rows="{{ max(6, substr_count(trim((string) $optionsInput), "\n") + 1) }}" required dir="ltr" style="text-align:start">{{ $optionsInput }}</textarea>
                <div class="hint">{{ __('crm.lf_options_hint') }}</div>
                <div class="hint" style="margin-top:10px"><i class="bi bi-info-circle"></i> {{ __('crm.os_replace_warning') }}</div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
            <a href="{{ route('v2.settings.option-sets.index') }}" class="btn soft">{{ __('crm.cancel') }}</a>
            <button type="submit" class="btn primary" style="min-width:160px"><i class="bi bi-check2"></i> {{ __('crm.save') }}</button>
        </div>
    </form>
</section>
@endsection

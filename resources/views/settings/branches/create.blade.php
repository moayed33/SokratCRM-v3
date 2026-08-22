@extends('settings.layout')

@section('title', __('crm.new_branch'))
@section('heading', __('crm.new_branch'))
@section('subheading', __('crm.add_new_branch_subtitle'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.new_branch_data') }}</h2>
            <p>{{ __('crm.fields_with_star_required') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('v2.settings.branches.store') }}">
        @csrf

        <div class="form-grid">
            <div class="field">
                <label for="name_ar">{{ __('crm.branch_name_ar') }} <span style="color:var(--red)">*</span></label>
                <input id="name_ar" name="name_ar" required maxlength="150" value="{{ old('name_ar') }}" placeholder="{{ __('crm.branch_name_example') }}" autofocus>
            </div>

            <div class="field">
                <label for="name_en">{{ __('crm.branch_name_en_optional') }}</label>
                <input id="name_en" name="name_en" maxlength="150" dir="ltr" value="{{ old('name_en') }}" placeholder="e.g. Cairo Branch / Alexandria Branch">
            </div>

            <div class="field">
                <label for="code">{{ __('crm.branch_code_slug') }} <span style="color:var(--red)">*</span></label>
                <input id="code" name="code" required maxlength="50" dir="ltr" value="{{ old('code') }}" placeholder="cairo-main">
                <div class="hint">{{ __('crm.branch_code_hint') }}</div>
            </div>

            <div class="field">
                <label for="phone">{{ __('crm.branch_phone_optional') }}</label>
                <input id="phone" name="phone" maxlength="50" dir="ltr" value="{{ old('phone') }}" placeholder="0223456789">
            </div>

            <div class="field full">
                <label for="address">{{ __('crm.branch_address_optional') }}</label>
                <textarea id="address" name="address" rows="2" maxlength="255" placeholder="{{ __('crm.branch_address_placeholder') }}">{{ old('address') }}</textarea>
            </div>

            <div class="field full">
                <label class="check-card" style="cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                    <span>
                        <strong>{{ __('crm.active_branch_label') }}</strong>
                        <small>{{ __('crm.active_branch_hint') }}</small>
                    </span>
                </label>
            </div>
        </div>

        <div class="actions" style="margin-top:24px">
            <button class="btn primary" type="submit">
                <i class="bi bi-check-lg"></i> {{ __('crm.save') }}
            </button>
            <a class="btn" href="{{ route('v2.settings.branches.index') }}">
                {{ __('crm.cancel') }}
            </a>
        </div>
    </form>
</section>
@endsection

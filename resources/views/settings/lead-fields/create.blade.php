@extends('settings.layout')

@section('title', __('crm.lf_new_field'))
@section('heading', __('crm.lf_new_field'))
@section('subheading', __('crm.lead_fields_desc'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.lf_new_field') }}</h2>
            <p>{{ __('crm.lf_new_field_desc') }}</p>
        </div>
        <a class="btn soft" href="{{ route('v2.settings.fields.index') }}">
            <i class="bi bi-arrow-left"></i> {{ __('crm.cancel') }}
        </a>
    </div>

    <form method="POST" action="{{ route('v2.settings.fields.store') }}">
        @csrf
        @include('settings.lead-fields._form', ['field' => $field, 'optionsInput' => old('options_input', '')])

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px">
            <a href="{{ route('v2.settings.fields.index') }}" class="btn soft">{{ __('crm.cancel') }}</a>
            <button type="submit" class="btn primary" style="min-width:160px">
                <i class="bi bi-plus-lg"></i> {{ __('crm.save') }}
            </button>
        </div>
    </form>
</section>
@endsection

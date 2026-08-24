@extends('settings.layout')

@section('title', __('crm.lf_edit_field'))
@section('heading', __('crm.lf_edit_field'))
@section('subheading', $field->label())

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.lf_edit_field') }}: {{ $field->label() }}</h2>
            @if ($field->is_system)
                <p><span class="badge system">{{ __('crm.lf_system_badge') }}</span> {{ __('crm.lf_system_edit_note') }}</p>
            @endif
        </div>
        <a class="btn soft" href="{{ route('v2.settings.fields.index') }}">
            <i class="bi bi-arrow-left"></i> {{ __('crm.cancel') }}
        </a>
    </div>

    <form method="POST" action="{{ route('v2.settings.fields.update', $field) }}">
        @csrf
        @method('PATCH')
        @include('settings.lead-fields._form', ['field' => $field, 'optionsInput' => $optionsInput])

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px">
            <a href="{{ route('v2.settings.fields.index') }}" class="btn soft">{{ __('crm.cancel') }}</a>
            <button type="submit" class="btn primary" style="min-width:160px">
                <i class="bi bi-check2"></i> {{ __('crm.save') }}
            </button>
        </div>
    </form>
</section>
@endsection

@extends('settings.layout')

@section('title', __('crm.new_group_title'))
@section('heading', __('crm.add_group'))
@section('subheading', __('crm.create_group_subtitle'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.group_data') }}</h2>
            <p>{{ __('crm.new_group_no_permissions') }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('v2.settings.groups.store') }}">
        @csrf
        @include('settings.groups._form')
    </form>
</section>
@endsection

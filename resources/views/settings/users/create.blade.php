@extends('settings.layout')

@section('title', __('crm.new_user_title'))
@section('heading', __('crm.add_user'))
@section('subheading', __('crm.create_user_subtitle'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.account_data') }}</h2>
            <p>{{ __('crm.selected_groups_notice') }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('v2.settings.users.store') }}">
        @csrf
        @include('settings.users._form')
    </form>
</section>
@endsection

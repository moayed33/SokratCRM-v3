@extends('settings.layout')

@section('title', __('crm.new_user_title'))
@section('heading', __('crm.add_user'))
@section('content')
<section class="panel">
    <div class="panel-head" style="margin-bottom: 20px;">
        <h2><i class="bi bi-person-plus-fill" style="color:var(--red); margin-inline-end: 8px;"></i>{{ __('crm.add_user') }}</h2>
    </div>
    <form method="POST" action="{{ route('v2.settings.users.store') }}">
        @csrf
        @include('settings.users._form')
    </form>
</section>
@endsection

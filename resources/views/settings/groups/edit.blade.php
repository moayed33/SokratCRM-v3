@extends('settings.layout')

@section('title', __('crm.edit_group'))
@section('heading', __('crm.edit_group'))
@section('subheading', $group->name.' · '.$group->code)

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.group_data') }}</h2>
            <p>{{ $group->is_system ? 'رمز مجموعة النظام محمي ولا يمكن تغييره.' : 'تغيير الرمز لا يغير صلاحيات المجموعة.' }}</p>
        </div>
        @if ($group->is_system)<span class="badge system">{{ __('crm.system_group') }}</span>@endif
    </div>
    <form method="POST" action="{{ route('v2.settings.groups.update', $group) }}">
        @csrf
        @method('PATCH')
        @include('settings.groups._form')
    </form>
</section>
@endsection

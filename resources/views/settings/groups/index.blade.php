@extends('settings.layout')

@section('title', __('crm.groups'))
@section('heading', __('crm.groups'))
@section('subheading', __('crm.groups_index_subheading'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.access_groups') }}</h2>
            <p>{{ __('crm.group_delete_notice') }}</p>
        </div>
        @can('groups.create')
            <a class="btn primary" href="{{ route('v2.settings.groups.create') }}">{{ __('crm.new_group') }}</a>
        @endcan
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>{{ __('crm.group') }}</th>
                    <th>{{ __('crm.code') }}</th>
                    <th>{{ __('crm.users') }}</th>
                    <th>{{ __('crm.permissions') }}</th>
                    <th>{{ __('crm.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr>
                        <td>
                            <strong>{{ $group->name }}</strong>
                            @if ($group->is_system)<span class="badge system">{{ __('crm.system') }}</span>@endif
                            <div class="hint">{{ $group->description }}</div>
                        </td>
                        <td><code>{{ $group->code }}</code></td>
                        <td>{{ number_format($group->users_count) }}</td>
                        <td>{{ number_format($group->permissions_count) }}</td>
                        <td>
                            <div class="actions">
                                @can('groups.update')
                                    <a class="btn small" href="{{ route('v2.settings.groups.edit', $group) }}">{{ __('crm.edit') }}</a>
                                @endcan
                                @can('groups.delete')
                                    @if (!$group->is_system)
                                        <form method="POST" action="{{ route('v2.settings.groups.destroy', $group) }}" onsubmit="return confirm(@json(__('crm.confirm_delete_group')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn small danger">{{ __('crm.delete') }}</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

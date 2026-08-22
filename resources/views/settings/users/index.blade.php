@extends('settings.layout')

@section('title', __('crm.users'))
@section('heading', __('crm.users'))
@section('subheading', __('crm.users_subtitle'))

@section('content')
<section class="panel" data-ar-label="{{ __('crm.users_legacy', [], 'ar') }}">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.user_list') }}</h2>
            <p>{{ __('crm.inactive_user_notice') }}</p>
        </div>
        @can('users.create')
            <a class="btn primary" href="{{ route('v2.settings.users.create') }}">{{ __('crm.new_user') }}</a>
        @endcan
    </div>

    <form class="toolbar" method="GET" action="{{ route('v2.settings.users.index') }}">
        <div class="search">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('crm.user_search_placeholder') }}">
            <button class="btn soft">{{ __('crm.search') }}</button>
            @if ($search !== '')
                <a class="btn" href="{{ route('v2.settings.users.index') }}">{{ __('crm.cancel') }}</a>
            @endif
        </div>
    </form>

    <div class="table-wrap" style="margin-top:18px">
        <table>
            <thead>
                <tr>
                    <th>{{ __('crm.user') }}</th>
                    <th>{{ __('crm.username') }}</th>
                    <th>{{ __('crm.branch') }}</th>
                    @if(!empty($isVoipConnected))
                    <th>{{ __('crm.voip_extension') }}</th>
                    @endif
                    <th>{{ __('crm.groups') }}</th>
                    <th>{{ __('crm.status_th') }}</th>
                    <th>{{ __('crm.last_login') }}</th>
                    <th>{{ __('crm.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $managedUser)
                    <tr>
                        <td>
                            <strong>{{ $managedUser->name }}</strong>
                            <div class="hint">{{ $managedUser->email ?: __('crm.no_email') }}</div>
                        </td>
                        <td><code>{{ $managedUser->username }}</code></td>
                        <td>
                            @if ($managedUser->branch)
                                <span class="badge" style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe">
                                    <i class="bi bi-buildings"></i> {{ $managedUser->branch->name_ar }}
                                </span>
                            @else
                                <span class="hint">—</span>
                            @endif
                        </td>
                        @if(!empty($isVoipConnected))
                        <td>
                            @if ($managedUser->voip_extension)
                                <span class="badge active" style="font-family:monospace">📞 {{ $managedUser->voip_extension }}</span>
                            @else
                                <span class="hint">—</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            @forelse ($managedUser->groups as $group)
                                <span class="badge {{ $group->isSuperAdmin() ? 'system' : '' }}">{{ $group->name }}</span>
                            @empty
                                <span class="hint">{{ __('crm.no_group') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge {{ $managedUser->is_active ? 'active' : 'inactive' }}">
                                {{ $managedUser->is_active ? __('crm.active') : __('crm.inactive') }}
                            </span>
                        </td>
                        <td>{{ $managedUser->last_login_at?->format('Y-m-d H:i') ?? __('crm.never_logged_in') }}</td>
                        <td>
                            <div class="actions">
                                @if (!$managedUser->isSuperAdmin() || auth()->user()->isSuperAdmin())
                                @can('users.update')
                                    <a class="btn small" href="{{ route('v2.settings.users.edit', $managedUser) }}">{{ __('crm.edit') }}</a>
                                @endcan
                                @can('users.activate')
                                    @if (!auth()->user()->is($managedUser))
                                        <form method="POST" action="{{ route('v2.settings.users.status', $managedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $managedUser->is_active ? 0 : 1 }}">
                                            <button class="btn small {{ $managedUser->is_active ? 'danger' : 'primary' }}">
                                                {{ $managedUser->is_active ? __('crm.deactivate') : __('crm.activate') }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ !empty($isVoipConnected) ? 8 : 7 }}" style="text-align:center;color:#697386;padding:30px">{{ __('crm.no_matching_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
</section>
@endsection

@extends('settings.layout')

@section('title', __('crm.access_control'))
@section('heading', __('crm.access_control'))
@section('subheading', __('crm.groups_index_subheading'))

@push('head')
<style>
    .access-tabs{
        display:flex;
        gap:6px;
        flex-wrap:wrap;
        background:var(--card,#fff);
        border:1px solid var(--line);
        padding:7px;
        border-radius:14px;
        margin-bottom:20px;
        position:sticky;
        top:12px;
        z-index:40;
        box-shadow:0 8px 24px rgba(15,23,42,.05)
    }
    .access-tab{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:11px 18px;
        border:0;
        border-radius:10px;
        background:transparent;
        color:#495367;
        font:inherit;
        font-weight:800;
        cursor:pointer;
        text-decoration:none;
        transition:background .15s ease,color .15s ease
    }
    .access-tab i{font-size:16px}
    .access-tab .count{
        min-width:24px;
        height:22px;
        display:inline-grid;
        place-items:center;
        padding:0 7px;
        border-radius:999px;
        background:#eef2f7;
        color:#536078;
        font-size:11px;
        font-weight:900
    }
    .access-tab:hover{background:#f5f6f9;color:var(--ink)}
    .access-tab.active{background:var(--red);color:#fff}
    .access-tab.active .count{background:#ffffff2e;color:#fff}
    .access-panel[hidden]{display:none}
    @media(max-width:900px){
        .access-tabs{top:0;border-radius:0;margin-inline:-18px;padding-inline:12px}
    }
    html.dark-mode .access-tabs{
        background:#18181b!important;
        border-color:rgba(255,255,255,0.08)!important;
        box-shadow:none!important;
    }
    html.dark-mode .access-tab{
        color:#a1a1aa!important;
    }
    html.dark-mode .access-tab:hover{
        background:rgba(255,255,255,0.06)!important;
        color:#f4f4f5!important;
    }
    html.dark-mode .access-tab.active{
        background:var(--red)!important;
        color:#fff!important;
    }
    html.dark-mode .access-tab .count{
        background:rgba(255,255,255,0.08)!important;
        color:#d4d4d8!important;
    }
</style>
@endpush

@section('content')
<nav class="access-tabs" role="tablist" aria-label="{{ __('crm.access_control') }}" data-access-tabs>
    <button type="button" class="access-tab active" role="tab" aria-selected="true" aria-controls="panel-users" data-access-tab="users" id="tab-users">
        <i class="bi bi-people"></i>
        <span>{{ __('crm.users') }}</span>
        <span class="count">{{ number_format($users->total()) }}</span>
    </button>
    <button type="button" class="access-tab" role="tab" aria-selected="false" aria-controls="panel-groups" data-access-tab="groups" id="tab-groups">
        <i class="bi bi-diagram-3"></i>
        <span>{{ __('crm.groups') }}</span>
        <span class="count">{{ number_format($groups->count()) }}</span>
    </button>
    <button type="button" class="access-tab" role="tab" aria-selected="false" aria-controls="panel-permissions" data-access-tab="permissions" id="tab-permissions">
        <i class="bi bi-shield-lock"></i>
        <span>{{ __('crm.permissions') }}</span>
        <span class="count">{{ number_format($permissionsCount) }}</span>
    </button>
</nav>

{{-- ============ USERS ============ --}}
<section class="access-panel panel" id="panel-users" role="tabpanel" aria-labelledby="tab-users" data-access-panel="users" @if($activeTab !== 'users') hidden @endif>
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
        <input type="hidden" name="tab" value="users">
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
                                <span class="badge active" style="font-family:monospace"><i class="bi bi-telephone"></i> {{ $managedUser->voip_extension }}</span>
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

{{-- ============ GROUPS ============ --}}
<section class="access-panel panel" id="panel-groups" role="tabpanel" aria-labelledby="tab-groups" data-access-panel="groups" @if($activeTab !== 'groups') hidden @endif>
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

{{-- ============ PERMISSIONS ============ --}}
<section class="access-panel panel" id="panel-permissions" role="tabpanel" aria-labelledby="tab-permissions" data-access-panel="permissions" @if($activeTab !== 'permissions') hidden @endif>
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.permissions_by_group') }}</h2>
            <p>{{ __('crm.permissions_matrix_desc') }}</p>
        </div>
    </div>

    @php($canEditPermissions = auth()->user()->can('groups.assign_permissions'))
    <form method="POST" action="{{ route('v2.settings.permissions.update') }}">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="permission-table">
                <thead>
                    <tr>
                        <th>{{ __('crm.permission_col') }}</th>
                        @foreach ($groups as $group)
                            <th>
                                {{ $group->name }}
                                @if ($group->is_system)<span class="badge system">{{ __('crm.protected') }}</span>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissionsByModule as $module => $permissions)
                        <tr class="module-row">
                            <td colspan="{{ $groups->count() + 1 }}">
                                {{ $moduleLabels[$module] ?? $module }}
                            </td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td>
                                    <strong>{{ $permission->name_ar }}</strong>
                                </td>
                                @foreach ($groups as $group)
                                    @php($checked = $group->isSuperAdmin() || $group->permissions->contains('code', $permission->code))
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="permissions[{{ $group->id }}][]"
                                            value="{{ $permission->code }}"
                                            @checked($checked)
                                            @disabled(!$canEditPermissions || $group->isSuperAdmin())
                                            aria-label="{{ $permission->name_ar }} - {{ $group->name }}"
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($canEditPermissions)
            <div class="actions" style="margin-top:20px">
                <button class="btn primary">{{ __('crm.save_permissions') }}</button>
            </div>
        @endif
    </form>
</section>

<script>
    (() => {
        const tabs = document.querySelectorAll('[data-access-tab]');
        const panels = document.querySelectorAll('[data-access-panel]');
        if (!tabs.length || !panels.length) return;

        const validTabs = new Set(Array.from(tabs, (tab) => tab.dataset.accessTab));

        const activate = (name, updateHash) => {
            if (!validTabs.has(name)) return;

            tabs.forEach((tab) => {
                const selected = tab.dataset.accessTab === name;
                tab.classList.toggle('active', selected);
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.accessPanel !== name;
            });

            if (updateHash && window.history.replaceState) {
                window.history.replaceState(null, '', '#' + name);
            }
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.accessTab, true));
        });

        window.addEventListener('hashchange', () => activate(location.hash.slice(1), false));

        activate(location.hash.slice(1) || document.querySelector('.access-tab.active')?.dataset.accessTab || 'users', false);

        // After a search submit or form error, make sure the users panel is visible.
        @if ($activeTab !== 'users')
            activate({!! json_encode($activeTab) !!}, false);
        @endif
    })();
</script>
@endsection

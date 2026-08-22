@extends('settings.layout')

@section('title', __('crm.branches'))
@section('heading', __('crm.branches'))
@section('subheading', __('crm.manage_branches_subtitle'))

@section('content')
<div class="grid stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <span>{{ __('crm.total_branches_count') }}</span>
        <b>{{ number_format($stats['total']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.active_branches_count') }}</span>
        <b style="color:#16a34a">{{ number_format($stats['active']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.assigned_employees_count') }}</span>
        <b style="color:#2563eb">{{ number_format($stats['total_users']) }}</b>
    </div>
    <div class="stat-card">
        <span>{{ __('crm.leads_and_donors_count') }}</span>
        <b style="color:#dc2637">{{ number_format($stats['total_leads']) }}</b>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.branches_list') }}</h2>
            <p>{{ __('crm.branches_list_desc') }}</p>
        </div>
        @can('branches.create')
            <a class="btn primary" href="{{ route('v2.settings.branches.create') }}">
                <i class="bi bi-plus-lg"></i> {{ __('crm.new_branch') }}
            </a>
        @endcan
    </div>

    <form class="toolbar" method="GET" action="{{ route('v2.settings.branches.index') }}">
        <div class="search">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('crm.search_branches_placeholder') }}">
            <button class="btn soft">{{ __('crm.search') }}</button>
            @if ($search !== '')
                <a class="btn" href="{{ route('v2.settings.branches.index') }}">{{ __('crm.cancel') }}</a>
            @endif
        </div>
    </form>

    <div class="table-wrap" style="margin-top:18px">
        <table>
            <thead>
                <tr>
                    <th>{{ __('crm.branch_name_th') }}</th>
                    <th>{{ __('crm.branch_code_th') }}</th>
                    <th>{{ __('crm.contact_info_th') }}</th>
                    <th>{{ __('crm.employees_th') }}</th>
                    <th>{{ __('crm.leads_th') }}</th>
                    <th>{{ __('crm.status_th') }}</th>
                    <th>{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="display:inline-grid;place-items:center;width:32px;height:32px;border-radius:8px;background:rgba(220,38,55,0.08);color:var(--red)">
                                    <i class="bi bi-buildings"></i>
                                </span>
                                <div>
                                    <strong>{{ $branch->name_ar }}</strong>
                                    @if ($branch->name_en)
                                        <div class="hint" dir="ltr" style="text-align:start">{{ $branch->name_en }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><code>{{ $branch->code }}</code></td>
                        <td>
                            @if ($branch->phone)
                                <div style="font-size:13px"><i class="bi bi-telephone"></i> <span dir="ltr">{{ $branch->phone }}</span></div>
                            @endif
                            @if ($branch->address)
                                <div class="hint"><i class="bi bi-geo-alt"></i> {{ $branch->address }}</div>
                            @endif
                            @if (!$branch->phone && !$branch->address)
                                <span class="hint">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="font-weight:700">
                                <i class="bi bi-people"></i> {{ number_format($branch->users_count) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge active" style="font-weight:700">
                                <i class="bi bi-person-heart"></i> {{ number_format($branch->leads_count) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $branch->is_active ? 'active' : 'inactive' }}">
                                {{ $branch->is_active ? __('crm.active') : __('crm.inactive') }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                @can('branches.update')
                                    <a class="btn small" href="{{ route('v2.settings.branches.edit', $branch) }}">
                                        <i class="bi bi-pencil"></i> {{ __('crm.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('v2.settings.branches.toggle', $branch) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn small {{ $branch->is_active ? 'danger' : 'primary' }}" type="submit">
                                            {{ $branch->is_active ? __('crm.deactivate') : __('crm.activate') }}
                                        </button>
                                    </form>
                                @endcan
                                @can('branches.delete')
                                    @if ($branch->leads_count === 0 && $branch->users_count === 0 && $branch->campaigns_count === 0)
                                        <form method="POST" action="{{ route('v2.settings.branches.destroy', $branch) }}" onsubmit="return confirm(@json(__('crm.confirm_delete_branch')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#697386;padding:36px">
                            <i class="bi bi-buildings" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.5"></i>
                            {{ __('crm.no_branches_match_search') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($branches->hasPages())
        <div class="pagination">{{ $branches->links() }}</div>
    @endif
</section>
@endsection

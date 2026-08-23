@extends('settings.layout')

@section('title', __('crm.permissions'))
@section('heading', __('crm.permissions_matrix'))
@section('subheading', __('crm.permissions_inheritance'))

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.permissions_by_group') }}</h2>
            <p>صلاحيات مدير النظام كاملة ومحمية. ويمكن ضبط صلاحيات بقية المجموعات من هذه المصفوفة. عند منح «عرض العملاء» دون نطاق «جميع العملاء» أو «مجموعات المستخدم»، يقتصر العرض تلقائيًا على العملاء المسندة للمستخدم أو المنشأة بواسطته.</p>
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
                        <th>الصلاحية</th>
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
@endsection

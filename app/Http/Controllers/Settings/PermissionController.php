<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePermissionMatrixRequest;
use App\Models\Group;
use App\Models\Permission;
use App\Security\CrmPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('code')
            ->get();

        return view('settings.permissions.index', [
            'groups' => Group::query()
                ->with('permissions:id,code')
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(),
            'permissionsByModule' => $permissions->groupBy('module'),
            'moduleLabels' => CrmPermission::moduleLabels(),
        ]);
    }

    public function update(
        UpdatePermissionMatrixRequest $request,
    ): RedirectResponse {
        $submitted = $request->validated('permissions', []);
        $permissionIds = Permission::query()->pluck('id', 'code');

        DB::transaction(function () use (
            $submitted,
            $permissionIds,
        ): void {
            Group::query()
                ->where('code', '<>', Group::SUPER_ADMIN_CODE)
                ->each(function (Group $group) use (
                    $submitted,
                    $permissionIds,
                ): void {
                    $codes = $submitted[(string) $group->id]
                        ?? $submitted[$group->id]
                        ?? [];

                    $ids = collect($codes)
                        ->unique()
                        ->map(
                            static fn (string $code): int => (int) $permissionIds->get($code),
                        )
                        ->filter()
                        ->values()
                        ->all();

                    $group->permissions()->sync($ids);
                });
        });

        return back()->with('success', 'تم حفظ مصفوفة الصلاحيات.');
    }
}

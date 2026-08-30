<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\BuildsAccessPage;
use App\Http\Requests\Settings\UpdatePermissionMatrixRequest;
use App\Models\Group;
use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    use BuildsAccessPage;

    public function index(Request $request): View
    {
        return $this->buildAccessPage($request, 'permissions');
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

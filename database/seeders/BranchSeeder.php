<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $branch = Branch::query()->firstOrCreate(
                ['code' => 'main'],
                [
                    'name_ar' => 'الفرع الرئيسي',
                    'name_en' => 'Main Branch',
                    'phone' => null,
                    'address' => 'المقر الرئيسي',
                    'is_active' => true,
                ],
            );

            // Associate existing users and leads with the main branch
            User::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
            Lead::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
            Campaign::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);
            LeadFollowup::query()->whereNull('branch_id')->update(['branch_id' => $branch->id]);

            // Ensure branch permissions exist
            $permissions = [
                CrmPermission::BRANCHES_VIEW,
                CrmPermission::BRANCHES_CREATE,
                CrmPermission::BRANCHES_UPDATE,
                CrmPermission::BRANCHES_DELETE,
            ];

            foreach ($permissions as $permission) {
                Permission::query()->updateOrCreate(
                    ['code' => $permission->value],
                    [
                        'module' => $permission->module(),
                        'name_ar' => $permission->label(),
                    ],
                );
            }

            // Ensure super admin group has all branch permissions
            $superAdmin = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->first();
            if ($superAdmin) {
                $permissionIds = Permission::query()
                    ->whereIn('code', array_map(static fn ($p) => $p->value, $permissions))
                    ->pluck('id');

                $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
            }
        });
    }
}

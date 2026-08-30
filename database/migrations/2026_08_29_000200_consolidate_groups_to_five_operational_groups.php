<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add is_active column to groups table if it doesn't exist
        if (Schema::hasTable('groups') && ! Schema::hasColumn('groups', 'is_active')) {
            Schema::table('groups', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('is_system');
            });
        }

        DB::transaction(function (): void {
            // Ensure all enum permissions exist in permissions table
            foreach (CrmPermission::cases() as $permission) {
                Permission::query()->updateOrCreate(
                    ['code' => $permission->value],
                    [
                        'module' => $permission->module(),
                        'name_ar' => $permission->label(),
                    ],
                );
            }

            // 2. Define the 5 target system groups and their permissions
            $targetGroupDefs = [
                Group::SUPER_ADMIN_CODE => [
                    'name' => 'سوبر أدمن',
                    'description' => 'وصول كامل ومحمي إلى جميع وظائف وفروع النظام (Super Admin)',
                    'is_system' => true,
                    'is_active' => true,
                    'permissions' => CrmPermission::values(),
                ],
                'branch-admin' => [
                    'name' => 'أدمن الفرع',
                    'description' => 'إدارة كاملة لعمليات وموظفي وتحصيلات الفرع (Branch Admin)',
                    'is_system' => true,
                    'is_active' => true,
                    'permissions' => [
                        CrmPermission::DASHBOARD_VIEW->value,
                        CrmPermission::LEADS_VIEW->value,
                        CrmPermission::LEADS_SCOPE_ALL->value,
                        CrmPermission::LEADS_SCOPE_GROUP->value,
                        CrmPermission::LEADS_ASSIGN->value,
                        CrmPermission::LEADS_CREATE->value,
                        CrmPermission::LEADS_UPDATE->value,
                        CrmPermission::LEADS_DELETE->value,
                        CrmPermission::LEADS_IMPORT->value,
                        CrmPermission::LEADS_EXPORT->value,
                        CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                        CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                        CrmPermission::TASKS_VIEW->value,
                        CrmPermission::CAMPAIGNS_VIEW->value,
                        CrmPermission::CAMPAIGNS_CREATE->value,
                        CrmPermission::CAMPAIGNS_REPORTS->value,
                        CrmPermission::REPORTS_VIEW->value,
                        CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
                        CrmPermission::USERS_VIEW->value,
                        CrmPermission::USERS_CREATE->value,
                        CrmPermission::USERS_UPDATE->value,
                        CrmPermission::USERS_ACTIVATE->value,
                        CrmPermission::USERS_RESET_PASSWORD->value,
                        CrmPermission::VOIP_VIEW->value,
                        CrmPermission::VOIP_RECORDINGS->value,
                        CrmPermission::VOIP_LIVE_PANEL->value,
                        CrmPermission::CALENDAR_VIEW->value,
                        CrmPermission::CALENDAR_MANAGE->value,
                        CrmPermission::COLLECTIONS_VIEW->value,
                        CrmPermission::COLLECTIONS_COLLECT->value,
                        CrmPermission::COLLECTIONS_ASSIGN->value,
                        CrmPermission::COLLECTIONS_MANAGE->value,
                        CrmPermission::COLLECTIONS_COMPLETE->value,
                        CrmPermission::COLLECTIONS_CANCEL->value,
                        CrmPermission::COLLECTIONS_REPORTS->value,
                        CrmPermission::COLLECTIONS_METHODS_MANAGE->value,
                        CrmPermission::BRANCHES_VIEW->value,
                        CrmPermission::BRANCHES_UPDATE->value,
                    ],
                ],
                'manager' => [
                    'name' => 'مدير',
                    'description' => 'إشراف وإدارة فرق المبيعات والتحصيل والمتابعات والتقارير التشغيلية (Manager)',
                    'is_system' => true,
                    'is_active' => true,
                    'permissions' => [
                        CrmPermission::DASHBOARD_VIEW->value,
                        CrmPermission::LEADS_VIEW->value,
                        CrmPermission::LEADS_SCOPE_GROUP->value,
                        CrmPermission::LEADS_ASSIGN->value,
                        CrmPermission::LEADS_CREATE->value,
                        CrmPermission::LEADS_UPDATE->value,
                        CrmPermission::LEADS_EXPORT->value,
                        CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                        CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                        CrmPermission::TASKS_VIEW->value,
                        CrmPermission::CAMPAIGNS_VIEW->value,
                        CrmPermission::CAMPAIGNS_REPORTS->value,
                        CrmPermission::REPORTS_VIEW->value,
                        CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
                        CrmPermission::VOIP_VIEW->value,
                        CrmPermission::VOIP_RECORDINGS->value,
                        CrmPermission::CALENDAR_VIEW->value,
                        CrmPermission::CALENDAR_MANAGE->value,
                        CrmPermission::COLLECTIONS_VIEW->value,
                        CrmPermission::COLLECTIONS_ASSIGN->value,
                        CrmPermission::COLLECTIONS_MANAGE->value,
                        CrmPermission::COLLECTIONS_COMPLETE->value,
                        CrmPermission::COLLECTIONS_CANCEL->value,
                        CrmPermission::COLLECTIONS_REPORTS->value,
                    ],
                ],
                'collector' => [
                    'name' => 'محصل',
                    'description' => 'تنفيذ حالات التحصيل المسندة وتأكيد استلام التبرعات (Collector)',
                    'is_system' => true,
                    'is_active' => true,
                    'permissions' => [
                        CrmPermission::DASHBOARD_VIEW->value,
                        CrmPermission::COLLECTIONS_VIEW->value,
                        CrmPermission::COLLECTIONS_COLLECT->value,
                        CrmPermission::COLLECTIONS_COMPLETE->value,
                        CrmPermission::CALENDAR_VIEW->value,
                        CrmPermission::TASKS_VIEW->value,
                        CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                    ],
                ],
                'employee' => [
                    'name' => 'موظف',
                    'description' => 'التعامل اليومي مع العملاء والمتابعات والمهام والتقويم (Employee)',
                    'is_system' => true,
                    'is_active' => true,
                    'permissions' => [
                        CrmPermission::DASHBOARD_VIEW->value,
                        CrmPermission::LEADS_VIEW->value,
                        CrmPermission::LEADS_CREATE->value,
                        CrmPermission::LEADS_UPDATE->value,
                        CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                        CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                        CrmPermission::TASKS_VIEW->value,
                        CrmPermission::CALENDAR_VIEW->value,
                        CrmPermission::VOIP_VIEW->value,
                    ],
                ],
            ];

            $createdGroups = [];
            $allPerms = Permission::query()->pluck('id', 'code');

            foreach ($targetGroupDefs as $code => $def) {
                $group = Group::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $def['name'],
                        'description' => $def['description'],
                        'is_system' => $def['is_system'],
                        'is_active' => $def['is_active'],
                    ],
                );

                $permIds = collect($def['permissions'])
                    ->map(fn (string $permCode) => $allPerms->get($permCode))
                    ->filter()
                    ->values()
                    ->all();

                $group->permissions()->sync($permIds);
                $createdGroups[$code] = $group;
            }

            // Sync dynamic lead assignment permissions
            LeadAssignment::synchronizeGroupPermissions();

            // Refresh permission IDs after dynamic permissions sync
            $allPerms = Permission::query()->pluck('id', 'code');

            // Attach dynamic lead assignment permissions for manager, branch-admin, super-admin
            $assignableGroupCodes = ['employee', 'collector', 'manager', 'branch-admin'];
            $assignablePermCodes = [];
            foreach ($assignableGroupCodes as $gCode) {
                if (isset($createdGroups[$gCode])) {
                    $assignablePermCodes[] = LeadAssignment::groupPermissionCode($createdGroups[$gCode]);
                }
            }

            $assignablePermIds = collect($assignablePermCodes)
                ->map(fn (string $code) => $allPerms->get($code))
                ->filter()
                ->values()
                ->all();

            $createdGroups['manager']->permissions()->syncWithoutDetaching($assignablePermIds);
            $createdGroups['branch-admin']->permissions()->syncWithoutDetaching($assignablePermIds);
            $createdGroups[Group::SUPER_ADMIN_CODE]->permissions()->syncWithoutDetaching(Permission::query()->pluck('id')->all());

            // 3. User mapping from legacy groups to target groups
            $userMapping = [
                'sales-manager' => $createdGroups['manager']->id,
                'team-leader' => $createdGroups['manager']->id,
                'sales-supervisor' => $createdGroups['manager']->id,
                'campaign-manager' => $createdGroups['manager']->id,
                'collection-manager' => $createdGroups['manager']->id,

                'sales-agent' => $createdGroups['employee']->id,
                'read-only' => $createdGroups['employee']->id,
                'voip-admin' => $createdGroups['employee']->id,

                'field-collector' => $createdGroups['collector']->id,
                'collector' => $createdGroups['collector']->id,

                'super-admin' => $createdGroups[Group::SUPER_ADMIN_CODE]->id,
                'branch-admin' => $createdGroups['branch-admin']->id,
            ];

            // Re-assign users
            $allUsers = User::with('groups')->get();
            foreach ($allUsers as $user) {
                $newGroupIds = [];
                foreach ($user->groups as $oldGroup) {
                    if (isset($userMapping[$oldGroup->code])) {
                        $newGroupIds[] = $userMapping[$oldGroup->code];
                    }
                }
                $newGroupIds = array_unique($newGroupIds);
                if (! empty($newGroupIds)) {
                    $user->groups()->sync($newGroupIds);
                }
            }

            // 4. Archive legacy groups (mark is_active = false, is_system = false)
            $legacyCodes = [
                'sales-manager',
                'sales-agent',
                'read-only',
                'collection-manager',
                'team-leader',
                'field-collector',
                'sales-supervisor',
                'campaign-manager',
                'voip-admin',
            ];

            Group::query()->whereIn('code', $legacyCodes)->update([
                'is_active' => false,
                'is_system' => false,
            ]);
        });
    }

    public function down(): void
    {
        // Revert target groups is_system if needed
        Group::query()->whereIn('code', ['employee', 'manager', 'branch-admin'])->update(['is_system' => false]);
    }
};

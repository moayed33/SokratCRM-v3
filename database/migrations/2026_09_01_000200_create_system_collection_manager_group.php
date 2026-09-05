<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Permission;
use App\Security\CrmPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            if (! Schema::hasTable('groups') || ! Schema::hasTable('permissions')) {
                return;
            }

            // Define the 17 permissions for Collection Manager across the 4 parts
            $permissionCodes = [
                // Part 1: Core Collection Permissions
                CrmPermission::COLLECTIONS_VIEW->value,
                CrmPermission::COLLECTIONS_ASSIGN->value,
                CrmPermission::COLLECTIONS_MANAGE->value,
                CrmPermission::COLLECTIONS_CANCEL->value,
                CrmPermission::COLLECTIONS_REPORTS->value,

                // Part 2: Dashboard, Reports & Analytics
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::REPORTS_VIEW->value,
                CrmPermission::REPORTS_EMPLOYEES_VIEW->value,

                // Part 3: Donors, Follow-ups & Calendar
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_GROUP->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::TASKS_VIEW->value,
                CrmPermission::CALENDAR_VIEW->value,
                CrmPermission::CALENDAR_MANAGE->value,

                // Part 4: Advanced / Configuration Permissions
                CrmPermission::COLLECTIONS_METHODS_MANAGE->value,
                CrmPermission::COLLECTIONS_GEOGRAPHY_MANAGE->value,
                CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND->value,
            ];

            // Ensure permissions exist in the permissions table
            foreach ($permissionCodes as $code) {
                $permission = CrmPermission::tryFrom($code);
                if ($permission !== null) {
                    DB::table('permissions')->updateOrInsert(
                        ['code' => $code],
                        [
                            'module' => $permission->module(),
                            'name_ar' => $permission->label(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            // Create or update the system Collection Manager group
            $groupId = DB::table('groups')->where('code', Group::COLLECTION_MANAGER_CODE)->value('id');

            if ($groupId === null) {
                $groupId = DB::table('groups')->insertGetId([
                    'code' => Group::COLLECTION_MANAGER_CODE,
                    'name' => 'مسؤولو التحصيل',
                    'description' => 'إدارة طابور التحصيل وإسناد الحالات ومتابعة تقارير الفرع والوسائل والمناطق (Collection Manager)',
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('groups')->where('id', $groupId)->update([
                    'name' => 'مسؤولو التحصيل',
                    'description' => 'إدارة طابور التحصيل وإسناد الحالات ومتابعة تقارير الفرع والوسائل والمناطق (Collection Manager)',
                    'is_system' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            }

            $permissionIds = DB::table('permissions')->whereIn('code', $permissionCodes)->pluck('id');

            // Sync permissions for the collection manager group
            DB::table('group_permission')->where('group_id', $groupId)->delete();
            foreach ($permissionIds as $permId) {
                DB::table('group_permission')->insert([
                    'group_id' => $groupId,
                    'permission_id' => $permId,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Revert group to non-system or delete if needed
        if (Schema::hasTable('groups')) {
            DB::table('groups')
                ->where('code', Group::COLLECTION_MANAGER_CODE)
                ->update(['is_system' => false]);
        }
    }
};

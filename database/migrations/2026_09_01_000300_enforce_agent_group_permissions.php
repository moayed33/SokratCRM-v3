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
        if (! Schema::hasTable('groups') || ! Schema::hasTable('permissions')) {
            return;
        }

        DB::transaction(function (): void {
            $employeeGroup = Group::query()->where('code', Group::EMPLOYEE_CODE)->first();
            if (! $employeeGroup) {
                return;
            }

            // Exact permission set for agents/employees (No campaigns, no broad scoping)
            $exactAgentPermissions = [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_CREATE->value,
                CrmPermission::LEADS_UPDATE->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                CrmPermission::TASKS_VIEW->value,
                CrmPermission::CALENDAR_VIEW->value,
                CrmPermission::VOIP_VIEW->value,
                CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND->value,
            ];

            $permIds = Permission::query()
                ->whereIn('code', $exactAgentPermissions)
                ->pluck('id')
                ->all();

            $employeeGroup->permissions()->sync($permIds);
        });
    }

    public function down(): void
    {
        // No-op rollback
    }
};

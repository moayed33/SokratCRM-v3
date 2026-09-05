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
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('groups')) {
            return;
        }

        DB::transaction(function (): void {
            $perm = Permission::query()->firstOrCreate(
                ['code' => CrmPermission::LEADS_VIEW_FULL_PHONE->value],
                [
                    'module' => 'leads',
                    'name_ar' => CrmPermission::LEADS_VIEW_FULL_PHONE->label(),
                    'description' => 'View unmasked full phone numbers for leads and callers',
                ]
            );

            $groups = Group::query()
                ->whereIn('code', [Group::SUPER_ADMIN_CODE, 'branch-admin'])
                ->get();

            foreach ($groups as $group) {
                $group->permissions()->syncWithoutDetaching([$perm->id]);
            }
        });
    }

    public function down(): void
    {
        Permission::query()
            ->where('code', CrmPermission::LEADS_VIEW_FULL_PHONE->value)
            ->delete();
    }
};

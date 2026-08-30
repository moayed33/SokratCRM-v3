<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODE = 'notifications.manage';

    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'code' => self::CODE,
            'module' => 'notifications',
            'name_ar' => 'إدارة التنبيهات',
            'description' => 'Configure notification rules, recipients, and delivery channels.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where('code', self::CODE)->value('id');
        if (! $permissionId) {
            return;
        }

        $groupIds = DB::table('groups')->where('code', 'super-admin')->pluck('id');
        foreach ($groupIds as $groupId) {
            DB::table('group_permission')->insertOrIgnore([
                'group_id' => $groupId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', self::CODE)->value('id');
        if ($permissionId) {
            DB::table('group_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};

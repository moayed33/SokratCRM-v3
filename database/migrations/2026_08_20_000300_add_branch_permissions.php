<?php

declare(strict_types=1);

use App\Security\CrmPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODES = [
        'branches.view',
        'branches.create',
        'branches.update',
        'branches.delete',
    ];

    public function up(): void
    {
        $now = now();
        $records = [
            [
                'code' => 'branches.view',
                'module' => 'branches',
                'name_ar' => 'عرض الفروع',
                'description' => 'View list of branches and branch details.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'branches.create',
                'module' => 'branches',
                'name_ar' => 'إضافة الفروع',
                'description' => 'Create new branches.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'branches.update',
                'module' => 'branches',
                'name_ar' => 'تعديل الفروع',
                'description' => 'Edit branch details and toggle active status.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'branches.delete',
                'module' => 'branches',
                'name_ar' => 'حذف الفروع',
                'description' => 'Delete branches without leads.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($records as $record) {
            DB::table('permissions')->insertOrIgnore($record);
        }

        $permissionIds = DB::table('permissions')->whereIn('code', self::CODES)->pluck('id');
        $groupIds = DB::table('groups')->where('code', 'super-admin')->pluck('id');

        foreach ($groupIds as $groupId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('group_permission')->insertOrIgnore([
                    'group_id' => $groupId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('code', self::CODES)->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('group_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};

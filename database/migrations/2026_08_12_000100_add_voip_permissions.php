<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'production' => 'sokrat_crm_v2',
            'testing' => 'sokrat_crm_v2_testing',
            default => null,
        };
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();

        if (
            $connection->getDriverName() !== 'mysql'
            || $expectedDatabase === null
            || $database !== $expectedDatabase
        ) {
            throw new RuntimeException("Unexpected database: {$database}");
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();
        $now = now();

        $voipPermissions = [
            [
                'code' => 'voip.view',
                'name_ar' => 'عرض بيانات السنترال',
                'module' => 'voip',
                'description' => 'عرض سجل مكالمات السنترال والإحصائيات الخاصة بالأرقام والملحقات.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'voip.recordings',
                'name_ar' => 'الاستماع للمكالمات المسجلة',
                'module' => 'voip',
                'description' => 'الاستماع لتسجيلات المكالمات الواردة والصادرة.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'voip.live_panel',
                'name_ar' => 'عرض المراقبة المباشرة',
                'module' => 'voip',
                'description' => 'عرض لوحة المراقبة المباشرة للخطوط والملحقات في السنترال.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'voip.settings',
                'name_ar' => 'إعدادات السنترال',
                'module' => 'voip',
                'description' => 'إدارة بيانات الربط والاقتران مع خادم Sokrat VoIP.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($voipPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $perm['code']],
                $perm
            );
        }

        // Auto-grant all voip permissions to Super Admin group
        $superAdminGroup = DB::table('groups')->where('code', 'super_admin')->first();
        if ($superAdminGroup) {
            $permIds = DB::table('permissions')
                ->whereIn('code', array_column($voipPermissions, 'code'))
                ->pluck('id');

            foreach ($permIds as $permId) {
                DB::table('group_permission')->updateOrInsert([
                    'group_id' => $superAdminGroup->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();
        $codes = ['voip.view', 'voip.recordings', 'voip.live_panel', 'voip.settings'];

        $permIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('group_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};

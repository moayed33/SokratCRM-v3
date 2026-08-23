<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const GROUP_PERMISSION_PREFIX = 'leads.assign.group.';

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
            throw new RuntimeException(sprintf(
                'Refusing scoped lead permissions migration for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();
        $now = now();
        $basePermissions = [
            [
                'code' => 'leads.scope.all',
                'module' => 'leads',
                'name_ar' => 'نطاق العملاء: جميع العملاء',
                'description' => 'يسمح بتنفيذ صلاحيات العملاء الممنوحة على جميع سجلات العملاء.',
            ],
            [
                'code' => 'leads.scope.group',
                'module' => 'leads',
                'name_ar' => 'نطاق العملاء: مجموعات المستخدم',
                'description' => 'يوسع نطاق العميل إلى العملاء المسندين لمستخدمين يشاركون المستخدم في مجموعة.',
            ],
            [
                'code' => 'leads.assign',
                'module' => 'leads',
                'name_ar' => 'إسناد العملاء لمستخدم آخر',
                'description' => 'يفعّل اختيار موظف آخر مع اشتراط صلاحية مجموعة الموظف المستهدف.',
            ],
        ];

        foreach ($basePermissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $permission['code']],
                $permission + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $groups = DB::table('groups')->get(['id', 'name', 'code']);

        foreach ($groups as $group) {
            DB::table('permissions')->updateOrInsert(
                ['code' => self::GROUP_PERMISSION_PREFIX.$group->id],
                [
                    'module' => 'leads',
                    'name_ar' => 'إسناد عميل إلى مجموعة: '.$group->name,
                    'description' => 'يسمح باختيار مستخدم نشط من هذه المجموعة عند إضافة عميل أو إعادة إسناده.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'code');
        $groupsByCode = $groups->keyBy('code');
        $grants = [
            'super-admin' => $permissionIds->keys()->all(),
            'sales-manager' => [
                'leads.scope.all',
                'leads.assign',
                isset($groupsByCode['sales-manager'])
                    ? self::GROUP_PERMISSION_PREFIX.$groupsByCode['sales-manager']->id
                    : null,
                isset($groupsByCode['sales-agent'])
                    ? self::GROUP_PERMISSION_PREFIX.$groupsByCode['sales-agent']->id
                    : null,
            ],
            'read-only' => [
                'leads.scope.all',
            ],
        ];

        foreach ($grants as $groupCode => $codes) {
            $group = $groupsByCode[$groupCode] ?? null;

            if ($group === null) {
                continue;
            }

            foreach (array_filter($codes) as $code) {
                $permissionId = $permissionIds[$code] ?? null;

                if ($permissionId === null) {
                    continue;
                }

                DB::table('group_permission')->insertOrIgnore([
                    'group_id' => $group->id,
                    'permission_id' => $permissionId,
                ]);
            }
        }

    }

    public function down(): void
    {
        $this->verifyDatabase();
        $permissionIds = DB::table('permissions')
            ->whereIn('code', [
                'leads.scope.all',
                'leads.scope.group',
                'leads.assign',
            ])
            ->orWhere('code', 'like', self::GROUP_PERMISSION_PREFIX.'%')
            ->pluck('id');

        DB::table('group_permission')
            ->whereIn('permission_id', $permissionIds)
            ->delete();
        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CrmAccessControlSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedPermissions();
            $groups = $this->seedGroups();
            $this->seedAssignmentPermissions($groups);
            $admin = $this->seedCurrentAdministrator();

            $admin->groups()->syncWithoutDetaching([
                $groups[Group::SUPER_ADMIN_CODE]->id,
            ]);

            $this->backfillAdministratorAttribution($admin);
        });
    }

    private function seedPermissions(): void
    {
        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ],
            );
        }
    }

    /**
     * @return array<string, Group>
     */
    private function seedGroups(): array
    {
        $definitions = [
            Group::SUPER_ADMIN_CODE => [
                'name' => 'سوبر أدمن',
                'description' => 'وصول كامل ومحمي إلى جميع وظائف وفروع النظام (Super Admin)',
                'is_system' => true,
                'permissions' => CrmPermission::values(),
            ],
            'branch-admin' => [
                'name' => 'أدمن الفرع',
                'description' => 'إدارة كاملة لعمليات وموظفي وتحصيلات الفرع (Branch Admin)',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.scope.all',
                    'leads.scope.group',
                    'leads.assign',
                    'leads.create',
                    'leads.update',
                    'leads.delete',
                    'leads.import',
                    'leads.export',
                    'leads.followups.view',
                    'leads.followups.create',
                    'tasks.view',
                    'campaigns.view',
                    'campaigns.create',
                    'campaigns.reports',
                    'reports.view',
                    'reports.employees.view',
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.activate',
                    'users.reset_password',
                    'voip.view',
                    'voip.recordings',
                    'voip.live_panel',
                    'calendar.view',
                    'calendar.manage',
                    'collections.view',
                    'collections.collect',
                    'collections.assign',
                    'collections.manage',
                    'collections.complete',
                    'collections.cancel',
                    'collections.reports',
                    'collections.methods.manage',
                    'branches.view',
                    'branches.update',
                ],
            ],
            'manager' => [
                'name' => 'مدير',
                'description' => 'إشراف وإدارة فرق المبيعات والتحصيل والمتابعات والتقارير التشغيلية (Manager)',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.scope.group',
                    'leads.assign',
                    'leads.create',
                    'leads.update',
                    'leads.export',
                    'leads.followups.view',
                    'leads.followups.create',
                    'tasks.view',
                    'campaigns.view',
                    'campaigns.reports',
                    'reports.view',
                    'reports.employees.view',
                    'voip.view',
                    'voip.recordings',
                    'calendar.view',
                    'calendar.manage',
                    'collections.view',
                    'collections.assign',
                    'collections.manage',
                    'collections.complete',
                    'collections.cancel',
                    'collections.reports',
                ],
            ],
            'collector' => [
                'name' => 'محصل',
                'description' => 'تنفيذ حالات التحصيل المسندة وتأكيد استلام التبرعات (Collector)',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'collections.view',
                    'collections.collect',
                    'collections.complete',
                    'calendar.view',
                    'tasks.view',
                    'leads.followups.view',
                ],
            ],
            'employee' => [
                'name' => 'موظف',
                'description' => 'التعامل اليومي مع العملاء والمتابعات والمهام والتقويم (Employee)',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.create',
                    'leads.update',
                    'leads.followups.view',
                    'leads.followups.create',
                    'tasks.view',
                    'calendar.view',
                    'voip.view',
                ],
            ],
        ];

        $permissionIds = Permission::query()
            ->pluck('id', 'code');
        $groups = [];

        foreach ($definitions as $code => $definition) {
            $group = Group::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => $definition['is_system'],
                ],
            );

            if ($group->isSuperAdmin() && ! $group->is_system) {
                $group->update(['is_system' => true]);
            }

            $group->permissions()->sync(
                collect($definition['permissions'])
                    ->map(
                        static fn (string $permissionCode): int => (int) $permissionIds->get($permissionCode),
                    )
                    ->filter()
                    ->values()
                    ->all(),
            );

            $groups[$code] = $group;
        }

        return $groups;
    }

    /**
     * @param  array<string, Group>  $groups
     */
    private function seedAssignmentPermissions(array $groups): void
    {
        LeadAssignment::synchronizeGroupPermissions();

        $groups[Group::SUPER_ADMIN_CODE]
            ->permissions()
            ->syncWithoutDetaching(
                Permission::query()->pluck('id')->all(),
            );

        $allPerms = Permission::query()->pluck('id', 'code');
        $assignablePermCodes = [];
        foreach (['employee', 'collector', 'manager', 'branch-admin'] as $gCode) {
            if (isset($groups[$gCode])) {
                $assignablePermCodes[] = LeadAssignment::groupPermissionCode($groups[$gCode]);
            }
        }

        $assignablePermIds = collect($assignablePermCodes)
            ->map(fn (string $code) => $allPerms->get($code))
            ->filter()
            ->values()
            ->all();

        if (isset($groups['manager'])) {
            $groups['manager']->permissions()->syncWithoutDetaching($assignablePermIds);
        }
        if (isset($groups['branch-admin'])) {
            $groups['branch-admin']->permissions()->syncWithoutDetaching($assignablePermIds);
        }
    }

    private function seedCurrentAdministrator(): User
    {
        $name = trim((string) config('crm.bootstrap_admin.name', 'مدير النظام'));
        $username = trim((string) config('crm.bootstrap_admin.username', 'admin'));
        $email = trim((string) config('crm.bootstrap_admin.email', 'admin@localhost.invalid'));
        $password = (string) (config('crm.bootstrap_admin.password') ?: 'Admin@123456');

        if ($username === '' || $password === '') {
            throw new RuntimeException(
                'CRM_V2_ADMIN_USER and CRM_V2_ADMIN_PASSWORD are required '
                .'to provision the default Super Admin.',
            );
        }
        $user = User::query()
            ->where('username', $username)
            ->first();

        if ($user === null && $email !== '') {
            $user = User::query()
                ->where('email', $email)
                ->whereNull('username')
                ->first();
        }

        if ($user === null) {
            return User::query()->create([
                'name' => $name !== '' ? $name : $username,
                'username' => $username,
                'email' => $email !== ''
                    ? $email
                    : $username.'@localhost.invalid',
                'password' => $password,
                'is_active' => true,
            ]);
        }

        $user->forceFill([
            'username' => $username,
            'is_active' => true,
        ]);

        if (trim((string) $user->name) === '') {
            $user->name = $name !== '' ? $name : $username;
        }

        $user->save();

        return $user;
    }

    private function backfillAdministratorAttribution(User $admin): void
    {
        $identifiers = array_values(array_unique(array_filter([
            trim((string) $admin->username),
            trim((string) $admin->name),
        ])));

        if ($identifiers === []) {
            return;
        }

        if (Schema::hasColumn('leads', 'assigned_user_id')) {
            DB::table('leads')
                ->whereNull('assigned_user_id')
                ->whereIn('assigned_employee', $identifiers)
                ->update(['assigned_user_id' => $admin->id]);

            DB::table('leads')
                ->whereNull('created_by_user_id')
                ->whereIn('created_by', $identifiers)
                ->update(['created_by_user_id' => $admin->id]);
        }

        if (Schema::hasColumn('lead_followups', 'user_id')) {
            DB::table('lead_followups')
                ->whereNull('user_id')
                ->whereIn('employee_name', $identifiers)
                ->update(['user_id' => $admin->id]);
        }

        if (Schema::hasColumn('lead_status_histories', 'changed_by_user_id')) {
            DB::table('lead_status_histories')
                ->whereNull('changed_by_user_id')
                ->whereIn('changed_by', $identifiers)
                ->update(['changed_by_user_id' => $admin->id]);
        }
    }
}

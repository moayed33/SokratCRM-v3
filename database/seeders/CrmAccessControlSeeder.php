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
                'name' => 'مدير النظام',
                'description' => 'وصول كامل ومحمي إلى جميع وظائف النظام.',
                'is_system' => true,
                'permissions' => CrmPermission::values(),
            ],
            'sales-manager' => [
                'name' => 'مدير المبيعات',
                'description' => 'إدارة المبيعات والعملاء والمتابعات والتقارير.',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.scope.all',
                    'leads.assign',
                    'leads.create',
                    'leads.update',
                    'leads.delete',
                    'leads.import',
                    'leads.export',
                    'leads.followups.view',
                    'leads.followups.create',
                    'tasks.view',
                    'quotations.view',
                    'quotations.create',
                    'reports.view',
                ],
            ],
            'sales-agent' => [
                'name' => 'موظف المبيعات',
                'description' => 'التعامل اليومي مع العملاء والمتابعات وعروض الأسعار.',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'leads.view',
                    'leads.create',
                    'leads.update',
                    'leads.followups.view',
                    'leads.followups.create',
                    'tasks.view',
                    'quotations.view',
                    'quotations.create',
                ],
            ],
            'read-only' => [
                'name' => 'مشاهدة فقط',
                'description' => 'عرض بيانات CRM دون تعديلها.',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'leads.scope.all',
                    'leads.view',
                    'leads.followups.view',
                    'tasks.view',
                    'quotations.view',
                    'campaigns.view',
                    'campaigns.reports',
                    'reports.view',
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

            if ($group->wasRecentlyCreated || $group->isSuperAdmin()) {
                $group->permissions()->sync(
                    collect($definition['permissions'])
                        ->map(
                            static fn (string $permissionCode): int => (int) $permissionIds->get($permissionCode),
                        )
                        ->filter()
                        ->values()
                        ->all(),
                );
            }

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

        $salesManager = $groups['sales-manager'];

        if (! $salesManager->wasRecentlyCreated) {
            return;
        }

        $targetCodes = [
            LeadAssignment::groupPermissionCode($groups['sales-manager']),
            LeadAssignment::groupPermissionCode($groups['sales-agent']),
        ];

        $salesManager->permissions()->syncWithoutDetaching(
            Permission::query()
                ->whereIn('code', $targetCodes)
                ->pluck('id')
                ->all(),
        );
    }

    private function seedCurrentAdministrator(): User
    {
        $name = trim((string) config('crm.bootstrap_admin.name'));
        $username = trim((string) config('crm.bootstrap_admin.username'));
        $email = trim((string) config('crm.bootstrap_admin.email'));
        $password = (string) config('crm.bootstrap_admin.password');

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

        if (Schema::hasColumn('quotations', 'created_by_user_id')) {
            DB::table('quotations')
                ->whereNull('created_by_user_id')
                ->whereIn('created_by', $identifiers)
                ->update(['created_by_user_id' => $admin->id]);
        }
    }
}

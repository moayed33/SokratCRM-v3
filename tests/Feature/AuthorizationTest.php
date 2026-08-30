<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_inherits_the_union_of_all_group_permissions(): void
    {
        $settingsGroup = $this->groupWithPermissions(
            'settings-team',
            [CrmPermission::SETTINGS_ACCESS],
        );
        $usersGroup = $this->groupWithPermissions(
            'user-readers',
            [CrmPermission::USERS_VIEW],
        );
        $user = User::factory()->create();
        $user->groups()->attach([$settingsGroup->id, $usersGroup->id]);

        $this->actingAs($user)
            ->get(route('v2.settings.users.index'))
            ->assertOk()
            ->assertSee('المستخدمون');
    }

    public function test_missing_permission_is_rejected_server_side(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->post(route('v2.leads.store'))
            ->assertForbidden();
    }

    public function test_super_admin_group_grants_every_defined_ability(): void
    {
        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $user = User::factory()->create();
        $user->groups()->attach($group);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                CrmPermission::GROUPS_ASSIGN_PERMISSIONS->value,
            ),
        );
        $this->assertTrue(
            Gate::forUser($user)->allows(
                CrmPermission::LEADS_DELETE->value,
            ),
        );
    }

    public function test_non_super_admin_cannot_assign_the_super_admin_group(): void
    {
        $actorGroup = $this->groupWithPermissions(
            'user-creators',
            [
                CrmPermission::SETTINGS_ACCESS,
                CrmPermission::USERS_CREATE,
                CrmPermission::USERS_UPDATE,
            ],
        );
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $actor = User::factory()->create();
        $protectedAdmin = User::factory()->create([
            'username' => 'protected-admin',
        ]);
        $protectedAdmin->groups()->attach($superAdminGroup);
        $actor->groups()->attach($actorGroup);

        $this->actingAs($actor)
            ->post(route('v2.settings.users.store'), [
                'name' => 'Escalated User',
                'username' => 'escalated-user',
                'email' => 'escalated@example.test',
                'password' => 'SecretPass123',
                'password_confirmation' => 'SecretPass123',
                'group_ids' => [$superAdminGroup->id],
            ])
            ->assertSessionHasErrors('group_ids');

        $this->assertDatabaseMissing('users', [
            'username' => 'escalated-user',
        ]);

        $this->get(
            route('v2.settings.users.edit', $protectedAdmin),
        )->assertForbidden();
    }

    public function test_last_active_super_admin_cannot_remove_own_super_group(): void
    {
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $regularGroup = Group::query()->create([
            'name' => 'المبيعات',
            'code' => 'sales',
        ]);
        $admin = User::factory()->create(['username' => 'admin']);
        $admin->groups()->attach($superAdminGroup);

        $this->actingAs($admin)
            ->patch(route('v2.settings.users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'group_ids' => [$regularGroup->id],
            ])
            ->assertSessionHasErrors('group_ids');

        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_authorized_user_can_create_and_update_accounts_without_email(): void
    {
        $actorGroup = $this->groupWithPermissions(
            'optional-email-creators',
            [
                CrmPermission::SETTINGS_ACCESS,
                CrmPermission::USERS_CREATE,
                CrmPermission::USERS_UPDATE,
            ],
        );
        $targetGroup = Group::query()->create([
            'name' => 'Optional Email Users',
            'code' => 'optional-email-users',
        ]);
        $actor = User::factory()->create();
        $actor->groups()->attach($actorGroup);
        $this->actingAs($actor);

        foreach (['one', 'two'] as $suffix) {
            $this->post(route('v2.settings.users.store'), [
                'name' => 'Optional Email '.$suffix,
                'username' => 'optional-email-'.$suffix,
                'password' => 'SecretPass123',
                'password_confirmation' => 'SecretPass123',
                'group_ids' => [$targetGroup->id],
            ])
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $firstUser = User::query()
            ->where('username', 'optional-email-one')
            ->firstOrFail();

        $this->assertNull($firstUser->email);
        $this->assertSame(
            2,
            User::query()
                ->whereIn('username', [
                    'optional-email-one',
                    'optional-email-two',
                ])
                ->whereNull('email')
                ->count(),
        );

        $this->from(route('v2.settings.users.edit', $firstUser))
            ->patch(route('v2.settings.users.update', $firstUser), [
                'name' => 'Optional Email Updated',
                'username' => $firstUser->username,
                'group_ids' => [$targetGroup->id],
            ])
            ->assertRedirect(route('v2.settings.users.edit', $firstUser))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $firstUser->id,
            'name' => 'Optional Email Updated',
            'email' => null,
        ]);
    }

    public function test_permission_matrix_updates_regular_groups_only(): void
    {
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $regularGroup = Group::query()->create([
            'name' => 'المبيعات',
            'code' => 'sales',
        ]);
        $admin = User::factory()->create();
        $admin->groups()->attach($superAdminGroup);

        $leadView = $this->permission(CrmPermission::LEADS_VIEW);
        $settingsAccess = $this->permission(
            CrmPermission::SETTINGS_ACCESS,
        );
        $superAdminGroup->permissions()->sync([$settingsAccess->id], false);

        $this->actingAs($admin)
            ->put(route('v2.settings.permissions.update'), [
                'permissions' => [
                    $regularGroup->id => [$leadView->code],
                    $superAdminGroup->id => [],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            $regularGroup->fresh()->permissions
                ->contains('code', CrmPermission::LEADS_VIEW->value),
        );
        $this->assertTrue(
            $superAdminGroup->fresh()->permissions
                ->contains('code', CrmPermission::SETTINGS_ACCESS->value),
        );
    }

    /**
     * @param  list<CrmPermission>  $permissions
     */
    private function groupWithPermissions(
        string $code,
        array $permissions,
    ): Group {
        $group = Group::query()->create([
            'name' => $code,
            'code' => $code,
        ]);

        $group->permissions()->sync(
            array_map(
                fn (CrmPermission $permission): int => $this->permission($permission)->id,
                $permissions,
            )
        );

        return $group;
    }

    private function permission(CrmPermission $permission): Permission
    {
        return Permission::query()->firstOrCreate(
            ['code' => $permission->value],
            [
                'module' => $permission->module(),
                'name_ar' => $permission->label(),
            ],
        );
    }
}

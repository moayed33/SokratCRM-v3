<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesManager;

    private User $salesAgent;

    private User $readOnlyUser;

    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ],
            );
        }

        $superAdminGroup = Group::query()->create([
            'name' => 'مدير النظام',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $salesManagerGroup = Group::query()->create([
            'name' => 'مدير المبيعات',
            'code' => 'sales-manager',
            'is_system' => false,
        ]);

        $salesAgentGroup = Group::query()->create([
            'name' => 'موظف المبيعات',
            'code' => 'sales-agent',
            'is_system' => false,
        ]);

        $readOnlyGroup = Group::query()->create([
            'name' => 'مشاهدة فقط',
            'code' => 'read-only',
            'is_system' => false,
        ]);

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        $this->salesManager = User::factory()->create(['is_active' => true]);
        $this->salesManager->groups()->attach($salesManagerGroup);

        $this->salesAgent = User::factory()->create(['is_active' => true]);
        $this->salesAgent->groups()->attach($salesAgentGroup);

        $this->readOnlyUser = User::factory()->create(['is_active' => true]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);

        $this->targetUser = User::factory()->create(['is_active' => true]);
    }

    public function test_non_super_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->salesManager)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->actingAs($this->salesAgent)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.settings'))
            ->assertForbidden();
    }

    public function test_non_super_admin_cannot_access_user_management(): void
    {
        $this->actingAs($this->salesManager)
            ->get(route('v2.settings.users.index'))
            ->assertForbidden();

        $this->actingAs($this->salesAgent)
            ->get(route('v2.settings.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_user_management(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.users.index'))
            ->assertOk();
    }
}

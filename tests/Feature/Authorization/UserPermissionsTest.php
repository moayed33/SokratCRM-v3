<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use DatabaseTransactions;

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

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
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

    public function test_creating_team_leader_assigns_subordinate_sales_employees(): void
    {
        $salesGroup = Group::query()->where('code', 'sales-agent')->firstOrFail();
        $leaderGroup = Group::query()->where('code', 'sales-manager')->firstOrFail();

        $agent1 = User::factory()->create(['is_active' => true]);
        $agent1->groups()->attach($salesGroup);
        $agent2 = User::factory()->create(['is_active' => true]);
        $agent2->groups()->attach($salesGroup);

        $this->actingAs($this->superAdmin)
            ->post(route('v2.settings.users.store'), [
                'name' => 'قائد فريق المبيعات',
                'username' => 'new_team_leader',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
                'group_ids' => [$leaderGroup->id],
                'subordinate_ids' => [$agent1->id, $agent2->id],
                'subordinates_section_rendered' => 1,
            ])
            ->assertRedirect();

        $newLeader = User::query()->where('username', 'new_team_leader')->firstOrFail();
        $this->assertSame($newLeader->id, $agent1->fresh()->manager_id);
        $this->assertSame($newLeader->id, $agent2->fresh()->manager_id);
        $this->assertCount(2, $newLeader->subordinates);
    }

    public function test_sales_employee_can_only_have_one_team_leader(): void
    {
        $salesGroup = Group::query()->where('code', 'sales-agent')->firstOrFail();
        $leaderGroup = Group::query()->where('code', 'sales-manager')->firstOrFail();

        $agent = User::factory()->create(['is_active' => true]);
        $agent->groups()->attach($salesGroup);

        $leaderA = User::factory()->create(['is_active' => true]);
        $leaderA->groups()->attach($leaderGroup);
        $agent->update(['manager_id' => $leaderA->id]);
        $this->assertSame($leaderA->id, $agent->fresh()->manager_id);

        $leaderB = User::factory()->create(['is_active' => true]);
        $leaderB->groups()->attach($leaderGroup);

        // Leader B claims agent
        $this->actingAs($this->superAdmin)
            ->patch(route('v2.settings.users.update', $leaderB), [
                'name' => $leaderB->name,
                'username' => $leaderB->username,
                'group_ids' => [$leaderGroup->id],
                'subordinate_ids' => [$agent->id],
                'subordinates_section_rendered' => 1,
            ])
            ->assertRedirect();

        // Agent is now only under Leader B, no longer under Leader A
        $this->assertSame($leaderB->id, $agent->fresh()->manager_id);
        $this->assertCount(0, $leaderA->fresh()->subordinates);
        $this->assertCount(1, $leaderB->fresh()->subordinates);
    }

    public function test_updating_team_leader_detaches_unselected_subordinates(): void
    {
        $salesGroup = Group::query()->where('code', 'sales-agent')->firstOrFail();
        $leaderGroup = Group::query()->where('code', 'sales-manager')->firstOrFail();

        $leader = User::factory()->create(['is_active' => true]);
        $leader->groups()->attach($leaderGroup);

        $agent1 = User::factory()->create(['is_active' => true, 'manager_id' => $leader->id]);
        $agent1->groups()->attach($salesGroup);
        $agent2 = User::factory()->create(['is_active' => true, 'manager_id' => $leader->id]);
        $agent2->groups()->attach($salesGroup);

        $this->assertCount(2, $leader->fresh()->subordinates);

        // Uncheck agent2, keep agent1
        $this->actingAs($this->superAdmin)
            ->patch(route('v2.settings.users.update', $leader), [
                'name' => $leader->name,
                'username' => $leader->username,
                'group_ids' => [$leaderGroup->id],
                'subordinate_ids' => [$agent1->id],
                'subordinates_section_rendered' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($leader->id, $agent1->fresh()->manager_id);
        $this->assertNull($agent2->fresh()->manager_id);
        $this->assertCount(1, $leader->fresh()->subordinates);
    }

    public function test_collection_manager_and_collector_subordinate_management(): void
    {
        $colMgrGroup = Group::query()->firstOrCreate(
            ['code' => 'collection-manager'],
            ['name' => 'مسؤولو التحصيل', 'is_system' => false],
        );
        $collectorGroup = Group::query()->firstOrCreate(
            ['code' => 'collector'],
            ['name' => 'المحصلون', 'is_system' => false],
        );

        $collector1 = User::factory()->create(['is_active' => true]);
        $collector1->groups()->attach($collectorGroup);
        $collector2 = User::factory()->create(['is_active' => true]);
        $collector2->groups()->attach($collectorGroup);

        // Create collection manager with collector1
        $this->actingAs($this->superAdmin)
            ->post(route('v2.settings.users.store'), [
                'name' => 'مسؤول التحصيل',
                'username' => 'new_col_manager',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
                'group_ids' => [$colMgrGroup->id],
                'subordinate_ids' => [$collector1->id],
                'subordinates_section_rendered' => 1,
            ])
            ->assertRedirect();

        $manager = User::query()->where('username', 'new_col_manager')->firstOrFail();

        // Edit collection manager to add collector2
        $this->actingAs($this->superAdmin)
            ->patch(route('v2.settings.users.update', $manager), [
                'name' => $manager->name,
                'username' => $manager->username,
                'group_ids' => [$colMgrGroup->id],
                'subordinate_ids' => [$collector1->id, $collector2->id],
                'subordinates_section_rendered' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($manager->id, $collector1->fresh()->manager_id);
        $this->assertSame($manager->id, $collector2->fresh()->manager_id);
        $this->assertCount(2, $manager->fresh()->subordinates);
    }
}

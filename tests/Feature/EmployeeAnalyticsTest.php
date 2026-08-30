<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Database\Seeders\CrmV2PipelineSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmployeeAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    private User $teamLeader;

    private User $salesAgent;

    private User $collectionManager;

    private User $collector;

    private User $unauthorizedUser;

    private Group $superAdminGroup;

    private Group $salesTeamGroup;

    private Group $collectionGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmV2PipelineSeeder::class);

        foreach (CrmPermission::values() as $code) {
            Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0] ?? 'crm', 'name_ar' => $code, 'description' => $code]
            );
        }

        $this->superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Administrator', 'is_system' => true]
        );
        $this->superAdminGroup->permissions()->sync(Permission::pluck('id')->unique()->all());

        $this->salesTeamGroup = Group::query()->create([
            'code' => 'sales-team-alpha',
            'name' => 'Sales Team Alpha',
        ]);
        $teamLeaderPerms = Permission::query()
            ->whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_GROUP->value,
                CrmPermission::LEADS_ASSIGN->value,
                CrmPermission::CAMPAIGNS_REPORTS->value,
                CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
            ])
            ->pluck('id');
        $this->salesTeamGroup->permissions()->sync($teamLeaderPerms);

        $this->collectionGroup = Group::query()->create([
            'code' => 'collection-team-beta',
            'name' => 'Collection Team Beta',
        ]);
        $collectionPerms = Permission::query()
            ->whereIn('code', [
                CrmPermission::COLLECTIONS_VIEW->value,
                CrmPermission::COLLECTIONS_MANAGE->value,
                CrmPermission::COLLECTIONS_REPORTS->value,
                CrmPermission::COLLECTIONS_COLLECT->value,
                CrmPermission::REPORTS_EMPLOYEES_VIEW->value,
            ])
            ->pluck('id');
        $this->collectionGroup->permissions()->sync($collectionPerms);

        // 1. Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Boss',
            'username' => 'super_boss',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->sync([$this->superAdminGroup->id]);

        // 2. Team Leader
        $this->teamLeader = User::factory()->create([
            'name' => 'Leader Tarek',
            'username' => 'leader_tarek',
            'is_active' => true,
        ]);
        $this->teamLeader->groups()->sync([$this->salesTeamGroup->id]);

        // 3. Sales Agent (assigned to Leader Tarek)
        $this->salesAgent = User::factory()->create([
            'name' => 'Agent Hossam',
            'username' => 'agent_hossam',
            'manager_id' => $this->teamLeader->id,
            'is_active' => true,
        ]);
        $this->salesAgent->groups()->sync([$this->salesTeamGroup->id]);

        // 4. Collection Manager
        $this->collectionManager = User::factory()->create([
            'name' => 'Manager Nader',
            'username' => 'mgr_nader',
            'is_active' => true,
        ]);
        $this->collectionManager->groups()->sync([$this->collectionGroup->id]);

        // 5. Collector (assigned to Manager Nader)
        $this->collector = User::factory()->create([
            'name' => 'Collector Mostafa',
            'username' => 'collector_mostafa',
            'manager_id' => $this->collectionManager->id,
            'is_active' => true,
        ]);
        $this->collector->groups()->sync([$this->collectionGroup->id]);

        // 6. Regular Unauthorized User
        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Plain User',
            'username' => 'plain_user',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_view_all_staff_analytics(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('v2.reports.employees'));

        $response->assertOk()
            ->assertSee('Leader Tarek')
            ->assertSee('Agent Hossam')
            ->assertSee('Manager Nader')
            ->assertSee('Collector Mostafa');
    }

    public function test_team_leader_only_sees_their_team_members(): void
    {
        $response = $this->actingAs($this->teamLeader)->get(route('v2.reports.employees'));

        $response->assertOk()
            ->assertSee('Leader Tarek')
            ->assertSee('Agent Hossam')
            ->assertDontSee('Collector Mostafa');
    }

    public function test_collection_manager_only_sees_collectors_they_manage(): void
    {
        $response = $this->actingAs($this->collectionManager)->get(route('v2.reports.employees'));

        $response->assertOk()
            ->assertSee('Manager Nader')
            ->assertSee('Collector Mostafa')
            ->assertDontSee('Agent Hossam');
    }

    public function test_unauthorized_user_is_forbidden_from_employee_analytics(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('v2.reports.employees'))
            ->assertForbidden();
    }

    public function test_user_can_be_created_and_updated_with_direct_manager_id(): void
    {
        // 1. Create user with manager_id
        $storeResponse = $this->actingAs($this->superAdmin)->post(route('v2.settings.users.store'), [
            'name' => 'New Junior Agent',
            'username' => 'junior_agent',
            'email' => 'junior@example.test',
            'manager_id' => $this->teamLeader->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'group_ids' => [$this->salesTeamGroup->id],
        ]);

        $storeResponse->assertRedirect();
        $newUser = User::query()->where('username', 'junior_agent')->firstOrFail();
        $this->assertSame($this->teamLeader->id, $newUser->manager_id);
        $this->assertSame('Leader Tarek', $newUser->manager?->name);

        // 2. Update user manager_id to Collection Manager
        $updateResponse = $this->actingAs($this->superAdmin)->patch(route('v2.settings.users.update', $newUser), [
            'name' => 'Promoted Junior Agent',
            'username' => 'junior_agent',
            'email' => 'junior@example.test',
            'manager_id' => $this->collectionManager->id,
            'group_ids' => [$this->collectionGroup->id],
        ]);

        $updateResponse->assertRedirect();
        $newUser->refresh();
        $this->assertSame($this->collectionManager->id, $newUser->manager_id);
        $this->assertSame('Manager Nader', $newUser->manager?->name);
    }

    public function test_employee_profile_can_be_viewed_by_authorized_leaders(): void
    {
        $response = $this->actingAs($this->teamLeader)
            ->get(route('v2.reports.employees.show', $this->salesAgent));

        $response->assertOk()
            ->assertSee('Agent Hossam')
            ->assertSee('agent_hossam')
            ->assertSee('Sales Team Alpha');
    }

    public function test_team_leader_cannot_view_unmanaged_employee_profile(): void
    {
        $this->actingAs($this->teamLeader)
            ->get(route('v2.reports.employees.show', $this->collector))
            ->assertForbidden();
    }

    public function test_employee_analytics_filters_by_group(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('v2.reports.employees', ['group_id' => $this->salesTeamGroup->id]));

        $response->assertOk()
            ->assertSee('Agent Hossam')
            ->assertDontSee('Collector Mostafa');
    }

    public function test_user_without_reports_employees_view_permission_is_denied_access(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('v2.reports.employees'));
        $response->assertForbidden();

        $responseShow = $this->actingAs($this->unauthorizedUser)->get(route('v2.reports.employees.show', $this->salesAgent));
        $responseShow->assertForbidden();
    }

    public function test_user_with_reports_employees_view_permission_can_access_index(): void
    {
        $managerUser = User::factory()->create([
            'name' => 'Custom Manager',
            'username' => 'custom_manager',
            'is_active' => true,
        ]);
        $group = Group::query()->create(['code' => 'custom-manager-grp', 'name' => 'Custom Manager Group']);
        $perm = Permission::query()->where('code', CrmPermission::REPORTS_EMPLOYEES_VIEW->value)->firstOrFail();
        $group->permissions()->sync([$perm->id]);
        $managerUser->groups()->sync([$group->id]);

        $response = $this->actingAs($managerUser)->get(route('v2.reports.employees'));
        $response->assertOk();
    }

    public function test_sidebar_displays_employee_analytics_link_only_when_user_has_permission(): void
    {
        $dashboardPerm = Permission::query()->where('code', CrmPermission::DASHBOARD_VIEW->value)->firstOrFail();
        $unauthGroup = Group::query()->create(['code' => 'unauth-sidebar-grp', 'name' => 'Unauth Sidebar Group']);
        $unauthGroup->permissions()->sync([$dashboardPerm->id]);
        $this->unauthorizedUser->groups()->sync([$unauthGroup->id]);

        // 1. User without reports.employees.view -> sidebar does not show link
        $responseUnauth = $this->actingAs($this->unauthorizedUser)->get(route('dashboard'));
        $responseUnauth->assertOk();
        $responseUnauth->assertDontSee(route('v2.reports.employees'));

        // 2. Team Leader with reports.employees.view -> sidebar shows link
        $this->salesTeamGroup->permissions()->attach($dashboardPerm->id);
        $responseAuth = $this->actingAs($this->teamLeader)->get(route('dashboard'));
        $responseAuth->assertOk();
        $responseAuth->assertSee(route('v2.reports.employees'));
    }

    public function test_crm_permission_enum_contains_reports_employees_view(): void
    {
        $this->assertSame('reports.employees.view', CrmPermission::REPORTS_EMPLOYEES_VIEW->value);
        $this->assertSame('reports', CrmPermission::REPORTS_EMPLOYEES_VIEW->module());
        $this->assertSame('عرض إحصائيات الموظفين', CrmPermission::REPORTS_EMPLOYEES_VIEW->label());
        $this->assertContains('reports.employees.view', CrmPermission::values());
    }
}

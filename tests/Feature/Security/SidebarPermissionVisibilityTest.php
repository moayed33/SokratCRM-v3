<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SidebarPermissionVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::query()->firstOrCreate(
            ['code' => 'main_test_branch'],
            ['name_ar' => 'فرع الاختبار الرئيسي', 'name_en' => 'Main Test Branch', 'is_active' => true],
        );

        $superGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Admin Group', 'is_system' => true],
        );

        $this->superAdmin = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);
        $this->superAdmin->groups()->attach($superGroup);
    }

    public function test_1_super_admin_sees_all_permitted_sidebar_modules(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('crmLeadsMenu');
        $response->assertSee('crmTasksMenu');
        $response->assertSee('crmCampaignsMenu');
        $response->assertSee('href="' . route('v2.leads') . '"', false);
        $response->assertSee('href="' . route('v2.leads.create') . '"', false);
        $response->assertSee('href="' . route('v2.leads.import') . '"', false);
        $response->assertSee('href="' . route('v2.leads.export') . '"', false);
        $response->assertSee('href="' . route('v2.leads.kanban') . '"', false);
        $response->assertSee('href="' . route('v2.calendar.index') . '"', false);
        $response->assertSee('href="' . route('v2.collections.index') . '"', false);
        $response->assertSee('href="' . route('v2.settings') . '"', false);
    }

    public function test_2_user_without_settings_access_does_not_see_settings_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('href="' . route('v2.settings') . '"', false);
    }

    public function test_3_direct_settings_route_is_forbidden_for_user_without_settings_access(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $this->actingAs($user)->get(route('v2.settings'))->assertForbidden();
        $this->actingAs($user)->get(route('v2.settings.branches.index'))->assertForbidden();
        $this->actingAs($user)->get(route('v2.settings.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('v2.settings.groups.index'))->assertForbidden();
    }

    public function test_4_leads_view_user_sees_leads_parent_and_kanban(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('crmLeadsMenu');
        $response->assertSee('href="' . route('v2.leads') . '"', false);
        $response->assertSee('href="' . route('v2.leads.kanban') . '"', false);
    }

    public function test_5_leads_view_only_user_does_not_see_add_import_export_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('href="' . route('v2.leads') . '"', false);
        $response->assertDontSee('href="' . route('v2.leads.create') . '"', false);
        $response->assertDontSee('href="' . route('v2.leads.import') . '"', false);
        $response->assertDontSee('href="' . route('v2.leads.export') . '"', false);
    }

    public function test_6_user_with_no_leads_permissions_does_not_see_leads_parent_or_kanban(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'calendar.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('crmLeadsMenu');
        $response->assertDontSee('href="' . route('v2.leads') . '"', false);
        $response->assertDontSee('href="' . route('v2.leads.kanban') . '"', false);
    }

    public function test_7_campaigns_reports_only_user_sees_parent_and_reports_link_only(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'campaigns.reports']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('crmCampaignsMenu');
        $response->assertSee('href="' . route('v2.campaigns.reports') . '"', false);
        $response->assertDontSee('href="' . route('v2.campaigns.index') . '"', false);
        $response->assertDontSee('href="' . route('v2.campaigns.create') . '"', false);
    }

    public function test_8_campaigns_view_absent_hides_campaigns_view_link(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'campaigns.create']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('crmCampaignsMenu');
        $response->assertSee('href="' . route('v2.campaigns.create') . '"', false);
        $response->assertDontSee('href="' . route('v2.campaigns.index') . '"', false);
    }

    public function test_9_tasks_view_absent_hides_tasks_menu_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('crmTasksMenu');
        $response->assertDontSee('href="' . route('v2.tasks.daily') . '"', false);
    }

    public function test_10_collections_view_absent_hides_collections_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('href="' . route('v2.collections.index') . '"', false);
    }

    public function test_11_calendar_view_absent_hides_calendar_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('href="' . route('v2.calendar.index') . '"', false);
    }

    public function test_12_voip_live_panel_absent_hides_voip_monitoring_in_sidebar(): void
    {
        $user = $this->createActorWithPermissions(['dashboard.view', 'leads.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('href="' . route('v2.voip.live') . '"', false);
    }

    public function test_13_empty_parent_menu_is_not_rendered_when_all_children_are_forbidden(): void
    {
        // User with no lead and no campaign permissions
        $user = $this->createActorWithPermissions(['dashboard.view', 'calendar.view']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('crmLeadsMenu');
        $response->assertDontSee('crmCampaignsMenu');
        $response->assertDontSee('crmTasksMenu');
    }

    /** @param list<string> $permissionCodes */
    private function createActorWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Test Actor Group '.fake()->unique()->word(),
            'code' => 'actor-'.fake()->unique()->slug(),
            'is_system' => false,
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->where('code', $code)->first()
                ?? Permission::query()->create([
                    'code' => $code,
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ]);
            $group->permissions()->syncWithoutDetaching($permission);
        }

        $user = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branch->id,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
        $user->groups()->attach($group);

        return $user;
    }
}

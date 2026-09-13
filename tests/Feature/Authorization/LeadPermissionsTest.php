<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeadPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    private User $salesManager;

    private User $salesAgent;

    private User $readOnlyUser;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Seed permissions
        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ],
            );
        }

        // Setup groups
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $salesManagerGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-manager'],
            ['name' => 'مدير المبيعات', 'is_system' => false]
        );
        $managerPermCodes = [
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.scope.all', 'leads.assign',
            'leads.import', 'leads.export', 'leads.followups.view', 'leads.followups.create',
            'tasks.view', 'reports.view',
        ];
        $salesManagerGroup->permissions()->sync(Permission::whereIn('code', $managerPermCodes)->pluck('id'));

        $salesAgentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            ['name' => 'موظف المبيعات', 'is_system' => false]
        );
        $agentPermCodes = [
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update',
            'leads.followups.view', 'leads.followups.create', 'tasks.view',
        ];
        $salesAgentGroup->permissions()->sync(Permission::whereIn('code', $agentPermCodes)->pluck('id'));

        $readOnlyGroup = Group::query()->firstOrCreate(
            ['code' => 'read-only'],
            ['name' => 'مشاهدة فقط', 'is_system' => false]
        );
        $readOnlyPermCodes = [
            'leads.scope.all',
            'dashboard.view', 'leads.view', 'leads.followups.view', 'tasks.view',
            'campaigns.view', 'campaigns.reports', 'reports.view',
        ];
        $readOnlyGroup->permissions()->sync(Permission::whereIn('code', $readOnlyPermCodes)->pluck('id'));

        // Create Users
        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        $this->salesManager = User::factory()->create(['is_active' => true]);
        $this->salesManager->groups()->attach($salesManagerGroup);

        $this->salesAgent = User::factory()->create(['is_active' => true]);
        $this->salesAgent->groups()->attach($salesAgentGroup);

        $this->readOnlyUser = User::factory()->create(['is_active' => true]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);

        // Ensure Pipeline Stage and Lead Status exist
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
                'is_active' => true,
            ]
        );

        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
                'is_terminal' => false,
            ]
        );
        $this->lead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'عميل تجريبي',
            'email' => 'testcustomer@example.test',
            'phone' => '01012345678',
            'assigned_employee' => $this->salesAgent->name,
            'assigned_user_id' => $this->salesAgent->id,
        ]);
    }

    public function test_read_only_user_cannot_create_lead(): void
    {
        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.leads.create'))
            ->assertForbidden();

        $this->actingAs($this->readOnlyUser)
            ->post(route('v2.leads.store'), [
                'name' => 'Attempt Lead',
                'phone' => '01123456789',
            ])
            ->assertForbidden();
    }

    public function test_read_only_user_cannot_edit_or_update_lead(): void
    {
        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.leads.edit', $this->lead))
            ->assertForbidden();

        $this->actingAs($this->readOnlyUser)
            ->patch(route('v2.leads.update', $this->lead), [
                'name' => 'Updated Lead Name',
            ])
            ->assertForbidden();
    }

    public function test_sales_agent_cannot_delete_lead(): void
    {
        $this->actingAs($this->salesAgent)
            ->delete(route('v2.leads.destroy', $this->lead))
            ->assertForbidden();
    }

    public function test_sales_manager_and_super_admin_can_delete_lead(): void
    {
        $this->actingAs($this->salesManager)
            ->delete(route('v2.leads.destroy', $this->lead))
            ->assertRedirect(route('v2.leads'));

        $this->assertDatabaseMissing('leads', ['id' => $this->lead->id]);

        $lead2 = Lead::query()->create([
            'lead_status_id' => $this->lead->lead_status_id,
            'name' => 'عميل سوبر أدمن',
            'email' => 'admin_lead@example.test',
            'phone' => '01099998888',
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('v2.leads.destroy', $lead2))
            ->assertRedirect(route('v2.leads'));

        $this->assertDatabaseMissing('leads', ['id' => $lead2->id]);
    }

    public function test_sales_agent_cannot_import_or_export_leads(): void
    {
        $this->actingAs($this->salesAgent)
            ->get(route('v2.leads.import'))
            ->assertForbidden();

        $this->actingAs($this->salesAgent)
            ->get(route('v2.leads.export'))
            ->assertForbidden();
    }
}

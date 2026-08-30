<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\BranchContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiBranchArchitectureTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'name_en' => 'New', 'color' => '#3b82f6', 'position' => 1, 'is_active' => true],
        );

        $this->leadStatus = LeadStatus::query()->firstOrCreate(
            ['pipeline_stage_id' => $stage->id, 'name_ar' => 'جديد'],
            ['code' => 'new_status', 'color' => '#3b82f6', 'position' => 1, 'is_active' => true],
        );

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true],
        );

        $this->mainBranch = Branch::query()->firstOrCreate(
            ['code' => 'main'],
            ['name_ar' => 'الفرع الرئيسي', 'is_active' => true],
        );

        $this->cairoBranch = Branch::query()->firstOrCreate(
            ['code' => 'cairo'],
            ['name_ar' => 'فرع القاهرة', 'is_active' => true],
        );

        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_test',
            'is_active' => true,
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->superAdmin->groups()->sync([$superAdminGroup->id]);
    }

    public function test_admin_can_view_branches_list(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.branches.index'));

        $response->assertOk();
        $response->assertSee('الفرع الرئيسي');
        $response->assertSee('فرع القاهرة');
    }

    public function test_admin_can_create_new_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('v2.settings.branches.store'), [
                'name_ar' => 'فرع الإسكندرية',
                'name_en' => 'Alexandria Branch',
                'code' => 'alex',
                'phone' => '031234567',
                'address' => 'سموحة، الإسكندرية',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('v2.settings.branches.index'));
        $this->assertDatabaseHas('branches', [
            'code' => 'alex',
            'name_ar' => 'فرع الإسكندرية',
            'name_en' => 'Alexandria Branch',
        ]);
    }

    public function test_branch_code_must_be_unique(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('v2.settings.branches.store'), [
                'name_ar' => 'فرع جديد',
                'code' => 'main', // already exists
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_branch(): void
    {
        $branch = Branch::query()->create([
            'name_ar' => 'فرع طنطا',
            'code' => 'tanta',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->patch(route('v2.settings.branches.update', $branch), [
                'name_ar' => 'فرع طنطا الجديد',
                'code' => 'tanta-new',
                'phone' => '040123456',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('v2.settings.branches.index'));
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name_ar' => 'فرع طنطا الجديد',
            'code' => 'tanta-new',
        ]);
    }

    public function test_cannot_delete_branch_with_associated_leads(): void
    {
        $branch = Branch::query()->create([
            'name_ar' => 'فرع المنصورة',
            'code' => 'mansoura',
            'is_active' => true,
        ]);

        Lead::query()->create([
            'branch_id' => $branch->id,
            'lead_status_id' => $this->leadStatus->id,
            'name' => 'عميل تجريبي',
            'phone' => '01099998888',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('v2.settings.branches.destroy', $branch));

        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_can_delete_empty_branch(): void
    {
        $branch = Branch::query()->create([
            'name_ar' => 'فرع أسوان',
            'code' => 'aswan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('v2.settings.branches.destroy', $branch));

        $response->assertRedirect(route('v2.settings.branches.index'));
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_regular_agent_is_strictly_scoped_to_assigned_branch(): void
    {
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'agent_group'],
            ['name' => 'موظف مبيعات'],
        );
        $agentGroup->permissions()->sync(
            \App\Models\Permission::whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_ALL->value,
            ])->pluck('id')
        );

        $agentCairo = User::factory()->create([
            'username' => 'agent_cairo',
            'is_active' => true,
            'branch_id' => $this->cairoBranch->id,
        ]);
        $agentCairo->groups()->sync([$agentGroup->id]);

        $leadMain = Lead::query()->create([
            'branch_id' => $this->mainBranch->id,
            'lead_status_id' => $this->leadStatus->id,
            'name' => 'عميل الفرع الرئيسي',
            'phone' => '01111111111',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $leadCairo = Lead::query()->create([
            'branch_id' => $this->cairoBranch->id,
            'lead_status_id' => $this->leadStatus->id,
            'name' => 'عميل فرع القاهرة',
            'phone' => '01222222222',
            'created_by_user_id' => $agentCairo->id,
        ]);

        // Agent in Cairo should only see Cairo lead
        $accessibleLeads = Lead::query()->accessibleTo($agentCairo)->pluck('id');

        $this->assertTrue($accessibleLeads->contains($leadCairo->id));
        $this->assertFalse($accessibleLeads->contains($leadMain->id));
    }

    public function test_super_admin_can_switch_branches_in_session(): void
    {
        $leadMain = Lead::query()->create([
            'branch_id' => $this->mainBranch->id,
            'lead_status_id' => $this->leadStatus->id,
            'name' => 'عميل رئيسي',
            'phone' => '01111111112',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        $leadCairo = Lead::query()->create([
            'branch_id' => $this->cairoBranch->id,
            'lead_status_id' => $this->leadStatus->id,
            'name' => 'عميل قاهرة',
            'phone' => '01222222223',
            'created_by_user_id' => $this->superAdmin->id,
        ]);

        // 1. All branches view
        session([BranchContext::SESSION_KEY => 'all']);
        $allLeads = Lead::query()->accessibleTo($this->superAdmin)->pluck('id');
        $this->assertTrue($allLeads->contains($leadMain->id));
        $this->assertTrue($allLeads->contains($leadCairo->id));

        // 2. Switch to Cairo branch
        $response = $this->actingAs($this->superAdmin)
            ->post(route('v2.branch.switch'), ['branch_id' => $this->cairoBranch->id]);

        $response->assertSessionHas(BranchContext::SESSION_KEY, $this->cairoBranch->id);

        session([BranchContext::SESSION_KEY => $this->cairoBranch->id]);
        $cairoLeads = Lead::query()->accessibleTo($this->superAdmin)->pluck('id');
        $this->assertTrue($cairoLeads->contains($leadCairo->id));
        $this->assertFalse($cairoLeads->contains($leadMain->id));
    }

    public function test_user_creation_with_branch_assignment(): void
    {
        $group = Group::first();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('v2.settings.users.store'), [
                'name' => 'موظف القاهرة الجديد',
                'username' => 'cairo_new_user',
                'email' => 'cairo_user@example.com',
                'branch_id' => $this->cairoBranch->id,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'group_ids' => [$group->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'username' => 'cairo_new_user',
            'branch_id' => $this->cairoBranch->id,
        ]);
    }
}

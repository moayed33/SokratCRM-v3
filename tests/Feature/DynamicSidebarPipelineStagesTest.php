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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicSidebarPipelineStagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $group = Group::query()->create([
            'name' => 'Super Admin Group',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($group);

        $this->seed(\Database\Seeders\CrmV2PipelineSeeder::class);
    }

    public function test_sidebar_displays_active_pipeline_stages_dynamically(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertOk();

        $activeStages = PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
        foreach ($activeStages as $stage) {
            $response->assertSee($stage->name_ar);
            $response->assertSee(route('v2.tasks.daily', ['stage_id' => $stage->id]));
        }
    }

    public function test_case_1_creating_new_stage_updates_sidebar_via_cache_invalidation(): void
    {
        // 1. Initial view has default stages
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertDontSee('مرحلة متابعة مخصصة');

        // 2. Create new stage
        $postResponse = $this->actingAs($this->admin)->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة متابعة مخصصة',
            'color' => '#8b5cf6',
            'icon' => 'bi-star',
            'description_ar' => 'وصف اختباري',
        ]);
        $postResponse->assertRedirect(route('v2.settings.stages.index'));

        // 3. Sidebar now renders the new stage
        $responseAfter = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $responseAfter->assertSee('مرحلة متابعة مخصصة');
    }

    public function test_case_2_renaming_stage_updates_sidebar_immediately(): void
    {
        $stage = PipelineStage::query()->where('code', 'new')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $stage), [
            'name_ar' => 'عملاء جدد محدث',
            'color' => $stage->color,
            'icon' => $stage->icon,
            'position' => $stage->position,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertSee('عملاء جدد محدث');
    }

    public function test_case_3_reordering_stages_updates_sidebar_order(): void
    {
        $stageNew = PipelineStage::query()->where('code', 'new')->firstOrFail();
        $stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();

        // Swap positions safely
        $stageNew->update(['position' => 99]);
        $stageDonor->update(['position' => 1]);
        $stageNew->update(['position' => 4]);
        PipelineStage::clearSidebarCache();

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $this->assertSame($stageDonor->id, $sidebarStages->first()->id);
    }
    public function test_case_4_and_5_disabling_and_reenabling_stage_updates_sidebar_visibility(): void
    {
        // Create an optional stage
        $stage = PipelineStage::query()->create([
            'code' => 'custom_stage',
            'name_ar' => 'مرحلة اختيارية قابلة للتعطيل',
            'position' => 5,
            'is_primary' => false,
            'is_active' => true,
        ]);

        // 1. Visible when active
        $response1 = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response1->assertSee('مرحلة اختيارية قابلة للتعطيل');

        // 2. Disable stage
        $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $stage), [
            'name_ar' => 'مرحلة اختيارية قابلة للتعطيل',
            'position' => 5,
            'is_active' => false,
        ]);

        // 3. Not visible in sidebar
        $response2 = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response2->assertDontSee('مرحلة اختيارية قابلة للتعطيل');

        // 4. Re-enable stage
        $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $stage), [
            'name_ar' => 'مرحلة اختيارية قابلة للتعطيل',
            'position' => 5,
            'is_active' => true,
        ]);

        // 5. Visible again in sidebar
        $response3 = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response3->assertSee('مرحلة اختيارية قابلة للتعطيل');
    }

    public function test_case_6_deleting_stage_removes_it_from_sidebar(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_to_delete',
            'name_ar' => 'مرحلة سيتم حذفها',
            'position' => 5,
            'is_primary' => false,
            'is_active' => true,
        ]);
        $response1 = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response1->assertSee('مرحلة سيتم حذفها');

        $this->actingAs($this->admin)->delete(route('v2.settings.stages.destroy', $stage));

        $response2 = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response2->assertDontSee('مرحلة سيتم حذفها');
    }

    public function test_sidebar_stage_link_filters_daily_tasks_and_enforces_branch_scoping(): void
    {
        $branchA = Branch::query()->create([
            'name_ar' => 'فرع القاهرة',
            'code' => 'cairo',
            'is_active' => true,
        ]);

        $branchB = Branch::query()->create([
            'name_ar' => 'فرع الإسكندرية',
            'code' => 'alex',
            'is_active' => true,
        ]);

        // Regular user belonging to Branch A
        $userBranchA = User::factory()->create([
            'is_active' => true,
            'branch_id' => $branchA->id,
        ]);

        $group = Group::query()->create([
            'name' => 'Agent Group',
            'code' => 'agent',
            'is_system' => false,
        ]);
        $userBranchA->groups()->attach($group);

        $permission = \App\Models\Permission::query()->firstOrCreate(
            ['code' => CrmPermission::TASKS_VIEW->value],
            ['name_ar' => 'عرض المهام', 'module' => 'tasks']
        );
        $permLeads = \App\Models\Permission::query()->firstOrCreate(
            ['code' => CrmPermission::LEADS_VIEW->value],
            ['name_ar' => 'عرض العملاء', 'module' => 'leads']
        );
        $group->permissions()->syncWithoutDetaching([$permission->id, $permLeads->id]);

        $stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();
        $statusDonor = LeadStatus::query()->where('pipeline_stage_id', $stageDonor->id)->firstOrFail();

        // Lead in Branch A (accessible)
        $leadA = Lead::query()->create([
            'name' => 'متبرع فرع القاهرة المتاح',
            'branch_id' => $branchA->id,
            'lead_status_id' => $statusDonor->id,
            'assigned_user_id' => $userBranchA->id,
            'next_follow_up_at' => now()->addHour(),
        ]);

        // Lead in Branch B (inaccessible to user in Branch A without all-branches permission)
        $leadB = Lead::query()->create([
            'name' => 'متبرع فرع الإسكندرية المحجوب',
            'branch_id' => $branchB->id,
            'lead_status_id' => $statusDonor->id,
            'next_follow_up_at' => now()->addHour(),
        ]);

        // Access daily tasks filtered by stage_id
        $response = $this->actingAs($userBranchA)->get(route('v2.tasks.daily', ['stage_id' => $stageDonor->id]));
        $response->assertOk();
        $response->assertSee('متبرع فرع القاهرة المتاح');
        $response->assertDontSee('متبرع فرع الإسكندرية المحجوب');
    }

    public function test_sidebar_highlights_active_stage_link(): void
    {
        $stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();

        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily', ['stage_id' => $stageDonor->id]));
        $response->assertOk();

        // The specific stage link has class active
        $response->assertSee('crm-task-status-link active', false);
    }

    public function test_sidebar_supports_unlimited_stages_without_three_stage_cap(): void
    {
        // Create 4 custom stages (making total 6 stages)
        for ($i = 3; $i <= 6; $i++) {
            $this->actingAs($this->admin)->post(route('v2.settings.stages.store'), [
                'name_ar' => "المرحلة رقم {$i}",
                'color' => '#8b5cf6',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertOk();

        for ($i = 3; $i <= 6; $i++) {
            $response->assertSee("المرحلة رقم {$i}");
        }

        $allActive = PipelineStage::getActiveStagesForSidebar();
        $this->assertCount(8, $allActive);
    }
}

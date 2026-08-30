<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PipelineStageRemovalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::query()->firstOrCreate(
            ['code' => 'main_branch_test'],
            ['name_ar' => 'فرع الاختبار', 'is_active' => true]
        );

        $this->admin = User::factory()->create([
            'username' => 'test_workflow_admin',
            'name' => 'مدير النظام للاختبار',
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);

        $superGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Admin', 'is_system' => true]
        );
        $this->admin->groups()->syncWithoutDetaching([$superGroup->id]);

        $permissions = Permission::query()->pluck('id')->all();
        $superGroup->permissions()->syncWithoutDetaching($permissions);
    }

    public function test_target_stages_are_no_longer_active_or_in_active_ordered_collection(): void
    {
        $activeStages = PipelineStage::activeOrdered();
        $activeNames = $activeStages->pluck('name_ar')->all();

        $this->assertNotContains('الاهتمام', $activeNames);
        $this->assertNotContains('التفاوض', $activeNames);

        $activeCodes = $activeStages->pluck('code')->all();
        $this->assertNotContains('interest', $activeCodes);
        $this->assertNotContains('negotiation', $activeCodes);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $sidebarNames = $sidebarStages->pluck('name_ar')->all();
        $this->assertNotContains('الاهتمام', $sidebarNames);
        $this->assertNotContains('التفاوض', $sidebarNames);
    }

    public function test_no_lead_has_orphan_or_invalid_lead_status_id(): void
    {
        $invalidLeadsCount = Lead::query()
            ->whereDoesntHave('status')
            ->count();

        $this->assertSame(0, $invalidLeadsCount);

        // All existing leads belong to active stages
        $leadsInInactiveStages = Lead::query()
            ->whereHas('status.stage', fn ($q) => $q->where('is_active', false))
            ->count();

        $this->assertSame(0, $leadsInInactiveStages);
    }

    public function test_kanban_renders_without_target_stages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $kanbanColumns = $response->viewData('kanbanColumns');
        $this->assertIsArray($kanbanColumns);

        $columnTitles = array_column($kanbanColumns, 'title');
        $columnNames = array_column($kanbanColumns, 'name');

        $this->assertNotContains('الاهتمام', $columnTitles);
        $this->assertNotContains('التفاوض', $columnTitles);
        $this->assertNotContains('الاهتمام', $columnNames);
        $this->assertNotContains('التفاوض', $columnNames);
    }

    public function test_dashboard_renders_without_target_stages(): void
    {
        app()->setLocale('ar');
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $stageDistribution = $response->viewData('stageDistribution');
        $labels = $stageDistribution['labels'] ?? [];

        $this->assertNotContains('الاهتمام', $labels);
        $this->assertNotContains('التفاوض', $labels);
    }

    public function test_stage_settings_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings.stages.index'));
        $response->assertOk();
        $response->assertViewHas('stages');
        $response->assertViewHas('totalStagesCount');
    }

    public function test_lead_history_remains_intact_for_historical_leads(): void
    {
        $newStatus = LeadStatus::query()
            ->whereHas('stage', fn ($q) => $q->where('code', 'new'))
            ->firstOrFail();

        $donorStatus = LeadStatus::query()
            ->whereHas('stage', fn ($q) => $q->where('code', 'donor'))
            ->firstOrFail();

        $lead = Lead::query()->create([
            'name' => 'عميل فحص السجل',
            'lead_status_id' => $newStatus->id,
            'branch_id' => $this->branch->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $history = LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => $newStatus->id,
            'to_status_id' => $donorStatus->id,
            'changed_by' => $this->admin->name,
            'changed_by_user_id' => $this->admin->id,
            'note' => 'تحويل اختباري',
            'changed_at' => now(),
        ]);

        $loadedHistory = LeadStatusHistory::query()
            ->with(['fromStatus', 'toStatus'])
            ->findOrFail($history->id);

        $this->assertNotNull($loadedHistory->fromStatus);
        $this->assertNotNull($loadedHistory->toStatus);
        $this->assertSame($newStatus->id, $loadedHistory->fromStatus->id);
        $this->assertSame($donorStatus->id, $loadedHistory->toStatus->id);
    }

    public function test_custom_stage_creation_and_lifecycle_remains_functional(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة اختبار ديناميكية',
            'description_ar' => 'وصف المرحلة للاختبار',
            'color' => '#10b981',
            'icon' => 'bi-star',
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));

        $createdStage = PipelineStage::query()->where('name_ar', 'مرحلة اختبار ديناميكية')->first();
        $this->assertNotNull($createdStage);
        $this->assertTrue($createdStage->is_active);
        $this->assertFalse($createdStage->is_primary);

        // Default status created automatically
        $status = $createdStage->statuses()->first();
        $this->assertNotNull($status);
        $this->assertSame('مرحلة اختبار ديناميكية', $status->name_ar);

        // Verify it appears in active stages
        $this->assertTrue(PipelineStage::activeOrdered()->contains('id', $createdStage->id));

        // Delete test stage cleanly
        $deleteResponse = $this->actingAs($this->admin)->delete(route('v2.settings.stages.destroy', $createdStage));
        $deleteResponse->assertRedirect(route('v2.settings.stages.index'));

        $this->assertNull(PipelineStage::query()->find($createdStage->id));
    }

    public function test_lead_transition_service_works_cleanly_without_fk_errors(): void
    {
        $donorStatus = LeadStatus::query()
            ->whereHas('stage', fn ($q) => $q->where('code', 'donor'))
            ->firstOrFail();

        $newStatus = LeadStatus::query()
            ->whereHas('stage', fn ($q) => $q->where('code', 'new'))
            ->firstOrFail();

        $lead = Lead::query()->create([
            'name' => 'عميل اختبار الترانزيشن',
            'lead_status_id' => $newStatus->id,
            'branch_id' => $this->branch->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'تبرع عام'],
            ['is_active' => true, 'position' => 1]
        );

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $donorStatus,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'تم الاتفاق',
                'donation' => [
                    'donation_type_id' => $donationType->id,
                    'donation_type' => $donationType->name_ar,
                    'amount' => '1000',
                    'cycle' => 'monthly',
                ],
                'donation_intent' => 'conversion',
            ]
        );

        $this->assertSame($donorStatus->id, $result['lead']->lead_status_id);

        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status_id' => $newStatus->id,
            'to_status_id' => $donorStatus->id,
        ]);
    }
}

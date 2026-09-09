<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FollowupLaterStageWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageNoAnswer;
    private PipelineStage $stageFollowupLater;
    private PipelineStage $stageNotInterested;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusNoAnswer;
    private LeadStatus $statusFollowupLater;
    private LeadStatus $statusNotInterested;
    private LeadStatus $statusDonor;

    protected function setUp(): void
    {
        parent::setUp();

        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Admin Group', 'is_system' => true]
        );

        $this->admin = User::factory()->create([
            'name' => 'Followup Later Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($group);

        $this->stageNew = PipelineStage::query()->where('code', 'new')->firstOrFail();
        $this->stageNoAnswer = PipelineStage::query()->where('code', 'no_answer')->firstOrFail();
        $this->stageFollowupLater = PipelineStage::query()->where('code', 'followup_later')->firstOrFail();
        $this->stageNotInterested = PipelineStage::query()->where('code', 'not_interested')->firstOrFail();
        $this->stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();

        $this->statusNew = LeadStatus::query()->where('code', 'new')->firstOrFail();
        $this->statusNoAnswer = LeadStatus::query()->where('code', 'no_answer')->firstOrFail();
        $this->statusFollowupLater = LeadStatus::query()->where('code', 'followup_later')->firstOrFail();
        $this->statusNotInterested = LeadStatus::query()->where('code', 'not_interested')->firstOrFail();
        $this->statusDonor = LeadStatus::query()->where('code', 'donor')->firstOrFail();
    }

    public function test_pipeline_stages_and_statuses_contain_followup_later_at_position_three(): void
    {
        $stages = PipelineStage::query()->where('is_primary', true)->orderBy('position')->get();
        $this->assertCount(5, $stages);
        $this->assertSame(
            ['new', 'no_answer', 'followup_later', 'not_interested', 'donor'],
            $stages->pluck('code')->all()
        );

        $this->assertSame(1, $this->stageNew->position);
        $this->assertSame(2, $this->stageNoAnswer->position);
        $this->assertSame(3, $this->stageFollowupLater->position);
        $this->assertSame(4, $this->stageNotInterested->position);
        $this->assertSame(5, $this->stageDonor->position);

        $this->assertSame('متابعة لاحقة', $this->stageFollowupLater->name_ar);
        $this->assertSame('متابعة لاحقة', $this->statusFollowupLater->name_ar);
        $this->assertSame($this->stageFollowupLater->id, $this->statusFollowupLater->pipeline_stage_id);
        $this->assertFalse($this->statusFollowupLater->is_terminal);
    }

    public function test_calling_new_lead_and_logging_followup_later_advances_stage_to_followup_later(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل جديد للمتابعة اللاحقة',
            'phone' => '01012345678',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01012345678',
            'is_primary' => true,
        ]);

        $this->assertSame($this->statusNew->id, $lead->lead_status_id);
        $this->assertSame('new', $lead->status->stage->code);

        $callbackDate = now()->addDays(3)->format('Y-m-d H:i');

        // Agent calls the lead, clicks "متابعة لاحقة", specifies next appointment, and saves
        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusFollowupLater->id,
            'communication_type' => 'call',
            'outcome' => 'العميل مهتم وطلب الاتصال به بعد 3 أيام',
            'next_follow_up_at' => $callbackDate,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $lead->refresh();

        // Lead stage must NO LONGER be "new" — it must now be "followup_later"
        $this->assertNotSame($this->statusNew->id, $lead->lead_status_id);
        $this->assertSame($this->statusFollowupLater->id, $lead->lead_status_id);
        $this->assertSame('followup_later', $lead->status->stage->code);
        $this->assertSame('متابعة لاحقة', $lead->status->stage->name_ar);
        $this->assertNotNull($lead->next_follow_up_at);

        // Status history must record the transition from new to followup_later
        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusNew->id,
            'to_status_id' => $this->statusFollowupLater->id,
        ]);
    }

    public function test_followup_later_requires_next_follow_up_at_validation(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل اختبار التحقق',
            'phone' => '01098765432',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // Attempt to move to followup_later without providing next_follow_up_at -> MUST fail
        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusFollowupLater->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة لاحقة بدون تاريخ',
            'next_follow_up_at' => '',
        ]);

        $response->assertSessionHasErrors('next_follow_up_at');
        $this->assertSame($this->statusNew->id, $lead->fresh()->lead_status_id);
    }

    public function test_kanban_view_displays_followup_later_column_and_cards(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل كانبان متابعة لاحقة',
            'phone' => '01055554444',
            'lead_status_id' => $this->statusFollowupLater->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(3),
        ]);
        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01055554444',
            'is_primary' => true,
        ]);

        $response = $this->get(route('v2.leads.kanban'));
        $response->assertOk();

        // 1. Column exists with stage_code followup_later
        $kanbanColumns = $response->viewData('kanbanColumns');
        $colCodes = array_column($kanbanColumns, 'code');
        $this->assertContains('followup_later', $colCodes);

        $targetCol = null;
        foreach ($kanbanColumns as $col) {
            if ($col['code'] === 'followup_later') {
                $targetCol = $col;
                break;
            }
        }

        $this->assertNotNull($targetCol);
        $this->assertSame('متابعة لاحقة', $targetCol['name']);
        $this->assertSame(3, $targetCol['position']);
        $this->assertSame(1, $targetCol['scope_counts']['today']);

        // 2. Card rendered in view
        $response->assertSee('عميل كانبان متابعة لاحقة');
        $response->assertSee('متابعة لاحقة');
    }

    public function test_kanban_cards_endpoint_paginates_followup_later_leads(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل بطاقة كانبان أجاكس',
            'phone' => '01077778888',
            'lead_status_id' => $this->statusFollowupLater->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);
        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01077778888',
            'is_primary' => true,
        ]);

        $response = $this->getJson(route('v2.leads.kanban.cards', [
            'stage_code' => 'followup_later',
            'scope' => 'upcoming',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'stage_code' => 'followup_later',
            'scope' => 'upcoming',
        ]);
        $this->assertStringContainsString('عميل بطاقة كانبان أجاكس', $response->json('html'));
    }

    public function test_task_status_controller_renders_followup_later_status_queue(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('v2.tasks.status', ['status' => 'followup-later']));
        $response->assertOk();
        $response->assertSee('متابعة لاحقة');
        $response->assertViewHas('statusRecord', function ($status) {
            return $status->code === 'followup_later';
        });
    }
}

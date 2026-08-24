<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadWorkflowArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageNoAnswer;
    private PipelineStage $stageNotInterested;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusNoAnswer;
    private LeadStatus $statusNotInterested;
    private LeadStatus $statusDonor;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::query()->create([
            'name_ar' => 'الفرع الرئيسي',
            'code' => 'main',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);

        $superGroup = Group::query()->create([
            'name' => 'Super Admin',
            'code' => 'super_admin',
            'is_system' => true,
        ]);
        $this->admin->groups()->attach($superGroup);

        foreach (CrmPermission::cases() as $perm) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $perm->value],
                ['name_ar' => $perm->value, 'module' => 'system']
            );
            $superGroup->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $this->stageNew = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'color' => '#3478f6', 'is_primary' => true, 'is_active' => true]
        );
        $this->statusNew = $this->stageNew->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $this->stageNew->id, 'name_ar' => 'جديد', 'position' => 1, 'color' => '#3478f6', 'is_terminal' => false]
        );

        $this->stageNoAnswer = PipelineStage::query()->firstOrCreate(
            ['code' => 'no_answer'],
            ['name_ar' => 'لم يتم الرد', 'position' => 2, 'color' => '#e59b16', 'is_primary' => true, 'is_active' => true]
        );
        $this->statusNoAnswer = $this->stageNoAnswer->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'no_answer'],
            ['pipeline_stage_id' => $this->stageNoAnswer->id, 'name_ar' => 'لم يتم الرد', 'position' => 2, 'color' => '#e59b16', 'is_terminal' => false]
        );

        $this->stageNotInterested = PipelineStage::query()->firstOrCreate(
            ['code' => 'not_interested'],
            ['name_ar' => 'غير مهتم', 'position' => 3, 'color' => '#dc2637', 'is_primary' => true, 'is_active' => true]
        );
        $this->statusNotInterested = $this->stageNotInterested->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'not_interested'],
            ['pipeline_stage_id' => $this->stageNotInterested->id, 'name_ar' => 'غير مهتم', 'position' => 3, 'color' => '#dc2637', 'is_terminal' => true]
        );

        $this->stageDonor = PipelineStage::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 4, 'color' => '#16a34a', 'is_primary' => true, 'is_active' => true]
        );
        $this->statusDonor = $this->stageDonor->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['pipeline_stage_id' => $this->stageDonor->id, 'name_ar' => 'متبرع', 'position' => 4, 'color' => '#16a34a', 'is_terminal' => false]
        );
    }

    public function test_custom_stage_automatically_gets_default_status(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة كبار الشخصيات',
            'color' => '#7c3aed',
            'icon' => 'bi-star-fill',
            'description_ar' => 'مرحلة مخصصة لكبار الشخصيات',
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));

        $stage = PipelineStage::query()->where('name_ar', 'مرحلة كبار الشخصيات')->firstOrFail();
        $this->assertFalse($stage->isPrimary());
        $this->assertTrue($stage->is_active);

        // Assert that a LeadStatus was created automatically and linked
        $status = $stage->statuses()->first();
        $this->assertNotNull($status);
        $this->assertSame($stage->id, $status->pipeline_stage_id);
        $this->assertSame($stage->code, $status->code);
        $this->assertSame('مرحلة كبار الشخصيات', $status->name_ar);
        $this->assertSame('#7c3aed', $status->color);
        $this->assertFalse($status->is_terminal);
    }

    public function test_existing_stage_without_status_is_repaired(): void
    {
        // Force-create an orphan stage bypassing hooks
        $stage = new PipelineStage([
            'code' => 'orphan_stage_test',
            'name_ar' => 'مرحلة بدون حالة',
            'position' => 99,
            'color' => '#0ea5e9',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $stage->saveQuietly();

        $this->assertSame(0, $stage->statuses()->count());

        $repaired = PipelineStage::repairOrphanStages();
        $this->assertGreaterThanOrEqual(1, $repaired);

        $stage->refresh();
        $this->assertSame(1, $stage->statuses()->count());
        $status = $stage->statuses()->first();
        $this->assertSame('orphan_stage_test', $status->code);
        $this->assertSame('مرحلة بدون حالة', $status->name_ar);
    }

    public function test_custom_stage_can_receive_kanban_transition(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a custom stage
        $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة المتابعة الدورية',
            'color' => '#059669',
        ]);

        $customStage = PipelineStage::query()->where('name_ar', 'مرحلة المتابعة الدورية')->firstOrFail();
        $customStatus = $customStage->statuses()->firstOrFail();

        // 2. Assert Kanban data has valid destination_status_id
        $response = $this->get(route('v2.leads.kanban'));
        $response->assertOk();
        $response->assertViewHas('kanbanColumns', function (array $columns) use ($customStage, $customStatus) {
            foreach ($columns as $col) {
                if ($col['id'] === $customStage->id) {
                    return $col['destination_status_id'] === $customStatus->id
                        && $col['has_destination_status'] === true;
                }
            }
            return false;
        });

        // 3. Create a lead and transition it into the custom stage via follow-up popup flow
        $lead = Lead::query()->create([
            'name' => 'عميل اختبار كانبان',
            'phone' => '01000001111',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $followupResponse = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق ونقل العميل إلى المرحلة المخصصة',
            'kanban_popup' => 1,
        ]);

        $followupResponse->assertRedirect(route('v2.leads.followups.index', ['lead' => $lead->id, 'kanban_popup' => 1, 'saved' => 1]));

        $lead->refresh();
        $this->assertSame($customStatus->id, $lead->lead_status_id);

        // Verify history was created
        $history = LeadStatusHistory::query()->where('lead_id', $lead->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($this->statusNew->id, $history->from_status_id);
        $this->assertSame($customStatus->id, $history->to_status_id);
    }

    public function test_lead_transition_service_updates_status_and_history_atomically(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل ترانزيشن سيرفيس',
            'phone' => '01000002222',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $service = app(LeadTransitionService::class);

        $result = $service->transition(
            $lead,
            $this->statusDonor,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'meeting',
                'outcome' => 'تم إتمام التبرع في المقابلة',
                'donation_value' => 5000.00,
                'donation_cycle' => 'monthly',
                'history_note' => 'تم تحويل العميل لمتبرع رسمي',
            ]
        );

        $this->assertTrue($result['changed']);
        $this->assertSame($this->statusDonor->id, $result['lead']->lead_status_id);
        $this->assertEquals(5000.00, (float) $result['lead']->donation_value);
        $this->assertSame('monthly', $result['lead']->donation_cycle);

        // History record verification
        $this->assertNotNull($result['history']);
        $this->assertSame($this->statusNew->id, $result['history']->from_status_id);
        $this->assertSame($this->statusDonor->id, $result['history']->to_status_id);
        $this->assertSame('تم تحويل العميل لمتبرع رسمي', $result['history']->note);
        $this->assertSame($this->admin->id, $result['history']->changed_by_user_id);

        // Followup record verification
        $this->assertNotNull($result['followup']);
        $this->assertSame('meeting', $result['followup']->communication_type);
        $this->assertSame('تم إتمام التبرع في المقابلة', $result['followup']->outcome);
    }

    public function test_no_answer_rule_is_enforced_across_endpoints(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل لم يتم الرد',
            'phone' => '01000003333',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $service = app(LeadTransitionService::class);

        // 1. Must fail when transitioning to no_answer in follow-up flow without next_follow_up_at
        $this->expectException(ValidationException::class);
        $service->transition(
            $lead,
            $this->statusNoAnswer,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'رن ولم يرد',
                'next_follow_up_at' => null,
            ]
        );
    }

    public function test_no_answer_succeeds_with_callback_date(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل لم يتم الرد مع موعد',
            'phone' => '01000004444',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $callbackDate = now()->addDays(2)->format('Y-m-d H:i:s');
        $service = app(LeadTransitionService::class);

        $result = $service->transition(
            $lead,
            $this->statusNoAnswer,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'رن ولم يرد - معاودة الاتصال بعد يومين',
                'next_follow_up_at' => $callbackDate,
            ]
        );

        $this->assertSame($this->statusNoAnswer->id, $result['lead']->lead_status_id);
        $this->assertNotNull($result['lead']->next_follow_up_at);
        $this->assertSame(
            now()->addDays(2)->format('Y-m-d H:i'),
            $result['lead']->next_follow_up_at->format('Y-m-d H:i')
        );
    }

    public function test_not_interested_clears_follow_up_date(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل غير مهتم',
            'phone' => '01000005555',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'next_follow_up_at' => now()->addDay(),
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $this->assertNotNull($lead->next_follow_up_at);

        $service = app(LeadTransitionService::class);
        $result = $service->transition(
            $lead,
            $this->statusNotInterested,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'العميل اعتذر وغير مهتم',
                'disinterest_reason' => 'غير مهتم بالخدمة حالياً',
            ]
        );

        $this->assertSame($this->statusNotInterested->id, $result['lead']->lead_status_id);
        $this->assertNull($result['lead']->next_follow_up_at);
    }

    public function test_lead_edit_uses_transition_service_and_creates_history(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل تعديل بيانات',
            'phone' => '01000006666',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Edit with status change
        $response = $this->patch(route('v2.leads.update', $lead), [
            'name' => 'عميل تعديل بيانات محدث',
            'phone' => '01000006666',
            'lead_status_id' => $this->statusDonor->id,
            'donation_value' => 2500,
        ]);

        $response->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertSame('عميل تعديل بيانات محدث', $lead->name);
        $this->assertSame($this->statusDonor->id, $lead->lead_status_id);

        $historyCount = LeadStatusHistory::query()->where('lead_id', $lead->id)->count();
        $this->assertSame(1, $historyCount);

        $history = LeadStatusHistory::query()->where('lead_id', $lead->id)->first();
        $this->assertSame($this->statusNew->id, $history->from_status_id);
        $this->assertSame($this->statusDonor->id, $history->to_status_id);

        // 2. Non-status Lead edit (name only) should NOT create false status history
        $this->patch(route('v2.leads.update', $lead), [
            'name' => 'عميل تعديل الاسم فقط',
            'phone' => '01000006666',
            'lead_status_id' => $this->statusDonor->id, // unchanged
        ]);

        $lead->refresh();
        $this->assertSame('عميل تعديل الاسم فقط', $lead->name);
        $this->assertSame(1, LeadStatusHistory::query()->where('lead_id', $lead->id)->count());
    }

    public function test_quick_followup_uses_transition_service_and_creates_history(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'عميل متابعة سريعة',
            'phone' => '01000007777',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $callbackDate = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->post(route('v2.tasks.quick_followup', $lead), [
            'communication_type' => 'call',
            'outcome' => 'مكالمة سريعة للمتابعة لاحقاً',
            'lead_status_id' => $this->statusNoAnswer->id,
            'next_follow_up_at' => $callbackDate,
        ]);

        $lead->refresh();
        $this->assertSame($this->statusNoAnswer->id, $lead->lead_status_id);

        // Followup created
        $this->assertSame(1, LeadFollowup::query()->where('lead_id', $lead->id)->count());

        // Status history created atomically
        $history = LeadStatusHistory::query()->where('lead_id', $lead->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame($this->statusNew->id, $history->from_status_id);
        $this->assertSame($this->statusNoAnswer->id, $history->to_status_id);
    }
}

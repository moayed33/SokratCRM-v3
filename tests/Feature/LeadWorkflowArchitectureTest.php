<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Donation;
use App\Models\DonationType;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeadWorkflowArchitectureTest extends TestCase
{
    use DatabaseTransactions;

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
        $this->assertNotNull($result['donation']);
        $this->assertSame('5000.00', (string) $result['donation']->amount);
    }

    public function test_donor_popup_shows_basic_data_and_donation_fields(): void
    {
        $donationType = DonationType::query()->create([
            'name_ar' => 'صدقة',
            'is_active' => true,
            'position' => 1,
        ]);
        $lead = Lead::query()->create([
            'name' => 'عميل للتحويل إلى متبرع',
            'phone' => '01012345678',
            'address' => 'القاهرة، مصر الجديدة',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead,
            'kanban_popup' => 1,
            'target_status_id' => $this->statusDonor->id,
        ]));

        $response->assertOk()
            ->assertSee($lead->name)
            ->assertSee($lead->phone)
            ->assertSee($lead->address)
            ->assertSee($donationType->name_ar)
            ->assertSee('name="donation_type_id"', false)
            ->assertSee('name="donation_value"', false)
            ->assertSee('name="donation_cycle"', false);
    }

    public function test_new_lead_form_does_not_ask_for_donation_data(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk()
            ->assertDontSee('name="donation_type"', false)
            ->assertDontSee('name="donation_type_id"', false)
            ->assertDontSee('name="donation_value"', false)
            ->assertDontSee('name="donation_cycle"', false)
            ->assertDontSee('name="donation_purpose"', false);
    }

    public function test_donor_followup_requires_donation_and_schedules_the_next_cycle(): void
    {
        $donationType = DonationType::query()->create([
            'name_ar' => 'زكاة مال',
            'is_active' => true,
            'position' => 1,
        ]);
        $lead = Lead::query()->create([
            'name' => 'عميل بتبرع شهري',
            'phone' => '01087654321',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $missingDonation = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusDonor->id,
                'communication_type' => 'call',
                'outcome' => 'تم تأكيد التبرع',
            ]
        );
        $missingDonation->assertSessionHasErrors([
            'donation_type_id',
            'donation_value',
            'donation_cycle',
        ]);
        $this->assertSame($this->statusNew->id, $lead->fresh()->lead_status_id);

        $donationAt = now()->setDate(2026, 8, 25)->setTime(10, 30, 0);
        $this->travelTo($donationAt);

        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'تم استلام أول تبرع',
            'donation_type_id' => $donationType->id,
            'donation_value' => 1250.50,
            'donation_cycle' => 'monthly',
        ]);

        $response->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertSame($this->statusDonor->id, $lead->lead_status_id);
        $this->assertSame($donationType->id, $lead->donation_type_id);
        $this->assertSame($donationType->name_ar, $lead->donation_type);
        $this->assertSame('1250.50', (string) $lead->donation_value);
        $this->assertSame('monthly', $lead->donation_cycle);
        $this->assertSame('2026-09-25 10:30', $lead->next_follow_up_at?->format('Y-m-d H:i'));

        $followup = LeadFollowup::query()->where('lead_id', $lead->id)->latest('id')->firstOrFail();
        $this->assertSame('2026-09-25 10:30', $followup->next_follow_up_at?->format('Y-m-d H:i'));

        $this->travelBack();
    }

    public function test_donor_followups_only_append_real_donations_and_keep_receipts_private(): void
    {
        Storage::fake('local');

        $donationType = DonationType::query()->create([
            'name_ar' => 'صدقة جارية',
            'name_en' => 'Ongoing Charity',
            'is_active' => true,
            'position' => 1,
        ]);
        $lead = Lead::query()->create([
            'name' => 'عميل بسجل تبرعات',
            'phone' => '01087650000',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $conversion = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusDonor->id,
                'communication_type' => 'meeting',
                'outcome' => 'تم استلام أول تبرع',
                'donation_type_id' => $donationType->id,
                'donation_value' => 600,
                'donation_cycle' => 'one_time',
                'donation_receipt' => UploadedFile::fake()->image('first-receipt.jpg'),
            ],
        );

        $conversion->assertSessionHasNoErrors();
        $firstDonation = Donation::query()->whereBelongsTo($lead)->sole();
        $this->assertSame('600.00', (string) $firstDonation->amount);
        $this->assertSame($this->admin->id, $firstDonation->recorded_by_user_id);
        $this->assertNotNull($firstDonation->lead_followup_id);
        Storage::disk('local')->assertExists($firstDonation->receipt_path);

        $routineFollowup = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة دورية بدون تبرع جديد',
        ]);

        $routineFollowup->assertSessionHasNoErrors();
        $this->assertSame(1, Donation::query()->whereBelongsTo($lead)->count());

        $newDonation = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'تم استلام تبرع جديد',
            'record_donation' => true,
            'donation_type_id' => $donationType->id,
            'donation_value' => 900,
            'donation_cycle' => 'monthly',
        ]);

        $newDonation->assertSessionHasNoErrors();
        $this->assertSame(2, Donation::query()->whereBelongsTo($lead)->count());
        $this->assertSame('900.00', (string) $lead->fresh()->donation_value);

        $this->get(route('v2.leads.donations.receipt.preview', [$lead, $firstDonation]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $otherLead = Lead::query()->create([
            'name' => 'عميل آخر',
            'phone' => '01087650001',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
        ]);
        $this->get(route('v2.leads.donations.receipt.preview', [$otherLead, $firstDonation]))
            ->assertNotFound();

        $this->get(route('v2.leads.show', $lead))
            ->assertOk()
            ->assertSee('600.00')
            ->assertSee('900.00')
            ->assertSee($this->admin->name)
            ->assertSee(__('crm.preview_receipt'));
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

    public function test_canonical_followup_cannot_bypass_required_donor_donation(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل تحويل سريع',
            'phone' => '01000003339',
            'branch_id' => $this->branch->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'communication_type' => 'call',
                'outcome' => 'محاولة تحويل بدون بيانات تبرع',
                'lead_status_id' => $this->statusDonor->id,
            ],
        );

        $response->assertSessionHasErrors('donation_value');
        $this->assertSame($this->statusNew->id, $lead->fresh()->lead_status_id);
        $this->assertSame(0, Donation::query()->whereBelongsTo($lead)->count());
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

    public function test_profile_edit_preserves_status_and_creates_no_transition_history(): void
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

        $response = $this->patch(route('v2.leads.update', $lead), [
            'name' => 'عميل تعديل بيانات محدث',
            'phone' => '01000006666',
            'lead_status_id' => $this->statusDonor->id,
            'pipeline_stage_id' => $this->stageDonor->id,
            'donation_value' => 2500,
        ]);

        $response->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertSame('عميل تعديل بيانات محدث', $lead->name);
        $this->assertSame($this->statusNew->id, $lead->lead_status_id);
        $this->assertSame(0, LeadStatusHistory::query()->where('lead_id', $lead->id)->count());
    }

    public function test_canonical_followup_endpoint_uses_transition_service_and_creates_history(): void
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

        $response = $this->post(route('v2.leads.followups.store', $lead), [
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

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FourCanonicalStatusesTest extends TestCase
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

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->groups()->attach($group);

        $this->seed(\Database\Seeders\CrmV2PipelineSeeder::class);

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

    public function test_database_contains_exactly_four_canonical_stages_and_four_statuses(): void
    {
        $stages = PipelineStage::query()->where('is_primary', true)->orderBy('position')->get();
        $this->assertCount(5, $stages);
        $this->assertSame(['new', 'no_answer', 'followup_later', 'not_interested', 'donor'], $stages->pluck('code')->all());

        $statuses = LeadStatus::query()->whereHas('stage', fn ($q) => $q->where('is_primary', true))->orderBy('position')->get();
        $this->assertCount(5, $statuses);
        $this->assertSame(['new', 'no_answer', 'followup_later', 'not_interested', 'donor'], $statuses->pluck('code')->all());

        // Verify 1:1 stage mappings
        $this->assertSame($this->stageNew->id, $this->statusNew->pipeline_stage_id);
        $this->assertSame($this->stageNoAnswer->id, $this->statusNoAnswer->pipeline_stage_id);
        $this->assertSame($this->stageFollowupLater->id, $this->statusFollowupLater->pipeline_stage_id);
        $this->assertSame($this->stageNotInterested->id, $this->statusNotInterested->pipeline_stage_id);
        $this->assertSame($this->stageDonor->id, $this->statusDonor->pipeline_stage_id);

        // Terminal status verification
        $this->assertFalse($this->statusNew->is_terminal);
        $this->assertFalse($this->statusNoAnswer->is_terminal);
        $this->assertFalse($this->statusFollowupLater->is_terminal);
        $this->assertTrue($this->statusNotInterested->is_terminal);
        $this->assertFalse($this->statusDonor->is_terminal);
    }

    public function test_no_answer_requires_next_follow_up_date_on_followup(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Lead Testing No Answer',
            'phone' => '01000000001',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // Attempt transition to no_answer without next_follow_up_at -> should fail validation
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'Tried calling client but line was busy',
            'next_follow_up_at' => '',
        ]);

        $response->assertSessionHasErrors('next_follow_up_at');

        // Provide next_follow_up_at -> should succeed
        $responseSuccess = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'Tried calling client, scheduled next callback',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $this->assertSame($this->statusNoAnswer->id, $lead->fresh()->lead_status_id);
        $this->assertNotNull($lead->fresh()->next_follow_up_at);
    }

    public function test_not_interested_requires_disinterest_reason_and_clears_next_follow_up_date(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Lead Testing Not Interested',
            'phone' => '01000000002',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        // Attempt transition to not_interested without disinterest_reason -> should fail
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNotInterested->id,
            'communication_type' => 'call',
            'outcome' => 'Client said not interested',
            'disinterest_reason' => '',
        ]);

        $response->assertSessionHasErrors('disinterest_reason');

        // Provide disinterest_reason -> should succeed and clear next_follow_up_at
        $responseSuccess = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNotInterested->id,
            'communication_type' => 'call',
            'outcome' => 'Client declined participation',
            'disinterest_reason' => 'غير مهتم بالتبرعات الخيرية حالياً',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $freshLead = $lead->fresh();
        $this->assertSame($this->statusNotInterested->id, $freshLead->lead_status_id);
        $this->assertNull($freshLead->next_follow_up_at);
    }

    public function test_donor_status_preserves_donation_recording_workflow(): void
    {
        $donationType = DonationType::query()->where('is_active', true)->firstOrFail();
        $lead = Lead::query()->create([
            'name' => 'Donor Converted Lead',
            'phone' => '01000000003',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'Confirmed donation pledge of 5000 EGP',
            'donation_type_id' => $donationType->id,
            'donation_value' => 5000,
            'donation_cycle' => 'one_time',
        ]);

        $response->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertSame($this->statusDonor->id, $lead->lead_status_id);
        $this->assertSame($donationType->id, $lead->donation_type_id);
        $this->assertSame('5000.00', (string) $lead->donation_value);
        $this->assertSame('one_time', $lead->donation_cycle);
        $this->assertNull($lead->next_follow_up_at);
    }

    public function test_canonical_followup_enforces_no_answer_and_not_interested_rules(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Quick Followup Lead',
            'phone' => '01000000004',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        // 1. No-answer transition without a callback date fails.
        $responseNoDate = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'communication_type' => 'call',
            'outcome' => 'No answer',
            'lead_status_id' => $this->statusNoAnswer->id,
            'next_follow_up_at' => '',
        ]);
        $responseNoDate->assertSessionHasErrors('next_follow_up_at');

        // 2. Not-interested transition clears the future callback.
        $responseNotInterested = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'communication_type' => 'call',
            'outcome' => 'Client explicitly refused',
            'lead_status_id' => $this->statusNotInterested->id,
            'disinterest_reason' => 'Client declined the offer',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);
        $responseNotInterested->assertSessionHasNoErrors();
        $this->assertNull($lead->fresh()->next_follow_up_at);
        $this->assertSame($this->statusNotInterested->id, $lead->fresh()->lead_status_id);
    }

    public function test_leads_index_filters_by_all_four_canonical_statuses(): void
    {
        $lead1 = Lead::query()->create([
            'name' => 'Lead In New',
            'phone' => '01011110001',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $lead2 = Lead::query()->create([
            'name' => 'Lead In No Answer',
            'phone' => '01011110002',
            'lead_status_id' => $this->statusNoAnswer->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        $lead3 = Lead::query()->create([
            'name' => 'Lead In Not Interested',
            'phone' => '01011110003',
            'lead_status_id' => $this->statusNotInterested->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $lead4 = Lead::query()->create([
            'name' => 'Lead In Donor',
            'phone' => '01011110004',
            'lead_status_id' => $this->statusDonor->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // Filter by 'donor'
        $responseDonor = $this->actingAs($this->admin)->get(route('v2.leads', ['status' => 'donor']));
        $responseDonor->assertOk();
        $responseDonor->assertSee('Lead In Donor');
        $responseDonor->assertDontSee('Lead In New');

        // Filter by 'no_answer'
        $responseNoAnswer = $this->actingAs($this->admin)->get(route('v2.leads', ['status' => 'no_answer']));
        $responseNoAnswer->assertOk();
        $responseNoAnswer->assertSee('Lead In No Answer');
        $responseNoAnswer->assertDontSee('Lead In Donor');
    }

    public function test_dashboard_renders_four_independent_canonical_status_cards(): void
    {
        Lead::query()->create([
            'name' => 'Lead New 1',
            'phone' => '01033330001',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        Lead::query()->create([
            'name' => 'Lead No Answer 1',
            'phone' => '01033330002',
            'lead_status_id' => $this->statusNoAnswer->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        Lead::query()->create([
            'name' => 'Lead Not Interested 1',
            'phone' => '01033330003',
            'lead_status_id' => $this->statusNotInterested->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        Lead::query()->create([
            'name' => 'Lead Donor 1',
            'phone' => '01033330004',
            'lead_status_id' => $this->statusDonor->id,
            'assigned_user_id' => $this->admin->id,
            'donation_value' => 2500.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('2,500.00');

        $response->assertViewHas('statusCards', function ($cards) {
            $codes = array_column($cards, 'code');
            return in_array('followup_later', $codes, true)
                && in_array('new', $codes, true)
                && in_array('donor', $codes, true);
        });
    }

    public function test_dashboard_filters_by_canonical_lead_status(): void
    {
        Lead::query()->create([
            'name' => 'Target In No Answer',
            'phone' => '01044440001',
            'lead_status_id' => $this->statusNoAnswer->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        Lead::query()->create([
            'name' => 'Target In Donor',
            'phone' => '01044440002',
            'lead_status_id' => $this->statusDonor->id,
            'assigned_user_id' => $this->admin->id,
            'donation_value' => 1000.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', ['status' => 'donor']));
        $response->assertOk();
        $response->assertViewHas('totalCustomersCount', 1);
        $response->assertViewHas('totalDonationValue', 1000.0);
    }
    public function test_kanban_renders_active_pipeline_stage_columns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();
        $response->assertViewHas('kanbanColumns', function ($columns) {
            $codes = array_column($columns, 'code');
            return in_array('new', $codes, true)
                && in_array('no_answer', $codes, true)
                && in_array('not_interested', $codes, true)
                && in_array('donor', $codes, true);
        });
    }
    public function test_kanban_popup_and_filtering_controls_are_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban', ['popup' => 1]));
        $response->assertOk();
        $response->assertViewHas('employees');
        $response->assertSee('kanban-toolbar');
        $response->assertSee('kanbanSearchInput');
        $response->assertSee('kanbanScopeFilterSelect');
        $response->assertSee('kanbanEmployeeFilterSelect');
        $response->assertSee('kanbanLimitSelect');
        $response->assertSee('kanban-column-pagination');
    }

    public function test_make_donation_query_parameter_preselects_donor_status_and_renders_donation_fields(): void
    {
        $this->actingAs($this->admin);
        $lead = Lead::query()->create([
            'name' => 'متبرع تجريبي',
            'phone' => '01099887766',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->get(route('v2.leads.followups.index', [$lead, 'make_donation' => 1]));
        $response->assertOk();
        $response->assertSee('donationReceiptField');
        $response->assertSee('receiptDropzone');
        $response->assertSee('Ctrl + V');
    }

    public function test_instant_donation_with_receipt_upload_records_donation_and_transitions_lead_to_donor(): void
    {
        $this->actingAs($this->admin);
        \Illuminate\Support\Facades\Storage::fake('local');

        $donationType = DonationType::query()->where('is_active', true)->firstOrFail();
        $instantMethod = \App\Models\InstantDonationMethod::query()->firstOrCreate(
            ['code' => 'instapay'],
            ['name_ar' => 'إنستاباي', 'name_en' => 'InstaPay', 'is_active' => true, 'position' => 1]
        );

        $lead = Lead::query()->create([
            'name' => 'محمد سعيد المتبرع',
            'phone' => '01234567890',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $receiptFile = UploadedFile::fake()->image('instapay_transfer.png', 400, 400);

        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'outcome' => 'تم تحويل مبلغ 1500 جنيه عبر إنستاباي وإرسال لقطة الشاشة.',
            'communication_type' => 'call',
            'donation_way' => 'instant',
            'donation_type_id' => $donationType->id,
            'donation_value' => 1500,
            'donation_cycle' => 'monthly',
            'instant_donation_method_id' => $instantMethod->id,
            'donation_receipt' => $receiptFile,
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals($this->statusDonor->id, $lead->lead_status_id);
        $this->assertEquals('donor', $lead->status?->stage?->code);

        $this->assertDatabaseHas('donations', [
            'lead_id' => $lead->id,
            'amount' => 1500.00,
            'cycle' => 'monthly',
            'donation_way' => 'instant',
            'instant_donation_method_id' => $instantMethod->id,
        ]);
    }

    public function test_collection_donation_spawns_collection_case_with_due_date_and_address(): void
    {
        $this->actingAs($this->admin);
        $donationType = DonationType::query()->where('is_active', true)->firstOrFail();

        $lead = Lead::query()->create([
            'name' => 'أحمد محمود كاش',
            'phone' => '01122334455',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'outcome' => 'طلب تحصيل بمندوب على العنوان المحدد.',
            'communication_type' => 'call',
            'donation_way' => 'collection',
            'donation_type_id' => $donationType->id,
            'donation_value' => 3000,
            'donation_cycle' => 'one_time',
            'collection_due_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'collection_address' => 'شارع التحرير، الدقي، الجيزة',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertDatabaseHas('collection_cases', [
            'lead_id' => $lead->id,
            'expected_amount' => 3000.00,
            'collection_address' => 'شارع التحرير، الدقي، الجيزة',
        ]);
    }

    public function test_call_buttons_support_microsip_via_sip_protocol(): void
    {
        $this->actingAs($this->admin);
        $lead = Lead::query()->create([
            'name' => 'عميل السنترال',
            'phone' => '01099887766',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $kanbanResponse = $this->get(route('v2.leads.kanban'));
        $kanbanResponse->assertOk();
        $kanbanResponse->assertSee('sip:01099887766');
        $kanbanResponse->assertSee('data-sip-href');

        $showResponse = $this->get(route('v2.leads.show', $lead));
        $showResponse->assertOk();
        $showResponse->assertSee('sip:01099887766');
    }

    public function test_normal_employee_cannot_create_or_import_or_export_and_sees_only_assigned_leads(): void
    {
        $employeeGroup = Group::query()->create([
            'code' => 'tele-sales-agent',
            'name' => 'Tele-sales Agent',
            'is_system' => false,
        ]);

        // Only give basic permissions (view leads, create followups)
        $viewPerm = \App\Models\Permission::query()->firstOrCreate(
            ['code' => CrmPermission::LEADS_VIEW->value],
            ['module' => 'leads', 'name_ar' => 'عرض العملاء', 'description' => 'عرض العملاء']
        );
        $followupPerm = \App\Models\Permission::query()->firstOrCreate(
            ['code' => CrmPermission::LEADS_FOLLOWUPS_CREATE->value],
            ['module' => 'leads', 'name_ar' => 'تسجيل المتابعات', 'description' => 'تسجيل المتابعات']
        );
        $employeeGroup->permissions()->sync([$viewPerm->id, $followupPerm->id]);
        $agent = User::factory()->create(['is_active' => true]);
        $agent->groups()->attach($employeeGroup->id);

        $otherUser = User::factory()->create(['is_active' => true]);

        $assignedLead = Lead::query()->create([
            'name' => 'عميل مسند للموظف',
            'phone' => '01000000001',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $agent->id,
            'created_by' => 'Admin',
        ]);

        $unassignedLead = Lead::query()->create([
            'name' => 'عميل لموظف آخر',
            'phone' => '01000000002',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $otherUser->id,
            'created_by' => 'Admin',
        ]);

        $this->actingAs($agent);

        // 1. Cannot access create lead page or POST lead store
        $this->get(route('v2.leads.create'))->assertForbidden();
        $this->post(route('v2.leads.store'), ['name' => 'Fake Lead', 'phone' => '01099999999'])->assertForbidden();

        // 2. Cannot access import page
        $this->get(route('v2.leads.import'))->assertForbidden();

        // Super admin can access create and store
        $this->actingAs($this->admin)->get(route('v2.leads.create'))->assertOk();
        $this->actingAs($agent);
        // 3. Cannot export leads
        $this->get(route('v2.leads.export'))->assertForbidden();

        // 4. Leads view only shows assigned lead
        $leadsResponse = $this->get(route('v2.leads'));
        $leadsResponse->assertOk();
        $leadsResponse->assertSee('عميل مسند للموظف');
        $leadsResponse->assertDontSee('عميل لموظف آخر');

        // 5. Kanban view only shows assigned lead
        $kanbanResponse = $this->get(route('v2.leads.kanban'));
        $kanbanResponse->assertOk();
        $kanbanResponse->assertSee('عميل مسند للموظف');
        $kanbanResponse->assertDontSee('عميل لموظف آخر');
    }


    public function test_daily_tasks_receives_all_four_canonical_statuses(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertOk();
        $response->assertViewHas('statuses', function ($statuses) {
            $codes = $statuses->pluck('code')->all();
            return in_array('followup_later', $codes, true) && in_array('new', $codes, true);
        });
    }

    public function test_task_status_controller_renders_canonical_status_tabs_without_duplicates(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.status', ['status' => 'no-answer']));
        $response->assertOk();
        $response->assertViewHas('statusLinks', function ($links) {
            return count($links) === 5 && array_keys($links) === ['new', 'no_answer', 'followup_later', 'not_interested', 'donor'];
        });
    }

    public function test_lead_import_normalizes_legacy_aliases_to_canonical_statuses(): void
    {
        $csvContent = "الاسم الأول,الاسم الأخير,رقم الهاتف,المصدر,الحالة\n"
            ."Ahmed,Ali,01099990001,Facebook,interested\n"
            ."Mahmoud,Hassan,01099990002,Website,no-answer\n"
            ."Khaled,Ibrahim,01099990003,Referral,not-interested\n"
            ."Sayed,Mohamed,01099990004,Event,donation_confirmed\n";

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
        ]);
        $previewResponse->assertOk();

        $preview = $previewResponse->viewData('preview');
        $this->assertNotNull($preview);
        $this->assertCount(4, $preview['rows']);
        $this->assertSame(0, $preview['error_count']);

        $statuses = array_column($preview['rows'], 'status');
        $this->assertSame(['جديد', 'لم يتم الرد', 'غير مهتم', 'متبرع'], $statuses);
    }
    public function test_settings_displays_four_independent_canonical_stages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings.stages.index'));
        $response->assertOk();

        $response->assertSee('جديد');
        $response->assertSee('لم يتم الرد');
        $response->assertSee('متابعة لاحقة');
        $response->assertSee('غير مهتم');
        $response->assertSee('متبرع');

        $stages = $response->viewData('stages');
        $primaryStages = $stages->where('is_primary', true);
        $this->assertCount(5, $primaryStages);
        $this->assertSame(['new', 'no_answer', 'followup_later', 'not_interested', 'donor'], $primaryStages->pluck('code')->values()->all());
    }
    public function test_sidebar_tasks_and_followups_displays_all_four_canonical_stages(): void
    {
        $sidebarStages = PipelineStage::getActiveStagesForSidebar()->where('is_primary', true);
        $this->assertCount(5, $sidebarStages);
        $this->assertSame(['new', 'no_answer', 'followup_later', 'not_interested', 'donor'], $sidebarStages->pluck('code')->values()->all());
    }

    public function test_leads_summary_renders_independent_canonical_stage_counts(): void
    {
        // Create sample leads across all 4 statuses
        Lead::query()->create(['name' => 'L1', 'phone' => '01000000010', 'lead_status_id' => $this->statusNew->id, 'assigned_user_id' => $this->admin->id]);
        Lead::query()->create(['name' => 'L2', 'phone' => '01000000011', 'lead_status_id' => $this->statusNew->id, 'assigned_user_id' => $this->admin->id]);
        Lead::query()->create(['name' => 'L3', 'phone' => '01000000012', 'lead_status_id' => $this->statusNoAnswer->id, 'assigned_user_id' => $this->admin->id]);
        Lead::query()->create(['name' => 'L4', 'phone' => '01000000013', 'lead_status_id' => $this->statusNotInterested->id, 'assigned_user_id' => $this->admin->id]);
        Lead::query()->create(['name' => 'L5', 'phone' => '01000000014', 'lead_status_id' => $this->statusDonor->id, 'assigned_user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads'));
        $response->assertOk();

        $stages = $response->viewData('stages');
        $primaryStages = $stages->where('is_primary', true);
        $this->assertCount(5, $primaryStages);
        $countsByCode = $stages->pluck('scoped_leads_count', 'code')->all();
        $this->assertGreaterThanOrEqual(2, $countsByCode['new']);
        $this->assertGreaterThanOrEqual(1, $countsByCode['no_answer']);
        $this->assertGreaterThanOrEqual(1, $countsByCode['not_interested']);
        $this->assertGreaterThanOrEqual(1, $countsByCode['donor']);
        $this->assertNotNull($response->viewData('totalLeads'));
    }

    public function test_leads_index_includes_whatsapp_action_for_local_egyptian_phone(): void
    {
        Lead::query()->create([
            'name' => 'WhatsApp Lead',
            'phone' => '01000000099',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('v2.leads'))
            ->assertOk()
            ->assertSee('href="https://wa.me/201000000099"', false)
            ->assertSee('bi bi-whatsapp', false);
    }

    public function test_transition_popup_resolves_each_canonical_target_code(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Popup Target Lead',
            'phone' => '01000000111',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        foreach ([
            'new' => $this->statusNew,
            'no_answer' => $this->statusNoAnswer,
            'followup_later' => $this->statusFollowupLater,
            'not_interested' => $this->statusNotInterested,
            'donor' => $this->statusDonor,
        ] as $code => $status) {
            $this->actingAs($this->admin)
                ->get(route('v2.leads.followups.index', [
                    'lead' => $lead,
                    'kanban_popup' => 1,
                    'target_status_code' => $code,
                ]))
                ->assertOk()
                ->assertViewHas('defaultStatusId', $status->id);
        }
    }

    public function test_kanban_transition_success_returns_saved_popup_response_and_post_message_contract(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Kanban Move Candidate',
            'phone' => '01011112233',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'Tried to reach out via kanban move',
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'kanban_popup' => 1,
            'context' => 'kanban',
        ]);
        $redirectUrl = (string) $response->headers->get('Location');
        $this->assertStringContainsString('kanban_popup=1', $redirectUrl);
        $this->assertStringContainsString('saved=1', $redirectUrl);
        $this->assertStringContainsString('context=kanban', $redirectUrl);
        $response->assertSessionHas('success');

        $lead->refresh();
        $this->assertSame($this->statusNoAnswer->id, $lead->lead_status_id);

        // Follow redirect using the session flash from the POST
        $viewResponse = $this->get($redirectUrl);
        $viewResponse->assertOk();
        $viewResponse->assertSee('crm-kanban-followup-saved');
        $viewResponse->assertSee('kanban');
    }

    public function test_kanban_transition_validation_failure_keeps_form_open_and_does_not_change_status(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Kanban Invalid Candidate',
            'phone' => '01033334455',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // Attempt no_answer without mandatory callback date
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'Invalid attempt',
            'kanban_popup' => 1,
            'context' => 'kanban',
        ]);

        $response->assertSessionHasErrors(['next_follow_up_at']);
        $lead->refresh();
        $this->assertSame($this->statusNew->id, $lead->lead_status_id);
    }

    public function test_daily_task_context_preserves_next_lead_redirect_payload(): void
    {
        $lead1 = Lead::query()->create([
            'name' => 'Task Lead 1',
            'phone' => '01044445566',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->subHour(),
        ]);

        $lead2 = Lead::query()->create([
            'name' => 'Task Lead 2 Waiting in Queue',
            'phone' => '01077778899',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead1), [
            'lead_status_id' => $this->statusNotInterested->id,
            'communication_type' => 'call',
            'outcome' => 'Declined service',
            'disinterest_reason' => 'price_high',
            'kanban_popup' => 1,
            'context' => 'daily_tasks',
        ]);

        $response->assertSessionHas('next_lead_url');
        $response->assertSessionHas('next_lead_id', $lead2->id);
    }

    public function test_followup_popup_displays_customer_donor_information_and_hides_company_centric_fields(): void
    {
        $lead = Lead::query()->create([
            'name' => 'فاطمة محمود المتبرعة',
            'phone' => '01012345678',
            'governorate' => 'الإسكندرية',
            'address' => 'شارع جمال عبد الناصر',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead->id,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('بيانات العميل / المتبرع');
        $response->assertSee('فاطمة محمود المتبرعة');
        $response->assertSee('01012345678');
        $response->assertSee('الإسكندرية');
        $response->assertSee('شارع جمال عبد الناصر');
        $response->assertDontSee('اسم الشركة');
        $response->assertDontSee('عدد المستخدمين');
        $response->assertDontSee('عدد الفروع');
        $response->assertDontSee('المنصب');
    }

    public function test_followup_popup_displays_donation_fields_when_donation_info_exists(): void
    {
        $lead = Lead::query()->create([
            'name' => 'متبرع دوري نشط',
            'phone' => '01099887766',
            'lead_status_id' => $this->statusDonor->id,
            'assigned_user_id' => $this->admin->id,
            'donation_type' => 'كفالة أيتام',
            'donation_cycle' => 'monthly',
            'donation_value' => '1500.00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead->id,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('كفالة أيتام');
        $response->assertSee('إجمالي التبرعات');
        $response->assertSee('1,500.00');
    }

    public function test_followup_popup_shows_unassigned_fallback_and_dynamic_stage(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل بدون موظف',
            'phone' => '01122334455',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead->id,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('غير معين');
        $response->assertSee($this->statusNew->stage->localizedName());
    }
}

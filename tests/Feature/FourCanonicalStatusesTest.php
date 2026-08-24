<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FourCanonicalStatusesTest extends TestCase
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
    protected function setUp(): void
    {
        parent::setUp();

        $group = Group::query()->create([
            'name' => 'Super Admin Group',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->groups()->attach($group);

        $this->seed(\Database\Seeders\CrmV2PipelineSeeder::class);

        $this->stageNew = PipelineStage::query()->where('code', 'new')->firstOrFail();
        $this->stageNoAnswer = PipelineStage::query()->where('code', 'no_answer')->firstOrFail();
        $this->stageNotInterested = PipelineStage::query()->where('code', 'not_interested')->firstOrFail();
        $this->stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();

        $this->statusNew = LeadStatus::query()->where('code', 'new')->firstOrFail();
        $this->statusNoAnswer = LeadStatus::query()->where('code', 'no_answer')->firstOrFail();
        $this->statusNotInterested = LeadStatus::query()->where('code', 'not_interested')->firstOrFail();
        $this->statusDonor = LeadStatus::query()->where('code', 'donor')->firstOrFail();
    }

    public function test_database_contains_exactly_four_canonical_stages_and_four_statuses(): void
    {
        $stages = PipelineStage::query()->orderBy('position')->get();
        $this->assertCount(4, $stages);
        $this->assertSame(['new', 'no_answer', 'not_interested', 'donor'], $stages->pluck('code')->all());

        $statuses = LeadStatus::query()->orderBy('position')->get();
        $this->assertCount(4, $statuses);
        $this->assertSame(['new', 'no_answer', 'not_interested', 'donor'], $statuses->pluck('code')->all());

        // Verify 1:1 stage mappings
        $this->assertSame($this->stageNew->id, $this->statusNew->pipeline_stage_id);
        $this->assertSame($this->stageNoAnswer->id, $this->statusNoAnswer->pipeline_stage_id);
        $this->assertSame($this->stageNotInterested->id, $this->statusNotInterested->pipeline_stage_id);
        $this->assertSame($this->stageDonor->id, $this->statusDonor->pipeline_stage_id);

        // Terminal status verification
        $this->assertFalse($this->statusNew->is_terminal);
        $this->assertFalse($this->statusNoAnswer->is_terminal);
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
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($this->statusDonor->id, $lead->fresh()->lead_status_id);
    }

    public function test_quick_followup_enforces_no_answer_and_not_interested_rules(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Quick Followup Lead',
            'phone' => '01000000004',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        // 1. Quick followup to no_answer without date -> fails
        $responseNoDate = $this->actingAs($this->admin)->post(route('v2.tasks.quick_followup', $lead), [
            'communication_type' => 'call',
            'outcome' => 'No answer',
            'lead_status_id' => $this->statusNoAnswer->id,
            'next_follow_up_at' => '',
        ]);
        $responseNoDate->assertSessionHasErrors('next_follow_up_at');

        // 2. Quick followup to not_interested -> clears next_follow_up_at
        $responseNotInterested = $this->actingAs($this->admin)->post(route('v2.tasks.quick_followup', $lead), [
            'communication_type' => 'call',
            'outcome' => 'Client explicitly refused',
            'lead_status_id' => $this->statusNotInterested->id,
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
            $totalCounts = array_sum(array_column($cards, 'count'));
            return count($cards) === 4
                && $codes === ['new', 'no_answer', 'not_interested', 'donor']
                && $totalCounts === 4;
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

    public function test_daily_tasks_receives_all_four_canonical_statuses(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.daily'));
        $response->assertOk();
        $response->assertViewHas('statuses', function ($statuses) {
            return $statuses->count() === 4 && $statuses->pluck('code')->all() === ['new', 'no_answer', 'not_interested', 'donor'];
        });
    }

    public function test_task_status_controller_renders_canonical_status_tabs_without_duplicates(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.tasks.status', ['status' => 'no-answer']));
        $response->assertOk();
        $response->assertViewHas('statusLinks', function ($links) {
            return count($links) === 4 && array_keys($links) === ['new', 'no_answer', 'not_interested', 'donor'];
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

        // 4 independent canonical stages appear as table rows
        $response->assertSee('جديد');
        $response->assertSee('لم يتم الرد');
        $response->assertSee('غير مهتم');
        $response->assertSee('متبرع');

        $stages = $response->viewData('stages');
        $this->assertCount(4, $stages);
        $this->assertSame(['new', 'no_answer', 'not_interested', 'donor'], $stages->pluck('code')->all());
    }

    public function test_sidebar_tasks_and_followups_displays_all_four_canonical_stages(): void
    {
        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $this->assertCount(4, $sidebarStages);
        $this->assertSame(['new', 'no_answer', 'not_interested', 'donor'], $sidebarStages->pluck('code')->all());
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
        $this->assertCount(4, $stages);

        $countsByCode = $stages->pluck('scoped_leads_count', 'code')->all();
        $this->assertEquals(2, $countsByCode['new']);
        $this->assertEquals(1, $countsByCode['no_answer']);
        $this->assertEquals(1, $countsByCode['not_interested']);
        $this->assertEquals(1, $countsByCode['donor']);

        $this->assertEquals(5, $response->viewData('totalLeads'));
    }
}

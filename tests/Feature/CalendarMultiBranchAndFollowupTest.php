<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CalendarMultiBranchAndFollowupTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;
    private Branch $branchB;
    private User $superAdmin;
    private User $agentBranchA;
    private User $agentBranchB;
    private PipelineStage $stage;
    private LeadStatus $status;
    private DonationPurpose $purpose;
    private DonationType $donationType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::query()->create([
            'name_ar' => 'فرع القاهرة',
            'name_en' => 'Cairo Branch',
            'code' => 'cairo',
            'is_active' => true,
        ]);

        $this->branchB = Branch::query()->create([
            'name_ar' => 'فرع الإسكندرية',
            'name_en' => 'Alexandria Branch',
            'code' => 'alex',
            'is_active' => true,
        ]);

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );

        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_cal',
            'is_active' => true,
            'branch_id' => $this->branchA->id,
        ]);
        $this->superAdmin->groups()->sync([$superAdminGroup->id]);

        $this->agentBranchA = $this->createAgentWithBranch($this->branchA);
        $this->agentBranchB = $this->createAgentWithBranch($this->branchB);

        $this->stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1]
        );

        $this->status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $this->stage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3b82f6',
            ]
        );

        $this->purpose = DonationPurpose::query()->create([
            'name_ar' => 'كفالة أيتام',
            'name_en' => 'Orphan Sponsorship',
            'is_active' => true,
        ]);

        $this->donationType = DonationType::query()->create([
            'name_ar' => 'شهري',
            'name_en' => 'Monthly',
            'is_active' => true,
        ]);
    }

    public function test_regular_agent_only_sees_calendar_events_from_assigned_branch(): void
    {
        $eventA = CalendarEvent::factory()->create([
            'branch_id' => $this->branchA->id,
            'user_id' => $this->agentBranchA->id,
            'title' => 'اجتماع فرع القاهرة',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
        ]);

        $eventB = CalendarEvent::factory()->create([
            'branch_id' => $this->branchB->id,
            'user_id' => $this->agentBranchB->id,
            'title' => 'اجتماع فرع الإسكندرية',
            'start_time' => '2026-09-01 12:00:00',
            'end_time' => '2026-09-01 13:00:00',
        ]);

        $responseA = $this->actingAs($this->agentBranchA)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-01 23:59:59',
            ]));

        $responseA->assertOk();
        $idsA = array_column($responseA->json('data'), 'id');
        $this->assertContains($eventA->id, $idsA);
        $this->assertNotContains($eventB->id, $idsA);

        $responseB = $this->actingAs($this->agentBranchB)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-01 23:59:59',
            ]));

        $responseB->assertOk();
        $idsB = array_column($responseB->json('data'), 'id');
        $this->assertContains($eventB->id, $idsB);
        $this->assertNotContains($eventA->id, $idsB);
    }

    public function test_super_admin_can_view_all_events_or_filter_by_branch(): void
    {
        $eventA = CalendarEvent::factory()->create([
            'branch_id' => $this->branchA->id,
            'user_id' => $this->agentBranchA->id,
            'title' => 'اجتماع فرع القاهرة',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
        ]);

        $eventB = CalendarEvent::factory()->create([
            'branch_id' => $this->branchB->id,
            'user_id' => $this->agentBranchB->id,
            'title' => 'اجتماع فرع الإسكندرية',
            'start_time' => '2026-09-01 12:00:00',
            'end_time' => '2026-09-01 13:00:00',
        ]);

        // All branches
        $responseAll = $this->actingAs($this->superAdmin)
            ->withSession([BranchContext::SESSION_KEY => 'all'])
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-01 23:59:59',
                'branch_id' => 'all',
            ]));

        $responseAll->assertOk();
        $idsAll = array_column($responseAll->json('data'), 'id');
        $this->assertContains($eventA->id, $idsAll);
        $this->assertContains($eventB->id, $idsAll);

        // Specific branch filter (Branch B)
        $responseFiltered = $this->actingAs($this->superAdmin)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-01 23:59:59',
                'branch_id' => $this->branchB->id,
            ]));

        $responseFiltered->assertOk();
        $idsFiltered = array_column($responseFiltered->json('data'), 'id');
        $this->assertContains($eventB->id, $idsFiltered);
        $this->assertNotContains($eventA->id, $idsFiltered);
    }

    public function test_lead_with_next_follow_up_at_appears_as_interactive_calendar_event(): void
    {
        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->status->id,
            'name' => 'الحاج أحمد المتبرع',
            'company_name' => 'مؤسسة البر',
            'phone' => '01011112222',
            'donation_purpose_id' => $this->purpose->id,
            'donation_type_id' => $this->donationType->id,
            'donation_value' => 5000.00,
            'donation_cycle' => 'شهري',
            'response_details' => 'يرغب في كفالة 5 أيتام بمبلغ 5000 شهريا',
            'next_follow_up_at' => Carbon::parse('2026-09-02 11:30:00'),
            'assigned_user_id' => $this->agentBranchA->id,
            'created_by_user_id' => $this->agentBranchA->id,
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01011112222',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->agentBranchA)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-03 23:59:59',
            ]));

        $response->assertOk();
        $data = $response->json('data');

        $leadEvents = array_filter($data, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id);
        $this->assertNotEmpty($leadEvents);

        $event = array_values($leadEvents)[0];
        $this->assertEquals('الحاج أحمد المتبرع', $event['lead_name']);
        $this->assertEquals('01011112222', $event['lead_phone']);
        $this->assertEquals('كفالة أيتام', $event['donation_target']);
        $this->assertEquals('5,000.00', $event['donation_value']);
        $this->assertEquals('شهري', $event['donation_type']);
        $this->assertEquals('فرع القاهرة', $event['branch_name']);
        $this->assertTrue($event['is_lead_followup']);
        $this->assertStringContainsString('/leads/' . $lead->id, $event['lead_url']);
    }

    public function test_lead_followup_can_be_rescheduled_via_calendar_reschedule_endpoint(): void
    {
        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->status->id,
            'name' => 'متبرع للتأجيل',
            'phone' => '01033334444',
            'next_follow_up_at' => Carbon::parse('2026-09-02 10:00:00'),
            'assigned_user_id' => $this->agentBranchA->id,
            'created_by_user_id' => $this->agentBranchA->id,
        ]);

        $newDate = '2026-09-05T14:00:00.000Z';

        $response = $this->actingAs($this->agentBranchA)
            ->patchJson(route('v2.calendar.lead.reschedule', $lead), [
                'start_time' => $newDate,
                'reason' => 'تأجيل الموعد بناء على طلب المتبرع',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $lead->refresh();
        $this->assertEquals('2026-09-05 14:00:00', $lead->next_follow_up_at->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas('lead_followups', [
            'lead_id' => $lead->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->agentBranchA->id,
        ]);
    }

    public function test_reminders_endpoint_aggregates_both_manual_events_and_lead_followups(): void
    {
        // Manual event in 10 minutes
        $manualEvent = CalendarEvent::factory()->create([
            'branch_id' => $this->branchA->id,
            'user_id' => $this->agentBranchA->id,
            'title' => 'مكالمة مهمة',
            'start_time' => now()->addMinutes(10),
            'end_time' => now()->addMinutes(30),
            'status' => 'scheduled',
        ]);

        // Lead follow-up in 20 minutes
        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->status->id,
            'name' => 'متبرع عاجل',
            'phone' => '01055556666',
            'next_follow_up_at' => now()->addMinutes(20),
            'assigned_user_id' => $this->agentBranchA->id,
            'created_by_user_id' => $this->agentBranchA->id,
        ]);

        $response = $this->actingAs($this->agentBranchA)
            ->getJson(route('v2.calendar.reminders', ['within_minutes' => 30]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2);

        $data = $response->json('data');
        $ids = array_column($data, 'id');

        $this->assertContains($manualEvent->id, $ids);
        $this->assertContains('lead_followup_' . $lead->id, $ids);
    }

    private function createAgentWithBranch(Branch $branch): User
    {
        $group = Group::query()->create([
            'name' => 'Agent Group ' . fake()->unique()->word(),
            'code' => 'agent-group-' . fake()->unique()->numerify('#####'),
        ]);

        $permissions = [
            'calendar.view',
            'calendar.manage',
            'leads.view',
            'leads.update',
            'leads.followups.view',
            'leads.followups.create',
        ];
        $permissionIds = Permission::query()->whereIn('code', $permissions)->pluck('id')->all();
        if (count($permissionIds) < count($permissions)) {
            $now = now();
            $permData = [];
            foreach ($permissions as $code) {
                $permData[] = [
                    'code' => $code,
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Permission::query()->insertOrIgnore($permData);
            $permissionIds = Permission::query()->whereIn('code', $permissions)->pluck('id')->all();
        }
        $group->permissions()->syncWithoutDetaching($permissionIds);

        $user = User::factory()->create([
            'is_active' => true,
            'branch_id' => $branch->id,
        ]);
        $user->groups()->attach($group);

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\CollectionCase;
use App\Models\Donation;
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
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiBranchIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;
    private Branch $branchB;
    private User $superAdmin;
    private User $userBranchA;
    private User $userBranchB;
    private PipelineStage $stage;
    private LeadStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::query()->firstOrCreate(
            ['code' => 'branch_a_iso'],
            ['name_ar' => 'فرع القاهرة أ', 'name_en' => 'Branch Cairo A', 'is_active' => true],
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_b_iso'],
            ['name_ar' => 'فرع الإسكندرية ب', 'name_en' => 'Branch Alex B', 'is_active' => true],
        );

        $superGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Admin Group', 'is_system' => true],
        );

        $this->superAdmin = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branchA->id,
            'locale' => 'en',
        ]);
        $this->superAdmin->groups()->attach($superGroup);

        $this->userBranchA = $this->createActorWithPermissions([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'leads.export', 'leads.import',
            'tasks.view', 'campaigns.view', 'campaigns.create', 'campaigns.reports',
            'calendar.view', 'calendar.manage', 'collections.view', 'collections.manage',
            'collections.assign', 'collections.collect', 'collections.complete', 'collections.cancel',
            'voip.view', 'reports.view',
        ], $this->branchA);

        $this->userBranchB = $this->createActorWithPermissions([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'leads.export', 'leads.import',
            'tasks.view', 'campaigns.view', 'campaigns.create', 'campaigns.reports',
            'calendar.view', 'calendar.manage', 'collections.view', 'collections.manage',
            'collections.assign', 'collections.collect', 'collections.complete', 'collections.cancel',
            'voip.view', 'reports.view',
        ], $this->branchB);

        $pos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stage = PipelineStage::query()->create([
            'code' => 'stage_iso_' . uniqid(),
            'name_ar' => 'مرحلة العزل',
            'position' => $pos,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        $stPos = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->status = LeadStatus::query()->create([
            'code' => 'status_iso_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة العزل',
            'position' => $stPos,
            'is_terminal' => false,
        ]);
    }

    public function test_1_leads_index_and_show_enforces_strict_branch_isolation(): void
    {
        $leadA = $this->createLead($this->userBranchA, $this->status, $this->branchA, 'عميل فرع أ السري');
        $leadB = $this->createLead($this->userBranchB, $this->status, $this->branchB, 'عميل فرع ب السري');

        // User A sees Lead A in index
        $resA = $this->actingAs($this->userBranchA)->get(route('v2.leads'));
        $resA->assertOk();
        $resA->assertSee('عميل فرع أ السري');
        $resA->assertDontSee('عميل فرع ب السري');

        // User A cannot view Lead B details -> 403 Forbidden
        $this->actingAs($this->userBranchA)->get(route('v2.leads.show', $leadB))->assertForbidden();

        // User B sees Lead B in index
        $resB = $this->actingAs($this->userBranchB)->get(route('v2.leads'));
        $resB->assertOk();
        $resB->assertSee('عميل فرع ب السري');
        $resB->assertDontSee('عميل فرع أ السري');

        // User B cannot view Lead A details -> 403 Forbidden
        $this->actingAs($this->userBranchB)->get(route('v2.leads.show', $leadA))->assertForbidden();
    }

    public function test_2_lead_mutations_and_followups_forbidden_across_branches(): void
    {
        $leadB = $this->createLead($this->userBranchB, $this->status, $this->branchB, 'عميل فرع ب محمي');

        // Edit form
        $this->actingAs($this->userBranchA)->get(route('v2.leads.edit', $leadB))->assertForbidden();

        // Update attempt
        $this->actingAs($this->userBranchA)->patch(route('v2.leads.update', $leadB), [
            'name' => 'اسم معدل خبيث',
        ])->assertForbidden();
        $this->assertSame('عميل فرع ب محمي', $leadB->fresh()->name);

        // Delete attempt
        $this->actingAs($this->userBranchA)->delete(route('v2.leads.destroy', $leadB))->assertForbidden();
        $this->assertNotNull($leadB->fresh());

        // Followup view attempt
        $this->actingAs($this->userBranchA)->get(route('v2.leads.followups.index', $leadB))->assertForbidden();

        // Followup store attempt
        $this->actingAs($this->userBranchA)->post(route('v2.leads.followups.store', $leadB), [
            'lead_status_id' => $this->status->id,
            'communication_type' => 'call',
            'outcome' => 'محاولة غير مصرحة',
        ])->assertForbidden();
    }

    public function test_3_kanban_cards_and_counts_strictly_isolated_by_branch(): void
    {
        $this->createLead($this->userBranchA, $this->status, $this->branchA, 'كانبان أ 1');
        $this->createLead($this->userBranchA, $this->status, $this->branchA, 'كانبان أ 2');
        $this->createLead($this->userBranchB, $this->status, $this->branchB, 'كانبان ب سري');

        $responseA = $this->actingAs($this->userBranchA)->get(route('v2.leads.kanban'));
        $responseA->assertOk();
        $responseA->assertSee('كانبان أ 1');
        $responseA->assertSee('كانبان أ 2');
        $responseA->assertDontSee('كانبان ب سري');

        $responseB = $this->actingAs($this->userBranchB)->get(route('v2.leads.kanban'));
        $responseB->assertOk();
        $responseB->assertSee('كانبان ب سري');
        $responseB->assertDontSee('كانبان أ 1');
        $responseB->assertDontSee('كانبان أ 2');
    }

    public function test_4_dashboard_metrics_do_not_leak_cross_branch_totals(): void
    {
        // Branch A: 2 leads
        $this->createLead($this->userBranchA, $this->status, $this->branchA, 'لوحة تحكم أ 1');
        $this->createLead($this->userBranchA, $this->status, $this->branchA, 'لوحة تحكم أ 2');

        // Branch B: 3 leads
        $this->createLead($this->userBranchB, $this->status, $this->branchB, 'لوحة تحكم ب 1');
        $this->createLead($this->userBranchB, $this->status, $this->branchB, 'لوحة تحكم ب 2');
        $this->createLead($this->userBranchB, $this->status, $this->branchB, 'لوحة تحكم ب 3');

        // User A dashboard MUST count only 2 for this stage
        $responseA = $this->actingAs($this->userBranchA)->get(route('dashboard'));
        $responseA->assertOk();
        $stageA = $responseA->viewData('pipelineStages')->firstWhere('id', $this->stage->id);
        $this->assertSame(2, $stageA?->leads_count);

        // User B dashboard MUST count only 3 for this stage
        $responseB = $this->actingAs($this->userBranchB)->get(route('dashboard'));
        $responseB->assertOk();
        $stageB = $responseB->viewData('pipelineStages')->firstWhere('id', $this->stage->id);
        $this->assertSame(3, $stageB?->leads_count);

        // Super Admin legitimately sees global total of 5
        $responseSuper = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $responseSuper->assertOk();
        $stageSuper = $responseSuper->viewData('pipelineStages')->firstWhere('id', $this->stage->id);
        $this->assertSame(5, $stageSuper?->leads_count);
    }

    public function test_5_campaign_isolation_prevents_foreign_leads_and_employee_tampering(): void
    {
        $campaignA = Campaign::query()->create([
            'branch_id' => $this->branchA->id,
            'name' => 'حملة فرع أ',
            'cost' => 5000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'created_by_user_id' => $this->userBranchA->id,
        ]);
        $campaignA->users()->attach($this->userBranchA->id);

        $campaignB = Campaign::query()->create([
            'branch_id' => $this->branchB->id,
            'name' => 'حملة فرع ب',
            'cost' => 8000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'created_by_user_id' => $this->userBranchB->id,
        ]);
        $campaignB->users()->attach($this->userBranchB->id);

        // User A cannot view or edit Campaign B
        $this->actingAs($this->userBranchA)->get(route('v2.campaigns.show', $campaignB))->assertForbidden();
        $this->actingAs($this->userBranchA)->get(route('v2.campaigns.edit', $campaignB))->assertForbidden();
        $this->actingAs($this->userBranchA)->delete(route('v2.campaigns.destroy', $campaignB))->assertForbidden();

        // User A cannot assign leads to User B (from another branch)
        $leadA = $this->createLead($this->userBranchA, $this->status, $this->branchA, 'عميل حملة أ');
        $campaignA->leads()->attach($leadA->id);

        $this->actingAs($this->userBranchA)->patch(route('v2.campaigns.leads.assign', $campaignA), [
            'lead_ids' => [$leadA->id],
            'target_user_id' => $this->userBranchB->id,
        ])->assertSessionHasErrors('target_user_id');
    }

    public function test_6_calendar_and_tasks_strictly_isolated_by_branch(): void
    {
        $leadA = $this->createLead($this->userBranchA, $this->status, $this->branchA, 'عميل تقويم أ');
        $leadB = $this->createLead($this->userBranchB, $this->status, $this->branchB, 'عميل تقويم ب');

        $eventA = CalendarEvent::query()->create([
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userBranchA->id,
            'lead_id' => $leadA->id,
            'title' => 'موعد فرع أ',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $eventB = CalendarEvent::query()->create([
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userBranchB->id,
            'lead_id' => $leadB->id,
            'title' => 'موعد فرع ب',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        // User A cannot view event B
        $this->actingAs($this->userBranchA)->getJson(route('v2.calendar.show', $eventB))->assertForbidden();

        // User A cannot update or delete event B
        $this->actingAs($this->userBranchA)->patchJson(route('v2.calendar.update', $eventB), [
            'title' => 'تعديل غير مصرح',
        ])->assertForbidden();
        $this->actingAs($this->userBranchA)->deleteJson(route('v2.calendar.destroy', $eventB))->assertForbidden();

        // Events list only returns Branch A events for User A
        $response = $this->actingAs($this->userBranchA)->getJson(route('v2.calendar.events'));
        $response->assertOk();
        $eventIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($eventA->id, $eventIds);
        $this->assertNotContains($eventB->id, $eventIds);
    }

    public function test_7_collections_and_donations_strictly_isolated_by_branch(): void
    {
        $leadB = $this->createLead($this->userBranchB, $this->status, $this->branchB, 'متبرع فرع ب');

        $caseB = CollectionCase::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_id' => $leadB->id,
            'assigned_collector_user_id' => $this->userBranchB->id,
            'created_by_user_id' => $this->userBranchB->id,
            'status' => 'pending',
            'donation_type' => 'cash',
            'expected_amount' => 1000.00,
            'cycle' => 'once',
            'collection_address' => 'عنوان الإسكندرية',
            'due_at' => now()->addHours(3),
        ]);

        // User A cannot view Case B
        $this->actingAs($this->userBranchA)->get(route('v2.collections.show', $caseB))->assertForbidden();

        // User A cannot assign, complete, or cancel Case B
        $this->actingAs($this->userBranchA)->patch(route('v2.collections.assign', $caseB), [
            'assigned_collector_user_id' => $this->userBranchA->id,
        ])->assertForbidden();

        $this->actingAs($this->userBranchA)->post(route('v2.collections.complete', $caseB), [
            'collected_amount' => 1000,
        ])->assertForbidden();

        $this->actingAs($this->userBranchA)->patch(route('v2.collections.cancel', $caseB), [
            'notes' => 'إلغاء غير مصرح',
        ])->assertForbidden();

        $this->assertSame('pending', $caseB->fresh()->status);
    }

    public function test_8_voip_call_history_requires_accessible_lead(): void
    {
        $leadA = $this->createLead($this->userBranchA, $this->status, $this->branchA, 'عميل اتصالات أ', '01011112222');
        $leadB = $this->createLead($this->userBranchB, $this->status, $this->branchB, 'عميل اتصالات ب', '01033334444');

        // User A with voip.view can access calls of Lead A
        $this->actingAs($this->userBranchA)->getJson(route('v2.leads.calls', $leadA))->assertOk();

        // User A with voip.view CANNOT access calls of foreign Lead B -> 403 Forbidden
        $this->actingAs($this->userBranchA)->getJson(route('v2.leads.calls', $leadB))->assertForbidden();
    }

    public function test_9_search_and_filter_never_leak_foreign_branch_records(): void
    {
        $this->createLead($this->userBranchA, $this->status, $this->branchA, 'عميل القاهرة العادي', '01011119999');
        $this->createLead($this->userBranchB, $this->status, $this->branchB, 'BRANCH_B_SECRET_LEAD', '01099998888');

        // Search by secret name as Branch A user
        $response = $this->actingAs($this->userBranchA)->get(route('v2.leads', ['q' => 'BRANCH_B_SECRET_LEAD']));
        $response->assertOk();
        $leadsInView = $response->viewData('leads');
        $this->assertCount(0, $leadsInView);
        $this->assertFalse(collect($leadsInView->items())->contains('name', 'BRANCH_B_SECRET_LEAD'));

        // Search by secret phone as Branch A user
        $response2 = $this->actingAs($this->userBranchA)->get(route('v2.leads', ['q' => '01099998888']));
        $response2->assertOk();
        $leadsInView2 = $response2->viewData('leads');
        $this->assertCount(0, $leadsInView2);
        $this->assertFalse(collect($leadsInView2->items())->contains('phone', '01099998888'));
    }

    private function createLead(User $user, LeadStatus $status, Branch $branch, string $name, string $phone = '01000000000'): Lead
    {
        $lead = Lead::query()->create([
            'branch_id' => $branch->id,
            'lead_status_id' => $status->getKey(),
            'name' => $name,
            'phone' => $phone,
            'assigned_user_id' => $user->getKey(),
            'created_by_user_id' => $user->getKey(),
            'next_follow_up_at' => now()->addDay(),
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => $phone,
            'is_primary' => true,
        ]);

        return $lead;
    }

    /** @param list<string> $permissionCodes */
    private function createActorWithPermissions(array $permissionCodes, Branch $branch): User
    {
        $group = Group::query()->create([
            'name' => 'Actor Group ' . fake()->unique()->word(),
            'code' => 'actor-' . fake()->unique()->slug(),
            'is_system' => false,
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->where('code', $code)->first()
                ?? Permission::query()->create([
                    'code' => $code,
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ]);
            $group->permissions()->syncWithoutDetaching($permission);
        }

        $user = User::factory()->create([
            'is_active' => true,
            'branch_id' => $branch->id,
            'timezone' => 'UTC',
            'locale' => 'ar',
        ]);
        $user->groups()->attach($group);

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Database\Seeders\CrmAccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationIsolationRegressionTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;
    private Branch $branchB;

    private Group $groupSuperAdmin;
    private Group $groupBranchAdmin;
    private Group $groupManager;
    private Group $groupCollector;
    private Group $groupEmployee;

    private User $userSuperAdmin;
    private User $userBranchAdminA;
    private User $userBranchAdminB;
    private User $userManagerA;
    private User $userManagerB;
    private User $userCollectorA;
    private User $userCollectorB;
    private User $userEmployeeA;
    private User $userEmployeeB;

    private PipelineStage $stageA;
    private LeadStatus $statusA;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.database' => true,
            'crm_notifications.channels.push' => false,
            'crm_notifications.channels.mail' => false,
            'crm_notifications.channels.sms' => false,
            'crm_notifications.channels.whatsapp' => false,
        ]);

        $this->seed(CrmAccessControlSeeder::class);

        $this->branchA = Branch::query()->firstOrCreate(
            ['code' => 'branch_iso_a'],
            ['name_ar' => 'فرع القاهرة المعزول', 'is_active' => true]
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_iso_b'],
            ['name_ar' => 'فرع الإسكندرية المعزول', 'is_active' => true]
        );

        $this->groupSuperAdmin = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();
        $this->groupBranchAdmin = Group::query()->where('code', Group::BRANCH_ADMIN_CODE)->firstOrFail();
        $this->groupManager = Group::query()->where('code', Group::MANAGER_CODE)->firstOrFail();
        $this->groupCollector = Group::query()->where('code', Group::COLLECTOR_CODE)->firstOrFail();
        $this->groupEmployee = Group::query()->where('code', Group::EMPLOYEE_CODE)->firstOrFail();

        $this->userSuperAdmin = User::factory()->create([
            'username' => 'iso_superadmin',
            'name' => 'سوبر أدمن المنظومة',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userSuperAdmin->groups()->sync([$this->groupSuperAdmin->id]);

        $this->userBranchAdminA = User::factory()->create([
            'username' => 'iso_branch_admin_a',
            'name' => 'أدمن فرع أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userBranchAdminA->groups()->sync([$this->groupBranchAdmin->id]);

        $this->userBranchAdminB = User::factory()->create([
            'username' => 'iso_branch_admin_b',
            'name' => 'أدمن فرع ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userBranchAdminB->groups()->sync([$this->groupBranchAdmin->id]);

        $this->userManagerA = User::factory()->create([
            'username' => 'iso_manager_a',
            'name' => 'مدير فرع أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userManagerA->groups()->sync([$this->groupManager->id]);

        $this->userManagerB = User::factory()->create([
            'username' => 'iso_manager_b',
            'name' => 'مدير فرع ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userManagerB->groups()->sync([$this->groupManager->id]);

        $this->userCollectorA = User::factory()->create([
            'username' => 'iso_collector_a',
            'name' => 'محصل فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userCollectorA->groups()->sync([$this->groupCollector->id]);

        $this->userCollectorB = User::factory()->create([
            'username' => 'iso_collector_b',
            'name' => 'محصل فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userCollectorB->groups()->sync([$this->groupCollector->id]);

        $this->userEmployeeA = User::factory()->create([
            'username' => 'iso_employee_a',
            'name' => 'موظف فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userEmployeeA->groups()->sync([$this->groupEmployee->id]);

        $this->userEmployeeB = User::factory()->create([
            'username' => 'iso_employee_b',
            'name' => 'موظف فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userEmployeeB->groups()->sync([$this->groupEmployee->id]);

        $existingStage = PipelineStage::query()->where('code', 'iso_stage')->first();
        $nextStagePos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stageA = $existingStage ?? PipelineStage::query()->create([
            'code' => 'iso_stage',
            'name_ar' => 'مرحلة المتابعة العادية',
            'name_en' => 'Standard Followup Stage',
            'position' => $nextStagePos,
            'color' => '#10b981',
            'icon' => 'bi-flag',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $existingStatus = LeadStatus::query()->where('code', 'iso_status')->first();
        $nextStatusPos = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->statusA = $existingStatus ?? LeadStatus::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'code' => 'iso_status',
            'name_ar' => 'قيد المتابعة العادية',
            'position' => $nextStatusPos,
            'color' => '#10b981',
            'is_terminal' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_1_employee_receives_assigned_followup_and_does_not_receive_foreign_followup(): void
    {
        $now = Carbon::parse('2026-08-29 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Lead in Branch A assigned to Employee A
        $leadA = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع أ الخاص',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        // Lead in Branch B assigned to Employee B
        $leadB = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع ب الخاص',
            'assigned_user_id' => $this->userEmployeeB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Employee A: sees leadA, does NOT see leadB
        $resA = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.index'));
        $resA->assertOk();
        $itemsA = collect($resA->json('data'));
        $this->assertTrue($itemsA->contains('source_id', $leadA->id));
        $this->assertFalse($itemsA->contains('source_id', $leadB->id));

        // Employee B: sees leadB, does NOT see leadA
        $resB = $this->actingAs($this->userEmployeeB)->getJson(route('v2.notifications.index'));
        $resB->assertOk();
        $itemsB = collect($resB->json('data'));
        $this->assertTrue($itemsB->contains('source_id', $leadB->id));
        $this->assertFalse($itemsB->contains('source_id', $leadA->id));
    }

    public function test_2_collector_receives_assigned_collection_case_and_does_not_receive_another_collectors_case(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $leadA = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'متبرع تحصيل أ',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
        ]);

        $leadB = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'متبرع تحصيل ب',
            'assigned_user_id' => $this->userEmployeeB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
        ]);

        $caseA = CollectionCase::query()->create([
            'lead_id' => $leadA->id,
            'branch_id' => $this->branchA->id,
            'donation_type' => 'cash',
            'expected_amount' => 500.00,
            'collection_address' => 'عنوان القاهرة',
            'assigned_collector_user_id' => $this->userCollectorA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'status' => 'pending',
            'due_at' => $now->copy()->addMinutes(10),
        ]);

        $caseB = CollectionCase::query()->create([
            'lead_id' => $leadB->id,
            'branch_id' => $this->branchB->id,
            'donation_type' => 'cash',
            'expected_amount' => 1200.00,
            'collection_address' => 'عنوان الإسكندرية',
            'assigned_collector_user_id' => $this->userCollectorB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
            'status' => 'pending',
            'due_at' => $now->copy()->addMinutes(10),
        ]);

        $rule = NotificationRule::query()->firstOrCreate(
            ['event_key' => NotificationRule::EVENT_COLLECTION_DUE],
            [
                'name_ar' => 'موعد تحصيل قريب',
                'name_en' => 'Collection due soon',
                'trigger_offset_minutes' => 15,
                'priority' => 'important',
                'enabled' => true,
            ]
        );
        $rule->channels()->firstOrCreate(['channel' => 'database']);
        $rule->recipients()->firstOrCreate(['recipient_type' => 'assigned_user']);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Collector A: receives caseA with lead stage, does NOT see caseB
        $resA = $this->actingAs($this->userCollectorA)->getJson(route('v2.notifications.index'));
        $resA->assertOk();
        $itemsA = collect($resA->json('data'));
        $this->assertTrue($itemsA->contains('source_id', $caseA->id));
        $this->assertFalse($itemsA->contains('source_id', $caseB->id));

        $itemA = $itemsA->firstWhere('source_id', $caseA->id);
        $this->assertSame($leadA->id, $itemA['lead_id']);
        $this->assertSame('مرحلة المتابعة العادية', $itemA['stage_name']);

        // Collector B: receives caseB, does NOT see caseA
        $resB = $this->actingAs($this->userCollectorB)->getJson(route('v2.notifications.index'));
        $resB->assertOk();
        $itemsB = collect($resB->json('data'));
        $this->assertTrue($itemsB->contains('source_id', $caseB->id));
        $this->assertFalse($itemsB->contains('source_id', $caseA->id));
    }

    public function test_3_manager_receives_managed_team_notification_and_does_not_receive_foreign_branch(): void
    {
        $now = Carbon::parse('2026-08-29 12:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Lead A in Manager A team
        $leadA = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فريق القاهرة',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        // Lead B in Branch B
        $leadB = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فريق الإسكندرية',
            'assigned_user_id' => $this->userEmployeeB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $rule = NotificationRule::query()->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)->firstOrFail();
        $rule->recipients()->firstOrCreate([
            'recipient_type' => 'group',
            'recipient_id' => $this->groupManager->id,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Manager A: receives leadA, does NOT receive leadB
        $resA = $this->actingAs($this->userManagerA)->getJson(route('v2.notifications.index'));
        $resA->assertOk();
        $itemsA = collect($resA->json('data'));
        $this->assertTrue($itemsA->contains('source_id', $leadA->id));
        $this->assertFalse($itemsA->contains('source_id', $leadB->id));
    }

    public function test_4_branch_admin_receives_intended_branch_and_does_not_receive_other_branch(): void
    {
        $now = Carbon::parse('2026-08-29 13:00:00', 'UTC');
        Carbon::setTestNow($now);

        $leadA = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع أ الحصري',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $leadB = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع ب الحصري',
            'assigned_user_id' => $this->userEmployeeB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $rule = NotificationRule::query()->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)->firstOrFail();
        $rule->recipients()->firstOrCreate([
            'recipient_type' => 'group',
            'recipient_id' => $this->groupBranchAdmin->id,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Branch Admin A: sees leadA, does NOT see leadB
        $resA = $this->actingAs($this->userBranchAdminA)->getJson(route('v2.notifications.index'));
        $resA->assertOk();
        $itemsA = collect($resA->json('data'));
        $this->assertTrue($itemsA->contains('source_id', $leadA->id));
        $this->assertFalse($itemsA->contains('source_id', $leadB->id));

        // Branch Admin B: sees leadB, does NOT see leadA
        $resB = $this->actingAs($this->userBranchAdminB)->getJson(route('v2.notifications.index'));
        $resB->assertOk();
        $itemsB = collect($resB->json('data'));
        $this->assertTrue($itemsB->contains('source_id', $leadB->id));
        $this->assertFalse($itemsB->contains('source_id', $leadA->id));
    }

    public function test_5_occurrence_deduplication_and_unread_isolation(): void
    {
        $now = Carbon::parse('2026-08-29 14:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص التكرار',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        // Run planner multiple times for the exact same minute
        $firstPlan = $planner->planScheduled($now);
        $secondPlan = $planner->planScheduled($now);

        $this->assertGreaterThanOrEqual(1, $firstPlan);
        $this->assertSame(0, $secondPlan); // Deduplicated!

        $dispatcher->dispatchDueOccurrences($now);

        // Employee A unread count is 1
        $countA = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.unread-count'));
        $countA->assertOk();
        $this->assertSame(1, $countA->json('count'));

        // Employee B unread count is 0 (isolated!)
        $countB = $this->actingAs($this->userEmployeeB)->getJson(route('v2.notifications.unread-count'));
        $countB->assertOk();
        $this->assertSame(0, $countB->json('count'));
    }
}

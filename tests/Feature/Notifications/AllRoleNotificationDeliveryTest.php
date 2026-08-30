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

class AllRoleNotificationDeliveryTest extends TestCase
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
            ['code' => 'branch_test_a'],
            ['name_ar' => 'فرع القاهرة التجريبي', 'is_active' => true]
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_test_b'],
            ['name_ar' => 'فرع الإسكندرية التجريبي', 'is_active' => true]
        );

        $this->groupSuperAdmin = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();
        $this->groupBranchAdmin = Group::query()->where('code', Group::BRANCH_ADMIN_CODE)->firstOrFail();
        $this->groupManager = Group::query()->where('code', Group::MANAGER_CODE)->firstOrFail();
        $this->groupCollector = Group::query()->where('code', Group::COLLECTOR_CODE)->firstOrFail();
        $this->groupEmployee = Group::query()->where('code', Group::EMPLOYEE_CODE)->firstOrFail();

        $this->userSuperAdmin = User::factory()->create([
            'username' => 'test_suite_superadmin',
            'name' => 'سوبر أدمن المنظومة',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userSuperAdmin->groups()->sync([$this->groupSuperAdmin->id]);

        $this->userBranchAdminA = User::factory()->create([
            'username' => 'test_suite_branch_admin_a',
            'name' => 'أدمن فرع أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userBranchAdminA->groups()->sync([$this->groupBranchAdmin->id]);

        $this->userBranchAdminB = User::factory()->create([
            'username' => 'test_suite_branch_admin_b',
            'name' => 'أدمن فرع ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userBranchAdminB->groups()->sync([$this->groupBranchAdmin->id]);

        $this->userManagerA = User::factory()->create([
            'username' => 'test_suite_manager_a',
            'name' => 'مدير فريق أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userManagerA->groups()->sync([$this->groupManager->id]);

        $this->userManagerB = User::factory()->create([
            'username' => 'test_suite_manager_b',
            'name' => 'مدير فريق ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userManagerB->groups()->sync([$this->groupManager->id]);

        $this->userCollectorA = User::factory()->create([
            'username' => 'test_suite_collector_a',
            'name' => 'محصل فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userCollectorA->groups()->sync([$this->groupCollector->id]);

        $this->userCollectorB = User::factory()->create([
            'username' => 'test_suite_collector_b',
            'name' => 'محصل فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userCollectorB->groups()->sync([$this->groupCollector->id]);

        $this->userEmployeeA = User::factory()->create([
            'username' => 'test_suite_employee_a',
            'name' => 'موظف فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userEmployeeA->groups()->sync([$this->groupEmployee->id]);

        $this->userEmployeeB = User::factory()->create([
            'username' => 'test_suite_employee_b',
            'name' => 'موظف فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
            'locale' => 'ar',
        ]);
        $this->userEmployeeB->groups()->sync([$this->groupEmployee->id]);

        $existingStage = PipelineStage::query()->where('code', 'test_negotiation')->first();
        $nextStagePos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stageA = $existingStage ?? PipelineStage::query()->create([
            'code' => 'test_negotiation',
            'name_ar' => 'مرحلة التفاوض',
            'name_en' => 'Negotiation Stage',
            'position' => $nextStagePos,
            'color' => '#f59e0b',
            'icon' => 'bi-chat-dots',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $existingStatus = LeadStatus::query()->where('code', 'test_in_negotiation')->first();
        $nextStatusPos = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->statusA = $existingStatus ?? LeadStatus::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'code' => 'test_in_negotiation',
            'name_ar' => 'قيد التفاوض',
            'position' => $nextStatusPos,
            'color' => '#f59e0b',
            'is_terminal' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_1_employee_receives_assigned_followup_with_pipeline_badge(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل موظف أ',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planned = $planner->planScheduled($now);
        $this->assertGreaterThanOrEqual(1, $planned);

        $dispatched = $dispatcher->dispatchDueOccurrences($now);
        $this->assertGreaterThanOrEqual(1, $dispatched);

        $response = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('عميل موظف أ', $item['lead_name']);
        $this->assertSame('مرحلة التفاوض', $item['stage_name']);
        $this->assertSame('#f59e0b', $item['stage_color']);
        $this->assertSame('bi-chat-dots', $item['stage_icon']);
    }

    public function test_2_collector_receives_assigned_collection_case_with_lead_stage_metadata(): void
    {
        $now = Carbon::parse('2026-08-29 12:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'متبرع التحصيل',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
        ]);

        $case = CollectionCase::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $this->branchA->id,
            'donation_type' => 'cash',
            'expected_amount' => 750.00,
            'collection_address' => 'القاهرة، مدينة نصر',
            'assigned_collector_user_id' => $this->userCollectorA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
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

        $response = $this->actingAs($this->userCollectorA)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $case->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('متبرع التحصيل', $item['lead_name']);
        $this->assertSame('مرحلة التفاوض', $item['stage_name']);
        $this->assertSame('#f59e0b', $item['stage_color']);
        $this->assertSame('bi-chat-dots', $item['stage_icon']);
    }

    public function test_3_employee_receives_assigned_task_due_notification(): void
    {
        $now = Carbon::parse('2026-08-29 13:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل المهمة',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
        ]);

        $task = CalendarEvent::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userEmployeeA->id,
            'title' => 'مهمة متابعة خاصة',
            'type' => 'task',
            'status' => 'scheduled',
            'start_time' => $now->copy()->addMinutes(10),
            'end_time' => $now->copy()->addMinutes(40),
            'reminder_minutes_before' => 15,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $task->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('عميل المهمة', $item['lead_name']);
    }

    public function test_4_manager_receives_group_notification_for_subordinate_lead(): void
    {
        $now = Carbon::parse('2026-08-29 14:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل الفريق أ',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        // Add Manager group as recipient to followup rule
        $rule = NotificationRule::query()->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)->firstOrFail();
        $rule->recipients()->firstOrCreate([
            'recipient_type' => 'group',
            'recipient_id' => $this->groupManager->id,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($this->userManagerA)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
    }

    public function test_5_branch_admin_receives_branch_notification(): void
    {
        $now = Carbon::parse('2026-08-29 15:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع أ العام',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
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

        $response = $this->actingAs($this->userBranchAdminA)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $lead->id);
        $this->assertNotNull($item);
    }

    public function test_6_super_admin_receives_notifications_across_different_branches(): void
    {
        $now = Carbon::parse('2026-08-29 16:00:00', 'UTC');
        Carbon::setTestNow($now);

        $leadBranchB = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع الإسكندرية',
            'assigned_user_id' => $this->userSuperAdmin->id,
            'created_by_user_id' => $this->userSuperAdmin->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Super Admin viewing notifications
        $response = $this->actingAs($this->userSuperAdmin)->getJson(route('v2.notifications.index'));
        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('source_id', $leadBranchB->id);
        $this->assertNotNull($item);
        $this->assertSame($leadBranchB->id, $item['lead_id']);
        $this->assertSame('عميل فرع الإسكندرية', $item['lead_name']);
    }

    public function test_7_unread_count_and_stream_endpoints(): void
    {
        $now = Carbon::parse('2026-08-29 17:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص العداد',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Check unread count
        $countResponse = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.unread-count'));
        $countResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, $countResponse->json('count'));

        // Check stream response
        $streamResponse = $this->actingAs($this->userEmployeeA)->get(route('v2.notifications.stream'));
        $streamResponse->assertOk();
        $this->assertStringContainsString('event: notifications', $streamResponse->streamedContent());
    }

    public function test_8_mark_read_and_read_all_operations(): void
    {
        $now = Carbon::parse('2026-08-29 18:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل القراءة',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $list = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.index', ['filter' => 'unread']));
        $list->assertOk();
        $notificationId = $list->json('data.0.id');
        $this->assertNotNull($notificationId);

        // Mark single as read
        $readRes = $this->actingAs($this->userEmployeeA)->patchJson(route('v2.notifications.read', ['notification' => $notificationId]));
        $readRes->assertOk();

        // Mark all read
        $readAllRes = $this->actingAs($this->userEmployeeA)->patchJson(route('v2.notifications.read-all'));
        $readAllRes->assertOk();

        $unreadAfter = $this->actingAs($this->userEmployeeA)->getJson(route('v2.notifications.unread-count'));
        $this->assertSame(0, $unreadAfter->json('count'));
    }

    public function test_9_missing_preference_row_defaults_to_in_app_enabled(): void
    {
        $now = Carbon::parse('2026-08-29 19:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Ensure user has no preferences row
        NotificationPreference::query()->where('user_id', $this->userEmployeeB->id)->delete();
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $this->userEmployeeB->id]);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل بدون تفضيلات مسبقة',
            'assigned_user_id' => $this->userEmployeeB->id,
            'created_by_user_id' => $this->userEmployeeB->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Must create database delivery with delivered status
        $occurrence = NotificationOccurrence::query()
            ->where('recipient_user_id', $this->userEmployeeB->id)
            ->where('source_id', $lead->id)
            ->first();
        $this->assertNotNull($occurrence);
        $this->assertSame(NotificationOccurrence::STATUS_DISPATCHED, $occurrence->status);

        $delivery = NotificationDelivery::query()
            ->where('notification_occurrence_id', $occurrence->id)
            ->where('channel', 'database')
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(NotificationDelivery::STATUS_DELIVERED, $delivery->status);
    }

    public function test_10_disabled_in_app_preference_suppresses_database_delivery(): void
    {
        $now = Carbon::parse('2026-08-29 20:00:00', 'UTC');
        Carbon::setTestNow($now);

        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $this->userEmployeeA->id],
            ['in_app_enabled' => false]
        );

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل معطل الإشعارات',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $occurrence = NotificationOccurrence::query()
            ->where('recipient_user_id', $this->userEmployeeA->id)
            ->where('source_id', $lead->id)
            ->first();
        $this->assertNotNull($occurrence);

        $delivery = NotificationDelivery::query()
            ->where('notification_occurrence_id', $occurrence->id)
            ->where('channel', 'database')
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->status);
    }

    public function test_11_inactive_user_is_suppressed_from_receiving_notifications(): void
    {
        $now = Carbon::parse('2026-08-29 21:00:00', 'UTC');
        Carbon::setTestNow($now);

        $this->userEmployeeA->update(['is_active' => false]);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل موظف معطل',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
            'next_follow_up_at' => $now->copy()->addMinutes(10),
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $created = $planner->planScheduled($now);
        $this->assertSame(0, $created);
    }

    public function test_12_completed_task_cancels_outstanding_occurrences(): void
    {
        $now = Carbon::parse('2026-08-29 22:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = Lead::query()->create([
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل المهمة المكتملة',
            'assigned_user_id' => $this->userEmployeeA->id,
            'created_by_user_id' => $this->userEmployeeA->id,
        ]);

        $task = CalendarEvent::query()->create([
            'lead_id' => $lead->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userEmployeeA->id,
            'title' => 'مهمة سيتم إكمالها',
            'type' => 'task',
            'status' => 'scheduled',
            'start_time' => $now->copy()->addMinutes(10),
            'end_time' => $now->copy()->addMinutes(40),
            'reminder_minutes_before' => 15,
        ]);

        $planner = app(ReminderPlanner::class);
        $planner->planScheduled($now);

        $this->assertDatabaseHas('notification_occurrences', [
            'source_kind' => 'calendar_event',
            'source_id' => $task->id,
            'status' => NotificationOccurrence::STATUS_PENDING,
        ]);

        // Complete the task
        $task->update(['status' => 'completed']);

        $this->assertDatabaseHas('notification_occurrences', [
            'source_kind' => 'calendar_event',
            'source_id' => $task->id,
            'status' => NotificationOccurrence::STATUS_CANCELED,
        ]);
    }
}

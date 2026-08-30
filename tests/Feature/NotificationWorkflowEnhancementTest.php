<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\NotificationOccurrence;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationWorkflowEnhancementTest extends TestCase
{
    use DatabaseTransactions;

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_1_followup_due_today_produces_notification_output(): void
    {
        $now = Carbon::parse('2026-08-29 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branch = $this->createBranch('cairo', 'فرع القاهرة');
        $user = $this->userWithPermissions(['tasks.view'], $branch);
        $stage = $this->createStage('negotiation', 'التفاوض', '#f59e0b', 'bi-chat-dots');
        $status = $this->createStatus($stage, 'in_negotiation', 'قيد التفاوض');
        $lead = $this->createLead($user, $status, $branch, $now->copy()->addMinutes(10), 'أحمد محمد');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $createdOccurrences = $planner->planScheduled($now);
        $this->assertGreaterThanOrEqual(1, $createdOccurrences);

        $dispatched = $dispatcher->dispatchDueOccurrences($now);
        $this->assertGreaterThanOrEqual(1, $dispatched);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'body',
                    'priority',
                    'event_key',
                    'source_kind',
                    'source_id',
                    'source_name',
                    'lead_id',
                    'lead_name',
                    'stage_id',
                    'stage_name',
                    'stage_color',
                    'stage_icon',
                    'action_url',
                    'created_at',
                    'read_at',
                    'can_snooze',
                ],
            ],
            'meta',
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $item = collect($data)->firstWhere('source_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('أحمد محمد', $item['lead_name']);
        $this->assertSame($stage->id, $item['stage_id']);
        $this->assertSame('التفاوض', $item['stage_name']);
        $this->assertSame('#f59e0b', $item['stage_color']);
        $this->assertSame('bi-chat-dots', $item['stage_icon']);
    }

    public function test_2_completed_followup_does_not_appear_as_active_reminder(): void
    {
        $now = Carbon::parse('2026-08-29 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('closing', 'الإغلاق');
        $status = $this->createStatus($stage, 'done', 'تم الإغلاق');

        // Lead where next_follow_up_at was cleared (completed)
        $lead = $this->createLead($user, $status, null, null, 'عميل منتهي المتابعة');

        $planner = app(ReminderPlanner::class);
        $createdOccurrences = $planner->planScheduled($now);
        $this->assertSame(0, $createdOccurrences);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_3_overdue_followup_behaves_according_to_existing_rules(): void
    {
        $now = Carbon::parse('2026-08-29 14:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('interest', 'الاهتمام', '#3b82f6');
        $status = $this->createStatus($stage, 'interested', 'مهتم');

        // Due 2 hours ago
        $lead = $this->createLead($user, $status, null, $now->copy()->subHours(2), 'عميل متأخر');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $items = $response->json('data');
        $item = collect($items)->firstWhere('source_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame($stage->id, $item['stage_id']);
        $this->assertSame('الاهتمام', $item['stage_name']);
    }

    public function test_4_task_due_today_appears_in_notifications(): void
    {
        $now = Carbon::parse('2026-08-29 09:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branch = $this->createBranch('cairo', 'القاهرة');
        $user = $this->userWithPermissions(['calendar.view', 'tasks.view'], $branch);
        $stage = $this->createStage('proposal', 'تقديم العرض', '#10b981');
        $status = $this->createStatus($stage, 'proposal_sent', 'تم إرسال العرض');
        $lead = $this->createLead($user, $status, $branch, null, 'شركة التقنية');

        $event = CalendarEvent::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'lead_id' => $lead->id,
            'title' => 'مهمة: إرسال عرض السعر المعدل',
            'type' => 'task',
            'status' => 'scheduled',
            'start_time' => $now->copy()->addMinutes(15),
            'end_time' => $now->copy()->addMinutes(45),
            'reminder_minutes_before' => 15,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $items = $response->json('data');
        $item = collect($items)->firstWhere('source_id', $event->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('شركة التقنية', $item['lead_name']);
        $this->assertSame($stage->id, $item['stage_id']);
        $this->assertSame('تقديم العرض', $item['stage_name']);
    }

    public function test_5_completed_task_does_not_appear_as_active_reminder(): void
    {
        $now = Carbon::parse('2026-08-29 09:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['calendar.view']);
        $event = CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'مهمة منتهية',
            'type' => 'task',
            'status' => 'completed',
            'start_time' => $now->copy()->addMinutes(15),
            'end_time' => $now->copy()->addMinutes(45),
        ]);

        $planner = app(ReminderPlanner::class);
        $planner->planScheduled($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_6_notification_linked_to_lead_includes_current_pipeline_stage(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('demo_stage', 'مرحلة العرض التوضيحي', '#8b5cf6', 'bi-camera-video');
        $status = $this->createStatus($stage, 'demo_scheduled', 'تم حجز العرض');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(10), 'خالد عبدالله');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame('مرحلة العرض التوضيحي', $item['stage_name']);
        $this->assertSame('#8b5cf6', $item['stage_color']);
        $this->assertSame('bi-camera-video', $item['stage_icon']);
    }

    public function test_7_stage_transition_shows_current_lead_stage_dynamically(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stageA = $this->createStage('stage_a', 'المرحلة أ - البداية', '#64748b');
        $statusA = $this->createStatus($stageA, 'status_a', 'حالة أ');

        $stageB = $this->createStage('stage_b', 'المرحلة ب - التفاوض المتقدم', '#dc2626');
        $statusB = $this->createStatus($stageB, 'status_b', 'حالة ب');

        $lead = $this->createLead($user, $statusA, null, $now->copy()->addMinutes(10), 'سارة أحمد');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Before transition: shows Stage A
        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertSame('المرحلة أ - البداية', $item['stage_name']);
        $this->assertSame($stageA->id, $item['stage_id']);

        // Move Lead to Stage B
        $lead->update(['lead_status_id' => $statusB->id]);

        // After transition: immediately reflects CURRENT Stage B
        $response2 = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response2->assertOk();
        $item2 = collect($response2->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertSame('المرحلة ب - التفاوض المتقدم', $item2['stage_name']);
        $this->assertSame($stageB->id, $item2['stage_id']);
        $this->assertSame('#dc2626', $item2['stage_color']);
    }

    public function test_8_custom_pipeline_stage_appears_correctly(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $customStage = PipelineStage::query()->create([
            'code' => 'custom_donor_vip',
            'name_ar' => 'متبرع كبار الشخصيات VIP',
            'position' => 99,
            'color' => '#eab308',
            'icon' => 'bi-star-fill',
            'is_active' => true,
        ]);
        $status = $this->createStatus($customStage, 'vip_donor', 'متبرع مميز');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(5), 'رجل الأعمال فلان');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertSame('متبرع كبار الشخصيات VIP', $item['stage_name']);
        $this->assertSame('#eab308', $item['stage_color']);
        $this->assertSame('bi-star-fill', $item['stage_icon']);
    }

    public function test_9_inactive_stage_relation_does_not_crash_rendering(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'archived_stage',
            'name_ar' => 'مرحلة مؤرشفة',
            'position' => 50,
            'is_active' => false,
        ]);
        $status = $this->createStatus($inactiveStage, 'archived_status', 'حالة مؤرشفة');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(5), 'عميل بمرحلة معطلة');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame('مرحلة مؤرشفة', $item['stage_name']);
    }

    public function test_10_missing_stage_relation_uses_fallback_safely(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('temp_stage', 'مرحلة مؤقتة');
        $status = $this->createStatus($stage, 'temp_status', 'حالة مؤقتة');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(5), 'عميل بدون مرحلة');
        // Simulate missing stage relation
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('pipeline_stages')
            ->where('id', $stage->id)
            ->delete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertNull($item['stage_id']);
        $this->assertSame('المرحلة غير محددة', $item['stage_name']);
    }

    public function test_11_unauthorized_lead_notification_is_not_visible(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branchA = $this->createBranch('branch_a', 'فرع أ');
        $branchB = $this->createBranch('branch_b', 'فرع ب');

        $userA = $this->userWithPermissions(['tasks.view'], $branchA);
        $userB = $this->userWithPermissions(['tasks.view'], $branchB);

        $stage = $this->createStage('lead_stage', 'مرحلة خاصة');
        $status = $this->createStatus($stage, 'lead_status', 'حالة خاصة');
        $leadA = $this->createLead($userA, $status, $branchA, $now->copy()->addMinutes(10), 'عميل فرع أ');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // User A sees notification
        $responseA = $this->actingAs($userA)->getJson(route('v2.notifications.index'));
        $responseA->assertOk();
        $this->assertCount(1, $responseA->json('data'));

        // User B must NOT see notification
        $responseB = $this->actingAs($userB)->getJson(route('v2.notifications.index'));
        $responseB->assertOk();
        $this->assertEmpty($responseB->json('data'));
    }

    public function test_12_cross_branch_task_notification_is_not_visible(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branchA = $this->createBranch('cairo', 'فرع القاهرة');
        $branchB = $this->createBranch('alex', 'فرع الإسكندرية');

        $userA = $this->userWithPermissions(['calendar.view'], $branchA);
        $userB = $this->userWithPermissions(['calendar.view'], $branchB);

        $stage = $this->createStage('task_stage', 'مرحلة المهمة');
        $status = $this->createStatus($stage, 'task_status', 'حالة المهمة');
        $leadA = $this->createLead($userA, $status, $branchA, null, 'عميل القاهرة');

        $task = CalendarEvent::query()->create([
            'branch_id' => $branchA->id,
            'user_id' => $userA->id,
            'lead_id' => $leadA->id,
            'title' => 'مهمة فرع القاهرة',
            'type' => 'task',
            'status' => 'scheduled',
            'start_time' => $now->copy()->addMinutes(15),
            'end_time' => $now->copy()->addMinutes(45),
            'reminder_minutes_before' => 15,
        ]);

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // User A sees task
        $responseA = $this->actingAs($userA)->getJson(route('v2.notifications.index'));
        $responseA->assertOk();
        $this->assertCount(1, $responseA->json('data'));

        // User B from Alex must NOT see Cairo task
        $responseB = $this->actingAs($userB)->getJson(route('v2.notifications.index'));
        $responseB->assertOk();
        $this->assertEmpty($responseB->json('data'));
    }

    public function test_13_scheduler_does_not_generate_duplicate_active_reminders(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('dedup_stage', 'مرحلة عدم التكرار');
        $status = $this->createStatus($stage, 'dedup_status', 'حالة عدم التكرار');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(10), 'عميل اختبار التكرار');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        // Run scheduler run 1
        $created1 = $planner->planScheduled($now);
        $this->assertSame(1, $created1);
        $dispatched1 = $dispatcher->dispatchDueOccurrences($now);
        $this->assertSame(1, $dispatched1);

        // Run scheduler run 2 at same time
        $created2 = $planner->planScheduled($now);
        $this->assertSame(0, $created2);
        $dispatched2 = $dispatcher->dispatchDueOccurrences($now);
        $this->assertSame(0, $dispatched2);

        // Run scheduler run 3 a minute later
        $created3 = $planner->planScheduled($now->copy()->addMinute());
        $this->assertSame(0, $created3);
        $dispatched3 = $dispatcher->dispatchDueOccurrences($now->copy()->addMinute());
        $this->assertSame(0, $dispatched3);

        // Verify only 1 notification exists in DB
        $this->assertCount(1, $user->notifications);
        $this->assertCount(1, NotificationOccurrence::all());
    }

    public function test_14_unread_count_remains_correct(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('unread_stage', 'مرحلة غير مقروء');
        $status = $this->createStatus($stage, 'unread_status', 'حالة غير مقروء');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(10), 'عميل العداد');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.unread-count'));
        $response->assertOk();
        $response->assertJson(['count' => 1]);

        $notification = $user->notifications()->first();
        $this->actingAs($user)->patchJson(route('v2.notifications.read', $notification->id))
            ->assertOk();

        $response2 = $this->actingAs($user)->getJson(route('v2.notifications.unread-count'));
        $response2->assertOk();
        $response2->assertJson(['count' => 0]);
    }

    public function test_15_notification_stream_returns_expected_structured_payload(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $stage = $this->createStage('stream_stage', 'مرحلة البث');
        $status = $this->createStatus($stage, 'stream_status', 'حالة البث');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(10), 'عميل البث');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->get(route('v2.notifications.stream'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $this->assertStringContainsString('event: notifications', $response->streamedContent());
        $this->assertStringContainsString('"count":1', $response->streamedContent());
    }

    public function test_16_arabic_and_english_labels_work_properly(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $userAr = $this->userWithPermissions(['tasks.view']);
        $userAr->update(['locale' => 'ar']);

        $userEn = $this->userWithPermissions(['tasks.view']);
        $userEn->update(['locale' => 'en']);

        $stage = $this->createStage('interest', 'الاهتمام', '#3b82f6');
        $status = $this->createStatus($stage, 'interested', 'مهتم');
        $leadAr = $this->createLead($userAr, $status, null, $now->copy()->addMinutes(10), 'أحمد');
        $leadEn = $this->createLead($userEn, $status, null, $now->copy()->addMinutes(10), 'John');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Arabic user gets Arabic stage name
        $responseAr = $this->actingAs($userAr)->getJson(route('v2.notifications.index'));
        $responseAr->assertOk();
        $itemAr = collect($responseAr->json('data'))->firstWhere('lead_id', $leadAr->id);
        $this->assertSame('الاهتمام', $itemAr['stage_name']);

        // English user gets English localized stage name
        $responseEn = $this->actingAs($userEn)->getJson(route('v2.notifications.index'));
        $responseEn->assertOk();
        $itemEn = collect($responseEn->json('data'))->firstWhere('lead_id', $leadEn->id);
        $this->assertSame('Interest', $itemEn['stage_name']);
    }
    public function test_17_no_stage_hardcoding_exists(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $user = $this->userWithPermissions(['tasks.view']);
        $randomStageCode = 'stage_rand_' . uniqid();
        $randomStageName = 'مرحلة مخصصة فريدة ' . rand(1000, 9999);
        $stage = $this->createStage($randomStageCode, $randomStageName, '#9333ea', 'bi-gem');
        $status = $this->createStatus($stage, 'status_rand_' . uniqid(), 'حالة فريدة');
        $lead = $this->createLead($user, $status, null, $now->copy()->addMinutes(10), 'عميل ديناميكي');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        $response = $this->actingAs($user)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('lead_id', $lead->id);
        $this->assertNotNull($item);
        $this->assertSame($randomStageName, $item['stage_name']);
        $this->assertSame('#9333ea', $item['stage_color']);
        $this->assertSame('bi-gem', $item['stage_icon']);
    }

    public function test_18_cross_branch_followup_notification_is_not_visible(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branchCairo = $this->createBranch('cairo_branch', 'فرع القاهرة');
        $branchAlex = $this->createBranch('alex_branch', 'فرع الإسكندرية');

        $userCairo = $this->userWithPermissions(['tasks.view'], $branchCairo);
        $userAlex = $this->userWithPermissions(['tasks.view'], $branchAlex);

        $stage = $this->createStage('branch_stage', 'مرحلة الفرع');
        $status = $this->createStatus($stage, 'branch_status', 'حالة الفرع');
        $leadCairo = $this->createLead($userCairo, $status, $branchCairo, $now->copy()->addMinutes(10), 'عميل القاهرة');

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $planner->planScheduled($now);
        $dispatcher->dispatchDueOccurrences($now);

        // Cairo user sees Cairo lead followup
        $responseCairo = $this->actingAs($userCairo)->getJson(route('v2.notifications.index'));
        $responseCairo->assertOk();
        $this->assertCount(1, $responseCairo->json('data'));

        // Alex user sees 0 notifications
        $responseAlex = $this->actingAs($userAlex)->getJson(route('v2.notifications.index'));
        $responseAlex->assertOk();
        $this->assertEmpty($responseAlex->json('data'));
    }
    public function test_19_collection_case_notification_includes_related_lead_stage(): void
    {
        $now = Carbon::parse('2026-08-29 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $branch = $this->createBranch('cairo_col', 'فرع القاهرة للتحصيل');
        $collector = $this->userWithPermissions(['collections.view'], $branch);
        $stage = $this->createStage('pledged', 'تم التعهد بالتبرع', '#059669', 'bi-cash-coin');
        $status = $this->createStatus($stage, 'pledged_status', 'تعهد');
        $lead = $this->createLead($collector, $status, $branch, null, 'المتبرع كريم');

        $collectionCase = \App\Models\CollectionCase::query()->create([
            'branch_id' => $branch->id,
            'lead_id' => $lead->id,
            'assigned_collector_user_id' => $collector->id,
            'created_by_user_id' => $collector->id,
            'status' => 'pending',
            'donation_type' => 'cash',
            'expected_amount' => 500.00,
            'cycle' => 'once',
            'collection_address' => 'العنوان: القاهرة',
            'due_at' => $now->copy()->addMinutes(15),
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

        $response = $this->actingAs($collector)->getJson(route('v2.notifications.index'));
        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('source_id', $collectionCase->id);
        $this->assertNotNull($item);
        $this->assertSame($lead->id, $item['lead_id']);
        $this->assertSame('المتبرع كريم', $item['lead_name']);
        $this->assertSame('تم التعهد بالتبرع', $item['stage_name']);
        $this->assertSame('#059669', $item['stage_color']);
        $this->assertSame('bi-cash-coin', $item['stage_icon']);
    }

    private function createBranch(string $code, string $nameAr): Branch
    {
        return Branch::query()->firstOrCreate(
            ['code' => $code],
            ['name_ar' => $nameAr, 'name_en' => $code, 'is_active' => true],
        );
    }

    private function createStage(string $code, string $nameAr, ?string $color = '#64748b', ?string $icon = null): PipelineStage
    {
        $existing = PipelineStage::query()->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $nextPosition = (int) (PipelineStage::query()->max('position') ?? 0) + 1;

        return PipelineStage::query()->create([
            'code' => $code,
            'name_ar' => $nameAr,
            'position' => $nextPosition,
            'color' => $color,
            'icon' => $icon,
            'is_active' => true,
            'is_primary' => true,
        ]);
    }

    private function createStatus(PipelineStage $stage, string $code, string $nameAr, ?string $color = null): LeadStatus
    {
        $existing = LeadStatus::query()->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $nextPosition = (int) (LeadStatus::query()->max('position') ?? 0) + 1;

        return LeadStatus::query()->create([
            'code' => $code,
            'pipeline_stage_id' => $stage->getKey(),
            'name_ar' => $nameAr,
            'position' => $nextPosition,
            'color' => $color ?? $stage->color,
            'is_terminal' => false,
        ]);
    }

    private function createLead(User $user, LeadStatus $status, ?Branch $branch, ?Carbon $dueAt, string $name): Lead
    {
        return Lead::query()->create([
            'branch_id' => $branch?->id ?? $user->branch_id,
            'lead_status_id' => $status->getKey(),
            'name' => $name,
            'assigned_user_id' => $user->getKey(),
            'created_by_user_id' => $user->getKey(),
            'next_follow_up_at' => $dueAt,
        ]);
    }

    /** @param list<string> $permissionCodes */
    private function userWithPermissions(array $permissionCodes, ?Branch $branch = null): User
    {
        $group = Group::query()->create([
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->slug(),
            'is_system' => false,
        ]);
        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0], 'name_ar' => $code],
            );
            $group->permissions()->syncWithoutDetaching($permission);
        }
        $user = User::factory()->create([
            'is_active' => true,
            'timezone' => 'UTC',
            'locale' => 'ar',
            'branch_id' => $branch?->id,
        ]);
        $user->groups()->attach($group);

        return $user;
    }
}

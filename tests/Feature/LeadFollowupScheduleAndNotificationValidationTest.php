<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\InstantDonationMethod;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LeadFollowupScheduleAndNotificationValidationTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;
    private Branch $branchB;
    private User $agentA;
    private User $agentB;
    private User $superAdmin;
    private PipelineStage $stageNew;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusNoAnswer;
    private LeadStatus $statusDonor;
    private DonationType $donationType;
    private InstantDonationMethod $instantMethod;

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

        $this->branchA = Branch::query()->firstOrCreate(
            ['code' => 'branch_cairo'],
            ['name_ar' => 'فرع القاهرة', 'name_en' => 'Cairo Branch', 'is_active' => true]
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_alex'],
            ['name_ar' => 'فرع الإسكندرية', 'name_en' => 'Alexandria Branch', 'is_active' => true]
        );

        $this->seedPermissions();

        $this->agentA = $this->createUserWithPermissions([
            'leads.view',
            'leads.update',
            'leads.followups.create',
            'leads.followups.view',
            'tasks.view',
            'calendar.view',
            'calendar.manage',
        ], $this->branchA, 'agent_cairo');

        $this->agentB = $this->createUserWithPermissions([
            'leads.view',
            'leads.update',
            'leads.followups.create',
            'leads.followups.view',
            'tasks.view',
            'calendar.view',
            'calendar.manage',
        ], $this->branchB, 'agent_alex');

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_' . uniqid(),
            'is_active' => true,
            'branch_id' => $this->branchA->id,
            'timezone' => 'UTC',
            'locale' => 'ar',
        ]);
        $this->superAdmin->groups()->sync([$superAdminGroup->id]);

        $this->stageNew = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'color' => '#3b82f6', 'is_active' => true]
        );

        $this->stageDonor = PipelineStage::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 2, 'color' => '#10b981', 'is_active' => true]
        );

        $this->statusNew = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $this->stageNew->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3b82f6',
            ]
        );

        $this->statusNoAnswer = LeadStatus::query()->firstOrCreate(
            ['code' => 'no_answer'],
            [
                'pipeline_stage_id' => $this->stageNew->id,
                'name_ar' => 'لم يتم الرد',
                'position' => 2,
                'color' => '#f59e0b',
            ]
        );

        $this->statusDonor = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            [
                'pipeline_stage_id' => $this->stageDonor->id,
                'name_ar' => 'متبرع',
                'position' => 3,
                'color' => '#10b981',
            ]
        );

        $this->donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'كفالة أيتام عامة'],
            ['name_en' => 'General Orphan Sponsorship', 'is_active' => true]
        );

        $this->instantMethod = InstantDonationMethod::query()->firstOrCreate(
            ['name_ar' => 'فودافون كاش'],
            ['name_en' => 'Vodafone Cash', 'is_active' => true, 'position' => 1]
        );

        $this->ensureNotificationRules();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Validate scheduling follow-up sets the exact date and time in database on lead and followup record.
     */
    public function test_schedule_followup_sets_exact_date_and_time_on_lead_and_followup_record(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'محمد عبد الرحمن');

        $scheduledDateTime = '2026-09-12 15:30:00';

        $response = $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'تم الاتصال بالعميل ولم يرد، وتم تحديد موعد متابعة قادمة',
                'next_follow_up_at' => $scheduledDateTime,
            ]);

        $response->assertRedirect(route('v2.leads.followups.index', $lead));
        $response->assertSessionHas('success');

        $lead->refresh();
        $this->assertNotNull($lead->next_follow_up_at);
        $this->assertSame($scheduledDateTime, $lead->next_follow_up_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->statusNoAnswer->id, $lead->lead_status_id);

        $this->assertDatabaseHas('lead_followups', [
            'lead_id' => $lead->id,
            'user_id' => $this->agentA->id,
            'from_status_id' => $this->statusNew->id,
            'to_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتصال بالعميل ولم يرد، وتم تحديد موعد متابعة قادمة',
            'next_follow_up_at' => $scheduledDateTime,
        ]);
    }

    /**
     * 2. Validate scheduled follow-up appears in Calendar at the exact time and date with metadata.
     */
    public function test_scheduled_followup_appears_in_calendar_with_correct_time_and_metadata(): void
    {
        $now = Carbon::parse('2026-09-07 09:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'فاطمة الزهراء', '01099887766');

        $scheduledDateTime = '2026-09-14 11:45:00';

        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'متابعة بخصوص التبرع',
                'next_follow_up_at' => $scheduledDateTime,
            ])
            ->assertRedirect();

        $lead->refresh();

        // 2a. Query calendar inside date range
        $calendarResponse = $this->actingAs($this->agentA)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-14 00:00:00',
                'end' => '2026-09-14 23:59:59',
            ]));

        $calendarResponse->assertOk();
        $events = $calendarResponse->json('data');

        $matchingEvents = array_values(array_filter(
            $events,
            static fn (array $e): bool => ($e['id'] ?? '') === 'lead_followup_' . $lead->id
        ));

        $this->assertCount(1, $matchingEvents, 'Expected exactly one calendar event for the scheduled follow-up');

        $event = $matchingEvents[0];
        $this->assertSame($lead->id, $event['raw_id']);
        $this->assertSame('lead_followup', $event['type']);
        $this->assertTrue($event['is_lead_followup']);
        $this->assertSame('فاطمة الزهراء', $event['lead_name']);
        $this->assertSame('01099887766', $event['lead_phone']);
        $this->assertSame($this->agentA->id, $event['user_id']);
        $this->assertSame(route('v2.leads.show', $lead->id), $event['lead_url']);

        // Check start time and duration (30 minutes default)
        $expectedStart = Carbon::parse($scheduledDateTime)->toIso8601String();
        $expectedEnd = Carbon::parse($scheduledDateTime)->addMinutes(30)->toIso8601String();
        $this->assertSame($expectedStart, $event['start']);
        $this->assertSame($expectedEnd, $event['end']);
        $this->assertSame('scheduled', $event['status']);

        // 2b. Query calendar outside date range
        $outsideResponse = $this->actingAs($this->agentA)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-15 00:00:00',
                'end' => '2026-09-15 23:59:59',
            ]));

        $outsideResponse->assertOk();
        $outsideEvents = $outsideResponse->json('data');
        $outsideMatching = array_filter(
            $outsideEvents,
            static fn (array $e): bool => ($e['id'] ?? '') === 'lead_followup_' . $lead->id
        );
        $this->assertEmpty($outsideMatching, 'Lead should not appear in calendar outside its scheduled date');
    }

    /**
     * 3. Validate scheduled follow-up appears in Daily Tasks page under correct scopes and cards/tables.
     */
    public function test_scheduled_followup_appears_in_daily_tasks_under_correct_scopes(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Lead 1: Due Today
        $leadToday = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'عميل موعد اليوم');
        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $leadToday), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'متابعة اليوم',
                'next_follow_up_at' => '2026-09-07 14:00:00',
            ])
            ->assertRedirect();

        // Lead 2: Upcoming (Future)
        $leadUpcoming = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'عميل موعد قادم');
        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $leadUpcoming), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'whatsapp',
                'outcome' => 'متابعة قادمة الأسبوع القادم',
                'next_follow_up_at' => '2026-09-12 11:00:00',
            ])
            ->assertRedirect();

        // Lead 3: Overdue (Past)
        $leadOverdue = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'عميل متأخر عن موعده');
        $leadOverdue->update(['next_follow_up_at' => Carbon::parse('2026-09-05 09:30:00')]);

        // 3a. Verify scope=today
        $responseToday = $this->actingAs($this->agentA)->get(route('v2.tasks.daily', ['scope' => 'today']));
        $responseToday->assertOk();
        $responseToday->assertSee('عميل موعد اليوم');
        $responseToday->assertDontSee('عميل موعد قادم');
        $responseToday->assertDontSee('عميل متأخر عن موعده');

        // 3b. Verify scope=upcoming
        $responseUpcoming = $this->actingAs($this->agentA)->get(route('v2.tasks.daily', ['scope' => 'upcoming']));
        $responseUpcoming->assertOk();
        $responseUpcoming->assertSee('عميل موعد قادم');
        $responseUpcoming->assertDontSee('عميل موعد اليوم');
        $responseUpcoming->assertDontSee('عميل متأخر عن موعده');

        // 3c. Verify scope=overdue
        $responseOverdue = $this->actingAs($this->agentA)->get(route('v2.tasks.daily', ['scope' => 'overdue']));
        $responseOverdue->assertOk();
        $responseOverdue->assertSee('عميل متأخر عن موعده');
        $responseOverdue->assertDontSee('عميل موعد اليوم');
        $responseOverdue->assertDontSee('عميل موعد قادم');

        // 3d. Verify table view renders correct date/time
        $responseTable = $this->actingAs($this->agentA)->get(route('v2.tasks.daily', ['scope' => 'today', 'view' => 'table']));
        $responseTable->assertOk();
        $responseTable->assertSee('عميل موعد اليوم');
        $responseTable->assertSee('07/09/2026 - 02:00 PM');
    }

    /**
     * 4. Validate next follow-up date appears on the Lead's Profile page (show.blade.php).
     */
    public function test_next_followup_date_appears_in_lead_profile_page_and_timeline(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'دكتور مصطفى محمود');

        $scheduledDateTime = '2026-09-16 16:30:00';
        $outcomeText = 'تم التواصل والاتفاق على مناقشة مشروع الوقف الخيري';

        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => $outcomeText,
                'next_follow_up_at' => $scheduledDateTime,
            ])
            ->assertRedirect();

        // Access Lead Profile
        $profileResponse = $this->actingAs($this->agentA)->get(route('v2.leads.show', $lead));
        $profileResponse->assertOk();

        // 4a. Header / info grid displays formatted next follow up date
        $expectedHeaderDate = '2026-09-16 16:30';
        $profileResponse->assertSee($expectedHeaderDate);

        // 4b. Timeline displays followup entry and its next follow up date
        $profileResponse->assertSee($outcomeText);
        $profileResponse->assertSee($this->agentA->name);
        $profileResponse->assertSee($expectedHeaderDate);
    }

    /**
     * 5. Validate that notification triggers and delivers when the scheduled follow-up date arrives.
     */
    public function test_notification_triggers_and_delivers_when_followup_date_arrives(): void
    {
        // 5a. Scheduling time: 2026-09-08 09:00:00
        $scheduleTime = Carbon::parse('2026-09-08 09:00:00', 'UTC');
        Carbon::setTestNow($scheduleTime);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'أستاذ طارق إبراهيم');

        // Follow-up scheduled for 2026-09-08 14:00:00
        $dueTime = Carbon::parse('2026-09-08 14:00:00', 'UTC');

        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'تحديد موعد متابعة بعد الظهر',
                'next_follow_up_at' => $dueTime->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        // At scheduling time, unread notifications must be 0
        $unreadResponseBefore = $this->actingAs($this->agentA)->getJson(route('v2.notifications.unread-count'));
        $unreadResponseBefore->assertOk()->assertJson(['count' => 0]);

        // 5b. Advance time to reminder window (15 minutes prior to due, matching default trigger_offset_minutes = 15)
        $reminderTriggerTime = $dueTime->copy()->subMinutes(15);
        Carbon::setTestNow($reminderTriggerTime);

        // Run the notification command
        Artisan::call('crm:notifications:dispatch');

        // Verify occurrence is created and dispatched
        $occurrence = NotificationOccurrence::query()
            ->where('source_kind', 'lead_followup')
            ->where('source_id', $lead->id)
            ->where('recipient_user_id', $this->agentA->id)
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)
            ->first();

        $this->assertNotNull($occurrence, 'Notification occurrence must be created when trigger time arrives');
        $this->assertSame(NotificationOccurrence::STATUS_DISPATCHED, $occurrence->status);

        // Verify delivery record
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_occurrence_id' => $occurrence->id,
            'channel' => 'database',
            'status' => NotificationDelivery::STATUS_DELIVERED,
        ]);

        // Verify DatabaseNotification created on the user
        $this->assertCount(1, $this->agentA->fresh()->notifications);
        $notification = $this->agentA->fresh()->notifications->first();
        $this->assertSame($notification->id, $occurrence->fresh()->notification_id);

        // 5c. Check in-app notification API
        $unreadResponseAfter = $this->actingAs($this->agentA)->getJson(route('v2.notifications.unread-count'));
        $unreadResponseAfter->assertOk()->assertJson(['count' => 1]);

        $listResponse = $this->actingAs($this->agentA)->getJson(route('v2.notifications.index'));
        $listResponse->assertOk();
        $data = $listResponse->json('data');
        $this->assertNotEmpty($data);
        $this->assertSame($lead->id, $data[0]['lead_id']);
        $this->assertSame('أستاذ طارق إبراهيم', $data[0]['lead_name']);
        $this->assertSame(route('v2.leads.show', $lead->id, false), $data[0]['action_url']);

        // 5d. User marks notification as read
        $this->actingAs($this->agentA)->patchJson(route('v2.notifications.read', $notification->id))
            ->assertOk();

        $unreadAfterRead = $this->actingAs($this->agentA)->getJson(route('v2.notifications.unread-count'));
        $unreadAfterRead->assertOk()->assertJson(['count' => 0]);
    }

    /**
     * 6. Validate rescheduling via Daily Tasks page updates date across calendar, tasks, profile, and notifications.
     */
    public function test_reschedule_via_daily_tasks_updates_date_everywhere(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNoAnswer, $this->branchA, 'أحمد صبري للتأجيل');
        $lead->update(['next_follow_up_at' => Carbon::parse('2026-09-07 15:00:00')]);

        // Reschedule via daily task reschedule endpoint
        $rescheduledDate = '2026-09-11 16:00:00';
        $rescheduleResponse = $this->actingAs($this->agentA)
            ->post(route('v2.tasks.reschedule', $lead), [
                'next_follow_up_at' => $rescheduledDate,
                'reschedule_reason' => 'طلب العميل إعادة الاتصال الأسبوع القادم',
            ]);

        $rescheduleResponse->assertRedirect();
        $lead->refresh();
        $this->assertSame($rescheduledDate, $lead->next_follow_up_at->format('Y-m-d H:i:s'));

        // Calendar check: not on 2026-09-07, present on 2026-09-11
        $oldCal = $this->actingAs($this->agentA)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-07 00:00:00',
            'end' => '2026-09-07 23:59:59',
        ]))->json('data');
        $this->assertEmpty(array_filter($oldCal, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id));

        $newCal = $this->actingAs($this->agentA)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-11 00:00:00',
            'end' => '2026-09-11 23:59:59',
        ]))->json('data');
        $matchingNewCal = array_values(array_filter($newCal, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id));
        $this->assertCount(1, $matchingNewCal);
        $this->assertSame(Carbon::parse($rescheduledDate)->toIso8601String(), $matchingNewCal[0]['start']);

        // Profile check
        $profile = $this->actingAs($this->agentA)->get(route('v2.leads.show', $lead));
        $profile->assertOk();
        $profile->assertSee('2026-09-11 16:00');

        // Notification check when the new rescheduled date arrives
        Carbon::setTestNow(Carbon::parse('2026-09-11 15:45:00', 'UTC'));
        Artisan::call('crm:notifications:dispatch');

        $this->assertDatabaseHas('notification_occurrences', [
            'source_kind' => 'lead_followup',
            'source_id' => $lead->id,
            'recipient_user_id' => $this->agentA->id,
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
        ]);
        $this->assertGreaterThanOrEqual(1, $this->agentA->fresh()->notifications()->count());
    }

    /**
     * 7. Validate rescheduling via Calendar endpoint updates date across all surfaces.
     */
    public function test_reschedule_via_calendar_endpoint_updates_date_across_surfaces(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNoAnswer, $this->branchA, 'مهندس سمير للتقويم');
        $lead->update(['next_follow_up_at' => Carbon::parse('2026-09-08 10:00:00')]);

        $newCalendarDate = '2026-09-15T14:30:00.000Z';

        $calReschedule = $this->actingAs($this->agentA)
            ->patchJson(route('v2.calendar.lead.reschedule', $lead), [
                'start_time' => $newCalendarDate,
                'reason' => 'إعادة جدولة عبر سحب الموعد في التقويم',
            ]);

        $calReschedule->assertOk()->assertJsonPath('success', true);

        $lead->refresh();
        $this->assertSame('2026-09-15 14:30:00', $lead->next_follow_up_at->format('Y-m-d H:i:s'));

        // Appears in Calendar on the new date
        $calEvents = $this->actingAs($this->agentA)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-15 00:00:00',
            'end' => '2026-09-15 23:59:59',
        ]))->json('data');
        $matching = array_filter($calEvents, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id);
        $this->assertNotEmpty($matching);

        // Appears in Profile
        $profile = $this->actingAs($this->agentA)->get(route('v2.leads.show', $lead));
        $profile->assertOk()->assertSee('2026-09-15 14:30');
    }

    /**
     * 8. Validate donor conversion followup with custom cycle and preferred donation date and time.
     */
    public function test_donor_conversion_followup_with_custom_cycle_and_preferred_date_and_time(): void
    {
        $now = Carbon::parse('2026-09-07 11:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'رجل أعمال متبرع دائم');

        $preferredDate = '2026-09-25';
        $preferredTime = '12:45';

        $response = $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusDonor->id,
                'communication_type' => 'meeting',
                'outcome' => 'تم الاتفاق على تبرع دوري وتحديد الموعد المناسب للتحصيل',
                'record_donation' => 1,
                'donation_way' => 'instant',
                'donation_type_id' => $this->donationType->id,
                'donation_value' => 15000.00,
                'donation_cycle' => 'other',
                'preferred_donation_date' => $preferredDate,
                'preferred_donation_time' => $preferredTime,
                'instant_donation_method_id' => $this->instantMethod->id,
            ]);

        $response->assertRedirect();
        $lead->refresh();

        $expectedDateTime = '2026-09-25 12:45:00';
        $this->assertSame($expectedDateTime, $lead->next_follow_up_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->statusDonor->id, $lead->lead_status_id);

        // Verify in Calendar
        $calEvents = $this->actingAs($this->agentA)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-25 00:00:00',
            'end' => '2026-09-25 23:59:59',
        ]))->json('data');

        $eventMatch = array_values(array_filter($calEvents, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id));
        $this->assertCount(1, $eventMatch);
        $this->assertSame(Carbon::parse($expectedDateTime)->toIso8601String(), $eventMatch[0]['start']);

        // Verify in Profile
        $profile = $this->actingAs($this->agentA)->get(route('v2.leads.show', $lead));
        $profile->assertOk();
        $profile->assertSee('2026-09-25 12:45');

        // Advance to preferred date and verify notification triggers
        Carbon::setTestNow(Carbon::parse('2026-09-25 12:30:00', 'UTC'));
        Artisan::call('crm:notifications:dispatch');

        $this->assertDatabaseHas('notification_occurrences', [
            'source_kind' => 'lead_followup',
            'source_id' => $lead->id,
            'recipient_user_id' => $this->agentA->id,
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
        ]);
        $this->assertGreaterThanOrEqual(1, $this->agentA->fresh()->notifications()->count());
    }

    /**
     * 9. Validate branch isolation and unauthorized user permissions on scheduled follow-ups and notifications.
     */
    public function test_branch_and_authorization_isolation_on_followup_and_notifications(): void
    {
        $now = Carbon::parse('2026-09-07 10:00:00', 'UTC');
        Carbon::setTestNow($now);

        // Lead belongs to Cairo (Branch A), assigned to Agent A
        $leadBranchA = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'عميل خاص بفرع القاهرة');
        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $leadBranchA), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'متابعة فرع القاهرة',
                'next_follow_up_at' => '2026-09-07 13:00:00',
            ])
            ->assertRedirect();

        // 9a. Agent B (Branch B) cannot see the lead in Daily Tasks
        $tasksB = $this->actingAs($this->agentB)->get(route('v2.tasks.daily', ['scope' => 'today']));
        $tasksB->assertOk();
        $tasksB->assertDontSee('عميل خاص بفرع القاهرة');

        // 9b. Agent B cannot see the lead in Calendar
        $calB = $this->actingAs($this->agentB)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-07 00:00:00',
            'end' => '2026-09-07 23:59:59',
        ]))->json('data');
        $this->assertEmpty(array_filter($calB, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $leadBranchA->id));

        // 9c. Agent B cannot view or create follow-ups for Branch A lead (forbidden 403)
        $this->actingAs($this->agentB)->get(route('v2.leads.followups.index', $leadBranchA))
            ->assertForbidden();
        $this->actingAs($this->agentB)->post(route('v2.leads.followups.store', $leadBranchA), [
            'lead_status_id' => $this->statusNoAnswer->id,
            'communication_type' => 'call',
            'outcome' => 'محاولة غير مصرح بها',
            'next_follow_up_at' => '2026-09-08 10:00:00',
        ])->assertForbidden();

        // 9d. A user without leads.view cannot view the lead profile (403)
        $userWithoutView = $this->createUserWithPermissions([], $this->branchA, 'no_view_user');
        $this->actingAs($userWithoutView)->get(route('v2.leads.show', $leadBranchA))
            ->assertForbidden();

        // 9e. When date arrives, Agent A gets notification, Agent B DOES NOT
        Carbon::setTestNow(Carbon::parse('2026-09-07 12:45:00', 'UTC'));
        Artisan::call('crm:notifications:dispatch');

        $this->assertCount(1, $this->agentA->fresh()->notifications);
        $this->assertCount(0, $this->agentB->fresh()->notifications);
    }

    /**
     * 10. Validate exact time boundary, midnight crossing, and minute-precision notifications.
     */
    public function test_exact_time_boundary_and_late_night_scheduling_preserves_timestamp_and_triggers_notifications(): void
    {
        $now = Carbon::parse('2026-09-07 14:00:00', 'UTC');
        Carbon::setTestNow($now);

        $lead = $this->createLead($this->agentA, $this->statusNew, $this->branchA, 'عميل موعد متأخر ليلا');

        // Schedule at 23:45 at night
        $scheduledTime = '2026-09-07 23:45:00';
        $this->actingAs($this->agentA)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'مكالمة مسائية متأخرة قبل منتصف الليل',
                'next_follow_up_at' => $scheduledTime,
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($scheduledTime, $lead->next_follow_up_at->format('Y-m-d H:i:s'));

        // 10a. Calendar event starts on 2026-09-07 at 23:45 and ends crossing midnight on 2026-09-08 at 00:15
        $calEvents = $this->actingAs($this->agentA)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-07 00:00:00',
            'end' => '2026-09-07 23:59:59',
        ]))->json('data');

        $matched = array_values(array_filter($calEvents, fn ($e) => ($e['id'] ?? '') === 'lead_followup_' . $lead->id));
        $this->assertCount(1, $matched);
        $expectedStart = Carbon::parse($scheduledTime)->toIso8601String();
        $expectedEnd = Carbon::parse($scheduledTime)->addMinutes(30)->toIso8601String();
        $this->assertSame($expectedStart, $matched[0]['start']);
        $this->assertSame($expectedEnd, $matched[0]['end']);
        // 10b. Daily tasks on 2026-09-07 lists the task under today
        $tasksToday = $this->actingAs($this->agentA)->get(route('v2.tasks.daily', ['scope' => 'today']));
        $tasksToday->assertOk();
        $tasksToday->assertSee('عميل موعد متأخر ليلا');
        $tasksToday->assertSee('11:45 PM');

        // 10c. Lead profile shows exact minute
        $profile = $this->actingAs($this->agentA)->get(route('v2.leads.show', $lead));
        $profile->assertOk();
        $profile->assertSee('2026-09-07 23:45');

        // 10d. When reminder time arrives (23:30), notification triggers
        Carbon::setTestNow(Carbon::parse('2026-09-07 23:30:00', 'UTC'));
        Artisan::call('crm:notifications:dispatch');

        $this->assertDatabaseHas('notification_occurrences', [
            'source_kind' => 'lead_followup',
            'source_id' => $lead->id,
            'recipient_user_id' => $this->agentA->id,
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
        ]);
        $this->assertCount(1, $this->agentA->fresh()->notifications);
    }

    // -------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------

    private function seedPermissions(): void
    {
        $codes = [
            'leads.view',
            'leads.update',
            'leads.followups.create',
            'leads.followups.view',
            'tasks.view',
            'calendar.view',
            'calendar.manage',
        ];

        foreach ($codes as $code) {
            Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0], 'name_ar' => $code]
            );
        }
    }

    /**
     * @param list<string> $permissionCodes
     */
    private function createUserWithPermissions(array $permissionCodes, Branch $branch, string $usernamePrefix): User
    {
        $group = Group::query()->create([
            'name' => 'Group ' . uniqid(),
            'code' => 'group_' . uniqid(),
            'is_system' => false,
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0], 'name_ar' => $code]
            );
            $group->permissions()->syncWithoutDetaching($permission);
        }

        $user = User::factory()->create([
            'username' => $usernamePrefix . '_' . uniqid(),
            'is_active' => true,
            'timezone' => 'UTC',
            'locale' => 'ar',
            'branch_id' => $branch->id,
        ]);

        $user->groups()->attach($group);

        return $user;
    }

    private function createLead(User $user, LeadStatus $status, Branch $branch, string $name, ?string $phone = '01012345678'): Lead
    {
        $lead = Lead::query()->create([
            'branch_id' => $branch->id,
            'lead_status_id' => $status->id,
            'name' => $name,
            'phone' => $phone,
            'assigned_user_id' => $user->id,
            'created_by_user_id' => $user->id,
        ]);

        if ($phone !== null) {
            LeadPhone::query()->create([
                'lead_id' => $lead->id,
                'phone' => $phone,
                'is_primary' => true,
            ]);
        }

        return $lead;
    }

    private function ensureNotificationRules(): void
    {
        $dueRule = NotificationRule::query()->firstOrCreate(
            ['event_key' => NotificationRule::EVENT_FOLLOWUP_DUE],
            [
                'name_ar' => 'موعد متابعة قادم',
                'name_en' => 'Follow-up due soon',
                'enabled' => true,
                'trigger_offset_minutes' => 15,
                'escalation_after_minutes' => null,
                'priority' => 'important',
            ]
        );

        $dueRule->recipients()->firstOrCreate([
            'recipient_type' => 'assigned_user',
            'recipient_id' => 0,
        ]);

        $dueRule->channels()->firstOrCreate(['channel' => 'database']);
    }
}

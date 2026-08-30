<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\CalendarSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdvancedCalendarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_reschedule_calendar_event_via_drag_and_drop_endpoint(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage']);

        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'اجتماع مراجعة العقد',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
            'status' => 'scheduled',
        ]);

        $newStart = '2026-09-02 14:00:00';
        $newEnd = '2026-09-02 15:30:00';

        $response = $this->actingAs($user)
            ->patchJson(route('v2.calendar.reschedule', $event), [
                'start_time' => $newStart,
                'end_time' => $newEnd,
                'status' => 'scheduled',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'status' => 'scheduled',
                ],
            ]);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'start_time' => Carbon::parse($newStart)->toDateTimeString(),
            'end_time' => Carbon::parse($newEnd)->toDateTimeString(),
        ]);
    }

    public function test_manager_can_filter_calendar_events_by_specific_team_member(): void
    {
        $manager = $this->userWithPermissions(['calendar.view', 'calendar.manage', CrmPermission::LEADS_SCOPE_ALL]);
        $agent1 = User::factory()->create();
        $agent2 = User::factory()->create();

        $event1 = CalendarEvent::factory()->create([
            'user_id' => $agent1->id,
            'title' => 'مكالمة Agent 1',
            'start_time' => '2026-09-05 10:00:00',
            'end_time' => '2026-09-05 10:30:00',
        ]);

        $event2 = CalendarEvent::factory()->create([
            'user_id' => $agent2->id,
            'title' => 'مكالمة Agent 2',
            'start_time' => '2026-09-05 11:00:00',
            'end_time' => '2026-09-05 11:30:00',
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('v2.calendar.events', [
                'user_id' => $agent1->id,
            ]));

        $response->assertStatus(200);

        $data = $response->json('data');
        $eventIds = array_column($data, 'id');

        $this->assertContains($event1->id, $eventIds);
        $this->assertNotContains($event2->id, $eventIds);
    }

    public function test_user_can_fetch_upcoming_reminders(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);

        // Event starting in 15 minutes
        $upcomingEvent = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'استشارة هامة قادمة',
            'start_time' => now()->addMinutes(15),
            'end_time' => now()->addMinutes(45),
            'status' => 'scheduled',
            'reminder_minutes_before' => 30,
        ]);

        // Past event should not trigger reminder
        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'حدث منتهي',
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('v2.calendar.reminders', ['within_minutes' => 30]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        $data = $response->json('data');
        $this->assertEquals($upcomingEvent->id, $data[0]['id']);
        $this->assertEquals('استشارة هامة قادمة', $data[0]['title']);
    }

    public function test_calendar_sync_service_prepares_payload_and_marks_as_synced(): void
    {
        $user = User::factory()->create(['email' => 'lawyer@example.com']);
        $lead = $this->createLead($user);
        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'lead_id' => $lead->id,
            'title' => 'اجتماع توقيع الاتفاقية',
            'description' => 'توقيع العقد النهائي مع العميل',
            'start_time' => '2026-09-10 10:00:00',
            'end_time' => '2026-09-10 11:00:00',
            'reminder_minutes_before' => 30,
        ]);

        $syncService = new CalendarSyncService();

        $payload = $syncService->prepareSyncPayload($event);

        $this->assertEquals('اجتماع توقيع الاتفاقية', $payload['summary']);
        $this->assertEquals(30, $payload['reminders']['overrides'][0]['minutes']);
        $this->assertEquals((string) $event->id, $payload['extendedProperties']['private']['crm_event_id']);

        $result = $syncService->syncEventToExternal($event, 'google');

        $this->assertTrue($result['success']);
        $this->assertEquals('google', $result['provider']);
        $this->assertNotEmpty($result['sync_id']);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'provider' => 'google',
        ]);

        $event->refresh();
        $this->assertTrue($event->isSynced());
    }

    public function test_user_can_trigger_external_calendar_sync_endpoint(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage']);

        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'جلسة مفاوضات خارجية',
            'start_time' => '2026-09-15 12:00:00',
            'end_time' => '2026-09-15 13:00:00',
        ]);
        $response = $this->actingAs($user)
            ->postJson(route('v2.calendar.sync', $event), [
                'provider' => 'google',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $event->refresh();
        $this->assertTrue($event->isSynced());
        $this->assertEquals('google', $event->provider);
    }


    public function test_calendar_events_include_scheduled_collection_cases_with_details(): void
    {
        $collector = $this->userWithPermissions(['calendar.view', 'collections.view', 'collections.collect']);
        $lead = $this->createLead($collector);

        $case = \App\Models\CollectionCase::query()->create([
            'lead_id' => $lead->id,
            'assigned_collector_user_id' => $collector->id,
            'created_by_user_id' => $collector->id,
            'donation_type' => 'نقدي',
            'expected_amount' => 3500.00,
            'cycle' => 'one_time',
            'status' => 'assigned',
            'due_at' => '2026-09-15 11:30:00',
            'collection_address' => 'شارع الجمهورية - عمارة الأمل',
            'notes' => 'الاتصال قبل الوصول بربع ساعة',
        ]);

        $response = $this->actingAs($collector)->getJson(route('v2.calendar.events', [
            'start' => '2026-09-01T00:00:00Z',
            'end' => '2026-09-30T23:59:59Z',
        ]));

        $response->assertOk();
        $data = $response->json();
        $list = $response->json('data') ?? $response->json();

        $collectionItem = collect($list)->firstWhere('id', 'collection_case_' . $case->id);
        $this->assertNotNull($collectionItem);
        $this->assertSame('collection', $collectionItem['type']);
        $this->assertSame('3,500.00', $collectionItem['expected_amount']);
        $this->assertStringContainsString('شارع الجمهورية', $collectionItem['collection_address']);
    }

    public function test_calendar_conflict_detection_endpoint_identifies_overlapping_events(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage']);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'جلسة نقاش العميل',
            'start_time' => '2026-09-10 10:00:00',
            'end_time' => '2026-09-10 11:00:00',
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        // 1. Overlapping window -> conflict
        $resConflict = $this->actingAs($user)->postJson(route('v2.calendar.conflict'), [
            'user_id' => $user->id,
            'start_time' => '2026-09-10 10:30:00',
            'end_time' => '2026-09-10 11:30:00',
        ]);

        $resConflict->assertOk()
            ->assertJsonPath('has_conflict', true)
            ->assertJsonPath('conflicting_event.title', 'جلسة نقاش العميل');

        // 2. Non-overlapping window -> no conflict
        $resFree = $this->actingAs($user)->postJson(route('v2.calendar.conflict'), [
            'user_id' => $user->id,
            'start_time' => '2026-09-10 12:00:00',
            'end_time' => '2026-09-10 13:00:00',
        ]);

        $resFree->assertOk()
            ->assertJsonPath('has_conflict', false);
    }

    public function test_calendar_ical_feed_exports_valid_vcalendar_payload(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);
        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'اجتماع تغذية التقويم',
            'start_time' => '2026-09-20 09:00:00',
            'end_time' => '2026-09-20 10:00:00',
            'status' => 'scheduled',
        ]);

        $validToken = hash_hmac('sha256', (string) $user->id, (string) config('app.key'));

        // 1. Valid Token -> Returns VCALENDAR
        $resValid = $this->get(route('v2.calendar.feed', ['user' => $user->id, 'token' => $validToken]));
        $resValid->assertOk();
        $this->assertStringContainsString('text/calendar', $resValid->headers->get('content-type'));
        $this->assertStringContainsString('BEGIN:VCALENDAR', $resValid->getContent());
        $this->assertStringContainsString('اجتماع تغذية التقويم', $resValid->getContent());

        // 2. Invalid Token -> 403
        $resInvalid = $this->get(route('v2.calendar.feed', ['user' => $user->id, 'token' => 'fake-invalid-token']));
        $resInvalid->assertForbidden();
    }
    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Test Group '.fake()->unique()->word(),
            'code' => 'test-group-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $codeStr = $code instanceof \BackedEnum ? $code->value : (string) $code;

            $permission = Permission::query()->firstOrCreate(
                ['code' => $codeStr],
                [
                    'module' => explode('.', $codeStr, 2)[0] ?? 'calendar',
                    'name_ar' => $codeStr,
                ]
            );
            $group->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group->id);

        return $user;
    }

    private function createLead(User $user): Lead
    {
        $stage = \App\Models\PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1]
        );

        $status = \App\Models\LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3b82f6',
            ]
        );

        return Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'شركة الأعمال المتقدمة',
            'company_name' => 'شركة الأعمال المتقدمة',
            'phone' => '01099998888',
            'assigned_user_id' => $user->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}

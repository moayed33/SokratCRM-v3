<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_cannot_access_calendar_or_events(): void
    {
        $this->get(route('v2.calendar.index'))
            ->assertRedirect(route('login'));
        $this->getJson(route('v2.calendar.events'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_calendar_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('v2.calendar.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('v2.calendar.events'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_calendar_index_page(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);

        $this->actingAs($user)
            ->get(route('v2.calendar.index'))
            ->assertOk()
            ->assertSee(__('crm.calendar_and_events'));
    }

    public function test_calendar_fullcalendar_script_renders_dynamic_locale_and_direction(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);

        // English session locale test
        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('v2.calendar.index'));

        $responseEn->assertOk();
        $responseEn->assertSee("direction: 'ltr'", false);
        $responseEn->assertSee("locale: 'en'", false);
        $responseEn->assertSee("today: 'Today'", false);

        // Arabic session locale test
        $responseAr = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.calendar.index'));

        $responseAr->assertOk();
        $responseAr->assertSee("direction: 'rtl'", false);
        $responseAr->assertSee("locale: 'ar'", false);
        $responseAr->assertSee("today: 'اليوم'", false);
    }

    public function test_authorized_user_can_fetch_calendar_events_json(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);
        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Client Meeting Demo',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_time' => now()->addDay()->setHour(10)->setMinute(0),
            'end_time' => now()->addDay()->setHour(11)->setMinute(0),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('v2.calendar.events', [
                'start' => now()->subDay()->toIso8601String(),
                'end' => now()->addWeek()->toIso8601String(),
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $event->id,
                'title' => 'Client Meeting Demo',
                'type' => 'meeting',
                'status' => 'scheduled',
            ]);
    }

    public function test_authorized_user_can_create_calendar_event(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage', 'leads.view', 'leads.scope.all']);
        $lead = $this->createLead($user);

        $payload = [
            'title' => 'Initial Sales Discussion',
            'description' => 'Discuss quotation details with customer',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
            'type' => 'meeting',
            'status' => 'scheduled',
            'lead_id' => $lead->id,
        ];

        $response = $this->actingAs($user)
            ->postJson(route('v2.calendar.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Initial Sales Discussion',
            'user_id' => $user->id,
            'lead_id' => $lead->id,
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);
    }

    public function test_authorized_user_can_update_own_calendar_event(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage']);
        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'status' => 'scheduled',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('v2.calendar.update', $event), [
                'title' => 'Updated Event Title',
                'status' => 'completed',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
            'status' => 'completed',
        ]);
    }

    public function test_authorized_user_can_delete_calendar_event(): void
    {
        $user = $this->userWithPermissions(['calendar.view', 'calendar.manage']);
        $event = CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Event To Delete',
            'start_time' => '2026-09-01 10:00:00',
            'end_time' => '2026-09-01 11:00:00',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('v2.calendar.destroy', $event));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('calendar_events', [
            'id' => $event->id,
        ]);
    }

    public function test_user_cannot_attach_unaccessible_lead_to_event(): void
    {
        $user1 = $this->userWithPermissions(['calendar.view', 'calendar.manage', 'leads.view']);
        $user2 = User::factory()->create(['is_active' => true]);

        $leadOfUser2 = $this->createLead($user2);

        $response = $this->actingAs($user1)
            ->postJson(route('v2.calendar.store'), [
                'title' => 'Unauthorized Lead Event',
                'start_time' => '2026-09-01 10:00:00',
                'end_time' => '2026-09-01 11:00:00',
                'type' => 'call',
                'status' => 'scheduled',
                'lead_id' => $leadOfUser2->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lead_id']);
    }

    public function test_user_can_filter_events_by_type_and_status(): void
    {
        $user = $this->userWithPermissions(['calendar.view']);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Call Event',
            'type' => 'call',
            'status' => 'scheduled',
            'start_time' => '2026-09-05 10:00:00',
            'end_time' => '2026-09-05 11:00:00',
        ]);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting Event',
            'type' => 'meeting',
            'status' => 'completed',
            'start_time' => '2026-09-05 12:00:00',
            'end_time' => '2026-09-05 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('v2.calendar.events', [
                'start' => '2026-09-01 00:00:00',
                'end' => '2026-09-10 23:59:59',
                'type' => 'call',
                'status' => 'scheduled',
            ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Call Event');
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Test Group '.fake()->unique()->word(),
            'code' => 'test-group-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ],
            );
            $group->permissions()->attach($permission);
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    private function createLead(User $user): Lead
    {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1]
        );

        $status = LeadStatus::query()->firstOrCreate(
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
            'name' => 'Test Customer',
            'company_name' => 'Test Corp',
            'phone' => '01099998888',
            'assigned_user_id' => $user->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}

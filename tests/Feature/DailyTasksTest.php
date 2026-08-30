<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyTasksTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $codes = ['tasks.view', 'leads.view', 'leads.update', 'leads.scope.all', 'calendar.view', 'leads.followups.create'];
        foreach ($codes as $code) {
            Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0], 'name_ar' => $code]
            );
        }
    }
    public function test_unauthenticated_user_cannot_access_daily_tasks(): void
    {
        $this->get(route('v2.tasks.daily'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_tasks_view_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('v2.tasks.daily'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_daily_tasks_in_arabic_and_english(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all', 'calendar.view']);

        // Arabic test (default)
        $responseAr = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.tasks.daily'));

        $responseAr->assertOk();
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('المهام اليومية');
        $responseAr->assertSee('مهام متأخرة');
        $responseAr->assertSee('مهام اليوم');
        $responseAr->assertSee('المكتملة اليوم');

        // English test
        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('v2.tasks.daily'));

        $responseEn->assertOk();
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('Daily Tasks');
        $responseEn->assertSee('Overdue Tasks');
        $responseEn->assertSee('Due Today');
        $responseEn->assertSee('Completed Today');
    }

    public function test_daily_tasks_displays_leads_in_correct_time_buckets(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all']);
        $status = $this->createStatus('no_answer', 'لم يتم الرد');

        // Overdue lead
        $overdueLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Overdue Customer Al-Amal',
            'company_name' => 'Al-Amal Co',
            'phone' => '01011112222',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->subDays(2),
        ]);

        // Today lead
        $todayLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Today Customer Al-Nour',
            'company_name' => 'Al-Nour Co',
            'phone' => '01033334444',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->addHours(2),
        ]);

        // Upcoming lead
        $upcomingLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Upcoming Customer Al-Fajr',
            'company_name' => 'Al-Fajr Co',
            'phone' => '01055556666',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->addDays(3),
        ]);

        // No date lead
        $noDateLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'No Date Customer Al-Shams',
            'company_name' => 'Al-Shams Co',
            'phone' => '01077778888',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('v2.tasks.daily'));
        $response->assertOk();
        $response->assertSee($overdueLead->name);
        $response->assertSee($todayLead->name);
        $response->assertSee($upcomingLead->name);
        $response->assertSee($noDateLead->name);
    }

    public function test_daily_tasks_scope_filtering(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all']);
        $status = $this->createStatus('no_answer', 'لم يتم الرد');

        $overdueLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Overdue Unique Target',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->subDays(1),
        ]);

        $todayLead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Today Unique Target',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(12),
        ]);

        // Overdue scope only
        $responseOverdue = $this->actingAs($user)->get(route('v2.tasks.daily', ['scope' => 'overdue']));
        $responseOverdue->assertOk();
        $responseOverdue->assertSee($overdueLead->name);
        $responseOverdue->assertDontSee($todayLead->name);

        // Today scope only
        $responseToday = $this->actingAs($user)->get(route('v2.tasks.daily', ['scope' => 'today']));
        $responseToday->assertOk();
        $responseToday->assertSee($todayLead->name);
        $responseToday->assertDontSee($overdueLead->name);
    }

    public function test_authorized_user_can_reschedule_lead_followup(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.update', 'leads.scope.all']);
        $status = $this->createStatus('new', 'جديد');

        $lead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Customer To Reschedule',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->subDay(),
        ]);

        $newDate = now()->addDays(2)->format('Y-m-d H:i');

        $response = $this->actingAs($user)->postJson(route('v2.tasks.reschedule', $lead), [
            'next_follow_up_at' => $newDate,
            'reschedule_reason' => 'Client requested postponement to next week',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $lead->refresh();
        $this->assertSame(
            now()->addDays(2)->format('Y-m-d H:i'),
            $lead->next_follow_up_at->format('Y-m-d H:i')
        );
    }

    public function test_authorized_user_can_log_task_outcome_through_canonical_followup_endpoint(): void
    {
        $user = $this->userWithPermissions([
            'tasks.view',
            'leads.view',
            'leads.followups.create',
            'leads.update',
            'leads.scope.all',
        ]);

        $statusNew = $this->createStatus('new', 'جديد');
        $statusNoAnswer = $this->createStatus('no_answer', 'لم يتم الرد');

        $lead = Lead::query()->create([
            'lead_status_id' => $statusNew->id,
            'name' => 'Quick Followup Target',
            'phone' => '01012345678',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now(),
        ]);

        $nextFollowup = now()->addDays(3)->format('Y-m-d H:i');

        $response = $this->actingAs($user)->post(route('v2.leads.followups.store', $lead), [
            'communication_type' => 'call',
            'outcome' => 'Called client, customer confirmed high interest and requested proposal review.',
            'lead_status_id' => $statusNoAnswer->id,
            'next_follow_up_at' => $nextFollowup,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame($statusNoAnswer->id, $lead->lead_status_id);
        $this->assertSame(
            now()->addDays(3)->format('Y-m-d H:i'),
            $lead->next_follow_up_at->format('Y-m-d H:i')
        );

        $this->assertDatabaseHas('lead_followups', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'from_status_id' => $statusNew->id,
            'to_status_id' => $statusNoAnswer->id,
            'communication_type' => 'call',
        ]);
    }

    public function test_daily_tasks_calculates_progress_and_completed_today(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all']);
        $status = $this->createStatus('no_answer', 'لم يتم الرد');

        $lead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Completed Task Lead',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->addHours(1),
        ]);

        LeadFollowup::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'from_status_id' => $status->id,
            'to_status_id' => $status->id,
            'employee_name' => $user->name,
            'communication_type' => 'call',
            'outcome' => 'Completed morning check-in call with client',
            'followed_up_at' => now(),
        ]);

        $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('v2.tasks.daily'));
        $response->assertOk();
        $response->assertSee('Completed Today', false);
    }

    public function test_sidebar_highlights_daily_tasks_when_active(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all']);

        $response = $this->actingAs($user)->get(route('v2.tasks.daily'));
        $response->assertOk();
        $response->assertSee('class="active"', false);
        $response->assertSee('crmTasksMenu', false);
    }

    public function test_daily_tasks_kpi_counts_respect_search_and_status_filters(): void
    {
        $user = $this->userWithPermissions(['tasks.view', 'leads.view', 'leads.scope.all']);
        $statusA = $this->createStatus('status_a', 'حالة أ');
        $statusB = $this->createStatus('status_b', 'حالة ب');

        // Lead A
        Lead::query()->create([
            'lead_status_id' => $statusA->id,
            'name' => 'Target Alpha',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->subDay(),
        ]);

        // Lead B
        Lead::query()->create([
            'lead_status_id' => $statusB->id,
            'name' => 'Target Beta',
            'assigned_user_id' => $user->id,
            'next_follow_up_at' => now()->subDay(),
        ]);

        // Search for Alpha only
        $responseSearch = $this->actingAs($user)->get(route('v2.tasks.daily', ['search' => 'Alpha']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Target Alpha');
        $responseSearch->assertDontSee('Target Beta');

        // Filter by status B only
        $responseStatus = $this->actingAs($user)->get(route('v2.tasks.daily', ['status_id' => $statusB->id]));
        $responseStatus->assertOk();
        $responseStatus->assertSee('Target Beta');
        $responseStatus->assertDontSee('Target Alpha');

        // Filter by stage of status A only
        $responseStage = $this->actingAs($user)->get(route('v2.tasks.daily', ['stage_id' => $statusA->pipeline_stage_id]));
        $responseStage->assertOk();
        $responseStage->assertSee('Target Alpha');
        $responseStage->assertDontSee('Target Beta');
        $responseStage->assertSee('name="stage_id"', false);
        $responseStage->assertDontSee('name="status_id"', false);
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Task Test Group '.fake()->unique()->word(),
            'code' => 'task-test-group-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->where('code', $code)->first();
            if (! $permission) {
                $permission = Permission::query()->create([
                    'code' => $code,
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ]);
            }
            $group->permissions()->attach($permission);
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    private function createStatus(string $code, string $nameAr): LeadStatus
    {
        $nextStagePos = ((int) PipelineStage::query()->max('position')) + 1;
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'stage_'.$code],
            ['name_ar' => 'مرحلة '.$nameAr, 'position' => $nextStagePos]
        );

        $nextStatusPos = ((int) LeadStatus::query()->max('position')) + 1;
        return LeadStatus::query()->firstOrCreate(
            ['code' => $code],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => $nameAr,
                'position' => $nextStatusPos,
                'color' => '#3b82f6',
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IdorAndTamperingAttackTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchA;
    private Branch $branchB;
    private User $userA;
    private User $userB;
    private PipelineStage $stage;
    private LeadStatus $status;
    private Lead $leadA;
    private Lead $leadB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::query()->firstOrCreate(
            ['code' => 'branch_idor_a'],
            ['name_ar' => 'فرع الهجوم أ', 'name_en' => 'Branch IDOR A', 'is_active' => true],
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_idor_b'],
            ['name_ar' => 'فرع الهجوم ب', 'name_en' => 'Branch IDOR B', 'is_active' => true],
        );

        $this->userA = $this->createActorWithPermissions([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'leads.export', 'leads.import',
            'tasks.view', 'campaigns.view', 'campaigns.create', 'campaigns.reports',
            'calendar.view', 'calendar.manage', 'collections.view', 'collections.manage',
            'collections.assign', 'collections.collect', 'collections.complete', 'collections.cancel',
            'voip.view', 'reports.view', 'settings.access', 'users.view', 'users.update',
            'users.activate', 'users.reset_password', 'groups.view', 'groups.update',
            'groups.delete', 'groups.assign_permissions', 'branches.view', 'branches.update',
            'branches.delete', 'notifications.manage',
        ], $this->branchA);

        $this->userB = $this->createActorWithPermissions([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'leads.export', 'leads.import',
            'tasks.view', 'campaigns.view', 'campaigns.create', 'campaigns.reports',
            'calendar.view', 'calendar.manage', 'collections.view', 'collections.manage',
            'collections.assign', 'collections.collect', 'collections.complete', 'collections.cancel',
            'voip.view', 'reports.view',
        ], $this->branchB);

        $pos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stage = PipelineStage::query()->create([
            'code' => 'stage_idor_' . uniqid(),
            'name_ar' => 'مرحلة اختبار الثغرات',
            'position' => $pos,
            'color' => '#dc2626',
            'is_active' => true,
        ]);

        $stPos = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->status = LeadStatus::query()->create([
            'code' => 'status_idor_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة اختبار الثغرات',
            'position' => $stPos,
            'is_terminal' => false,
        ]);

        $this->leadA = $this->createLead($this->userA, $this->status, $this->branchA, 'عميل الضحية أ');
        $this->leadB = $this->createLead($this->userB, $this->status, $this->branchB, 'عميل الهدف ب');
    }

    public function test_idor_leads_module_vectors(): void
    {
        // 1. Show foreign lead -> 200 OK with cross-branch read-only view
        $this->actingAs($this->userA)->get(route('v2.leads.show', $this->leadB))->assertOk()->assertSee($this->leadB->name);

        // 2. Edit foreign lead -> 403
        $this->actingAs($this->userA)->get(route('v2.leads.edit', $this->leadB))->assertForbidden();

        // 3. Update foreign lead -> 403 & DB unchanged
        $bOriginalName = $this->leadB->name;
        $this->actingAs($this->userA)->patch(route('v2.leads.update', $this->leadB), [
            'name' => 'اسم مخترق',
        ])->assertForbidden();
        $this->assertSame($bOriginalName, $this->leadB->fresh()->name);

        // 4. Destroy foreign lead -> 403 & DB unchanged
        $this->actingAs($this->userA)->delete(route('v2.leads.destroy', $this->leadB))->assertForbidden();
        $this->assertNotNull($this->leadB->fresh());

        // 5. Foreign lead followups index -> 403
        $this->actingAs($this->userA)->get(route('v2.leads.followups.index', $this->leadB))->assertForbidden();

        // 6. Foreign lead followups store -> 403
        $this->actingAs($this->userA)->post(route('v2.leads.followups.store', $this->leadB), [
            'lead_status_id' => $this->status->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة غير مصرحة',
        ])->assertForbidden();

        // 7. Foreign lead calls history -> 403
        $this->actingAs($this->userA)->getJson(route('v2.leads.calls', $this->leadB))->assertForbidden();

        // 8. Foreign lead reschedule through tasks endpoint -> 403
        $this->actingAs($this->userA)->post(route('v2.tasks.reschedule', $this->leadB), [
            'next_follow_up_at' => now()->addDays(5)->format('Y-m-d H:i'),
        ])->assertForbidden();

        // 9. Foreign lead reschedule through calendar endpoint -> 403
        $this->actingAs($this->userA)->patchJson(route('v2.calendar.lead.reschedule', $this->leadB), [
            'start_time' => now()->addDays(5)->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_idor_campaigns_module_vectors(): void
    {
        $campaignB = Campaign::query()->create([
            'branch_id' => $this->branchB->id,
            'name' => 'حملة الفرع ب المستهدفة',
            'cost' => 10000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'created_by_user_id' => $this->userB->id,
        ]);
        $campaignB->users()->attach($this->userB->id);

        // 10. Show foreign campaign -> 403
        $this->actingAs($this->userA)->get(route('v2.campaigns.show', $campaignB))->assertForbidden();

        // 11. Edit foreign campaign -> 403
        $this->actingAs($this->userA)->get(route('v2.campaigns.edit', $campaignB))->assertForbidden();

        // 12. Update foreign campaign -> 403 & DB unchanged
        $this->actingAs($this->userA)->patch(route('v2.campaigns.update', $campaignB), [
            'name' => 'تعديل حملة غير مصرح',
            'cost' => 1,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(10)->toDateTimeString(),
            'user_ids' => [$this->userA->id],
        ])->assertForbidden();
        $this->assertSame('حملة الفرع ب المستهدفة', $campaignB->fresh()->name);

        // 13. Destroy foreign campaign -> 403 & DB unchanged
        $this->actingAs($this->userA)->delete(route('v2.campaigns.destroy', $campaignB))->assertForbidden();
        $this->assertNotNull($campaignB->fresh());
    }

    public function test_idor_calendar_events_module_vectors(): void
    {
        $eventB = CalendarEvent::query()->create([
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'lead_id' => $this->leadB->id,
            'title' => 'موعد خاص بفرع ب',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(3),
        ]);

        // 14. Show foreign calendar event -> 403
        $this->actingAs($this->userA)->getJson(route('v2.calendar.show', $eventB))->assertForbidden();

        // 15. Update foreign calendar event -> 403 & DB unchanged
        $this->actingAs($this->userA)->patchJson(route('v2.calendar.update', $eventB), [
            'title' => 'عنوان مخترق',
        ])->assertForbidden();
        $this->assertSame('موعد خاص بفرع ب', $eventB->fresh()->title);

        // 16. Reschedule foreign calendar event -> 403 & DB unchanged
        $this->actingAs($this->userA)->patchJson(route('v2.calendar.reschedule', $eventB), [
            'start_time' => now()->addDays(3)->toDateTimeString(),
            'end_time' => now()->addDays(3)->addHour()->toDateTimeString(),
        ])->assertForbidden();

        // 17. Destroy foreign calendar event -> 403 & DB unchanged
        $this->actingAs($this->userA)->deleteJson(route('v2.calendar.destroy', $eventB))->assertForbidden();
        $this->assertNotNull($eventB->fresh());
    }

    public function test_idor_collections_module_vectors(): void
    {
        $caseB = CollectionCase::query()->create([
            'branch_id' => $this->branchB->id,
            'lead_id' => $this->leadB->id,
            'assigned_collector_user_id' => $this->userB->id,
            'created_by_user_id' => $this->userB->id,
            'status' => 'pending',
            'donation_type' => 'cash',
            'expected_amount' => 1500.00,
            'cycle' => 'once',
            'collection_address' => 'شارع الإسكندرية',
            'due_at' => now()->addHours(4),
        ]);

        // 18. Show foreign collection case -> 403
        $this->actingAs($this->userA)->get(route('v2.collections.show', $caseB))->assertForbidden();

        // 19. Assign foreign collection case -> 403 & DB unchanged
        $this->actingAs($this->userA)->patch(route('v2.collections.assign', $caseB), [
            'assigned_collector_user_id' => $this->userA->id,
        ])->assertForbidden();
        $this->assertSame($this->userB->id, $caseB->fresh()->assigned_collector_user_id);

        // 20. Reschedule foreign collection case -> 403
        $this->actingAs($this->userA)->patch(route('v2.collections.reschedule', $caseB), [
            'due_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertForbidden();

        // 21. Fail foreign collection case -> 403
        $this->actingAs($this->userA)->patch(route('v2.collections.fail', $caseB), [
            'notes' => 'محاولة فاشلة غير مصرحة',
        ])->assertForbidden();

        // 22. Complete foreign collection case -> 403 & DB unchanged
        $this->actingAs($this->userA)->post(route('v2.collections.complete', $caseB), [
            'collected_amount' => 1500,
        ])->assertForbidden();
        $this->assertSame('pending', $caseB->fresh()->status);

        // 23. Cancel foreign collection case -> 403 & DB unchanged
        $this->actingAs($this->userA)->patch(route('v2.collections.cancel', $caseB), [
            'notes' => 'محاولة إلغاء غير مصرحة',
        ])->assertForbidden();
        $this->assertSame('pending', $caseB->fresh()->status);

        // 24. Receipt preview of foreign case -> 403
        $this->actingAs($this->userA)->get(route('v2.collections.receipt', $caseB))->assertForbidden();

        // 25. Receipt download of foreign case -> 403
        $this->actingAs($this->userA)->get(route('v2.collections.receipt.download', $caseB))->assertForbidden();
    }

    public function test_idor_nonexistent_ids_fail_safely(): void
    {
        // 26. Nonexistent lead -> 404
        $this->actingAs($this->userA)->get('/leads/99999999')->assertNotFound();

        // 27. Nonexistent calendar event -> 404
        $this->actingAs($this->userA)->getJson('/calendar/events/99999999')->assertNotFound();

        // 28. Nonexistent collection case -> 404
        $this->actingAs($this->userA)->get('/collections/99999999')->assertNotFound();

        // 29. Nonexistent campaign -> 404
        $this->actingAs($this->userA)->get('/campaigns/99999999')->assertNotFound();

        // 30. Nonexistent user settings -> 404
        $this->actingAs($this->userA)->get('/settings/users/99999999/edit')->assertNotFound();

        // 31. Nonexistent branch settings -> 404
        $this->actingAs($this->userA)->get('/settings/branches/99999999/edit')->assertNotFound();

        // 32. Nonexistent group settings -> 404
        $this->actingAs($this->userA)->get('/settings/groups/99999999/edit')->assertNotFound();
    }

    public function test_bulk_array_tampering_is_safely_rejected(): void
    {
        // Submitting mixed array containing authorized lead and foreign lead in export-selected
        $this->actingAs($this->userA)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->leadA->id, $this->leadB->id],
        ])->assertStatus(422);
    }

    public function test_parameter_tampering_with_branch_id_is_scoped_or_ignored(): void
    {
        // User A tries to create lead passing Branch B's branch_id
        $response = $this->actingAs($this->userA)->post(route('v2.leads.store'), [
            'branch_id' => $this->branchB->id,
            'name' => 'عميل محاولة تزوير الفرع',
            'phone' => '01055554444',
            'lead_status_id' => $this->status->id,
        ]);

        $createdLead = Lead::query()->where('phone', '01055554444')->first();
        $this->assertNotNull($createdLead);
        // Server side must have overridden/scoped branch to User A's branch
        $this->assertSame((int) $this->branchA->id, (int) $createdLead->branch_id);
    }

    private function createLead(User $user, LeadStatus $status, Branch $branch, string $name): Lead
    {
        $lead = Lead::query()->create([
            'branch_id' => $branch->id,
            'lead_status_id' => $status->getKey(),
            'name' => $name,
            'phone' => '010' . rand(10000000, 99999999),
            'assigned_user_id' => $user->getKey(),
            'created_by_user_id' => $user->getKey(),
            'next_follow_up_at' => now()->addDay(),
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
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

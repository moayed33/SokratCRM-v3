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
use App\Models\NotificationOccurrence;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdversarialSecurityPassTwoTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchX;
    private Branch $branchY;
    private User $agentX;
    private User $agentY;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchX = Branch::query()->firstOrCreate(
            ['code' => 'branch_x_adv'],
            ['name_ar' => 'فرع الخصم سين', 'name_en' => 'Branch Adversarial X', 'is_active' => true],
        );

        $this->branchY = Branch::query()->firstOrCreate(
            ['code' => 'branch_y_adv'],
            ['name_ar' => 'فرع الخصم صاد', 'name_en' => 'Branch Adversarial Y', 'is_active' => true],
        );

        $this->agentX = $this->createActor([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'leads.export', 'leads.import',
            'tasks.view', 'campaigns.view', 'campaigns.create', 'calendar.view', 'calendar.manage',
            'collections.view', 'collections.manage', 'collections.assign', 'collections.collect',
            'collections.complete', 'voip.view', 'voip.recordings',
        ], $this->branchX);
        $this->agentY = $this->createActor([
            'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.delete',
            'leads.followups.view', 'leads.followups.create', 'tasks.view', 'campaigns.view',
            'campaigns.create', 'calendar.view', 'calendar.manage', 'collections.view',
            'collections.manage', 'collections.assign', 'collections.collect', 'collections.complete',
            'voip.view', 'voip.recordings',
        ], $this->branchY);

        $pos1 = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stageA = PipelineStage::query()->create([
            'code' => 'stage_adv_a_' . uniqid(),
            'name_ar' => 'المرحلة الهجومية الأولى',
            'position' => $pos1,
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $pos2 = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stageB = PipelineStage::query()->create([
            'code' => 'stage_adv_b_' . uniqid(),
            'name_ar' => 'المرحلة الهجومية الثانية',
            'position' => $pos2,
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $stPos1 = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->statusA = LeadStatus::query()->create([
            'code' => 'status_adv_a_' . uniqid(),
            'pipeline_stage_id' => $this->stageA->id,
            'name_ar' => 'الحالة الهجومية الأولى',
            'position' => $stPos1,
            'is_terminal' => false,
        ]);

        $stPos2 = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->statusB = LeadStatus::query()->create([
            'code' => 'status_adv_b_' . uniqid(),
            'pipeline_stage_id' => $this->stageB->id,
            'name_ar' => 'الحالة الهجومية الثانية',
            'position' => $stPos2,
            'is_terminal' => false,
        ]);
    }

    public function test_1_adversarial_lead_stage_transition_cannot_cross_branches(): void
    {
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'عميل صاد المستهدف');

        // Agent X attempts to transition Lead Y to Stage B -> must be 403 Forbidden
        $this->actingAs($this->agentX)->post(route('v2.leads.followups.store', $leadY), [
            'lead_status_id' => $this->statusB->id,
            'communication_type' => 'call',
            'outcome' => 'محاولة نقل غير مصرحة',
        ])->assertForbidden();

        // Ensure DB state untouched
        $this->assertSame((int) $this->statusA->id, (int) $leadY->fresh()->lead_status_id);
    }

    public function test_2_adversarial_stage_questions_and_fields_submission_rejected_for_inaccessible_lead(): void
    {
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'عميل صاد للحقول');

        // Agent X attempts to update stage fields on Lead Y -> 403 Forbidden
        $this->actingAs($this->agentX)->patch(route('v2.leads.update', $leadY), [
            'lead_status_id' => $this->statusB->id,
            'stage_fields' => ['custom_note' => 'قيمة خبيثة'],
        ])->assertForbidden();

        $this->assertSame((int) $this->statusA->id, (int) $leadY->fresh()->lead_status_id);
    }

    public function test_3_adversarial_campaign_lead_assignment_blocks_foreign_user_and_foreign_lead(): void
    {
        $campaignX = Campaign::query()->create([
            'branch_id' => $this->branchX->id,
            'name' => 'حملة سين',
            'cost' => 3000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'created_by_user_id' => $this->agentX->id,
        ]);
        $campaignX->users()->attach($this->agentX->id);

        $leadX = $this->createLead($this->agentX, $this->statusA, $this->branchX, 'عميل سين 1');
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'عميل صاد محمي');

        $campaignX->leads()->attach($leadX->id);

        // Attempt to assign foreign Lead Y via Campaign X -> lead Y is not in campaign and belongs to foreign branch
        $this->actingAs($this->agentX)->patch(route('v2.campaigns.leads.assign', $campaignX), [
            'lead_ids' => [$leadX->id, $leadY->id],
            'target_user_id' => $this->agentX->id,
        ]);

        // Lead Y must NOT have been assigned to Agent X
        $this->assertSame((int) $this->agentY->id, (int) $leadY->fresh()->assigned_user_id);
    }

    public function test_4_adversarial_notification_payload_does_not_leak_metadata_for_unauthorized_viewer(): void
    {
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'عميل سري جداً صاد');

        // Notification for Agent Y
        $notifId = (string) \Illuminate\Support\Str::uuid();
        $this->agentY->notifications()->create([
            'id' => $notifId,
            'type' => 'App\Notifications\CrmReminderNotification',
            'data' => [
                'title' => 'متابعة سرية',
                'body' => 'تفاصيل سرية لفرع صاد',
                'priority' => 'urgent',
                'event_key' => 'followup.due',
                'source_kind' => 'lead_followup',
                'source_id' => $leadY->id,
                'lead_id' => $leadY->id,
                'lead_name' => 'عميل سري جداً صاد',
                'action_url' => '/leads/' . $leadY->id,
            ],
            'read_at' => null,
            'created_at' => now(),
        ]);

        // Agent X queries notifications -> MUST NOT receive Agent Y's notification
        $responseX = $this->actingAs($this->agentX)->getJson(route('v2.notifications.index'));
        $responseX->assertOk();
        $itemsX = collect($responseX->json('data'));
        $this->assertFalse($itemsX->contains('id', $notifId));
        $this->assertFalse($itemsX->contains('lead_name', 'عميل سري جداً صاد'));
    }

    public function test_5_adversarial_collection_case_actions_blocked_for_cross_branch_collector(): void
    {
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'متبرع صاد');

        $caseY = CollectionCase::query()->create([
            'branch_id' => $this->branchY->id,
            'lead_id' => $leadY->id,
            'assigned_collector_user_id' => $this->agentY->id,
            'created_by_user_id' => $this->agentY->id,
            'status' => 'pending',
            'donation_type' => 'cash',
            'expected_amount' => 5000.00,
            'cycle' => 'once',
            'collection_address' => 'عنوان فرع صاد',
            'due_at' => now()->addDay(),
        ]);

        // Agent X from Branch X attempts actions on Case Y from Branch Y -> all must be 403
        $this->actingAs($this->agentX)->get(route('v2.collections.show', $caseY))->assertForbidden();
        $this->actingAs($this->agentX)->patch(route('v2.collections.assign', $caseY), ['assigned_collector_user_id' => $this->agentX->id])->assertForbidden();
        $this->actingAs($this->agentX)->patch(route('v2.collections.reschedule', $caseY), ['due_at' => now()->addDays(3)->format('Y-m-d\TH:i')])->assertForbidden();
        $this->actingAs($this->agentX)->post(route('v2.collections.complete', $caseY), ['collected_amount' => 5000])->assertForbidden();
        $this->actingAs($this->agentX)->patch(route('v2.collections.cancel', $caseY), ['notes' => 'محاولة إلغاء'])->assertForbidden();

        // State remains strictly pending and assigned to Agent Y
        $caseFresh = $caseY->fresh();
        $this->assertSame('pending', $caseFresh->status);
        $this->assertSame((int) $this->agentY->id, (int) $caseFresh->assigned_collector_user_id);
    }

    public function test_6_adversarial_voip_recording_requires_valid_signed_url(): void
    {
        // Unsigned request without signature -> 403 Invalid Signature
        $this->actingAs($this->agentX)
            ->get('/voip/recordings/test-media-id-123')
            ->assertForbidden();
    }

    public function test_7_adversarial_mixed_array_in_export_selected_aborts_without_leakage(): void
    {
        $leadX = $this->createLead($this->agentX, $this->statusA, $this->branchX, 'عميل سين متاح');
        $leadY = $this->createLead($this->agentY, $this->statusA, $this->branchY, 'عميل صاد محظور');

        // Submitting mixed array [leadX, leadY]
        $this->actingAs($this->agentX)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$leadX->id, $leadY->id],
        ])->assertStatus(422);
    }

    public function test_8_adversarial_sidebar_for_minimal_actor_hides_all_privileged_sections(): void
    {
        $minimalGroup = Group::query()->create([
            'name' => 'Minimal Group ' . uniqid(),
            'code' => 'minimal-' . uniqid(),
            'is_system' => false,
        ]);

        $permDashboard = Permission::query()->where('code', 'dashboard.view')->first()
            ?? Permission::query()->create(['code' => 'dashboard.view', 'name_ar' => 'dashboard.view', 'module' => 'dashboard']);
        $minimalGroup->permissions()->attach($permDashboard);

        $minimalUser = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branchX->id,
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
        $minimalUser->groups()->attach($minimalGroup);

        $response = $this->actingAs($minimalUser)->get(route('dashboard'));
        $response->assertOk();

        // Dashboard is visible
        $response->assertSee('href="' . route('dashboard') . '"', false);

        // All other modules must NOT be rendered in sidebar
        $response->assertDontSee('crmLeadsMenu');
        $response->assertDontSee('crmTasksMenu');
        $response->assertDontSee('crmCampaignsMenu');
        $response->assertDontSee('href="' . route('v2.leads') . '"', false);
        $response->assertDontSee('href="' . route('v2.leads.kanban') . '"', false);
        $response->assertDontSee('href="' . route('v2.calendar.index') . '"', false);
        $response->assertDontSee('href="' . route('v2.collections.index') . '"', false);
        $response->assertDontSee('href="' . route('v2.settings') . '"', false);
        $response->assertDontSee('href="' . route('v2.voip.live') . '"', false);
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
    private function createActor(array $permissionCodes, Branch $branch): User
    {
        $group = Group::query()->create([
            'name' => 'Adversarial Group ' . fake()->unique()->word(),
            'code' => 'adv-' . fake()->unique()->slug(),
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

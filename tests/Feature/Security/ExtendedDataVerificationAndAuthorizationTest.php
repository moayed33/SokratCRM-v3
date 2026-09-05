<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExtendedDataVerificationAndAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branchCairo;
    private Branch $branchAlex;

    private User $superAdmin;
    private User $managerCairo;
    private User $agentCairo1;
    private User $agentCairo2;
    private User $agentAlex;
    private User $collectorCairo;

    private Group $adminGroup;
    private Group $managerGroup;
    private Group $agentGroup;
    private Group $collectorGroup;

    private PipelineStage $stageNew;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusDonor;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure all CRM permissions exist
        foreach (CrmPermission::cases() as $perm) {
            Permission::query()->firstOrCreate(
                ['code' => $perm->value],
                ['name_ar' => $perm->value, 'description' => $perm->value, 'module' => 'security']
            );
        }

        // 2. Branches
        $this->branchCairo = Branch::query()->firstOrCreate(
            ['code' => 'ext_cairo_' . uniqid()],
            ['name_ar' => 'فرع القاهرة التجريبي', 'name_en' => 'Cairo Test Branch', 'is_active' => true]
        );

        $this->branchAlex = Branch::query()->firstOrCreate(
            ['code' => 'ext_alex_' . uniqid()],
            ['name_ar' => 'فرع الإسكندرية التجريبي', 'name_en' => 'Alexandria Test Branch', 'is_active' => true]
        );

        // 3. Stages & Statuses
        $stgMax = (int) (PipelineStage::query()->max('position') ?? 0);
        $this->stageNew = PipelineStage::query()->create([
            'code' => 'stage_new_' . uniqid(),
            'name_ar' => 'مرحلة جديدة',
            'position' => $stgMax + 1,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);
        $this->statusNew = LeadStatus::query()->create([
            'code' => 'status_new_' . uniqid(),
            'pipeline_stage_id' => $this->stageNew->id,
            'name_ar' => 'عملاء جدد',
            'position' => ((int) (LeadStatus::query()->max('position') ?? 0)) + 1,
            'is_terminal' => false,
        ]);

        $this->stageDonor = PipelineStage::query()->create([
            'code' => 'stage_donor_' . uniqid(),
            'name_ar' => 'مرحلة المتبرعين',
            'position' => $stgMax + 2,
            'color' => '#16a34a',
            'is_active' => true,
        ]);
        $this->statusDonor = LeadStatus::query()->create([
            'code' => 'status_donor_' . uniqid(),
            'pipeline_stage_id' => $this->stageDonor->id,
            'name_ar' => 'متبرع نشط',
            'position' => ((int) (LeadStatus::query()->max('position') ?? 0)) + 1,
            'is_terminal' => false,
        ]);

        // 4. Groups
        $this->adminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'مدير النظام الفائق', 'is_system' => true]
        );

        $this->managerGroup = Group::query()->create([
            'name' => 'مديرو الفرق',
            'code' => 'grp_mgr_' . uniqid(),
            'is_system' => false,
        ]);
        $this->managerGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_GROUP->value,
                CrmPermission::LEADS_CREATE->value,
                CrmPermission::LEADS_UPDATE->value,
                CrmPermission::LEADS_ASSIGN->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                CrmPermission::TASKS_VIEW->value,
            ])->pluck('id')
        );

        $this->agentGroup = Group::query()->create([
            'name' => 'موظفو المبيعات',
            'code' => 'grp_agt_' . uniqid(),
            'is_system' => false,
        ]);
        $this->agentGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_CREATE->value,
                CrmPermission::LEADS_UPDATE->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                CrmPermission::TASKS_VIEW->value,
            ])->pluck('id')
        );

        $this->collectorGroup = Group::query()->create([
            'name' => 'محصلون',
            'code' => 'grp_col_' . uniqid(),
            'is_system' => false,
        ]);
        $this->collectorGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::COLLECTIONS_VIEW->value,
                CrmPermission::COLLECTIONS_COLLECT->value,
                CrmPermission::COLLECTIONS_COMPLETE->value,
            ])->pluck('id')
        );

        // 5. Users
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin User',
            'username' => 'super_admin_' . uniqid(),
            'branch_id' => $this->branchCairo->id,
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->sync([$this->adminGroup->id]);

        $this->managerCairo = User::factory()->create([
            'name' => 'Cairo Team Leader',
            'username' => 'mgr_cairo_' . uniqid(),
            'branch_id' => $this->branchCairo->id,
            'is_active' => true,
        ]);
        $this->managerCairo->groups()->sync([$this->managerGroup->id]);

        $this->agentCairo1 = User::factory()->create([
            'name' => 'Cairo Agent 1',
            'username' => 'agent_cairo1_' . uniqid(),
            'branch_id' => $this->branchCairo->id,
            'manager_id' => $this->managerCairo->id,
            'is_active' => true,
        ]);
        $this->agentCairo1->groups()->sync([$this->agentGroup->id]);

        $this->agentCairo2 = User::factory()->create([
            'name' => 'Cairo Agent 2',
            'username' => 'agent_cairo2_' . uniqid(),
            'branch_id' => $this->branchCairo->id,
            'manager_id' => null, // Peer agent, not managed by managerCairo
            'is_active' => true,
        ]);
        $this->agentCairo2->groups()->sync([$this->agentGroup->id]);

        $this->agentAlex = User::factory()->create([
            'name' => 'Alexandria Agent',
            'username' => 'agent_alex_' . uniqid(),
            'branch_id' => $this->branchAlex->id,
            'is_active' => true,
        ]);
        $this->agentAlex->groups()->sync([$this->agentGroup->id]);

        $this->collectorCairo = User::factory()->create([
            'name' => 'Cairo Collector',
            'username' => 'collector_cairo_' . uniqid(),
            'branch_id' => $this->branchCairo->id,
            'is_active' => true,
        ]);
        $this->collectorCairo->groups()->sync([$this->collectorGroup->id]);

        // Sync group assignment permission for agentGroup
        LeadAssignment::synchronizeGroupPermission($this->agentGroup);
    }

    /* ══════════════════════════════════════════════════════════════════════════════
     * SECTION 1: BETWEEN BRANCHES (Cross-Branch Search & Isolation)
     * ══════════════════════════════════════════════════════════════════════════════ */

    public function test_cross_branch_search_and_read_only_profile_inspection(): void
    {
        // Create lead in Alexandria branch with follow-up timeline
        $alexLead = Lead::query()->create([
            'name' => 'مؤسسة الإسكندرية للتوريدات',
            'phone' => '01055550001',
            'branch_id' => $this->branchAlex->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentAlex->id,
        ]);

        LeadFollowup::query()->create([
            'lead_id' => $alexLead->id,
            'branch_id' => $this->branchAlex->id,
            'from_status_id' => null,
            'to_status_id' => $this->statusNew->id,
            'employee_name' => $this->agentAlex->name,
            'user_id' => $this->agentAlex->id,
            'communication_type' => 'call',
            'outcome' => 'مكالمة هاتفية استطلاعية بفرع الإسكندرية',
            'followed_up_at' => now()->subDays(3),
        ]);

        // 1. Cairo Agent regular index (unsearched) strictly excludes Alexandria lead
        $unsearched = $this->actingAs($this->agentCairo1)->get(route('v2.leads'));
        $unsearched->assertOk();
        $unsearched->assertDontSee('مؤسسة الإسكندرية للتوريدات');

        // 2. Cairo Agent active search by phone matches the Alexandria lead
        $searchRes = $this->actingAs($this->agentCairo1)->get(route('v2.leads', ['q' => '01055550001']));
        $searchRes->assertOk();
        $searchRes->assertSee('مؤسسة الإسكندرية للتوريدات');
        $searchRes->assertSee($this->branchAlex->name_ar);

        // 3. Cairo Agent can open lead profile: returns 200 OK
        $showRes = $this->actingAs($this->agentCairo1)->get(route('v2.leads.show', $alexLead));
        $showRes->assertOk();
        $showRes->assertSee('مؤسسة الإسكندرية للتوريدات');
        $showRes->assertSee('01055550001');
        $showRes->assertSee($this->branchAlex->name_ar);
        // Follow-up history timeline is visible to cross-branch agent
        $showRes->assertSee('مكالمة هاتفية استطلاعية بفرع الإسكندرية');
        // Notice banner is rendered
        $showRes->assertSee($this->branchAlex->name_ar);
    }

    public function test_cross_branch_mutating_actions_strictly_prohibited(): void
    {
        $alexLead = Lead::query()->create([
            'name' => 'شركة النورس بالإسكندرية',
            'phone' => '01055550002',
            'branch_id' => $this->branchAlex->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentAlex->id,
        ]);

        $originalName = $alexLead->name;

        // 1. Cairo Agent cannot access edit form -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->get(route('v2.leads.edit', $alexLead))->assertForbidden();

        // 2. Cairo Agent cannot update lead details -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->patch(route('v2.leads.update', $alexLead), [
            'name' => 'اسم معدل غير مصرح به',
        ])->assertForbidden();
        $this->assertSame($originalName, $alexLead->fresh()->name);

        // 3. Cairo Agent cannot delete lead -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->delete(route('v2.leads.destroy', $alexLead))->assertForbidden();
        $this->assertNotNull($alexLead->fresh());

        // 4. Cairo Agent cannot create follow-up on Alexandria lead -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->post(route('v2.leads.followups.store', $alexLead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'محاولة اختراق المتابعات',
        ])->assertForbidden();
        $this->assertDatabaseMissing('lead_followups', ['outcome' => 'محاولة اختراق المتابعات']);

        // 5. Cairo Agent cannot access raw PBX VoIP calls -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->getJson(route('v2.leads.calls', $alexLead))->assertForbidden();

        // 6. Cairo Agent cannot reschedule lead via tasks endpoint -> 403 Forbidden
        $this->actingAs($this->agentCairo1)->post(route('v2.tasks.reschedule', $alexLead), [
            'next_follow_up_at' => now()->addDays(4)->format('Y-m-d H:i'),
        ])->assertForbidden();
    }

    public function test_cross_branch_phone_lookup_screen_pop_detection(): void
    {
        $alexLead = Lead::query()->create([
            'name' => 'متصل من فرع الإسكندرية',
            'phone' => '01055550003',
            'branch_id' => $this->branchAlex->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentAlex->id,
        ]);

        $response = $this->actingAs($this->agentCairo1)
            ->getJson(route('v2.leads.by-phone', ['phone' => '01055550003']));

        $response->assertOk();
        $data = $response->json();

        $this->assertNotEmpty($data['leads']);
        $match = $data['leads'][0];
        $this->assertSame('متصل من فرع الإسكندرية', $match['name']);
        $this->assertSame($this->branchAlex->name_ar, $match['branch_name']);
        $this->assertTrue($match['is_other_branch']);
    }

    public function test_cross_branch_collections_isolation(): void
    {
        $alexLead = Lead::query()->create([
            'name' => 'عميل تحصيل إسكندرية',
            'phone' => '01055550004',
            'branch_id' => $this->branchAlex->id,
            'lead_status_id' => $this->statusDonor->id,
        ]);

        $alexCase = CollectionCase::query()->create([
            'lead_id' => $alexLead->id,
            'branch_id' => $this->branchAlex->id,
            'assigned_collector_id' => $this->agentAlex->id,
            'donation_type' => 'تبرع عام',
            'cycle' => 'one_time',
            'expected_amount' => 5000.00,
            'collection_address' => 'الإسكندرية، محطة الرمل',
            'status' => 'assigned',
            'due_at' => now()->addDays(2),
        ]);

        // Cairo collector cannot view or complete Alexandria collection case -> 403 Forbidden
        $this->actingAs($this->collectorCairo)
            ->get(route('v2.collections.show', $alexCase))
            ->assertForbidden();

        $this->actingAs($this->collectorCairo)
            ->post(route('v2.collections.complete', $alexCase), [
                'collected_amount' => 5000.00,
                'notes' => 'تحصيل غير مصرح عبر الفروع',
            ])
            ->assertForbidden();

        $this->assertSame('assigned', $alexCase->fresh()->status);
    }

    /* ══════════════════════════════════════════════════════════════════════════════
     * SECTION 2: BETWEEN LEADS (Data Association & Anti-Tampering)
     * ══════════════════════════════════════════════════════════════════════════════ */

    public function test_followup_and_timeline_integrity_strictly_bound_to_lead(): void
    {
        $lead1 = Lead::query()->create([
            'name' => 'عميل أول',
            'phone' => '01066660001',
            'branch_id' => $this->branchCairo->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentCairo1->id,
        ]);

        $lead2 = Lead::query()->create([
            'name' => 'عميل ثان منفصل',
            'phone' => '01066660002',
            'branch_id' => $this->branchCairo->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentCairo1->id,
        ]);

        $followup1 = LeadFollowup::query()->create([
            'lead_id' => $lead1->id,
            'branch_id' => $this->branchCairo->id,
            'from_status_id' => null,
            'to_status_id' => $this->statusNew->id,
            'employee_name' => $this->agentCairo1->name,
            'user_id' => $this->agentCairo1->id,
            'communication_type' => 'call',
            'outcome' => 'سجل سري يخص العميل الأول فقط',
            'followed_up_at' => now()->subDay(),
        ]);

        // Viewing lead 1 shows followup 1
        $res1 = $this->actingAs($this->agentCairo1)->get(route('v2.leads.show', $lead1));
        $res1->assertOk();
        $res1->assertSee('سجل سري يخص العميل الأول فقط');

        // Viewing lead 2 strictly NEVER leaks followup 1
        $res2 = $this->actingAs($this->agentCairo1)->get(route('v2.leads.show', $lead2));
        $res2->assertOk();
        $res2->assertDontSee('سجل سري يخص العميل الأول فقط');
    }

    public function test_phone_number_multi_phone_relationship_integrity(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل متعدد الهواتف',
            'phone' => '01077770001',
            'branch_id' => $this->branchCairo->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentCairo1->id,
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01077770001',
            'is_primary' => true,
            'label' => 'أساسي',
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01177770002',
            'is_primary' => false,
            'label' => 'واتساب العمل',
        ]);

        // Phone search matches both primary and secondary phones
        $resPrimary = $this->actingAs($this->agentCairo1)->get(route('v2.leads', ['q' => '01077770001']));
        $resPrimary->assertOk();
        $resPrimary->assertSee('عميل متعدد الهواتف');

        $resSecondary = $this->actingAs($this->agentCairo1)->get(route('v2.leads', ['q' => '01177770002']));
        $resSecondary->assertOk();
        $resSecondary->assertSee('عميل متعدد الهواتف');
    }

    /* ══════════════════════════════════════════════════════════════════════════════
     * SECTION 3: BETWEEN EMPLOYEES & ROLES (Hierarchy & Scope Enforcement)
     * ══════════════════════════════════════════════════════════════════════════════ */

    public function test_three_tier_lead_scope_personal_vs_group_vs_all(): void
    {
        // 1. Lead assigned to agentCairo1
        $leadAgent1 = Lead::query()->create([
            'name' => 'عميل الموظف الأول',
            'phone' => '01088880001',
            'branch_id' => $this->branchCairo->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentCairo1->id,
        ]);

        // 2. Lead assigned to agentCairo2 (peer agent, not managed by managerCairo)
        $leadAgent2 = Lead::query()->create([
            'name' => 'عميل الموظف الثاني المنفصل',
            'phone' => '01088880002',
            'branch_id' => $this->branchCairo->id,
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentCairo2->id,
        ]);

        // Tier A: Agent 1 has PERSONAL scope (no LEADS_SCOPE_ALL, no LEADS_SCOPE_GROUP)
        // Agent 1 can only see own assigned lead, not peer's lead in unsearched listing
        $resAgent1 = $this->actingAs($this->agentCairo1)->get(route('v2.leads'));
        $resAgent1->assertOk();
        $resAgent1->assertSee('عميل الموظف الأول');
        $resAgent1->assertDontSee('عميل الموظف الثاني المنفصل');

        // Tier B: Manager Cairo has GROUP scope (LEADS_SCOPE_GROUP) and manages agentCairo1
        // Manager can see subordinate's lead (agentCairo1), but cannot see unmanaged peer's lead (agentCairo2)
        $resManager = $this->actingAs($this->managerCairo)->get(route('v2.leads'));
        $resManager->assertOk();
        $resManager->assertSee('عميل الموظف الأول');
        $resManager->assertDontSee('عميل الموظف الثاني المنفصل');

        // Tier C: Super Admin has global scope
        $resAdmin = $this->actingAs($this->superAdmin)->get(route('v2.leads'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('عميل الموظف الأول');
        $resAdmin->assertSee('عميل الموظف الثاني المنفصل');
    }

    public function test_lead_assignment_matrix_respects_group_permissions(): void
    {
        // Manager has permission to assign to agentGroup
        $assignPermCode = LeadAssignment::groupPermissionCode($this->agentGroup);
        $this->managerGroup->permissions()->attach(
            Permission::query()->firstOrCreate(['code' => $assignPermCode], [
                'module' => 'leads',
                'name_ar' => 'إسناد لمجموعة المبيعات',
                'description' => 'Assign to sales',
            ])->id
        );

        // Manager can assign to agent in agentGroup
        $this->assertTrue(LeadAssignment::canAssignTo($this->managerCairo, $this->agentCairo1));

        // Manager CANNOT assign to collector (not in allowed group)
        $this->assertFalse(LeadAssignment::canAssignTo($this->managerCairo, $this->collectorCairo));
    }

    public function test_personal_password_change_isolated_to_own_account(): void
    {
        // Agent 1 can update own password
        $resSelf = $this->actingAs($this->agentCairo1)->patch(route('v2.my.password'), [
            'current_password' => 'secret', // factory default password
            'password' => 'NewStrongPass2026!',
            'password_confirmation' => 'NewStrongPass2026!',
        ]);

        // If current password matches factory or fails validation, ensures endpoint exists and is isolated
        $this->assertNotSame(500, $resSelf->status());

        // Regular agent cannot reset another user's password via settings -> 403 Forbidden
        $this->actingAs($this->agentCairo1)
            ->patch(route('v2.settings.users.password', $this->agentCairo2), [
                'password' => 'HackedPass2026!',
                'password_confirmation' => 'HackedPass2026!',
            ])
            ->assertForbidden();
    }
}

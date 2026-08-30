<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Database\Seeders\CrmAccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FiveRoleGroupModelTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmAccessControlSeeder::class);

        $this->branchA = Branch::query()->firstOrCreate(
            ['code' => 'branch_a'],
            ['name_ar' => 'فرع القاهرة (أ)', 'is_active' => true]
        );

        $this->branchB = Branch::query()->firstOrCreate(
            ['code' => 'branch_b'],
            ['name_ar' => 'فرع الإسكندرية (ب)', 'is_active' => true]
        );

        $this->groupSuperAdmin = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();
        $this->groupBranchAdmin = Group::query()->where('code', Group::BRANCH_ADMIN_CODE)->firstOrFail();
        $this->groupManager = Group::query()->where('code', Group::MANAGER_CODE)->firstOrFail();
        $this->groupCollector = Group::query()->where('code', Group::COLLECTOR_CODE)->firstOrFail();
        $this->groupEmployee = Group::query()->where('code', Group::EMPLOYEE_CODE)->firstOrFail();

        // 1. Super Admin
        $this->userSuperAdmin = User::factory()->create([
            'username' => 'test_superadmin',
            'name' => 'سوبر أدمن تجريبي',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->userSuperAdmin->groups()->sync([$this->groupSuperAdmin->id]);

        // 2. Branch Admin A & B
        $this->userBranchAdminA = User::factory()->create([
            'username' => 'test_branch_admin_a',
            'name' => 'أدمن فرع أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->userBranchAdminA->groups()->sync([$this->groupBranchAdmin->id]);

        $this->userBranchAdminB = User::factory()->create([
            'username' => 'test_branch_admin_b',
            'name' => 'أدمن فرع ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
        ]);
        $this->userBranchAdminB->groups()->sync([$this->groupBranchAdmin->id]);

        // 3. Manager A & B
        $this->userManagerA = User::factory()->create([
            'username' => 'test_manager_a',
            'name' => 'مدير فرع أ',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->userManagerA->groups()->sync([$this->groupManager->id]);

        $this->userManagerB = User::factory()->create([
            'username' => 'test_manager_b',
            'name' => 'مدير فرع ب',
            'branch_id' => $this->branchB->id,
            'is_active' => true,
        ]);
        $this->userManagerB->groups()->sync([$this->groupManager->id]);

        // 4. Collector A & B
        $this->userCollectorA = User::factory()->create([
            'username' => 'test_collector_a',
            'name' => 'محصل فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
        ]);
        $this->userCollectorA->groups()->sync([$this->groupCollector->id]);

        $this->userCollectorB = User::factory()->create([
            'username' => 'test_collector_b',
            'name' => 'محصل فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
        ]);
        $this->userCollectorB->groups()->sync([$this->groupCollector->id]);

        // 5. Employee A & B
        $this->userEmployeeA = User::factory()->create([
            'username' => 'test_employee_a',
            'name' => 'موظف فرع أ',
            'branch_id' => $this->branchA->id,
            'manager_id' => $this->userManagerA->id,
            'is_active' => true,
        ]);
        $this->userEmployeeA->groups()->sync([$this->groupEmployee->id]);

        $this->userEmployeeB = User::factory()->create([
            'username' => 'test_employee_b',
            'name' => 'موظف فرع ب',
            'branch_id' => $this->branchB->id,
            'manager_id' => $this->userManagerB->id,
            'is_active' => true,
        ]);
        $this->userEmployeeB->groups()->sync([$this->groupEmployee->id]);
    }

    public function test_exactly_five_active_system_groups_exist(): void
    {
        $activeGroups = Group::query()->where('is_active', true)->where('is_system', true)->get();
        $this->assertCount(5, $activeGroups);

        $codes = $activeGroups->pluck('code')->all();
        $expectedCodes = ['super-admin', 'branch-admin', 'manager', 'collector', 'employee'];
        sort($codes);
        sort($expectedCodes);

        $this->assertSame($expectedCodes, $codes);

        foreach ($activeGroups as $group) {
            $this->assertTrue((bool) $group->is_system, "Group {$group->code} must be a system group");
            $this->assertTrue((bool) $group->is_active, "Group {$group->code} must be active");
        }
    }

    public function test_legacy_groups_are_archived_and_hidden_from_active_list(): void
    {
        $legacyCodes = [
            'sales-manager',
            'sales-agent',
            'read-only',
            'collection-manager',
            'team-leader',
            'field-collector',
            'sales-supervisor',
            'campaign-manager',
            'voip-admin',
        ];

        $legacyGroups = Group::query()->whereIn('code', $legacyCodes)->get();
        foreach ($legacyGroups as $lg) {
            $this->assertFalse((bool) $lg->is_active, "Legacy group {$lg->code} must be inactive");
            $this->assertSame(0, $lg->users()->count(), "Legacy group {$lg->code} must have 0 users");
        }
    }

    public function test_employee_permission_profile(): void
    {
        $employee = $this->userEmployeeA;

        // Allowed
        $this->assertTrue($employee->hasPermission(CrmPermission::DASHBOARD_VIEW));
        $this->assertTrue($employee->hasPermission(CrmPermission::LEADS_VIEW));
        $this->assertTrue($employee->hasPermission(CrmPermission::LEADS_CREATE));
        $this->assertTrue($employee->hasPermission(CrmPermission::LEADS_UPDATE));
        $this->assertTrue($employee->hasPermission(CrmPermission::LEADS_FOLLOWUPS_VIEW));
        $this->assertTrue($employee->hasPermission(CrmPermission::LEADS_FOLLOWUPS_CREATE));
        $this->assertTrue($employee->hasPermission(CrmPermission::TASKS_VIEW));
        $this->assertTrue($employee->hasPermission(CrmPermission::CALENDAR_VIEW));
        $this->assertTrue($employee->hasPermission(CrmPermission::VOIP_VIEW));

        // Forbidden
        $this->assertFalse($employee->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertFalse($employee->hasPermission(CrmPermission::USERS_VIEW));
        $this->assertFalse($employee->hasPermission(CrmPermission::GROUPS_VIEW));
        $this->assertFalse($employee->hasPermission(CrmPermission::COLLECTIONS_VIEW));
        $this->assertFalse($employee->hasPermission(CrmPermission::BRANCHES_VIEW));
        $this->assertFalse($employee->hasPermission(CrmPermission::REPORTS_VIEW));
        $this->assertFalse($employee->hasPermission(CrmPermission::LEADS_SCOPE_ALL));
        $this->assertFalse($employee->hasPermission(CrmPermission::LEADS_SCOPE_GROUP));
        $this->assertFalse($employee->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW));
    }
    public function test_collector_permission_profile(): void
    {
        $collector = $this->userCollectorA;

        // Allowed
        $this->assertTrue($collector->hasPermission(CrmPermission::DASHBOARD_VIEW));
        $this->assertTrue($collector->hasPermission(CrmPermission::COLLECTIONS_VIEW));
        $this->assertTrue($collector->hasPermission(CrmPermission::COLLECTIONS_COLLECT));
        $this->assertTrue($collector->hasPermission(CrmPermission::COLLECTIONS_COMPLETE));
        $this->assertTrue($collector->hasPermission(CrmPermission::CALENDAR_VIEW));
        $this->assertTrue($collector->hasPermission(CrmPermission::TASKS_VIEW));

        // Forbidden
        $this->assertFalse($collector->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertFalse($collector->hasPermission(CrmPermission::LEADS_VIEW));
        $this->assertFalse($collector->hasPermission(CrmPermission::LEADS_SCOPE_ALL));
        $this->assertFalse($collector->hasPermission(CrmPermission::LEADS_CREATE));
        $this->assertFalse($collector->hasPermission(CrmPermission::USERS_VIEW));
        $this->assertFalse($collector->hasPermission(CrmPermission::GROUPS_VIEW));
        $this->assertFalse($collector->hasPermission(CrmPermission::COLLECTIONS_ASSIGN));
        $this->assertFalse($collector->hasPermission(CrmPermission::COLLECTIONS_MANAGE));
        $this->assertFalse($collector->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW));
    }
    public function test_manager_permission_profile(): void
    {
        $manager = $this->userManagerA;

        // Allowed
        $this->assertTrue($manager->hasPermission(CrmPermission::DASHBOARD_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_SCOPE_GROUP));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_ASSIGN));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_CREATE));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_UPDATE));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_EXPORT));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_FOLLOWUPS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::LEADS_FOLLOWUPS_CREATE));
        $this->assertTrue($manager->hasPermission(CrmPermission::TASKS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::CAMPAIGNS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::CAMPAIGNS_REPORTS));
        $this->assertTrue($manager->hasPermission(CrmPermission::REPORTS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::CALENDAR_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::CALENDAR_MANAGE));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_VIEW));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_ASSIGN));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_MANAGE));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_COMPLETE));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_CANCEL));
        $this->assertTrue($manager->hasPermission(CrmPermission::COLLECTIONS_REPORTS));

        // Forbidden
        $this->assertFalse($manager->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertFalse($manager->hasPermission(CrmPermission::USERS_VIEW));
        $this->assertFalse($manager->hasPermission(CrmPermission::GROUPS_VIEW));
        $this->assertFalse($manager->hasPermission(CrmPermission::LEADS_SCOPE_ALL));
        $this->assertFalse($manager->hasPermission(CrmPermission::LEADS_DELETE));
        $this->assertFalse($manager->hasPermission(CrmPermission::BRANCHES_VIEW));
    }

    public function test_branch_admin_permission_profile(): void
    {
        $branchAdmin = $this->userBranchAdminA;

        // Allowed
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::DASHBOARD_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_SCOPE_ALL));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_ASSIGN));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_CREATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_UPDATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_DELETE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_IMPORT));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::LEADS_EXPORT));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::CAMPAIGNS_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::CAMPAIGNS_CREATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::CAMPAIGNS_REPORTS));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::REPORTS_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::USERS_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::USERS_CREATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::USERS_UPDATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::USERS_ACTIVATE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::USERS_RESET_PASSWORD));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::COLLECTIONS_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::COLLECTIONS_MANAGE));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::BRANCHES_VIEW));
        $this->assertTrue($branchAdmin->hasPermission(CrmPermission::BRANCHES_UPDATE));

        // Forbidden
        $this->assertFalse($branchAdmin->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertFalse($branchAdmin->hasPermission(CrmPermission::GROUPS_VIEW));
        $this->assertFalse($branchAdmin->hasPermission(CrmPermission::GROUPS_CREATE));
        $this->assertFalse($branchAdmin->hasPermission(CrmPermission::BRANCHES_CREATE));
        $this->assertFalse($branchAdmin->hasPermission(CrmPermission::BRANCHES_DELETE));
    }

    public function test_super_admin_permission_profile(): void
    {
        $superAdmin = $this->userSuperAdmin;

        $this->assertTrue($superAdmin->isSuperAdmin());
        foreach (CrmPermission::cases() as $perm) {
            $this->assertTrue($superAdmin->hasPermission($perm), "Super admin must have permission {$perm->value}");
        }
    }

    public function test_employee_cross_branch_lead_isolation(): void
    {
        $newStatus = LeadStatus::query()->firstOrFail();

        $leadA = Lead::query()->create([
            'name' => 'عميل فرع أ',
            'branch_id' => $this->branchA->id,
            'assigned_user_id' => $this->userEmployeeA->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $leadB = Lead::query()->create([
            'name' => 'عميل فرع ب',
            'branch_id' => $this->branchB->id,
            'assigned_user_id' => $this->userEmployeeB->id,
            'lead_status_id' => $newStatus->id,
        ]);

        // Employee A sees Lead A, cannot see Lead B
        $this->assertTrue(Lead::accessibleTo($this->userEmployeeA)->whereKey($leadA->id)->exists());
        $this->assertFalse(Lead::accessibleTo($this->userEmployeeA)->whereKey($leadB->id)->exists());

        // Employee B sees Lead B, cannot see Lead A
        $this->assertTrue(Lead::accessibleTo($this->userEmployeeB)->whereKey($leadB->id)->exists());
        $this->assertFalse(Lead::accessibleTo($this->userEmployeeB)->whereKey($leadA->id)->exists());
    }

    public function test_collector_cross_branch_collection_case_isolation(): void
    {
        $donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'تبرع نقدي'],
            ['is_active' => true, 'position' => 1]
        );

        $newStatus = LeadStatus::query()->firstOrFail();

        $leadA = Lead::query()->create([
            'name' => 'عميل تحصيل فرع أ',
            'branch_id' => $this->branchA->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $leadB = Lead::query()->create([
            'name' => 'عميل تحصيل فرع ب',
            'branch_id' => $this->branchB->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $caseA = CollectionCase::query()->create([
            'lead_id' => $leadA->id,
            'branch_id' => $this->branchA->id,
            'expected_amount' => 500,
            'donation_type_id' => $donationType->id,
            'donation_type' => $donationType->name_ar,
            'cycle' => 'monthly',
            'due_at' => now()->addDays(2),
            'collection_address' => 'العنوان فرع أ',
            'assigned_collector_user_id' => $this->userCollectorA->id,
            'status' => CollectionCase::STATUS_ASSIGNED,
        ]);

        $caseB = CollectionCase::query()->create([
            'lead_id' => $leadB->id,
            'branch_id' => $this->branchB->id,
            'expected_amount' => 1000,
            'donation_type_id' => $donationType->id,
            'donation_type' => $donationType->name_ar,
            'cycle' => 'monthly',
            'due_at' => now()->addDays(2),
            'collection_address' => 'العنوان فرع ب',
            'assigned_collector_user_id' => $this->userCollectorB->id,
            'status' => CollectionCase::STATUS_ASSIGNED,
        ]);

        // Collector A can access Case A, not Case B
        $this->assertTrue(CollectionCase::accessibleTo($this->userCollectorA)->whereKey($caseA->id)->exists());
        $this->assertFalse(CollectionCase::accessibleTo($this->userCollectorA)->whereKey($caseB->id)->exists());

        // Collector B can access Case B, not Case A
        $this->assertTrue(CollectionCase::accessibleTo($this->userCollectorB)->whereKey($caseB->id)->exists());
        $this->assertFalse(CollectionCase::accessibleTo($this->userCollectorB)->whereKey($caseA->id)->exists());
    }

    public function test_manager_managed_team_isolation(): void
    {
        $newStatus = LeadStatus::query()->firstOrFail();

        $leadTeamA = Lead::query()->create([
            'name' => 'عميل فريق أ',
            'branch_id' => $this->branchA->id,
            'assigned_user_id' => $this->userEmployeeA->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $leadTeamB = Lead::query()->create([
            'name' => 'عميل فريق ب',
            'branch_id' => $this->branchB->id,
            'assigned_user_id' => $this->userEmployeeB->id,
            'lead_status_id' => $newStatus->id,
        ]);

        // Manager A can see Lead Team A (same group / branch), cannot see Lead Team B (Branch B)
        $this->assertTrue(Lead::accessibleTo($this->userManagerA)->whereKey($leadTeamA->id)->exists());
        $this->assertFalse(Lead::accessibleTo($this->userManagerA)->whereKey($leadTeamB->id)->exists());
    }

    public function test_branch_admin_branch_isolation(): void
    {
        $newStatus = LeadStatus::query()->firstOrFail();

        $leadA = Lead::query()->create([
            'name' => 'عميل فرع أ للادمن',
            'branch_id' => $this->branchA->id,
            'assigned_user_id' => $this->userEmployeeA->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $leadB = Lead::query()->create([
            'name' => 'عميل فرع ب للادمن',
            'branch_id' => $this->branchB->id,
            'assigned_user_id' => $this->userEmployeeB->id,
            'lead_status_id' => $newStatus->id,
        ]);

        // Branch Admin A sees all Branch A leads, cannot see Branch B leads
        $this->assertTrue(Lead::accessibleTo($this->userBranchAdminA)->whereKey($leadA->id)->exists());
        $this->assertFalse(Lead::accessibleTo($this->userBranchAdminA)->whereKey($leadB->id)->exists());
    }

    public function test_super_admin_has_global_access_across_branches(): void
    {
        $newStatus = LeadStatus::query()->firstOrFail();

        $leadA = Lead::query()->create([
            'name' => 'عميل فرع أ للسوبر',
            'branch_id' => $this->branchA->id,
            'assigned_user_id' => $this->userEmployeeA->id,
            'lead_status_id' => $newStatus->id,
        ]);

        $leadB = Lead::query()->create([
            'name' => 'عميل فرع ب للسوبر',
            'branch_id' => $this->branchB->id,
            'assigned_user_id' => $this->userEmployeeB->id,
            'lead_status_id' => $newStatus->id,
        ]);

        // Super Admin sees leads across all branches
        $this->assertTrue(Lead::accessibleTo($this->userSuperAdmin)->whereKey($leadA->id)->exists());
        $this->assertTrue(Lead::accessibleTo($this->userSuperAdmin)->whereKey($leadB->id)->exists());
    }

    public function test_super_admin_assignment_is_protected(): void
    {
        // Branch Admin cannot access settings to assign Super Admin group (403 Forbidden)
        $targetUser = User::factory()->create([
            'username' => 'target_user_test',
            'name' => 'المستخدم المستهدف',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->userBranchAdminA)->patch(route('v2.settings.users.update', $targetUser), [
            'name' => $targetUser->name,
            'username' => $targetUser->username,
            'branch_id' => $this->branchA->id,
            'group_ids' => [$this->groupSuperAdmin->id],
        ]);

        $response->assertForbidden();
        $this->assertFalse($targetUser->fresh()->isSuperAdmin());

        // Even if a user somehow had settings.access without isSuperAdmin, the controller blocks super-admin assignment
        $fakeAdmin = User::factory()->create([
            'username' => 'fake_admin_test',
            'name' => 'ادمن وهمي',
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $customGroup = Group::query()->create([
            'name' => 'Custom Settings Admin',
            'code' => 'custom-settings-admin',
            'is_system' => false,
            'is_active' => true,
        ]);
        $customGroup->permissions()->sync(
            Permission::query()->whereIn('code', ['settings.access', 'users.view', 'users.update'])->pluck('id')->all()
        );
        $fakeAdmin->groups()->sync([$customGroup->id]);

        $fakeResponse = $this->actingAs($fakeAdmin)->patch(route('v2.settings.users.update', $targetUser), [
            'name' => $targetUser->name,
            'username' => $targetUser->username,
            'branch_id' => $this->branchA->id,
            'group_ids' => [$this->groupSuperAdmin->id],
        ]);

        $fakeResponse->assertSessionHasErrors('group_ids');
        $this->assertFalse($targetUser->fresh()->isSuperAdmin());
    }

    public function test_sidebar_html_renders_only_permitted_modules_for_each_role(): void
    {
        app()->setLocale('ar');

        // 1. Employee
        $empRes = $this->actingAs($this->userEmployeeA)->get(route('dashboard'));
        $empRes->assertOk();
        $empHtml = $empRes->getContent();
        $this->assertStringContainsString('لوحة التحكم', $empHtml);
        $this->assertStringNotContainsString('الإعدادات', $empHtml);
        $this->assertStringNotContainsString('المستخدمين والمجموعات', $empHtml);
        $this->assertStringNotContainsString('طابور التحصيل', $empHtml);

        // 2. Collector
        $colRes = $this->actingAs($this->userCollectorA)->get(route('dashboard'));
        $colRes->assertOk();
        $colHtml = $colRes->getContent();
        $this->assertStringContainsString('التحصيل', $colHtml);
        $this->assertStringNotContainsString('الإعدادات', $colHtml);
        $this->assertStringNotContainsString('المستخدمين والمجموعات', $colHtml);

        // 3. Super Admin
        $superRes = $this->actingAs($this->userSuperAdmin)->get(route('dashboard'));
        $superRes->assertOk();
        $superHtml = $superRes->getContent();
        $this->assertStringContainsString('لوحة التحكم', $superHtml);
        $this->assertStringContainsString('الإعدادات', $superHtml);
    }
}

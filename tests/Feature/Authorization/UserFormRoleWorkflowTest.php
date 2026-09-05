<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Branch;
use App\Models\Governorate;
use App\Models\GovernorateSubregion;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserFormRoleWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    private User $branchAdmin;

    private Branch $branchCairo;

    private Branch $branchGiza;

    private Group $superGroup;

    private Group $branchAdminGroup;

    private Group $collectorGroup;

    private Group $employeeGroup;

    private Group $managerGroup;

    private GovernorateSubregion $nasrCitySub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchCairo = Branch::query()->create(['name_ar' => 'فرع القاهرة', 'code' => 'cairo_test_flow', 'is_active' => true]);
        $this->branchGiza = Branch::query()->create(['name_ar' => 'فرع الجيزة', 'code' => 'giza_test_flow', 'is_active' => true]);

        $gov = Governorate::query()->firstOrCreate(['code' => 'cairo'], ['name_ar' => 'القاهرة', 'is_active' => true]);
        $this->nasrCitySub = GovernorateSubregion::query()->firstOrCreate(
            ['code' => 'nasr_city', 'governorate_id' => $gov->id],
            ['name_ar' => 'مدينة نصر', 'is_active' => true]
        );

        $this->superGroup = Group::query()->firstOrCreate(['code' => Group::SUPER_ADMIN_CODE], ['name' => 'Super Admin', 'is_system' => true]);
        $this->branchAdminGroup = Group::query()->firstOrCreate(['code' => Group::BRANCH_ADMIN_CODE], ['name' => 'Branch Admin', 'is_system' => true]);
        $this->collectorGroup = Group::query()->firstOrCreate(['code' => Group::COLLECTOR_CODE], ['name' => 'Collector', 'is_system' => true]);
        $this->employeeGroup = Group::query()->firstOrCreate(['code' => Group::EMPLOYEE_CODE], ['name' => 'Employee', 'is_system' => true]);
        $this->managerGroup = Group::query()->firstOrCreate(['code' => Group::MANAGER_CODE], ['name' => 'Manager', 'is_system' => true]);

        $permSettings = Permission::query()->firstOrCreate(['code' => CrmPermission::SETTINGS_ACCESS->value], ['name_ar' => 'الإعدادات', 'module' => 'settings']);
        $permView = Permission::query()->firstOrCreate(['code' => CrmPermission::USERS_VIEW->value], ['name_ar' => 'عرض المستخدمين', 'module' => 'users']);
        $permCreate = Permission::query()->firstOrCreate(['code' => CrmPermission::USERS_CREATE->value], ['name_ar' => 'إنشاء مستخدم', 'module' => 'users']);
        $permUpdate = Permission::query()->firstOrCreate(['code' => CrmPermission::USERS_UPDATE->value], ['name_ar' => 'تعديل مستخدم', 'module' => 'users']);

        $allPermIds = [$permSettings->id, $permView->id, $permCreate->id, $permUpdate->id];
        $this->superGroup->permissions()->syncWithoutDetaching($allPermIds);
        $this->branchAdminGroup->permissions()->syncWithoutDetaching($allPermIds);

        $this->superAdmin = User::factory()->create(['username' => 'super_flow_admin', 'is_active' => true]);
        $this->superAdmin->groups()->sync([$this->superGroup->id]);

        $this->branchAdmin = User::factory()->create([
            'username' => 'cairo_branch_admin',
            'branch_id' => $this->branchCairo->id,
            'is_active' => true,
        ]);
        $this->branchAdmin->groups()->sync([$this->branchAdminGroup->id]);
    }

    public function test_create_user_view_renders_cleanly_without_syntax_or_variable_errors(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('v2.settings.users.create'));
        $response->assertOk();
        $response->assertSee('تحديد دور / وظيفة المستخدم');
        $response->assertSee('super-admin');
        $response->assertSee('collector');
        $response->assertSee('name="username"', false);
    }

    public function test_creating_collector_saves_subregion_and_mobile_phone(): void
    {
        $manager = User::factory()->create(['branch_id' => $this->branchCairo->id, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin)->post(route('v2.settings.users.store'), [
            'name' => 'المحصل مصطفى إبراهيم',
            'mobile_phone' => '01011223344',
            'branch_id' => $this->branchCairo->id,
            'manager_id' => $manager->id,
            'collection_subregion_id' => $this->nasrCitySub->id,
            'group_ids' => [$this->collectorGroup->id],
            'password' => 'CollectorPass123!',
            'password_confirmation' => 'CollectorPass123!',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::query()->where('name', 'المحصل مصطفى إبراهيم')->firstOrFail();
        $this->assertSame($this->branchCairo->id, $user->branch_id);
        $this->assertSame($manager->id, $user->manager_id);
        $this->assertSame($this->nasrCitySub->id, $user->collection_subregion_id);
        $this->assertSame('01011223344', $user->mobile_phone);
        $this->assertTrue($user->isCollector());
    }

    public function test_creating_non_collector_clears_collection_subregion(): void
    {
        $manager = User::factory()->create(['branch_id' => $this->branchCairo->id, 'is_active' => true]);

        // Submit with employee role but inadvertently supplying a collection_subregion_id
        $response = $this->actingAs($this->superAdmin)->post(route('v2.settings.users.store'), [
            'name' => 'موظف خدمة عملاء',
            'mobile_phone' => '01055667788',
            'branch_id' => $this->branchCairo->id,
            'manager_id' => $manager->id,
            'collection_subregion_id' => $this->nasrCitySub->id,
            'group_ids' => [$this->employeeGroup->id],
            'password' => 'EmployeePass123!',
            'password_confirmation' => 'EmployeePass123!',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::query()->where('name', 'موظف خدمة عملاء')->firstOrFail();
        $this->assertNull($user->collection_subregion_id);
        $this->assertFalse($user->isCollector());
        $this->assertTrue($user->isEmployee());
    }

    public function test_creating_super_admin_clears_branch_and_manager(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('v2.settings.users.store'), [
            'name' => 'أدمن نظام عام',
            'branch_id' => $this->branchCairo->id,
            'group_ids' => [$this->superGroup->id],
            'password' => 'SuperPass123!',
            'password_confirmation' => 'SuperPass123!',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::query()->where('name', 'أدمن نظام عام')->firstOrFail();
        $this->assertNull($user->branch_id);
        $this->assertNull($user->manager_id);
        $this->assertNull($user->collection_subregion_id);
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_branch_admin_is_scoped_to_their_own_branch_on_create(): void
    {
        $response = $this->actingAs($this->branchAdmin)->post(route('v2.settings.users.store'), [
            'name' => 'موظف فرع القاهرة المحلي',
            'branch_id' => $this->branchGiza->id, // Attempt to assign to Giza
            'group_ids' => [$this->employeeGroup->id],
            'password' => 'LocalEmployee123!',
            'password_confirmation' => 'LocalEmployee123!',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::query()->where('name', 'موظف فرع القاهرة المحلي')->firstOrFail();
        // Overridden / constrained to branch admin's branch (Cairo)
        $this->assertSame($this->branchCairo->id, $user->branch_id);
    }
}

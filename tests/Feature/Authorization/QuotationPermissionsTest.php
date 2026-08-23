<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesAgent;

    private User $readOnlyUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ],
            );
        }

        $superAdminGroup = Group::query()->create([
            'name' => 'مدير النظام',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $salesAgentGroup = Group::query()->create([
            'name' => 'موظف المبيعات',
            'code' => 'sales-agent',
            'is_system' => false,
        ]);
        $salesAgentGroup->permissions()->sync(
            Permission::whereIn('code', ['dashboard.view', 'quotations.view', 'quotations.create'])->pluck('id')
        );

        $readOnlyGroup = Group::query()->create([
            'name' => 'مشاهدة فقط',
            'code' => 'read-only',
            'is_system' => false,
        ]);
        $readOnlyGroup->permissions()->sync(
            Permission::whereIn('code', ['dashboard.view', 'quotations.view'])->pluck('id')
        );

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        $this->salesAgent = User::factory()->create(['is_active' => true]);
        $this->salesAgent->groups()->attach($salesAgentGroup);

        $this->readOnlyUser = User::factory()->create(['is_active' => true]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);
    }

    public function test_read_only_user_can_view_quotations_but_cannot_create(): void
    {
        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.quotations.index'))
            ->assertOk();

        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.quotations.create'))
            ->assertForbidden();

        $this->actingAs($this->readOnlyUser)
            ->post(route('v2.quotations.store'), [
                'title' => 'Test Quotation',
            ])
            ->assertForbidden();
    }

    public function test_sales_agent_can_view_and_create_quotation_form(): void
    {
        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.index'))
            ->assertOk();

        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.create'))
            ->assertOk();
    }
}

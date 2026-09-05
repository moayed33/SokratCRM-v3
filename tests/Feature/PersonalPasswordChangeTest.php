<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonalPasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $employeeGroup = Group::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Employee', 'is_system' => true]);
        $perm = Permission::query()->firstOrCreate(['code' => CrmPermission::LEADS_VIEW->value], ['name_ar' => 'عرض العملاء', 'module' => 'leads']);
        $employeeGroup->permissions()->syncWithoutDetaching([$perm->id]);

        $this->agent = User::factory()->create([
            'username' => 'sarah_callcenter',
            'name' => 'سارة أحمد (كول سنتر)',
            'password' => 'InitialSecretPassword123!',
            'is_active' => true,
        ]);
        $this->agent->groups()->sync([$employeeGroup->id]);
    }

    public function test_agent_can_view_change_password_button_and_modal(): void
    {
        $response = $this->actingAs($this->agent)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-crm-open-password-modal', false);
        $response->assertSee('crmPasswordModalBackdrop');
        $response->assertSee(route('v2.my.password'));
    }

    public function test_agent_can_change_own_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->agent)->patch(route('v2.my.password'), [
            'current_password' => 'InitialSecretPassword123!',
            'password' => 'BrandNewSecretPassword2026!',
            'password_confirmation' => 'BrandNewSecretPassword2026!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('password_success');
        $response->assertSessionHasNoErrors();

        $this->agent->refresh();
        $this->assertTrue(Hash::check('BrandNewSecretPassword2026!', $this->agent->password));

        // Verify login with new password works
        $this->post('/logout');
        $loginRes = $this->post('/login', [
            'username' => 'sarah_callcenter',
            'password' => 'BrandNewSecretPassword2026!',
        ]);
        $loginRes->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->agent);
    }

    public function test_password_change_fails_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->agent)->patch(route('v2.my.password'), [
            'current_password' => 'WrongCurrentPassword999!',
            'password' => 'BrandNewSecretPassword2026!',
            'password_confirmation' => 'BrandNewSecretPassword2026!',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->agent->refresh();
        $this->assertTrue(Hash::check('InitialSecretPassword123!', $this->agent->password));
    }

    public function test_password_change_fails_with_confirmation_mismatch(): void
    {
        $response = $this->actingAs($this->agent)->patch(route('v2.my.password'), [
            'current_password' => 'InitialSecretPassword123!',
            'password' => 'BrandNewSecretPassword2026!',
            'password_confirmation' => 'MismatchConfirmation999!',
        ]);

        $response->assertSessionHasErrors('password');

        $this->agent->refresh();
        $this->assertTrue(Hash::check('InitialSecretPassword123!', $this->agent->password));
    }

    public function test_unauthenticated_user_cannot_access_my_password_endpoint(): void
    {
        $response = $this->patch(route('v2.my.password'), [
            'current_password' => 'InitialSecretPassword123!',
            'password' => 'BrandNewSecretPassword2026!',
            'password_confirmation' => 'BrandNewSecretPassword2026!',
        ]);

        $response->assertRedirect(route('login'));
    }
}

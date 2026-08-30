<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Database\Seeders\CrmAccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_installer_user_is_seeded_as_the_default_super_admin(): void
    {
        config()->set('crm.bootstrap_admin', [
            'name' => 'Current CRM Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'Admin@123',
        ]);

        $this->seed(CrmAccessControlSeeder::class);

        $admin = User::query()
            ->where('username', 'admin')
            ->firstOrFail();

        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('Admin@123', $admin->password));
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue(
            $admin->hasPermission(CrmPermission::GROUPS_ASSIGN_PERMISSIONS),
        );
        $this->assertSame(
            count(CrmPermission::cases()) + Group::query()->count(),
            Permission::query()->count(),
        );
    }

    public function test_active_database_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'username' => 'sales-agent',
            'password' => 'SecretPass123',
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $response = $this->post('/login', [
            'username' => 'sales-agent',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'username' => 'disabled-user',
            'password' => 'SecretPass123',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'username' => 'disabled-user',
            'password' => 'SecretPass123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_deactivated_user_with_an_existing_session_is_logged_out(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_protected_crm_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/settings')->assertRedirect(route('login'));
        $this->get('/campaigns')->assertRedirect(route('login'));
    }
}

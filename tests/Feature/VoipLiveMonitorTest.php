<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoipLiveMonitorTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsPath = sys_get_temp_dir().'/crm-v3-voip-live-'.bin2hex(random_bytes(5)).'.json';
        config([
            'voip.credentials_path' => $this->credentialsPath,
            'voip.api_url' => 'http://voip.test/api',
            'voip.client_secret' => 'live-secret-test',
        ]);

        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Administrator'],
        );

        foreach (CrmPermission::values() as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code)[0], 'name_ar' => $code, 'description' => $code],
            );
            $group->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->sync([$group->id]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);

        parent::tearDown();
    }

    public function test_live_panel_renders_active_pbx_extensions_and_employee_mapping(): void
    {
        $agent = User::factory()->create([
            'name' => 'Support Rep Mazen',
            'voip_extension' => '102',
            'is_active' => true,
        ]);

        Http::fake([
            'http://voip.test/api/extensions*' => Http::response([
                'extensions' => [
                    [
                        'extension' => '101',
                        'name' => 'Ahmed Line',
                        'online' => true,
                        'in_call' => false,
                        'status' => 'online',
                        'call' => null,
                    ],
                    [
                        'extension' => '102',
                        'name' => 'Mazen Softphone',
                        'online' => true,
                        'in_call' => true,
                        'status' => 'in_call',
                        'call' => [
                            'state' => 'In Call',
                            'partner' => '01012345678',
                            'started_at' => now()->subSeconds(45)->getTimestampMs(),
                            'duration_seconds' => 45,
                        ],
                    ],
                    [
                        'extension' => '103',
                        'name' => 'Standby Desk',
                        'online' => false,
                        'in_call' => false,
                        'status' => 'offline',
                        'call' => null,
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.voip.live'));

        $response->assertOk()
            ->assertSee('101')
            ->assertSee('102')
            ->assertSee('103')
            ->assertSee('Support Rep Mazen')
            ->assertSee('01012345678')
            ->assertSee('id="countTotal"', false)
            ->assertSee('id="countOnline"', false)
            ->assertSee('id="countInCall"', false)
            ->assertSee('id="countOffline"', false);
    }

    public function test_live_data_endpoint_returns_json_payload_for_polling(): void
    {
        User::factory()->create([
            'name' => 'Desk Agent 101',
            'voip_extension' => '101',
            'is_active' => true,
        ]);

        Http::fake([
            'http://voip.test/api/extensions*' => Http::response([
                'extensions' => [
                    [
                        'extension' => '101',
                        'name' => '101',
                        'online' => true,
                        'in_call' => false,
                        'status' => 'online',
                        'call' => null,
                    ],
                    [
                        'extension' => '102',
                        'name' => '102',
                        'online' => false,
                        'in_call' => false,
                        'status' => 'offline',
                        'call' => null,
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.voip.live.data'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.total', 2)
            ->assertJsonPath('counts.online', 1)
            ->assertJsonPath('counts.in_call', 0)
            ->assertJsonPath('counts.offline', 1);

        $this->assertIsString($response->json('html'));
        $this->assertStringContainsString('data-extension="101"', $response->json('html'));
        $this->assertStringContainsString('Desk Agent 101', $response->json('html'));
    }

    public function test_live_panel_requires_permission(): void
    {
        $unauthorized = User::factory()->create(['is_active' => true]);

        $this->actingAs($unauthorized)
            ->get(route('v2.voip.live'))
            ->assertForbidden();

        $this->actingAs($unauthorized)
            ->getJson(route('v2.voip.live.data'))
            ->assertForbidden();
    }
}

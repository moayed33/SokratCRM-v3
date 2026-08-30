<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\User;
use App\Models\VoipExtensionAssignment;
use App\Security\CrmPermission;
use App\Services\VoipCallAnalytics;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoipInsightsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsPath = sys_get_temp_dir().'/crm-v3-voip-feature-'.bin2hex(random_bytes(5)).'.json';
        config([
            'voip.credentials_path' => $this->credentialsPath,
            'voip.api_url' => 'http://voip.test/api',
            'voip.client_secret' => 'test-secret',
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

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->groups()->sync([$group->id]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);

        parent::tearDown();
    }

    public function test_lead_calls_include_historical_employee_metrics_and_signed_recording(): void
    {
        $historicalUser = User::factory()->create(['name' => 'Historical Agent', 'voip_extension' => null]);
        $currentUser = User::factory()->create(['name' => 'Current Agent', 'voip_extension' => '101']);
        VoipExtensionAssignment::query()->create([
            'user_id' => $historicalUser->id,
            'extension' => '101',
            'assigned_from' => '2026-08-01 00:00:00',
            'assigned_until' => '2026-08-20 00:00:00',
        ]);
        VoipExtensionAssignment::query()->create([
            'user_id' => $currentUser->id,
            'extension' => '101',
            'assigned_from' => '2026-08-20 00:00:00',
        ]);
        $lead = Lead::query()->create(['name' => 'CDR Lead', 'phone' => '01012345678']);

        Http::fake([
            'http://voip.test/api/calls*' => Http::response(['calls' => [[
                'id' => 'cdr-1',
                'started_at' => '2026-08-10T10:00:00+03:00',
                'direction' => 'outgoing',
                'customer_number' => '01012345678',
                'agent_extension' => '101',
                'duration_seconds' => 125,
                'disposition' => 'ANSWERED',
                'recording' => ['available' => true, 'media_id' => 'media-1'],
            ]]]),
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.calls', $lead));

        $response->assertOk()
            ->assertJsonPath('summary.total_calls', 1)
            ->assertJsonPath('summary.answered_calls', 1)
            ->assertJsonPath('calls.0.crm_user.id', $historicalUser->id)
            ->assertJsonPath('calls.0.crm_user.name', 'Historical Agent')
            ->assertJsonPath('calls.0.direction', 'outbound')
            ->assertJsonPath('calls.0.duration_seconds', 125);
        $this->assertStringContainsString('/voip/recordings/media-1', $response->json('calls.0.recording_url'));
        $this->assertStringContainsString('signature=', $response->json('calls.0.recording_url'));
    }

    public function test_lead_calls_require_access_to_the_lead(): void
    {
        $lead = Lead::query()->create(['name' => 'Scoped Lead', 'phone' => '01099999999']);
        $restricted = User::factory()->create(['is_active' => true]);
        $voipPermission = Permission::query()->where('code', CrmPermission::VOIP_VIEW->value)->firstOrFail();
        $group = Group::query()->create(['name' => 'VoIP only', 'code' => 'voip-only']);
        $group->permissions()->sync([$voipPermission->id]);
        $restricted->groups()->sync([$group->id]);

        $this->actingAs($restricted)
            ->getJson(route('v2.leads.calls', $lead))
            ->assertForbidden();
    }

    public function test_extension_must_be_unique_when_creating_users(): void
    {
        User::factory()->create(['voip_extension' => '202']);
        $group = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('v2.settings.users.store'), [
                'name' => 'Duplicate Extension',
                'username' => 'duplicate_extension',
                'voip_extension' => '202',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'group_ids' => [$group->id],
            ])
            ->assertSessionHasErrors('voip_extension');
    }

    public function test_personal_and_team_reports_render_extension_statistics(): void
    {
        $this->admin->update(['voip_extension' => '102']);
        VoipExtensionAssignment::query()->create([
            'user_id' => $this->admin->id,
            'extension' => '102',
            'assigned_from' => now()->subDay(),
        ]);
        Http::fake([
            'http://voip.test/api/extensions/102/stats*' => Http::response([
                'summary' => [
                    'total_calls' => 8,
                    'answered_calls' => 6,
                    'missed_calls' => 2,
                    'outbound_calls' => 5,
                    'total_talk_seconds' => 420,
                    'answer_rate_percent' => 75,
                ],
                'recent_calls' => [],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->get(route('v2.voip.profile'))
            ->assertOk()
            ->assertSee(__('crm.my_call_profile'))
            ->assertSee('75.0%');
        $this->actingAs($this->admin)
            ->get(route('v2.reports.voip'))
            ->assertOk()
            ->assertSee(__('crm.voip_team_report'))
            ->assertSee($this->admin->name);
    }

    public function test_extension_analytics_translate_live_filters_and_apply_unsupported_filters_locally(): void
    {
        $this->admin->update(['voip_extension' => '103']);
        Http::fake([
            'http://voip.test/api/extensions/103/stats*' => Http::response([
                'summary' => [
                    'total_calls' => 3,
                    'answered_calls' => 1,
                    'inbound_calls' => 1,
                    'outbound_calls' => 1,
                    'internal_calls' => 1,
                    'total_talk_seconds' => 20,
                    'avg_talk_seconds' => 20,
                    'answer_rate_percent' => 33.3,
                ],
                'disposition_breakdown' => ['ANSWERED' => 1, 'FAILED' => 2],
                'daily_breakdown' => [['date' => '2026-08-25', 'total' => 3]],
                'recent_calls' => [
                    ['id' => '1', 'started_at' => '2026-08-25T10:00:00+03:00', 'direction' => 'inbound', 'agent_extension' => '103', 'disposition' => 'ANSWERED', 'duration_seconds' => 30, 'billable_seconds' => 20],
                    ['id' => '2', 'started_at' => '2026-08-25T11:00:00+03:00', 'direction' => 'outbound', 'agent_extension' => '103', 'disposition' => 'FAILED', 'duration_seconds' => 8, 'billable_seconds' => 0],
                    ['id' => '3', 'started_at' => '2026-08-25T12:00:00+03:00', 'direction' => 'internal', 'agent_extension' => '103', 'disposition' => 'FAILED', 'duration_seconds' => 5, 'billable_seconds' => 0],
                ],
            ]),
        ]);

        $analytics = app(VoipCallAnalytics::class);
        $analytics->forUser($this->admin, [
            'from_date' => '2026-08-25',
            'to_date' => '2026-08-25',
            'direction' => 'incoming',
        ]);
        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['direction'] ?? null) === 'inbound'
                && str_starts_with((string) ($query['from'] ?? ''), '2026-08-25T00:00:00')
                && str_starts_with((string) ($query['to'] ?? ''), '2026-08-25T23:59:59');
        });

        $answered = $analytics->forUser($this->admin, ['status' => 'answered']);
        $this->assertSame(1, $answered['summary']['total_calls']);
        $this->assertSame(20, $answered['summary']['total_talk_seconds']);

        $internal = $analytics->forUser($this->admin, ['direction' => 'internal']);
        $this->assertSame(1, $internal['summary']['total_calls']);
        $this->assertSame(0, $internal['summary']['total_talk_seconds']);
    }
}

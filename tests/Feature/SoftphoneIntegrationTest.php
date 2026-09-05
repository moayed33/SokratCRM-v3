<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SoftphoneIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialsPath = sys_get_temp_dir().'/crm-v3-softphone-feature-'.bin2hex(random_bytes(5)).'.json';
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

        $this->admin = User::factory()->create([
            'is_active' => true,
            'voip_extension' => '150',
        ]);
        $this->admin->groups()->sync([$group->id]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        parent::tearDown();
    }

    public function test_softphone_redirects_with_a_server_issued_scoped_ticket(): void
    {
        Http::fake([
            'http://voip.test/api/embed-tickets' => Http::response([
                'ticket' => 'tkt_feature_ticket',
                'effective_scopes' => ['softphone:use'],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->get(route('v2.voip.softphone'))
            ->assertRedirect('/phone/embed?ticket=tkt_feature_ticket');

        Http::assertSent(fn (Request $request): bool =>
            $request->method() === 'POST'
            && $request->url() === 'http://voip.test/api/embed-tickets'
            && $request->hasHeader('Authorization', 'Bearer test-secret')
            && $request['crm_user_id'] === (string) $this->admin->id
            && $request['extension'] === '150'
            && $request['supervisor_extension'] === '150'
            && $request['requested_scopes'] === ['softphone:use']
        );
    }

    public function test_softphone_ticket_failure_is_fail_closed_without_fallback_credentials(): void
    {
        Http::fake([
            'http://voip.test/api/embed-tickets' => Http::response([
                'success' => false,
                'error' => 'upstream unavailable',
            ], 503),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('v2.voip.softphone'))
            ->assertStatus(502)
            ->assertExactJson([
                'success' => false,
                'error' => 'Could not acquire softphone ticket.',
            ]);
    }

    public function test_phone_screen_pop_lookup_returns_only_leads_accessible_to_the_user(): void
    {
        $restricted = User::factory()->create(['is_active' => true]);
        $permission = Permission::query()->firstOrCreate(
            ['code' => CrmPermission::LEADS_VIEW->value],
            ['module' => 'leads', 'name_ar' => 'عرض العملاء', 'description' => 'View leads'],
        );
        $group = Group::query()->create([
            'name' => 'Scoped lead viewer',
            'code' => 'scoped-lead-viewer-'.bin2hex(random_bytes(3)),
        ]);
        $group->permissions()->sync([$permission->id]);
        $restricted->groups()->sync([$group->id]);

        $visible = Lead::query()->create([
            'name' => 'Visible Caller',
            'phone' => '01012345678',
            'assigned_user_id' => $restricted->id,
        ]);
        Lead::query()->create([
            'name' => 'Hidden Caller',
            'phone' => '01012345678',
            'assigned_user_id' => $this->admin->id,
        ]);

        $this->actingAs($restricted)
            ->getJson(route('v2.leads.by-phone', ['phone' => '+20 10 1234 5678']))
            ->assertOk()
            ->assertJsonCount(1, 'leads')
            ->assertJsonPath('leads.0.id', $visible->id)
            ->assertJsonPath('leads.0.name', 'Visible Caller');
    }

    public function test_phone_screen_pop_lookup_rejects_short_numbers(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('v2.leads.by-phone', ['phone' => '123']))
            ->assertOk()
            ->assertExactJson(['leads' => []]);
    }

    public function test_kanban_view_lead_button_is_a_direct_link_without_nested_iframe_popup(): void
    {
        $status = \App\Models\LeadStatus::query()->first();
        $lead = Lead::query()->create([
            'name' => 'Direct Link Lead',
            'phone' => '01011112222',
            'lead_status_id' => $status?->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('v2.leads.kanban'));

        $response->assertOk();
        $response->assertSee(route('v2.leads.show', $lead));
        $response->assertDontSee('data-kanban-customer-popup');
        $response->assertDontSee('id="crmKanbanActionModal"', false);
        $response->assertDontSee('id="crmKanbanActionFrame"', false);
    }

    public function test_voice_dock_is_suppressed_on_popup_and_iframe_requests(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Popup Lead',
            'phone' => '01011113333',
            'assigned_user_id' => $this->admin->id,
        ]);

        $normal = $this->actingAs($this->admin)->get(route('v2.leads'));
        $normal->assertOk();
        $normal->assertSee('id="sokratVoiceDock"', false);

        $popup = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead,
            'kanban_popup' => 1,
        ]));
        $popup->assertOk();
        $popup->assertDontSee('id="sokratVoiceDock"', false);
        $popup->assertDontSee('id="voiceDockToggle"', false);
        $popup->assertDontSee('id="sokratVoicePanel"', false);
    }

    public function test_voice_dock_auto_boots_and_reconnects_on_tab_switch(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));
        $response->assertOk();
        $response->assertSee('id="sokratVoiceDock"', false);
        $response->assertSee('id="sokratVoiceFrame"', false);
        $response->assertSee('loadFreshSession()', false);
        $response->assertSee('sokrat.voice.resume', false);
        $response->assertSee("sessionStorage.getItem('sokrat_voice_panel_open')", false);
        $response->assertSee('#sokratVoicePanel[hidden]', false);
    }

    public function test_phone_number_masking_policy_enforces_gui_permission(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Masked Caller',
            'phone' => '01012345678',
            'assigned_user_id' => $this->admin->id,
        ]);

        // 1. Admin with leads.view_full_phone sees raw number
        $this->actingAs($this->admin);
        $this->assertSame('01012345678', $lead->display_phone);
        $this->get(route('v2.leads'))->assertSee('01012345678');

        // 2. Restricted agent without leads.view_full_phone sees masked number
        $agent = User::factory()->create([
            'is_active' => true,
            'voip_extension' => '102',
        ]);
        $permView = Permission::query()->where('code', CrmPermission::LEADS_VIEW->value)->firstOrFail();
        $permVoip = Permission::query()->where('code', CrmPermission::VOIP_VIEW->value)->firstOrFail();
        $group = Group::query()->create(['name' => 'Restricted Agent', 'code' => 'agent-' . bin2hex(random_bytes(3))]);
        $group->permissions()->sync([$permView->id, $permVoip->id]);
        $agent->groups()->sync([$group->id]);
        $lead->update(['assigned_user_id' => $agent->id]);

        $this->actingAs($agent);
        $this->assertSame('0101****678', $lead->fresh()->display_phone);

        // Assert CRM view renders masked phone
        $res = $this->get(route('v2.leads'));
        $res->assertOk();
        $res->assertSee('0101****678');
        $res->assertDontSee('>01012345678<', false);

        // Assert by-phone API includes display_phone masked
        $resApi = $this->getJson(route('v2.leads.by-phone', ['phone' => '01012345678']));
        $resApi->assertOk();
        $resApi->assertJsonPath('leads.0.display_phone', '0101****678');

        // Assert softphone redirect carries mask_phone=1
        Http::fake([
            'http://voip.test/api/embed-tickets' => Http::response([
                'ticket' => 'tkt_masked_ticket',
                'effective_scopes' => ['softphone:use'],
            ]),
        ]);
        $this->get(route('v2.voip.softphone'))
            ->assertRedirect('/phone/embed?ticket=tkt_masked_ticket&mask_phone=1');
    }

    public function test_screen_pop_profile_link_opens_in_new_tab_to_protect_active_call(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));
        $response->assertOk();

        // Assert screen-pop profile button has target="_blank" so active calls are protected in the original tab
        $response->assertSee('target="_blank" class="sokrat-pop-btn sokrat-pop-btn-primary"', false);

        // Assert phantom sessionStorage persistence is removed
        $response->assertDontSee("sessionStorage.setItem('sokrat_voice_active_call'", false);
    }
}

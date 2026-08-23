<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_campaign_form_and_create_campaign(): void
    {
        $admin = $this->superAdmin();
        $assignedUser = User::factory()->create([
            'name' => 'Campaign Agent',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.create'))
            ->assertOk()
            ->assertSee('اسم الحملة')
            ->assertSee('Campaign Agent');

        $response = $this->post(route('v2.campaigns.store'), [
            'name' => 'Summer Campaign',
            'cost' => '12500.50',
            'starts_at' => '2026-08-20 09:00',
            'ends_at' => '2026-08-31 18:00',
            'user_ids' => [$assignedUser->id],
        ]);

        $response
            ->assertRedirect(route('v2.campaigns.index'))
            ->assertSessionHas('success');

        $campaign = Campaign::query()->firstOrFail();

        $this->assertSame('Summer Campaign', $campaign->name);
        $this->assertSame('12500.50', $campaign->cost);
        $this->assertTrue($campaign->creator->is($admin));
        $this->assertTrue(
            $campaign->users()->whereKey($assignedUser->id)->exists(),
        );
    }

    public function test_campaign_image_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $admin = $this->superAdmin();
        $agent = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('v2.campaigns.store'), [
                'name' => 'Campaign With Image',
                'image' => UploadedFile::fake()->image('campaign.jpg'),
                'cost' => '1000',
                'starts_at' => '2026-08-20 09:00',
                'ends_at' => '2026-08-31 18:00',
                'user_ids' => [$agent->id],
            ])
            ->assertRedirect(route('v2.campaigns.index'));

        $campaign = Campaign::query()
            ->where('name', 'Campaign With Image')
            ->firstOrFail();
        $originalImage = $campaign->image_path;

        $this->assertNotNull($originalImage);
        Storage::disk('public')->assertExists($originalImage);

        $this->actingAs($admin)
            ->patch(route('v2.campaigns.update', $campaign), [
                'name' => 'Campaign With New Image',
                'image' => UploadedFile::fake()->image('replacement.png'),
                'cost' => '1000',
                'starts_at' => '2026-08-20 09:00',
                'ends_at' => '2026-08-31 18:00',
                'user_ids' => [$agent->id],
            ])
            ->assertRedirect(route('v2.campaigns.show', $campaign));

        $campaign->refresh();

        $this->assertNotSame($originalImage, $campaign->image_path);
        Storage::disk('public')->assertMissing($originalImage);
        Storage::disk('public')->assertExists($campaign->image_path);
    }

    public function test_campaign_requires_valid_timing_and_active_users(): void
    {
        $admin = $this->superAdmin();
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('v2.campaigns.store'), [
                'name' => 'Invalid Campaign',
                'cost' => '-1',
                'starts_at' => '2026-08-20 12:00',
                'ends_at' => '2026-08-20 11:00',
                'user_ids' => [$inactiveUser->id],
            ])
            ->assertSessionHasErrors([
                'cost',
                'ends_at',
                'user_ids.0',
            ]);

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_list_can_be_filtered_by_name_and_user(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = User::factory()->create(['name' => 'Filter First Agent']);
        $secondAgent = User::factory()->create(['name' => 'Filter Second Agent']);
        $matchingCampaign = $this->campaign($admin, [$firstAgent]);
        $matchingCampaign->update(['name' => 'Alpha Search Campaign']);
        $otherCampaign = $this->campaign($admin, [$secondAgent]);
        $otherCampaign->update(['name' => 'Beta Hidden Campaign']);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.index', [
                'q' => 'Alpha',
                'user_id' => $firstAgent->id,
            ]))
            ->assertOk()
            ->assertSee('Alpha Search Campaign')
            ->assertDontSee('Beta Hidden Campaign');
    }

    public function test_campaign_manager_can_edit_campaign_information(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = User::factory()->create(['name' => 'First Agent']);
        $secondAgent = User::factory()->create(['name' => 'Second Agent']);
        $campaign = $this->campaign($admin, [$firstAgent]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('تعديل الحملة')
            ->assertSee($campaign->name);

        $this->actingAs($admin)
            ->patch(route('v2.campaigns.update', $campaign), [
                'name' => 'Updated Campaign',
                'cost' => '2250.75',
                'starts_at' => '2026-09-01 09:00',
                'ends_at' => '2026-09-15 18:00',
                'user_ids' => [$secondAgent->id],
            ])
            ->assertRedirect(route('v2.campaigns.show', $campaign))
            ->assertSessionHas('success');

        $campaign->refresh();

        $this->assertSame('Updated Campaign', $campaign->name);
        $this->assertSame('2250.75', $campaign->cost);
        $this->assertFalse($campaign->users()->whereKey($firstAgent->id)->exists());
        $this->assertTrue($campaign->users()->whereKey($secondAgent->id)->exists());
    }

    public function test_campaign_creator_cannot_edit_another_creators_campaign(): void
    {
        $creator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $otherCreator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $campaign = $this->campaign($creator, [$creator]);

        $this->actingAs($otherCreator)
            ->get(route('v2.campaigns.edit', $campaign))
            ->assertForbidden();
    }

    public function test_campaign_manager_can_delete_campaign_without_deleting_leads(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $lead = $this->lead(['assigned_user_id' => $admin->id]);
        $campaign->leads()->attach($lead);

        $this->actingAs($admin)
            ->delete(route('v2.campaigns.destroy', $campaign))
            ->assertRedirect(route('v2.campaigns.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('campaign_lead', [
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
        ]);
    }

    public function test_campaign_creator_cannot_delete_another_creators_campaign(): void
    {
        $creator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $otherCreator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $campaign = $this->campaign($creator, [$creator]);

        $this->actingAs($otherCreator)
            ->delete(route('v2.campaigns.destroy', $campaign))
            ->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_import_uses_existing_rules_and_attaches_created_leads(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $this->leadStatus();

        $this->actingAs($admin)
            ->get(route('v2.leads.import', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertSee($campaign->name);

        $preview = $this->post(route('v2.leads.import.preview'), [
            'campaign_id' => $campaign->id,
            'import_file' => UploadedFile::fake()->createWithContent(
                'campaign-leads.csv',
                "first_name,phone,source,next_follow_up_at\nImported Lead,01012345678,Campaign File,2026-08-20 12:00\n",
            ),
        ]);

        $preview
            ->assertOk()
            ->assertViewHas(
                'campaign',
                static fn (Campaign $viewCampaign): bool => $viewCampaign
                    ->is($campaign),
            )
            ->assertSee('Imported Lead');

        $previewData = $preview->viewData('preview');
        $this->assertContains(
            'next_follow_up_at',
            $previewData['ignored_headers'],
        );
        $token = $previewData['token'];

        $this->post(route('v2.leads.import.confirm'), [
            'preview_token' => $token,
        ])->assertRedirect(route('v2.campaigns.show', $campaign));

        $lead = Lead::query()
            ->where('phone', '01012345678')
            ->firstOrFail();

        $this->assertNull($lead->next_follow_up_at);
        $this->assertTrue(
            $campaign->leads()->whereKey($lead->id)->exists(),
        );
    }

    public function test_campaign_manager_can_manually_add_lead_to_campaign(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $otherCampaign = Campaign::query()->create([
            'name' => 'Other Manual Campaign',
            'cost' => 500,
            'starts_at' => '2026-09-01 09:00',
            'ends_at' => '2026-09-10 18:00',
            'created_by_user_id' => $admin->id,
        ]);
        $status = $this->leadStatus();

        $this->actingAs($admin)
            ->get(route('v2.leads.create', ['campaign_id' => $campaign->id]))
            ->assertOk()
            ->assertSee('إضافة عميل إلى '.$campaign->name)
            ->assertSee('name="campaign_id"', false)
            ->assertSee('بدون حملة')
            ->assertSee($otherCampaign->name)
            ->assertSee(
                'value="'.$campaign->id.'"',
                false,
            );

        $response = $this->actingAs($admin)
            ->post(route('v2.leads.store'), [
                'campaign_id' => $campaign->id,
                'first_name' => 'Manual Campaign',
                'last_name' => 'Lead',
                'phone' => '01055555555',
                'source' => 'Manual Campaign Entry',
                'assigned_user_id' => $admin->id,
                'lead_status_id' => $status->id,
            ]);

        $lead = Lead::query()->where('phone', '01055555555')->firstOrFail();

        $response
            ->assertRedirect(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => $admin->id,
            ]))
            ->assertSessionHas('success');
        $this->assertTrue($campaign->leads()->whereKey($lead->id)->exists());
    }

    public function test_followup_can_move_lead_and_assign_campaign_user(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create(['name' => 'Followup Campaign Agent']);
        $oldCampaign = $this->campaign($admin, [$admin]);
        $newCampaign = $this->campaign($admin, [$agent]);
        $lead = $this->lead(['assigned_user_id' => $admin->id]);
        $oldCampaign->leads()->attach($lead);

        $this->actingAs($admin)
            ->get(route('v2.leads.followups.index', $lead))
            ->assertOk()
            ->assertSee($newCampaign->name)
            ->assertSee('Followup Campaign Agent');

        $this->actingAs($admin)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $lead->lead_status_id,
                'communication_type' => 'other',
                'outcome' => 'Moved during follow-up',
                'campaign_id' => $newCampaign->id,
                'assigned_user_id' => $agent->id,
            ])
            ->assertRedirect(route('v2.leads.followups.index', $lead))
            ->assertSessionHas('success');

        $lead->refresh();

        $this->assertSame($agent->id, $lead->assigned_user_id);
        $this->assertSame($agent->name, $lead->assigned_employee);
        $this->assertTrue($newCampaign->leads()->whereKey($lead->id)->exists());
        $this->assertFalse($oldCampaign->leads()->whereKey($lead->id)->exists());
    }

    public function test_followup_rejects_assignee_outside_selected_campaign(): void
    {
        $admin = $this->superAdmin();
        $campaignAgent = User::factory()->create();
        $outsider = User::factory()->create();
        $campaign = $this->campaign($admin, [$campaignAgent]);
        $lead = $this->lead(['assigned_user_id' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $lead->lead_status_id,
                'communication_type' => 'other',
                'campaign_id' => $campaign->id,
                'assigned_user_id' => $outsider->id,
            ])
            ->assertForbidden();

        $this->assertSame($admin->id, $lead->fresh()->assigned_user_id);
    }

    public function test_failed_campaign_import_returns_to_get_import_page(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);

        $this->actingAs($admin)
            ->from(route('v2.leads.import.preview'))
            ->post(route('v2.leads.import.preview'), [
                'campaign_id' => $campaign->id,
            ])
            ->assertRedirect(route('v2.leads.import', [
                'campaign' => $campaign->id,
            ]))
            ->assertSessionHasErrors('import_file');
    }

    public function test_manager_can_bulk_assign_campaign_leads_to_campaign_user(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        $campaign = $this->campaign($admin, [$agent]);
        $lead = $this->lead(['assigned_user_id' => $admin->id]);
        $campaign->leads()->attach($lead);

        $this->actingAs($admin)
            ->patch(route('v2.campaigns.leads.assign', $campaign), [
                'lead_ids' => [$lead->id],
                'target_user_id' => $agent->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $lead->refresh();

        $this->assertSame($agent->id, $lead->assigned_user_id);
        $this->assertSame('Assigned Agent', $lead->assigned_employee);
    }

    public function test_campaign_user_only_sees_leads_assigned_to_them(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = $this->userWithPermissions(['campaigns.view']);
        $secondAgent = $this->userWithPermissions(['campaigns.view']);
        $campaign = $this->campaign($admin, [$firstAgent, $secondAgent]);

        $visibleLead = $this->lead([
            'name' => 'Visible Campaign Lead',
            'assigned_user_id' => $firstAgent->id,
        ]);
        $hiddenLead = $this->lead([
            'name' => 'Hidden Campaign Lead',
            'assigned_user_id' => $secondAgent->id,
        ]);
        $campaign->leads()->attach([$visibleLead->id, $hiddenLead->id]);

        $this->actingAs($firstAgent)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => $secondAgent->id,
            ]))
            ->assertOk()
            ->assertSee('Visible Campaign Lead')
            ->assertDontSee('Hidden Campaign Lead');

        $outsider = $this->userWithPermissions(['campaigns.view']);

        $this->actingAs($outsider)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertForbidden();
    }

    public function test_campaign_manager_only_sees_leads_assigned_to_them(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create();
        $campaign = $this->campaign($admin, [$admin, $agent]);

        $visibleLead = $this->lead([
            'name' => 'Manager Campaign Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $hiddenLead = $this->lead([
            'name' => 'Agent Campaign Lead',
            'assigned_user_id' => $agent->id,
        ]);
        $campaign->leads()->attach([$visibleLead->id, $hiddenLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Manager Campaign Lead')
            ->assertDontSee('Agent Campaign Lead');
    }

    public function test_campaign_manager_can_filter_leads_by_campaign_user(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create(['name' => 'Filter Agent']);
        $campaign = $this->campaign($admin, [$agent]);

        $managerLead = $this->lead([
            'name' => 'Manager Filter Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $agentLead = $this->lead([
            'name' => 'Filtered Agent Lead',
            'assigned_user_id' => $agent->id,
        ]);
        $campaign->leads()->attach([$managerLead->id, $agentLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => $agent->id,
            ]))
            ->assertOk()
            ->assertSee('Filter Agent')
            ->assertSee('(1)')
            ->assertSee('Filtered Agent Lead')
            ->assertDontSee('Manager Filter Lead');
    }

    public function test_campaign_manager_can_filter_unassigned_leads(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $assignedLead = $this->lead([
            'name' => 'Assigned Campaign Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $unassignedLead = $this->lead([
            'name' => 'Unassigned Campaign Lead',
            'assigned_user_id' => null,
        ]);
        $campaign->leads()->attach([$assignedLead->id, $unassignedLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => 'unassigned',
            ]))
            ->assertOk()
            ->assertSee('غير مسند لأي مستخدم (1)')
            ->assertSee('Unassigned Campaign Lead')
            ->assertDontSee('Assigned Campaign Lead');
    }

    public function test_campaign_leads_can_be_filtered_by_status(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $newStatus = $this->leadStatus();
        $wonStatus = LeadStatus::query()->create([
            'pipeline_stage_id' => $newStatus->pipeline_stage_id,
            'code' => 'campaign-won',
            'name_ar' => 'مكتمل',
            'position' => 2,
            'is_terminal' => true,
        ]);
        $newLead = $this->lead([
            'name' => 'New Status Campaign Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $wonLead = $this->lead([
            'name' => 'Won Status Campaign Lead',
            'assigned_user_id' => $admin->id,
            'lead_status_id' => $wonStatus->id,
        ]);
        $campaign->leads()->attach([$newLead->id, $wonLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'status' => $wonStatus->code,
            ]))
            ->assertOk()
            ->assertSee('Won Status Campaign Lead')
            ->assertDontSee('New Status Campaign Lead');
    }

    private function campaign(User $creator, array $users): Campaign
    {
        $campaign = Campaign::query()->create([
            'name' => 'Campaign Workspace',
            'cost' => 1000,
            'starts_at' => '2026-08-20 09:00',
            'ends_at' => '2026-08-31 18:00',
            'created_by_user_id' => $creator->id,
        ]);
        $campaign->users()->attach(
            collect($users)->pluck('id')->all(),
        );

        return $campaign;
    }

    private function lead(array $attributes = []): Lead
    {
        return Lead::query()->create(array_merge([
            'lead_status_id' => $this->leadStatus()->id,
            'name' => 'Campaign Lead',
            'first_name' => 'Campaign',
            'phone' => fake()->unique()->numerify('010########'),
            'source' => 'Campaign',
            'created_by' => 'Test',
        ], $attributes));
    }

    private function leadStatus(): LeadStatus
    {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'name_ar' => 'جديد',
                'position' => 1,
                'is_active' => true,
            ],
        );

        return LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'is_terminal' => false,
            ],
        );
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Campaign User '.fake()->unique()->word(),
            'code' => 'campaign-user-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ],
            );
            $group->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }

    private function superAdmin(): User
    {
        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'Super Admin',
                'is_system' => true,
            ],
        );
        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }
}

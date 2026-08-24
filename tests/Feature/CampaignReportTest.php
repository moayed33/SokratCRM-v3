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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_campaign_report_requires_report_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('v2.campaigns.reports'))
            ->assertForbidden();
    }

    public function test_report_calculates_period_metrics_and_current_donor_conversion(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#169a64', 2);

        $endedCampaign = $this->campaign(
            $admin,
            'August Ended Campaign',
            '2026-08-03 10:00:00',
            '2026-08-04 09:00:00',
            '2026-08-10 18:00:00',
        );
        $activeCampaign = $this->campaign(
            $admin,
            'August Active Campaign',
            '2026-08-05 10:00:00',
            '2026-08-05 09:00:00',
            '2026-08-30 18:00:00',
        );
        $this->campaign(
            $admin,
            'July Ended Campaign',
            '2026-07-02 10:00:00',
            '2026-07-02 09:00:00',
            '2026-07-20 18:00:00',
        );

        $sharedDonor = $this->lead($donorStatus, 'Shared Donor');
        $secondDonor = $this->lead($donorStatus, 'Second Donor');
        $newLead = $this->lead($newStatus, 'New Campaign Lead');

        $endedCampaign->leads()->attach([$sharedDonor->id, $newLead->id]);
        $activeCampaign->leads()->attach([$sharedDonor->id, $secondDonor->id]);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'period' => 'custom',
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('campaigns.reports')
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['created_campaigns'] === 2
                && $metrics['ended_campaigns'] === 1
                && $metrics['current_leads'] === 3
                && $metrics['donor_leads'] === 2
                && $metrics['total_campaign_cost'] === 3000.0
                && $metrics['lead_cost'] === 1000.0
                && $metrics['conversion_rate'] === 66.7)
            ->assertViewHas('timeline', static fn (array $timeline): bool => collect($timeline)->sum('created') === 2
                && collect($timeline)->sum('ended') === 1)
            ->assertViewHas('statusDistribution', static fn ($statuses): bool => $statuses->firstWhere('code', 'donor')['count'] === 2
                && $statuses->firstWhere('code', 'new')['count'] === 1);
    }

    public function test_report_can_filter_metrics_and_conversion_by_campaign(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#169a64', 2);
        $campaign = $this->campaign(
            $admin,
            'Selected Campaign',
            '2026-08-03 10:00:00',
            '2026-08-04 09:00:00',
            '2026-08-10 18:00:00',
        );
        $otherCampaign = $this->campaign(
            $admin,
            'Other Campaign',
            '2026-08-05 10:00:00',
            '2026-08-05 09:00:00',
            '2026-08-30 18:00:00',
        );

        $campaign->leads()->attach([
            $this->lead($donorStatus, 'Selected Donor')->id,
            $this->lead($newStatus, 'Selected New Lead')->id,
        ]);
        $otherCampaign->leads()->attach([
            $this->lead($donorStatus, 'Other Donor')->id,
        ]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.reports', [
                'campaign_id' => $campaign->id,
                'period' => 'custom',
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('name="campaign_id" value="'.$campaign->id.'"', false)
            ->assertSee(route('v2.campaigns.reports', [
                'period' => 'custom',
                'from' => '2026-08-01',
                'to' => '2026-08-31',
                'campaign_id' => $otherCampaign->id,
            ]))
            ->assertViewHas('selectedCampaign', static fn (?Campaign $selected): bool => $selected?->is($campaign) === true)
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['created_campaigns'] === 1
                && $metrics['ended_campaigns'] === 1
                && $metrics['current_leads'] === 2
                && $metrics['donor_leads'] === 1
                && $metrics['total_campaign_cost'] === 1000.0
                && $metrics['lead_cost'] === 500.0
                && $metrics['conversion_rate'] === 50.0);
    }

    public function test_report_filters_selected_campaign_metrics_by_employee(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $employeeOne = User::factory()->create(['name' => 'Employee One']);
        $employeeTwo = User::factory()->create(['name' => 'Employee Two']);
        $outsideEmployee = User::factory()->create(['name' => 'Outside Employee']);
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#169a64', 2);

        $campaign = $this->campaign(
            $admin,
            'Employee Campaign',
            '2026-08-03 10:00:00',
            '2026-08-04 09:00:00',
            '2026-08-30 18:00:00',
        );
        $otherCampaign = $this->campaign(
            $admin,
            'Second Employee Campaign',
            '2026-08-05 10:00:00',
            '2026-08-05 09:00:00',
            '2026-08-30 18:00:00',
        );
        $outsideCampaign = $this->campaign(
            $admin,
            'Outside Campaign',
            '2026-08-06 10:00:00',
            '2026-08-06 09:00:00',
            '2026-08-30 18:00:00',
        );

        $campaign->users()->attach([$employeeOne->id, $employeeTwo->id]);
        $otherCampaign->users()->attach($employeeTwo);
        $outsideCampaign->users()->attach($outsideEmployee);

        $employeeDonor = $this->lead($donorStatus, 'Employee Donor');
        $employeeDonor->update(['assigned_user_id' => $employeeOne->id]);
        $employeeNewLead = $this->lead($newStatus, 'Employee New Lead');
        $employeeNewLead->update(['assigned_user_id' => $employeeOne->id]);
        $otherEmployeeLead = $this->lead($newStatus, 'Other Employee Lead');
        $otherEmployeeLead->update(['assigned_user_id' => $employeeTwo->id]);
        $secondCampaignDonor = $this->lead($donorStatus, 'Second Campaign Donor');
        $secondCampaignDonor->update(['assigned_user_id' => $employeeTwo->id]);

        $campaign->leads()->attach([
            $employeeDonor->id,
            $employeeNewLead->id,
            $otherEmployeeLead->id,
        ]);
        $otherCampaign->leads()->attach($secondCampaignDonor);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.reports', [
                'campaign_id' => $campaign->id,
                'employee_id' => $employeeOne->id,
                'period' => 'all',
            ]))
            ->assertOk()
            ->assertSee('name="employee_id"', false)
            ->assertSee('value="'.$employeeOne->id.'" selected', false)
            ->assertSee('Employee One')
            ->assertSee('Employee Two')
            ->assertDontSee('Outside Employee')
            ->assertViewHas('employees', static fn ($employees): bool => $employees->modelKeys() === [
                $employeeOne->id,
                $employeeTwo->id,
            ])
            ->assertViewHas('selectedEmployee', static fn (?User $selected): bool => $selected?->is($employeeOne) === true)
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['created_campaigns'] === 1
                && $metrics['current_leads'] === 2
                && $metrics['donor_leads'] === 1
                && $metrics['total_campaign_cost'] === 1000.0
                && $metrics['lead_cost'] === 500.0
                && $metrics['conversion_rate'] === 50.0)
            ->assertViewHas('statusDistribution', static fn ($statuses): bool => $statuses->firstWhere('code', 'donor')['count'] === 1
                && $statuses->firstWhere('code', 'new')['count'] === 1);

        $this->get(route('v2.campaigns.reports', [
            'employee_id' => $employeeTwo->id,
            'period' => 'all',
        ]))
            ->assertOk()
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['created_campaigns'] === 2
                && $metrics['current_leads'] === 2
                && $metrics['donor_leads'] === 1
                && $metrics['total_campaign_cost'] === 2000.0
                && $metrics['lead_cost'] === 1000.0
                && $metrics['conversion_rate'] === 50.0);

        $this->get(route('v2.campaigns.reports', [
            'campaign_id' => $campaign->id,
            'employee_id' => $outsideEmployee->id,
        ]))->assertNotFound();
    }

    public function test_report_rejects_campaign_outside_the_users_visible_scope(): void
    {
        $admin = $this->superAdmin();
        $reporter = $this->userWithPermissions(['campaigns.reports']);
        $visibleCampaign = $this->campaign(
            $admin,
            'Visible Campaign',
            '2026-08-01 10:00:00',
            '2026-08-02 09:00:00',
            '2026-08-30 18:00:00',
        );
        $hiddenCampaign = $this->campaign(
            $admin,
            'Hidden Campaign',
            '2026-08-01 10:00:00',
            '2026-08-02 09:00:00',
            '2026-08-30 18:00:00',
        );
        $visibleCampaign->users()->attach($reporter);

        $this->actingAs($reporter)
            ->get(route('v2.campaigns.reports'))
            ->assertOk()
            ->assertSee('campaign-selector-card is-all is-active', false)
            ->assertSee('Visible Campaign')
            ->assertDontSee('Hidden Campaign')
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['lead_cost'] === 0.0);

        $this->get(route('v2.campaigns.reports', [
            'campaign_id' => $hiddenCampaign->id,
        ]))->assertNotFound();
    }

    public function test_report_rejects_an_excessive_custom_period(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('v2.campaigns.reports', [
                'period' => 'custom',
                'from' => '2000-01-01',
                'to' => '2021-01-02',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('to');
    }

    private function campaign(
        User $creator,
        string $name,
        string $createdAt,
        string $startsAt,
        string $endsAt,
    ): Campaign {
        $campaign = Campaign::query()->create([
            'name' => $name,
            'cost' => 1000,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by_user_id' => $creator->id,
        ]);
        $campaign->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $campaign;
    }

    private function lead(LeadStatus $status, string $name): Lead
    {
        return Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => $name,
            'first_name' => $name,
            'phone' => fake()->unique()->numerify('010########'),
            'source' => 'Campaign report test',
            'created_by' => 'Test',
        ]);
    }

    private function leadStatus(
        string $code,
        string $name,
        bool $terminal,
        string $color,
        int $position,
    ): LeadStatus {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => $code],
            [
                'name_ar' => $name,
                'position' => ((int) PipelineStage::query()->max('position')) + 1,
                'color' => $color,
                'is_active' => true,
            ],
        );

        return LeadStatus::query()->firstOrCreate(
            ['code' => $code],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => $name,
                'position' => $position,
                'color' => $color,
                'is_terminal' => $terminal,
            ],
        );
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Campaign Reporter',
            'code' => 'campaign-reporter',
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => 'campaigns', 'name_ar' => $code],
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
            ['name' => 'Super Admin', 'is_system' => true],
        );
        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }
}

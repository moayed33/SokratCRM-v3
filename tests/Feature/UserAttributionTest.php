<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_lead_is_attributed_to_authenticated_user(): void
    {
        $permission = Permission::query()->create([
            'code' => CrmPermission::LEADS_CREATE->value,
            'module' => CrmPermission::LEADS_CREATE->module(),
            'name_ar' => CrmPermission::LEADS_CREATE->label(),
        ]);
        $group = Group::query()->create([
            'name' => 'موظف المبيعات',
            'code' => 'sales-agent',
        ]);
        $group->permissions()->attach($permission);

        $user = User::factory()->create([
            'name' => 'Sales User',
        ]);
        $user->groups()->attach($group);

        $stage = PipelineStage::query()->create([
            'code' => 'initial',
            'name_ar' => 'البداية',
            'position' => 1,
            'color' => '#64748b',
            'is_active' => true,
        ]);
        $status = LeadStatus::query()->create([
            'pipeline_stage_id' => $stage->id,
            'code' => 'new',
            'name_ar' => 'جديد',
            'position' => 1,
            'color' => '#64748b',
            'is_terminal' => false,
        ]);

        $this->actingAs($user)
            ->post(route('v2.leads.store'), [
                'lead_status_id' => $status->id,
                'first_name' => 'New',
                'last_name' => 'Lead',
                'phone' => '01000000000',
                'source' => 'Website',
            ])
            ->assertRedirect(route('v2.leads'));

        $lead = Lead::query()->sole();

        $this->assertTrue($lead->assignedUser->is($user));
        $this->assertTrue($lead->creator->is($user));
        $this->assertSame($user->name, $lead->assigned_employee);
        $this->assertSame($user->name, $lead->created_by);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScopeAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Group $actorGroup;

    private Group $targetGroup;

    private Group $blockedTargetGroup;

    private User $actor;

    private User $peer;

    private User $target;

    private User $blockedTarget;

    private LeadStatus $status;

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

        $this->actorGroup = $this->createGroup(
            'scope-actor',
            [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_CREATE->value,
                CrmPermission::LEADS_UPDATE->value,
                CrmPermission::LEADS_ASSIGN->value,
                CrmPermission::LEADS_EXPORT->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::TASKS_VIEW->value,
                CrmPermission::QUOTATIONS_VIEW->value,
            ],
        );
        $this->targetGroup = $this->createGroup('scope-target');
        $this->blockedTargetGroup = $this->createGroup('scope-blocked');

        $this->actor = $this->createUser('Scope Actor', $this->actorGroup);
        $this->peer = $this->createUser('Scope Peer', $this->actorGroup);
        $this->target = $this->createUser('Allowed Target', $this->targetGroup);
        $this->blockedTarget = $this->createUser(
            'Blocked Target',
            $this->blockedTargetGroup,
        );

        $stage = PipelineStage::query()->create([
            'code' => 'initial',
            'name_ar' => 'أولي',
            'position' => 1,
            'is_active' => true,
        ]);
        $this->status = LeadStatus::query()->create([
            'pipeline_stage_id' => $stage->id,
            'code' => 'new',
            'name_ar' => 'جديد',
            'position' => 1,
            'is_terminal' => false,
        ]);
    }

    public function test_own_scope_applies_to_lead_pages_dashboard_tasks_and_export(): void
    {
        $assignedLead = $this->createLead(
            $this->actor,
            $this->actor,
            'Assigned Lead',
        );
        $createdLead = $this->createLead(
            $this->target,
            $this->actor,
            'Created Lead',
        );
        $foreignLead = $this->createLead(
            $this->peer,
            $this->peer,
            'Foreign Lead',
        );

        $this->assertSame(
            [$assignedLead->id, $createdLead->id],
            Lead::query()
                ->accessibleTo($this->actor)
                ->orderBy('id')
                ->pluck('id')
                ->all(),
        );

        $this->actingAs($this->actor)
            ->get(route('v2.leads'))
            ->assertOk()
            ->assertViewHas('totalLeads', 2);
        $this->actingAs($this->actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalLeads', 2);
        $this->actingAs($this->actor)
            ->get(route('v2.leads.kanban'))
            ->assertOk()
            ->assertViewHas('totalLeads', 2);
        $this->actingAs($this->actor)
            ->get(route('v2.tasks.status', ['status' => 'new']))
            ->assertOk()
            ->assertViewHas('totalLeads', 2)
            ->assertViewHas('totalStatusLeads', 2);
        $this->actingAs($this->actor)
            ->get(route('v2.leads.export'))
            ->assertOk()
            ->assertViewHas('totalLeads', 2);

        $this->actingAs($this->actor)
            ->get(route('v2.leads.show', $foreignLead))
            ->assertForbidden();
        $this->actingAs($this->actor)
            ->get(route('v2.leads.followups.index', $foreignLead))
            ->assertForbidden();
        $this->actingAs($this->actor)
            ->get(route('v2.leads.quotation.preview', $foreignLead))
            ->assertForbidden();
        $this->actingAs($this->actor)
            ->post(route('v2.leads.export-selected'), [
                'lead_ids' => [$foreignLead->id],
            ])
            ->assertUnprocessable();
    }

    public function test_group_scope_allows_access_to_leads_owned_by_group_members(): void
    {
        $peerLead = $this->createLead(
            $this->peer,
            $this->peer,
            'Peer Group Lead',
        );
        $crossGroupLead = $this->createLead(
            $this->target,
            $this->target,
            'Cross Group Lead',
        );

        $this->actingAs($this->actor)
            ->get(route('v2.leads.show', $peerLead))
            ->assertForbidden();

        $this->grantPermissions(
            $this->actorGroup,
            [CrmPermission::LEADS_SCOPE_GROUP->value],
        );
        $this->actor->unsetRelation('groups');

        $this->assertTrue($peerLead->isAccessibleTo($this->actor));
        $this->assertFalse($crossGroupLead->isAccessibleTo($this->actor));
        $this->actingAs($this->actor)
            ->get(route('v2.leads.show', $peerLead))
            ->assertOk();
        $this->actingAs($this->actor)
            ->get(route('v2.leads.show', $crossGroupLead))
            ->assertForbidden();
    }

    public function test_view_permission_defaults_to_assigned_or_created_leads(): void
    {
        $ownScopeGroup = $this->createGroup(
            'default-own-scope',
            [CrmPermission::LEADS_VIEW->value],
        );
        $ownScopeUser = $this->createUser(
            'Default Own Scope User',
            $ownScopeGroup,
        );
        $ownLead = $this->createLead(
            $ownScopeUser,
            $ownScopeUser,
            'Default Own Lead',
        );
        $foreignLead = $this->createLead(
            $this->target,
            $this->target,
            'Default Foreign Lead',
        );

        $this->assertTrue($ownLead->isAccessibleTo($ownScopeUser));
        $this->assertFalse($foreignLead->isAccessibleTo($ownScopeUser));
        $this->actingAs($ownScopeUser)
            ->get(route('v2.leads.show', $ownLead))
            ->assertOk();
        $this->actingAs($ownScopeUser)
            ->get(route('v2.leads.show', $foreignLead))
            ->assertForbidden();
    }

    public function test_assignment_requires_base_and_target_group_permissions(): void
    {
        $this->actingAs($this->actor)
            ->get(route('v2.leads.create'))
            ->assertOk()
            ->assertSee('name="assigned_user_id"', false)
            ->assertDontSee($this->target->name);

        $this->actingAs($this->actor)
            ->post(route('v2.leads.store'), $this->createPayload(
                'Denied Assignment',
                '5551001',
                $this->target,
            ))
            ->assertForbidden();

        $this->grantPermissions(
            $this->actorGroup,
            [LeadAssignment::groupPermissionCode($this->targetGroup)],
        );
        $this->actor->unsetRelation('groups');

        $this->actingAs($this->actor)
            ->get(route('v2.leads.create'))
            ->assertOk()
            ->assertSee($this->target->name)
            ->assertDontSee($this->blockedTarget->name);

        $this->actingAs($this->actor)
            ->post(route('v2.leads.store'), $this->createPayload(
                'Allowed Assignment',
                '5551002',
                $this->target,
            ))
            ->assertRedirect(route('v2.leads'));

        $created = Lead::query()
            ->where('phone', '5551002')
            ->firstOrFail();
        $this->assertSame($this->target->id, $created->assigned_user_id);
        $this->assertSame($this->target->name, $created->assigned_employee);
        $this->assertSame($this->actor->id, $created->created_by_user_id);
    }

    public function test_reassignment_and_inactive_targets_are_rejected_server_side(): void
    {
        $lead = $this->createLead(
            $this->target,
            $this->actor,
            'Reassignment Lead',
        );

        $this->actingAs($this->actor)
            ->patch(
                route('v2.leads.update', $lead),
                $this->updatePayload($lead, $this->blockedTarget),
            )
            ->assertForbidden();

        $this->grantPermissions(
            $this->actorGroup,
            [LeadAssignment::groupPermissionCode($this->blockedTargetGroup)],
        );
        $this->actor->unsetRelation('groups');
        $this->blockedTarget->update(['is_active' => false]);

        $this->actingAs($this->actor)
            ->patch(
                route('v2.leads.update', $lead),
                $this->updatePayload($lead, $this->blockedTarget),
            )
            ->assertForbidden();

        $this->blockedTarget->update(['is_active' => true]);
        $this->actingAs($this->actor)
            ->patch(
                route('v2.leads.update', $lead),
                $this->updatePayload($lead, $this->blockedTarget),
            )
            ->assertRedirect(route('v2.leads'));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_user_id' => $this->blockedTarget->id,
            'assigned_employee' => $this->blockedTarget->name,
        ]);
    }

    public function test_permission_matrix_accepts_the_same_permission_for_multiple_groups(): void
    {
        $superGroup = $this->createGroup(
            Group::SUPER_ADMIN_CODE,
            isSystem: true,
        );
        $superAdmin = $this->createUser('Matrix Admin', $superGroup);

        $response = $this->actingAs($superAdmin)
            ->from(route('v2.settings.permissions.index'))
            ->put(route('v2.settings.permissions.update'), [
                'permissions' => [
                    $this->actorGroup->id => [
                        CrmPermission::LEADS_VIEW->value,
                    ],
                    $this->targetGroup->id => [
                        CrmPermission::LEADS_VIEW->value,
                    ],
                    $this->blockedTargetGroup->id => [],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('v2.settings.permissions.index'));

        $this->assertTrue(
            $this->actorGroup->fresh()->permissions()
                ->where('code', CrmPermission::LEADS_VIEW->value)
                ->exists(),
        );
        $this->assertTrue(
            $this->targetGroup->fresh()->permissions()
                ->where('code', CrmPermission::LEADS_VIEW->value)
                ->exists(),
        );
    }

    private function createGroup(
        string $code,
        array $permissions = [],
        bool $isSystem = false,
    ): Group {
        $group = Group::query()->create([
            'name' => $code,
            'code' => $code,
            'is_system' => $isSystem,
        ]);

        $this->grantPermissions($group, $permissions);

        return $group;
    }

    private function grantPermissions(Group $group, array $codes): void
    {
        $ids = Permission::query()
            ->whereIn('code', $codes)
            ->pluck('id');
        $group->permissions()->syncWithoutDetaching($ids);
    }

    private function createUser(string $name, Group $group): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->groups()->attach($group);

        return $user;
    }

    private function createLead(
        User $assignee,
        User $creator,
        string $name,
    ): Lead {
        static $phoneSuffix = 2000;
        $phoneSuffix++;

        return Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => $name,
            'first_name' => $name,
            'phone' => '555'.$phoneSuffix,
            'source' => 'Test',
            'assigned_employee' => $assignee->name,
            'assigned_user_id' => $assignee->id,
            'created_by' => $creator->name,
            'created_by_user_id' => $creator->id,
            'next_follow_up_at' => now()->addHour(),
        ]);
    }

    private function createPayload(
        string $name,
        string $phone,
        User $assignee,
    ): array {
        return [
            'first_name' => $name,
            'phone' => $phone,
            'source' => 'Test',
            'assigned_user_id' => $assignee->id,
            'lead_status_id' => $this->status->id,
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\\TH:i'),
        ];
    }

    private function updatePayload(Lead $lead, User $assignee): array
    {
        return [
            'first_name' => $lead->first_name,
            'phone' => $lead->phone,
            'source' => $lead->source,
            'assigned_user_id' => $assignee->id,
        ];
    }
}

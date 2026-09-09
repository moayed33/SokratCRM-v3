<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanServerPaginationTest extends TestCase
{
    private User $admin;
    private PipelineStage $stage;
    private LeadStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        $this->admin = User::factory()->create([
            'name' => 'Kanban Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($group);

        $pos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $this->stage = PipelineStage::query()->create([
            'code' => 'stage_paginated_' . uniqid(),
            'name_ar' => 'مرحلة التصفح',
            'position' => $pos,
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $stPos = (int) (LeadStatus::query()->max('position') ?? 0) + 1;
        $this->status = LeadStatus::query()->create([
            'code' => 'status_paginated_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة التصفح',
            'position' => $stPos,
            'is_terminal' => false,
        ]);
    }

    public function test_kanban_view_loads_only_page_one_leads_initially(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 25; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $this->status->id,
                'name' => "عميل تجريبي {$i}",
                'phone' => "010000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->startOfDay()->addHours(2),
            ]);
            LeadPhone::query()->create([
                'lead_id' => $lead->id,
                'phone' => "010000000{$i}",
                'is_primary' => true,
            ]);
        }

        $response = $this->get(route('v2.leads.kanban'));
        $response->assertOk();

        $kanbanColumns = $response->viewData('kanbanColumns');
        $this->assertIsArray($kanbanColumns);

        $targetCol = null;
        foreach ($kanbanColumns as $col) {
            if ($col['id'] === $this->stage->id) {
                $targetCol = $col;
                break;
            }
        }

        $this->assertNotNull($targetCol);
        $this->assertSame(25, $targetCol['total_count']);
        $this->assertSame(25, $targetCol['scope_counts']['today']);
        $this->assertSame(1, $targetCol['current_page']);
        $this->assertSame(3, $targetCol['total_pages']);
        // Leads in today scope on initial page 1 must be exactly 10, not all 25
        $this->assertCount(10, $targetCol['scope_leads']['today']);
    }

    public function test_kanban_cards_endpoint_returns_paginated_json_and_html(): void
    {
        $this->actingAs($this->admin);

        for ($i = 1; $i <= 25; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $this->status->id,
                'name' => "عميل صفحة ثانية {$i}",
                'phone' => "011000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->startOfDay()->addHours(2),
            ]);
            LeadPhone::query()->create([
                'lead_id' => $lead->id,
                'phone' => "011000000{$i}",
                'is_primary' => true,
            ]);
        }

        // Request page 2
        $response = $this->getJson(route('v2.leads.kanban.cards', [
            'stage_id' => $this->stage->id,
            'scope' => 'today',
            'page' => 2,
            'limit' => 10,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'stage_id' => $this->stage->id,
            'scope' => 'today',
            'page' => 2,
            'per_page' => 10,
            'total' => 25,
            'total_pages' => 3,
            'from' => 11,
            'to' => 20,
            'count' => 10,
        ]);

        $html = $response->json('html');
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('class="kanban-card"', $html);
    }

    public function test_kanban_cards_endpoint_filters_by_search_keyword(): void
    {
        $this->actingAs($this->admin);

        $targetLead = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'عميل مميز للبحث',
            'phone' => '01099998888',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(2),
        ]);
        LeadPhone::query()->create([
            'lead_id' => $targetLead->id,
            'phone' => '01099998888',
            'is_primary' => true,
        ]);

        $otherLead = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'عميل آخر غير مطابق',
            'phone' => '01011112222',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(2),
        ]);
        LeadPhone::query()->create([
            'lead_id' => $otherLead->id,
            'phone' => '01011112222',
            'is_primary' => true,
        ]);

        $response = $this->getJson(route('v2.leads.kanban.cards', [
            'stage_id' => $this->stage->id,
            'scope' => 'today',
            'search' => 'مميز للبحث',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total' => 1,
        ]);
        $this->assertStringContainsString('عميل مميز للبحث', $response->json('html'));
        $this->assertStringNotContainsString('عميل آخر غير مطابق', $response->json('html'));
    }
}

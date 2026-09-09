<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Database\Seeders\CrmV2PipelineSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerDonorPipelineTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusDonor;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();
        $superAdminGroup->permissions()->syncWithoutDetaching(
            Permission::query()->pluck('id')->all()
        );

        $this->admin = User::factory()->create([
            'username' => 'admin_tester',
            'name' => 'مدير النظام التجريبي',
            'is_active' => true,
        ]);

        $this->admin->groups()->sync([$superAdminGroup->id]);

        $this->seed(CrmV2PipelineSeeder::class);

        $this->stageNew = PipelineStage::query()->where('code', 'new')->firstOrFail();
        $this->stageDonor = PipelineStage::query()->where('code', 'donor')->firstOrFail();
        $this->statusNew = LeadStatus::query()->where('pipeline_stage_id', $this->stageNew->id)->firstOrFail();
        $this->statusDonor = LeadStatus::query()->where('pipeline_stage_id', $this->stageDonor->id)->firstOrFail();
    }

    public function test_default_pipeline_has_four_primary_stages_with_stable_codes(): void
    {
        $stages = PipelineStage::query()->where('is_primary', true)->orderBy('position')->get();
        $this->assertCount(5, $stages);

        // Stable technical codes & Arabic display names
        $this->assertEquals('new', $stages[0]->code);
        $this->assertEquals('جديد', $stages[0]->name_ar);
        $this->assertTrue($stages[0]->isPrimary());

        $this->assertEquals('no_answer', $stages[1]->code);
        $this->assertEquals('لم يتم الرد', $stages[1]->name_ar);
        $this->assertTrue($stages[1]->isPrimary());

        $this->assertEquals('followup_later', $stages[2]->code);
        $this->assertEquals('متابعة لاحقة', $stages[2]->name_ar);
        $this->assertTrue($stages[2]->isPrimary());

        $this->assertEquals('not_interested', $stages[3]->code);
        $this->assertEquals('غير مهتم', $stages[3]->name_ar);
        $this->assertTrue($stages[3]->isPrimary());

        $this->assertEquals('donor', $stages[4]->code);
        $this->assertEquals('متبرع', $stages[4]->name_ar);
        $this->assertTrue($stages[4]->isPrimary());

        $this->assertGreaterThanOrEqual(4, DonationType::query()->count());
        $this->assertGreaterThanOrEqual(4, DonationPurpose::query()->count());
    }

    public function test_admin_can_add_multiple_custom_stages_without_limit(): void
    {
        $this->actingAs($this->admin);
        $initialCount = PipelineStage::query()->count();

        // 1. Visit settings stages page
        $response = $this->get(route('v2.settings.stages.index'));
        $response->assertOk();
        $response->assertSee('مراحل مسار العملاء');
        $response->assertSee('إضافة مرحلة مخصصة');

        // 2. Add custom stage
        $add3 = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة إضافية أولى',
            'color' => '#7b61df',
            'description_ar' => 'مرحلة إضافية للمتابعة الدورية',
        ]);
        $add3->assertRedirect(route('v2.settings.stages.index'));
        $this->assertEquals($initialCount + 1, PipelineStage::query()->count());
        // 3. Add 6th custom stage (should also succeed without limit)
        $add4 = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المرحلة الرابعة',
            'color' => '#dc2637',
            'description_ar' => 'مرحلة رابعة مخصصة',
        ]);
        $add4->assertRedirect(route('v2.settings.stages.index'));
        $this->assertEquals($initialCount + 2, PipelineStage::query()->count());
        // 4. Add 7th custom stage (should also succeed)
        $add5 = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المرحلة الخامسة',
            'color' => '#0284c7',
            'description_ar' => 'مرحلة خامسة مخصصة',
        ]);
        $this->assertEquals($initialCount + 3, PipelineStage::query()->count());
        $indexResponse = $this->get(route('v2.settings.stages.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('مرحلة إضافية أولى');
        $indexResponse->assertSee('المرحلة الرابعة');
        $indexResponse->assertSee('المرحلة الخامسة');
        $indexResponse->assertDontSee('الحد الأقصى مكتمل');
    }
    public function test_settings_stages_index_displays_independent_canonical_stages(): void
    {
        $this->actingAs($this->admin);

        // Add custom stage
        $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المتابعة اللاحقة',
            'color' => '#7b61df',
        ]);

        $response = $this->get(route('v2.settings.stages.index'));
        $response->assertOk();

        // Check canonical stages in table
        $response->assertSee('جديد');
        $response->assertSee('لم يتم الرد');
        $response->assertSee('غير مهتم');
        $response->assertSee('متبرع');
        $response->assertSee('المتابعة اللاحقة');
    }
    public function test_renaming_stage_does_not_change_stable_technical_code(): void
    {
        $this->actingAs($this->admin);

        $response = $this->patch(route('v2.settings.stages.update', $this->stageDonor), [
            'name_ar' => 'كبار المتبرعين',
            'color' => '#16a34a',
            'position' => 2,
        ]);
        $response->assertRedirect(route('v2.settings.stages.index'));

        $this->stageDonor->refresh();
        $this->assertEquals('كبار المتبرعين', $this->stageDonor->name_ar);
        $this->assertEquals('donor', $this->stageDonor->code); // Stable code untouched
    }

    public function test_primary_stage_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('v2.settings.stages.destroy', $this->stageNew));
        $response->assertSessionHasErrors(['stage']);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageNew->id]);
    }

    public function test_cannot_delete_stage_containing_leads(): void
    {
        $this->actingAs($this->admin);

        // Create 3rd stage
        $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة بها عملاء',
            'color' => '#7b61df',
        ]);
        $stage3 = PipelineStage::query()->where('name_ar', 'مرحلة بها عملاء')->firstOrFail();
        $status3 = $stage3->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'status_'.$stage3->id],
            [
                'pipeline_stage_id' => $stage3->id,
                'name_ar' => $stage3->name_ar,
                'position' => 5,
                'color' => '#7b61df',
                'is_terminal' => false,
            ]
        );
        // Attach a lead to this stage
        Lead::query()->create([
            'name' => 'عميل تجريبي في المرحلة 3',
            'phone' => '01099998888',
            'lead_status_id' => $status3->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        // Attempt delete
        $response = $this->delete(route('v2.settings.stages.destroy', $stage3));
        $response->assertSessionHasErrors(['stage']);
        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage3->id]);
    }

    public function test_customer_creation_defaults_to_new_stage(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('v2.leads.store'), [
            'name' => 'عميل تلقائي جديد',
            'phone' => '01011119999',
            // Omit pipeline_stage_id and lead_status_id to test default assignment
        ]);
        $response->assertRedirect(route('v2.leads'));

        $lead = Lead::query()->where('phone', '01011119999')->firstOrFail();
        $this->assertEquals('new', $lead->status?->stage?->code);
    }

    public function test_admin_can_create_customer_with_full_donor_attributes_and_phones(): void
    {
        $this->actingAs($this->admin);

        $donationType = DonationType::query()->firstOrFail();
        $donationPurpose = DonationPurpose::query()->firstOrFail();

        $payload = [
            'name' => 'د. محمود إبراهيم الشرقاوي',
            'phone' => '01012345678',
            'additional_phones' => [
                ['phone' => '01123456789', 'label' => 'عمل'],
                ['phone' => '01234567890', 'label' => 'واتساب'],
            ],
            'donation_type_id' => $donationType->id,
            'donation_cycle' => 'monthly',
            'donation_value' => 1500.50,
            'donation_purpose_id' => $donationPurpose->id,
            'responding_user_id' => $this->admin->id,
            'pipeline_stage_id' => $this->stageDonor->id,
            'response_details' => 'أبدى رغبة في التبرع الشهري بقيمة 1500 جنيه لدعم الحالات الحرجة.',
            'contact_date' => '2026-08-19',
            'next_follow_up_at' => '2026-08-25T14:30',
            'source' => 'إعلان فيسبوك',
        ];

        $response = $this->post(route('v2.leads.store'), $payload);
        $response->assertRedirect(route('v2.leads'));

        // Assert lead record in database
        $lead = Lead::query()->where('phone', '01012345678')->firstOrFail();
        $this->assertEquals('د. محمود إبراهيم الشرقاوي', $lead->name);
        $this->assertEquals($donationType->name_ar, $lead->donation_type);
        $this->assertEquals($donationType->id, $lead->donation_type_id);
        $this->assertEquals('monthly', $lead->donation_cycle);
        $this->assertEquals('1500.50', (string) $lead->donation_value);
        $this->assertEquals($donationPurpose->name_ar, $lead->donation_purpose);
        $this->assertEquals($donationPurpose->id, $lead->donation_purpose_id);
        $this->assertEquals($this->admin->id, $lead->responding_user_id);
        $this->assertEquals($this->admin->id, $lead->assigned_user_id);
        $this->assertEquals('donor', $lead->status?->stage?->code);

        // Assert normalized phones
        $this->assertCount(3, $lead->phones);
        $primaryPhone = $lead->primaryPhone;
        $this->assertNotNull($primaryPhone);
        $this->assertEquals('01012345678', $primaryPhone->phone);
        $this->assertTrue($primaryPhone->is_primary);

        $additionalPhones = $lead->additionalPhones;
        $this->assertCount(2, $additionalPhones);
        $this->assertTrue($additionalPhones->contains('phone', '01123456789'));
        $this->assertTrue($additionalPhones->contains('phone', '01234567890'));

    }

    public function test_profile_edit_cannot_bypass_followup_transition_workflow(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'طارق السعيد',
            'phone' => '01055554444',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->patch(route('v2.leads.update', $lead), [
            'name' => 'طارق السعيد المنشاوي',
            'phone' => '01055554444',
            'donation_cycle' => 'annual',
            'donation_value' => 5000,
            'pipeline_stage_id' => $this->stageDonor->id,
            'lead_status_id' => $this->statusDonor->id,
            'next_follow_up_at' => '2027-08-19T10:00',
        ]);

        $response->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertSame('طارق السعيد المنشاوي', $lead->name);
        $this->assertSame($this->statusNew->id, $lead->lead_status_id);
        $this->assertSame('new', $lead->status?->stage?->code);
    }

    public function test_dashboard_renders_dynamic_donor_kpis_and_stage_cards(): void
    {
        $this->actingAs($this->admin);

        // Seed 3 new customers, 2 donor customers
        for ($i = 1; $i <= 3; $i++) {
            Lead::query()->create([
                'name' => "عميل جديد {$i}",
                'phone' => "0101111000{$i}",
                'lead_status_id' => $this->statusNew->id,
                'created_by' => $this->admin->name,
                'created_by_user_id' => $this->admin->id,
            ]);
        }

        for ($j = 1; $j <= 2; $j++) {
            Lead::query()->create([
                'name' => "متبرع مؤكد {$j}",
                'phone' => "0102222000{$j}",
                'lead_status_id' => $this->statusDonor->id,
                'donation_value' => 1000 * $j,
                'created_by' => $this->admin->name,
                'created_by_user_id' => $this->admin->id,
            ]);
        }

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // 1. Core KPIs
        $response->assertSee('إجمالي العملاء');
        $response->assertSee('نسبة التحويل إلى متبرع');
        $response->assertSee('إجمالي قيمة التبرعات');
        $response->assertSee('المتابعة اليوم');

        // Check conversion rate: 2 donors out of 5 customers = 40.0%
        $response->assertSee('40%');
        // Total donation value: 1000 + 2000 = 3000
        $response->assertSee('3,000.00');

        // 2. Dynamic Stage Summary Cards
        $response->assertSee('مسار رحلة المتبرع');
        $response->assertSee('جديد');
        $response->assertSee('متبرع');

    }

    public function test_dashboard_dynamically_shows_optional_third_stage_when_created(): void
    {
        $this->actingAs($this->admin);

        // Add 3rd stage
        $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المتابعة اللاحقة',
            'color' => '#7b61df',
        ]);

        $stage3 = PipelineStage::query()->where('name_ar', 'المتابعة اللاحقة')->firstOrFail();
        $status3 = $stage3->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'status_'.$stage3->id],
            [
                'pipeline_stage_id' => $stage3->id,
                'name_ar' => $stage3->name_ar,
                'position' => 5,
                'color' => '#7b61df',
                'is_terminal' => false,
            ]
        );
        Lead::query()->create([
            'name' => 'عميل في المرحلة الثالثة',
            'phone' => '01033334444',
            'lead_status_id' => $status3->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('المتابعة اللاحقة');
        $response->assertSee(__('crm.active_stages'));
    }

    public function test_dashboard_shows_active_zero_lead_stage_and_excludes_inactive_stage(): void
    {
        $this->actingAs($this->admin);

        $pos = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $activeEmptyStage = PipelineStage::query()->create([
            'code' => 'stage_empty_active',
            'name_ar' => 'مرحلة نشطة بدون عملاء',
            'position' => $pos,
            'color' => '#8b5cf6',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_inactive_test',
            'name_ar' => 'مرحلة معطلة مخفية',
            'position' => $pos + 1,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('مرحلة نشطة بدون عملاء');
        $response->assertDontSee('مرحلة معطلة مخفية');
        $response->assertSee('0%');
    }

    public function test_kanban_dynamically_shows_active_pipeline_stages_and_zero_lead_stages(): void
    {
        $this->actingAs($this->admin);

        $activeCustomStage = PipelineStage::query()->create([
            'code' => 'stage_kanban_active',
            'name_ar' => 'مرحلة كانبان مخصصة',
            'position' => 12,
            'color' => '#14b8a6',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $inactiveKanbanStage = PipelineStage::query()->create([
            'code' => 'stage_kanban_inactive',
            'name_ar' => 'مرحلة كانبان معطلة',
            'position' => 13,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $response = $this->get(route('v2.leads.kanban'));
        $response->assertOk();
        $response->assertSee('مرحلة كانبان مخصصة');
        $response->assertDontSee('مرحلة كانبان معطلة');
        $response->assertViewHas('kanbanColumns', function ($columns) {
            $codes = array_column($columns, 'code');
            return in_array('stage_kanban_active', $codes, true)
                && ! in_array('stage_kanban_inactive', $codes, true);
        });
    }

    public function test_stage_icon_can_be_saved_updated_and_cleared(): void
    {
        $this->actingAs($this->admin);

        // 1. Create with icon
        $response = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة مع أيقونة نجمة',
            'color' => '#f59e0b',
            'icon' => 'bi-star',
        ]);
        $response->assertRedirect(route('v2.settings.stages.index'));

        $stage = PipelineStage::query()->where('name_ar', 'مرحلة مع أيقونة نجمة')->firstOrFail();
        $this->assertSame('bi-star', $stage->icon);

        // 2. Update icon
        $updateResponse = $this->patch(route('v2.settings.stages.update', $stage), [
            'name_ar' => 'مرحلة مع أيقونة قلب',
            'color' => '#ef4444',
            'icon' => 'bi-heart-fill',
            'position' => $stage->position,
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect(route('v2.settings.stages.index'));

        $stage->refresh();
        $this->assertSame('bi-heart-fill', $stage->icon);

        // 3. Clear icon (No Icon)
        $clearResponse = $this->patch(route('v2.settings.stages.update', $stage), [
            'name_ar' => 'مرحلة بدون أيقونة',
            'color' => '#64748b',
            'icon' => '',
            'position' => $stage->position,
            'is_active' => 1,
        ]);
        $clearResponse->assertRedirect(route('v2.settings.stages.index'));

        $stage->refresh();
        $this->assertNull($stage->icon);

        // 4. View renders icon picker
        $viewResponse = $this->get(route('v2.settings.stages.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('addStageIconWrap');
        $viewResponse->assertSee('editStageIconWrap');
        $viewResponse->assertSee('crmIconsMap');
    }
    public function test_customer_show_page_displays_all_normalized_data(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'فاطمة الزهراء علي',
            'phone' => '01077776666',
            'lead_status_id' => $this->statusDonor->id,
            'donation_type' => 'كفالة أيتام',
            'donation_cycle' => 'monthly',
            'donation_value' => 800,
            'donation_purpose' => 'كفالات شهرية',
            'response_details' => 'ترغب في كفالة طفلين شهرياً وتأكيد التحويل البنكي.',
            'contact_date' => now(),
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01077776666',
            'is_primary' => true,
            'label' => 'أساسي',
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01144443333',
            'is_primary' => false,
            'label' => 'واتساب',
        ]);


        $response = $this->get(route('v2.leads.show', $lead));
        $response->assertOk();
        $response->assertSee('فاطمة الزهراء علي');
        $response->assertSee('01077776666');
        $response->assertSee('01144443333');
        $response->assertSee('كفالة أيتام');
        $response->assertSee('ترغب في كفالة طفلين شهرياً');
    }

    public function test_customer_search_finds_by_primary_phone_and_additional_phone(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'صبري عبد الرحمن',
            'phone' => '01099887766',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01099887766',
            'is_primary' => true,
            'label' => 'أساسي',
        ]);

        LeadPhone::query()->create([
            'lead_id' => $lead->id,
            'phone' => '01299991234',
            'is_primary' => false,
            'label' => 'عمل',
        ]);


        // 1. Search by primary phone
        $res1 = $this->get(route('v2.leads', ['q' => '01099887766']));
        $res1->assertOk();
        $res1->assertSee('صبري عبد الرحمن');

        // 2. Search by additional phone
        $res2 = $this->get(route('v2.leads', ['q' => '01299991234']));
        $res2->assertOk();
        $res2->assertSee('صبري عبد الرحمن');

    }

    public function test_recording_followup_updates_customer_response_details_and_contact_date(): void
    {
        $this->actingAs($this->admin);
        $donationType = DonationType::query()->where('is_active', true)->firstOrFail();

        $lead = Lead::query()->create([
            'name' => 'خالد عبد الوهاب',
            'phone' => '01011112222',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'outcome' => 'تم الاتصال بالمتبرع وتأكيد التبرع بمبلغ 2000 جنيه لحالات العمليات الجراحية.',
            'communication_type' => 'call',
            'donation_type_id' => $donationType->id,
            'donation_value' => 2000,
            'donation_cycle' => 'monthly',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals($this->statusDonor->id, $lead->lead_status_id);
        $this->assertEquals('donor', $lead->status?->stage?->code);
        $this->assertStringContainsString('2000 جنيه', $lead->response_details);
        $this->assertNotNull($lead->contact_date);
        $this->assertEquals($this->admin->id, $lead->responding_user_id);
    }

    public function test_leads_can_be_filtered_by_minimum_and_maximum_donation_amount(): void
    {
        $this->actingAs($this->admin);

        $leadLow = Lead::query()->create([
            'name' => 'متبرع بمبلغ صغير',
            'phone' => '01010000001',
            'donation_value' => 250,
            'lead_status_id' => $this->statusDonor->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $leadMid = Lead::query()->create([
            'name' => 'متبرع بمبلغ متوسط',
            'phone' => '01010000002',
            'donation_value' => 1500,
            'lead_status_id' => $this->statusDonor->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        $leadHigh = Lead::query()->create([
            'name' => 'متبرع بمبلغ كبير',
            'phone' => '01010000003',
            'donation_value' => 7500,
            'lead_status_id' => $this->statusDonor->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Filter with donation_min = 1000
        $responseMin = $this->get(route('v2.leads', ['donation_min' => 1000]));
        $responseMin->assertOk();
        $responseMin->assertSee('متبرع بمبلغ متوسط');
        $responseMin->assertSee('متبرع بمبلغ كبير');
        $responseMin->assertDontSee('متبرع بمبلغ صغير');

        // 2. Filter with donation_max = 2000
        $responseMax = $this->get(route('v2.leads', ['donation_max' => 2000]));
        $responseMax->assertOk();
        $responseMax->assertSee('متبرع بمبلغ صغير');
        $responseMax->assertSee('متبرع بمبلغ متوسط');
        $responseMax->assertDontSee('متبرع بمبلغ كبير');

        // 3. Filter with range: donation_min = 500 and donation_max = 3000
        $responseRange = $this->get(route('v2.leads', ['donation_min' => 500, 'donation_max' => 3000]));
        $responseRange->assertOk();
        $responseRange->assertSee('متبرع بمبلغ متوسط');
        $responseRange->assertDontSee('متبرع بمبلغ صغير');
        $responseRange->assertDontSee('متبرع بمبلغ كبير');
    }

    public function test_donor_transition_modal_contains_other_option_in_donation_cycle_dropdown(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل مرحلة التحويل',
            'phone' => '01099990001',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $lead,
            'target_status_id' => $this->statusDonor->id,
        ]));

        $response->assertOk();
        $response->assertSee('value="other"', false);
        $response->assertSee('أخرى');
        $response->assertSee('id="customerPreferredSchedulePanel"', false);
        $response->assertSee('name="preferred_donation_date"', false);
        $response->assertSee('name="preferred_donation_time"', false);
    }

    public function test_donor_transition_with_other_cycle_requires_preferred_donation_date(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل اختبار موعد فارغ',
            'phone' => '01099990002',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $donationType = DonationType::query()->first();

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'موافقة على التبرع بموعد مخصص',
            'donation_type_id' => $donationType->id,
            'donation_value' => 500,
            'donation_cycle' => 'other',
            'preferred_donation_date' => '',
            'donation_way' => 'instant',
            'instant_donation_method_id' => \App\Models\InstantDonationMethod::query()->where('is_active', true)->value('id'),
        ]);

        $response->assertSessionHasErrors('preferred_donation_date');
        $this->assertNotSame((int) $this->statusDonor->id, (int) $lead->fresh()->lead_status_id);
        $this->assertDatabaseMissing('donations', [
            'lead_id' => $lead->id,
        ]);
    }

    public function test_donor_transition_with_other_cycle_and_preferred_date_and_time_succeeds(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل موعد مخصص مؤكد',
            'phone' => '01099990003',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $donationType = DonationType::query()->first();
        $instantMethod = \App\Models\InstantDonationMethod::query()->where('is_active', true)->first();

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق على موعد مخصص للتبرع',
            'donation_type_id' => $donationType->id,
            'donation_value' => 1200,
            'donation_cycle' => 'other',
            'preferred_donation_date' => '2026-09-18',
            'preferred_donation_time' => '11:30',
            'preferred_donation_note' => 'التواصل هاتفياً قبل الموعد بنصف ساعة',
            'donation_way' => 'instant',
            'instant_donation_method_id' => $instantMethod->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $freshLead = $lead->fresh();
        $this->assertSame((int) $this->statusDonor->id, (int) $freshLead->lead_status_id);
        $this->assertSame('other', $freshLead->donation_cycle);
        $this->assertSame('2026-09-18 11:30:00', $freshLead->next_follow_up_at?->toDateTimeString());

        $this->assertDatabaseHas('donations', [
            'lead_id' => $lead->id,
            'amount' => '1200.00',
            'cycle' => 'other',
        ]);
    }

    public function test_donor_transition_switching_from_other_to_monthly_ignores_stale_preferred_date(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل تراجع عن الموعد المخصص لاختيار شهري',
            'phone' => '01099990004',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $donationType = DonationType::query()->first();
        $instantMethod = \App\Models\InstantDonationMethod::query()->where('is_active', true)->first();

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->statusDonor->id,
            'communication_type' => 'call',
            'outcome' => 'تم اختيار التبرع الشهري',
            'donation_type_id' => $donationType->id,
            'donation_value' => 800,
            'donation_cycle' => 'monthly',
            'preferred_donation_date' => '2026-01-01', // Stale input
            'preferred_donation_time' => '09:00',
            'donation_way' => 'instant',
            'instant_donation_method_id' => $instantMethod->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $freshLead = $lead->fresh();
        $this->assertSame('monthly', $freshLead->donation_cycle);
        $this->assertNotNull($freshLead->next_follow_up_at);
        $this->assertEqualsWithDelta(now()->addMonthsNoOverflow(1)->timestamp, $freshLead->next_follow_up_at->timestamp, 120);
    }

    public function test_predefined_cycles_regression_in_donor_transition(): void
    {
        $cycles = ['one_time', 'monthly', 'quarterly', 'semi_annual', 'annual'];
        $donationType = DonationType::query()->first();
        $instantMethod = \App\Models\InstantDonationMethod::query()->where('is_active', true)->first();

        foreach ($cycles as $idx => $cycle) {
            $lead = Lead::query()->create([
                'name' => 'متبرع دوري ' . $cycle,
                'phone' => '0108888000' . $idx,
                'lead_status_id' => $this->statusNew->id,
                'assigned_user_id' => $this->admin->id,
            ]);

            $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
                'lead_status_id' => $this->statusDonor->id,
                'communication_type' => 'call',
                'outcome' => 'تسجيل تبرع دوري ' . $cycle,
                'donation_type_id' => $donationType->id,
                'donation_value' => 1000,
                'donation_cycle' => $cycle,
                'donation_way' => 'instant',
                'instant_donation_method_id' => $instantMethod->id,
            ]);

            $response->assertSessionHasNoErrors();
            $fresh = $lead->fresh();
            $this->assertSame($cycle, $fresh->donation_cycle);
            if ($cycle === 'one_time') {
                $this->assertNull($fresh->next_follow_up_at);
            } else {
                $this->assertNotNull($fresh->next_follow_up_at);
            }
        }
    }
}

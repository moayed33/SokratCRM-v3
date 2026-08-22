<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadPhone;
use App\Models\LeadRelatedPerson;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Database\Seeders\CrmV2PipelineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDonorPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageDonor;
    private LeadStatus $statusNew;
    private LeadStatus $statusDonor;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Administrator']
        );

        foreach (CrmPermission::values() as $code) {
            $perm = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0] ?? 'crm',
                    'name_ar' => $code,
                    'description' => $code,
                ]
            );
            $superAdminGroup->permissions()->syncWithoutDetaching([$perm->id]);
        }

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

    public function test_default_pipeline_has_two_primary_stages_with_stable_codes_new_and_donor(): void
    {
        $stages = PipelineStage::query()->orderBy('position')->get();
        $this->assertCount(2, $stages);

        $firstStage = $stages[0];
        $secondStage = $stages[1];

        // Stable technical codes & Arabic display names
        $this->assertEquals('new', $firstStage->code);
        $this->assertEquals('جديد', $firstStage->name_ar);
        $this->assertTrue($firstStage->isPrimary());

        $this->assertEquals('donor', $secondStage->code);
        $this->assertEquals('متبرع', $secondStage->name_ar);
        $this->assertTrue($secondStage->isPrimary());

        $this->assertGreaterThanOrEqual(4, DonationType::query()->count());
        $this->assertGreaterThanOrEqual(4, DonationPurpose::query()->count());
    }

    public function test_admin_can_add_third_optional_stage_but_cannot_exceed_max_three_stages(): void
    {
        $this->actingAs($this->admin);

        // 1. Visit settings stages page
        $response = $this->get(route('v2.settings.stages.index'));
        $response->assertOk();
        $response->assertSee('مراحل مسار العملاء');
        $response->assertSee('إضافة مرحلة إضافية');

        // 2. Add 3rd optional stage (should succeed)
        $addResponse = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المتابعة اللاحقة',
            'color' => '#7b61df',
            'description_ar' => 'مرحلة إضافية للمتابعة الدورية',
        ]);
        $addResponse->assertRedirect(route('v2.settings.stages.index'));
        $this->assertEquals(3, PipelineStage::query()->count());

        $stage3 = PipelineStage::query()->where('name_ar', 'المتابعة اللاحقة')->first();
        $this->assertNotNull($stage3);
        $this->assertFalse($stage3->isPrimary());

        // 3. Attempt to add 4th stage (MUST BE REJECTED)
        $fourthResponse = $this->post(route('v2.settings.stages.store'), [
            'name_ar' => 'المرحلة الرابعة المرفوضة',
            'color' => '#dc2637',
        ]);
        $fourthResponse->assertSessionHasErrors(['stage']);
        $this->assertEquals(3, PipelineStage::query()->count());

        // 4. View page now displays limit reached badge
        $indexResponse = $this->get(route('v2.settings.stages.index'));
        $indexResponse->assertSee('الحد الأقصى مكتمل (3 مراحل)');
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
        $status3 = LeadStatus::query()->where('pipeline_stage_id', $stage3->id)->firstOrFail();

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

    public function test_admin_can_create_customer_with_full_donor_attributes_phones_and_related_people(): void
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
            'related_people' => [
                [
                    'name' => 'أحمد الشرقاوي',
                    'phone' => '01511112222',
                    'relationship_type' => 'ابن / ابنة',
                    'notes' => 'للتواصل في حالة عدم الرد',
                ],
                [
                    'name' => 'منى محمود',
                    'phone' => '01033334444',
                    'relationship_type' => 'زوج / زوجة',
                    'notes' => 'متابعة كفالات الأيتام',
                ],
            ],
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

        // Assert related people
        $this->assertCount(2, $lead->relatedPeople);
        $this->assertTrue($lead->relatedPeople->contains('name', 'أحمد الشرقاوي'));
        $this->assertTrue($lead->relatedPeople->contains('name', 'منى محمود'));
    }

    public function test_moving_customer_to_donor_preserves_next_follow_up_capability(): void
    {
        $this->actingAs($this->admin);

        $lead = Lead::query()->create([
            'name' => 'طارق السعيد',
            'phone' => '01055554444',
            'lead_status_id' => $this->statusNew->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        // Move to donor stage with recurring annual donation and future follow-up
        $updatePayload = [
            'name' => 'طارق السعيد المنشاوي',
            'phone' => '01055554444',
            'donation_cycle' => 'annual',
            'donation_value' => 5000,
            'pipeline_stage_id' => $this->stageDonor->id,
            'next_follow_up_at' => '2027-08-19T10:00',
        ];

        $response = $this->patch(route('v2.leads.update', $lead), $updatePayload);
        $response->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertEquals('donor', $lead->status?->stage?->code);
        $this->assertEquals('annual', $lead->donation_cycle);
        $this->assertEquals('5000.00', (string) $lead->donation_value);
        $this->assertNotNull($lead->next_follow_up_at);
        $this->assertEquals('2027-08-19 10:00:00', $lead->next_follow_up_at->format('Y-m-d H:i:s'));
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

        // 3. 4-step animated banner
        $response->assertSee('تواصل');
        $response->assertSee('متابعة');
        $response->assertSee('متبرع ✓');
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
        $status3 = LeadStatus::query()->where('pipeline_stage_id', $stage3->id)->firstOrFail();

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
        $response->assertSee(__('crm.active_statuses'));
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

        LeadRelatedPerson::query()->create([
            'lead_id' => $lead->id,
            'name' => 'علي حسن',
            'phone' => '01211119999',
            'relationship_type' => 'زوج / زوجة',
            'notes' => 'المسؤول عن تسليم التبرع',
        ]);

        $response = $this->get(route('v2.leads.show', $lead));
        $response->assertOk();
        $response->assertSee('فاطمة الزهراء علي');
        $response->assertSee('01077776666');
        $response->assertSee('01144443333');
        $response->assertSee('كفالة أيتام');
        $response->assertSee('علي حسن');
        $response->assertSee('زوج / زوجة');
        $response->assertSee('المسؤول عن تسليم التبرع');
        $response->assertSee('ترغب في كفالة طفلين شهرياً');
    }

    public function test_customer_search_finds_by_primary_phone_additional_phone_and_related_person(): void
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

        LeadRelatedPerson::query()->create([
            'lead_id' => $lead->id,
            'name' => 'هشام عبد الرحمن',
            'phone' => '01588884321',
            'relationship_type' => 'أخ / أخت',
        ]);

        // 1. Search by primary phone
        $res1 = $this->get(route('v2.leads', ['q' => '01099887766']));
        $res1->assertOk();
        $res1->assertSee('صبري عبد الرحمن');

        // 2. Search by additional phone
        $res2 = $this->get(route('v2.leads', ['q' => '01299991234']));
        $res2->assertOk();
        $res2->assertSee('صبري عبد الرحمن');

        // 3. Search by related person name
        $res3 = $this->get(route('v2.leads', ['q' => 'هشام عبد الرحمن']));
        $res3->assertOk();
        $res3->assertSee('صبري عبد الرحمن');

        // 4. Search by related person phone
        $res4 = $this->get(route('v2.leads', ['q' => '01588884321']));
        $res4->assertOk();
        $res4->assertSee('صبري عبد الرحمن');
    }

    public function test_recording_followup_updates_customer_response_details_and_contact_date(): void
    {
        $this->actingAs($this->admin);

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
            'next_follow_up_at' => '2026-09-01T10:00',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals($this->statusDonor->id, $lead->lead_status_id);
        $this->assertEquals('donor', $lead->status?->stage?->code);
        $this->assertStringContainsString('2000 جنيه', $lead->response_details);
        $this->assertNotNull($lead->contact_date);
        $this->assertEquals($this->admin->id, $lead->responding_user_id);
    }
}

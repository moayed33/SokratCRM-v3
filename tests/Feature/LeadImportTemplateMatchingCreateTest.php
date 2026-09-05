<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFormField;
use App\Models\LeadPhone;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\LeadFieldSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeadImportTemplateMatchingCreateTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Branch $branch;
    private Campaign $campaign;
    private DonationType $donationType;
    private DonationPurpose $donationPurpose;
    private PipelineStage $stage;
    private LeadStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $group = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            ['name' => 'Super Admins', 'is_system' => true]
        );

        $this->admin = User::query()->firstOrCreate(
            ['email' => 'import_admin@sokratcrm.test'],
            [
                'name' => 'Import Testing Admin',
                'username' => 'import_admin_unique',
                'password' => bcrypt('Password123!'),
                'is_active' => true,
            ]
        );

        $this->admin->groups()->syncWithoutDetaching([$group->id]);

        $permCodes = [
            CrmPermission::LEADS_VIEW,
            CrmPermission::LEADS_CREATE,
            CrmPermission::LEADS_IMPORT,
            CrmPermission::LEADS_EXPORT,
            CrmPermission::LEADS_ASSIGN,
            CrmPermission::LEADS_SCOPE_ALL,
            CrmPermission::CAMPAIGNS_VIEW,
            CrmPermission::CAMPAIGNS_CREATE,
        ];

        foreach ($permCodes as $code) {
            $perm = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['name_ar' => $code, 'description' => $code, 'module' => 'leads']
            );
            $group->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $this->branch = Branch::query()->create([
            'name_ar' => 'فرع القاهرة للاستيراد',
            'name_en' => 'Cairo Branch Import',
            'code' => 'CAI_IMP',
            'is_active' => true,
        ]);

        $this->campaign = Campaign::query()->create([
            'name' => 'حملة رمضان الخير للاستيراد',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'created_by_user_id' => $this->admin->id,
        ]);

        $this->donationType = DonationType::query()->firstOrCreate(
            ['name_ar' => 'كفالة أيتام'],
            [
                'name_en' => 'Orphan Sponsorship',
                'is_active' => true,
                'position' => 1,
            ]
        );

        $this->donationPurpose = DonationPurpose::query()->firstOrCreate(
            ['name_ar' => 'رعاية تعليمية'],
            [
                'name_en' => 'Educational Care',
                'is_active' => true,
                'position' => 1,
            ]
        );

        $this->stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرعون رسميون', 'position' => 1, 'is_active' => true]
        );

        $this->status = LeadStatus::query()->firstOrCreate(
            ['code' => 'donor'],
            ['name_ar' => 'متبرع', 'position' => 1, 'is_active' => true, 'pipeline_stage_id' => $this->stage->id]
        );
    }

    public function test_import_template_download_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.import.template'));
        $response->assertOk();
        $response->assertHeader('Content-Disposition');

        $content = $response->streamedContent();
        $tmp = tempnam(sys_get_temp_dir(), 'test_tpl_');
        file_put_contents($tmp, $content);

        $zip = new \ZipArchive();
        $opened = $zip->open($tmp);
        $this->assertTrue($opened);

        // Read sheet1 XML to verify there are only columns A and B (Name and Phone)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="B1"', $sheetXml);
        $this->assertStringNotContainsString('r="C1"', $sheetXml);
    }

    public function test_import_with_matching_create_fields_parses_and_persists_lead(): void
    {
        $csvHeaders = implode(',', [
            'اسم العميل / المتبرع',
            'رقم الهاتف الأساسي',
            'أرقام هواتف إضافية',
            'البريد الإلكتروني',
            'اسم الشركة',
            'نشاط الشركة',
            'المحافظة',
            'العنوان',
            'نوع التبرع',
            'دورة التبرع',
            'قيمة التبرع',
            'غرض التبرع',
            'الموظف المسؤول',
            'الفرع',
            'المصدر',
            'الحملة',
            'تاريخ التواصل',
            'تفاصيل الرد والمكالمة',
            'الحالة',
        ]);

        $csvRow = implode(',', [
            'محمود عبد الله',
            '01012345678',
            '01187654321',
            'mahmoud@example.com',
            'شركة النيل للتجارة',
            'تجارة وتوزيع',
            'القاهرة',
            'شارع النصر مدينة نصر',
            'كفالة أيتام',
            'شهري',
            '2500',
            'رعاية تعليمية',
            'Import Testing Admin',
            'فرع القاهرة للاستيراد',
            'إعلان فيسبوك',
            'حملة رمضان الخير للاستيراد',
            '2026-08-25',
            'تم الاتفاق على التبرع الشهري',
            'متبرع',
        ]);

        $csvContent = $csvHeaders."\n".$csvRow."\n";
        $file = UploadedFile::fake()->createWithContent('leads_matching_create.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
        ]);

        $previewResponse->assertOk();
        $preview = $previewResponse->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(1, $preview['total_count']);
        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(0, $preview['error_count']);
        $this->assertSame('محمود عبد الله', $preview['rows'][0]['name']);
        $this->assertSame('01012345678', $preview['rows'][0]['phone']);
        $this->assertSame('Import Testing Admin', $preview['rows'][0]['assigned_employee']);
        $this->assertNotEmpty($preview['employee_distribution']);
        $this->assertSame('Import Testing Admin', $preview['employee_distribution'][0]['name']);
        $this->assertSame(1, $preview['employee_distribution'][0]['count']);
        $this->assertEquals(100.0, $preview['employee_distribution'][0]['percentage']);
        $token = $preview['token'];
        $this->assertNotEmpty($token);

        $confirmResponse = $this->post(route('v2.leads.import.confirm'), [
            'preview_token' => $token,
        ]);

        $confirmResponse->assertRedirect(route('v2.leads.import'));

        $lead = Lead::query()->where('phone', '01012345678')->first();
        $this->assertNotNull($lead);
        $this->assertSame('محمود عبد الله', $lead->name);
        $this->assertSame('محمود', $lead->first_name);
        $this->assertSame('عبد الله', $lead->last_name);
        $this->assertSame('mahmoud@example.com', $lead->email);
        $this->assertSame('شركة النيل للتجارة', $lead->company_name);
        $this->assertSame('تجارة وتوزيع', $lead->activity);
        $this->assertSame('القاهرة', $lead->governorate);
        $this->assertSame('شارع النصر مدينة نصر', $lead->address);
        $this->assertSame('كفالة أيتام', $lead->donation_type);
        $this->assertNotNull($lead->donation_type_id);
        $this->assertSame('monthly', $lead->donation_cycle);
        $this->assertEquals(2500.00, (float) $lead->donation_value);
        $this->assertSame('رعاية تعليمية', $lead->donation_purpose);
        $this->assertNotNull($lead->donation_purpose_id);
        $this->assertNotNull($lead->assigned_user_id);
        $this->assertNotNull($lead->branch_id);
        $this->assertSame('إعلان فيسبوك', $lead->source);
        $this->assertSame('تم الاتفاق على التبرع الشهري', $lead->response_details);

        // Check primary and additional phones
        $primaryPhone = LeadPhone::query()->where('lead_id', $lead->id)->where('is_primary', true)->first();
        $this->assertNotNull($primaryPhone);
        $this->assertSame('01012345678', $primaryPhone->phone);

        $extraPhone = LeadPhone::query()->where('lead_id', $lead->id)->where('is_primary', false)->first();
        $this->assertNotNull($extraPhone);
        $this->assertSame('01187654321', $extraPhone->phone);

        // Check campaign attachment
        $this->assertTrue($this->campaign->leads()->whereKey($lead->id)->exists());
    }

    public function test_import_preview_computes_multi_employee_distribution(): void
    {
        $agent1 = User::query()->create([
            'name' => 'Agent Alpha',
            'username' => 'agent_alpha',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);
        $agent2 = User::query()->create([
            'name' => 'Agent Beta',
            'username' => 'agent_beta',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);

        $csvHeaders = 'اسم العميل / المتبرع,رقم الهاتف الأساسي,الموظف المسؤول,الحالة';
        $csvRows = [
            'عميل أول,01011111111,Agent Alpha,متبرع',
            'عميل ثاني,01022222222,Agent Alpha,متبرع',
            'عميل ثالث,01033333333,Agent Beta,متبرع',
        ];

        $csvContent = $csvHeaders."\n".implode("\n", $csvRows)."\n";
        $file = UploadedFile::fake()->createWithContent('leads_multi_agent.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'round_robin',
            'distribution_user_ids' => [$agent1->id, $agent2->id],
        ]);

        $response->assertOk();
        $preview = $response->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(3, $preview['total_count']);
        $this->assertSame(3, $preview['valid_count']);

        // Check row assigned employees (round robin: Alpha, Beta, Alpha)
        $this->assertSame('Agent Alpha', $preview['rows'][0]['assigned_employee']);
        $this->assertSame('Agent Beta', $preview['rows'][1]['assigned_employee']);
        $this->assertSame('Agent Alpha', $preview['rows'][2]['assigned_employee']);

        // Check aggregated distribution
        $dist = $preview['employee_distribution'];
        $this->assertCount(2, $dist);
        $this->assertSame('Agent Alpha', $dist[0]['name']);
        $this->assertSame(2, $dist[0]['count']);
        $this->assertEquals(66.7, $dist[0]['percentage']);

        $this->assertSame('Agent Beta', $dist[1]['name']);
        $this->assertSame(1, $dist[1]['count']);
        $this->assertEquals(33.3, $dist[1]['percentage']);
    }

    public function test_import_preview_round_robin_distributes_leads_evenly(): void
    {
        $agentA = User::query()->create([
            'name' => 'Agent Alpha RR',
            'username' => 'agent_alpha_rr',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);
        $agentB = User::query()->create([
            'name' => 'Agent Beta RR',
            'username' => 'agent_beta_rr',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);
        $agentC = User::query()->create([
            'name' => 'Agent Gamma RR',
            'username' => 'agent_gamma_rr',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);

        $csvHeaders = 'اسم العميل / المتبرع,رقم الهاتف الأساسي';
        $csvRows = [
            'عميل 1,01044444441',
            'عميل 2,01044444442',
            'عميل 3,01044444443',
        ];

        $csvContent = $csvHeaders."\n".implode("\n", $csvRows)."\n";
        $file = UploadedFile::fake()->createWithContent('leads_rr_2col.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'round_robin',
            'distribution_user_ids' => [$agentA->id, $agentB->id, $agentC->id],
        ]);

        $response->assertOk();
        $preview = $response->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(3, $preview['valid_count']);

        // Distributed round-robin across A, B, C
        $this->assertSame('Agent Alpha RR', $preview['rows'][0]['assigned_employee']);
        $this->assertSame('Agent Beta RR', $preview['rows'][1]['assigned_employee']);
        $this->assertSame('Agent Gamma RR', $preview['rows'][2]['assigned_employee']);

        $dist = $preview['employee_distribution'];
        $this->assertCount(3, $dist);
        $this->assertSame(1, $dist[0]['count']);
        $this->assertSame(1, $dist[1]['count']);
        $this->assertSame(1, $dist[2]['count']);
    }

    public function test_import_preview_round_robin_override_all_and_confirm_persists(): void
    {
        $agentA = User::query()->create([
            'name' => 'Agent Alpha Over',
            'username' => 'agent_alpha_over',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);
        $agentB = User::query()->create([
            'name' => 'Agent Beta Over',
            'username' => 'agent_beta_over',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);

        $csvHeaders = 'اسم العميل / المتبرع,رقم الهاتف الأساسي,الموظف المسؤول,الحالة';
        $csvRows = [
            'عميل 1,01055555551,Some Old Employee,متبرع',
            'عميل 2,01055555552,Some Old Employee,متبرع',
            'عميل 3,01055555553,Some Old Employee,متبرع',
        ];

        $csvContent = $csvHeaders."\n".implode("\n", $csvRows)."\n";
        $file = UploadedFile::fake()->createWithContent('leads_rr_override.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'round_robin',
            'distribution_override' => 'override_all',
            'distribution_user_ids' => [$agentA->id, $agentB->id],
        ]);

        $previewResponse->assertOk();
        $preview = $previewResponse->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(3, $preview['valid_count']);

        // All 3 rows are overridden and distributed round-robin: A, B, A
        $this->assertSame('Agent Alpha Over', $preview['rows'][0]['assigned_employee']);
        $this->assertSame('Agent Beta Over', $preview['rows'][1]['assigned_employee']);
        $this->assertSame('Agent Alpha Over', $preview['rows'][2]['assigned_employee']);

        // Confirm import and verify in database
        $confirmResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.confirm'), [
            'preview_token' => $preview['token'],
        ]);

        $confirmResponse->assertRedirect(route('v2.leads.import'));

        $lead1 = Lead::query()->where('phone', '01055555551')->first();
        $lead2 = Lead::query()->where('phone', '01055555552')->first();
        $lead3 = Lead::query()->where('phone', '01055555553')->first();

        $this->assertNotNull($lead1);
        $this->assertNotNull($lead2);
        $this->assertNotNull($lead3);

        $this->assertSame($agentA->id, (int) $lead1->assigned_user_id);
        $this->assertSame($agentB->id, (int) $lead2->assigned_user_id);
        $this->assertSame($agentA->id, (int) $lead3->assigned_user_id);
    }

    public function test_import_preview_rejects_round_robin_without_selected_users(): void
    {
        $csvContent = "اسم العميل / المتبرع,رقم الهاتف الأساسي,الحالة\nعميل,01066666661,متبرع\n";
        $file = UploadedFile::fake()->createWithContent('leads_rr_empty.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'round_robin',
            'distribution_user_ids' => [],
        ]);

        $response->assertSessionHasErrors(['distribution_user_ids']);
    }

    public function test_import_with_minimal_name_and_phone_template_succeeds(): void
    {
        $csvContent = "اسم العميل / المتبرع,رقم الهاتف الأساسي\nخالد إبراهيم,01077777771\n";
        $file = UploadedFile::fake()->createWithContent('leads_minimal.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
        ]);

        $previewResponse->assertOk();
        $preview = $previewResponse->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(1, $preview['total_count']);
        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(0, $preview['error_count']);
        $this->assertSame('خالد إبراهيم', $preview['rows'][0]['name']);
        $this->assertSame('01077777771', $preview['rows'][0]['phone']);

        $confirmResponse = $this->actingAs($this->admin)->post(route('v2.leads.import.confirm'), [
            'preview_token' => $preview['token'],
        ]);

        $confirmResponse->assertRedirect(route('v2.leads.import'));

        $lead = Lead::query()->where('phone', '01077777771')->first();
        $this->assertNotNull($lead);
        $this->assertSame('خالد إبراهيم', $lead->name);
        $this->assertSame('خالد', $lead->first_name);
        $this->assertSame('إبراهيم', $lead->last_name);
        $this->assertSame((int) $this->admin->id, (int) $lead->assigned_user_id);

        // Primary phone record in lead_phones
        $this->assertTrue(LeadPhone::query()->where('lead_id', $lead->id)->where('phone', '01077777771')->where('is_primary', true)->exists());
    }

    public function test_import_preview_single_mode_assigns_all_leads_to_chosen_employee(): void
    {
        $agent = User::query()->create([
            'name' => 'Sole Assignee',
            'username' => 'sole_assignee',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);

        $csvContent = "اسم العميل / المتبرع,رقم الهاتف الأساسي\nعميل 1,01088888881\nعميل 2,01088888882\n";
        $file = UploadedFile::fake()->createWithContent('leads_single.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'single',
            'single_user_id' => $agent->id,
        ]);

        $response->assertOk();
        $preview = $response->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(2, $preview['valid_count']);
        $this->assertSame('Sole Assignee', $preview['rows'][0]['assigned_employee']);
        $this->assertSame('Sole Assignee', $preview['rows'][1]['assigned_employee']);

        $dist = $preview['employee_distribution'];
        $this->assertCount(1, $dist);
        $this->assertSame('Sole Assignee', $dist[0]['name']);
        $this->assertSame(2, $dist[0]['count']);
        $this->assertEquals(100.0, $dist[0]['percentage']);
    }

    public function test_import_preview_workload_mode_balances_least_loaded_first(): void
    {
        $busyAgent = User::query()->create([
            'name' => 'Busy Agent',
            'username' => 'busy_agent',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);
        $freeAgent = User::query()->create([
            'name' => 'Free Agent',
            'username' => 'free_agent',
            'password' => bcrypt('Pass123!'),
            'is_active' => true,
        ]);

        // Busy agent already has 2 leads in DB
        Lead::query()->create([
            'name' => 'Pre-existing 1',
            'phone' => '01099999991',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $busyAgent->id,
        ]);
        Lead::query()->create([
            'name' => 'Pre-existing 2',
            'phone' => '01099999992',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $busyAgent->id,
        ]);

        // Import 3 leads under workload balancing between Busy (2) and Free (0)
        // Lead 1 -> Free (now 1)
        // Lead 2 -> Free (now 2)
        // Lead 3 -> Busy (both tied at 2, assigns Busy or Free based on list order)
        $csvContent = "اسم العميل / المتبرع,رقم الهاتف الأساسي\nوارد 1,01099999993\nوارد 2,01099999994\nوارد 3,01099999995\n";
        $file = UploadedFile::fake()->createWithContent('leads_workload.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'workload',
            'distribution_user_ids' => [$busyAgent->id, $freeAgent->id],
        ]);

        $response->assertOk();
        $preview = $response->viewData('preview');

        $this->assertNotNull($preview);
        $this->assertSame(3, $preview['valid_count']);

        // Leads 1 and 2 MUST go to Free Agent to level the workload
        $this->assertSame('Free Agent', $preview['rows'][0]['assigned_employee']);
        $this->assertSame('Free Agent', $preview['rows'][1]['assigned_employee']);
        // Lead 3 goes to the first agent in the list once workloads are equal (2 each)
        $this->assertSame('Busy Agent', $preview['rows'][2]['assigned_employee']);
    }

    public function test_import_preview_rejects_single_mode_without_user_id(): void
    {
        $csvContent = "اسم العميل / المتبرع,رقم الهاتف الأساسي\nعميل,01088888889\n";
        $file = UploadedFile::fake()->createWithContent('leads_single_empty.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.import.preview'), [
            'import_file' => $file,
            'distribution_mode' => 'single',
            'single_user_id' => null,
        ]);

        $response->assertSessionHasErrors(['single_user_id']);
    }
}

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
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        $database = (string) DB::connection()
            ->getDatabaseName();
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'testing' => 'sokrat_crm_v3_testing',
            default => 'sokrat_crm_v3',
        };

        if ($expectedDatabase !== null && $database !== $expectedDatabase) {
            throw new RuntimeException(sprintf(
                'Refusing migration for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();

        Schema::create('lead_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label_ar', 150);
            $table->string('label_en', 150)->nullable();
            $table->string('type', 30)->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->string('system_column', 64)->nullable();
            $table->boolean('show_in_create')->default(true);
            $table->boolean('show_in_edit')->default(true);
            $table->boolean('show_in_filter')->default(false);
            $table->boolean('show_in_table')->default(false);
            $table->boolean('show_in_export')->default(false);
            $table->string('section', 30)->default('other');
            $table->unsignedInteger('position')->default(0);
            $table->string('help_text_ar', 255)->nullable();
            $table->string('help_text_en', 255)->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('leads', 'custom_fields')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->json('custom_fields')
                    ->nullable()
                    ->after('response_details');
            });
        }

        foreach ($this->systemFields() as $index => $field) {
            $exists = DB::table('lead_form_fields')
                ->where('key', $field['key'])
                ->exists();

            if (! $exists) {
                DB::table('lead_form_fields')->insert(array_merge(
                    $field,
                    ['position' => ($index + 1) * 10],
                ));
            }
        }

        \App\Support\LeadFieldSchema::flush();
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::dropIfExists('lead_form_fields');

        if (Schema::hasColumn('leads', 'custom_fields')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropColumn('custom_fields');
            });
        }

        \App\Support\LeadFieldSchema::flush();
    }

    /**
     * Mirrors every field that exists on the current hardcoded
     * create/edit screens plus the legacy lead columns that are
     * already accepted by store()/update() but not yet rendered
     * (email, company_name, activity, governorate, address).
     */
    private function systemFields(): array
    {
        $now = now();

        return [
            [
                'key' => 'name',
                'label_ar' => 'اسم العميل / المتبرع',
                'label_en' => 'Lead / Donor Name',
                'type' => 'text',
                'options' => null,
                'is_required' => true,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => true,
                'system_column' => 'name',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'phone',
                'label_ar' => 'رقم الهاتف الأساسي',
                'label_en' => 'Primary Phone Number',
                'type' => 'tel',
                'options' => null,
                'is_required' => true,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => true,
                'system_column' => 'phone',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'additional_phones',
                'label_ar' => 'أرقام هواتف إضافية (اختياري)',
                'label_en' => 'Additional Phone Numbers (Optional)',
                'type' => 'repeater',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => null,
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'email',
                'label_ar' => 'البريد الإلكتروني',
                'label_en' => 'Email Address',
                'type' => 'email',
                'options' => null,
                'is_required' => false,
                'is_active' => false,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'email',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'company_name',
                'label_ar' => 'اسم الشركة',
                'label_en' => 'Company Name',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_active' => false,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'company_name',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => true,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'activity',
                'label_ar' => 'نشاط الشركة',
                'label_en' => 'Business Activity',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_active' => false,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'activity',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'governorate',
                'label_ar' => 'المحافظة',
                'label_en' => 'Governorate',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_active' => false,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'governorate',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => true,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'address',
                'label_ar' => 'العنوان',
                'label_en' => 'Address',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_active' => false,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'address',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'basic_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'donation_type',
                'label_ar' => 'نوع التبرع',
                'label_en' => 'Donation Type',
                'type' => 'donation_types',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'donation_type',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'donation_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'donation_cycle',
                'label_ar' => 'دورة التبرع (التكرار)',
                'label_en' => 'Donation Cycle (Frequency)',
                'type' => 'donation_cycles',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'donation_cycle',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'donation_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'donation_value',
                'label_ar' => 'قيمة التبرع',
                'label_en' => 'Donation Value',
                'type' => 'number',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'donation_value',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'donation_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'donation_purpose',
                'label_ar' => 'غرض / وجهة التبرع',
                'label_en' => 'Donation Purpose',
                'type' => 'donation_purposes',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'donation_purpose',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'donation_info',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'pipeline_stage_id',
                'label_ar' => 'المرحلة الحالية',
                'label_en' => 'Current Stage',
                'type' => 'pipeline_stages',
                'options' => null,
                'is_required' => true,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => true,
                'system_column' => 'pipeline_stage_id',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'assigned_user_id',
                'label_ar' => 'الموظف المستجيب / المسؤول',
                'label_en' => 'Responding / Responsible Employee',
                'type' => 'assignee',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'assigned_user_id',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'branch_id',
                'label_ar' => 'الفرع التابع له العميل',
                'label_en' => 'Assigned Branch',
                'type' => 'branch',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'branch_id',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'source',
                'label_ar' => 'مصدر العميل',
                'label_en' => 'Lead Source',
                'type' => 'text',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'source',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'campaign_id',
                'label_ar' => 'الحملة المرتبطة',
                'label_en' => 'Associated Campaign',
                'type' => 'campaign',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => null,
                'show_in_create' => true,
                'show_in_edit' => false,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_date',
                'label_ar' => 'تاريخ التواصل',
                'label_en' => 'Contact Date',
                'type' => 'date',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'contact_date',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'next_follow_up_at',
                'label_ar' => 'موعد المتابعة القادمة',
                'label_en' => 'Next Follow-up Date',
                'type' => 'datetime',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'next_follow_up_at',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'response_details',
                'label_ar' => 'تفاصيل الرد والمكالمة (ملاحظات التواصل)',
                'label_en' => 'Call & Response Details (Notes)',
                'type' => 'textarea',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => 'response_details',
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'contact_followup',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'related_people',
                'label_ar' => 'الأشخاص المرتبطين بالعميل',
                'label_en' => 'Related Contacts',
                'type' => 'repeater',
                'options' => null,
                'is_required' => false,
                'is_active' => true,
                'is_system' => true,
                'is_locked' => false,
                'system_column' => null,
                'show_in_create' => true,
                'show_in_edit' => true,
                'show_in_filter' => false,
                'show_in_table' => false,
                'show_in_export' => false,
                'section' => 'other',
                'help_text_ar' => null,
                'help_text_en' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }
};

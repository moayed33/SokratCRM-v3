<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUPER_ADMIN_GROUP_CODE = 'super-admin';

    private function verifyDatabase(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'testing' => 'sokrat_crm_v3_testing',
            default => 'sokrat_crm_v3',
        };

        if ($database !== $expectedDatabase) {
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

        // ------------------------------------------------------------------
        // 1. Reusable picklists ("option sets") managed from Settings.
        // ------------------------------------------------------------------
        Schema::create('crm_options', function (Blueprint $table): void {
            $table->id();
            $table->string('set_key', 50)->index();
            $table->string('value', 100);
            $table->string('label_ar', 150);
            $table->string('label_en', 150)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['set_key', 'value']);
        });

        foreach ($this->seededOptionSets() as $index => $option) {
            DB::table('crm_options')->insertOrIgnore(array_merge(
                $option,
                ['position' => ($index % 10 + 1) * 10],
            ));
        }

        // ------------------------------------------------------------------
        // 2. Field schema: multi-entity support + conditional display rules.
        // ------------------------------------------------------------------
        Schema::table('lead_form_fields', function (Blueprint $table): void {
            $table->string('entity', 50)->default('leads')->after('id');
            $table->string('condition_field', 50)->nullable()->after('help_text_en');
            $table->string('condition_value', 150)->nullable()->after('condition_field');

            $table->index(['entity', 'is_active']);
        });

        // ------------------------------------------------------------------
        // 3. Module Builder: GUI-defined entities + generic record storage.
        // ------------------------------------------------------------------
        Schema::create('custom_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('icon', 50)->default('bi-grid');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_entity_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('title', 255)->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('entity_id')
                ->references('id')
                ->on('custom_entities')
                ->cascadeOnDelete();
        });

        // ------------------------------------------------------------------
        // 4. Permissions for built modules.
        // ------------------------------------------------------------------
        $now = now();
        $permissions = [
            [
                'code' => 'records.view',
                'module' => 'records',
                'name_ar' => 'عرض السجلات المخصصة',
                'description' => 'View records of GUI-built custom modules.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'records.manage',
                'module' => 'records',
                'name_ar' => 'إدارة السجلات المخصصة',
                'description' => 'Create, edit and delete records of GUI-built custom modules.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', ['records.view', 'records.manage'])
            ->pluck('id');
        $superAdminGroups = DB::table('groups')
            ->where('code', self::SUPER_ADMIN_GROUP_CODE)
            ->pluck('id');

        foreach ($superAdminGroups as $groupId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('group_permission')->insertOrIgnore([
                    'group_id' => $groupId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::dropIfExists('custom_entity_records');
        Schema::dropIfExists('custom_entities');

        Schema::table('lead_form_fields', function (Blueprint $table): void {
            $table->dropIndex(['entity', 'is_active']);
            $table->dropColumn(['entity', 'condition_field', 'condition_value']);
        });

        Schema::dropIfExists('crm_options');
    }

    /**
     * Seeds the two picklists currently hardcoded across the lead screens:
     * extra-phone labels and related-person relationship types.
     *
     * @return list<array{set_key:string,value:string,label_ar:string,label_en:string,is_active:bool}>
     */
    private function seededOptionSets(): array
    {
        return [
            ['set_key' => 'phone_label', 'value' => 'عمل', 'label_ar' => 'عمل', 'label_en' => 'Work', 'is_active' => true],
            ['set_key' => 'phone_label', 'value' => 'منزل', 'label_ar' => 'منزل', 'label_en' => 'Home', 'is_active' => true],
            ['set_key' => 'phone_label', 'value' => 'واتساب', 'label_ar' => 'واتساب', 'label_en' => 'WhatsApp', 'is_active' => true],
            ['set_key' => 'phone_label', 'value' => 'إضافي', 'label_ar' => 'إضافي', 'label_en' => 'Extra', 'is_active' => true],
            ['set_key' => 'phone_label', 'value' => 'أخرى', 'label_ar' => 'أخرى', 'label_en' => 'Other', 'is_active' => true],

            ['set_key' => 'relation_type', 'value' => 'عائلة / قريب', 'label_ar' => 'عائلة / قريب', 'label_en' => 'Family / Relative', 'is_active' => true],
            ['set_key' => 'relation_type', 'value' => 'صديق', 'label_ar' => 'صديق', 'label_en' => 'Friend', 'is_active' => true],
            ['set_key' => 'relation_type', 'value' => 'ممثل / مفوض', 'label_ar' => 'ممثل / مفوض', 'label_en' => 'Representative', 'is_active' => true],
            ['set_key' => 'relation_type', 'value' => 'زميل', 'label_ar' => 'زميل', 'label_en' => 'Colleague', 'is_active' => true],
            ['set_key' => 'relation_type', 'value' => 'أخرى', 'label_ar' => 'أخرى', 'label_en' => 'Other', 'is_active' => true],
        ];
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (! Schema::hasTable('governorates')) {
            Schema::create('governorates', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->index('is_active');
                $table->index('position');
            });

            $governorates = [
                ['code' => 'cairo', 'name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'position' => 1],
                ['code' => 'giza', 'name_ar' => 'الجيزة', 'name_en' => 'Giza', 'position' => 2],
                ['code' => 'alexandria', 'name_ar' => 'الإسكندرية', 'name_en' => 'Alexandria', 'position' => 3],
                ['code' => 'dakahlia', 'name_ar' => 'الدقهلية', 'name_en' => 'Dakahlia', 'position' => 4],
                ['code' => 'red_sea', 'name_ar' => 'البحر الأحمر', 'name_en' => 'Red Sea', 'position' => 5],
                ['code' => 'beheira', 'name_ar' => 'البحيرة', 'name_en' => 'Beheira', 'position' => 6],
                ['code' => 'fayoum', 'name_ar' => 'الفيوم', 'name_en' => 'Fayoum', 'position' => 7],
                ['code' => 'gharbia', 'name_ar' => 'الغربية', 'name_en' => 'Gharbia', 'position' => 8],
                ['code' => 'ismailia', 'name_ar' => 'الإسماعيلية', 'name_en' => 'Ismailia', 'position' => 9],
                ['code' => 'menofia', 'name_ar' => 'المنوفية', 'name_en' => 'Menofia', 'position' => 10],
                ['code' => 'minya', 'name_ar' => 'المنيا', 'name_en' => 'Minya', 'position' => 11],
                ['code' => 'qaliubiya', 'name_ar' => 'القليوبية', 'name_en' => 'Qaliubiya', 'position' => 12],
                ['code' => 'new_valley', 'name_ar' => 'الوادي الجديد', 'name_en' => 'New Valley', 'position' => 13],
                ['code' => 'suez', 'name_ar' => 'السويس', 'name_en' => 'Suez', 'position' => 14],
                ['code' => 'aswan', 'name_ar' => 'أسوان', 'name_en' => 'Aswan', 'position' => 15],
                ['code' => 'assiut', 'name_ar' => 'أسيوط', 'name_en' => 'Assiut', 'position' => 16],
                ['code' => 'beni_suef', 'name_ar' => 'بني سويف', 'name_en' => 'Beni Suef', 'position' => 17],
                ['code' => 'port_said', 'name_ar' => 'بورسعيد', 'name_en' => 'Port Said', 'position' => 18],
                ['code' => 'damietta', 'name_ar' => 'دمياط', 'name_en' => 'Damietta', 'position' => 19],
                ['code' => 'sharkia', 'name_ar' => 'الشرقية', 'name_en' => 'Sharkia', 'position' => 20],
                ['code' => 'south_sinai', 'name_ar' => 'جنوب سيناء', 'name_en' => 'South Sinai', 'position' => 21],
                ['code' => 'kafr_el_sheikh', 'name_ar' => 'كفر الشيخ', 'name_en' => 'Kafr El Sheikh', 'position' => 22],
                ['code' => 'matrouh', 'name_ar' => 'مطروح', 'name_en' => 'Matrouh', 'position' => 23],
                ['code' => 'luxor', 'name_ar' => 'الأقصر', 'name_en' => 'Luxor', 'position' => 24],
                ['code' => 'qena', 'name_ar' => 'قنا', 'name_en' => 'Qena', 'position' => 25],
                ['code' => 'north_sinai', 'name_ar' => 'شمال سيناء', 'name_en' => 'North Sinai', 'position' => 26],
                ['code' => 'sohag', 'name_ar' => 'سوهاج', 'name_en' => 'Sohag', 'position' => 27],
            ];

            foreach ($governorates as $gov) {
                DB::table('governorates')->insert([
                    'code' => $gov['code'],
                    'name_ar' => $gov['name_ar'],
                    'name_en' => $gov['name_en'],
                    'is_active' => true,
                    'position' => $gov['position'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('governorate_subregions')) {
            Schema::create('governorate_subregions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('governorate_id')->constrained('governorates')->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['governorate_id', 'code']);
                $table->index('is_active');
                $table->index('position');
            });

            // Seed common starter subregions for Cairo and Giza
            $cairoId = (int) DB::table('governorates')->where('code', 'cairo')->value('id');
            if ($cairoId > 0) {
                $cairoSubregions = [
                    ['code' => 'nasr_city', 'name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City', 'position' => 1],
                    ['code' => 'heliopolis', 'name_ar' => 'مصر الجديدة', 'name_en' => 'Heliopolis', 'position' => 2],
                    ['code' => 'new_cairo', 'name_ar' => 'التجمع والقاهرة الجديدة', 'name_en' => 'New Cairo', 'position' => 3],
                    ['code' => 'maadi', 'name_ar' => 'المعادي', 'name_en' => 'Maadi', 'position' => 4],
                    ['code' => 'shubra', 'name_ar' => 'شبرا', 'name_en' => 'Shubra', 'position' => 5],
                    ['code' => 'downtown', 'name_ar' => 'وسط البلد', 'name_en' => 'Downtown', 'position' => 6],
                ];
                foreach ($cairoSubregions as $sub) {
                    DB::table('governorate_subregions')->insert([
                        'governorate_id' => $cairoId,
                        'code' => $sub['code'],
                        'name_ar' => $sub['name_ar'],
                        'name_en' => $sub['name_en'],
                        'is_active' => true,
                        'position' => $sub['position'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $gizaId = (int) DB::table('governorates')->where('code', 'giza')->value('id');
            if ($gizaId > 0) {
                $gizaSubregions = [
                    ['code' => 'dokki', 'name_ar' => 'الدقي', 'name_en' => 'Dokki', 'position' => 1],
                    ['code' => 'mohandessin', 'name_ar' => 'المهندسين', 'name_en' => 'Mohandessin', 'position' => 2],
                    ['code' => 'agouza', 'name_ar' => 'العجوزة', 'name_en' => 'Agouza', 'position' => 3],
                    ['code' => 'october_city', 'name_ar' => 'مدينة 6 أكتوبر والشيخ زايد', 'name_en' => '6th of October & Zayed', 'position' => 4],
                    ['code' => 'haram', 'name_ar' => 'الهرم وفيصل', 'name_en' => 'Haram & Faisal', 'position' => 5],
                ];
                foreach ($gizaSubregions as $sub) {
                    DB::table('governorate_subregions')->insert([
                        'governorate_id' => $gizaId,
                        'code' => $sub['code'],
                        'name_ar' => $sub['name_ar'],
                        'name_en' => $sub['name_en'],
                        'is_active' => true,
                        'position' => $sub['position'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'collection_subregion_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('collection_subregion_id')->nullable()->after('collection_zone')->constrained('governorate_subregions')->nullOnDelete();
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table): void {
                if (! Schema::hasColumn('leads', 'governorate_id')) {
                    $table->foreignId('governorate_id')->nullable()->after('governorate')->constrained('governorates')->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'subregion_id')) {
                    $table->foreignId('subregion_id')->nullable()->after('governorate_id')->constrained('governorate_subregions')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('collection_cases')) {
            Schema::table('collection_cases', function (Blueprint $table): void {
                if (! Schema::hasColumn('collection_cases', 'governorate_id')) {
                    $table->foreignId('governorate_id')->nullable()->after('collection_address')->constrained('governorates')->nullOnDelete();
                }
                if (! Schema::hasColumn('collection_cases', 'subregion_id')) {
                    $table->foreignId('subregion_id')->nullable()->after('governorate_id')->constrained('governorate_subregions')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('collection_escalations')) {
            Schema::create('collection_escalations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('collection_case_id')->constrained('collection_cases')->cascadeOnDelete();
                $table->foreignId('collector_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('call_center_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('collection_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->text('collector_note')->nullable();
                $table->text('response_note')->nullable();
                $table->dateTime('new_due_at')->nullable();
                $table->dateTime('requested_at');
                $table->dateTime('responded_at')->nullable();
                $table->timestamps();

                $table->index(['collection_case_id', 'status']);
                $table->index(['call_center_user_id', 'status']);
                $table->index(['collector_user_id', 'status']);
            });
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('groups')) {
            $newPermissions = [
                'collections.geography.manage' => 'إدارة المحافظات والمناطق الجغرافية',
                'collections.escalations.respond' => 'الاستجابة لنداءات التحصيل العاجلة',
            ];

            foreach ($newPermissions as $code => $name) {
                DB::table('permissions')->updateOrInsert(
                    ['code' => $code],
                    ['module' => 'collections', 'name_ar' => $name, 'updated_at' => $now, 'created_at' => $now],
                );
            }

            // Assign geography management to super admin and manager groups
            $geoPermId = (int) DB::table('permissions')->where('code', 'collections.geography.manage')->value('id');
            $escalationPermId = (int) DB::table('permissions')->where('code', 'collections.escalations.respond')->value('id');

            $managerGroups = DB::table('groups')->whereIn('code', ['super-admin', 'manager', 'branch-admin', 'collection-manager'])->pluck('id');
            foreach ($managerGroups as $groupId) {
                if ($geoPermId > 0) {
                    DB::table('group_permission')->updateOrInsert([
                        'group_id' => $groupId,
                        'permission_id' => $geoPermId,
                    ]);
                }
                if ($escalationPermId > 0) {
                    DB::table('group_permission')->updateOrInsert([
                        'group_id' => $groupId,
                        'permission_id' => $escalationPermId,
                    ]);
                }
            }

            // Assign escalation respond to employee (call center)
            $employeeGroups = DB::table('groups')->whereIn('code', ['employee', 'sales-agent', 'call-center'])->pluck('id');
            foreach ($employeeGroups as $groupId) {
                if ($escalationPermId > 0) {
                    DB::table('group_permission')->updateOrInsert([
                        'group_id' => $groupId,
                        'permission_id' => $escalationPermId,
                    ]);
                }
            }

            // Add notification rules for collection escalations if notification_rules exists
            if (Schema::hasTable('notification_rules')) {
                $escalatedRuleId = DB::table('notification_rules')->insertGetId([
                    'name_ar' => 'طلب مساعدة عاجل: تعذر الوصول للمتبرع',
                    'name_en' => 'Urgent Call Center Handoff: Donor Unreachable',
                    'event_key' => 'collection.call_center_escalated',
                    'enabled' => true,
                    'trigger_offset_minutes' => 0,
                    'escalation_after_minutes' => null,
                    'priority' => 'urgent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('notification_rule_channels')->insert([
                    ['notification_rule_id' => $escalatedRuleId, 'channel' => 'database', 'created_at' => $now, 'updated_at' => $now],
                    ['notification_rule_id' => $escalatedRuleId, 'channel' => 'push', 'created_at' => $now, 'updated_at' => $now],
                ]);
                DB::table('notification_rule_recipients')->insert([
                    ['notification_rule_id' => $escalatedRuleId, 'recipient_type' => 'assigned_user', 'recipient_id' => 0, 'created_at' => $now, 'updated_at' => $now],
                ]);

                $resolvedRuleId = DB::table('notification_rules')->insertGetId([
                    'name_ar' => 'تم اتخاذ قرار بشأن نداء التحصيل',
                    'name_en' => 'Collection Handoff Decision Recorded',
                    'event_key' => 'collection.call_center_resolved',
                    'enabled' => true,
                    'trigger_offset_minutes' => 0,
                    'escalation_after_minutes' => null,
                    'priority' => 'urgent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('notification_rule_channels')->insert([
                    ['notification_rule_id' => $resolvedRuleId, 'channel' => 'database', 'created_at' => $now, 'updated_at' => $now],
                    ['notification_rule_id' => $resolvedRuleId, 'channel' => 'push', 'created_at' => $now, 'updated_at' => $now],
                ]);
                DB::table('notification_rule_recipients')->insert([
                    ['notification_rule_id' => $resolvedRuleId, 'recipient_type' => 'assigned_user', 'recipient_id' => 0, 'created_at' => $now, 'updated_at' => $now],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_escalations');

        if (Schema::hasTable('collection_cases')) {
            Schema::table('collection_cases', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('subregion_id');
                $table->dropConstrainedForeignId('governorate_id');
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('subregion_id');
                $table->dropConstrainedForeignId('governorate_id');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('collection_subregion_id');
            });
        }

        Schema::dropIfExists('governorate_subregions');
        Schema::dropIfExists('governorates');
    }
};

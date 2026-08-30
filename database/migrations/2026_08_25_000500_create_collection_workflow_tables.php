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

        if (! Schema::hasTable('instant_donation_methods')) {
            Schema::create('instant_donation_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->index('is_active');
            });

            $now = now();
            DB::table('instant_donation_methods')->insert([
                ['code' => 'instapay', 'name_ar' => 'إنستاباي', 'name_en' => 'Instapay', 'is_active' => true, 'position' => 10, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'vodafone_cash', 'name_ar' => 'فودافون كاش', 'name_en' => 'Vodafone Cash', 'is_active' => true, 'position' => 20, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'fawry', 'name_ar' => 'فوري', 'name_en' => 'Fawry', 'is_active' => true, 'position' => 30, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'bank_transfer', 'name_ar' => 'تحويل بنكي', 'name_en' => 'Bank Transfer', 'is_active' => true, 'position' => 40, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('collection_cases')) {
            Schema::create('collection_cases', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('source_followup_id')->nullable()->unique()->constrained('lead_followups')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('donation_type_id')->nullable()->constrained('donation_types')->nullOnDelete();
                $table->string('donation_type', 100);
                $table->decimal('expected_amount', 14, 2);
                $table->string('cycle', 50);
                $table->string('status', 30)->default('pending');
                $table->dateTime('due_at');
                $table->string('collection_address');
                $table->text('notes')->nullable();
                $table->foreignId('assigned_collector_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index('due_at');
                $table->index('completed_at');
                $table->index(['branch_id', 'status', 'due_at']);
                $table->index(['assigned_collector_user_id', 'status', 'due_at'], 'collection_collector_status_due_idx');
                $table->index(['lead_id', 'status']);
            });
        }

        if (! Schema::hasTable('collection_activities')) {
            Schema::create('collection_activities', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('collection_case_id');
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('action', 40);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('old_due_at')->nullable();
                $table->dateTime('new_due_at')->nullable();
                $table->unsignedBigInteger('old_collector_user_id')->nullable();
                $table->unsignedBigInteger('new_collector_user_id')->nullable();
                $table->timestamps();

                $table->foreign('collection_case_id')->references('id')->on('collection_cases')->cascadeOnDelete();
                $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('old_collector_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('new_collector_user_id')->references('id')->on('users')->nullOnDelete();

                $table->index(['collection_case_id', 'created_at']);
            });
        }

        if (Schema::hasTable('donations') && ! Schema::hasColumn('donations', 'donation_way')) {
            Schema::table('donations', function (Blueprint $table): void {
                $table->string('donation_way', 30)->default('legacy')->after('cycle')->index();
                $table->foreignId('instant_donation_method_id')->nullable()->after('donation_way')->constrained('instant_donation_methods')->nullOnDelete();
                $table->foreignId('collection_case_id')->nullable()->unique()->after('instant_donation_method_id')->constrained('collection_cases')->nullOnDelete();
            });
        }
        if (Schema::hasTable('permissions') && Schema::hasTable('groups')) {
            $permissions = [
                'collections.view' => 'عرض لوحة التحصيل',
                'collections.collect' => 'تنفيذ مهام التحصيل المسندة',
                'collections.assign' => 'إسناد وإعادة إسناد التحصيل',
                'collections.manage' => 'إدارة تحصيلات الفرع',
                'collections.complete' => 'تأكيد استلام التحصيل',
                'collections.cancel' => 'إلغاء طلبات التحصيل',
                'collections.reports' => 'عرض تقارير التحصيل',
                'collections.methods.manage' => 'إدارة وسائل التبرع الفوري',
            ];

            foreach ($permissions as $code => $name) {
                DB::table('permissions')->updateOrInsert(
                    ['code' => $code],
                    ['module' => 'collections', 'name_ar' => $name, 'updated_at' => $now, 'created_at' => $now],
                );
            }

            $groups = [
                'collection-manager' => [
                    'name' => 'مسؤولو التحصيل',
                    'description' => 'إدارة طابور التحصيل وإسناد الحالات ومتابعة تقارير الفرع.',
                    'permissions' => ['dashboard.view', 'collections.view', 'collections.assign', 'collections.manage', 'collections.cancel', 'collections.reports', 'collections.methods.manage'],
                ],
                'collector' => [
                    'name' => 'المحصلون',
                    'description' => 'تنفيذ حالات التحصيل المسندة وتأكيد استلام التبرعات.',
                    'permissions' => ['dashboard.view', 'collections.view', 'collections.collect', 'collections.complete'],
                ],
            ];

            foreach ($groups as $code => $definition) {
                DB::table('groups')->updateOrInsert(
                    ['code' => $code],
                    ['name' => $definition['name'], 'description' => $definition['description'], 'is_system' => false, 'updated_at' => $now, 'created_at' => $now],
                );

                $groupId = (int) DB::table('groups')->where('code', $code)->value('id');
                $permissionIds = DB::table('permissions')->whereIn('code', $definition['permissions'])->pluck('id');
                foreach ($permissionIds as $permissionId) {
                    DB::table('group_permission')->updateOrInsert([
                        'group_id' => $groupId,
                        'permission_id' => (int) $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('collection_case_id');
            $table->dropConstrainedForeignId('instant_donation_method_id');
            $table->dropColumn('donation_way');
        });

        Schema::dropIfExists('collection_activities');
        Schema::dropIfExists('collection_cases');
        Schema::dropIfExists('instant_donation_methods');

        if (Schema::hasTable('groups') && Schema::hasTable('permissions')) {
            $groupIds = DB::table('groups')->whereIn('code', ['collection-manager', 'collector'])->pluck('id');
            DB::table('group_user')->whereIn('group_id', $groupIds)->delete();
            DB::table('group_permission')->whereIn('group_id', $groupIds)->delete();
            DB::table('groups')->whereIn('id', $groupIds)->delete();

            $permissionIds = DB::table('permissions')->where('module', 'collections')->pluck('id');
            DB::table('group_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};

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
        if (! Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('lead_id')
                    ->nullable()
                    ->constrained('leads')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->string('title', 255);
                $table->text('description')->nullable();

                $table->dateTime('start_time');
                $table->dateTime('end_time');

                $table->enum('type', ['meeting', 'call', 'task', 'reminder'])->default('meeting');
                $table->enum('status', ['scheduled', 'completed', 'canceled'])->default('scheduled');

                $table->softDeletes();
                $table->timestamps();

                $table->index('start_time');
                $table->index('end_time');
                $table->index('type');
                $table->index('status');
                $table->index(['user_id', 'start_time']);
                $table->index(['lead_id', 'start_time']);
            });
        }

        // Insert default permissions for calendar module
        $now = now();
        $permissions = [
            [
                'code' => 'calendar.view',
                'name_ar' => 'عرض التقويم والأحداث',
                'module' => 'calendar',
                'description' => 'عرض التقويم والأحداث الخاصة بالمهام والمقابلات.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'calendar.manage',
                'name_ar' => 'إدارة التقويم والأحداث',
                'module' => 'calendar',
                'description' => 'إضافة وتعديل وحذف أحداث التقويم.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $perm['code']],
                $perm
            );
        }

        // Grant permissions to super-admin group if exists
        $superAdminGroup = DB::table('groups')->where('code', 'super-admin')->first();
        if ($superAdminGroup) {
            $permIds = DB::table('permissions')
                ->whereIn('code', ['calendar.view', 'calendar.manage'])
                ->pluck('id');

            foreach ($permIds as $permId) {
                DB::table('group_permission')->updateOrInsert([
                    'group_id' => $superAdminGroup->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');

        $permIds = DB::table('permissions')
            ->whereIn('code', ['calendar.view', 'calendar.manage'])
            ->pluck('id');

        DB::table('group_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('code', ['calendar.view', 'calendar.manage'])->delete();
    }
};

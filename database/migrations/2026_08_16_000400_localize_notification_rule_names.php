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
        if (! Schema::hasTable('notification_rules')) {
            return;
        }

        Schema::table('notification_rules', function (Blueprint $table): void {
            $table->string('name_ar', 150)->nullable()->after('name');
            $table->string('name_en', 150)->nullable()->after('name_ar');
        });
        DB::table('notification_rules')->update([
            'name_ar' => DB::raw('name'),
            'name_en' => DB::raw('name'),
        ]);

        $defaultNames = [
            'followup.due' => ['ar' => 'متابعة مستحقة قريبًا', 'en' => 'Follow-up due soon'],
            'followup.overdue' => ['ar' => 'متابعة متأخرة', 'en' => 'Follow-up overdue'],
            'followup.rescheduled' => ['ar' => 'إعادة جدولة متابعة', 'en' => 'Follow-up rescheduled'],
            'followup.reassigned' => ['ar' => 'إعادة تعيين عميل', 'en' => 'Lead reassigned'],
            'calendar.due' => ['ar' => 'موعد تقويم قريب', 'en' => 'Calendar event due'],
            'calendar.updated' => ['ar' => 'تحديث موعد تقويم', 'en' => 'Calendar event updated'],
            'calendar.canceled' => ['ar' => 'إلغاء موعد تقويم', 'en' => 'Calendar event canceled'],
            'system.test' => ['ar' => 'اختبار قناة التنبيهات', 'en' => 'Notification channel test'],
        ];

        foreach ($defaultNames as $eventKey => $names) {
            DB::table('notification_rules')
                ->where('event_key', $eventKey)
                ->whereNull('created_by_user_id')
                ->update([
                    'name_ar' => $names['ar'],
                    'name_en' => $names['en'],
                ]);
        }

        Schema::table('notification_rules', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_rules')) {
            return;
        }

        Schema::table('notification_rules', function (Blueprint $table): void {
            $table->string('name', 150)->nullable()->after('id');
        });

        DB::table('notification_rules')->update([
            'name' => DB::raw('COALESCE(name_en, name_ar)'),
        ]);

        Schema::table('notification_rules', function (Blueprint $table): void {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};

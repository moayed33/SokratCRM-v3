<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('calendar_events') && ! Schema::hasColumn('calendar_events', 'branch_id')) {
            Schema::table('calendar_events', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('lead_id')
                    ->constrained('branches')
                    ->nullOnDelete();

                $table->index(['branch_id', 'start_time']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('calendar_events') && Schema::hasColumn('calendar_events', 'branch_id')) {
            Schema::table('calendar_events', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};

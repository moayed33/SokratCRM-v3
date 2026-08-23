<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->string('sync_id', 255)->nullable()->after('status')->index();
            $table->string('provider', 50)->nullable()->after('sync_id')->index();
            $table->dateTime('synced_at')->nullable()->after('provider');
            $table->unsignedInteger('reminder_minutes_before')->nullable()->default(15)->after('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropColumn(['sync_id', 'provider', 'synced_at', 'reminder_minutes_before']);
        });
    }
};

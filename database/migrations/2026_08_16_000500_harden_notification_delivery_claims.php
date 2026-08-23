<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_occurrences', function (Blueprint $table): void {
            $table->index(['status', 'trigger_at'], 'notification_occurrence_due_index');
        });

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('claim_token')->nullable()->after('attempts')->index();
            $table->string('claim_type', 12)->nullable()->after('claim_token');
            $table->dateTime('processing_started_at')->nullable()->after('claim_type');
            $table->index(['status', 'scheduled_at'], 'notification_delivery_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropIndex('notification_delivery_due_index');
            $table->dropColumn(['claim_token', 'claim_type', 'processing_started_at']);
        });

        Schema::table('notification_occurrences', function (Blueprint $table): void {
            $table->dropIndex('notification_occurrence_due_index');
        });
    }
};

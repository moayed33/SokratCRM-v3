<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('notification_occurrences', 'notification_occurrence_due_index')) {
            Schema::table('notification_occurrences', function (Blueprint $table): void {
                $table->index(['status', 'trigger_at'], 'notification_occurrence_due_index');
            });
        }

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('notification_deliveries', 'claim_token')) {
                $table->uuid('claim_token')->nullable()->after('attempts')->index();
            }
            if (! Schema::hasColumn('notification_deliveries', 'claim_type')) {
                $table->string('claim_type', 12)->nullable()->after('claim_token');
            }
            if (! Schema::hasColumn('notification_deliveries', 'processing_started_at')) {
                $table->dateTime('processing_started_at')->nullable()->after('claim_type');
            }
            if (! Schema::hasIndex('notification_deliveries', 'notification_delivery_due_index')) {
                $table->index(['status', 'scheduled_at'], 'notification_delivery_due_index');
            }
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

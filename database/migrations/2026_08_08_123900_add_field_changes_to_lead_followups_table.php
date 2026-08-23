<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'lead_followups',
            function (Blueprint $table): void {
                $table->json(
                    'field_changes'
                )
                    ->nullable()
                    ->after('outcome');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'lead_followups',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'field_changes'
                );
            }
        );
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        if (! Schema::hasTable('lead_followups')) {
            Schema::create(
            'lead_followups',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('lead_id')
                    ->constrained('leads')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId('from_status_id')
                    ->nullable()
                    ->constrained('lead_statuses')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->foreignId('to_status_id')
                    ->constrained('lead_statuses')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->string(
                    'employee_name',
                    150
                );

                $table->string(
                    'communication_type',
                    30
                );

                $table->text('outcome');

                $table->dateTime(
                    'next_follow_up_at'
                )->nullable();

                $table->dateTime(
                    'followed_up_at'
                )->useCurrent();

                $table->timestamps();

                $table->index([
                    'lead_id',
                    'followed_up_at',
                ]);

                $table->index([
                    'employee_name',
                    'followed_up_at',
                ]);

                $table->index([
                    'communication_type',
                    'followed_up_at',
                ]);
            }
        );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'lead_followups'
        );
    }
};

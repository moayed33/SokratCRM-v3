<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_histories', function (Blueprint $table) {
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

            $table->string('changed_by', 150)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->useCurrent()->index();
            $table->timestamps();

            $table->index([
                'lead_id',
                'changed_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pipeline_stage_id')
                ->constrained('pipeline_stages')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('code', 50)->unique();
            $table->string('name_ar', 100);
            $table->unsignedTinyInteger('position')->unique();
            $table->string('color', 20)->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();

            $table->unique([
                'pipeline_stage_id',
                'name_ar',
            ]);

            $table->index([
                'pipeline_stage_id',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_statuses');
    }
};

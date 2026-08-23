<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_status_id')
                ->constrained('lead_statuses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->string('company_name', 150)->nullable();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email', 190)->nullable()->index();
            $table->string('source', 100)->nullable();
            $table->string('assigned_employee', 150)->nullable();
            $table->string('created_by', 150)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('next_follow_up_at')->nullable()->index();
            $table->timestamps();

            $table->index([
                'lead_status_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

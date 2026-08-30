<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->decimal('cost', 14, 2)->default(0);
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index('starts_at');
                $table->index('ends_at');
            });
        }

        if (! Schema::hasTable('campaign_user')) {
            Schema::create('campaign_user', function (Blueprint $table): void {
                $table->foreignId('campaign_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['campaign_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_user');
        Schema::dropIfExists('campaigns');
    }
};

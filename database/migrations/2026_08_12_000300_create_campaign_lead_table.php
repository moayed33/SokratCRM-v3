<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaign_lead')) {
            Schema::create('campaign_lead', function (Blueprint $table): void {
                $table->foreignId('campaign_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('lead_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['campaign_id', 'lead_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_lead');
    }
};

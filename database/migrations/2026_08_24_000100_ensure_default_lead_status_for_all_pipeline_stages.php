<?php

declare(strict_types=1);

use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pipeline_stages') || ! Schema::hasTable('lead_statuses')) {
            return;
        }

        PipelineStage::repairOrphanStages();
        PipelineStage::clearSidebarCache();
    }

    public function down(): void
    {
        // Data repair migration is permanent and safe
    }
};

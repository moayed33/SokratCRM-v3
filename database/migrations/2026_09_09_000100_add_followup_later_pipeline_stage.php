<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // 1. Shift existing stages and statuses at position >= 3 up by 1
            // Use reverse order to prevent collision with unique constraints if any
            $stagesToShift = PipelineStage::query()
                ->where('position', '>=', 3)
                ->where('code', '!=', 'followup_later')
                ->orderByDesc('position')
                ->get();

            foreach ($stagesToShift as $stg) {
                $stg->update(['position' => $stg->position + 1]);
            }

            $statusesToShift = LeadStatus::query()
                ->where('position', '>=', 3)
                ->where('code', '!=', 'followup_later')
                ->orderByDesc('position')
                ->get();

            foreach ($statusesToShift as $st) {
                $st->update(['position' => $st->position + 1]);
            }

            // Explicitly ensure canonical positions
            PipelineStage::query()->where('code', 'new')->update(['position' => 1]);
            PipelineStage::query()->where('code', 'no_answer')->update(['position' => 2]);
            PipelineStage::query()->where('code', 'not_interested')->update(['position' => 4]);
            PipelineStage::query()->where('code', 'donor')->update(['position' => 5]);

            LeadStatus::query()->where('code', 'new')->update(['position' => 1]);
            LeadStatus::query()->where('code', 'no_answer')->update(['position' => 2]);
            LeadStatus::query()->where('code', 'not_interested')->update(['position' => 4]);
            LeadStatus::query()->where('code', 'donor')->update(['position' => 5]);

            // 2. Create or update the followup_later PipelineStage at position 3
            $stage = PipelineStage::query()->updateOrCreate(
                ['code' => 'followup_later'],
                [
                    'name_ar' => 'متابعة لاحقة',
                    'name_en' => 'Follow-up Later',
                    'description_ar' => 'عميل تم التواصل معه وطلب تحديد موعد متابعة لاحقة',
                    'position' => 3,
                    'color' => '#7b61df',
                    'icon' => 'bi-clock-history',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            // 3. Create or update the 1:1 LeadStatus for followup_later at position 3
            LeadStatus::query()->updateOrCreate(
                ['code' => 'followup_later'],
                [
                    'pipeline_stage_id' => $stage->id,
                    'name_ar' => 'متابعة لاحقة',
                    'name_en' => 'Follow-up Later',
                    'color' => '#7b61df',
                    'position' => 3,
                    'is_terminal' => false,
                ]
            );

            // 4. Flush pipeline and sidebar caches
            PipelineStage::clearSidebarCache();
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $status = LeadStatus::query()->where('code', 'followup_later')->first();
            $stage = PipelineStage::query()->where('code', 'followup_later')->first();
            $fallbackStatus = LeadStatus::query()->where('code', 'no_answer')->first()
                ?? LeadStatus::query()->where('code', 'new')->first();

            if ($status && $fallbackStatus) {
                Lead::query()->where('lead_status_id', $status->id)->update([
                    'lead_status_id' => $fallbackStatus->id,
                ]);
                $status->delete();
            }

            $stage?->delete();

            // Re-shift stages and statuses back down
            $stagesToShift = PipelineStage::query()
                ->where('position', '>', 3)
                ->orderBy('position')
                ->get();

            foreach ($stagesToShift as $stg) {
                $stg->update(['position' => max(1, $stg->position - 1)]);
            }

            $statusesToShift = LeadStatus::query()
                ->where('position', '>', 3)
                ->orderBy('position')
                ->get();

            foreach ($statusesToShift as $st) {
                $st->update(['position' => max(1, $st->position - 1)]);
            }

            PipelineStage::clearSidebarCache();
        });
    }
};

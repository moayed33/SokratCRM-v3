<?php

declare(strict_types=1);

use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pipeline_stages') || ! Schema::hasTable('lead_statuses')) {
            return;
        }

        DB::transaction(function (): void {
            // 1. Ensure the 4 canonical Pipeline Stages exist with safe temporary positions
            $stageNew = PipelineStage::query()->updateOrCreate(
                ['code' => 'new'],
                [
                    'name_ar' => 'جديد',
                    'description_ar' => 'عميل مسجل جديد أو قيد التواصل والتنسيق الأولي',
                    'position' => 101,
                    'color' => '#3478f6',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            $stageNoAnswer = PipelineStage::query()->updateOrCreate(
                ['code' => 'no_answer'],
                [
                    'name_ar' => 'لم يتم الرد',
                    'description_ar' => 'عميل لم يقم بالرد على محاولات التواصل المجدولة',
                    'position' => 102,
                    'color' => '#e59b16',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            $stageNotInterested = PipelineStage::query()->updateOrCreate(
                ['code' => 'not_interested'],
                [
                    'name_ar' => 'غير مهتم',
                    'description_ar' => 'عميل غير مهتم بالخدمة أو التبرع حالياً',
                    'position' => 103,
                    'color' => '#dc2637',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            $stageDonor = PipelineStage::query()->updateOrCreate(
                ['code' => 'donor'],
                [
                    'name_ar' => 'متبرع',
                    'description_ar' => 'عميل متبرع مؤكد قام بتسليم أو تأكيد التبرع ويخضع للمتابعة الدورية',
                    'position' => 104,
                    'color' => '#16a34a',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            // 2. Set clean final positions 1, 2, 3, 4 for the canonical stages
            $stageNew->update(['position' => 1]);
            $stageNoAnswer->update(['position' => 2]);
            $stageNotInterested->update(['position' => 3]);
            $stageDonor->update(['position' => 4]);

            // Re-sequence any custom non-primary stages after the 4 canonical stages
            $customStages = PipelineStage::query()
                ->where('is_primary', false)
                ->whereNotIn('id', [$stageNew->id, $stageNoAnswer->id, $stageNotInterested->id, $stageDonor->id])
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $pos = 5;
            foreach ($customStages as $cStage) {
                $cStage->update(['position' => $pos++]);
            }

            // 3. Reassign the 4 canonical LeadStatus records 1:1 to their corresponding PipelineStage
            $statusNew = LeadStatus::query()->firstOrCreate(
                ['code' => 'new'],
                [
                    'pipeline_stage_id' => $stageNew->id,
                    'name_ar' => 'جديد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'is_terminal' => false,
                ]
            );
            $statusNew->update([
                'pipeline_stage_id' => $stageNew->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
                'is_terminal' => false,
            ]);

            $statusNoAnswer = LeadStatus::query()->firstOrCreate(
                ['code' => 'no_answer'],
                [
                    'pipeline_stage_id' => $stageNoAnswer->id,
                    'name_ar' => 'لم يتم الرد',
                    'position' => 2,
                    'color' => '#e59b16',
                    'is_terminal' => false,
                ]
            );
            $statusNoAnswer->update([
                'pipeline_stage_id' => $stageNoAnswer->id,
                'name_ar' => 'لم يتم الرد',
                'position' => 2,
                'color' => '#e59b16',
                'is_terminal' => false,
            ]);

            $statusNotInterested = LeadStatus::query()->firstOrCreate(
                ['code' => 'not_interested'],
                [
                    'pipeline_stage_id' => $stageNotInterested->id,
                    'name_ar' => 'غير مهتم',
                    'position' => 3,
                    'color' => '#dc2637',
                    'is_terminal' => true,
                ]
            );
            $statusNotInterested->update([
                'pipeline_stage_id' => $stageNotInterested->id,
                'name_ar' => 'غير مهتم',
                'position' => 3,
                'color' => '#dc2637',
                'is_terminal' => true,
            ]);

            $statusDonor = LeadStatus::query()->firstOrCreate(
                ['code' => 'donor'],
                [
                    'pipeline_stage_id' => $stageDonor->id,
                    'name_ar' => 'متبرع',
                    'position' => 4,
                    'color' => '#16a34a',
                    'is_terminal' => false,
                ]
            );
            $statusDonor->update([
                'pipeline_stage_id' => $stageDonor->id,
                'name_ar' => 'متبرع',
                'position' => 4,
                'color' => '#16a34a',
                'is_terminal' => false,
            ]);

            // Clear sidebar and pipeline caches
            PipelineStage::clearSidebarCache();
        });
    }

    public function down(): void
    {
        // Permanent flattening migration
    }
};

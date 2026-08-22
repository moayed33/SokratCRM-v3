<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! PipelineStage::query()->exists() && ! LeadStatus::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            // 1. Ensure the 2 canonical pipeline stages exist
            $stageNew = PipelineStage::query()->firstOrCreate(
                ['code' => 'new'],
                [
                    'name_ar' => 'جديد',
                    'description_ar' => 'عميل مسجل جديد أو قيد التواصل والتنسيق الأولي ولم يصبح متبرعاً مؤكداً بعد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            $stageDonor = PipelineStage::query()->firstOrCreate(
                ['code' => 'donor'],
                [
                    'name_ar' => 'متبرع',
                    'description_ar' => 'عميل متبرع مؤكد قام بتسليم أو تأكيد التبرع ويخضع للمتابعة الدورية',
                    'position' => 2,
                    'color' => '#16a34a',
                    'is_primary' => true,
                    'is_active' => true,
                ]
            );

            // 2. Ensure the 4 canonical lead statuses exist with safe temporary positions
            $statusNew = LeadStatus::query()->firstOrCreate(
                ['code' => 'new'],
                [
                    'pipeline_stage_id' => $stageNew->id,
                    'name_ar' => 'جديد',
                    'position' => 101,
                    'color' => '#3478f6',
                    'is_terminal' => false,
                ]
            );

            $statusNoAnswer = LeadStatus::query()->firstOrCreate(
                ['code' => 'no_answer'],
                [
                    'pipeline_stage_id' => $stageNew->id,
                    'name_ar' => 'لم يتم الرد',
                    'position' => 102,
                    'color' => '#e59b16',
                    'is_terminal' => false,
                ]
            );

            $statusNotInterested = LeadStatus::query()->firstOrCreate(
                ['code' => 'not_interested'],
                [
                    'pipeline_stage_id' => $stageNew->id,
                    'name_ar' => 'غير مهتم',
                    'position' => 103,
                    'color' => '#dc2637',
                    'is_terminal' => true,
                ]
            );

            $statusDonor = LeadStatus::query()->firstOrCreate(
                ['code' => 'donor'],
                [
                    'pipeline_stage_id' => $stageDonor->id,
                    'name_ar' => 'متبرع',
                    'position' => 104,
                    'color' => '#16a34a',
                    'is_terminal' => false,
                ]
            );

            // Canonical mapping table
            $targetStatusMap = [
                'new' => $statusNew->id,
                'interested' => $statusNew->id,
                'no-answer' => $statusNoAnswer->id,
                'no_answer' => $statusNoAnswer->id,
                'donor_no_answer' => $statusNoAnswer->id,
                'not-interested' => $statusNotInterested->id,
                'not_interested' => $statusNotInterested->id,
                'donor' => $statusDonor->id,
                'donation_confirmed' => $statusDonor->id,
                'donation_collected' => $statusDonor->id,
                'active_donor' => $statusDonor->id,
                'completed' => $statusDonor->id,
                'meeting' => $statusNew->id,
                'quotation' => $statusNew->id,
                'discussion' => $statusNew->id,
                'contract_closed' => $statusDonor->id,
                'contract-closing' => $statusDonor->id,
                'execution' => $statusDonor->id,
            ];

            // 3. Remap all existing leads referencing obsolete statuses
            $allStatuses = LeadStatus::query()->get();
            $canonicalIds = [$statusNew->id, $statusNoAnswer->id, $statusNotInterested->id, $statusDonor->id];

            foreach ($allStatuses as $st) {
                if (in_array($st->id, $canonicalIds, true)) {
                    continue;
                }

                $targetId = $targetStatusMap[$st->code] ?? $statusNew->id;

                Lead::query()->where('lead_status_id', $st->id)->update(['lead_status_id' => $targetId]);
                LeadFollowup::query()->where('from_status_id', $st->id)->update(['from_status_id' => $targetId]);
                LeadFollowup::query()->where('to_status_id', $st->id)->update(['to_status_id' => $targetId]);
                LeadStatusHistory::query()->where('from_status_id', $st->id)->update(['from_status_id' => $targetId]);
                LeadStatusHistory::query()->where('to_status_id', $st->id)->update(['to_status_id' => $targetId]);

                // Delete obsolete status
                $st->delete();
            }

            // 4. Now assign clean final positions 1, 2, 3, 4
            $statusNew->update([
                'pipeline_stage_id' => $stageNew->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
                'is_terminal' => false,
            ]);

            $statusNoAnswer->update([
                'pipeline_stage_id' => $stageNew->id,
                'name_ar' => 'لم يتم الرد',
                'position' => 2,
                'color' => '#e59b16',
                'is_terminal' => false,
            ]);

            $statusNotInterested->update([
                'pipeline_stage_id' => $stageNew->id,
                'name_ar' => 'غير مهتم',
                'position' => 3,
                'color' => '#dc2637',
                'is_terminal' => true,
            ]);

            $statusDonor->update([
                'pipeline_stage_id' => $stageDonor->id,
                'name_ar' => 'متبرع',
                'position' => 4,
                'color' => '#16a34a',
                'is_terminal' => false,
            ]);

            // 5. Clean any leads pointing to non-existent status IDs
            Lead::query()->whereNotIn('lead_status_id', $canonicalIds)->orWhereNull('lead_status_id')->update(['lead_status_id' => $statusNew->id]);

            // 6. Clean any obsolete stages other than new and donor
            $canonicalStageIds = [$stageNew->id, $stageDonor->id];
            PipelineStage::query()->whereNotIn('id', $canonicalStageIds)->delete();

            // 7. Clear sidebar and pipeline caches
            PipelineStage::clearSidebarCache();
        });
    }

    public function down(): void
    {
        // Consolidation migration is permanent
    }
};

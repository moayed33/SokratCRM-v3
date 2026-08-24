<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmV2PipelineSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // 1. Four Default Primary Pipeline Stages
            $stages = [
                [
                    'code' => 'new',
                    'name_ar' => 'جديد',
                    'description_ar' => 'عميل مسجل جديد أو قيد التواصل والتنسيق الأولي',
                    'position' => 1,
                    'color' => '#3478f6',
                    'is_primary' => true,
                ],
                [
                    'code' => 'no_answer',
                    'name_ar' => 'لم يتم الرد',
                    'description_ar' => 'عميل لم يقم بالرد على محاولات التواصل المجدولة',
                    'position' => 2,
                    'color' => '#e59b16',
                    'is_primary' => true,
                ],
                [
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'description_ar' => 'عميل غير مهتم بالخدمة أو التبرع حالياً',
                    'position' => 3,
                    'color' => '#dc2637',
                    'is_primary' => true,
                ],
                [
                    'code' => 'donor',
                    'name_ar' => 'متبرع',
                    'description_ar' => 'عميل متبرع مؤكد قام بتسليم أو تأكيد التبرع ويخضع للمتابعة الدورية',
                    'position' => 4,
                    'color' => '#16a34a',
                    'is_primary' => true,
                ],
            ];

            $stageModels = [];

            foreach ($stages as $stage) {
                $stageModels[$stage['code']] = PipelineStage::query()->updateOrCreate(
                    ['code' => $stage['code']],
                    [
                        'name_ar' => $stage['name_ar'],
                        'description_ar' => $stage['description_ar'],
                        'position' => $stage['position'],
                        'color' => $stage['color'],
                        'is_primary' => $stage['is_primary'],
                        'is_active' => true,
                    ]
                );
            }

            // 2. Exactly Four Canonical Lead Statuses (1:1 with Stages)
            $statuses = [
                [
                    'stage' => 'new',
                    'code' => 'new',
                    'name_ar' => 'جديد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'terminal' => false,
                ],
                [
                    'stage' => 'no_answer',
                    'code' => 'no_answer',
                    'name_ar' => 'لم يتم الرد',
                    'position' => 2,
                    'color' => '#e59b16',
                    'terminal' => false,
                ],
                [
                    'stage' => 'not_interested',
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'position' => 3,
                    'color' => '#dc2637',
                    'terminal' => true,
                ],
                [
                    'stage' => 'donor',
                    'code' => 'donor',
                    'name_ar' => 'متبرع',
                    'position' => 4,
                    'color' => '#16a34a',
                    'terminal' => false,
                ],
            ];

            $statusModels = [];
            foreach ($statuses as $status) {
                $statusModels[$status['code']] = LeadStatus::query()->updateOrCreate(
                    ['code' => $status['code']],
                    [
                        'pipeline_stage_id' => $stageModels[$status['stage']]->id,
                        'name_ar' => $status['name_ar'],
                        'position' => $status['position'],
                        'color' => $status['color'],
                        'is_terminal' => $status['terminal'],
                    ]
                );
            }

            // Remap any existing leads referencing obsolete statuses
            $targetStatusMap = [
                'new' => $statusModels['new']->id,
                'interested' => $statusModels['new']->id,
                'no-answer' => $statusModels['no_answer']->id,
                'no_answer' => $statusModels['no_answer']->id,
                'donor_no_answer' => $statusModels['no_answer']->id,
                'not-interested' => $statusModels['not_interested']->id,
                'not_interested' => $statusModels['not_interested']->id,
                'donor' => $statusModels['donor']->id,
                'donation_confirmed' => $statusModels['donor']->id,
                'donation_collected' => $statusModels['donor']->id,
                'active_donor' => $statusModels['donor']->id,
                'completed' => $statusModels['donor']->id,
                'meeting' => $statusModels['new']->id,
                'quotation' => $statusModels['new']->id,
                'discussion' => $statusModels['new']->id,
                'contract_closed' => $statusModels['donor']->id,
                'contract-closing' => $statusModels['donor']->id,
                'execution' => $statusModels['donor']->id,
            ];

            $allStatuses = LeadStatus::query()->get();
            $canonicalIds = array_map(static fn ($m) => $m->id, $statusModels);

            foreach ($allStatuses as $st) {
                if (in_array($st->id, $canonicalIds, true)) {
                    continue;
                }

                $targetId = $targetStatusMap[$st->code] ?? $statusModels['new']->id;

                Lead::query()->where('lead_status_id', $st->id)->update(['lead_status_id' => $targetId]);
                LeadFollowup::query()->where('from_status_id', $st->id)->update(['from_status_id' => $targetId]);
                LeadFollowup::query()->where('to_status_id', $st->id)->update(['to_status_id' => $targetId]);
                LeadStatusHistory::query()->where('from_status_id', $st->id)->update(['from_status_id' => $targetId]);
                LeadStatusHistory::query()->where('to_status_id', $st->id)->update(['to_status_id' => $targetId]);

                $st->delete();
            }

            // 3. Donation Types Lookup Table
            $donationTypes = [
                ['name_ar' => 'صدقة', 'position' => 1],
                ['name_ar' => 'زكاة مال', 'position' => 2],
                ['name_ar' => 'كفالة أيتام', 'position' => 3],
                ['name_ar' => 'سقيا ماء / آبار', 'position' => 4],
                ['name_ar' => 'إطعام مساكين / كراتين', 'position' => 5],
                ['name_ar' => 'وقف خيري', 'position' => 6],
            ];

            foreach ($donationTypes as $type) {
                DonationType::query()->updateOrCreate(
                    ['name_ar' => $type['name_ar']],
                    [
                        'position' => $type['position'],
                        'is_active' => true,
                    ]
                );
            }

            // 4. Donation Purposes Lookup Table
            $donationPurposes = [
                ['name_ar' => 'عام / حيث تدعو الحاجة', 'position' => 1],
                ['name_ar' => 'كفالات شهرية للأسر المتعففة', 'position' => 2],
                ['name_ar' => 'بناء وترميم المساجد', 'position' => 3],
                ['name_ar' => 'حفر وتجهيز آبار المياه', 'position' => 4],
                ['name_ar' => 'علاج ومرضى عمليات جراحية', 'position' => 5],
                ['name_ar' => 'تجهيز فتيات يتيمات للزواج', 'position' => 6],
                ['name_ar' => 'حملة رمضان الموسمية', 'position' => 7],
            ];

            foreach ($donationPurposes as $purpose) {
                DonationPurpose::query()->updateOrCreate(
                    ['name_ar' => $purpose['name_ar']],
                    [
                        'position' => $purpose['position'],
                        'is_active' => true,
                    ]
                );
            }

            PipelineStage::clearSidebarCache();
        });
    }
}

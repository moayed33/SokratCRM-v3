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
use App\Models\PipelineStageField;
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
                    'code' => 'followup_later',
                    'name_ar' => 'متابعة لاحقة',
                    'description_ar' => 'عميل تم التواصل معه وطلب تحديد موعد متابعة لاحقة',
                    'position' => 3,
                    'color' => '#7b61df',
                    'is_primary' => true,
                ],
                [
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'description_ar' => 'عميل غير مهتم بالخدمة أو التبرع حالياً',
                    'position' => 4,
                    'color' => '#dc2637',
                    'is_primary' => true,
                ],
                [
                    'code' => 'donor',
                    'name_ar' => 'متبرع',
                    'description_ar' => 'عميل متبرع مؤكد قام بتسليم أو تأكيد التبرع ويخضع للمتابعة الدورية',
                    'position' => 5,
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

            // 2. Exactly Five Canonical Lead Statuses (1:1 with Stages)
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
                    'stage' => 'followup_later',
                    'code' => 'followup_later',
                    'name_ar' => 'متابعة لاحقة',
                    'position' => 3,
                    'color' => '#7b61df',
                    'terminal' => false,
                ],
                [
                    'stage' => 'not_interested',
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'position' => 4,
                    'color' => '#dc2637',
                    'terminal' => true,
                ],
                [
                    'stage' => 'donor',
                    'code' => 'donor',
                    'name_ar' => 'متبرع',
                    'position' => 5,
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

            // 5. Canonical Pipeline Stage Fields
            if (isset($stageModels['no_answer'])) {
                PipelineStageField::query()->firstOrCreate(
                    [
                        'pipeline_stage_id' => $stageModels['no_answer']->id,
                        'key' => 'callback_at',
                    ],
                    [
                        'label_ar' => 'موعد إعادة الاتصال',
                        'label_en' => 'Next Callback Date',
                        'type' => 'datetime',
                        'placeholder_ar' => 'حدد تاريخ ووقت إعادة الاتصال',
                        'placeholder_en' => 'Select callback date and time',
                        'help_text_ar' => 'موعد الاتصال القادم بالعميل لمتابعة الرد',
                        'help_text_en' => 'Next scheduled callback date for this lead',
                        'is_required' => true,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 1,
                    ]
                );

                PipelineStageField::query()->firstOrCreate(
                    [
                        'pipeline_stage_id' => $stageModels['no_answer']->id,
                        'key' => 'notes',
                    ],
                    [
                        'label_ar' => 'ملاحظات المحاولة',
                        'label_en' => 'Attempt Notes',
                        'type' => 'textarea',
                        'placeholder_ar' => 'أدخل أي ملاحظات حول محاولة الاتصال',
                        'placeholder_en' => 'Enter any notes about this attempt',
                        'help_text_ar' => 'تفاصيل إضافية حول سبب عدم الرد',
                        'help_text_en' => 'Additional details about no-answer attempt',
                        'is_required' => false,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 2,
                    ]
                );
            }

            if (isset($stageModels['not_interested'])) {
                $reasonOptions = [
                    ['value' => 'high_price', 'label_ar' => 'السعر مرتفع', 'label_en' => 'High Price'],
                    ['value' => 'not_convinced', 'label_ar' => 'غير مقتنع بالفكرة', 'label_en' => 'Not Convinced'],
                    ['value' => 'no_budget', 'label_ar' => 'لا توجد ميزانية حالياً', 'label_en' => 'No Budget Currently'],
                    ['value' => 'competitor', 'label_ar' => 'يتعامل مع جهة أخرى', 'label_en' => 'Using Another Provider'],
                    ['value' => 'bad_timing', 'label_ar' => 'التوقيت غير مناسب', 'label_en' => 'Bad Timing'],
                    ['value' => 'other', 'label_ar' => 'سبب آخر', 'label_en' => 'Other Reason'],
                ];

                PipelineStageField::query()->firstOrCreate(
                    [
                        'pipeline_stage_id' => $stageModels['not_interested']->id,
                        'key' => 'reason',
                    ],
                    [
                        'label_ar' => 'سبب عدم الاهتمام',
                        'label_en' => 'Disinterest Reason',
                        'type' => 'select',
                        'placeholder_ar' => 'اختر سبب عدم الاهتمام',
                        'placeholder_en' => 'Select disinterest reason',
                        'help_text_ar' => 'السبب الرئيسي لعدم اهتمام العميل',
                        'help_text_en' => 'Primary reason for disinterest',
                        'is_required' => true,
                        'options' => $reasonOptions,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 1,
                    ]
                );

                PipelineStageField::query()->firstOrCreate(
                    [
                        'pipeline_stage_id' => $stageModels['not_interested']->id,
                        'key' => 'notes',
                    ],
                    [
                        'label_ar' => 'ملاحظات وتفاصيل',
                        'label_en' => 'Notes & Details',
                        'type' => 'textarea',
                        'placeholder_ar' => 'أدخل تفاصيل إضافية حول سبب الرفض',
                        'placeholder_en' => 'Enter additional details about the refusal',
                        'help_text_ar' => 'أي تعليقات أو شروط من العميل',
                        'help_text_en' => 'Any customer feedback or conditions',
                        'is_required' => false,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 2,
                    ]
                );
            }

            PipelineStageField::flushCache($stageModels['no_answer']->id ?? 0);
            PipelineStageField::flushCache($stageModels['not_interested']->id ?? 0);

            PipelineStage::clearSidebarCache();
        });
    }
}

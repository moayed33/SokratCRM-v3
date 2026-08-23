<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmV2PipelineSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $stages = [
                [
                    'code' => 'start',
                    'name_ar' => 'البداية',
                    'description_ar' => 'استقبال العميل ومحاولات التواصل',
                    'position' => 1,
                    'color' => '#3478f6',
                ],
                [
                    'code' => 'interest',
                    'name_ar' => 'الاهتمام',
                    'description_ar' => 'تحديد مدى اهتمام العميل',
                    'position' => 2,
                    'color' => '#169a64',
                ],
                [
                    'code' => 'negotiation',
                    'name_ar' => 'التفاوض',
                    'description_ar' => 'العرض والمناقشة والوصول لاتفاق',
                    'position' => 3,
                    'color' => '#e59b16',
                ],
                [
                    'code' => 'closing_execution',
                    'name_ar' => 'الإغلاق والتنفيذ',
                    'description_ar' => 'إتمام الاتفاق وبدء تنفيذ المشروع',
                    'position' => 4,
                    'color' => '#7b61df',
                ],
            ];

            $stageModels = [];

            foreach ($stages as $stage) {
                $stageModels[$stage['code']] =
                    PipelineStage::query()->updateOrCreate(
                        ['code' => $stage['code']],
                        [
                            'name_ar' => $stage['name_ar'],
                            'description_ar' => $stage['description_ar'],
                            'position' => $stage['position'],
                            'color' => $stage['color'],
                            'is_active' => true,
                        ]
                    );
            }

            $statuses = [
                [
                    'stage' => 'start',
                    'code' => 'new',
                    'name_ar' => 'جديد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'terminal' => false,
                ],
                [
                    'stage' => 'start',
                    'code' => 'no_answer',
                    'name_ar' => 'لم يرد',
                    'position' => 2,
                    'color' => '#e59b16',
                    'terminal' => false,
                ],
                [
                    'stage' => 'interest',
                    'code' => 'interested',
                    'name_ar' => 'مهتم',
                    'position' => 3,
                    'color' => '#169a64',
                    'terminal' => false,
                ],
                [
                    'stage' => 'interest',
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'position' => 4,
                    'color' => '#dc2637',
                    'terminal' => true,
                ],
                [
                    'stage' => 'negotiation',
                    'code' => 'meeting',
                    'name_ar' => 'مقابلة',
                    'position' => 5,
                    'color' => '#7b61df',
                    'terminal' => false,
                ],
                [
                    'stage' => 'negotiation',
                    'code' => 'quotation',
                    'name_ar' => 'عرض سعر',
                    'position' => 6,
                    'color' => '#e59b16',
                    'terminal' => false,
                ],
                [
                    'stage' => 'negotiation',
                    'code' => 'discussion',
                    'name_ar' => 'مناقشة',
                    'position' => 7,
                    'color' => '#5865f2',
                    'terminal' => false,
                ],
                [
                    'stage' => 'closing_execution',
                    'code' => 'contract_closed',
                    'name_ar' => 'تقفيل عقد',
                    'position' => 8,
                    'color' => '#169a64',
                    'terminal' => false,
                ],
                [
                    'stage' => 'closing_execution',
                    'code' => 'execution',
                    'name_ar' => 'تنفيذ',
                    'position' => 9,
                    'color' => '#7b61df',
                    'terminal' => true,
                ],
            ];

            foreach ($statuses as $status) {
                LeadStatus::query()->updateOrCreate(
                    ['code' => $status['code']],
                    [
                        'pipeline_stage_id' =>
                            $stageModels[$status['stage']]->id,
                        'name_ar' => $status['name_ar'],
                        'position' => $status['position'],
                        'color' => $status['color'],
                        'is_terminal' => $status['terminal'],
                    ]
                );
            }
        });
    }
}

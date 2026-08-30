<?php

declare(strict_types=1);

use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'testing' => 'sokrat_crm_v3_testing',
            default => 'sokrat_crm_v3',
        };

        if ($database !== $expectedDatabase) {
            throw new RuntimeException(sprintf(
                'Refusing migration for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();

        // 1. Pipeline Stage Fields table
        Schema::create('pipeline_stage_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pipeline_stage_id')
                ->constrained('pipeline_stages')
                ->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('label_ar', 255);
            $table->string('label_en', 255)->nullable();
            $table->string('type', 50)->default('text');
            $table->string('placeholder_ar', 255)->nullable();
            $table->string('placeholder_en', 255)->nullable();
            $table->text('help_text_ar')->nullable();
            $table->text('help_text_en')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('show_on_transition')->default(true);
            $table->boolean('show_on_stage_view')->default(true);
            $table->boolean('show_in_history')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['pipeline_stage_id', 'is_active', 'position'], 'psf_stage_active_pos_idx');
            $table->index(['pipeline_stage_id', 'key'], 'psf_stage_key_idx');
        });

        // 2. Lead Stage Field Values table
        Schema::create('lead_stage_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();
            $table->foreignId('pipeline_stage_id')
                ->constrained('pipeline_stages')
                ->cascadeOnDelete();
            $table->foreignId('pipeline_stage_field_id')
                ->nullable()
                ->constrained('pipeline_stage_fields')
                ->nullOnDelete();
            $table->foreignId('lead_status_history_id')
                ->nullable()
                ->constrained('lead_status_histories')
                ->nullOnDelete();
            $table->string('field_key', 100);
            $table->string('field_type', 50)->default('text');
            $table->longText('value')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'pipeline_stage_id'], 'lsfv_lead_stage_idx');
            $table->index(['lead_id', 'lead_status_history_id'], 'lsfv_lead_history_idx');
            $table->index(['pipeline_stage_field_id'], 'lsfv_field_idx');
        });

        // 3. Seed canonical stage fields if stages exist
        $this->seedCanonicalStageFields();
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::dropIfExists('lead_stage_field_values');
        Schema::dropIfExists('pipeline_stage_fields');
    }

    private function seedCanonicalStageFields(): void
    {
        // No Answer stage: Next callback date (datetime, required), Notes (textarea, optional)
        $noAnswerStage = DB::table('pipeline_stages')->where('code', 'no_answer')->first();
        if ($noAnswerStage) {
            $exists = DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $noAnswerStage->id)
                ->where('key', 'callback_at')
                ->exists();

            if (! $exists) {
                DB::table('pipeline_stage_fields')->insert([
                    [
                        'pipeline_stage_id' => $noAnswerStage->id,
                        'key' => 'callback_at',
                        'label_ar' => 'موعد إعادة الاتصال',
                        'label_en' => 'Next Callback Date',
                        'type' => 'datetime',
                        'placeholder_ar' => 'حدد تاريخ ووقت إعادة الاتصال',
                        'placeholder_en' => 'Select callback date and time',
                        'help_text_ar' => 'موعد الاتصال القادم بالعميل لمتابعة الرد',
                        'help_text_en' => 'Next scheduled callback date for this lead',
                        'is_required' => true,
                        'options' => null,
                        'validation_rules' => null,
                        'conditions' => null,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'pipeline_stage_id' => $noAnswerStage->id,
                        'key' => 'notes',
                        'label_ar' => 'ملاحظات المحاولة',
                        'label_en' => 'Attempt Notes',
                        'type' => 'textarea',
                        'placeholder_ar' => 'أدخل أي ملاحظات حول محاولة الاتصال',
                        'placeholder_en' => 'Enter any notes about this attempt',
                        'help_text_ar' => 'تفاصيل إضافية حول سبب عدم الرد',
                        'help_text_en' => 'Additional details about no-answer attempt',
                        'is_required' => false,
                        'options' => null,
                        'validation_rules' => null,
                        'conditions' => null,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }

        // Not Interested stage: Reason (select/text, required), Notes (textarea, optional)
        $notInterestedStage = DB::table('pipeline_stages')->where('code', 'not_interested')->first();
        if ($notInterestedStage) {
            $exists = DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $notInterestedStage->id)
                ->where('key', 'reason')
                ->exists();

            if (! $exists) {
                $reasonOptions = json_encode([
                    ['value' => 'high_price', 'label_ar' => 'السعر مرتفع', 'label_en' => 'High Price'],
                    ['value' => 'not_convinced', 'label_ar' => 'غير مقتنع بالفكرة', 'label_en' => 'Not Convinced'],
                    ['value' => 'no_budget', 'label_ar' => 'لا توجد ميزانية حالياً', 'label_en' => 'No Budget Currently'],
                    ['value' => 'competitor', 'label_ar' => 'يتعامل مع جهة أخرى', 'label_en' => 'Using Another Provider'],
                    ['value' => 'bad_timing', 'label_ar' => 'التوقيت غير مناسب', 'label_en' => 'Bad Timing'],
                    ['value' => 'other', 'label_ar' => 'سبب آخر', 'label_en' => 'Other Reason'],
                ], JSON_UNESCAPED_UNICODE);

                DB::table('pipeline_stage_fields')->insert([
                    [
                        'pipeline_stage_id' => $notInterestedStage->id,
                        'key' => 'reason',
                        'label_ar' => 'سبب عدم الاهتمام',
                        'label_en' => 'Disinterest Reason',
                        'type' => 'select',
                        'placeholder_ar' => 'اختر سبب عدم الاهتمام',
                        'placeholder_en' => 'Select disinterest reason',
                        'help_text_ar' => 'السبب الرئيسي لعدم اهتمام العميل',
                        'help_text_en' => 'Primary reason for disinterest',
                        'is_required' => true,
                        'options' => $reasonOptions,
                        'validation_rules' => null,
                        'conditions' => null,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'pipeline_stage_id' => $notInterestedStage->id,
                        'key' => 'notes',
                        'label_ar' => 'ملاحظات وتفاصيل',
                        'label_en' => 'Notes & Details',
                        'type' => 'textarea',
                        'placeholder_ar' => 'أدخل تفاصيل إضافية حول سبب الرفض',
                        'placeholder_en' => 'Enter additional details about the refusal',
                        'help_text_ar' => 'أي تعليقات أو شروط من العميل',
                        'help_text_en' => 'Any customer feedback or conditions',
                        'is_required' => false,
                        'options' => null,
                        'validation_rules' => null,
                        'conditions' => null,
                        'show_on_transition' => true,
                        'show_on_stage_view' => true,
                        'show_in_history' => true,
                        'is_active' => true,
                        'position' => 2,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }
    }
};

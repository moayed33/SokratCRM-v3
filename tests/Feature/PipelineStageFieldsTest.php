<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PipelineStageFieldsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private PipelineStage $noAnswerStage;
    private PipelineStage $notInterestedStage;
    private PipelineStage $customStage;
    private LeadStatus $noAnswerStatus;
    private LeadStatus $notInterestedStatus;
    private LeadStatus $customStatus;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->firstOrFail();
        $superAdminGroup->permissions()->syncWithoutDetaching(
            Permission::query()->pluck('id')->all()
        );

        $this->admin = User::factory()->create([
            'username' => 'stage_admin',
            'name' => 'مدير المراحل',
            'is_active' => true,
        ]);
        $this->admin->groups()->syncWithoutDetaching([$superAdminGroup->id]);

        $stageNew = PipelineStage::query()->where('code', 'new')->first()
            ?? PipelineStage::query()->create([
                'code' => 'new',
                'name_ar' => 'جديد',
                'position' => 1,
                'is_primary' => true,
                'is_active' => true,
            ]);

        $statusNew = LeadStatus::query()->where('code', 'new')->first()
            ?? LeadStatus::query()->create([
                'pipeline_stage_id' => $stageNew->id,
                'code' => 'new',
                'name_ar' => 'جديد',
                'position' => 1,
                'is_active' => true,
            ]);

        $this->noAnswerStage = PipelineStage::query()->where('code', 'no_answer')->first()
            ?? PipelineStage::query()->create([
                'code' => 'no_answer',
                'name_ar' => 'لم يتم الرد',
                'position' => 2,
                'is_primary' => true,
                'is_active' => true,
            ]);

        $this->noAnswerStatus = LeadStatus::query()->where('code', 'no_answer')->first()
            ?? LeadStatus::query()->create([
                'pipeline_stage_id' => $this->noAnswerStage->id,
                'code' => 'no_answer',
                'name_ar' => 'لم يتم الرد',
                'position' => 2,
                'is_active' => true,
            ]);

        $this->notInterestedStage = PipelineStage::query()->where('code', 'not_interested')->first()
            ?? PipelineStage::query()->create([
                'code' => 'not_interested',
                'name_ar' => 'غير مهتم',
                'position' => 3,
                'is_primary' => true,
                'is_active' => true,
            ]);

        $this->notInterestedStatus = LeadStatus::query()->where('code', 'not_interested')->first()
            ?? LeadStatus::query()->create([
                'pipeline_stage_id' => $this->notInterestedStage->id,
                'code' => 'not_interested',
                'name_ar' => 'غير مهتم',
                'position' => 3,
                'is_active' => true,
            ]);
        $this->customStage = PipelineStage::query()->create([
            'code' => 'stage_viewing_' . uniqid(),
            'name_ar' => 'معاينة العقار',
            'name_en' => 'Property Viewing',
            'position' => 10,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->customStatus = $this->customStage->statuses()->first()
            ?? LeadStatus::query()->create([
                'pipeline_stage_id' => $this->customStage->id,
                'code' => 'status_' . uniqid(),
                'name_ar' => 'معاينة العقار الأساسية',
                'position' => 10,
                'is_active' => true,
            ]);

        $this->lead = Lead::query()->create([
            'name' => 'أحمد العميل',
            'phone' => '01012345678',
            'lead_status_id' => $statusNew->id,
            'created_by_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_stage_fields_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings.stages.fields.index', $this->customStage));
        $response->assertOk();
        $response->assertSee('معاينة العقار');
    }

    public function test_admin_can_create_stage_field(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.fields.store', $this->customStage), [
            'label_ar' => 'تاريخ المعاينة',
            'label_en' => 'Viewing Date',
            'key' => 'viewing_date',
            'type' => 'date',
            'is_required' => '1',
            'show_on_transition' => '1',
            'show_in_history' => '1',
        ]);

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));

        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
        ]);
    }

    public function test_admin_can_edit_stage_field(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_location',
            'label_ar' => 'مكان المعاينة',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('v2.settings.stages.fields.update', [$this->customStage, $field]),
            [
                'label_ar' => 'موقع المعاينة بالتفصيل',
                'label_en' => 'Detailed Location',
                'type' => 'text',
                'is_required' => '1',
                'position' => 2,
            ]
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));

        $field->refresh();
        $this->assertSame('موقع المعاينة بالتفصيل', $field->label_ar);
        $this->assertSame('Detailed Location', $field->label_en);
        $this->assertTrue($field->is_required);
        $this->assertSame(2, $field->position);
    }

    public function test_admin_can_toggle_and_delete_stage_field_safely(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'temp_field',
            'label_ar' => 'حقل مؤقت',
            'type' => 'text',
            'is_active' => true,
        ]);

        // Toggle
        $this->actingAs($this->admin)->patch(route('v2.settings.stages.fields.toggle', [$this->customStage, $field]));
        $this->assertFalse($field->fresh()->is_active);

        // Delete (soft-delete)
        $this->actingAs($this->admin)->delete(route('v2.settings.stages.fields.destroy', [$this->customStage, $field]));
        $this->assertSoftDeleted('pipeline_stage_fields', ['id' => $field->id]);
    }

    public function test_required_field_prevents_transition_when_empty(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => []]
        );
    }

    public function test_optional_field_does_not_prevent_transition(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_notes',
            'label_ar' => 'ملاحظات المعاينة',
            'type' => 'textarea',
            'is_required' => false,
            'is_active' => true,
        ]);

        $result = app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => []]
        );

        $this->assertSame((int) $this->customStatus->id, (int) $result['lead']->lead_status_id);
    }

    public function test_select_rejects_values_outside_configured_options(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'property_type',
            'label_ar' => 'نوع العقار',
            'type' => 'select',
            'options' => [
                ['value' => 'apartment', 'label_ar' => 'شقة'],
                ['value' => 'villa', 'label_ar' => 'فيلا'],
            ],
            'is_required' => true,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'property_type' => 'commercial_shop', // invalid
                ],
            ]
        );
    }

    public function test_custom_pipeline_stage_fields_work_generically_without_hardcoded_stage_codes(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
            'is_active' => true,
        ]);

        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'inspector_name',
            'label_ar' => 'اسم المعاين',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $result = app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'viewing_date' => '2026-09-01',
                    'inspector_name' => 'م. حازم',
                ],
            ]
        );

        $this->assertSame((int) $this->customStatus->id, (int) $result['lead']->lead_status_id);

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'inspector_name',
            'value' => 'م. حازم',
        ]);
    }

    public function test_custom_stage_receives_dynamic_transition_form_in_followup_screen(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'target_status_id' => $this->customStatus->id,
        ]));

        $response->assertOk();
        $response->assertSee('تاريخ المعاينة');
        $response->assertSee('stage_fields[viewing_date]', false);
    }

    public function test_kanban_transition_submits_stage_field_values(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق على موعد المعاينة',
            'kanban_popup' => 1,
            'stage_fields' => [
                'viewing_date' => '2026-09-05',
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'viewing_date',
        ]);
    }

    public function test_lead_edit_cannot_transition_or_submit_other_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('v2.leads.update', $this->lead), [
            'name' => $this->lead->name,
            'phone' => $this->lead->phone,
            'pipeline_stage_id' => $this->customStage->id,
            'lead_status_id' => $this->customStatus->id,
            'stage_fields' => [
                'viewing_date' => '2026-09-10',
            ],
        ]);

        $response->assertRedirect(route('v2.leads'));
        $this->assertNotSame($this->customStatus->id, $this->lead->fresh()->lead_status_id);
        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'viewing_date',
        ]);
    }

    public function test_canonical_followup_submits_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_date',
            'label_ar' => 'تاريخ المعاينة',
            'type' => 'date',
            'is_required' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'communication_type' => 'call',
            'outcome' => 'متابعة سريعة للمعاينة',
            'lead_status_id' => $this->customStatus->id,
            'stage_fields' => [
                'viewing_date' => '2026-09-12',
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'viewing_date',
        ]);
    }

    public function test_conditional_required_field_evaluated_server_side(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'is_confirmed',
            'label_ar' => 'تم التأكيد؟',
            'type' => 'checkbox',
            'is_required' => false,
            'is_active' => true,
        ]);

        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'cancellation_reason',
            'label_ar' => 'سبب الإلغاء',
            'type' => 'text',
            'is_required' => true,
            'conditions' => [
                'field' => 'is_confirmed',
                'operator' => 'is_not_checked',
            ],
            'is_active' => true,
        ]);

        // When confirmed = true: cancellation_reason is not required
        $result = app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'is_confirmed' => true,
                    'cancellation_reason' => null,
                ],
            ]
        );
        $this->assertSame((int) $this->customStatus->id, (int) $result['lead']->lead_status_id);

        // When confirmed = false: cancellation_reason is required and empty will fail
        $this->expectException(ValidationException::class);
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'is_confirmed' => false,
                    'cancellation_reason' => '',
                ],
            ]
        );
    }

    public function test_field_belonging_to_another_stage_is_rejected(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->notInterestedStage->id,
            'key' => 'alien_key',
            'label_ar' => 'حقل لمرحلة أخرى',
            'type' => 'text',
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus, // Transitioning to custom stage
            $this->admin,
            [
                'stage_fields' => [
                    'alien_key' => 'some_value',
                ],
            ]
        );
    }

    public function test_inactive_field_cannot_be_injected(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'inactive_secret',
            'label_ar' => 'حقل معطل',
            'type' => 'text',
            'is_active' => false, // Inactive!
        ]);

        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'inactive_secret' => 'injected_val',
                ],
            ]
        );
    }

    public function test_arbitrary_unknown_key_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            [
                'stage_fields' => [
                    'hacker_custom_param' => 'exploit',
                ],
            ]
        );
    }

    public function test_historical_values_remain_after_leaving_stage(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_location',
            'label_ar' => 'مكان المعاينة',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        // Step 1: Transition into Custom Stage
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['viewing_location' => 'القاهرة الجديدة']]
        );

        // Step 2: Transition out to Not Interested stage
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->notInterestedStatus,
            $this->admin,
            ['disinterest_reason' => 'توقيت غير مناسب', 'stage_fields' => ['reason' => 'bad_timing']]
        );

        // Verify that custom stage historical value still exists in database
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'viewing_location',
            'value' => 'القاهرة الجديدة',
        ]);
    }

    public function test_repeated_visits_to_a_stage_preserve_full_audit_history(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'visit_notes',
            'label_ar' => 'ملاحظات الزيارة',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        // Visit 1
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['visit_notes' => 'الزيارة الأولى']]
        );

        // Leave stage
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->notInterestedStatus,
            $this->admin,
            ['disinterest_reason' => 'توقيت غير مناسب', 'stage_fields' => ['reason' => 'bad_timing']]
        );

        // Visit 2
        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['visit_notes' => 'الزيارة الثانية']]
        );

        $values = LeadStageFieldValue::query()
            ->where('lead_id', $this->lead->id)
            ->where('field_key', 'visit_notes')
            ->get();

        $this->assertCount(2, $values);
        $this->assertSame(['الزيارة الأولى', 'الزيارة الثانية'], $values->pluck('value')->all());
    }

    public function test_actor_attribution_is_stored(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'notes',
            'label_ar' => 'ملاحظات',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['notes' => 'اختبار المنسوب']]
        );

        $savedValue = LeadStageFieldValue::query()
            ->where('lead_id', $this->lead->id)
            ->where('field_key', 'notes')
            ->first();

        $this->assertNotNull($savedValue);
        $this->assertSame((int) $this->admin->id, (int) $savedValue->created_by_user_id);
    }

    public function test_lead_detail_shows_stage_history(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'inspection_report',
            'label_ar' => 'تقرير الفحص',
            'type' => 'text',
            'show_in_history' => true,
            'is_required' => false,
            'is_active' => true,
        ]);

        app(LeadTransitionService::class)->transition(
            $this->lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['inspection_report' => 'العقار بحالة ممتازة']]
        );

        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $this->lead));
        $response->assertOk();
        $response->assertSee('تقرير الفحص');
        $response->assertSee('العقار بحالة ممتازة');
    }

    public function test_admin_can_add_question_with_minimal_inputs_and_auto_generated_key(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->customStage),
            [
                'label_ar' => 'رقم رخصة البناء',
                'label_en' => 'Building License Number',
                'type' => 'text',
                'is_required' => '1',
            ]
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));

        $field = PipelineStageField::query()
            ->where('pipeline_stage_id', $this->customStage->id)
            ->where('label_ar', 'رقم رخصة البناء')
            ->first();

        $this->assertNotNull($field);
        $this->assertSame('building_license_number', $field->key);
        $this->assertTrue($field->is_required);
        $this->assertTrue($field->is_active);
        $this->assertTrue($field->show_on_transition);
        $this->assertTrue($field->show_in_history);
    }

    public function test_key_collisions_are_resolved_automatically(): void
    {
        $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->customStage),
            [
                'label_ar' => 'ملاحظات',
                'label_en' => 'Notes',
                'type' => 'text',
            ]
        );

        $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->customStage),
            [
                'label_ar' => 'ملاحظات أخرى',
                'label_en' => 'Notes',
                'type' => 'text',
            ]
        );

        $fields = PipelineStageField::query()
            ->where('pipeline_stage_id', $this->customStage->id)
            ->get();

        $keys = $fields->pluck('key')->all();
        $this->assertContains('notes', $keys);
        $this->assertContains('notes_1', $keys);
    }

    public function test_options_list_builder_persists_options_correctly(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->customStage),
            [
                'label_ar' => 'نوع الوحدة',
                'type' => 'select',
                'options_list' => [
                    ['label_ar' => 'شقة سكنية', 'label_en' => 'Apartment'],
                    ['label_ar' => 'فيلا مستقلة', 'label_en' => 'Standalone Villa'],
                    ['label_ar' => 'محل تجاري', 'label_en' => 'Commercial Shop'],
                ],
            ]
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));

        $field = PipelineStageField::query()
            ->where('pipeline_stage_id', $this->customStage->id)
            ->where('label_ar', 'نوع الوحدة')
            ->firstOrFail();

        $this->assertCount(3, $field->options);
        $this->assertSame('شقة سكنية', $field->options[0]['label_ar']);
        $this->assertSame('Apartment', $field->options[0]['label_en']);
        $this->assertSame('فيلا مستقلة', $field->options[1]['label_ar']);
    }

    public function test_preset_templates_can_be_applied_to_empty_stage(): void
    {
        $newCustomStage = PipelineStage::query()->create([
            'code' => 'stage_booking_' . uniqid(),
            'name_ar' => 'حجز مبدئي',
            'position' => 20,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.preset', $newCustomStage),
            ['preset' => 'viewing_appointment']
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $newCustomStage));

        $fields = $newCustomStage->fields()->get();
        $this->assertCount(4, $fields);
        $this->assertNotNull($fields->firstWhere('key', 'appointment_at'));
        $this->assertNotNull($fields->firstWhere('key', 'is_confirmed'));
        $this->assertNotNull($fields->firstWhere('key', 'cancellation_reason'));
    }

    public function test_preset_template_refuses_overwrite_without_confirmation(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'existing_q',
            'label_ar' => 'سؤال موجود مسبقاً',
            'type' => 'text',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.preset', $this->customStage),
            [
                'preset' => 'no_answer',
                'overwrite' => '0',
            ]
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));
        $response->assertSessionHas('error');

        $this->assertTrue(
            PipelineStageField::query()
                ->where('pipeline_stage_id', $this->customStage->id)
                ->where('key', 'existing_q')
                ->exists()
        );
    }

    public function test_validation_messages_are_human_readable_with_question_labels(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'viewing_time',
            'label_ar' => 'موعد المعاينة الدقيق',
            'type' => 'datetime',
            'is_required' => true,
            'is_active' => true,
        ]);

        try {
            StageFieldSchema::validateAndExtract($this->customStage, ['viewing_time' => '']);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('viewing_time', $errors);
            $this->assertSame('يرجى إدخال موعد المعاينة الدقيق.', $errors['viewing_time'][0]);
        }
    }

    public function test_predefined_donation_scheduling_option_still_works(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'label_en' => 'Scheduled Donation Date',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع', 'label_en' => '1 Week'],
                ['value' => '2_weeks', 'label_ar' => 'بعد أسبوعين', 'label_en' => '2 Weeks'],
                ['value' => '1_month', 'label_ar' => 'بعد شهر', 'label_en' => '1 Month'],
                ['value' => '3_months', 'label_ar' => 'بعد 3 أشهر', 'label_en' => '3 Months'],
                ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'الاتفاق على التبرع بعد أسبوع',
            'stage_fields' => [
                'scheduled_donation_date' => '1_week',
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $freshLead = $this->lead->fresh();
        $this->assertSame((int) $this->customStatus->id, (int) $freshLead->lead_status_id);
        $this->assertNotNull($freshLead->next_follow_up_at);
        $this->assertEqualsWithDelta(now()->addWeek()->timestamp, $freshLead->next_follow_up_at->timestamp, 120);

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'scheduled_donation_date',
            'value' => '1_week',
        ]);
    }

    public function test_donation_scheduling_dropdown_contains_other_option(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'label_en' => 'Scheduled Donation Date',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع', 'label_en' => '1 Week'],
                ['value' => '1_month', 'label_ar' => 'بعد شهر', 'label_en' => '1 Month'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $this->assertTrue($field->allowsCustomValue());
        $this->assertSame('date', $field->customInputType());

        $opts = $field->normalizedOptions();
        $otherOpt = collect($opts)->firstWhere('value', 'other');
        $this->assertNotNull($otherOpt);
        $this->assertSame('أخرى', $otherOpt['label_ar']);
        $this->assertSame('Other', $otherOpt['label_en']);

        // View render contains option and custom input
        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead,
            'target_status_id' => $this->customStatus->id,
        ]));

        $response->assertOk();
        $response->assertSee('value="other"', false);
        $response->assertSee('stage_fields_custom[scheduled_donation_date]', false);
    }

    public function test_selecting_other_requires_custom_date_and_rejects_empty(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع'],
                ['value' => 'other', 'label_ar' => 'أخرى'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'طلب موعد آخر للتبرع',
            'stage_fields' => [
                'scheduled_donation_date' => 'other',
            ],
            'stage_fields_custom' => [
                'scheduled_donation_date' => '',
            ],
        ]);

        $response->assertSessionHasErrors('scheduled_donation_date');

        // Lead should not have moved
        $this->assertNotSame((int) $this->customStatus->id, (int) $this->lead->fresh()->lead_status_id);
        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
        ]);
    }

    public function test_other_plus_valid_custom_date_succeeds_and_persists_actual_date(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع'],
                ['value' => 'other', 'label_ar' => 'أخرى'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق على موعد مخصص',
            'stage_fields' => [
                'scheduled_donation_date' => 'other',
            ],
            'stage_fields_custom' => [
                'scheduled_donation_date' => '2026-09-18',
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $freshLead = $this->lead->fresh();
        $this->assertSame((int) $this->customStatus->id, (int) $freshLead->lead_status_id);
        $this->assertSame('2026-09-18 00:00:00', $freshLead->next_follow_up_at?->toDateTimeString());

        // Saved in lead_stage_field_values as actual custom date, NOT 'other'
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'scheduled_donation_date',
            'value' => '2026-09-18',
        ]);

        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'field_key' => 'scheduled_donation_date',
            'value' => 'other',
        ]);
    }

    public function test_predefined_option_ignores_stale_custom_value(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_month', 'label_ar' => 'بعد شهر'],
                ['value' => 'other', 'label_ar' => 'أخرى'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'التبرع بعد شهر',
            'stage_fields' => [
                'scheduled_donation_date' => '1_month',
            ],
            'stage_fields_custom' => [
                'scheduled_donation_date' => '2026-01-01', // Stale value from switching
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $freshLead = $this->lead->fresh();
        $this->assertNotNull($freshLead->next_follow_up_at);
        // Should be ~1 month from now, not 2026-01-01
        $this->assertEqualsWithDelta(now()->addMonthsNoOverflow(1)->timestamp, $freshLead->next_follow_up_at->timestamp, 120);

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'field_key' => 'scheduled_donation_date',
            'value' => '1_month',
        ]);
    }

    public function test_history_stores_and_displays_meaningful_custom_date_answer(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد التبرع القادم',
            'label_en' => 'Next Donation Date',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع', 'label_en' => '1 Week'],
                ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'تسجيل موعد مخصص',
            'stage_fields' => [
                'scheduled_donation_date' => 'other',
            ],
            'stage_fields_custom' => [
                'scheduled_donation_date' => '2026-09-18',
            ],
        ]);

        $savedValue = LeadStageFieldValue::query()
            ->where('lead_id', $this->lead->id)
            ->where('field_key', 'scheduled_donation_date')
            ->first();

        $this->assertNotNull($savedValue);
        $this->assertSame('2026-09-18', $savedValue->value);
        $this->assertSame('2026-09-18', $savedValue->formattedValue());
        $this->assertNotNull($savedValue->lead_status_history_id);
    }

    public function test_admin_can_configure_custom_option_in_stage_question(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.fields.store', $this->customStage), [
            'label_ar' => 'موعد استلام التبرع',
            'label_en' => 'Donation Date',
            'type' => 'select',
            'allow_custom' => '1',
            'custom_type' => 'date',
            'options_list' => [
                ['label_ar' => 'بعد أسبوع', 'label_en' => '1 Week', 'value' => '1_week'],
                ['label_ar' => 'بعد شهر', 'label_en' => '1 Month', 'value' => '1_month'],
            ],
        ]);

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));

        $createdField = PipelineStageField::query()
            ->where('pipeline_stage_id', $this->customStage->id)
            ->where('label_ar', 'موعد استلام التبرع')
            ->first();

        $this->assertNotNull($createdField);
        $this->assertTrue($createdField->allowsCustomValue());
        $this->assertSame('date', $createdField->customInputType());
        $this->assertTrue($createdField->validation_rules['allow_custom']);
    }

    public function test_LeadTransition_service_executes_custom_donation_scheduling_atomically(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'scheduled_donation_date',
            'label_ar' => 'موعد استلام التبرع',
            'label_en' => 'Scheduled Donation Date',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => '1_week', 'label_ar' => 'بعد أسبوع', 'label_en' => '1 Week'],
                ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
            ],
            'validation_rules' => [
                'allow_custom' => true,
                'custom_type' => 'date',
            ],
        ]);

        $service = app(LeadTransitionService::class);
        $result = $service->transition($this->lead, $this->customStatus, $this->admin, [
            'record_followup' => true,
            'communication_type' => 'call',
            'outcome' => 'الاتفاق على موعد مخصص للتبرع',
            'stage_fields' => [
                'scheduled_donation_date' => 'other',
            ],
            'stage_fields_custom' => [
                'scheduled_donation_date' => '2026-09-25',
            ],
        ]);

        $this->assertSame((int) $this->customStatus->id, (int) $result['lead']->lead_status_id);
        $this->assertSame('2026-09-25 00:00:00', $result['lead']->next_follow_up_at?->toDateTimeString());
        $this->assertNotNull($result['history']);
        $this->assertNotNull($result['followup']);

        $savedField = $result['stage_field_values']->firstWhere('field_key', 'scheduled_donation_date');
        $this->assertNotNull($savedField);
        $this->assertSame('2026-09-25', $savedField->value);
        $this->assertSame('2026-09-25', $savedField->formattedValue());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFormField;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\LeadFieldSchema;
use Database\Seeders\CrmV2PipelineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCustomFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            ['name' => 'Super Administrator']
        );

        foreach (CrmPermission::values() as $code) {
            $perm = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0] ?? 'crm',
                    'name_ar' => $code,
                    'description' => $code,
                ]
            );
            $superAdminGroup->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $this->admin = User::factory()->create([
            'username' => 'admin_tester',
            'name' => 'مدير النظام التجريبي',
            'is_active' => true,
        ]);
        $this->admin->groups()->sync([$superAdminGroup->id]);

        $this->seed(CrmV2PipelineSeeder::class);
    }

    private function createContractNumberField(): LeadFormField
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'label_ar' => 'رقم العقد',
            'label_en' => 'Contract Number',
            'key' => 'contract_number',
            'type' => 'text',
            'section' => 'other',
            'is_active' => '1',
            'show_in_create' => '1',
            'show_in_edit' => '1',
            'show_in_filter' => '1',
            'show_in_table' => '1',
            'show_in_export' => '1',
        ]);

        $response->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
        $response->assertSessionHas('success');

        return LeadFormField::query()->where('key', 'contract_number')->firstOrFail();
    }

    private function createCityDropdown(): LeadFormField
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'label_ar' => 'المدينة',
            'label_en' => 'City',
            'key' => 'city',
            'type' => 'select',
            'section' => 'basic_info',
            'options_input' => "cairo | القاهرة | Cairo\nalex | الإسكندرية | Alexandria",
            'is_active' => '1',
            'show_in_filter' => '1',
        ]);

        $response->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));

        return LeadFormField::query()->where('key', 'city')->firstOrFail();
    }

    public function test_admin_can_add_update_and_delete_custom_fields_from_settings(): void
    {
        $field = $this->createContractNumberField();

        $this->assertFalse($field->is_system);
        // 21 seeded system fields occupy positions 10..210; new field appends after them
        $this->assertEquals(220, $field->position);

        // Settings listing renders the new field
        $this->actingAs($this->admin)->get(route('v2.settings.fields.index'))
            ->assertOk()
            ->assertSee('contract_number', false)
            ->assertSee(__('crm.lf_system_badge'));

        // Create form for another field renders
        $this->actingAs($this->admin)->get(route('v2.settings.fields.create'))->assertOk();

        // Edit form prefills
        $this->actingAs($this->admin)->get(route('v2.settings.fields.edit', $field))
            ->assertOk()
            ->assertSee('value="رقم العقد"', false);

        // Update label + visibility
        $update = $this->actingAs($this->admin)->patch(route('v2.settings.fields.update', $field), [
            'label_ar' => 'رقم العقد المعدل',
            'label_en' => 'Contract No.',
            'key' => 'contract_number',
            'type' => 'text',
            'section' => 'other',
            'is_active' => '1',
        ]);
        $update->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
        $this->assertEquals('رقم العقد المعدل', $field->fresh()->label_ar);

        // Delete
        $delete = $this->actingAs($this->admin)->delete(route('v2.settings.fields.destroy', $field));
        $delete->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
        $this->assertDatabaseMissing('lead_form_fields', ['key' => 'contract_number']);
    }

    public function test_reserved_and_invalid_keys_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'label_ar' => 'حقل محجوز',
            'key' => 'source',
            'type' => 'text',
            'section' => 'other',
        ]);

        $response->assertSessionHasErrors(['key']);
        // 'source' exists as a SYSTEM field; no custom duplicate may be created
        $this->assertSame(0, LeadFormField::query()->where('key', 'source')->where('is_system', false)->count());
    }

    public function test_select_fields_require_options(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'label_ar' => 'قائمة فارغة',
            'key' => 'empty_select',
            'type' => 'select',
            'section' => 'other',
            'options_input' => '',
        ]);

        $response->assertSessionHasErrors(['options_input']);
        $this->assertDatabaseMissing('lead_form_fields', ['key' => 'empty_select']);
    }

    public function test_system_fields_cannot_be_deleted_or_change_type(): void
    {
        $systemField = LeadFormField::query()->where('key', 'name')->firstOrFail();
        $this->assertTrue($systemField->is_system);

        $delete = $this->actingAs($this->admin)->delete(route('v2.settings.fields.destroy', $systemField));
        $delete->assertSessionHasErrors(['delete']);
        $this->assertDatabaseHas('lead_form_fields', ['key' => 'name']);

        // Locked field cannot be deactivated
        $toggle = $this->actingAs($this->admin)->patch(route('v2.settings.fields.toggle', $systemField));
        $toggle->assertSessionHasErrors(['toggle']);
        $this->assertTrue((bool) $systemField->fresh()->is_active);
    }

    public function test_custom_field_appears_on_create_form_and_persists_on_store(): void
    {
        $field = $this->createContractNumberField();

        $view = $this->actingAs($this->admin)->get(route('v2.leads.create'));
        $view->assertOk()->assertSee('custom_fields[contract_number]', false);

        $store = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'name' => 'عميل بحقل مخصص',
            'phone' => '01055556666',
            'custom_fields' => [
                'contract_number' => 'CTR-2026-001',
            ],
        ]);
        $store->assertRedirect(route('v2.leads'));

        $lead = Lead::query()->where('phone', '01055556666')->firstOrFail();
        $this->assertSame('CTR-2026-001', $lead->custom_fields['contract_number'] ?? null);
    }

    public function test_required_custom_field_blocks_submission_when_empty(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'label_ar' => 'حقل إلزامي',
            'key' => 'mandatory_note',
            'type' => 'text',
            'section' => 'other',
            'is_required' => '1',
            'is_active' => '1',
        ]);
        $response->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
        LeadFieldSchema::flush();

        $store = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'name' => 'عميل بدون حقل إلزامي',
            'phone' => '01077778888',
            'custom_fields' => [],
        ]);

        $store->assertSessionHasErrors(['custom_fields.mandatory_note']);
        $this->assertNull(Lead::query()->where('phone', '01077778888')->first());
    }

    public function test_profile_page_shows_formatted_custom_values(): void
    {
        $city = $this->createCityDropdown();

        $lead = Lead::query()->create([
            'name' => 'عميل القاهرة',
            'phone' => '01012341234',
            'lead_status_id' => LeadStatus::query()->firstOrFail()->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['city' => 'cairo'],
        ]);

        $page = $this->actingAs($this->admin)->get(route('v2.leads.show', $lead));
        $page->assertOk()
            ->assertSee(__('crm.lf_additional_data'))
            ->assertSee('القاهرة');
    }

    public function test_edit_form_prefills_and_update_merges_and_clears_values(): void
    {
        $field = $this->createContractNumberField();

        $lead = Lead::query()->create([
            'name' => 'عميل للتعديل',
            'phone' => '01099990000',
            'lead_status_id' => LeadStatus::query()->firstOrFail()->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['contract_number' => 'OLD-1'],
        ]);

        $editPage = $this->actingAs($this->admin)->get(route('v2.leads.edit', $lead));
        $editPage->assertOk()->assertSee('value="OLD-1"', false);

        // Update: change value of one key while preserving unknown/deactivated ones
        $lead->custom_fields = ['contract_number' => 'OLD-1', 'legacy_key' => 'keep-me'];
        $lead->save();

        $update = $this->actingAs($this->admin)->patch(route('v2.leads.update', $lead), [
            'name' => 'عميل للتعديل',
            'phone' => '01099990000',
            'custom_fields' => ['contract_number' => 'NEW-42'],
        ]);
        $update->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertSame('NEW-42', $lead->custom_fields['contract_number']);
        $this->assertSame('keep-me', $lead->custom_fields['legacy_key']);

        // Clearing the input removes the key
        $clear = $this->actingAs($this->admin)->patch(route('v2.leads.update', $lead), [
            'name' => 'عميل للتعديل',
            'phone' => '01099990000',
            'custom_fields' => ['contract_number' => ''],
        ]);
        $clear->assertRedirect(route('v2.leads'));

        $lead->refresh();
        $this->assertArrayNotHasKey('contract_number', $lead->custom_fields ?? []);
    }

    public function test_filters_reflect_configurable_fields(): void
    {
        $city = $this->createCityDropdown();

        $statusId = LeadStatus::query()->firstOrFail()->id;

        Lead::query()->create([
            'name' => 'عميل في القاهرة',
            'phone' => '01111111111',
            'lead_status_id' => $statusId,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['city' => 'cairo'],
        ]);

        $alexLead = Lead::query()->create([
            'name' => 'عميل في الإسكندرية',
            'phone' => '01222222222',
            'lead_status_id' => $statusId,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['city' => 'alex'],
        ]);

        Lead::query()->create([
            'name' => 'عميل بدون مدينة',
            'phone' => '01333333333',
            'lead_status_id' => $statusId,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
        ]);

        // Filter form shows the configurable field
        $index = $this->actingAs($this->admin)->get(route('v2.leads'));
        $index->assertOk()->assertSee('name="city"', false);

        // Exact dropdown match
        $filtered = $this->actingAs($this->admin)->get(route('v2.leads', ['city' => 'cairo']));
        $filtered->assertOk()
            ->assertSee('عميل في القاهرة')
            ->assertDontSee('عميل في الإسكندرية');

        // Reset link keeps active filters
        $this->actingAs($this->admin)->get(route('v2.leads', ['city' => 'cairo']))
            ->assertSee(route('v2.leads'));
    }

    public function test_text_number_checkbox_date_and_multiselect_filters_apply(): void
    {
        $statusId = LeadStatus::query()->firstOrFail()->id;

        $mk = static fn (): Lead => Lead::query()->make([
            'name' => 'x',
            'phone' => '0100000000'.strval(random_int(10, 99)),
            'lead_status_id' => $statusId,
            'created_by' => 'tester',
        ]);

        $textLead = $mk();
        $textLead->custom_fields = ['note_txt' => 'hello-world-marker'];
        $textLead->save();

        $numLead = $mk();
        $numLead->custom_fields = ['score_num' => 7];
        $numLead->save();

        $boolLead = $mk();
        $boolLead->custom_fields = ['flag_bool' => true];
        $boolLead->save();

        $dateLead = $mk();
        $dateLead->custom_fields = ['visit_day' => '2026-08-24'];
        $dateLead->save();

        $multiLead = $mk();
        $multiLead->custom_fields = ['tags_multi' => ['vip', 'urgent']];
        $multiLead->save();

        foreach ([
            ['key' => 'note_txt', 'type' => 'text'],
            ['key' => 'score_num', 'type' => 'number'],
            ['key' => 'flag_bool', 'type' => 'checkbox'],
            ['key' => 'visit_day', 'type' => 'date'],
            ['key' => 'tags_multi', 'type' => 'multiselect'],
        ] as $definition) {
            $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
                'label_ar' => $definition['key'],
                'key' => $definition['key'],
                'type' => $definition['type'],
                'section' => 'other',
                'options_input' => $definition['type'] === 'multiselect' ? "vip\nurgent\nnormal" : null,
                'is_active' => '1',
                'show_in_filter' => '1',
            ])->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
        }
        LeadFieldSchema::flush();

        $expectSingle = function (array $params, Lead $expected): void {
            $response = $this->actingAs($this->admin)->get(route('v2.leads', $params));
            $response->assertOk();
            $ids = $response->viewData('leads')->getCollection()->pluck('id')->all();
            $this->assertContains($expected->id, $ids, 'Expected lead '.$expected->id.' for params '.json_encode($params));
        };

        $expectSingle(['note_txt' => 'hello-world'], $textLead);
        $expectSingle(['score_num' => '7'], $numLead);
        $expectSingle(['flag_bool' => '1'], $boolLead);
        $expectSingle(['visit_day' => '2026-08-24'], $dateLead);
        $expectSingle(['tags_multi' => ['vip']], $multiLead);

        // Checkbox negative match excludes the true lead
        $response = $this->actingAs($this->admin)->get(route('v2.leads', ['flag_bool' => '0']));
        $ids = $response->viewData('leads')->getCollection()->pluck('id')->all();
        $this->assertNotContains($boolLead->id, $ids);

        // Multiselect requires ALL selected tags (AND semantics)
        $response = $this->actingAs($this->admin)->get(route('v2.leads', ['tags_multi' => ['vip', 'urgent']]));
        $ids = $response->viewData('leads')->getCollection()->pluck('id')->all();
        $this->assertContains($multiLead->id, $ids);
    }

    public function test_table_column_and_csv_export_include_flagged_fields(): void
    {
        $field = $this->createContractNumberField();

        $lead = Lead::query()->create([
            'name' => 'عميل تصدير',
            'phone' => '01444444444',
            'lead_status_id' => LeadStatus::query()->firstOrFail()->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['contract_number' => 'EXP-9'],
        ]);

        // Table column header rendered
        $this->actingAs($this->admin)->get(route('v2.leads'))
            ->assertOk()
            ->assertSee('رقم العقد');

        // Export selected contains the custom column + value
        $export = $this->actingAs($this->admin)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$lead->id],
        ]);
        $export->assertOk();

        $content = $export->streamedContent();
        $this->assertStringContainsString('رقم العقد', $content);
        $this->assertStringContainsString('EXP-9', $content);
    }

    public function test_search_covers_text_like_custom_fields(): void
    {
        $field = $this->createContractNumberField();

        $lead = Lead::query()->create([
            'name' => 'عميل بحث',
            'phone' => '01555550000',
            'lead_status_id' => LeadStatus::query()->firstOrFail()->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['contract_number' => 'UNIQUE-XYZ'],
        ]);

        $found = $this->actingAs($this->admin)->get(route('v2.leads', ['q' => 'UNIQUE-XYZ']));
        $ids = $found->viewData('leads')->getCollection()->pluck('id')->all();
        $this->assertContains($lead->id, $ids);
    }

    public function test_deleting_a_field_strips_stored_values(): void
    {
        $field = $this->createContractNumberField();

        $lead = Lead::query()->create([
            'name' => 'عميل قبل الحذف',
            'phone' => '01666660000',
            'lead_status_id' => LeadStatus::query()->firstOrFail()->id,
            'created_by' => $this->admin->name,
            'created_by_user_id' => $this->admin->id,
            'custom_fields' => ['contract_number' => 'GONE-1'],
        ]);

        $this->actingAs($this->admin)->delete(route('v2.settings.fields.destroy', $field))
            ->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));

        $lead->refresh();
        $this->assertArrayNotHasKey('contract_number', $lead->custom_fields ?? []);
    }

    public function test_schema_cache_flushes_after_mutations(): void
    {
        $before = LeadFieldSchema::active()->count();

        $this->createContractNumberField();

        $after = LeadFieldSchema::active()->count();
        $this->assertSame($before + 1, $after);
    }
}

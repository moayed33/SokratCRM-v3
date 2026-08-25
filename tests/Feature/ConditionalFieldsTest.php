<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalFieldsTest extends TestCase
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
            'username' => 'cond_admin',
            'name' => 'مدير الحقول الشرطية',
            'is_active' => true,
        ]);
        $this->admin->groups()->sync([$superAdminGroup->id]);
    }

    private function createParentDropdown(): void
    {
        $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'entity' => 'leads',
            'label_ar' => 'نوع الجهة',
            'key' => 'org_kind',
            'type' => 'select',
            'section' => 'other',
            'options_input' => "company | شركة | Company\nperson | فرد | Person",
            'is_active' => '1',
            'show_in_create' => '1',
        ])->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));
    }

    public function test_field_with_condition_renders_hidden_markers_on_form(): void
    {
        $this->createParentDropdown();

        $createDependent = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'entity' => 'leads',
            'label_ar' => 'اسم الشركة الأم',
            'key' => 'parent_company',
            'type' => 'text',
            'section' => 'other',
            'is_active' => '1',
            'condition_field' => 'org_kind',
            'condition_value' => 'company',
        ]);

        $createDependent->assertRedirect(route('v2.settings.fields.index', ['entity' => 'leads']));

        // The dependent field is stored with its condition
        $this->assertDatabaseHas('lead_form_fields', [
            'key' => 'parent_company',
            'condition_field' => 'org_kind',
            'condition_value' => 'company',
        ]);

        // Lead create form renders the condition markers + runtime script
        $page = $this->actingAs($this->admin)->get(route('v2.leads.create'));
        $page->assertOk()
            ->assertSee('data-conditioned="1"', false)
            ->assertSee('data-parent="org_kind"', false)
            ->assertSee('custom_fields[parent_company]', false);
    }

    public function test_invalid_condition_parent_is_rejected(): void
    {
        $this->createParentDropdown();

        $response = $this->actingAs($this->admin)->post(route('v2.settings.fields.store'), [
            'entity' => 'leads',
            'label_ar' => 'حقل تابع لأب غير صالح',
            'key' => 'dependent_bad',
            'type' => 'text',
            'section' => 'other',
            'is_active' => '1',
            'condition_field' => 'does_not_exist',
            'condition_value' => 'x',
        ]);

        $response->assertSessionHasErrors(['condition_field']);
        $this->assertDatabaseMissing('lead_form_fields', ['key' => 'dependent_bad']);
    }
}

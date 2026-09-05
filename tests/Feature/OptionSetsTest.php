<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\CrmOptions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OptionSetsTest extends TestCase
{
    use DatabaseTransactions;

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
            'username' => 'picklist_admin',
            'name' => 'مدير القوائم',
            'is_active' => true,
        ]);
        $this->admin->groups()->sync([$superAdminGroup->id]);
    }

    public function test_seeded_option_sets_exist(): void
    {
        $this->assertSame(['عمل', 'منزل', 'واتساب', 'إضافي', 'أخرى'], CrmOptions::values('phone_label'));
        $this->assertContains('عائلة / قريب', CrmOptions::values('relation_type'));
    }
    public function test_admin_can_view_edit_option_set_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings.option-sets.edit', ['set' => 'relation_type']));
        $response->assertOk();
        $response->assertSee('relation_type');
        $response->assertSee('عائلة / قريب');

        $phoneResponse = $this->actingAs($this->admin)->get(route('v2.settings.option-sets.edit', ['set' => 'phone_label']));
        $phoneResponse->assertOk();
        $phoneResponse->assertSee('phone_label');
        $phoneResponse->assertSee('عمل');
    }

    public function test_admin_can_edit_picklist_and_forms_reflect_it(): void
    {
        $response = $this->actingAs($this->admin)->put(route('v2.settings.option-sets.update', ['set' => 'phone_label']), [
            'options_input' => "عمل | عمل | Work\nشقة | شقة | Apartment",
        ]);
        $response->assertRedirect(route('v2.settings.option-sets.edit', ['set' => 'phone_label']));

        $this->assertSame(['عمل', 'شقة'], CrmOptions::values('phone_label'));

        // Lead create form uses the managed list instead of hardcoded labels
        $page = $this->actingAs($this->admin)->get(route('v2.leads.create'));
        $page->assertOk()->assertSee('شقة');
    }

    public function test_admin_can_create_and_delete_custom_picklists(): void
    {
        $create = $this->actingAs($this->admin)->post(route('v2.settings.option-sets.store'), [
            'set_key' => 'shipping_methods',
            'options_input' => "courier | مندوب | Courier\nmail | بريد | Mail",
        ]);
        $create->assertRedirect(route('v2.settings.option-sets.edit', ['set' => 'shipping_methods']));

        $this->assertSame(['courier', 'mail'], CrmOptions::values('shipping_methods'));

        $delete = $this->actingAs($this->admin)->delete(route('v2.settings.option-sets.destroy', ['set' => 'shipping_methods']));
        $delete->assertRedirect(route('v2.settings.option-sets.index'));
        $this->assertSame([], CrmOptions::values('shipping_methods'));
    }

    public function test_system_picklists_cannot_be_deleted(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('v2.settings.option-sets.destroy', ['set' => 'phone_label']));
        $response->assertRedirect();
        $this->assertNotSame([], CrmOptions::values('phone_label'));
    }
}

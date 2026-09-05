<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Governorate;
use App\Models\GovernorateSubregion;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GeographyManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'geo.admin',
            'is_active' => true,
        ]);
        $this->givePermissions($this->admin, [
            CrmPermission::SETTINGS_ACCESS,
            CrmPermission::COLLECTIONS_GEOGRAPHY_MANAGE,
        ]);
    }

    public function test_admin_can_view_governorates_list_and_subregions(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings.governorates.index'));

        $response->assertOk();
        $response->assertSee('القاهرة');
        $response->assertSee('الجيزة');
        $response->assertSee('الإسكندرية');
    }

    public function test_admin_can_create_update_and_toggle_governorate(): void
    {
        // 1. Create new governorate
        $res = $this->actingAs($this->admin)->post(route('v2.settings.governorates.store'), [
            'name_ar' => 'محافظة تجريبية جديدة',
            'name_en' => 'Test Governorate',
            'code' => 'test_gov',
            'position' => 99,
        ]);

        $res->assertRedirect();
        $gov = Governorate::query()->where('code', 'test_gov')->firstOrFail();
        $this->assertSame('محافظة تجريبية جديدة', $gov->name_ar);
        $this->assertSame('Test Governorate', $gov->name_en);
        $this->assertTrue($gov->is_active);

        // 2. Update governorate
        $updateRes = $this->actingAs($this->admin)->patch(route('v2.settings.governorates.update', $gov), [
            'name_ar' => 'محافظة تجريبية محدثة',
            'name_en' => 'Updated Test Governorate',
            'code' => 'test_gov_updated',
            'position' => 100,
        ]);
        $updateRes->assertRedirect();
        $gov->refresh();
        $this->assertSame('محافظة تجريبية محدثة', $gov->name_ar);
        $this->assertSame('test_gov_updated', $gov->code);

        // 3. Toggle active
        $toggleRes = $this->actingAs($this->admin)->patch(route('v2.settings.governorates.toggle', $gov));
        $toggleRes->assertRedirect();
        $gov->refresh();
        $this->assertFalse($gov->is_active);

        // 4. Delete empty governorate
        $deleteRes = $this->actingAs($this->admin)->delete(route('v2.settings.governorates.destroy', $gov));
        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('governorates', ['id' => $gov->id]);
    }

    public function test_admin_can_create_update_toggle_and_delete_subregion(): void
    {
        $cairo = Governorate::query()->where('code', 'cairo')->firstOrFail();

        // 1. Create subregion
        $res = $this->actingAs($this->admin)->post(route('v2.settings.governorates.subregions.store', $cairo), [
            'name_ar' => 'حي السفارات',
            'name_en' => 'Embassies District',
            'code' => 'embassies_district',
        ]);
        $res->assertRedirect();

        $sub = GovernorateSubregion::query()->where('code', 'embassies_district')->where('governorate_id', $cairo->id)->firstOrFail();
        $this->assertSame('حي السفارات', $sub->name_ar);
        $this->assertTrue($sub->is_active);

        // 2. Update subregion
        $updateRes = $this->actingAs($this->admin)->patch(route('v2.settings.subregions.update', $sub), [
            'name_ar' => 'حي السفارات الراقي',
            'name_en' => 'Diplomatic Quarter',
            'code' => 'diplomatic_quarter',
        ]);
        $updateRes->assertRedirect();
        $sub->refresh();
        $this->assertSame('حي السفارات الراقي', $sub->name_ar);

        // 3. Toggle subregion
        $toggleRes = $this->actingAs($this->admin)->patch(route('v2.settings.subregions.toggle', $sub));
        $toggleRes->assertRedirect();
        $sub->refresh();
        $this->assertFalse($sub->is_active);

        // 4. API endpoint returns only active subregions
        $apiRes = $this->actingAs($this->admin)->getJson(route('v2.governorates.subregions.api', $cairo));
        $apiRes->assertOk();
        $apiRes->assertJsonMissing(['code' => 'diplomatic_quarter']);

        // Reactivate and check API
        $sub->update(['is_active' => true]);
        $apiRes2 = $this->actingAs($this->admin)->getJson(route('v2.governorates.subregions.api', $cairo));
        $apiRes2->assertOk();
        $apiRes2->assertJsonFragment(['code' => 'diplomatic_quarter']);

        // 5. Delete empty subregion
        $delRes = $this->actingAs($this->admin)->delete(route('v2.settings.subregions.destroy', $sub));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('governorate_subregions', ['id' => $sub->id]);
    }

    private function givePermissions(User $user, array $permissions): void
    {
        $group = \App\Models\Group::query()->firstOrCreate(
            ['code' => 'geo-test-group'],
            ['name' => 'Geo Test Group', 'is_system' => false]
        );
        $permIds = \App\Models\Permission::query()
            ->whereIn('code', array_map(static fn (CrmPermission $p) => $p->value, $permissions))
            ->pluck('id');
        $group->permissions()->sync($permIds);
        $user->groups()->sync([$group->id]);
    }
}

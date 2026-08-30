<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Models\VoipExtensionAssignment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use DatabaseTransactions;

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Test User '.fake()->unique()->word(),
            'code' => 'test-user-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ],
            );
            $group->permissions()->attach($permission);
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    public function test_language_switcher_changes_only_the_session_and_redirects(): void
    {
        $user = $this->userWithPermissions([]);
        $user->update(['locale' => 'ar']);

        $response = $this->actingAs($user)
            ->get(route('v2.lang.switch', ['locale' => 'en']));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_middleware_applies_locale_from_session(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertSame('en', app()->getLocale());
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
        $response->assertSee('Dashboard');
        $response->assertSee('Colorful light');
        $response->assertSee('Colorful dark');
        $response->assertSee('Monotone light');
        $response->assertSee('Monotone dark');
    }

    public function test_arabic_locale_default_and_rtl_direction(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertSame('ar', app()->getLocale());
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('لوحة التحكم');
    }

    public function test_voip_live_panel_translations_in_english_and_arabic(): void
    {
        $user = $this->userWithPermissions(['voip.live_panel']);
        $user->update(['voip_extension' => '101']);
        VoipExtensionAssignment::query()->create([
            'user_id' => $user->id,
            'extension' => '101',
            'assigned_from' => now()->subDay(),
        ]);
        // Test English locale
        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('v2.voip.live'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('dir="ltr"', false);
        $responseEn->assertSee('lang="en"', false);
        $responseEn->assertSee('Live PBX Monitor');
        $responseEn->assertSee('System Admin');
        $responseEn->assertSee('Total Extensions');
        $responseEn->assertSee('Online Lines');
        $responseEn->assertSee('Active Calls');
        $responseEn->assertSee('All');
        $responseEn->assertSee('Online');
        $responseEn->assertSee('In Call');
        $responseEn->assertSee('Offline');
        $responseEn->assertSee('Search by name or extension number...');
        $responseEn->assertSee('Extension:');
        $responseEn->assertDontSee('Call Actions');
        $responseEn->assertDontSee('class="action-btn"', false);

        // Test Arabic locale
        $responseAr = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.voip.live'));

        $responseAr->assertStatus(200);
        $responseAr->assertSee('dir="rtl"', false);
        $responseAr->assertSee('lang="ar"', false);
        $responseAr->assertSee('لوحة المراقبة المباشرة للسنترال');
        $responseAr->assertSee('مدير النظام');
        $responseAr->assertSee('إجمالي التحويلات');
        $responseAr->assertSee('الخطوط المتصلة');
        $responseAr->assertSee('المكالمات الجارية');
        $responseAr->assertSee('الكل');
        $responseAr->assertSee('متصل');
        $responseAr->assertSee('في مكالمة');
        $responseAr->assertSee('غير متصل');
        $responseAr->assertSee('بحث بالاسم أو رقم التحويلة...');
        $responseAr->assertSee('التحويلة :');
        $responseAr->assertDontSee('إجراءات المكالمة');
        $responseAr->assertDontSee('class="action-btn"', false);
    }

    public function test_settings_page_displays_language_switcher_options(): void
    {
        $user = $this->userWithPermissions(['settings.access']);

        $responseAr = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.settings'));
        $responseAr->assertStatus(200);
        $responseAr->assertSee('لغة النظام');
        $responseAr->assertSee(route('v2.lang.switch', 'ar'));
        $responseAr->assertSee(route('v2.lang.switch', 'en'));

        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('v2.settings'));
        $responseEn->assertStatus(200);
        $responseEn->assertSee('System Language');
        $responseEn->assertSee(route('v2.lang.switch', 'ar'));
        $responseEn->assertSee(route('v2.lang.switch', 'en'));
    }
}

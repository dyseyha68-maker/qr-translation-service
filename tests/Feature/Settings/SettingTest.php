<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\RoleName;
use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName(RoleName::ADMIN->value));

        $this->staff = User::factory()->create();
        $this->staff->assignRole(Role::findByName(RoleName::STAFF->value));
    }

    public function test_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertOk();
    }

    public function test_staff_cannot_view_settings_page(): void
    {
        $response = $this->actingAs($this->staff)->get(route('settings.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_branding_settings_and_color(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update'), [
            'brand_name' => 'Prestige Translation Phnom Penh',
            'theme_color' => 'emerald',
            'support_email' => 'contact@prestigetranslation.com',
            'support_phone' => '+855 23 888 999',
            'address' => 'Norodom Blvd, Phnom Penh',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('Prestige Translation Phnom Penh', AppSetting::getValue('brand_name'));
        $this->assertEquals('emerald', AppSetting::getValue('theme_color'));
        $this->assertEquals('contact@prestigetranslation.com', AppSetting::getValue('support_email'));
    }

    public function test_admin_can_upload_and_delete_system_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('custom_logo.png', 400, 400);

        $response = $this->actingAs($this->admin)->post(route('settings.update'), [
            'brand_name' => 'Logo Test Translation',
            'theme_color' => 'indigo',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('settings.index'));

        $savedPath = AppSetting::getValue('system_logo');
        $this->assertNotNull($savedPath);
        Storage::disk('public')->assertExists($savedPath);

        // Now test delete logo
        $deleteResponse = $this->actingAs($this->admin)->delete(route('settings.logo.destroy'));
        $deleteResponse->assertRedirect(route('settings.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertNull(AppSetting::getValue('system_logo'));
        Storage::disk('public')->assertMissing($savedPath);
    }

    public function test_user_can_upload_and_remove_profile_avatar(): void
    {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($this->admin)->patch(route('profile.update'), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->admin->refresh();

        $this->assertNotNull($this->admin->avatar_path);
        Storage::disk('public')->assertExists($this->admin->avatar_path);

        // Delete avatar
        $deleteResponse = $this->actingAs($this->admin)->delete(route('profile.avatar.destroy'));
        $deleteResponse->assertRedirect(route('profile.edit'));
        $deleteResponse->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertNull($this->admin->avatar_path);
    }
}

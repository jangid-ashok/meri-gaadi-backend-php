<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_view_and_update_settings(): void
    {
        $admin = $this->adminWithPermissions('settings.view', 'settings.update');

        $this->actingAs($admin)->getJson('/api/admin/settings')->assertOk();

        $this->actingAs($admin)->putJson('/api/admin/settings', [
            'site_name' => 'Meri Gaadi',
            'contact_email' => 'hello@example.com',
            'maintenance_mode' => true,
            'default_pagination' => 20,
        ])->assertOk();

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Meri Gaadi']);
        $this->assertDatabaseHas('settings', ['key' => 'maintenance_mode', 'value' => '1']);
    }

    public function test_unauthorized_users_cannot_access_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/settings')->assertForbidden();
        $this->actingAs($admin)->putJson('/api/admin/settings', ['site_name' => 'Meri Gaadi'])->assertForbidden();

        $viewer = $this->adminWithPermissions('settings.view');
        $this->actingAs($viewer)->putJson('/api/admin/settings', ['site_name' => 'Meri Gaadi'])->assertForbidden();
    }

    public function test_validation_rejects_unknown_keys_invalid_values_and_invalid_urls(): void
    {
        $admin = $this->adminWithPermissions('settings.view', 'settings.update');

        $this->actingAs($admin)->putJson('/api/admin/settings', [
            'unknown_key' => 'nope',
            'site_name' => '  ',
            'contact_email' => 'not-an-email',
            'default_pagination' => 'abc',
            'maintenance_mode' => 'maybe',
            'facebook_url' => 'not-a-url',
        ])->assertUnprocessable()->assertJsonValidationErrors(['unknown_key', 'site_name', 'contact_email', 'default_pagination', 'maintenance_mode', 'facebook_url']);
    }

    public function test_seeded_settings_are_present_and_grouped(): void
    {
        $admin = $this->adminWithPermissions('settings.view');

        $this->actingAs($admin)->getJson('/api/admin/settings/general')->assertOk()->assertJsonFragment(['key' => 'site_name']);
        $this->actingAs($admin)->getJson('/api/admin/settings/system')->assertOk()->assertJsonFragment(['key' => 'default_pagination']);
        $this->assertNotNull(Setting::query()->where('key', 'site_name')->first());
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Settings tester '.uniqid(), 'slug' => 'settings-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Settings']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_api_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_api_requires_dashboard_permission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_admin_with_dashboard_permission_receives_live_counts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $permission = Permission::create(['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard']);
        $role = Role::create(['name' => 'Dashboard viewer', 'slug' => 'dashboard-viewer']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);
        User::factory()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('statistics.total_users', 2)
            ->assertJsonPath('statistics.total_admins', 1)
            ->assertJsonPath('statistics.total_roles', 1)
            ->assertJsonPath('statistics.total_permissions', 1)
            ->assertJsonPath('recent_activity', []);
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $admin->roles()->attach($role);

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk();
    }
}

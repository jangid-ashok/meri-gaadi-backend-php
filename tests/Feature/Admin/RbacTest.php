<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_rbac_api_requests_return_unauthorized(): void
    {
        $this->getJson('/api/admin/roles')->assertUnauthorized();
    }

    public function test_user_with_permission_can_access_role_api(): void
    {
        [$admin, $permission] = $this->adminWithPermission('roles.view');
        $role = Role::create(['name' => 'Reader', 'slug' => 'reader']);

        $this->actingAs($admin)->getJson('/api/admin/roles')->assertOk();
        $this->assertTrue($admin->hasPermission($permission->slug));
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/roles')->assertForbidden();
    }

    public function test_multiple_roles_combine_permissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $view = Permission::create(['name' => 'View roles', 'slug' => 'roles.view', 'module' => 'Roles']);
        $create = Permission::create(['name' => 'Create roles', 'slug' => 'roles.create', 'module' => 'Roles']);
        $admin->roles()->create(['name' => 'Viewer', 'slug' => 'viewer'])->permissions()->attach($view);
        $admin->roles()->create(['name' => 'Creator', 'slug' => 'creator'])->permissions()->attach($create);

        $this->assertTrue($admin->hasPermission('roles.view'));
        $this->assertTrue($admin->hasPermission('roles.create'));
    }

    public function test_super_admin_can_access_every_permission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $admin->roles()->attach($role);

        $this->assertTrue($admin->hasPermission('future-module.delete'));
    }

    public function test_normal_admin_cannot_assign_super_admin(): void
    {
        [$admin] = $this->adminWithPermission('roles.assign');
        $target = User::factory()->create(['is_admin' => true]);
        $superAdmin = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);

        $this->actingAs($admin)->putJson('/api/admin/admins/'.$target->id.'/roles', ['roles' => [$superAdmin->id]])
            ->assertForbidden();
    }

    public function test_normal_admin_cannot_assign_a_role_with_unauthorized_permissions(): void
    {
        [$admin] = $this->adminWithPermission('roles.assign');
        $target = User::factory()->create(['is_admin' => true]);
        $privilegedPermission = Permission::create(['name' => 'Delete users', 'slug' => 'users.delete', 'module' => 'Users']);
        $role = Role::create(['name' => 'Privileged', 'slug' => 'privileged']);
        $role->permissions()->attach($privilegedPermission);

        $this->actingAs($admin)->putJson('/api/admin/admins/'.$target->id.'/roles', ['roles' => [$role->id]])
            ->assertForbidden();
    }

    public function test_system_roles_cannot_be_deleted_or_modified(): void
    {
        [$admin] = $this->adminWithPermission('roles.delete');
        $systemRole = Role::create(['name' => 'System', 'slug' => 'system', 'is_system' => true]);

        $this->actingAs($admin)->deleteJson('/api/admin/roles/'.$systemRole->id)->assertForbidden();
    }

    private function adminWithPermission(string $slug): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $permission = Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'Roles']);
        $role = Role::create(['name' => 'Test role', 'slug' => 'test-'.str_replace('.', '-', $slug)]);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        return [$admin, $permission];
    }
}
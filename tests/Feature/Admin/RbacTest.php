<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Admin\BlogCategory;
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

    public function test_role_assignment_cannot_remove_the_last_active_super_admin(): void
    {
        [$admin] = $this->adminWithPermission('roles.assign');
        $target = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $superAdmin = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $target->roles()->attach($superAdmin);
        $replacement = Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $this->actingAs($admin)->putJson('/api/admin/admins/'.$target->id.'/roles', ['roles' => [$replacement->id]])
            ->assertStatus(409);

        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $superAdmin->id]);
    }

    public function test_user_without_article_permissions_cannot_manage_blog_categories(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/blog-categories')->assertForbidden();
        $this->actingAs($admin)->post('/admin/blog-categories/delete', ['id' => 1])->assertForbidden();
    }

    public function test_legacy_blog_routes_enforce_create_update_delete_and_publish_permissions(): void
    {
        [$creator] = $this->adminWithPermission('articles.create');
        $category = BlogCategory::create(['title' => 'Guides', 'slug' => 'guides']);
        $payload = [
            'category_ids' => $category->id,
            'title' => 'Draft article',
            'sub_title' => 'A draft',
            'slug' => 'draft-article',
            'status' => 'inactive',
        ];

        $this->actingAs($creator)->postJson('/admin/blog/store', $payload)->assertOk()->assertJsonPath('success', true);
        $articleId = \App\Models\Admin\Blogs::where('slug', 'draft-article')->value('id');
        $this->assertNotNull($articleId);

        $this->actingAs($creator)->postJson('/admin/blog/store', array_merge($payload, ['title' => 'Published article', 'slug' => 'published-article', 'status' => 'active']))
            ->assertForbidden();
        $this->actingAs($creator)->postJson('/admin/blog/update', array_merge($payload, ['id' => $articleId]))
            ->assertForbidden();
        $this->actingAs($creator)->postJson('/admin/blog/delete', ['record_id' => $articleId])
            ->assertForbidden();

        [$publisher] = $this->adminWithPermission('articles.publish');
        $this->actingAs($publisher)->postJson('/admin/blog/store', $payload)->assertForbidden();
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

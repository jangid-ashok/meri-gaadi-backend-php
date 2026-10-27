<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_crud_is_scoped_to_admins_and_never_returns_secrets(): void
    {
        $admin = $this->adminWithPermissions('users.view', 'users.create', 'users.update', 'users.delete');
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $payload = ['name' => 'New Admin', 'email' => 'new-admin@example.com', 'role_id' => $role->id, 'status' => 'active', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'];

        $this->actingAs($admin)->postJson('/api/admin/users', $payload)->assertCreated()
            ->assertJsonPath('data.email', $payload['email'])->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');
        $created = User::whereEmail($payload['email'])->firstOrFail();
        $this->assertTrue(Hash::check($payload['password'], $created->password));
        $created->tokens()->create(['name' => 'secret', 'token' => hash('sha256', 'secret')]);

        $this->actingAs($admin)->getJson('/api/admin/users?search=New%20Admin&role='.$role->id.'&status=active')
            ->assertOk()->assertJsonPath('data.total', 1)->assertJsonMissingPath('data.data.0.password')->assertJsonMissingPath('data.data.0.remember_token');
        $this->actingAs($admin)->getJson('/api/admin/users/'.$created->id)->assertOk()->assertJsonMissingPath('data.password');
        $this->actingAs($admin)->putJson('/api/admin/users/'.$created->id, ['name' => 'Updated Admin', 'email' => $payload['email'], 'role_id' => $role->id, 'status' => 'active'])->assertOk();
        $this->assertTrue(Hash::check($payload['password'], $created->fresh()->password));
        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$created->id)->assertNoContent();
    }

    public function test_self_protection_role_assignment_and_last_super_admin_are_enforced(): void
    {
        $actor = $this->adminWithPermissions('users.view', 'users.create', 'users.update', 'users.delete', 'users.status');
        $superRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $target = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $target->roles()->attach($superRole);

        $this->actingAs($actor)->deleteJson('/api/admin/users/'.$actor->id)->assertStatus(409);
        $this->actingAs($actor)->patchJson('/api/admin/users/'.$actor->id.'/status', ['status' => 'inactive'])->assertStatus(409);
        $this->actingAs($actor)->patchJson('/api/admin/users/'.$target->id.'/status', ['status' => 'inactive'])->assertStatus(409);
        $this->actingAs($actor)->deleteJson('/api/admin/users/'.$target->id)->assertStatus(409);
        $this->actingAs($actor)->postJson('/api/admin/users', ['name' => 'Bad Role', 'email' => 'bad@example.com', 'role_id' => $superRole->id, 'status' => 'active', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertForbidden();
    }

    public function test_users_api_requires_permissions_and_validates_input(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->getJson('/api/admin/users')->assertUnauthorized();
        $this->actingAs($admin)->getJson('/api/admin/users')->assertForbidden();
        $writer = $this->adminWithPermissions('users.create');
        $this->actingAs($writer)->postJson('/api/admin/users', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'role_id', 'password']);
    }

    public function test_admin_user_pages_render_with_permission_aware_actions(): void
    {
        $admin = $this->adminWithPermissions('users.view', 'users.create', 'users.update');
        $target = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Admin users')->assertSee($target->email);
        $this->actingAs($admin)->get('/admin/users/create')->assertOk()->assertSee('Add user')->assertSee('password_confirmation');
        $this->actingAs($admin)->get('/admin/users/'.$target->id)->assertOk()->assertSee('User details')->assertSee($target->email);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $role = Role::create(['name' => 'User test '.uniqid(), 'slug' => 'user-test-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Users']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);
        return $admin;
    }
}

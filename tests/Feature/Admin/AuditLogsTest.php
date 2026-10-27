<?php

namespace Tests\Feature\Admin;

use App\Models\{AuditLog, Brand, CarModel, Permission, Role, User};
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_model_changes_are_logged_with_diff_and_secrets_redacted(): void
    {
        $admin = $this->adminWithPermissions('audit_logs.view', 'cars.create', 'cars.update', 'cars.delete');
        $brand = Brand::create(['name' => 'Audit Motors', 'slug' => 'audit-motors', 'status' => 'active']);

        $this->actingAs($admin);
        $car = CarModel::create(['brand_id' => $brand->id, 'name' => 'Audit Car', 'slug' => 'audit-car', 'status' => 'active']);
        $car->update(['description' => 'Updated description']);
        $car->update(['status' => 'inactive']);

        $created = AuditLog::where('auditable_type', CarModel::class)->where('auditable_id', $car->id)->where('action', 'create')->firstOrFail();
        $updated = AuditLog::where('auditable_type', CarModel::class)->where('auditable_id', $car->id)->where('action', 'update')->firstOrFail();
        $status = AuditLog::where('auditable_type', CarModel::class)->where('auditable_id', $car->id)->where('action', 'status_change')->firstOrFail();
        $this->assertSame($admin->id, $created->user_id);
        $this->assertSame('Audit Car', $created->record_label);
        $this->assertSame('Updated description', $updated->new_values['description']);
        $this->assertSame('active', $status->old_values['status']);
        $this->assertSame('inactive', $status->new_values['status']);
        $this->assertArrayNotHasKey('password', app(AuditLogger::class)->clean(['password' => 'should-never-be-stored']));

        $car->delete();
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => CarModel::class, 'auditable_id' => $car->id, 'action' => 'delete']);
    }

    public function test_audit_logs_are_permission_gated_read_only_searchable_and_paginated(): void
    {
        $admin = $this->adminWithPermissions('audit_logs.view');
        $noAccess = User::factory()->create(['is_admin' => true]);
        AuditLog::create(['user_id' => $admin->id, 'action' => 'create', 'resource' => 'Car', 'record_label' => 'Searchable Car', 'description' => 'Created car "Searchable Car"', 'new_values' => ['status' => 'active'], 'created_at' => now()]);

        $this->actingAs($noAccess)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($admin)->get('/admin/audit-logs?search=Searchable+Car&per_page=10')->assertOk()->assertSee('Searchable Car');
        $this->get('/admin/audit-logs?user_id='.$admin->id.'&action=create&resource=Car&from='.now()->toDateString().'&to='.now()->toDateString())->assertOk()->assertSee('Searchable Car');
        $this->getJson('/api/admin/audit-logs?search=Searchable+Car&per_page=10')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.per_page', 10);
        $log = AuditLog::firstOrFail();
        try { $log->update(['description' => 'tampered']); $this->fail('Audit log updates must be rejected.'); } catch (\LogicException) {}
        $this->postJson('/api/admin/audit-logs', [])->assertNotFound();
    }

    public function test_successful_admin_login_is_recorded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        event(new \Illuminate\Auth\Events\Login('web', $admin, false));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'login', 'resource' => 'Authentication']);
        event(new \Illuminate\Auth\Events\Logout('web', $admin));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'logout', 'resource' => 'Authentication']);
    }

    public function test_failed_validation_does_not_create_a_success_audit_event(): void
    {
        $admin = $this->adminWithPermissions('cars.create');
        $this->actingAs($admin)->postJson('/api/admin/cars', ['name' => '', 'brand_id' => 999999])->assertUnprocessable();
        $this->assertSame(0, AuditLog::count());
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Audit test', 'slug' => 'audit-test-'.uniqid(), 'is_system' => false]);
        $permissions = collect($slugs)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Audit Logs']));
        $role->permissions()->sync($permissions->modelKeys());
        $user->roles()->attach($role);
        return $user;
    }
}

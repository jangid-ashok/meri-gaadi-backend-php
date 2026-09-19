<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_brand_requests_are_rejected(): void
    {
        $this->getJson('/api/admin/brands')->assertUnauthorized();
    }

    public function test_user_without_brand_permission_is_forbidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->getJson('/api/admin/brands')->assertForbidden();
    }

    public function test_user_with_view_permission_can_list_brands(): void
    {
        $admin = $this->adminWithPermissions('brands.view');
        Brand::create(['name' => 'Hyundai', 'slug' => 'hyundai', 'status' => 'active']);

        $this->actingAs($admin)->getJson('/api/admin/brands')->assertOk()->assertJsonPath('data.data.0.name', 'Hyundai');
    }

    public function test_create_requires_create_permission(): void
    {
        $admin = $this->adminWithPermissions('brands.view');

        $this->actingAs($admin)->postJson('/api/admin/brands', $this->brandData())->assertForbidden();
    }

    public function test_brand_can_be_created_with_generated_slug(): void
    {
        $admin = $this->adminWithPermissions('brands.create');

        $this->actingAs($admin)->postJson('/api/admin/brands', $this->brandData())->assertCreated()->assertJsonPath('data.slug', 'maruti-suzuki');
        $this->assertDatabaseHas('brands', ['name' => 'Maruti Suzuki', 'slug' => 'maruti-suzuki']);
    }

    public function test_duplicate_name_and_invalid_fields_are_rejected(): void
    {
        $admin = $this->adminWithPermissions('brands.create');
        Brand::create(['name' => 'Toyota', 'slug' => 'toyota', 'status' => 'active']);

        $this->actingAs($admin)->postJson('/api/admin/brands', array_merge($this->brandData(), [
            'name' => 'Toyota', 'website' => 'invalid', 'founded_year' => now()->year + 1,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['name', 'website', 'founded_year']);
    }

    public function test_logo_is_stored_and_brand_can_be_updated_and_deleted(): void
    {
        Storage::fake('brand_media');
        $admin = $this->adminWithPermissions('brands.create', 'brands.update', 'brands.delete');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $response = $this->actingAs($admin)->post('/api/admin/brands', array_merge($this->brandData(), ['logo' => UploadedFile::fake()->createWithContent('logo.png', $png)]), ['Accept' => 'application/json']);
        $response->assertCreated();
        $brand = $response->json('data');
        $model = Brand::findOrFail($brand['id']);
        Storage::disk('brand_media')->assertExists($model->logo);

        $this->actingAs($admin)->putJson('/api/admin/brands/'.$model->id, ['name' => 'Maruti Suzuki Updated', 'status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('brands', ['id' => $model->id, 'name' => 'Maruti Suzuki Updated', 'slug' => 'maruti-suzuki-updated']);
        $this->actingAs($admin)->deleteJson('/api/admin/brands/'.$model->id)->assertNoContent();
        $this->assertDatabaseMissing('brands', ['id' => $model->id]);
    }

    public function test_svg_logo_is_accepted_and_edit_replacement_is_multipart_safe(): void
    {
        Storage::fake('brand_media');
        $admin = $this->adminWithPermissions('brands.create', 'brands.update');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="red"/></svg>';

        $created = $this->actingAs($admin)->post('/api/admin/brands', array_merge($this->brandData(), [
            'name' => 'SVG Motors',
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', $svg),
        ]), ['Accept' => 'application/json']);
        $created->assertCreated();
        $brand = Brand::findOrFail($created->json('data.id'));
        $oldLogo = $brand->logo;

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $updated = $this->actingAs($admin)->post('/api/admin/brands/'.$brand->id, [
            '_method' => 'PUT',
            'name' => 'SVG Motors',
            'status' => 'active',
            'logo' => UploadedFile::fake()->createWithContent('replacement.png', $png),
        ], ['Accept' => 'application/json']);
        $updated->assertOk();
        $brand->refresh();

        $this->assertNotSame($oldLogo, $brand->logo);
        Storage::disk('brand_media')->assertMissing($oldLogo);
        Storage::disk('brand_media')->assertExists($brand->logo);
    }

    public function test_search_status_filter_and_pagination_work(): void
    {
        $admin = $this->adminWithPermissions('brands.view');
        foreach (range(1, 16) as $number) {
            Brand::create(['name' => 'Brand '.$number, 'slug' => 'brand-'.$number, 'status' => $number === 1 ? 'inactive' : 'active']);
        }

        $this->actingAs($admin)->getJson('/api/admin/brands?search=Brand%201&status=inactive')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/brands')->assertOk()->assertJsonPath('data.per_page', 15)->assertJsonPath('data.last_page', 2);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Brand tester '.uniqid(), 'slug' => 'brand-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'Brands']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }

    private function brandData(): array
    {
        return ['name' => 'Maruti Suzuki', 'description' => 'Indian automotive manufacturer.', 'country' => 'India', 'website' => 'https://www.marutisuzuki.com', 'founded_year' => 1981, 'status' => 'active', 'sort_order' => 1];
    }
}
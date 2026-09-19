<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_and_unauthorized_requests_are_rejected(): void
    {
        $this->getJson('/api/admin/cars')->assertUnauthorized();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->getJson('/api/admin/cars')->assertForbidden();
    }

    public function test_car_can_be_created_with_generated_brand_scoped_slug(): void
    {
        $admin = $this->adminWithPermissions('cars.create'); $brand = Brand::create(['name' => 'Hyundai', 'slug' => 'hyundai', 'status' => 'active']);
        $this->actingAs($admin)->postJson('/api/admin/cars', $this->data($brand))->assertCreated()->assertJsonPath('data.slug', 'creta');
        $this->assertDatabaseHas('car_models', ['brand_id' => $brand->id, 'slug' => 'creta']);
    }

    public function test_invalid_brand_duplicate_same_brand_and_years_are_rejected(): void
    {
        $admin = $this->adminWithPermissions('cars.create'); $brand = Brand::create(['name' => 'Tata Motors', 'slug' => 'tata-motors', 'status' => 'active']);
        CarModel::create(['brand_id' => $brand->id, 'name' => 'Nexon', 'slug' => 'nexon', 'status' => 'active']);
        $this->actingAs($admin)->postJson('/api/admin/cars', array_merge($this->data($brand), ['brand_id' => 99999, 'launch_year' => 2020, 'discontinued_year' => 2019]))->assertUnprocessable()->assertJsonValidationErrors(['brand_id', 'discontinued_year']);
        $this->actingAs($admin)->postJson('/api/admin/cars', array_merge($this->data($brand), ['name' => 'Nexon']))->assertCreated()->assertJsonPath('data.slug', 'nexon-2');
    }

    public function test_same_model_name_is_allowed_under_another_brand(): void
    {
        $admin = $this->adminWithPermissions('cars.create'); $one = Brand::create(['name' => 'Brand One', 'slug' => 'brand-one', 'status' => 'active']); $two = Brand::create(['name' => 'Brand Two', 'slug' => 'brand-two', 'status' => 'active']);
        $this->actingAs($admin)->postJson('/api/admin/cars', $this->data($one))->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/cars', $this->data($two))->assertCreated()->assertJsonPath('data.slug', 'creta');
    }

    public function test_car_can_be_updated_brand_changed_deleted_and_thumbnail_replaced(): void
    {
        Storage::fake('car_media'); $admin = $this->adminWithPermissions('cars.create', 'cars.update', 'cars.delete'); $one = Brand::create(['name' => 'One', 'slug' => 'one', 'status' => 'active']); $two = Brand::create(['name' => 'Two', 'slug' => 'two', 'status' => 'active']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $created = $this->actingAs($admin)->post('/api/admin/cars', array_merge($this->data($one), ['thumbnail' => UploadedFile::fake()->createWithContent('a.png', $png)]), ['Accept' => 'application/json']); $created->assertCreated();
        $car = CarModel::findOrFail($created->json('data.id')); $old = $car->thumbnail;
        $this->actingAs($admin)->post('/api/admin/cars/'.$car->id, ['_method' => 'PUT', 'brand_id' => $two->id, 'name' => 'Creta', 'status' => 'inactive', 'thumbnail' => UploadedFile::fake()->createWithContent('b.png', $png)], ['Accept' => 'application/json'])->assertOk();
        $car->refresh(); $this->assertSame($two->id, $car->brand_id); Storage::disk('car_media')->assertMissing($old); Storage::disk('car_media')->assertExists($car->thumbnail);
        $this->actingAs($admin)->deleteJson('/api/admin/cars/'.$car->id)->assertNoContent(); $this->assertDatabaseMissing('car_models', ['id' => $car->id]);
    }

    public function test_search_filters_and_pagination_work(): void
    {
        $admin = $this->adminWithPermissions('cars.view'); $brand = Brand::create(['name' => 'Search Brand', 'slug' => 'search-brand', 'status' => 'active']);
        foreach (range(1, 16) as $number) CarModel::create(['brand_id' => $brand->id, 'name' => 'Model '.$number, 'slug' => 'model-'.$number, 'body_type' => $number === 1 ? 'suv' : 'sedan', 'status' => $number === 2 ? 'inactive' : 'active']);
        $this->actingAs($admin)->getJson('/api/admin/cars?brand_id='.$brand->id.'&body_type=suv')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/cars?search=Model%202&status=inactive')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/cars')->assertOk()->assertJsonPath('data.last_page', 2);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]); $role = Role::create(['name' => 'Car tester '.uniqid(), 'slug' => 'car-tester-'.uniqid()]);
        foreach ($slugs as $slug) { $permission = Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'Cars']); $role->permissions()->attach($permission); }
        $admin->roles()->attach($role); return $admin;
    }

    private function data(Brand $brand): array
    {
        return ['brand_id' => $brand->id, 'name' => 'Creta', 'short_description' => 'Compact SUV', 'description' => 'A model description.', 'launch_year' => 2020, 'body_type' => 'suv', 'fuel_types' => ['petrol'], 'transmission_types' => ['automatic'], 'status' => 'active', 'sort_order' => 1];
    }
}
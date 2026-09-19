<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_and_unauthorized_requests_are_rejected(): void
    {
        $this->getJson('/api/admin/variants')->assertUnauthorized();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->getJson('/api/admin/variants')->assertForbidden();
    }

    public function test_valid_variant_can_be_created_and_full_name_is_returned(): void
    {
        $admin = $this->adminWithPermissions('variants.create');
        [$brand, $model] = $this->model('Hyundai', 'Creta');

        $response = $this->actingAs($admin)->postJson('/api/admin/variants', $this->data($brand, $model, ['name' => 'SX(O)', 'variant_code' => 'CRETA-SXO']));

        $response->assertCreated()->assertJsonPath('data.slug', 'sx-o')->assertJsonPath('data.full_name', 'Hyundai Creta SX(O)');
        $this->assertDatabaseHas('variants', ['car_model_id' => $model->id, 'name' => 'SX(O)']);
    }

    public function test_invalid_and_mismatched_parent_models_are_rejected(): void
    {
        $admin = $this->adminWithPermissions('variants.create');
        [$brand, $model] = $this->model('Hyundai', 'Creta');
        [$otherBrand] = $this->model('Tata Motors', 'Nexon');

        $this->actingAs($admin)->postJson('/api/admin/variants', $this->data($brand, $model, ['car_model_id' => 99999]))->assertUnprocessable()->assertJsonValidationErrors(['car_model_id']);
        $this->actingAs($admin)->postJson('/api/admin/variants', $this->data($otherBrand, $model))->assertUnprocessable()->assertJsonValidationErrors(['car_model_id']);
    }

    public function test_duplicate_name_and_code_are_rejected_but_same_name_works_on_another_model(): void
    {
        $admin = $this->adminWithPermissions('variants.create');
        [$brand, $model] = $this->model('Hyundai', 'Creta');
        [$sameBrand, $otherModel] = $this->model('Hyundai', 'i20');
        $data = $this->data($brand, $model, ['name' => 'S', 'variant_code' => 'S-01']);

        $this->actingAs($admin)->postJson('/api/admin/variants', $data)->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/variants', $data)->assertUnprocessable()->assertJsonValidationErrors(['name', 'variant_code']);
        $this->actingAs($admin)->postJson('/api/admin/variants', $this->data($sameBrand, $otherModel, ['name' => 'S', 'variant_code' => 'S-01']))->assertCreated();
    }

    public function test_variant_can_be_updated_to_another_model_and_deleted(): void
    {
        $admin = $this->adminWithPermissions('variants.create', 'variants.update', 'variants.delete');
        [$brand, $model] = $this->model('Hyundai', 'Creta');
        [$otherBrand, $otherModel] = $this->model('Tata Motors', 'Nexon');
        $variant = Variant::create(['car_model_id' => $model->id, 'name' => 'E', 'slug' => 'e', 'status' => 'active']);

        $this->actingAs($admin)->putJson('/api/admin/variants/'.$variant->id, $this->data($otherBrand, $otherModel, ['name' => 'Pure']))->assertOk()->assertJsonPath('data.car_model_id', $otherModel->id);
        $this->actingAs($admin)->deleteJson('/api/admin/variants/'.$variant->id)->assertNoContent();
        $this->assertDatabaseMissing('variants', ['id' => $variant->id]);
    }

    public function test_search_filters_pagination_and_dependent_models_work(): void
    {
        $admin = $this->adminWithPermissions('variants.view');
        [$brand, $model] = $this->model('Hyundai', 'Creta');
        foreach (range(1, 16) as $number) Variant::create(['car_model_id' => $model->id, 'name' => 'Trim '.$number, 'slug' => 'trim-'.$number, 'variant_code' => 'T'.$number, 'status' => $number === 1 ? 'inactive' : 'active']);

        $this->actingAs($admin)->getJson('/api/admin/variants?search=Trim%202&brand_id='.$brand->id.'&car_model_id='.$model->id.'&status=inactive')->assertOk()->assertJsonPath('data.total', 1);
        $this->actingAs($admin)->getJson('/api/admin/variants')->assertOk()->assertJsonPath('data.last_page', 2);
        $this->actingAs($admin)->getJson('/api/admin/variants/models?brand_id='.$brand->id)->assertOk()->assertJsonPath('data.0.id', $model->id);
    }

    private function model(string $brandName, string $modelName): array
    {
        $brand = Brand::firstOrCreate(['name' => $brandName], ['slug' => str($brandName)->slug(), 'status' => 'active']);
        $model = CarModel::create(['brand_id' => $brand->id, 'name' => $modelName, 'slug' => str($modelName)->slug(), 'status' => 'active']);
        return [$brand, $model];
    }

    private function data(Brand $brand, CarModel $model, array $overrides = []): array
    {
        return array_merge(['brand_id' => $brand->id, 'car_model_id' => $model->id, 'name' => 'E', 'description' => 'Entry trim', 'status' => 'active', 'sort_order' => 1], $overrides);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Variant tester '.uniqid(), 'slug' => 'variant-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'Variants']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);
        return $admin;
    }
}
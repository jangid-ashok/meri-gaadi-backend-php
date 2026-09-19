<?php

namespace Tests\Feature\Admin;

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_categories_require_permission_and_support_crud(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->getJson('/api/admin/feature-categories')->assertForbidden();

        $admin = $this->adminWithPermissions('feature_categories.create', 'feature_categories.view', 'feature_categories.update', 'feature_categories.delete');
        $response = $this->actingAs($admin)->postJson('/api/admin/feature-categories', [
            'name' => 'Comfort',
            'description' => 'Comfort features',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'comfort');
        $this->assertDatabaseHas('feature_categories', ['name' => 'Comfort', 'slug' => 'comfort']);

        $this->actingAs($admin)->postJson('/api/admin/feature-categories', ['name' => 'Comfort', 'status' => 'active'])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $category = FeatureCategory::first();
        $this->actingAs($admin)->putJson('/api/admin/feature-categories/'.$category->id, ['name' => 'Comfort Updated', 'status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('feature_categories', ['id' => $category->id, 'name' => 'Comfort Updated', 'slug' => 'comfort-updated']);

        $this->actingAs($admin)->deleteJson('/api/admin/feature-categories/'.$category->id)->assertNoContent();
        $this->assertSoftDeleted('feature_categories', ['id' => $category->id]);
    }

    public function test_features_are_tied_to_categories_and_validate_uniqueness(): void
    {
        $admin = $this->adminWithPermissions('features.create');
        $category = FeatureCategory::create(['name' => 'Comfort', 'slug' => 'comfort', 'status' => 'active']);

        $response = $this->actingAs($admin)->postJson('/api/admin/features', [
            'category_id' => $category->id,
            'name' => 'Sunroof',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $response->assertCreated()->assertJsonPath('data.slug', 'sunroof');

        $this->actingAs($admin)->postJson('/api/admin/features', [
            'category_id' => $category->id,
            'name' => 'Sunroof',
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $this->actingAs($admin)->postJson('/api/admin/features', [
            'category_id' => $category->id,
            'name' => 'Automatic Climate Control',
            'status' => 'active',
        ])->assertCreated();
    }

    public function test_variant_features_validate_values_and_reject_duplicates(): void
    {
        $admin = $this->adminWithPermissions('features.view', 'features.create', 'features.update', 'features.delete');
        $brand = \App\Models\Brand::create(['name' => 'Hyundai', 'slug' => 'hyundai', 'status' => 'active']);
        $model = \App\Models\CarModel::create(['brand_id' => $brand->id, 'name' => 'Creta', 'slug' => 'creta', 'status' => 'active']);
        $variant = Variant::create(['car_model_id' => $model->id, 'name' => 'SX', 'slug' => 'sx', 'status' => 'active']);

        $category = FeatureCategory::create(['name' => 'Comfort', 'slug' => 'comfort', 'status' => 'active']);
        $sunroof = Feature::create(['category_id' => $category->id, 'name' => 'Sunroof', 'slug' => 'sunroof', 'status' => 'active']);
        $climate = Feature::create(['category_id' => $category->id, 'name' => 'Automatic Climate Control', 'slug' => 'automatic-climate-control', 'status' => 'active']);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/features', [[
            'feature_id' => $sunroof->id,
            'value' => 'true',
        ], [
            'feature_id' => $climate->id,
            'value' => 'false',
        ]])->assertOk();

        $this->assertDatabaseHas('variant_features', ['variant_id' => $variant->id, 'feature_id' => $sunroof->id, 'value' => 'true']);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/features', [[
            'feature_id' => $sunroof->id,
            'value' => 'maybe',
        ]])->assertUnprocessable()->assertJsonValidationErrors(['0.value']);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/features', [[
            'feature_id' => $sunroof->id,
            'value' => 'true',
        ], [
            'feature_id' => $sunroof->id,
            'value' => 'false',
        ]])->assertUnprocessable()->assertJsonValidationErrors(['1.feature_id']);

        $feature = VariantFeature::where('variant_id', $variant->id)->first();
        $this->actingAs($admin)->putJson('/api/admin/variants/'.$variant->id.'/features/'.$feature->id, ['value' => 'false'])->assertOk();
        $this->assertDatabaseHas('variant_features', ['id' => $feature->id, 'value' => 'false']);

        $this->actingAs($admin)->deleteJson('/api/admin/variants/'.$variant->id.'/features/'.$feature->id)->assertNoContent();
        $this->assertDatabaseMissing('variant_features', ['id' => $feature->id]);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Features tester '.uniqid(), 'slug' => 'features-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Features']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpecificationCategory;
use App\Models\SpecificationDefinition;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_require_permission_and_support_crud(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->getJson('/api/admin/specification-categories')->assertForbidden();

        $admin = $this->adminWithPermissions('specification_categories.create', 'specification_categories.view', 'specification_categories.update', 'specification_categories.delete');
        $response = $this->actingAs($admin)->postJson('/api/admin/specification-categories', [
            'name' => 'Engine',
            'description' => 'Engine details',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $response->assertCreated()->assertJsonPath('data.slug', 'engine');
        $this->assertDatabaseHas('specification_categories', ['name' => 'Engine', 'slug' => 'engine']);

        $this->actingAs($admin)->postJson('/api/admin/specification-categories', ['name' => 'Engine', 'status' => 'active'])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $category = SpecificationCategory::first();
        $this->actingAs($admin)->putJson('/api/admin/specification-categories/'.$category->id, ['name' => 'Engine Updated', 'status' => 'inactive'])->assertOk();
        $this->assertDatabaseHas('specification_categories', ['id' => $category->id, 'name' => 'Engine Updated', 'slug' => 'engine-updated']);

        $this->actingAs($admin)->deleteJson('/api/admin/specification-categories/'.$category->id)->assertNoContent();
        $this->assertSoftDeleted('specification_categories', ['id' => $category->id]);
    }

    public function test_definitions_are_tied_to_categories_and_validate_data_types(): void
    {
        $admin = $this->adminWithPermissions('specification_definitions.create');
        $category = SpecificationCategory::create(['name' => 'Engine', 'slug' => 'engine', 'status' => 'active']);

        $response = $this->actingAs($admin)->postJson('/api/admin/specification-definitions', [
            'category_id' => $category->id,
            'name' => 'Engine Displacement',
            'data_type' => 'number',
            'unit' => 'cc',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        $response->assertCreated()->assertJsonPath('data.slug', 'engine-displacement');

        $this->actingAs($admin)->postJson('/api/admin/specification-definitions', [
            'category_id' => $category->id,
            'name' => 'Engine Displacement',
            'data_type' => 'number',
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $this->actingAs($admin)->postJson('/api/admin/specification-definitions', [
            'category_id' => $category->id,
            'name' => 'Transmission Type',
            'data_type' => 'select',
            'options' => ['Manual', 'Automatic'],
            'status' => 'active',
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/admin/specification-definitions', [
            'category_id' => $category->id,
            'name' => 'Oops Type',
            'data_type' => 'select',
            'options' => ['Manual', 999],
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['options.1']);
    }

    public function test_variant_specifications_validate_values_and_reject_duplicates(): void
    {
        $admin = $this->adminWithPermissions('specifications.view', 'specifications.create', 'specifications.update', 'specifications.delete');
        $brand = Brand::create(['name' => 'Hyundai', 'slug' => 'hyundai', 'status' => 'active']);
        $model = CarModel::create(['brand_id' => $brand->id, 'name' => 'Creta', 'slug' => 'creta', 'status' => 'active']);
        $variant = Variant::create(['car_model_id' => $model->id, 'name' => 'SX', 'slug' => 'sx', 'status' => 'active']);

        $category = SpecificationCategory::create(['name' => 'Engine', 'slug' => 'engine', 'status' => 'active']);
        $numberDefinition = SpecificationDefinition::create([
            'category_id' => $category->id,
            'name' => 'Cylinders',
            'slug' => 'cylinders',
            'data_type' => 'number',
            'status' => 'active',
        ]);
        $decimalDefinition = SpecificationDefinition::create([
            'category_id' => $category->id,
            'name' => 'Displacement',
            'slug' => 'displacement',
            'data_type' => 'decimal',
            'unit' => 'cc',
            'status' => 'active',
        ]);
        $boolDefinition = SpecificationDefinition::create([
            'category_id' => $category->id,
            'name' => 'ABS',
            'slug' => 'abs',
            'data_type' => 'boolean',
            'status' => 'active',
        ]);
        $selectDef = SpecificationDefinition::create([
            'category_id' => $category->id,
            'name' => 'Transmission Type',
            'slug' => 'transmission-type',
            'data_type' => 'select',
            'options' => ['Manual', 'Automatic', 'DCT'],
            'status' => 'active',
        ]);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/specifications', [[
            'specification_definition_id' => $numberDefinition->id,
            'value' => '4',
        ], [
            'specification_definition_id' => $decimalDefinition->id,
            'value' => '1497.0',
        ], [
            'specification_definition_id' => $boolDefinition->id,
            'value' => 'true',
        ], [
            'specification_definition_id' => $selectDef->id,
            'value' => 'DCT',
        ]])->assertOk();

        $this->assertDatabaseHas('variant_specifications', ['variant_id' => $variant->id, 'specification_definition_id' => $numberDefinition->id, 'value' => '4']);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/specifications', [[
            'specification_definition_id' => $numberDefinition->id,
            'value' => 'not-a-number',
        ]])->assertUnprocessable()->assertJsonValidationErrors(['0.value']);

        $this->actingAs($admin)->postJson('/api/admin/variants/'.$variant->id.'/specifications', [[
            'specification_definition_id' => $numberDefinition->id,
            'value' => '4',
        ], [
            'specification_definition_id' => $numberDefinition->id,
            'value' => '6',
        ]])->assertUnprocessable()->assertJsonValidationErrors(['1.specification_definition_id']);

        $spec = VariantSpecification::where('variant_id', $variant->id)->first();
        $this->actingAs($admin)->putJson('/api/admin/variants/'.$variant->id.'/specifications/'.$spec->id, ['value' => '6'])->assertOk();
        $this->assertDatabaseHas('variant_specifications', ['id' => $spec->id, 'value' => '6']);

        $this->actingAs($admin)->deleteJson('/api/admin/variants/'.$variant->id.'/specifications/'.$spec->id)->assertNoContent();
        $this->assertDatabaseMissing('variant_specifications', ['id' => $spec->id]);
    }

    private function adminWithPermissions(string ...$slugs): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = Role::create(['name' => 'Specifications tester '.uniqid(), 'slug' => 'specifications-tester-'.uniqid()]);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => 'Specifications']);
            $role->permissions()->attach($permission);
        }
        $admin->roles()->attach($role);

        return $admin;
    }
}

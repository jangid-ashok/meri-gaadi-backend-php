<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard'],
            ['name' => 'View roles', 'slug' => 'roles.view', 'module' => 'Roles'],
            ['name' => 'Create roles', 'slug' => 'roles.create', 'module' => 'Roles'],
            ['name' => 'Update roles', 'slug' => 'roles.update', 'module' => 'Roles'],
            ['name' => 'Delete roles', 'slug' => 'roles.delete', 'module' => 'Roles'],
            ['name' => 'Assign roles', 'slug' => 'roles.assign', 'module' => 'Roles'],
            ['name' => 'View brands', 'slug' => 'brands.view', 'module' => 'Brands'],
            ['name' => 'Create brands', 'slug' => 'brands.create', 'module' => 'Brands'],
            ['name' => 'Update brands', 'slug' => 'brands.update', 'module' => 'Brands'],
            ['name' => 'Delete brands', 'slug' => 'brands.delete', 'module' => 'Brands'],
            ['name' => 'View cars', 'slug' => 'cars.view', 'module' => 'Cars'],
            ['name' => 'Create cars', 'slug' => 'cars.create', 'module' => 'Cars'],
            ['name' => 'Update cars', 'slug' => 'cars.update', 'module' => 'Cars'],
            ['name' => 'Delete cars', 'slug' => 'cars.delete', 'module' => 'Cars'],
            ['name' => 'View variants', 'slug' => 'variants.view', 'module' => 'Variants'],
            ['name' => 'Create variants', 'slug' => 'variants.create', 'module' => 'Variants'],
            ['name' => 'Update variants', 'slug' => 'variants.update', 'module' => 'Variants'],
            ['name' => 'Delete variants', 'slug' => 'variants.delete', 'module' => 'Variants'],
            ['name' => 'View specification categories', 'slug' => 'specification_categories.view', 'module' => 'Specifications'],
            ['name' => 'Create specification categories', 'slug' => 'specification_categories.create', 'module' => 'Specifications'],
            ['name' => 'Update specification categories', 'slug' => 'specification_categories.update', 'module' => 'Specifications'],
            ['name' => 'Delete specification categories', 'slug' => 'specification_categories.delete', 'module' => 'Specifications'],
            ['name' => 'View specification definitions', 'slug' => 'specification_definitions.view', 'module' => 'Specifications'],
            ['name' => 'Create specification definitions', 'slug' => 'specification_definitions.create', 'module' => 'Specifications'],
            ['name' => 'Update specification definitions', 'slug' => 'specification_definitions.update', 'module' => 'Specifications'],
            ['name' => 'Delete specification definitions', 'slug' => 'specification_definitions.delete', 'module' => 'Specifications'],
            ['name' => 'View features categories', 'slug' => 'feature_categories.view', 'module' => 'Features'],
            ['name' => 'Create features categories', 'slug' => 'feature_categories.create', 'module' => 'Features'],
            ['name' => 'Update features categories', 'slug' => 'feature_categories.update', 'module' => 'Features'],
            ['name' => 'Delete features categories', 'slug' => 'feature_categories.delete', 'module' => 'Features'],
            ['name' => 'View features', 'slug' => 'features.view', 'module' => 'Features'],
            ['name' => 'Create features', 'slug' => 'features.create', 'module' => 'Features'],
            ['name' => 'Update features', 'slug' => 'features.update', 'module' => 'Features'],
            ['name' => 'Delete features', 'slug' => 'features.delete', 'module' => 'Features'],
            ['name' => 'View specifications', 'slug' => 'specifications.view', 'module' => 'Specifications'],
            ['name' => 'Create specifications', 'slug' => 'specifications.create', 'module' => 'Specifications'],
            ['name' => 'Update specifications', 'slug' => 'specifications.update', 'module' => 'Specifications'],
            ['name' => 'Delete specifications', 'slug' => 'specifications.delete', 'module' => 'Specifications'],
        ];

        foreach ($definitions as $definition) {
            Permission::updateOrCreate(['slug' => $definition['slug']], $definition);
        }

        $permissions = Permission::whereIn('slug', array_column($definitions, 'slug'))->get();
        $all = $permissions->modelKeys();

        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full administrative access.', 'is_system' => true]
        );
        $superAdmin->permissions()->sync($all);

        $editor = Role::updateOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editor', 'description' => 'Content and role overview access.', 'is_system' => true]
        );
        $editor->permissions()->sync($permissions->whereIn('slug', [
            'dashboard.view', 'roles.view', 'brands.view', 'brands.create', 'brands.update', 'cars.view', 'cars.create', 'cars.update', 'variants.view', 'variants.create', 'variants.update',
            'specification_categories.view', 'specification_categories.create', 'specification_categories.update', 'specification_definitions.view', 'specification_definitions.create', 'specification_definitions.update', 'feature_categories.view', 'feature_categories.create', 'feature_categories.update', 'features.view', 'features.create', 'features.update', 'specifications.view', 'specifications.create', 'specifications.update',
        ])->modelKeys());

        $carManager = Role::updateOrCreate(
            ['slug' => 'car-manager'],
            ['name' => 'Car Manager', 'description' => 'Automotive content access.', 'is_system' => true]
        );
        $carManager->permissions()->sync($permissions->whereIn('slug', [
            'dashboard.view', 'brands.view', 'brands.create', 'brands.update', 'cars.view', 'cars.create', 'cars.update', 'cars.delete', 'variants.view', 'variants.create', 'variants.update', 'variants.delete',
            'specification_categories.view', 'specification_categories.create', 'specification_categories.update', 'specification_definitions.view', 'specification_definitions.create', 'specification_definitions.update', 'feature_categories.view', 'feature_categories.create', 'feature_categories.update', 'feature_categories.delete', 'features.view', 'features.create', 'features.update', 'features.delete', 'specifications.view', 'specifications.create', 'specifications.update', 'specifications.delete',
        ])->modelKeys());

        $admin = User::where('is_admin', true)->oldest('id')->first();
        if ($admin) {
            $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }
}
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
            ['name' => 'View audit logs', 'slug' => 'audit_logs.view', 'module' => 'Audit Logs'],
            ['name' => 'View SEO metadata', 'slug' => 'seo.view', 'module' => 'SEO'],
            ['name' => 'Create SEO metadata', 'slug' => 'seo.create', 'module' => 'SEO'],
            ['name' => 'Update SEO metadata', 'slug' => 'seo.update', 'module' => 'SEO'],
            ['name' => 'Delete SEO metadata', 'slug' => 'seo.delete', 'module' => 'SEO'],
            ['name' => 'View featured content', 'slug' => 'featured_content.view', 'module' => 'Featured Content'],
            ['name' => 'Create featured content', 'slug' => 'featured_content.create', 'module' => 'Featured Content'],
            ['name' => 'Update featured content', 'slug' => 'featured_content.update', 'module' => 'Featured Content'],
            ['name' => 'Delete featured content', 'slug' => 'featured_content.delete', 'module' => 'Featured Content'],
            ['name' => 'View homepage configuration', 'slug' => 'homepage.view', 'module' => 'Homepage'],
            ['name' => 'Update homepage configuration', 'slug' => 'homepage.update', 'module' => 'Homepage'],
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard'],
            ['name' => 'View roles', 'slug' => 'roles.view', 'module' => 'Roles'],
            ['name' => 'Create roles', 'slug' => 'roles.create', 'module' => 'Roles'],
            ['name' => 'Update roles', 'slug' => 'roles.update', 'module' => 'Roles'],
            ['name' => 'Delete roles', 'slug' => 'roles.delete', 'module' => 'Roles'],
            ['name' => 'Assign roles', 'slug' => 'roles.assign', 'module' => 'Roles'],
            ['name' => 'View users', 'slug' => 'users.view', 'module' => 'Users'],
            ['name' => 'Create users', 'slug' => 'users.create', 'module' => 'Users'],
            ['name' => 'Update users', 'slug' => 'users.update', 'module' => 'Users'],
            ['name' => 'Delete users', 'slug' => 'users.delete', 'module' => 'Users'],
            ['name' => 'Change user status', 'slug' => 'users.status', 'module' => 'Users'],
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
            ['name' => 'View categories', 'slug' => 'categories.view', 'module' => 'Categories'],
            ['name' => 'Create categories', 'slug' => 'categories.create', 'module' => 'Categories'],
            ['name' => 'Update categories', 'slug' => 'categories.update', 'module' => 'Categories'],
            ['name' => 'Delete categories', 'slug' => 'categories.delete', 'module' => 'Categories'],
            ['name' => 'View tags', 'slug' => 'tags.view', 'module' => 'Tags'],
            ['name' => 'Create tags', 'slug' => 'tags.create', 'module' => 'Tags'],
            ['name' => 'Update tags', 'slug' => 'tags.update', 'module' => 'Tags'],
            ['name' => 'Delete tags', 'slug' => 'tags.delete', 'module' => 'Tags'],
            ['name' => 'View settings', 'slug' => 'settings.view', 'module' => 'Settings'],
            ['name' => 'Update settings', 'slug' => 'settings.update', 'module' => 'Settings'],
            ['name' => 'View FAQs', 'slug' => 'faqs.view', 'module' => 'FAQs'],
            ['name' => 'Create FAQs', 'slug' => 'faqs.create', 'module' => 'FAQs'],
            ['name' => 'Update FAQs', 'slug' => 'faqs.update', 'module' => 'FAQs'],
            ['name' => 'Delete FAQs', 'slug' => 'faqs.delete', 'module' => 'FAQs'],
            ['name' => 'View articles', 'slug' => 'articles.view', 'module' => 'Articles'],
            ['name' => 'View media library', 'slug' => 'media.view', 'module' => 'Media'],
            ['name' => 'Upload media', 'slug' => 'media.upload', 'module' => 'Media'],
            ['name' => 'Update media metadata', 'slug' => 'media.update', 'module' => 'Media'],
            ['name' => 'Delete media', 'slug' => 'media.delete', 'module' => 'Media'],
            ['name' => 'Create articles', 'slug' => 'articles.create', 'module' => 'Articles'],
            ['name' => 'Update articles', 'slug' => 'articles.update', 'module' => 'Articles'],
            ['name' => 'Delete articles', 'slug' => 'articles.delete', 'module' => 'Articles'],
            ['name' => 'Publish articles', 'slug' => 'articles.publish', 'module' => 'Articles'],
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
            'categories.view', 'categories.create', 'categories.update',
            'tags.view', 'tags.create', 'tags.update',
            'settings.view',
            'articles.view', 'articles.create', 'articles.update',
            'homepage.view', 'homepage.update', 'featured_content.view', 'featured_content.create', 'featured_content.update',
            'seo.view', 'seo.create', 'seo.update',
            'media.view', 'media.upload',
        ])->modelKeys());

        $carManager = Role::updateOrCreate(
            ['slug' => 'car-manager'],
            ['name' => 'Car Manager', 'description' => 'Automotive content access.', 'is_system' => true]
        );
        $carManager->permissions()->sync($permissions->whereIn('slug', [
            'dashboard.view', 'brands.view', 'brands.create', 'brands.update', 'cars.view', 'cars.create', 'cars.update', 'cars.delete', 'variants.view', 'variants.create', 'variants.update', 'variants.delete',
            'specification_categories.view', 'specification_categories.create', 'specification_categories.update', 'specification_definitions.view', 'specification_definitions.create', 'specification_definitions.update', 'feature_categories.view', 'feature_categories.create', 'feature_categories.update', 'feature_categories.delete', 'features.view', 'features.create', 'features.update', 'features.delete', 'specifications.view', 'specifications.create', 'specifications.update', 'specifications.delete',
            'categories.view', 'categories.create', 'categories.update',
            'tags.view', 'tags.create', 'tags.update',
            'settings.view',
            'articles.view', 'articles.create', 'articles.update',
            'media.view', 'media.upload',
        ])->modelKeys());

        $admin = User::where('is_admin', true)->oldest('id')->first();
        if ($admin) {
            $admin->roles()->syncWithoutDetaching([$superAdmin->id]);
        }
    }
}

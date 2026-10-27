<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $automotive = Category::updateOrCreate(
            ['slug' => 'automotive'],
            ['name' => 'Automotive', 'description' => 'Automotive categories.', 'category_type' => 'automotive', 'status' => 'active', 'sort_order' => 1, 'parent_id' => null]
        );

        foreach ([
            ['name' => 'Hatchbacks', 'slug' => 'hatchbacks', 'sort_order' => 1],
            ['name' => 'Sedans', 'slug' => 'sedans', 'sort_order' => 2],
            ['name' => 'SUVs', 'slug' => 'suvs', 'sort_order' => 3],
            ['name' => 'Electric Cars', 'slug' => 'electric-cars', 'sort_order' => 4],
        ] as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['category_type' => 'automotive', 'status' => 'active', 'parent_id' => $automotive->id])
            );
        }

        foreach ([
            ['name' => 'Car Reviews', 'slug' => 'car-reviews', 'sort_order' => 1],
            ['name' => 'Buying Guides', 'slug' => 'buying-guides', 'sort_order' => 2],
            ['name' => 'Car News', 'slug' => 'car-news', 'sort_order' => 3],
            ['name' => 'Maintenance', 'slug' => 'maintenance', 'sort_order' => 4],
        ] as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['category_type' => 'content', 'status' => 'active', 'parent_id' => null])
            );
        }
    }
}
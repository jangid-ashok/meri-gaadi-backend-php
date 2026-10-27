<?php

namespace Database\Seeders;

use App\Models\Admin\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'SUV', 'slug' => 'suv', 'sort_order' => 1],
            ['name' => 'Sedan', 'slug' => 'sedan', 'sort_order' => 2],
            ['name' => 'Electric', 'slug' => 'electric', 'sort_order' => 3],
            ['name' => 'EV', 'slug' => 'ev', 'sort_order' => 4],
            ['name' => 'Petrol', 'slug' => 'petrol', 'sort_order' => 5],
            ['name' => 'Diesel', 'slug' => 'diesel', 'sort_order' => 6],
            ['name' => 'Automatic', 'slug' => 'automatic', 'sort_order' => 7],
            ['name' => 'Manual', 'slug' => 'manual', 'sort_order' => 8],
            ['name' => 'Luxury', 'slug' => 'luxury', 'sort_order' => 9],
            ['name' => 'Car Review', 'slug' => 'car-review', 'sort_order' => 10],
            ['name' => 'Buying Guide', 'slug' => 'buying-guide', 'sort_order' => 11],
            ['name' => 'Maintenance', 'slug' => 'maintenance', 'sort_order' => 12],
        ] as $tag) {
            Tag::updateOrCreate(['slug' => $tag['slug']], array_merge($tag, [
                'status' => 'active',
                'description' => null,
            ]));
        }
    }
}
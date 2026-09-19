<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Maruti Suzuki', 'country' => 'India'],
            ['name' => 'Hyundai', 'country' => 'South Korea'],
            ['name' => 'Tata Motors', 'country' => 'India'],
            ['name' => 'Mahindra', 'country' => 'India'],
            ['name' => 'Toyota', 'country' => 'Japan'],
        ] as $brand) {
            Brand::updateOrCreate(['name' => $brand['name']], [
                ...$brand,
                'slug' => str($brand['name'])->slug(),
                'status' => 'active',
                'sort_order' => 0,
            ]);
        }
    }
}
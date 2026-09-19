<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Database\Seeder;

class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            ['brand' => 'Maruti Suzuki', 'name' => 'Swift', 'body_type' => 'hatchback'],
            ['brand' => 'Maruti Suzuki', 'name' => 'Baleno', 'body_type' => 'hatchback'],
            ['brand' => 'Hyundai', 'name' => 'Creta', 'body_type' => 'suv'],
            ['brand' => 'Hyundai', 'name' => 'i20', 'body_type' => 'hatchback'],
            ['brand' => 'Tata Motors', 'name' => 'Nexon', 'body_type' => 'suv'],
            ['brand' => 'Mahindra', 'name' => 'Scorpio-N', 'body_type' => 'suv'],
        ];

        foreach ($models as $data) {
            $brand = Brand::where('name', $data['brand'])->first();
            if (! $brand) continue;
            CarModel::updateOrCreate(
                ['brand_id' => $brand->id, 'slug' => str($data['name'])->slug()],
                ['name' => $data['name'], 'body_type' => $data['body_type'], 'status' => 'active', 'sort_order' => 0]
            );
        }
    }
}
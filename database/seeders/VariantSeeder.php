<?php

namespace Database\Seeders;

use App\Models\CarModel;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class VariantSeeder extends Seeder
{
    public function run(): void
    {
        $variants = [
            'Hyundai|Creta' => ['E', 'S', 'SX', 'SX(O)'],
            'Tata Motors|Nexon' => ['Smart', 'Pure', 'Creative', 'Fearless'],
            'Maruti Suzuki|Swift' => ['LXi', 'VXi', 'ZXi', 'ZXi+'],
        ];

        foreach ($variants as $modelKey => $names) {
            [$brandName, $modelName] = explode('|', $modelKey);
            $model = CarModel::whereHas('brand', fn ($query) => $query->where('name', $brandName))->where('name', $modelName)->first();
            if (! $model) continue;

            foreach ($names as $sortOrder => $name) {
                Variant::updateOrCreate(
                    ['car_model_id' => $model->id, 'name' => $name],
                    ['slug' => str($name)->slug(), 'status' => 'active', 'sort_order' => $sortOrder]
                );
            }
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\SpecificationCategory;
use App\Models\SpecificationDefinition;
use Illuminate\Database\Seeder;

class SpecificationSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Engine', 'slug' => 'engine', 'description' => 'Engine and powertrain specifications.', 'sort_order' => 1],
            ['name' => 'Transmission', 'slug' => 'transmission', 'description' => 'Drive and gearbox information.', 'sort_order' => 2],
            ['name' => 'Dimensions', 'slug' => 'dimensions', 'description' => 'Vehicle size and space measurements.', 'sort_order' => 3],
            ['name' => 'Performance', 'slug' => 'performance', 'description' => 'Performance and efficiency details.', 'sort_order' => 4],
            ['name' => 'Fuel', 'slug' => 'fuel', 'description' => 'Fuel and range statistics.', 'sort_order' => 5],
            ['name' => 'Safety', 'slug' => 'safety', 'description' => 'Safety and driver assistance features.', 'sort_order' => 6],
            ['name' => 'Comfort', 'slug' => 'comfort', 'description' => 'Cabin and comfort features.', 'sort_order' => 7],
            ['name' => 'EV', 'slug' => 'ev', 'description' => 'Battery and electric vehicle details.', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            $model = SpecificationCategory::updateOrCreate(['slug' => $category['slug']], array_merge($category, ['status' => 'active']));

            $definitions = [
                'engine' => [
                    ['name' => 'Engine Type', 'slug' => 'engine-type', 'data_type' => 'select', 'options' => ['Petrol', 'Diesel', 'CNG', 'Hybrid', 'Electric'], 'unit' => null],
                    ['name' => 'Engine Displacement', 'slug' => 'engine-displacement', 'data_type' => 'number', 'unit' => 'cc'],
                    ['name' => 'Cylinders', 'slug' => 'cylinders', 'data_type' => 'number', 'unit' => 'cyl'],
                    ['name' => 'Max Power', 'slug' => 'max-power', 'data_type' => 'decimal', 'unit' => 'PS'],
                    ['name' => 'Max Torque', 'slug' => 'max-torque', 'data_type' => 'decimal', 'unit' => 'Nm'],
                ],
                'transmission' => [
                    ['name' => 'Transmission Type', 'slug' => 'transmission-type', 'data_type' => 'select', 'options' => ['Manual', 'Automatic', 'AMT', 'CVT', 'DCT'], 'unit' => null],
                    ['name' => 'Number of Gears', 'slug' => 'number-of-gears', 'data_type' => 'number', 'unit' => 'gears'],
                    ['name' => 'Drive Type', 'slug' => 'drive-type', 'data_type' => 'select', 'options' => ['FWD', 'RWD', 'AWD', '4WD'], 'unit' => null],
                ],
                'dimensions' => [
                    ['name' => 'Length', 'slug' => 'length', 'data_type' => 'number', 'unit' => 'mm'],
                    ['name' => 'Width', 'slug' => 'width', 'data_type' => 'number', 'unit' => 'mm'],
                    ['name' => 'Height', 'slug' => 'height', 'data_type' => 'number', 'unit' => 'mm'],
                    ['name' => 'Wheelbase', 'slug' => 'wheelbase', 'data_type' => 'number', 'unit' => 'mm'],
                    ['name' => 'Ground Clearance', 'slug' => 'ground-clearance', 'data_type' => 'number', 'unit' => 'mm'],
                    ['name' => 'Boot Space', 'slug' => 'boot-space', 'data_type' => 'number', 'unit' => 'L'],
                ],
                'performance' => [
                    ['name' => 'Mileage', 'slug' => 'mileage', 'data_type' => 'decimal', 'unit' => 'km/l'],
                    ['name' => 'Top Speed', 'slug' => 'top-speed', 'data_type' => 'number', 'unit' => 'km/h'],
                    ['name' => '0–100 km/h', 'slug' => 'zero-to-hundred', 'data_type' => 'decimal', 'unit' => 's'],
                ],
                'fuel' => [
                    ['name' => 'Fuel Type', 'slug' => 'fuel-type', 'data_type' => 'select', 'options' => ['Petrol', 'Diesel', 'CNG', 'Hybrid', 'Electric'], 'unit' => null],
                    ['name' => 'Fuel Tank Capacity', 'slug' => 'fuel-tank-capacity', 'data_type' => 'number', 'unit' => 'L'],
                ],
                'safety' => [
                    ['name' => 'Airbags', 'slug' => 'airbags', 'data_type' => 'number', 'unit' => 'airbags'],
                    ['name' => 'ABS', 'slug' => 'abs', 'data_type' => 'boolean', 'unit' => null],
                    ['name' => 'ESC', 'slug' => 'esc', 'data_type' => 'boolean', 'unit' => null],
                    ['name' => 'ADAS', 'slug' => 'adas', 'data_type' => 'boolean', 'unit' => null],
                ],
                'comfort' => [
                    ['name' => 'Sunroof', 'slug' => 'sunroof', 'data_type' => 'boolean', 'unit' => null],
                    ['name' => 'Automatic Climate Control', 'slug' => 'automatic-climate-control', 'data_type' => 'boolean', 'unit' => null],
                ],
                'ev' => [
                    ['name' => 'Battery Capacity', 'slug' => 'battery-capacity', 'data_type' => 'number', 'unit' => 'kWh'],
                    ['name' => 'Range', 'slug' => 'range', 'data_type' => 'number', 'unit' => 'km'],
                    ['name' => 'Charging Time', 'slug' => 'charging-time', 'data_type' => 'number', 'unit' => 'hours'],
                ],
            ];

            foreach ($definitions[$model->slug] ?? [] as $index => $definition) {
                SpecificationDefinition::updateOrCreate(
                    ['category_id' => $model->id, 'slug' => $definition['slug']],
                    [
                        'name' => $definition['name'],
                        'description' => null,
                        'data_type' => $definition['data_type'],
                        'unit' => $definition['unit'] ?? null,
                        'options' => $definition['options'] ?? null,
                        'status' => 'active',
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }

}
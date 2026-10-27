<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\HomepageSection;
use Illuminate\Database\Seeder;

class HomepageSectionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the demo records referenced by the homepage are available.
        $this->call([
            BrandSeeder::class,
            CarModelSeeder::class,
            CategorySeeder::class,
            ArticleSeeder::class,
        ]);

        $carIds = $this->orderedIds(CarModel::query()->where('status', 'active')->get(), [
            'swift', 'baleno', 'creta', 'i20', 'nexon', 'scorpio-n',
        ]);
        $brandIds = $this->orderedIds(Brand::query()->where('status', 'active')->get(), [
            'maruti-suzuki', 'hyundai', 'tata-motors', 'mahindra', 'toyota',
        ]);
        $categoryIds = $this->orderedIds(Category::query()->where('status', 'active')->get(), [
            'hatchbacks', 'sedans', 'suvs', 'electric-cars',
        ]);

        $sections = [
            'hero' => [
                'title' => 'Find Your Perfect Car',
                'enabled' => true,
                'display_order' => 1,
                'item_limit' => null,
                'config' => [
                    'heading' => 'Find Your Perfect Car',
                    'subheading' => 'Compare trusted brands, explore popular models, and find the right car for every journey.',
                    'media_id' => null,
                    'cta_text' => 'Explore Cars',
                    'cta_url' => '/cars',
                ],
            ],
            'featured_cars' => [
                'title' => 'Featured Cars',
                'enabled' => true,
                'display_order' => 2,
                'item_limit' => 6,
                'config' => ['item_ids' => $carIds],
            ],
            'popular_brands' => [
                'title' => 'Popular Brands',
                'enabled' => true,
                'display_order' => 3,
                'item_limit' => 8,
                'config' => ['item_ids' => $brandIds],
            ],
            'latest_articles' => [
                'title' => 'Latest Articles',
                'enabled' => true,
                'display_order' => 4,
                'item_limit' => 6,
                'config' => [],
            ],
            'car_categories' => [
                'title' => 'Explore Cars',
                'enabled' => true,
                'display_order' => 5,
                'item_limit' => 8,
                'config' => ['item_ids' => $categoryIds],
            ],
            'promotion' => [
                'title' => 'Make Your Next Drive Count',
                'enabled' => true,
                'display_order' => 6,
                'item_limit' => null,
                'config' => [
                    'description' => 'Explore reviews, compare specifications, and get the details you need before choosing your next car.',
                    'media_id' => null,
                    'cta_text' => 'Browse Cars',
                    'cta_url' => '/cars',
                ],
            ],
        ];

        foreach ($sections as $sectionKey => $section) {
            HomepageSection::updateOrCreate(
                ['section_key' => $sectionKey],
                $section,
            );
        }
    }

    private function orderedIds($models, array $slugs): array
    {
        return $models
            ->filter(fn ($model) => in_array($model->slug, $slugs, true))
            ->sortBy(fn ($model) => array_search($model->slug, $slugs, true))
            ->pluck('id')
            ->values()
            ->all();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin\Tag;
use App\Models\Admin\Blogs as Article;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\CarUseCase;
use App\Models\Faq;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_brands_and_cars_are_paginated_filtered_and_hide_inactive_records(): void
    {
        $brand = Brand::create(['name' => 'Meri Motors', 'slug' => 'meri-motors', 'status' => 'active', 'sort_order' => 1]);
        Brand::create(['name' => 'Hidden Motors', 'slug' => 'hidden-motors', 'status' => 'inactive']);
        $car = CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Roadster', 'slug' => 'roadster',
            'status' => 'active', 'fuel_types' => ['petrol'], 'transmission_types' => ['manual'],
        ]);
        CarModel::create(['brand_id' => $brand->id, 'name' => 'Hidden Model', 'slug' => 'hidden-model', 'status' => 'inactive']);
        $activeVariant = Variant::create(['car_model_id' => $car->id, 'name' => 'Base', 'slug' => 'base', 'status' => 'active']);
        Variant::create(['car_model_id' => $car->id, 'name' => 'Hidden', 'slug' => 'hidden', 'status' => 'inactive']);

        $this->getJson('/api/brands?per_page=10')->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.slug', 'meri-motors')
            ->assertJsonMissingPath('data.data.0.status');
        $this->getJson('/api/brands/hidden-motors')->assertNotFound();

        $this->getJson('/api/cars?brand=meri-motors&fuel_type=petrol&per_page=10')->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.brand.slug', 'meri-motors')
            ->assertJsonPath('data.data.0.image_url', null);
        $this->getJson('/api/cars/meri-motors/roadster')->assertOk()
            ->assertJsonPath('data.variants.0.id', $activeVariant->id)
            ->assertJsonMissingPath('data.status');
        $this->getJson('/api/cars/hidden-model')->assertNotFound();
        $this->getJson('/api/cars/meri-motors/roadster/variants/base')->assertOk()
            ->assertJsonPath('data.slug', 'base')
            ->assertJsonPath('data.car.slug', 'roadster');
    }

    public function test_public_car_use_cases_filter_combine_and_remain_paginated(): void
    {
        $brand = Brand::create(['name' => 'Use Case Motors', 'slug' => 'use-case-motors', 'status' => 'active']);
        $otherBrand = Brand::create(['name' => 'Other Motors', 'slug' => 'other-motors', 'status' => 'active']);

        $overlapping = CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Overlap', 'slug' => 'overlap', 'status' => 'active',
            'body_type' => 'hatchback', 'fuel_types' => ['petrol'], 'transmission_types' => ['automatic'],
        ]);
        $this->assignUseCases($overlapping, ['first-car', 'family', 'city', 'road-trips', 'driving-fun']);
        $familySuv = CarModel::create([
            'brand_id' => $otherBrand->id, 'name' => 'Family SUV', 'slug' => 'family-suv', 'status' => 'active',
            'body_type' => 'suv', 'fuel_types' => ['diesel'], 'transmission_types' => ['manual'],
        ]);
        $this->assignUseCases($familySuv, ['family']);
        $electric = CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Battery Electric', 'slug' => 'battery-electric', 'status' => 'active',
            'fuel_types' => ['electric'],
        ]);
        CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Hybrid', 'slug' => 'hybrid', 'status' => 'active',
            'fuel_types' => ['electric', 'hybrid'],
        ]);
        CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Mixed Fuel Model', 'slug' => 'mixed-fuel-model', 'status' => 'active',
            'fuel_types' => ['petrol', 'electric'],
        ]);
        $hiddenFamily = CarModel::create([
            'brand_id' => $brand->id, 'name' => 'Hidden Family', 'slug' => 'hidden-family', 'status' => 'inactive',
        ]);
        $this->assignUseCases($hiddenFamily, ['family']);
        $inactiveBrand = Brand::create(['name' => 'Hidden Brand', 'slug' => 'hidden-brand', 'status' => 'inactive']);
        $hiddenBrandModel = CarModel::create([
            'brand_id' => $inactiveBrand->id, 'name' => 'Hidden Brand Model', 'slug' => 'hidden-brand-model', 'status' => 'active',
        ]);
        $this->assignUseCases($hiddenBrandModel, ['family']);

        foreach ([
            'first-car' => [$overlapping->slug],
            'family' => [$overlapping->slug, 'family-suv'],
            'city' => [$overlapping->slug],
            'road-trips' => [$overlapping->slug],
            'electric' => [$electric->slug],
            'driving-fun' => [$overlapping->slug],
        ] as $useCase => $expectedSlugs) {
            $response = $this->getJson('/api/cars?use_case='.$useCase.'&per_page=10');
            $response->assertOk()->assertJsonPath('data.total', count($expectedSlugs));
            $this->assertEqualsCanonicalizing($expectedSlugs, collect($response->json('data.data'))->pluck('slug')->all());
        }

        $this->getJson('/api/cars?use_case=family&brand=use-case-motors&body_type=hatchback&fuel_type=petrol&transmission_type=automatic&search=Overlap')
            ->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.slug', $overlapping->slug);
        $this->getJson('/api/cars?use_case=family&brand=use-case-motors')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/cars?use_case=not-a-use-case')->assertUnprocessable()->assertJsonValidationErrors(['use_case']);
        $this->getJson('/api/cars?use_case=family&brand=missing-brand')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/cars?use_case=electric&fuel_type=hybrid')->assertOk()->assertJsonPath('data.total', 0);

        foreach (range(1, 11) as $number) {
            $pagedModel = CarModel::create([
                'brand_id' => $brand->id, 'name' => sprintf('Paged Model %02d', $number),
                'slug' => 'paged-model-'.$number, 'status' => 'active',
            ]);
            $this->assignUseCases($pagedModel, ['first-car']);
        }
        $this->getJson('/api/cars?use_case=first-car&sort=name&direction=desc&per_page=10&page=2')->assertOk()
            ->assertJsonPath('data.total', 12)
            ->assertJsonPath('data.current_page', 2)
            ->assertJsonPath('data.data.0.name', 'Paged Model 01');
    }

    private function assignUseCases(CarModel $car, array $slugs): void
    {
        $ids = CarUseCase::whereIn('slug', $slugs)->pluck('id');
        $car->useCases()->sync($ids);
    }

    public function test_public_categories_tags_and_faqs_only_include_active_content(): void
    {
        Storage::fake('media');
        $categoryImage = 'images/2026/10/suv.png';
        $parent = Category::create(['name' => 'Cars', 'slug' => 'cars-category', 'category_type' => 'automotive', 'status' => 'active']);
        $child = Category::create(['name' => 'SUVs', 'slug' => 'suvs', 'category_type' => 'automotive', 'status' => 'active', 'parent_id' => $parent->id, 'image' => $categoryImage]);
        Category::create(['name' => 'Private', 'slug' => 'private', 'category_type' => 'automotive', 'status' => 'inactive']);
        Faq::create(['category_id' => $parent->id, 'question' => 'How do I choose?', 'answer' => 'Compare your needs.', 'slug' => 'choose-car', 'status' => 'active']);
        Faq::create(['question' => 'Private question?', 'answer' => 'Hidden answer.', 'slug' => 'private-question', 'status' => 'inactive']);
        Tag::create(['name' => 'Guides', 'slug' => 'guides', 'status' => 'active']);
        Tag::create(['name' => 'Hidden tag', 'slug' => 'hidden-tag', 'status' => 'inactive']);

        $this->getJson('/api/categories?category_type=automotive&parent_id=root')->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.slug', 'cars-category')
            ->assertJsonPath('data.data.0.image_url', null);
        $this->getJson('/api/categories/cars-category')->assertOk()
            ->assertJsonPath('data.children.0.slug', 'suvs')
            ->assertJsonPath('data.children.0.image_url', Storage::disk('media')->url($categoryImage));
        $this->getJson('/api/categories/private')->assertNotFound();

        $this->getJson('/api/tags')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.slug', 'guides');
        $this->getJson('/api/tags/hidden-tag')->assertNotFound();
        $this->getJson('/api/faqs')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.slug', 'choose-car');
    }

    public function test_public_article_filters_keep_inactive_related_content_private(): void
    {
        $category = Category::create(['name' => 'Guides', 'slug' => 'guides', 'category_type' => 'automotive', 'status' => 'active']);
        $activeTag = Tag::create(['name' => 'Advice', 'slug' => 'advice', 'status' => 'active']);
        $inactiveTag = Tag::create(['name' => 'Private', 'slug' => 'private-tag', 'status' => 'inactive']);
        $article = Article::create([
            'title' => 'Buying advice', 'slug' => 'buying-advice', 'excerpt' => 'A short guide.',
            'description' => 'Public article content.', 'category_id' => $category->id,
            'status' => 'active', 'publication_status' => 'published', 'is_deleted' => 'no', 'published_at' => now()->subMinute(),
        ]);
        $article->tags()->sync([$activeTag->id, $inactiveTag->id]);
        Article::create([
            'title' => 'Draft advice', 'slug' => 'draft-advice', 'category_id' => $category->id,
            'status' => 'inactive', 'publication_status' => 'draft', 'is_deleted' => 'no',
        ]);

        $this->getJson('/api/articles?category=guides&tag=advice&per_page=10')->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.slug', 'buying-advice')
            ->assertJsonPath('data.data.0.category.slug', 'guides')
            ->assertJsonCount(1, 'data.data.0.tags')
            ->assertJsonMissing(['slug' => 'private-tag']);
        $this->getJson('/api/articles/draft-advice')->assertNotFound();
    }

    public function test_ambiguous_car_slugs_require_brand_scoped_detail_and_seo_urls(): void
    {
        $firstBrand = Brand::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        $secondBrand = Brand::create(['name' => 'Beta', 'slug' => 'beta', 'status' => 'active']);
        CarModel::create(['brand_id' => $firstBrand->id, 'name' => 'City', 'slug' => 'city', 'status' => 'active']);
        CarModel::create(['brand_id' => $secondBrand->id, 'name' => 'City', 'slug' => 'city', 'status' => 'active']);

        $this->getJson('/api/cars/city')->assertNotFound();
        $this->getJson('/api/cars/alpha/city')->assertOk()->assertJsonPath('data.brand.slug', 'alpha');
        $this->getJson('/api/seo/car/city')->assertNotFound();
        $this->getJson('/api/seo/car/alpha/city')->assertOk();
    }
}

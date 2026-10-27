<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Admin\Tag;
use App\Models\Variant;
use App\Models\VariantFeature;
use App\Models\VariantSpecification;
use App\Services\SeoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicApiController extends Controller
{
    public function brands(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['name', 'founded_year'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Brand::active()->search($request->input('search'))
            ->when($request->filled('sort'), fn ($q) => $q->orderBy($request->input('sort'), $request->input('direction') ?: 'asc'))
            ->when(! $request->filled('sort'), fn ($q) => $q->ordered());

        return response()->json(['data' => $this->paginateRequest($request, $query)->through(fn (Brand $brand) => $this->brandResource($brand))]);
    }

    public function brand(string $slug, SeoMetadataService $seo)
    {
        $brand = Brand::active()->where('slug', $slug)->with(['seoMeta.ogImage', 'seoMeta.twitterImage'])->firstOrFail();
        return response()->json(['data' => $this->brandResource($brand) + ['seo' => $seo->metadata('brand', $brand)]]);
    }

    public function cars(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'body_type' => ['nullable', Rule::in(CarModel::BODY_TYPES)],
            'fuel_type' => ['nullable', Rule::in(CarModel::FUEL_TYPES)],
            'transmission_type' => ['nullable', Rule::in(CarModel::TRANSMISSION_TYPES)],
            'use_case' => ['nullable', Rule::in(array_keys(config('constants.CAR_USE_CASES')))],
            'sort' => ['nullable', Rule::in(['name', 'launch_year'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = CarModel::active()->whereHas('brand', fn ($q) => $q->active())
            ->with('brand:id,name,slug,logo')
            ->search($request->input('search'))
            ->when($request->filled('brand'), fn ($q) => $q->whereHas('brand', fn ($brand) => $brand->where('slug', $request->input('brand'))->active()))
            ->when($request->filled('body_type'), fn ($q) => $q->where('body_type', $request->input('body_type')))
            ->when($request->filled('fuel_type'), fn ($q) => $q->whereJsonContains('fuel_types', $request->input('fuel_type')))
            ->when($request->filled('transmission_type'), fn ($q) => $q->whereJsonContains('transmission_types', $request->input('transmission_type')))
            ->when($request->input('use_case') === 'electric', fn ($q) => $q
                ->whereJsonLength('fuel_types', 1)
                ->whereJsonContains('fuel_types', 'electric'))
            ->when($request->filled('use_case') && $request->input('use_case') !== 'electric', fn ($q) => $q
                ->whereHas('useCases', fn ($useCases) => $useCases->where('slug', $request->input('use_case'))))
            ->when($request->filled('sort'), fn ($q) => $q->orderBy($request->input('sort'), $request->input('direction') ?: 'asc'))
            ->when(! $request->filled('sort'), fn ($q) => $q->ordered());

        return response()->json(['data' => $this->paginateRequest($request, $query)->through(fn (CarModel $car) => $this->carResource($car))]);
    }

    public function car(string $slug, SeoMetadataService $seo)
    {
        $cars = CarModel::active()->where('slug', $slug)->whereHas('brand', fn ($q) => $q->active())
            ->limit(2)->get(['id']);
        abort_if($cars->count() !== 1, 404);

        return $this->brandCar($cars->first()->brand()->value('slug'), $slug, $seo);
    }

    public function brandCar(string $brandSlug, string $carSlug, SeoMetadataService $seo)
    {
        $car = CarModel::active()->where('slug', $carSlug)->whereHas('brand', fn ($q) => $q->active()->where('slug', $brandSlug))
            ->with(['brand:id,name,slug,logo', 'variants' => fn ($q) => $q->active()->ordered(), 'seoMeta.ogImage', 'seoMeta.twitterImage'])
            ->firstOrFail();

        return response()->json(['data' => $this->carResource($car) + [
            'description' => $car->description,
            'variants' => $car->variants->map(fn (Variant $variant) => $this->variantResource($variant))->values(),
            'seo' => $seo->metadata('car', $car),
        ]]);
    }

    public function variant(string $brandSlug, string $carSlug, string $variantSlug, SeoMetadataService $seo)
    {
        $car = CarModel::active()->where('slug', $carSlug)->whereHas('brand', fn ($q) => $q->active()->where('slug', $brandSlug))
            ->with('brand:id,name,slug,logo')->firstOrFail();
        $variant = Variant::active()->where('car_model_id', $car->id)->where('slug', $variantSlug)->firstOrFail();

        $specifications = VariantSpecification::query()->where('variant_id', $variant->id)
            ->whereHas('specificationDefinition', fn ($q) => $q->active()->whereHas('category', fn ($category) => $category->active()))
            ->with('specificationDefinition.category')->get()
            ->map(fn (VariantSpecification $item) => $this->specificationResource($item))->values();
        $features = VariantFeature::query()->where('variant_id', $variant->id)
            ->whereHas('feature', fn ($q) => $q->active()->whereHas('category', fn ($category) => $category->active()))
            ->with('feature.category')->get()
            ->map(fn (VariantFeature $item) => $this->featureResource($item))->values();

        return response()->json(['data' => $this->variantResource($variant) + [
            'car' => $this->carResource($car),
            'specifications' => $specifications,
            'features' => $features,
            'seo' => $seo->metadata('car', $car),
        ]]);
    }

    public function categories(Request $request)
    {
        $types = array_keys(config('constants.CATEGORY_TYPES'));
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_type' => ['nullable', Rule::in($types)],
            'parent_id' => ['nullable', 'regex:/^(root|[0-9]+)$/', 'max:20'],
        ]);

        $query = Category::active()->search($request->input('search'))
            ->when($request->filled('category_type'), fn ($q) => $q->where('category_type', $request->input('category_type')))
            ->when($request->input('parent_id') === 'root', fn ($q) => $q->root())
            ->when($request->filled('parent_id') && $request->input('parent_id') !== 'root', fn ($q) => $q->where('parent_id', (int) $request->input('parent_id')))
            ->with(['parent' => fn ($q) => $q->active()->select(['id', 'name', 'slug'])])->ordered();

        return response()->json(['data' => $this->paginateRequest($request, $query)->through(fn (Category $category) => $this->categoryResource($category))]);
    }

    public function category(string $slug)
    {
        $category = Category::active()->where('slug', $slug)->with([
            'parent' => fn ($q) => $q->active()->select(['id', 'name', 'slug']),
            'children' => fn ($q) => $q->active()->ordered(),
        ])->firstOrFail();
        return response()->json(['data' => $this->categoryResource($category) + [
            'children' => $category->children->map(fn (Category $child) => $this->categoryResource($child))->values(),
        ]]);
    }

    public function tags(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = Tag::active()->search($request->input('search'))->ordered();
        return response()->json(['data' => $this->paginateRequest($request, $query)->through(fn (Tag $tag) => [
            'id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug, 'description' => $tag->description,
        ])]);
    }

    public function tag(string $slug)
    {
        $tag = Tag::active()->where('slug', $slug)->firstOrFail();
        return response()->json(['data' => ['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug, 'description' => $tag->description]]);
    }

    public function faqs(Request $request)
    {
        $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'category_type' => ['nullable', Rule::in(array_keys(config('constants.CATEGORY_TYPES')))],
        ]);

        $query = Faq::active()->where(fn ($q) => $q->whereNull('category_id')->orWhereHas('category', fn ($category) => $category->active()))
            ->with('category:id,name,slug,category_type')
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($category) => $category->active()->where('slug', $request->input('category'))))
            ->when($request->filled('category_type'), fn ($q) => $q->whereHas('category', fn ($category) => $category->active()->where('category_type', $request->input('category_type'))))
            ->ordered();

        return response()->json(['data' => $this->paginateRequest($request, $query)->through(fn (Faq $faq) => [
            'id' => $faq->id, 'question' => $faq->question, 'answer' => $faq->answer, 'slug' => $faq->slug,
            'category' => $faq->category ? ['id' => $faq->category->id, 'name' => $faq->category->name, 'slug' => $faq->category->slug, 'type' => $faq->category->category_type] : null,
        ])]);
    }

    private function brandResource(Brand $brand): array
    {
        return [
            'id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug,
            'description' => $brand->description, 'logo_url' => $brand->logo_url,
            'country' => $brand->country, 'website' => $brand->website, 'founded_year' => $brand->founded_year,
        ];
    }

    private function carResource(CarModel $car): array
    {
        return [
            'id' => $car->id, 'name' => $car->name, 'slug' => $car->slug,
            'short_description' => $car->short_description, 'image_url' => $car->thumbnail_url,
            'launch_year' => $car->launch_year, 'discontinued_year' => $car->discontinued_year,
            'body_type' => $car->body_type, 'fuel_types' => $car->fuel_types ?? [],
            'transmission_types' => $car->transmission_types ?? [],
            'brand' => $car->brand ? ['id' => $car->brand->id, 'name' => $car->brand->name, 'slug' => $car->brand->slug, 'logo_url' => $car->brand->logo_url] : null,
        ];
    }

    private function variantResource(Variant $variant): array
    {
        return ['id' => $variant->id, 'name' => $variant->name, 'slug' => $variant->slug, 'description' => $variant->description, 'variant_code' => $variant->variant_code];
    }

    private function categoryResource(Category $category): array
    {
        return [
            'id' => $category->id, 'name' => $category->name, 'slug' => $category->slug,
            'description' => $category->description, 'type' => $category->category_type,
            'image_url' => media_image_url($category->image, 'media'),
            'parent' => $category->parent ? ['id' => $category->parent->id, 'name' => $category->parent->name, 'slug' => $category->parent->slug] : null,
        ];
    }

    private function specificationResource(VariantSpecification $item): array
    {
        $definition = $item->specificationDefinition;
        return [
            'id' => $definition->id, 'name' => $definition->name, 'slug' => $definition->slug,
            'value' => $item->value, 'unit' => $definition->unit, 'data_type' => $definition->data_type,
            'category' => ['id' => $definition->category->id, 'name' => $definition->category->name, 'slug' => $definition->category->slug],
        ];
    }

    private function featureResource(VariantFeature $item): array
    {
        $feature = $item->feature;
        return [
            'id' => $feature->id, 'name' => $feature->name, 'slug' => $feature->slug,
            'description' => $feature->description, 'value' => $item->value,
            'category' => ['id' => $feature->category->id, 'name' => $feature->category->name, 'slug' => $feature->category->slug],
        ];
    }
}

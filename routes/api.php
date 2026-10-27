<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RbacApiController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarModelController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\SpecificationCategoryController;
use App\Http\Controllers\Admin\SpecificationDefinitionController;
use App\Http\Controllers\Admin\VariantSpecificationController;
use App\Http\Controllers\Admin\FeatureCategoryController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\VariantFeatureController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\FeaturedContentController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\PublicApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('articles', [ArticleController::class, 'publicIndex']);
Route::get('articles/{slug}', [ArticleController::class, 'publicShow']);
Route::get('brands', [PublicApiController::class, 'brands']);
Route::get('brands/{slug}', [PublicApiController::class, 'brand']);
Route::get('cars', [PublicApiController::class, 'cars']);
Route::get('cars/{slug}', [PublicApiController::class, 'car']);
Route::get('cars/{brandSlug}/{carSlug}', [PublicApiController::class, 'brandCar']);
Route::get('cars/{brandSlug}/{carSlug}/variants/{variantSlug}', [PublicApiController::class, 'variant']);
Route::get('categories', [PublicApiController::class, 'categories']);
Route::get('categories/{slug}', [PublicApiController::class, 'category']);
Route::get('tags', [PublicApiController::class, 'tags']);
Route::get('tags/{slug}', [PublicApiController::class, 'tag']);
Route::get('faqs', [PublicApiController::class, 'faqs']);
Route::get('homepage', [HomepageController::class, 'publicIndex']);
Route::get('featured-content', [FeaturedContentController::class, 'publicIndex']);
Route::get('seo/homepage', [SeoController::class, 'publicHomepage']);
Route::get('seo/car/{brandSlug}/{carSlug}', [SeoController::class, 'publicCar']);
Route::get('seo/{type}/{slug}', [SeoController::class, 'publicEntity']);

Route::middleware(['web', 'admin'])->prefix('admin')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'apiIndex'])->middleware('permission:audit_logs.view');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'apiShow'])->middleware('permission:audit_logs.view');
    Route::get('seo/entities/{type}', [SeoController::class, 'entities'])->middleware('permission:seo.create,seo.update');
    Route::get('seo', [SeoController::class, 'index'])->middleware('permission:seo.view');
    Route::post('seo', [SeoController::class, 'store'])->middleware('permission:seo.create');
    Route::get('seo/{seo}', [SeoController::class, 'show'])->middleware('permission:seo.view');
    Route::match(['put','patch'], 'seo/{seo}', [SeoController::class, 'update'])->middleware('permission:seo.update');
    Route::delete('seo/{seo}', [SeoController::class, 'destroy'])->middleware('permission:seo.delete');
    Route::get('featured-content', [FeaturedContentController::class, 'index'])->middleware('permission:featured_content.view');
    Route::post('featured-content', [FeaturedContentController::class, 'store'])->middleware('permission:featured_content.create');
    Route::get('featured-content/{featuredContent}', [FeaturedContentController::class, 'show'])->middleware('permission:featured_content.view');
    Route::match(['put','patch'], 'featured-content/{featuredContent}', [FeaturedContentController::class, 'update'])->middleware('permission:featured_content.update');
    Route::delete('featured-content/{featuredContent}', [FeaturedContentController::class, 'destroy'])->middleware('permission:featured_content.delete');
    Route::get('homepage', [HomepageController::class, 'show'])->middleware('permission:homepage.view');
    Route::get('homepage/options/{type}', [HomepageController::class, 'options'])->middleware('permission:homepage.view');
    Route::put('homepage', [HomepageController::class, 'update'])->middleware('permission:homepage.update');
    Route::get('media', [MediaController::class, 'index'])->middleware('permission:media.view');
    Route::post('media', [MediaController::class, 'store'])->middleware('permission:media.upload');
    Route::get('media/{medium}', [MediaController::class, 'show'])->middleware('permission:media.view');
    Route::post('media/{medium}/replace', [MediaController::class, 'replace'])->middleware('permission:media.update');
    Route::match(['put', 'patch'], 'media/{medium}', [MediaController::class, 'update'])->middleware('permission:media.update');
    Route::delete('media/{medium}', [MediaController::class, 'destroy'])->middleware('permission:media.delete');
    Route::get('dashboard', [DashboardController::class, 'data'])->middleware('permission:dashboard.view');
    Route::get('brands', [BrandController::class, 'index'])->middleware('permission:brands.view');
    Route::post('brands', [BrandController::class, 'store'])->middleware('permission:brands.create');
    Route::get('brands/{brand}', [BrandController::class, 'show'])->middleware('permission:brands.view');
    Route::match(['put', 'patch'], 'brands/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete');
    Route::get('cars', [CarModelController::class, 'index'])->middleware('permission:cars.view');
    Route::post('cars', [CarModelController::class, 'store'])->middleware('permission:cars.create');
    Route::get('cars/{car}', [CarModelController::class, 'show'])->middleware('permission:cars.view');
    Route::match(['put', 'patch'], 'cars/{car}', [CarModelController::class, 'update'])->middleware('permission:cars.update');
    Route::delete('cars/{car}', [CarModelController::class, 'destroy'])->middleware('permission:cars.delete');
    Route::get('variants', [VariantController::class, 'index'])->middleware('permission:variants.view');
    Route::get('variants/models', [VariantController::class, 'models'])->middleware('permission:variants.view,variants.create');
    Route::post('variants', [VariantController::class, 'store'])->middleware('permission:variants.create');
    Route::get('variants/{variant}', [VariantController::class, 'show'])->middleware('permission:variants.view');
    Route::match(['put', 'patch'], 'variants/{variant}', [VariantController::class, 'update'])->middleware('permission:variants.update');
    Route::delete('variants/{variant}', [VariantController::class, 'destroy'])->middleware('permission:variants.delete');
    Route::get('specification-categories', [SpecificationCategoryController::class, 'index'])->middleware('permission:specification_categories.view');
    Route::post('specification-categories', [SpecificationCategoryController::class, 'store'])->middleware('permission:specification_categories.create');
    Route::get('specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'show'])->middleware('permission:specification_categories.view');
    Route::match(['put', 'patch'], 'specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'update'])->middleware('permission:specification_categories.update');
    Route::delete('specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'destroy'])->middleware('permission:specification_categories.delete');
    Route::get('specification-definitions', [SpecificationDefinitionController::class, 'index'])->middleware('permission:specification_definitions.view');
    Route::get('specification-definitions/category', [SpecificationDefinitionController::class, 'byCategory'])->middleware('permission:specification_definitions.view,specification_definitions.create');
    Route::post('specification-definitions', [SpecificationDefinitionController::class, 'store'])->middleware('permission:specification_definitions.create');
    Route::get('specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'show'])->middleware('permission:specification_definitions.view');
    Route::match(['put', 'patch'], 'specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'update'])->middleware('permission:specification_definitions.update');
    Route::delete('specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'destroy'])->middleware('permission:specification_definitions.delete');
    Route::get('feature-categories', [FeatureCategoryController::class, 'index'])->middleware('permission:feature_categories.view');
    Route::post('feature-categories', [FeatureCategoryController::class, 'store'])->middleware('permission:feature_categories.create');
    Route::get('feature-categories/{featureCategory}', [FeatureCategoryController::class, 'show'])->middleware('permission:feature_categories.view');
    Route::match(['put', 'patch'], 'feature-categories/{featureCategory}', [FeatureCategoryController::class, 'update'])->middleware('permission:feature_categories.update');
    Route::delete('feature-categories/{featureCategory}', [FeatureCategoryController::class, 'destroy'])->middleware('permission:feature_categories.delete');
    Route::get('features', [FeatureController::class, 'index'])->middleware('permission:features.view');
    Route::get('features/category', [FeatureController::class, 'byCategory'])->middleware('permission:features.view,features.create');
    Route::post('features', [FeatureController::class, 'store'])->middleware('permission:features.create');
    Route::get('features/{feature}', [FeatureController::class, 'show'])->middleware('permission:features.view');
    Route::match(['put', 'patch'], 'features/{feature}', [FeatureController::class, 'update'])->middleware('permission:features.update');
    Route::delete('features/{feature}', [FeatureController::class, 'destroy'])->middleware('permission:features.delete');
    Route::get('categories/active', [CategoryController::class, 'active'])->middleware('permission:categories.view,categories.create');
    Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:categories.view');
    Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:categories.create');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view');
    Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete');
    Route::get('tags/active', [TagController::class, 'active'])->middleware('permission:tags.view,tags.create');
    Route::get('tags', [TagController::class, 'index'])->middleware('permission:tags.view');
    Route::post('tags', [TagController::class, 'store'])->middleware('permission:tags.create');
    Route::get('tags/{tag}', [TagController::class, 'show'])->middleware('permission:tags.view');
    Route::match(['put', 'patch'], 'tags/{tag}', [TagController::class, 'update'])->middleware('permission:tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->middleware('permission:tags.delete');
    Route::get('faqs/active', [FaqController::class, 'active'])->middleware('permission:faqs.view,faqs.create');
    Route::get('faqs', [FaqController::class, 'index'])->middleware('permission:faqs.view');
    Route::post('faqs', [FaqController::class, 'store'])->middleware('permission:faqs.create');
    Route::get('faqs/{faq}', [FaqController::class, 'show'])->middleware('permission:faqs.view');
    Route::match(['put', 'patch'], 'faqs/{faq}', [FaqController::class, 'update'])->middleware('permission:faqs.update');
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->middleware('permission:faqs.delete');
    Route::get('variants/{variant}/specifications', [VariantSpecificationController::class, 'index'])->middleware('permission:specifications.view');
    Route::post('variants/{variant}/specifications', [VariantSpecificationController::class, 'store'])->middleware('permission:specifications.create');
    Route::put('variants/{variant}/specifications/{specification}', [VariantSpecificationController::class, 'update'])->middleware('permission:specifications.update');
    Route::delete('variants/{variant}/specifications/{specification}', [VariantSpecificationController::class, 'destroy'])->middleware('permission:specifications.delete');
    Route::get('variants/{variant}/features', [VariantFeatureController::class, 'index'])->middleware('permission:features.view');
    Route::post('variants/{variant}/features', [VariantFeatureController::class, 'store'])->middleware('permission:features.create');
    Route::put('variants/{variant}/features/{feature}', [VariantFeatureController::class, 'update'])->middleware('permission:features.update');
    Route::delete('variants/{variant}/features/{feature}', [VariantFeatureController::class, 'destroy'])->middleware('permission:features.delete');
    Route::get('me', [RbacApiController::class, 'me']);
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::patch('users/{user}/status', [UserController::class, 'status'])->middleware('permission:users.status');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
    Route::get('articles', [ArticleController::class, 'index'])->middleware('permission:articles.view');
    Route::post('articles', [ArticleController::class, 'store'])->middleware('permission:articles.create');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->middleware('permission:articles.view');
    Route::match(['put', 'patch'], 'articles/{article}', [ArticleController::class, 'update'])->middleware('permission:articles.update');
    Route::patch('articles/{article}/status', [ArticleController::class, 'status'])->middleware('permission:articles.publish');
    Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->middleware('permission:articles.delete');
    Route::get('settings', [SettingController::class, 'index'])->middleware('permission:settings.view');
    Route::get('settings/{group}', [SettingController::class, 'byGroup'])->middleware('permission:settings.view');
    Route::match(['put', 'patch'], 'settings', [SettingController::class, 'update'])->middleware('permission:settings.update');
    Route::get('permissions', [RbacApiController::class, 'permissions'])->middleware('permission:roles.view');
    Route::get('roles', [RbacApiController::class, 'roles'])->middleware('permission:roles.view');
    Route::get('roles/{role}', [RbacApiController::class, 'showRole'])->middleware('permission:roles.view');
    Route::post('roles', [RbacApiController::class, 'storeRole'])->middleware('permission:roles.create');
    Route::put('roles/{role}', [RbacApiController::class, 'updateRole'])->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RbacApiController::class, 'deleteRole'])->middleware('permission:roles.delete');
    Route::get('admins/{user}/roles', [RbacApiController::class, 'userRoles'])->middleware('permission:roles.assign');
    Route::put('admins/{user}/roles', [RbacApiController::class, 'assignRoles'])->middleware('permission:roles.assign');
});

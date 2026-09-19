<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarModelController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\SpecificationCategoryController;
use App\Http\Controllers\Admin\SpecificationDefinitionController;
use App\Http\Controllers\Admin\VariantSpecificationController;
use App\Http\Controllers\Admin\FeatureCategoryController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Admin\VariantFeatureController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {

    // Admin Dashboard
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    Route::get('403', fn () => view('admin.forbidden'))->name('forbidden');

    Route::middleware('permission:roles.view')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });
    Route::middleware('permission:roles.create')->post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::middleware('permission:roles.update')->group(function () {
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('permission:roles.delete')->delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::middleware('permission:brands.create')->get('brands/create', [BrandController::class, 'create'])->name('brands.create');
    Route::middleware('permission:brands.view')->group(function () {
        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])->middleware('permission:brands.update')->name('brands.edit');
        Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
    });
    Route::middleware('permission:brands.create')->post('brands', [BrandController::class, 'store'])->name('brands.store');
    Route::middleware('permission:brands.update')->put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::middleware('permission:brands.delete')->delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    Route::middleware('permission:cars.create')->get('cars/create', [CarModelController::class, 'create'])->name('cars.create');
    Route::middleware('permission:cars.view')->group(function () {
        Route::get('cars', [CarModelController::class, 'index'])->name('cars.index');
        Route::get('cars/{car}/edit', [CarModelController::class, 'edit'])->middleware('permission:cars.update')->name('cars.edit');
        Route::get('cars/{car}', [CarModelController::class, 'show'])->name('cars.show');
    });
    Route::middleware('permission:cars.create')->post('cars', [CarModelController::class, 'store'])->name('cars.store');
    Route::middleware('permission:cars.update')->put('cars/{car}', [CarModelController::class, 'update'])->name('cars.update');
    Route::middleware('permission:cars.delete')->delete('cars/{car}', [CarModelController::class, 'destroy'])->name('cars.destroy');

    Route::middleware('permission:variants.create')->get('variants/create', [VariantController::class, 'create'])->name('variants.create');
    Route::middleware('permission:variants.view,variants.create')->get('variants/models', [VariantController::class, 'models'])->name('variants.models');
    Route::middleware('permission:variants.view')->group(function () {
        Route::get('variants', [VariantController::class, 'index'])->name('variants.index');
        Route::get('variants/{variant}/edit', [VariantController::class, 'edit'])->middleware('permission:variants.update')->name('variants.edit');
        Route::get('variants/{variant}', [VariantController::class, 'show'])->name('variants.show');
        Route::get('variants/{variant}/specifications', [VariantSpecificationController::class, 'index'])->name('variant-specifications.index');
    });
    Route::middleware('permission:variants.create')->post('variants', [VariantController::class, 'store'])->name('variants.store');
    Route::middleware('permission:variants.update')->put('variants/{variant}', [VariantController::class, 'update'])->name('variants.update');
    Route::middleware('permission:variants.delete')->delete('variants/{variant}', [VariantController::class, 'destroy'])->name('variants.destroy');

    Route::middleware('permission:specification_categories.create')->get('specification-categories/create', [SpecificationCategoryController::class, 'create'])->name('specification-categories.create');
    Route::middleware('permission:specification_categories.view')->group(function () {
        Route::get('specification-categories', [SpecificationCategoryController::class, 'index'])->name('specification-categories.index');
        Route::get('specification-categories/{specificationCategory}/edit', [SpecificationCategoryController::class, 'edit'])->middleware('permission:specification_categories.update')->name('specification-categories.edit');
        Route::get('specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'show'])->name('specification-categories.show');
    });
    Route::middleware('permission:specification_categories.create')->post('specification-categories', [SpecificationCategoryController::class, 'store'])->name('specification-categories.store');
    Route::middleware('permission:specification_categories.update')->put('specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'update'])->name('specification-categories.update');
    Route::middleware('permission:specification_categories.delete')->delete('specification-categories/{specificationCategory}', [SpecificationCategoryController::class, 'destroy'])->name('specification-categories.destroy');

    Route::middleware('permission:specification_definitions.create')->get('specification-definitions/create', [SpecificationDefinitionController::class, 'create'])->name('specification-definitions.create');
    Route::middleware('permission:specification_definitions.view')->group(function () {
        Route::get('specification-definitions', [SpecificationDefinitionController::class, 'index'])->name('specification-definitions.index');
        Route::get('specification-definitions/{specificationDefinition}/edit', [SpecificationDefinitionController::class, 'edit'])->middleware('permission:specification_definitions.update')->name('specification-definitions.edit');
        Route::get('specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'show'])->name('specification-definitions.show');
    });
    Route::middleware('permission:specification_definitions.create')->post('specification-definitions', [SpecificationDefinitionController::class, 'store'])->name('specification-definitions.store');
    Route::middleware('permission:specification_definitions.update')->put('specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'update'])->name('specification-definitions.update');
    Route::middleware('permission:specification_definitions.delete')->delete('specification-definitions/{specificationDefinition}', [SpecificationDefinitionController::class, 'destroy'])->name('specification-definitions.destroy');

    Route::middleware('permission:feature_categories.create')->get('feature-categories/create', [FeatureCategoryController::class, 'create'])->name('feature-categories.create');
    Route::middleware('permission:feature_categories.view')->group(function () {
        Route::get('feature-categories', [FeatureCategoryController::class, 'index'])->name('feature-categories.index');
        Route::get('feature-categories/{featureCategory}/edit', [FeatureCategoryController::class, 'edit'])->middleware('permission:feature_categories.update')->name('feature-categories.edit');
        Route::get('feature-categories/{featureCategory}', [FeatureCategoryController::class, 'show'])->name('feature-categories.show');
    });
    Route::middleware('permission:feature_categories.create')->post('feature-categories', [FeatureCategoryController::class, 'store'])->name('feature-categories.store');
    Route::middleware('permission:feature_categories.update')->put('feature-categories/{featureCategory}', [FeatureCategoryController::class, 'update'])->name('feature-categories.update');
    Route::middleware('permission:feature_categories.delete')->delete('feature-categories/{featureCategory}', [FeatureCategoryController::class, 'destroy'])->name('feature-categories.destroy');

    Route::middleware('permission:features.create')->get('features/create', [FeatureController::class, 'create'])->name('features.create');
    Route::middleware('permission:features.view')->group(function () {
        Route::get('features', [FeatureController::class, 'index'])->name('features.index');
        Route::get('features/{feature}/edit', [FeatureController::class, 'edit'])->middleware('permission:features.update')->name('features.edit');
        Route::get('features/{feature}', [FeatureController::class, 'show'])->name('features.show');
        Route::get('variants/{variant}/features', [VariantFeatureController::class, 'index'])->name('variant-features.index');
    });
    Route::middleware('permission:features.create')->post('features', [FeatureController::class, 'store'])->name('features.store');
    Route::middleware('permission:features.update')->put('features/{feature}', [FeatureController::class, 'update'])->name('features.update');
    Route::middleware('permission:features.delete')->delete('features/{feature}', [FeatureController::class, 'destroy'])->name('features.destroy');

    // Blog Categories Routes Start
    // List all categories
    Route::get('blog-categories', [BlogCategoryController::class, 'index'])->name('blog-categories.index');
    Route::get('get-blog-categories', [BlogCategoryController::class, 'getCategoriesList']);
    // Show add form
    Route::get('blog-categories/create', [BlogCategoryController::class, 'create'])->name('blog-categories.create');
    // Handle add form submission
    Route::post('blog-categories/store', [BlogCategoryController::class, 'store'])->name('blog-categories.store');
    // Show edit form
    Route::get('blog-categories/edit/{id}', [BlogCategoryController::class, 'edit'])->name('blog-categories.edit');
    // Handle update form submission
    Route::post('blog-categories/update', [BlogCategoryController::class, 'update'])->name('blog-categories.update');
    Route::post('blog-categories/delete', [BlogCategoryController::class, 'destroy'])->name('blog-categories.delete');
    // Blog Categories Routes end

    // Blogs Routes Start
    Route::get('blogs', [BlogController::class, 'index']);
    Route::get('get-blogs', [BlogController::class, 'getBlogsList']);
    Route::get('blog/create', [BlogController::class, 'create']);
    Route::post('blog/store', [BlogController::class, 'store'])->name('blog.store');
    Route::get('blog/edit/{id}', [BlogController::class, 'edit'])->name('blog.edit');
    Route::post('blog/update', [BlogController::class, 'update'])->name('blog.update');
    Route::post('blog/delete', [BlogController::class, 'destroy'])->name('blog.delete');
    Route::get('blog/gallery/{id}', [BlogController::class, 'galleryImages'])->name('blog.gallery');
    Route::get('blog/gallery/{id}/media', [BlogController::class, 'getGalleryMediaList']);
    Route::post('blog/gallery/{id}/upload', [BlogController::class, 'uploadGalleryMedia'])->name('blog.gallery.upload');
    Route::post('blog/gallery/media/delete', [BlogController::class, 'deleteGalleryMedia'])->name('blog.gallery.delete');
});


require __DIR__.'/auth.php';

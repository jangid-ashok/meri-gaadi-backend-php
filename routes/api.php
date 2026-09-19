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

Route::middleware(['web', 'admin'])->prefix('admin')->group(function () {
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
    Route::get('variants/{variant}/specifications', [VariantSpecificationController::class, 'index'])->middleware('permission:specifications.view');
    Route::post('variants/{variant}/specifications', [VariantSpecificationController::class, 'store'])->middleware('permission:specifications.create');
    Route::put('variants/{variant}/specifications/{specification}', [VariantSpecificationController::class, 'update'])->middleware('permission:specifications.update');
    Route::delete('variants/{variant}/specifications/{specification}', [VariantSpecificationController::class, 'destroy'])->middleware('permission:specifications.delete');
    Route::get('variants/{variant}/features', [VariantFeatureController::class, 'index'])->middleware('permission:features.view');
    Route::post('variants/{variant}/features', [VariantFeatureController::class, 'store'])->middleware('permission:features.create');
    Route::put('variants/{variant}/features/{feature}', [VariantFeatureController::class, 'update'])->middleware('permission:features.update');
    Route::delete('variants/{variant}/features/{feature}', [VariantFeatureController::class, 'destroy'])->middleware('permission:features.delete');
    Route::get('me', [RbacApiController::class, 'me']);
    Route::get('permissions', [RbacApiController::class, 'permissions'])->middleware('permission:roles.view');
    Route::get('roles', [RbacApiController::class, 'roles'])->middleware('permission:roles.view');
    Route::get('roles/{role}', [RbacApiController::class, 'showRole'])->middleware('permission:roles.view');
    Route::post('roles', [RbacApiController::class, 'storeRole'])->middleware('permission:roles.create');
    Route::put('roles/{role}', [RbacApiController::class, 'updateRole'])->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RbacApiController::class, 'deleteRole'])->middleware('permission:roles.delete');
    Route::get('admins/{user}/roles', [RbacApiController::class, 'userRoles'])->middleware('permission:roles.assign');
    Route::put('admins/{user}/roles', [RbacApiController::class, 'assignRoles'])->middleware('permission:roles.assign');
});

<?php

namespace App\Providers;

use App\Models\Admin\Blogs;
use App\Models\Admin\BlogCategory;
use App\Models\Admin\Tag;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\FeaturedContent;
use App\Models\HomepageSection;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SeoMeta;
use App\Models\Setting;
use App\Models\SpecificationCategory;
use App\Models\SpecificationDefinition;
use App\Models\User;
use App\Models\Variant;
use App\Models\VariantFeature;
use App\Models\VariantSpecification;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        foreach ([Blogs::class, BlogCategory::class, Tag::class, Brand::class, CarModel::class, Category::class, Faq::class, Feature::class, FeatureCategory::class, FeaturedContent::class, HomepageSection::class, Media::class, Permission::class, Role::class, SeoMeta::class, Setting::class, SpecificationCategory::class, SpecificationDefinition::class, User::class, Variant::class, VariantFeature::class, VariantSpecification::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}

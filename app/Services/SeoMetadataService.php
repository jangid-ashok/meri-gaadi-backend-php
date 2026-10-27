<?php

namespace App\Services;

use App\Models\{Brand, CarModel, Category, SeoMeta};
use App\Models\Admin\Blogs as Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoMetadataService
{
    public const ENTITY_TYPES = ['homepage', 'car', 'brand', 'category', 'article'];

    public function metadata(string $type, ?Model $entity = null, ?SeoMeta $seo = null): array
    {
        if (! $seo && $entity) {
            $entity->loadMissing(['seoMeta.ogImage', 'seoMeta.twitterImage']);
            $seo = $entity->seoMeta;
        }
        if ($type === 'car' && $entity) $entity->loadMissing('brand:id,name,slug');
        $name = $type === 'homepage' ? 'Meri Gaadi' : (string)($entity?->name ?? $entity?->title ?? '');
        $description = match ($type) {
            'homepage' => 'Explore cars, brands, specifications and articles on Meri Gaadi.',
            'car' => $entity->short_description ?: $entity->description ?: 'Explore '.$name.' specifications and available details on Meri Gaadi.',
            'brand' => $entity->description ?: 'Explore cars and models from '.$name.' on Meri Gaadi.',
            'category' => $entity->description ?: 'Explore '.$name.' cars on Meri Gaadi.',
            'article' => $entity->excerpt ?: $entity->description ?: null,
            default => null,
        };
        $description = $description ? Str::limit(trim(strip_tags($description)), 2000, '') : null;
        $title = match ($type) {
            'homepage' => 'Meri Gaadi | Explore Cars',
            'car' => $name.' | Car Details | Meri Gaadi',
            'brand' => $name.' Cars & Models | Meri Gaadi',
            'category' => $name.' Cars | Meri Gaadi',
            'article' => $name.' | Meri Gaadi',
            default => 'Meri Gaadi',
        };
        $title = Str::limit($title, 255, '');
        $baseUrl = rtrim((string)config('app.url'), '/');
        $canonical = match ($type) {
            'homepage' => $baseUrl,
            'car' => $baseUrl.'/cars/'.rawurlencode($entity->brand?->slug ?? '').'/'.rawurlencode($entity->slug),
            'brand' => $baseUrl.'/brands/'.rawurlencode($entity->slug),
            'category' => $baseUrl.'/categories/'.rawurlencode($entity->slug),
            'article' => $baseUrl.'/articles/'.rawurlencode($entity->slug),
            default => null,
        };
        $image = $seo?->ogImage?->url;
        if (! $image && $type === 'car' && $entity?->thumbnail) $image = media_image_url($entity->thumbnail, 'car_media');
        if (! $image && $type === 'brand' && $entity?->logo) $image = media_image_url($entity->logo, 'brand_media');
        if (! $image && $type === 'article' && $entity?->featured_image) $image = media_image_url($entity->featured_image, 'brand_media');

        $metaTitle = $seo?->meta_title ?: ($type === 'article' ? ($entity?->meta_title ?: $title) : $title);
        $metaDescription = $seo?->meta_description ?: ($type === 'article' ? ($entity?->meta_description ?: $description) : $description);
        $ogTitle = $seo?->og_title ?: $metaTitle;
        $ogDescription = $seo?->og_description ?: $metaDescription;
        $ogImage = $seo?->ogImage?->url ?: $image;

        return [
            'title' => $metaTitle,
            'description' => $metaDescription,
            'canonical' => $seo?->canonical_url ?: $canonical,
            'robots' => $seo?->robots ?: 'index, follow',
            'og' => ['title'=>$ogTitle,'description'=>$ogDescription,'image'=>$ogImage],
            'twitter' => [
                'card' => $seo?->twitter_card_type ?: 'summary_large_image',
                'title' => $seo?->twitter_title ?: $ogTitle,
                'description' => $seo?->twitter_description ?: $ogDescription,
                'image' => $seo?->twitterImage?->url ?: $ogImage,
            ],
        ];
    }

    public function publicEntity(string $type, string $slug): ?Model
    {
        return match ($type) {
            'car' => $this->uniquePublicCar($slug),
            'brand' => Brand::active()->where('slug',$slug)->first(),
            'category' => Category::active()->where('slug',$slug)->first(),
            'article' => Article::where('slug',$slug)->where('is_deleted','no')->where('status','active')->where('publication_status','published')->whereNotNull('published_at')->where('published_at','<=',now())->first(),
            default => null,
        };
    }

    private function uniquePublicCar(string $slug): ?Model
    {
        $matches = CarModel::active()->where('slug', $slug)
            ->whereHas('brand', fn ($query) => $query->active())
            ->with('brand:id,name,slug')->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function publicSeoForSlug(string $type, string $slug): ?array
    {
        $entity = $this->publicEntity($type, $slug);
        if (! $entity) return null;
        return $this->metadata($type, $entity);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Brand extends Model
{
    protected $appends = ['logo_url'];
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'country',
        'website',
        'founded_year',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'sort_order' => 'integer',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return media_image_url($this->logo, 'brand_media');
    }

    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function seoMeta(): MorphOne { return $this->morphOne(SeoMeta::class, 'seoable'); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $term = '%' . addcslashes(trim($term), '%_') . '%';

        return $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('slug', 'like', $term)
                ->orWhere('country', 'like', $term);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

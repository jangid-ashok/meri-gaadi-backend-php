<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'image', 'parent_id', 'category_type', 'status', 'sort_order'];

    protected $casts = ['parent_id' => 'integer', 'sort_order' => 'integer'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function seoMeta(): MorphOne { return $this->morphOne(SeoMeta::class, 'seoable'); }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
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
                ->orWhere('description', 'like', $term)
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('name', 'like', $term));
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

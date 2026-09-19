<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_model_id', 'name', 'slug', 'description', 'variant_code', 'status', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    protected $appends = ['full_name'];

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function variantSpecifications(): HasMany
    {
        return $this->hasMany(VariantSpecification::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->carModel?->brand?->name ? $this->carModel->brand->name . ' ' : '') . ($this->carModel?->name ? $this->carModel->name . ' ' : '') . $this->name);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) return $query;

        $term = '%' . addcslashes(trim($term), '%_') . '%';
        return $query->where(fn (Builder $query) => $query
            ->where('variants.name', 'like', $term)
            ->orWhere('variants.slug', 'like', $term)
            ->orWhere('variants.variant_code', 'like', $term));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
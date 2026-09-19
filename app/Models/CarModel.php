<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    use HasFactory;

    public const BODY_TYPES = ['hatchback', 'sedan', 'suv', 'muv', 'coupe', 'convertible', 'pickup', 'wagon', 'mpv', 'other'];
    public const FUEL_TYPES = ['petrol', 'diesel', 'cng', 'electric', 'hybrid'];
    public const TRANSMISSION_TYPES = ['manual', 'automatic', 'amt', 'cvt', 'dct', 'imt'];

    protected $table = 'car_models';

    protected $fillable = [
        'brand_id', 'name', 'slug', 'short_description', 'description', 'thumbnail',
        'launch_year', 'discontinued_year', 'body_type', 'fuel_types',
        'transmission_types', 'status', 'sort_order',
    ];

    protected $casts = [
        'launch_year' => 'integer', 'discontinued_year' => 'integer', 'sort_order' => 'integer',
        'fuel_types' => 'array', 'transmission_types' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) return $query;
        $term = '%' . addcslashes(trim($term), '%_') . '%';
        return $query->where(fn (Builder $query) => $query->where('car_models.name', 'like', $term)->orWhere('car_models.slug', 'like', $term));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
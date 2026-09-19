<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecificationDefinition extends Model
{
    use HasFactory, SoftDeletes;

    public const DATA_TYPES = ['text', 'number', 'decimal', 'boolean', 'select'];

    protected $fillable = ['category_id', 'name', 'slug', 'description', 'data_type', 'unit', 'options', 'sort_order', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
        'options' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SpecificationCategory::class, 'category_id');
    }

    public function variantSpecifications(): HasMany
    {
        return $this->hasMany(VariantSpecification::class, 'specification_definition_id');
    }

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
            $query->where('specification_definitions.name', 'like', $term)
                ->orWhere('specification_definitions.slug', 'like', $term)
                ->orWhere('specification_definitions.unit', 'like', $term);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

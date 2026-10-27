<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['category_id', 'question', 'answer', 'slug', 'status', 'sort_order'];

    protected $casts = ['category_id' => 'integer', 'sort_order' => 'integer'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
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
            $query->where('faqs.question', 'like', $term)
                ->orWhere('faqs.answer', 'like', $term);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at')->orderBy('id');
    }
}

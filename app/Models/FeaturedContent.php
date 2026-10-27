<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedContent extends Model
{
    public const PLACEMENTS = ['homepage_hero', 'homepage_featured', 'homepage_promotion', 'category_page'];
    protected $fillable = ['title', 'description', 'media_id', 'cta_text', 'cta_url', 'placement', 'status', 'display_order'];
    protected $casts = ['display_order' => 'integer'];

    public function media(): BelongsTo { return $this->belongsTo(Media::class); }
}

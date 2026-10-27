<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    public const ROBOTS = ['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'];
    public const TWITTER_CARDS = ['summary', 'summary_large_image'];

    protected $fillable = [
        'seoable_type','seoable_id','meta_title','meta_description','canonical_url','og_title','og_description','og_image_id','robots',
        'twitter_card_type','twitter_title','twitter_description','twitter_image_id',
    ];

    public function seoable(): MorphTo { return $this->morphTo(); }
    public function ogImage(): BelongsTo { return $this->belongsTo(Media::class, 'og_image_id'); }
    public function twitterImage(): BelongsTo { return $this->belongsTo(Media::class, 'twitter_image_id'); }
}

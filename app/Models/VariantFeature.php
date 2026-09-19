<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantFeature extends Model
{
    use HasFactory;

    protected $fillable = ['variant_id', 'feature_id', 'value'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}

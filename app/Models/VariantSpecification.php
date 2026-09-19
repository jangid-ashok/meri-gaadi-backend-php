<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantSpecification extends Model
{
    use HasFactory;

    protected $fillable = ['variant_id', 'specification_definition_id', 'value'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function specificationDefinition(): BelongsTo
    {
        return $this->belongsTo(SpecificationDefinition::class, 'specification_definition_id');
    }
}

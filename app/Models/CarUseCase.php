<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CarUseCase extends Model
{
    protected $fillable = ['slug', 'label'];

    public function carModels(): BelongsToMany
    {
        return $this->belongsToMany(CarModel::class, 'car_model_use_cases');
    }
}

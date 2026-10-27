<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $fillable = ['section_key', 'title', 'enabled', 'display_order', 'item_limit', 'config'];
    protected $casts = ['enabled' => 'boolean', 'display_order' => 'integer', 'item_limit' => 'integer', 'config' => 'array'];

}

<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'meta_title',
        'slug',
        'meta_description',
        'description',
        'image',
        'is_deleted',
        'deleted_at',
        'status',
    ];

    public function blogs()
    {
        return $this->belongsToMany(Blogs::class, 'blog_blog_category', 'blog_category_id', 'blog_id')
            ->withTimestamps();
    }
}

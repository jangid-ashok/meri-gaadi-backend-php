<?php

namespace App\Models\Admin;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\SeoMeta;

class Blogs extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'sub_title',
        'meta_title',
        'slug',
        'meta_description',
        'description',
        'is_deleted',
        'deleted_at',
        'status',
        'published_at',
        'excerpt',
        'featured_image',
        'category_id',
        'publication_status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_blog_category', 'blog_id', 'blog_category_id')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tag', 'blog_id', 'tag_id')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class, 'blog_id');
    }

    public function media()
    {
        return $this->hasMany(BlogMedia::class, 'blog_id');
    }

    public function seoMeta(): MorphOne { return $this->morphOne(SeoMeta::class, 'seoable'); }
}

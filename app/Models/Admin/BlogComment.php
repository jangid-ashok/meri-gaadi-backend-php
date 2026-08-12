<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogComment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'blog_id',
        'user_id',
        'body',
        'status',
    ];

    public function blog()
    {
        return $this->belongsTo(Blogs::class, 'blog_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

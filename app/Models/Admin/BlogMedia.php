<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BlogMedia extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'blog_id',
        'file_name',
        'disk',
        'path',
        'mime',
        'size',
    ];

    /**
     * Base public path for all blog gallery files (no trailing slash).
     */
    public static function galleryBasePath(): string
    {
        return trim(config('constants.BLOG_GALLERY_MEDIA_PATH'), '/');
    }

    public function blog()
    {
        return $this->belongsTo(Blogs::class, 'blog_id');
    }

    /**
     * Stored file name on disk (path column holds filename only).
     */
    public function storedFileName(): string
    {
        $path = (string) $this->path;

        // Legacy rows may still hold a full relative path.
        if (strpos($path, '/') !== false) {
            return basename($path);
        }

        return $path;
    }

    /**
     * Relative URL path under public/: base/blogId/filename
     */
    public function relativeUrlPath(): string
    {
        return static::galleryBasePath() . '/' . $this->storedFileName();
    }

    public function fullDiskPath(): string
    {
        if ($this->disk === 'media') return Storage::disk('media')->path($this->path);
        return public_path($this->relativeUrlPath());
    }

    public function getUrlAttribute()
    {
        if ($this->disk === 'media') return Storage::disk('media')->url($this->path);
        return asset($this->relativeUrlPath());
    }

    public function isVideo()
    {
        return strpos((string) $this->mime, 'video/') === 0;
    }

    public function isImage()
    {
        return strpos((string) $this->mime, 'image/') === 0;
    }

    public function displayName()
    {
        return $this->original_name ?: ($this->file_name ?: $this->storedFileName());
    }
}

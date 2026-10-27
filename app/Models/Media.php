<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';
    protected $fillable = ['blog_id', 'original_name', 'file_name', 'disk', 'path', 'mime', 'extension', 'size', 'width', 'height', 'alt_text', 'title', 'folder', 'created_by', 'thumbnail_path'];

    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function getUrlAttribute(): string { return Storage::disk($this->disk)->url($this->path); }
    public function getThumbnailUrlAttribute(): string { return Storage::disk($this->disk)->url($this->thumbnail_path ?: $this->path); }
}

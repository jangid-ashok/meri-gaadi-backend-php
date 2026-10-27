<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    private const MIME_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif', 'image/gif' => 'gif'];

    public function store(UploadedFile $file, int $userId, ?string $directory = null, ?string $disk = null, string $field = 'file', ?int $blogId = null, ?int $maxKb = null): Media
    {
        $maxKb ??= (int) config('media.max_image_kb', 5120);
        if ($file->getSize() > $maxKb * 1024) {
            $limitMb = max(1, (int) ceil($maxKb / 1024));
            throw ValidationException::withMessages([$field => "Image must be {$limitMb} MB or smaller."]);
        }
        $mime = strtolower((string) $file->getMimeType());
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        $clientExtension = strtolower($file->getClientOriginalExtension());
        $image = @getimagesize($file->getRealPath());
        $extensionMatches = $extension && ($clientExtension === $extension || ($extension === 'jpg' && $clientExtension === 'jpeg'));
        if (! $extensionMatches || ! $image || ($image['mime'] ?? null) !== $mime) {
            throw ValidationException::withMessages([$field => 'Upload a valid JPG, PNG, GIF, WebP or AVIF image.']);
        }
        [$width, $height] = [$image[0] ?? 0, $image[1] ?? 0];
        $max = config('media.max_dimension', 20000);
        if ($width < 1 || $height < 1 || $width > $max || $height > $max || ($width * $height) > config('media.max_pixels', 120000000)) {
            throw ValidationException::withMessages([$field => 'Image dimensions exceed the configured limit.']);
        }

        $disk ??= config('media.disk', 'media');
        $directory ??= 'images/'.now()->format('Y/m');
        $name = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $name, $disk);
        if (! $path) throw new \RuntimeException('The image could not be stored.');

        try {
            return Media::create([
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_name' => $name, 'disk' => $disk, 'path' => $path,
                'mime' => $mime, 'extension' => $extension, 'size' => $file->getSize(),
                'width' => $width, 'height' => $height, 'created_by' => $userId,
                'blog_id' => $blogId,
            ]);
        } catch (\Throwable $e) { Storage::disk($disk)->delete($path); throw $e; }
    }

    public function deleteIfUnreferenced(Media $media): bool
    {
        if ($this->usageReferences($media)) return false;
        Storage::disk($media->disk)->delete(array_filter([$media->path, $media->thumbnail_path]));
        $media->delete();
        return true;
    }

    /** Remove a file created by this request if the database write that uses it failed. */
    public function cleanupNewUpload(string $path, string $disk = 'media'): void
    {
        $media = Media::where('disk', $disk)->where('path', $path)->first();
        if ($media) {
            $this->deleteIfUnreferenced($media);
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    public function usageReferences(Media $media): array
    {
        $path = $media->path;
        $references = [];
        if (\App\Models\FeaturedContent::where('media_id', $media->id)->exists()) $references[] = 'Featured content';
        if (\App\Models\SeoMeta::where('og_image_id', $media->id)->orWhere('twitter_image_id', $media->id)->exists()) $references[] = 'SEO metadata';
        if (\App\Models\HomepageSection::query()->whereJsonContains('config->media_id', (int) $media->id)->exists()) $references[] = 'Homepage section';
        if ($media->blog_id) {
            $blog = \App\Models\Admin\Blogs::find($media->blog_id);
            $references[] = 'Blog gallery'.($blog ? ': '.$blog->title : '');
        }
        if (in_array($media->disk, ['media', 'brand_media'], true)) {
            $references = array_merge($references,
                \App\Models\Brand::where('logo', $path)->pluck('name')->map(fn ($name) => 'Brand: '.$name)->all(),
                \App\Models\Admin\Blogs::where('featured_image', $path)->pluck('title')->map(fn ($title) => 'Article: '.$title)->all()
            );
        }
        if (in_array($media->disk, ['media', 'car_media'], true)) {
            $references = array_merge($references, \App\Models\CarModel::where('thumbnail', $path)->pluck('name')->map(fn ($name) => 'Car: '.$name)->all());
        }
        if (in_array($media->disk, ['media', 'public'], true)) {
            $references = array_merge($references, \App\Models\User::where('profile_image', $path)->pluck('name')->map(fn ($name) => 'User profile: '.$name)->all());
        }
        if (in_array($media->disk, ['media', 'blog_category_media'], true)) {
            $references = array_merge($references, \App\Models\Admin\BlogCategory::where('image', $path)->orWhere('image', basename($path))->pluck('title')->map(fn ($title) => 'Blog category: '.$title)->all());
        }
        if ($media->disk === 'media') {
            $references = array_merge($references, \App\Models\Category::where('image', $path)->pluck('name')->map(fn ($name) => 'Category: '.$name)->all());
        }
        return array_values(array_unique($references));
    }

    public function replace(Media $media, UploadedFile $file, int $userId): Media
    {
        $oldThumbnailPath = $media->thumbnail_path;
        $temporary = $this->store($file, $userId, dirname($media->path) === '.' ? '' : dirname($media->path), $media->disk);
        $oldPath = $media->path;
        try {
            DB::transaction(function () use ($media, $temporary, $oldPath) {
                \App\Models\Brand::where('logo', $oldPath)->update(['logo' => $temporary->path]);
                \App\Models\CarModel::where('thumbnail', $oldPath)->update(['thumbnail' => $temporary->path]);
                \App\Models\Admin\Blogs::where('featured_image', $oldPath)->update(['featured_image' => $temporary->path]);
                \App\Models\Admin\BlogCategory::where('image', $oldPath)->update(['image' => $temporary->path]);
                \App\Models\User::where('profile_image', $oldPath)->update(['profile_image' => $temporary->path]);
                $media->update([
                    'original_name' => $temporary->original_name,
                    'file_name' => $temporary->file_name,
                    'path' => $temporary->path,
                    'mime' => $temporary->mime,
                    'extension' => $temporary->extension,
                    'size' => $temporary->size,
                    'width' => $temporary->width,
                    'height' => $temporary->height,
                    'thumbnail_path' => null,
                ]);
                $temporary->delete();
            });
        } catch (\Throwable $exception) {
            $temporaryRecord = Media::find($temporary->id);
            if ($temporaryRecord) $this->deleteIfUnreferenced($temporaryRecord);
            else Storage::disk($temporary->disk)->delete($temporary->path);
            throw $exception;
        }
        $oldReference = clone $media;
        $oldReference->path = $oldPath;
        $oldReference->blog_id = null;
        if (! $this->usageReferences($oldReference) && ! Media::where('disk', $media->disk)->where('path', $oldPath)->exists()) {
            Storage::disk($media->disk)->delete($oldPath);
        }
        if ($oldThumbnailPath) Storage::disk($media->disk)->delete($oldThumbnailPath);
        return $media->refresh();
    }

    public function deleteGalleryItem(Media $media): void
    {
        $media->blog_id = null;
        $media->save();
        if ($this->usageReferences($media)) return;
        Storage::disk($media->disk)->delete(array_filter([$media->path, $media->thumbnail_path]));
        $media->delete();
    }
}

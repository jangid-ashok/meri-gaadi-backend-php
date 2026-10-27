<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $this->validateFilters($request);
        $media = $this->paginateRequest($request, $this->mediaQuery($request));
        $media->getCollection()->transform(fn (Media $item) => $this->resource($item));
        return response()->json(['data' => $media]);
    }

    public function library(Request $request)
    {
        $this->validateFilters($request);
        return view('admin.media.index', [
            'media' => $this->paginateRequest($request, $this->mediaQuery($request)),
            'page_title' => 'Media Library',
            'leftMenuActive' => 'media',
        ]);
    }

    public function store(Request $request, MediaService $service)
    {
        $request->validate(['file' => ['required', 'file', 'max:'.config('media.max_image_kb', 5120)]]);
        $item = $service->store($request->file('file'), (int) $request->user()->id)->load('uploader:id,name');
        return response()->json(['data' => $this->resource($item)], 201);
    }

    public function show(Media $medium) { return response()->json(['data' => $this->resource($medium->load('uploader:id,name'))]); }

    public function update(Request $request, Media $medium)
    {
        $medium->update($request->validate(['alt_text' => ['nullable', 'string', 'max:255'], 'title' => ['nullable', 'string', 'max:255']]));
        return response()->json(['data' => $this->resource($medium->fresh()->load('uploader:id,name'))]);
    }

    public function replace(Request $request, Media $medium, \App\Services\MediaService $service)
    {
        abort_unless(str_starts_with((string) $medium->mime, 'image/'), 422, 'Only image files can be replaced in the Media Library.');
        $request->validate(['file' => ['required', 'file', 'max:'.config('media.max_image_kb', 5120)]]);
        $item = $service->replace($medium, $request->file('file'), (int) $request->user()->id)->load('uploader:id,name');
        return response()->json(['data' => $this->resource($item)]);
    }

    public function destroy(Media $medium, \App\Services\MediaService $service)
    {
        $references = $service->usageReferences($medium);
        if ($references || ! $service->deleteIfUnreferenced($medium)) {
            return response()->json(['message' => 'This image is in use and cannot be deleted.', 'references' => $references], 409);
        }
        return response()->noContent();
    }

    private function resource(Media $item): array
    {
        return ['id' => $item->id, 'original_name' => $item->original_name ?: $item->file_name, 'file_name' => $item->file_name, 'url' => $item->url, 'thumbnail_url' => $item->thumbnail_url, 'mime_type' => $item->mime, 'extension' => $item->extension, 'size' => $item->size, 'width' => $item->width, 'height' => $item->height, 'alt_text' => $item->alt_text, 'title' => $item->title, 'uploaded_by' => $item->uploader?->name, 'created_at' => $item->created_at?->toISOString()];
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', Rule::in(['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'disk' => ['nullable', Rule::in(['media', 'brand_media', 'car_media', 'blog_category_media', 'public'])],
            'selectable' => ['nullable', 'boolean'],
        ]);
    }

    private function mediaQuery(Request $request)
    {
        return Media::query()->with('uploader:id,name')
            ->where('mime', 'like', 'image/%')
            ->when($request->boolean('selectable'), fn ($q) => $q->whereNull('blog_id')->whereNotNull('created_by'))
            ->when($request->filled('disk'), fn ($q) => $q->where('disk', $request->input('disk')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('original_name', 'like', '%'.addcslashes($request->input('search'), '%_').'%')->orWhere('file_name', 'like', '%'.addcslashes($request->input('search'), '%_').'%')))
            ->when($request->filled('format'), fn ($q) => $q->where('extension', $request->input('format')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest();
    }
}

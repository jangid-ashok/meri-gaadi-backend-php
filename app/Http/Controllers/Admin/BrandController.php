<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = $this->paginateRequest($request, $this->query($request));

        if ($request->expectsJson()) {
            return response()->json(['data' => $brands]);
        }

        return view('admin.brands.index', [
            'brands' => $brands,
            'page_title' => 'Brands',
            'leftMenuActive' => 'brands',
        ]);
    }

    public function create()
    {
        return view('admin.brands.form', [
            'brand' => new Brand(),
            'page_title' => 'Add Brand',
            'leftMenuActive' => 'brands',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $mediaId = $data['logo_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        unset($data['logo_media_id']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $uploadedLogo = $request->hasFile('logo')
            ? $this->storeLogo($request, app(MediaService::class))
            : null;
        $data['logo'] = $request->hasFile('logo')
            ? $uploadedLogo
            : ($mediaId ? Media::where('disk', 'media')->findOrFail($mediaId)->path : null);

        try {
            $brand = Brand::create($data);
        } catch (\Throwable $exception) {
            if ($uploadedLogo) app(MediaService::class)->cleanupNewUpload($uploadedLogo);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $brand], 201);
        }

        return redirect()->route('admin.brands.show', $brand)->with('status', 'Brand created successfully.');
    }

    public function show(Brand $brand)
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => $brand]);
        }

        return view('admin.brands.show', [
            'brand' => $brand,
            'page_title' => $brand->name,
            'leftMenuActive' => 'brands',
        ]);
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.form', [
            'brand' => $brand,
            'page_title' => 'Edit Brand',
            'leftMenuActive' => 'brands',
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request, $brand);
        $mediaId = $data['logo_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        unset($data['logo_media_id']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $brand);

        $uploadedLogo = null;
        if ($request->hasFile('logo') || $mediaId) {
            $oldLogo = $brand->logo;
            $uploadedLogo = $request->hasFile('logo') ? $this->storeLogo($request, app(MediaService::class)) : null;
            $data['logo'] = $request->hasFile('logo')
                ? $uploadedLogo
                : Media::where('disk', 'media')->findOrFail($mediaId)->path;
        }

        try {
            $brand->update($data);
        } catch (\Throwable $exception) {
            if ($uploadedLogo) app(MediaService::class)->cleanupNewUpload($uploadedLogo);
            throw $exception;
        }
        if (isset($oldLogo) && $oldLogo) $this->deleteLogo($oldLogo);

        if ($request->expectsJson()) {
            return response()->json(['data' => $brand->fresh()]);
        }

        return redirect()->route('admin.brands.show', $brand)->with('status', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $logo = $brand->logo;
        $brand->delete();
        $this->deleteLogo($logo);

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.brands.index')->with('status', 'Brand deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = Brand::query()->search($request->string('search')->toString());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = $request->input('direction', 'asc');
        $allowedSorts = ['sort_order', 'name', 'created_at', 'status'];

        if (in_array($sort, $allowedSorts, true)) {
            return $query->orderBy($sort, in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc')
                ->when($sort !== 'name', fn ($query) => $query->orderBy('name'));
        }

        return $query->ordered();
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand)],
            'description' => ['nullable', 'string', 'max:5000'],
            'country' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . now()->year],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo' => ['nullable', 'file', 'max:'.config('media.max_image_kb', 5120)],
            'logo_media_id' => ['nullable', Rule::exists('media', 'id')->where(fn ($query) => $query->where('disk', 'media')->whereNull('blog_id')->whereNotNull('created_by'))],
        ]);

        return $data;
    }

    private function uniqueSlug(string $name, ?Brand $brand = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 2;

        while (Brand::where('slug', $slug)->when($brand, fn ($query) => $query->where('id', '!=', $brand->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function storeLogo(Request $request, MediaService $mediaService): ?string
    {
        return $request->hasFile('logo') ? $mediaService->store($request->file('logo'), (int) $request->user()->id, null, 'media', 'logo')->path : null;
    }

    private function deleteLogo(?string $logo): void
    {
        if ($logo) {
            $media = Media::where('disk', 'media')->where('path', $logo)->first();
            if (! $media) $media = Media::where('disk', 'brand_media')->where('path', $logo)->first();
            if ($media) app(MediaService::class)->deleteIfUnreferenced($media);
            else Storage::disk('brand_media')->delete($logo);
        }
    }
}

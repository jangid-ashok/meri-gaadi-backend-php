<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'category_type' => ['nullable', Rule::in(array_keys(config('constants.CATEGORY_TYPES')))],
            'parent_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value === 'root') {
                    return;
                }

                if (! filter_var($value, FILTER_VALIDATE_INT) || ! Category::whereKey((int) $value)->exists()) {
                    $fail('The selected parent category is invalid.');
                }
            }],
        ]);

        $categories = $this->paginateRequest($request, $this->query($request)->with('parent:id,name'));

        if ($request->expectsJson()) {
            return response()->json(['data' => $categories]);
        }

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::active()->ordered()->get(['id', 'name', 'category_type', 'parent_id']),
            'categoryTypes' => config('constants.CATEGORY_TYPES'),
            'page_title' => 'Categories',
            'leftMenuActive' => 'categories',
        ]);
    }

    public function active(Request $request)
    {
        $request->validate([
            'category_type' => ['nullable', Rule::in(array_keys(config('constants.CATEGORY_TYPES')))],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('status', 'active')],
        ]);

        $categories = Category::query()->active()->with('parent:id,name')->when(
            $request->filled('category_type'),
            fn ($query) => $query->where('category_type', $request->input('category_type'))
        )->when(
            $request->has('parent_id'),
            fn ($query) => $request->filled('parent_id')
                ? $query->where('parent_id', $request->integer('parent_id'))
                : $query->root()
        )->ordered()->get();

        return response()->json(['data' => $categories]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['category_type' => 'automotive', 'status' => 'active', 'sort_order' => 0]),
            'parents' => $this->availableParents(),
            'categoryTypes' => config('constants.CATEGORY_TYPES'),
            'page_title' => 'Add Category',
            'leftMenuActive' => 'categories',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        unset($data['image'], $data['remove_image']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $uploadedImagePath = $this->storeImage($request);
        if ($uploadedImagePath) {
            $data['image'] = $uploadedImagePath;
        }

        try {
            $category = Category::create($data)->load('parent:id,name');
        } catch (\Throwable $exception) {
            if ($uploadedImagePath) app(MediaService::class)->cleanupNewUpload($uploadedImagePath);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $category], 201);
        }

        return redirect()->route('admin.categories.show', $category)->with('status', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        $category->load('parent:id,name');

        if (request()->expectsJson()) {
            return response()->json(['data' => $category->loadCount('children')]);
        }

        return view('admin.categories.show', [
            'category' => $category->loadCount('children'),
            'page_title' => $category->name,
            'leftMenuActive' => 'categories',
        ]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => $this->availableParents($category),
            'categoryTypes' => config('constants.CATEGORY_TYPES'),
            'page_title' => 'Edit Category',
            'leftMenuActive' => 'categories',
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        unset($data['image'], $data['remove_image']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $category);

        $oldImagePath = $category->getRawOriginal('image');
        $uploadedImagePath = $this->storeImage($request);
        if ($uploadedImagePath) {
            $data['image'] = $uploadedImagePath;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        try {
            $category->update($data);
        } catch (\Throwable $exception) {
            if ($uploadedImagePath) app(MediaService::class)->cleanupNewUpload($uploadedImagePath);
            throw $exception;
        }

        if ($oldImagePath && ($uploadedImagePath || $request->boolean('remove_image'))) {
            $this->deleteImageIfUnreferenced($oldImagePath);
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $category->fresh()->load('parent:id,name')->loadCount('children')]);
        }

        return redirect()->route('admin.categories.show', $category)->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            abort(409, 'This category has child categories and cannot be deleted.');
        }

        $imagePath = $category->getRawOriginal('image');
        $category->delete();
        if ($imagePath) {
            $this->deleteImageIfUnreferenced($imagePath);
        }

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = Category::query()->search($request->string('search')->toString());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_type')) {
            $query->where('category_type', $request->input('category_type'));
        }

        if ($request->has('parent_id')) {
            if ($request->input('parent_id') === 'root' || ! $request->filled('parent_id')) {
                $query->root();
            } else {
                $query->where('parent_id', $request->integer('parent_id'));
            }
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'name', 'created_at', 'status', 'category_type'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $types = array_keys(config('constants.CATEGORY_TYPES'));
        $categoryType = $request->input('category_type', $category?->category_type ?? 'automotive');
        $nameRule = Rule::unique('categories', 'name')
            ->where(fn ($query) => $query->where('category_type', $categoryType));

        if ($category) {
            $nameRule->ignore($category);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('The name field must not be empty.');
                }
            }, $nameRule],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('status', 'active')],
            'category_type' => [$category ? 'sometimes' : 'required', 'string', Rule::in($types)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif', 'max:'.config('media.max_image_kb', 5120)],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data['category_type'] ??= $categoryType;

        if ($category && (int) ($data['parent_id'] ?? 0) === $category->id) {
            abort(422, 'A category cannot be its own parent.');
        }

        if ($category && $category->children()->exists() && $category->category_type !== $data['category_type']) {
            abort(422, 'A category with children cannot change category type.');
        }

        if (! empty($data['parent_id'])) {
            $parent = Category::findOrFail($data['parent_id']);
            if ($parent->category_type !== $data['category_type']) {
                abort(422, 'The parent category must use the same category type.');
            }
            if ($category && $this->isDescendantOf($parent, $category)) {
                abort(422, 'A category cannot use one of its descendants as its parent.');
            }
        }

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return app(MediaService::class)->store(
            $request->file('image'),
            (int) $request->user()->id,
            null,
            'media',
            'image'
        )->path;
    }

    private function deleteImageIfUnreferenced(string $path): void
    {
        $media = Media::where('disk', 'media')->where('path', $path)->first();
        if ($media) {
            app(MediaService::class)->deleteIfUnreferenced($media);
        }
    }

    private function uniqueSlug(string $name, ?Category $category = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::withTrashed()->where('slug', $slug)->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function availableParents(?Category $category = null)
    {
        $excluded = $category ? collect([$category->id]) : collect();

        if ($category) {
            $pending = [$category->id];
            while ($pending) {
                $childIds = Category::whereIn('parent_id', $pending)->pluck('id')->all();
                $excluded = $excluded->merge($childIds);
                $pending = $childIds;
            }
        }

        return Category::active()->whereNotIn('id', $excluded->unique())->ordered()->get(['id', 'name', 'category_type', 'parent_id']);
    }

    private function isDescendantOf(Category $candidate, Category $category): bool
    {
        $seen = [];
        while ($candidate->parent_id) {
            if (in_array($candidate->parent_id, $seen, true)) {
                return true;
            }
            if ((int) $candidate->parent_id === $category->id) {
                return true;
            }
            $seen[] = (int) $candidate->parent_id;
            $candidate = Category::find($candidate->parent_id);
            if (! $candidate) {
                break;
            }
        }

        return false;
    }
}
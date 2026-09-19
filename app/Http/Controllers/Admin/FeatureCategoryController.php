<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $this->query($request)->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => $categories]);
        }

        return view('admin.feature-categories.index', [
            'categories' => $categories,
            'page_title' => 'Feature Categories',
            'leftMenuActive' => 'feature-categories',
        ]);
    }

    public function create()
    {
        return view('admin.feature-categories.form', [
            'category' => new FeatureCategory(),
            'page_title' => 'Add Feature Category',
            'leftMenuActive' => 'feature-categories',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $category = FeatureCategory::create($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $category], 201);
        }

        return redirect()->route('admin.feature-categories.show', $category)->with('status', 'Feature category created successfully.');
    }

    public function show(FeatureCategory $featureCategory)
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => $featureCategory]);
        }

        return view('admin.feature-categories.show', [
            'category' => $featureCategory,
            'page_title' => $featureCategory->name,
            'leftMenuActive' => 'feature-categories',
        ]);
    }

    public function edit(FeatureCategory $featureCategory)
    {
        return view('admin.feature-categories.form', [
            'category' => $featureCategory,
            'page_title' => 'Edit Feature Category',
            'leftMenuActive' => 'feature-categories',
        ]);
    }

    public function update(Request $request, FeatureCategory $featureCategory)
    {
        $data = $this->validated($request, $featureCategory);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $featureCategory);

        $featureCategory->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $featureCategory->fresh()]);
        }

        return redirect()->route('admin.feature-categories.show', $featureCategory)->with('status', 'Feature category updated successfully.');
    }

    public function destroy(FeatureCategory $featureCategory)
    {
        if ($featureCategory->features()->exists()) {
            abort(409, 'This category has features and cannot be deleted.');
        }

        $featureCategory->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.feature-categories.index')->with('status', 'Feature category deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = FeatureCategory::query()->search($request->string('search')->toString());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'name', 'created_at', 'status'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function validated(Request $request, ?FeatureCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('feature_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name, ?FeatureCategory $category = null): string
    {
        $base = Str::slug($name) ?: 'feature-category';
        $slug = $base;
        $suffix = 2;

        while (FeatureCategory::where('slug', $slug)->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}

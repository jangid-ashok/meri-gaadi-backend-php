<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureController extends Controller
{
    public function index(Request $request)
    {
        $features = $this->query($request)->with('category')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => $features]);
        }

        return view('admin.features.index', [
            'features' => $features,
            'categories' => FeatureCategory::ordered()->get(['id', 'name', 'status']),
            'page_title' => 'Features',
            'leftMenuActive' => 'features',
        ]);
    }

    public function create()
    {
        return view('admin.features.form', $this->formData(new Feature(), 'Add Feature'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['category_id'], $data['name']);

        $feature = Feature::create($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $feature->load('category')], 201);
        }

        return redirect()->route('admin.features.show', $feature)->with('status', 'Feature created successfully.');
    }

    public function show(Feature $feature)
    {
        $feature->load('category');

        if (request()->expectsJson()) {
            return response()->json(['data' => $feature]);
        }

        return view('admin.features.show', [
            'feature' => $feature,
            'page_title' => $feature->name,
            'leftMenuActive' => 'features',
        ]);
    }

    public function edit(Feature $feature)
    {
        return view('admin.features.form', $this->formData($feature, 'Edit Feature'));
    }

    public function update(Request $request, Feature $feature)
    {
        $data = $this->validated($request, $feature);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['category_id'], $data['name'], $feature);

        $feature->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $feature->fresh()->load('category')]);
        }

        return redirect()->route('admin.features.show', $feature)->with('status', 'Feature updated successfully.');
    }

    public function destroy(Feature $feature)
    {
        if ($feature->variantFeatures()->exists()) {
            abort(409, 'This feature is already assigned to variants and cannot be deleted.');
        }

        $feature->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.features.index')->with('status', 'Feature deleted successfully.');
    }

    public function byCategory(Request $request)
    {
        $request->validate(['category_id' => ['required', 'integer', Rule::exists('feature_categories', 'id')]]);

        $features = Feature::where('category_id', $request->integer('category_id'))
            ->active()
            ->ordered()
            ->get(['id', 'category_id', 'name', 'slug', 'status']);

        return response()->json(['data' => $features]);
    }

    private function query(Request $request)
    {
        $query = Feature::query()->search($request->string('search')->toString());

        foreach (['category_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'name', 'created_at', 'status'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function formData(Feature $feature, string $title): array
    {
        return [
            'feature' => $feature,
            'categories' => FeatureCategory::ordered()->get(['id', 'name', 'status']),
            'page_title' => $title,
            'leftMenuActive' => 'features',
        ];
    }

    private function validated(Request $request, ?Feature $feature = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('feature_categories', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('features', 'name')->where(fn ($query) => $query->where('category_id', $request->integer('category_id')))->ignore($feature)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(int $categoryId, string $name, ?Feature $feature = null): string
    {
        $base = Str::slug($name) ?: 'feature';
        $slug = $base;
        $suffix = 2;

        while (Feature::where('category_id', $categoryId)->where('slug', $slug)->when($feature, fn ($query) => $query->whereKeyNot($feature->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}

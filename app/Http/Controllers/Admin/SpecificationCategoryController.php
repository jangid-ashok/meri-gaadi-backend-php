<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecificationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpecificationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $this->query($request)->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => $categories]);
        }

        return view('admin.specification-categories.index', [
            'categories' => $categories,
            'page_title' => 'Specification Categories',
            'leftMenuActive' => 'specification-categories',
        ]);
    }

    public function create()
    {
        return view('admin.specification-categories.form', [
            'category' => new SpecificationCategory(),
            'page_title' => 'Add Specification Category',
            'leftMenuActive' => 'specification-categories',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $category = SpecificationCategory::create($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $category], 201);
        }

        return redirect()->route('admin.specification-categories.show', $category)->with('status', 'Specification category created successfully.');
    }

    public function show(SpecificationCategory $specificationCategory)
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => $specificationCategory]);
        }

        return view('admin.specification-categories.show', [
            'category' => $specificationCategory,
            'page_title' => $specificationCategory->name,
            'leftMenuActive' => 'specification-categories',
        ]);
    }

    public function edit(SpecificationCategory $specificationCategory)
    {
        return view('admin.specification-categories.form', [
            'category' => $specificationCategory,
            'page_title' => 'Edit Specification Category',
            'leftMenuActive' => 'specification-categories',
        ]);
    }

    public function update(Request $request, SpecificationCategory $specificationCategory)
    {
        $data = $this->validated($request, $specificationCategory);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $specificationCategory);

        $specificationCategory->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $specificationCategory->fresh()]);
        }

        return redirect()->route('admin.specification-categories.show', $specificationCategory)->with('status', 'Specification category updated successfully.');
    }

    public function destroy(SpecificationCategory $specificationCategory)
    {
        if ($specificationCategory->definitions()->exists()) {
            abort(409, 'This category has specification definitions and cannot be deleted.');
        }

        $specificationCategory->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.specification-categories.index')->with('status', 'Specification category deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = SpecificationCategory::query()->search($request->string('search')->toString());

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

    private function validated(Request $request, ?SpecificationCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('specification_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name, ?SpecificationCategory $category = null): string
    {
        $base = Str::slug($name) ?: 'specification-category';
        $slug = $base;
        $suffix = 2;

        while (SpecificationCategory::where('slug', $slug)->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = $this->query($request)->paginate(15)->withQueryString();

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
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['logo'] = $this->storeLogo($request);

        $brand = Brand::create($data);

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
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['name'], $brand);

        if ($request->hasFile('logo')) {
            $this->deleteLogo($brand->logo);
            $data['logo'] = $this->storeLogo($request);
        }

        $brand->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $brand->fresh()]);
        }

        return redirect()->route('admin.brands.show', $brand)->with('status', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $this->deleteLogo($brand->logo);
        $brand->delete();

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
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        if ($request->hasFile('logo') && strtolower($request->file('logo')->getClientOriginalExtension()) === 'svg') {
            $contents = file_get_contents($request->file('logo')->getRealPath());
            abort_if((bool) preg_match('/<script|javascript:|on[a-z]+\s*=/i', $contents), 422, 'The logo contains unsafe SVG content.');
        }

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

    private function storeLogo(Request $request): ?string
    {
        return $request->hasFile('logo') ? $request->file('logo')->store('brand-logos', 'brand_media') : null;
    }

    private function deleteLogo(?string $logo): void
    {
        if ($logo) {
            Storage::disk('brand_media')->delete($logo);
        }
    }
}
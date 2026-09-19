<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VariantController extends Controller
{
    public function index(Request $request)
    {
        $variants = $this->query($request)->with('carModel.brand')->paginate(15)->withQueryString();

        if ($request->expectsJson()) return response()->json(['data' => $variants]);

        return view('admin.variants.index', [
            'variants' => $variants,
            'brands' => Brand::ordered()->get(['id', 'name']),
            'models' => CarModel::with('brand')->ordered()->get(['id', 'brand_id', 'name']),
            'page_title' => 'Variants',
            'leftMenuActive' => 'variants',
        ]);
    }

    public function create()
    {
        return view('admin.variants.form', $this->formData(new Variant(), 'Add Variant'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['variant_code'] = isset($data['variant_code']) ? trim($data['variant_code']) ?: null : null;
        $data['slug'] = $this->uniqueSlug($data['car_model_id'], $data['name']);
        unset($data['brand_id']);

        $variant = Variant::create($data);

        if ($request->expectsJson()) return response()->json(['data' => $variant->load('carModel.brand')], 201);
        return redirect()->route('admin.variants.show', $variant)->with('status', 'Variant created successfully.');
    }

    public function show(Variant $variant)
    {
        $variant->load('carModel.brand');
        if (request()->expectsJson()) return response()->json(['data' => $variant]);
        return view('admin.variants.show', ['variant' => $variant, 'page_title' => $variant->full_name, 'leftMenuActive' => 'variants']);
    }

    public function edit(Variant $variant)
    {
        return view('admin.variants.form', $this->formData($variant, 'Edit Variant'));
    }

    public function update(Request $request, Variant $variant)
    {
        $data = $this->validated($request, $variant);
        $data['name'] = trim($data['name']);
        $data['variant_code'] = isset($data['variant_code']) ? trim($data['variant_code']) ?: null : null;
        $data['slug'] = $this->uniqueSlug($data['car_model_id'], $data['name'], $variant);
        unset($data['brand_id']);

        $variant->update($data);

        if ($request->expectsJson()) return response()->json(['data' => $variant->fresh()->load('carModel.brand')]);
        return redirect()->route('admin.variants.show', $variant)->with('status', 'Variant updated successfully.');
    }

    public function destroy(Variant $variant)
    {
        $variant->delete();
        if (request()->expectsJson()) return response()->noContent();
        return redirect()->route('admin.variants.index')->with('status', 'Variant deleted successfully.');
    }

    public function models(Request $request)
    {
        $request->validate(['brand_id' => ['required', 'integer', Rule::exists('brands', 'id')]]);
        $models = CarModel::where('brand_id', $request->integer('brand_id'))->ordered()->get(['id', 'brand_id', 'name', 'status']);
        return response()->json(['data' => $models]);
    }

    private function query(Request $request)
    {
        $query = Variant::query()->search($request->string('search')->toString());
        foreach (['car_model_id', 'status'] as $filter) {
            if ($request->filled($filter)) $query->where($filter, $request->input($filter));
        }
        if ($request->filled('brand_id')) {
            $query->whereHas('carModel', fn ($query) => $query->where('brand_id', $request->input('brand_id')));
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        return in_array($sort, ['sort_order', 'name', 'variant_code', 'created_at', 'status'], true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function formData(Variant $variant, string $title): array
    {
        $variant->loadMissing('carModel.brand');
        return [
            'variant' => $variant,
            'brands' => Brand::ordered()->get(['id', 'name']),
            'models' => $variant->car_model_id
                ? CarModel::where('brand_id', $variant->carModel?->brand_id)->ordered()->get(['id', 'brand_id', 'name', 'status'])
                : collect(),
            'page_title' => $title,
            'leftMenuActive' => 'variants',
        ];
    }

    private function validated(Request $request, ?Variant $variant = null): array
    {
        $data = $request->validate([
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'car_model_id' => ['required', 'integer', Rule::exists('car_models', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('variants', 'name')->where(fn ($query) => $query->where('car_model_id', $request->input('car_model_id')))->ignore($variant)],
            'description' => ['nullable', 'string', 'max:10000'],
            'variant_code' => [
                'nullable', 'string', 'max:100',
                Rule::unique('variants', 'variant_code')->where(fn ($query) => $query->where('car_model_id', $request->input('car_model_id')))->ignore($variant),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! CarModel::whereKey($data['car_model_id'])->where('brand_id', $data['brand_id'])->exists()) {
            throw ValidationException::withMessages(['car_model_id' => 'The selected model does not belong to the selected brand.']);
        }

        $data['sort_order'] ??= 0;
        return $data;
    }

    private function uniqueSlug(int $carModelId, string $name, ?Variant $variant = null): string
    {
        $base = Str::slug($name) ?: 'variant';
        $slug = $base;
        $suffix = 2;
        while (Variant::where('car_model_id', $carModelId)->where('slug', $slug)->when($variant, fn ($query) => $query->where('id', '!=', $variant->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}
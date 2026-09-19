<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarModelController extends Controller
{
    public function index(Request $request)
    {
        $cars = $this->query($request)->with('brand:id,name,logo')->paginate(15)->withQueryString();
        if ($request->expectsJson()) return response()->json(['data' => $cars]);
        return view('admin.cars.index', [
            'cars' => $cars, 'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'bodyTypes' => CarModel::BODY_TYPES, 'page_title' => 'Cars / Models', 'leftMenuActive' => 'cars',
        ]);
    }

    public function create()
    {
        return view('admin.cars.form', $this->formData(new CarModel(), 'Add Car / Model'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['brand_id'], $data['name']);
        $data['thumbnail'] = $this->storeThumbnail($request);
        $car = CarModel::create($data);
        if ($request->expectsJson()) return response()->json(['data' => $car->load('brand')], 201);
        return redirect()->route('admin.cars.show', $car)->with('status', 'Car model created successfully.');
    }

    public function show(CarModel $car)
    {
        $car->load('brand');
        if (request()->expectsJson()) return response()->json(['data' => $car]);
        return view('admin.cars.show', ['car' => $car, 'page_title' => $car->name, 'leftMenuActive' => 'cars']);
    }

    public function edit(CarModel $car)
    {
        return view('admin.cars.form', $this->formData($car, 'Edit Car / Model'));
    }

    public function update(Request $request, CarModel $car)
    {
        $data = $this->validated($request, $car);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['brand_id'], $data['name'], $car);
        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($car->thumbnail);
            $data['thumbnail'] = $this->storeThumbnail($request);
        }
        $car->update($data);
        if ($request->expectsJson()) return response()->json(['data' => $car->fresh()->load('brand')]);
        return redirect()->route('admin.cars.show', $car)->with('status', 'Car model updated successfully.');
    }

    public function destroy(CarModel $car)
    {
        $this->deleteThumbnail($car->thumbnail);
        $car->delete();
        if (request()->expectsJson()) return response()->noContent();
        return redirect()->route('admin.cars.index')->with('status', 'Car model deleted successfully.');
    }

    private function query(Request $request)
    {
        $query = CarModel::query()->search($request->string('search')->toString());
        foreach (['brand_id', 'status', 'body_type'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->input($filter));
        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        return in_array($sort, ['sort_order', 'name', 'launch_year', 'created_at', 'status'], true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function formData(CarModel $car, string $title): array
    {
        return ['car' => $car, 'brands' => Brand::orderBy('name')->get(['id', 'name']), 'bodyTypes' => CarModel::BODY_TYPES, 'fuelTypes' => CarModel::FUEL_TYPES, 'transmissionTypes' => CarModel::TRANSMISSION_TYPES, 'page_title' => $title, 'leftMenuActive' => 'cars'];
    }

    private function validated(Request $request, ?CarModel $car = null): array
    {
        $data = $request->validate([
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'launch_year' => ['nullable', 'integer', 'min:1886', 'max:'.now()->year],
            'discontinued_year' => ['nullable', 'integer', 'min:1886', 'max:'.now()->year, 'gte:launch_year'],
            'body_type' => ['nullable', Rule::in(CarModel::BODY_TYPES)],
            'fuel_types' => ['nullable', 'array'], 'fuel_types.*' => [Rule::in(CarModel::FUEL_TYPES)],
            'transmission_types' => ['nullable', 'array'], 'transmission_types.*' => [Rule::in(CarModel::TRANSMISSION_TYPES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,avif'],
        ]);

        if ($request->hasFile('thumbnail') && strtolower($request->file('thumbnail')->getClientOriginalExtension()) === 'svg') {
            $contents = file_get_contents($request->file('thumbnail')->getRealPath());
            abort_if((bool) preg_match('/<script|javascript:|on[a-z]+\s*=/i', $contents), 422, 'The thumbnail contains unsafe SVG content.');
        }

        $data['fuel_types'] = $request->input('fuel_types', []);
        $data['transmission_types'] = $request->input('transmission_types', []);

        return $data;
    }

    private function uniqueSlug(int $brandId, string $name, ?CarModel $car = null): string
    {
        $base = Str::slug($name) ?: 'model'; $slug = $base; $suffix = 2;
        while (CarModel::where('brand_id', $brandId)->where('slug', $slug)->when($car, fn ($query) => $query->where('id', '!=', $car->id))->exists()) $slug = $base.'-'.($suffix++);
        return $slug;
    }

    private function storeThumbnail(Request $request): ?string
    {
        return $request->hasFile('thumbnail') ? $request->file('thumbnail')->store('car-thumbnails', 'car_media') : null;
    }

    private function deleteThumbnail(?string $path): void
    {
        if ($path) Storage::disk('car_media')->delete($path);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarUseCase;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarModelController extends Controller
{
    public function index(Request $request)
    {
        $cars = $this->paginateRequest($request, $this->query($request)->with('brand:id,name,logo'));
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
        $mediaId = $data['thumbnail_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        unset($data['thumbnail_media_id']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['brand_id'], $data['name']);
        $useCaseIds = $this->useCaseIds($data['use_cases'] ?? []);
        unset($data['use_cases']);
        $uploadedThumbnail = $request->hasFile('thumbnail')
            ? $this->storeThumbnail($request, app(MediaService::class))
            : null;
        $data['thumbnail'] = $request->hasFile('thumbnail')
            ? $uploadedThumbnail
            : ($mediaId ? Media::where('disk', 'media')->findOrFail($mediaId)->path : null);
        try {
            $car = DB::transaction(function () use ($data, $useCaseIds) {
                $car = CarModel::create($data);
                $car->useCases()->sync($useCaseIds);

                return $car;
            });
        } catch (\Throwable $exception) {
            if ($uploadedThumbnail) app(MediaService::class)->cleanupNewUpload($uploadedThumbnail);
            throw $exception;
        }
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
        $mediaId = $data['thumbnail_media_id'] ?? null;
        abort_unless(! $mediaId || $request->user()->hasPermission('media.view'), 403);
        unset($data['thumbnail_media_id']);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['brand_id'], $data['name'], $car);
        $syncUseCases = array_key_exists('use_cases', $data) || $request->boolean('use_cases_present');
        $useCaseIds = $syncUseCases ? $this->useCaseIds($data['use_cases'] ?? []) : [];
        unset($data['use_cases']);
        $oldThumbnail = null;
        $uploadedThumbnail = null;
        if ($request->hasFile('thumbnail') || $mediaId) {
            $oldThumbnail = $car->thumbnail;
            $uploadedThumbnail = $request->hasFile('thumbnail') ? $this->storeThumbnail($request, app(MediaService::class)) : null;
            $data['thumbnail'] = $request->hasFile('thumbnail')
                ? $uploadedThumbnail
                : Media::where('disk', 'media')->findOrFail($mediaId)->path;
        }
        try {
            DB::transaction(function () use ($car, $data, $useCaseIds, $syncUseCases) {
                $car->update($data);
                if ($syncUseCases) {
                    $car->useCases()->sync($useCaseIds);
                }
            });
        } catch (\Throwable $exception) {
            if ($uploadedThumbnail) app(MediaService::class)->cleanupNewUpload($uploadedThumbnail);
            throw $exception;
        }
        if ($oldThumbnail) $this->deleteThumbnail($oldThumbnail);
        if ($request->expectsJson()) return response()->json(['data' => $car->fresh()->load('brand')]);
        return redirect()->route('admin.cars.show', $car)->with('status', 'Car model updated successfully.');
    }

    public function destroy(CarModel $car)
    {
        $thumbnail = $car->thumbnail;
        $car->delete();
        $this->deleteThumbnail($thumbnail);
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
        $car->loadMissing('useCases');

        return [
            'car' => $car,
            'selectedUseCases' => $car->useCases->pluck('slug')->all(),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'bodyTypes' => CarModel::BODY_TYPES,
            'fuelTypes' => CarModel::FUEL_TYPES,
            'transmissionTypes' => CarModel::TRANSMISSION_TYPES,
            'lifestyleUseCases' => array_diff_key(config('constants.CAR_USE_CASES'), ['electric' => true]),
            'page_title' => $title,
            'leftMenuActive' => 'cars',
        ];
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
            'use_cases' => ['nullable', 'array'],
            'use_cases.*' => ['required', 'string', Rule::in(array_diff(array_keys(config('constants.CAR_USE_CASES')), ['electric']))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'thumbnail' => ['nullable', 'file', 'max:'.config('media.max_image_kb', 5120)],
            'thumbnail_media_id' => ['nullable', Rule::exists('media', 'id')->where(fn ($query) => $query->where('disk', 'media')->whereNull('blog_id')->whereNotNull('created_by'))],
        ]);

        $data['fuel_types'] = $request->input('fuel_types', []);
        $data['transmission_types'] = $request->input('transmission_types', []);
        if ($request->exists('use_cases') || $request->boolean('use_cases_present')) {
            $data['use_cases'] = array_values(array_unique($request->input('use_cases') ?? []));
        }

        return $data;
    }

    private function useCaseIds(array $slugs): array
    {
        return CarUseCase::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function uniqueSlug(int $brandId, string $name, ?CarModel $car = null): string
    {
        $base = Str::slug($name) ?: 'model'; $slug = $base; $suffix = 2;
        while (CarModel::where('brand_id', $brandId)->where('slug', $slug)->when($car, fn ($query) => $query->where('id', '!=', $car->id))->exists()) $slug = $base.'-'.($suffix++);
        return $slug;
    }

    private function storeThumbnail(Request $request, MediaService $mediaService): ?string
    {
        return $request->hasFile('thumbnail') ? $mediaService->store($request->file('thumbnail'), (int) $request->user()->id, null, 'media', 'thumbnail')->path : null;
    }

    private function deleteThumbnail(?string $path): void
    {
        if (! $path) return;
        $media = Media::where('disk', 'media')->where('path', $path)->first();
        if ($media) app(MediaService::class)->deleteIfUnreferenced($media);
        else Storage::disk('car_media')->delete($path);
    }
}

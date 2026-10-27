<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\Variant;
use App\Models\VariantFeature;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VariantFeatureController extends Controller
{
    public function index(Request $request, Variant $variant)
    {
        $variant->load(['carModel.brand']);
        $features = VariantFeature::query()->with('feature.category')
            ->where('variant_id', $variant->id)
            ->get()
            ->groupBy(fn ($feature) => $feature->feature?->category?->id);

        $categories = FeatureCategory::with(['features' => fn ($query) => $query->active()->ordered()])
            ->active()
            ->ordered()
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['data' => ['variant' => $variant, 'categories' => $categories, 'values' => $features]]);
        }

        return view('admin.variant-features.index', [
            'variant' => $variant,
            'categories' => $categories,
            'values' => $features,
            'page_title' => 'Edit Features',
            'leftMenuActive' => 'variants',
        ]);
    }

    public function store(Request $request, Variant $variant)
    {
        $payloads = $this->validatedBulk($request);
        $data = $payloads
            ->map(fn ($payload) => [
                'variant_id' => $variant->id,
                'feature_id' => (int) $payload['feature_id'],
                'value' => $payload['value'],
            ])
            ->values()
            ->all();

        if ($data === []) {
            throw ValidationException::withMessages(['features' => 'At least one feature value is required.']);
        }

        $rows = [];
        foreach ($data as $payload) {
            $rows[] = [
                'variant_id' => $payload['variant_id'],
                'feature_id' => $payload['feature_id'],
                'value' => $payload['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        VariantFeature::query()->insertOrIgnore($rows);

        return response()->json(['data' => VariantFeature::query()->where('variant_id', $variant->id)->with('feature.category')->get()]);
    }

    public function update(Request $request, Variant $variant, VariantFeature $feature)
    {
        abort_if($feature->variant_id !== $variant->id, 404);
        $value = $this->validateSingleValue($request->all(), $feature->feature_id, $feature->value);

        $feature->update(['value' => $value]);

        return response()->json(['data' => $feature->fresh()->load('feature.category')]);
    }

    public function destroy(Request $request, Variant $variant, VariantFeature $feature)
    {
        abort_if($feature->variant_id !== $variant->id, 404);
        $feature->delete();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.variants.show', $variant)->with('status', 'Feature deleted successfully.');
    }

    private function validatedBulk(Request $request): \Illuminate\Support\Collection
    {
        $payloads = $request->all();
        if (! is_array($payloads) || ! array_is_list($payloads)) {
            throw ValidationException::withMessages(['features' => 'Feature values must be provided as a list.']);
        }
        if ($payloads === [] || count($payloads) > 100) throw ValidationException::withMessages(['features' => 'Provide between 1 and 100 feature values.']);

        $rules = [];
        foreach ($payloads as $index => $entry) {
            $rules[$index] = ['required', 'array'];
            $rules[$index . '.feature_id'] = ['required', 'integer'];
            $rules[$index . '.value'] = ['required', 'string', 'max:255'];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($payloads, $rules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $ids = collect($payloads)->pluck('feature_id')->map(fn ($id) => (int) $id);
        $definitions = Feature::with('category')->whereIn('id', $ids)->get()->keyBy('id');
        $features = collect($payloads);
        $seen = [];
        foreach ($features as $index => $entry) {
            $key = (int) $entry['feature_id'];
            $feature = $definitions->get($key);
            if (! $feature) throw ValidationException::withMessages([$index.'.feature_id' => 'The selected feature does not exist.']);
            $normalized = $this->normalizeValue($feature, $entry['value'], true);
            if ($normalized === null) throw ValidationException::withMessages([$index.'.value' => 'The feature value is invalid.']);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    $index . '.feature_id' => 'Duplicate feature selected for this variant.',
                ]);
            }
            $seen[$key] = true;
            $payloads[$index]['value'] = $normalized;
        }

        return collect($payloads);
    }

    private function validateSingleValue(array $payload, int $featureId, ?string $existing = null): string
    {
        $feature = Feature::findOrFail($featureId);
        $value = $payload['value'] ?? $existing;
        $normalized = $this->normalizeValue($feature, $value, true);

        if ($normalized === null) {
            throw ValidationException::withMessages(['value' => 'The feature value is invalid.']);
        }

        return $normalized;
    }

    private function normalizeValue(Feature $feature, $value, bool $throw = false): ?string
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            if ($throw) {
                return null;
            }
            return '';
        }

        $normalized = strtolower($stringValue);
        if (in_array($normalized, ['true', 'false', '1', '0', 'yes', 'no'], true)) {
            return in_array($normalized, ['true', '1', 'yes'], true) ? 'true' : 'false';
        }

        if (! in_array($normalized, ['true', 'false'], true)) {
            return $throw ? null : $stringValue;
        }

        return $stringValue;
    }
}

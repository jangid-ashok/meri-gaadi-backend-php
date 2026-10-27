<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecificationCategory;
use App\Models\SpecificationDefinition;
use App\Models\Variant;
use App\Models\VariantSpecification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VariantSpecificationController extends Controller
{
    public function index(Request $request, Variant $variant)
    {
        $variant->load(['carModel.brand']);
        $specs = VariantSpecification::query()->with('specificationDefinition.category')
            ->where('variant_id', $variant->id)
            ->get()
            ->groupBy(fn ($spec) => $spec->specificationDefinition?->category?->id);

        $categories = SpecificationCategory::with(['definitions' => fn ($query) => $query->active()->ordered()])
            ->active()
            ->ordered()
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['data' => ['variant' => $variant, 'categories' => $categories, 'values' => $specs]]);
        }

        return view('admin.variant-specifications.index', [
            'variant' => $variant,
            'categories' => $categories,
            'values' => $specs,
            'page_title' => 'Edit Specifications',
            'leftMenuActive' => 'variants',
        ]);
    }

    public function store(Request $request, Variant $variant)
    {
        $payloads = $this->validatedBulk($request);
        $data = $payloads
            ->map(fn ($payload) => [
                'variant_id' => $variant->id,
                'specification_definition_id' => (int) $payload['specification_definition_id'],
                'value' => $payload['value'],
            ])
            ->values()
            ->all();

        if ($data === []) {
            throw ValidationException::withMessages(['specifications' => 'At least one specification value is required.']);
        }

        $rows = [];
        foreach ($data as $payload) {
            $rows[] = [
                'variant_id' => $payload['variant_id'],
                'specification_definition_id' => $payload['specification_definition_id'],
                'value' => $payload['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        VariantSpecification::query()->insertOrIgnore($rows);

        return response()->json(['data' => VariantSpecification::query()->where('variant_id', $variant->id)->with('specificationDefinition.category')->get()]);
    }

    public function update(Request $request, Variant $variant, VariantSpecification $specification)
    {
        abort_if($specification->variant_id !== $variant->id, 404);
        $value = $this->validateSingleValue($request->all(), $specification->specification_definition_id, $specification->value);

        $specification->update(['value' => $value]);

        return response()->json(['data' => $specification->fresh()->load('specificationDefinition.category')]);
    }

    public function destroy(Request $request, Variant $variant, VariantSpecification $specification)
    {
        abort_if($specification->variant_id !== $variant->id, 404);
        $specification->delete();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.variants.show', $variant)->with('status', 'Specification deleted successfully.');
    }

    private function validatedBulk(Request $request): \Illuminate\Support\Collection
    {
        $payloads = $request->all();
        if (! is_array($payloads) || ! array_is_list($payloads)) {
            throw ValidationException::withMessages(['specifications' => 'Specification values must be provided as a list.']);
        }
        if ($payloads === [] || count($payloads) > 100) throw ValidationException::withMessages(['specifications' => 'Provide between 1 and 100 specification values.']);

        $rules = [];
        foreach ($payloads as $index => $entry) {
            $rules[$index] = ['required', 'array'];
            $rules[$index.'.specification_definition_id'] = ['required', 'integer'];
            $rules[$index.'.value'] = ['required', 'string', 'max:255'];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($payloads, $rules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $ids = collect($payloads)->pluck('specification_definition_id')->map(fn ($id) => (int) $id);
        $definitions = SpecificationDefinition::with('category')->whereIn('id', $ids)->get()->keyBy('id');
        $specs = collect($payloads);
        $seen = [];
        foreach ($specs as $index => $entry) {
            $key = (int) $entry['specification_definition_id'];
            $definition = $definitions->get($key);
            if (! $definition) throw ValidationException::withMessages([$index.'.specification_definition_id' => 'The selected specification does not exist.']);
            $normalized = $this->normalizeValue($definition, $entry['value'], true);
            if ($normalized === null) throw ValidationException::withMessages([$index.'.value' => 'The specification value is invalid for its data type.']);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    $index.'.specification_definition_id' => 'Duplicate specification definition selected for this variant.',
                ]);
            }
            $seen[$key] = true;
            $payloads[$index]['value'] = $normalized;
        }

        return collect($payloads);
    }

    private function validateSingleValue(array $payload, int $definitionId, ?string $existing = null): string
    {
        $definition = SpecificationDefinition::findOrFail($definitionId);
        $value = $payload['value'] ?? $existing;
        $normalized = $this->normalizeValue($definition, $value, true);

        if ($normalized === null) {
            throw ValidationException::withMessages(['value' => 'The specification value is invalid for its data type.']);
        }

        return $normalized;
    }

    private function normalizeValue(SpecificationDefinition $definition, $value, bool $throw = false): ?string
    {
        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            if ($throw) {
                return null;
            }
            return '';
        }

        switch ($definition->data_type) {
            case 'number':
                $valid = preg_match('/^-?\d+$/', $stringValue) === 1;
                return $valid ? (string) (int) $stringValue : null;
            case 'decimal':
                $valid = preg_match('/^-?\d+(\.\d+)?$/', $stringValue) === 1;
                return $valid ? (string) $stringValue : null;
            case 'boolean':
                $valid = in_array(strtolower($stringValue), ['true', 'false', '1', '0', 'yes', 'no'], true);
                return $valid ? (in_array(strtolower($stringValue), ['true', '1', 'yes'], true) ? 'true' : 'false') : null;
            case 'select':
                $options = array_map(fn ($option) => trim((string) $option), $definition->options ?? []);
                $valid = in_array($stringValue, $options, true);
                return $valid ? $stringValue : null;
            case 'text':
                return $stringValue;
            default:
                return null;
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecificationCategory;
use App\Models\SpecificationDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpecificationDefinitionController extends Controller
{
    public function index(Request $request)
    {
        $definitions = $this->query($request)->with('category')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['data' => $definitions]);
        }

        return view('admin.specification-definitions.index', [
            'definitions' => $definitions,
            'categories' => SpecificationCategory::ordered()->get(['id', 'name', 'status']),
            'page_title' => 'Specification Definitions',
            'leftMenuActive' => 'specification-definitions',
        ]);
    }

    public function create()
    {
        return view('admin.specification-definitions.form', $this->formData(new SpecificationDefinition(), 'Add Specification Definition'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['category_id'], $data['name']);

        $definition = SpecificationDefinition::create($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $definition->load('category')], 201);
        }

        return redirect()->route('admin.specification-definitions.show', $definition)->with('status', 'Specification definition created successfully.');
    }

    public function show(SpecificationDefinition $specificationDefinition)
    {
        $specificationDefinition->load('category');

        if (request()->expectsJson()) {
            return response()->json(['data' => $specificationDefinition]);
        }

        return view('admin.specification-definitions.show', [
            'definition' => $specificationDefinition,
            'page_title' => $specificationDefinition->name,
            'leftMenuActive' => 'specification-definitions',
        ]);
    }

    public function edit(SpecificationDefinition $specificationDefinition)
    {
        return view('admin.specification-definitions.form', $this->formData($specificationDefinition, 'Edit Specification Definition'));
    }

    public function update(Request $request, SpecificationDefinition $specificationDefinition)
    {
        $data = $this->validated($request, $specificationDefinition);
        $data['name'] = trim($data['name']);
        $data['slug'] = $this->uniqueSlug($data['category_id'], $data['name'], $specificationDefinition);

        $specificationDefinition->update($data);

        if ($request->expectsJson()) {
            return response()->json(['data' => $specificationDefinition->fresh()->load('category')]);
        }

        return redirect()->route('admin.specification-definitions.show', $specificationDefinition)->with('status', 'Specification definition updated successfully.');
    }

    public function destroy(SpecificationDefinition $specificationDefinition)
    {
        if ($specificationDefinition->variantSpecifications()->exists()) {
            abort(409, 'This specification definition is already assigned to variants and cannot be deleted.');
        }

        $specificationDefinition->delete();

        if (request()->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.specification-definitions.index')->with('status', 'Specification definition deleted successfully.');
    }

    public function byCategory(Request $request)
    {
        $request->validate(['category_id' => ['required', 'integer', Rule::exists('specification_categories', 'id')]]);

        $definitions = SpecificationDefinition::where('category_id', $request->integer('category_id'))
            ->active()
            ->ordered()
            ->get(['id', 'category_id', 'name', 'slug', 'data_type', 'unit', 'options', 'status']);

        return response()->json(['data' => $definitions]);
    }

    private function query(Request $request)
    {
        $query = SpecificationDefinition::query()->search($request->string('search')->toString());

        foreach (['category_id', 'data_type', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = in_array($request->input('direction'), ['asc', 'desc'], true) ? $request->input('direction') : 'asc';
        $allowed = ['sort_order', 'name', 'created_at', 'status', 'data_type'];

        return in_array($sort, $allowed, true)
            ? $query->orderBy($sort, $direction)->when($sort !== 'name', fn ($query) => $query->orderBy('name'))
            : $query->ordered();
    }

    private function formData(SpecificationDefinition $definition, string $title): array
    {
        return [
            'definition' => $definition,
            'categories' => SpecificationCategory::ordered()->get(['id', 'name', 'status']),
            'dataTypes' => SpecificationDefinition::DATA_TYPES,
            'page_title' => $title,
            'leftMenuActive' => 'specification-definitions',
        ];
    }

    private function validated(Request $request, ?SpecificationDefinition $definition = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('specification_categories', 'id')],
            'name' => ['required', 'string', 'max:255', Rule::unique('specification_definitions', 'name')->where(fn ($query) => $query->where('category_id', $request->integer('category_id')))->ignore($definition)],
            'description' => ['nullable', 'string', 'max:5000'],
            'data_type' => ['required', Rule::in(SpecificationDefinition::DATA_TYPES)],
            'unit' => ['nullable', 'string', 'max:50'],
            'options' => ['nullable', 'array'],
            'options.*' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (($data['data_type'] ?? null) === 'select') {
            $options = collect($data['options'] ?? [])->map(fn ($option) => trim((string) $option))->filter()->values()->all();
            if (count($options) < 1) {
                throw ValidationException::withMessages(['options' => 'Select fields must include at least one option.']);
            }
            $data['options'] = $options;
        } else {
            $data['options'] = null;
        }

        return $data;
    }

    private function uniqueSlug(int $categoryId, string $name, ?SpecificationDefinition $definition = null): string
    {
        $base = Str::slug($name) ?: 'specification-definition';
        $slug = $base;
        $suffix = 2;

        while (SpecificationDefinition::where('category_id', $categoryId)->where('slug', $slug)->when($definition, fn ($query) => $query->whereKeyNot($definition->getKey()))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}

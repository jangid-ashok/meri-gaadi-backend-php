@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div><h1 class="h3 mb-1">{{ $definition->exists ? 'Edit definition' : 'Add definition' }}</h1><p class="text-body-secondary mb-0">Create a reusable specification field for a category.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.specification-definitions.index') }}">Back to definitions</a>
    </div>
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $definition->exists ? route('admin.specification-definitions.update', $definition) : route('admin.specification-definitions.store') }}" class="row g-4">
                @csrf
                @if ($definition->exists) @method('PUT') @endif
                <div class="col-md-6"><label class="form-label" for="category_id">Category <span class="text-danger">*</span></label><select class="form-select" id="category_id" name="category_id" required><option value="">Select a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $definition->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="name">Name <span class="text-danger">*</span></label><input class="form-control" id="name" name="name" value="{{ old('name', $definition->name) }}" required maxlength="255"></div>
                <div class="col-md-3"><label class="form-label" for="data_type">Data type <span class="text-danger">*</span></label><select class="form-select" id="data_type" name="data_type" required>@foreach($dataTypes as $type)<option value="{{ $type }}" @selected(old('data_type', $definition->data_type ?: 'text') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label" for="unit">Unit</label><input class="form-control" id="unit" name="unit" value="{{ old('unit', $definition->unit) }}" maxlength="50"></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $definition->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $definition->status) === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3"><label class="form-label" for="sort_order">Sort order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $definition->sort_order ?? 0) }}" min="0"></div>
                <div class="col-12" id="options-wrapper" style="display: {{ old('data_type', $definition->data_type) === 'select' ? 'block' : 'none' }};">
                    <label class="form-label" for="options">Options</label>
                    <textarea class="form-control" id="options" name="options" rows="4" placeholder="One option per line">{{ old('options', is_array($definition->options) ? implode("\n", $definition->options) : '') }}</textarea>
                    <div class="form-text">Only used for select fields. Add one option per line.</div>
                </div>
                <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $definition->description) }}</textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">{{ $definition->exists ? 'Save changes' : 'Create definition' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.specification-definitions.index') }}">Cancel</a></div>
            </form>
        </div>
    </div>
</main>
<script>
const dataType = document.getElementById('data_type');
const optionsWrapper = document.getElementById('options-wrapper');
const toggleOptions = () => {
    const show = dataType.value === 'select';
    optionsWrapper.style.display = show ? 'block' : 'none';
};
dataType.addEventListener('change', toggleOptions);
toggleOptions();
</script>
@endsection

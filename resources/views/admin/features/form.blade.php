@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div><h1 class="h3 mb-1">{{ $feature->exists ? 'Edit feature' : 'Add feature' }}</h1><p class="text-body-secondary mb-0">Create a reusable feature for a category.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.features.index') }}">Back to features</a>
    </div>
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $feature->exists ? route('admin.features.update', $feature) : route('admin.features.store') }}" class="row g-4">
                @csrf
                @if ($feature->exists) @method('PUT') @endif
                <div class="col-md-6"><label class="form-label" for="category_id">Category <span class="text-danger">*</span></label><select class="form-select" id="category_id" name="category_id" required><option value="">Select a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $feature->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="name">Name <span class="text-danger">*</span></label><input class="form-control" id="name" name="name" value="{{ old('name', $feature->name) }}" required maxlength="255"></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $feature->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $feature->status) === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3"><label class="form-label" for="sort_order">Sort order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $feature->sort_order ?? 0) }}" min="0"></div>
                <div class="col-md-6"></div>
                <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $feature->description) }}</textarea></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">{{ $feature->exists ? 'Save changes' : 'Create feature' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.features.index') }}">Cancel</a></div>
            </form>
        </div>
    </div>
</main>
@endsection

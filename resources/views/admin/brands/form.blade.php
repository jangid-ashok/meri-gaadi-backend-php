@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6"><div><h1 class="h3 mb-1">{{ $brand->exists ? 'Edit brand' : 'Add brand' }}</h1><p class="text-body-secondary mb-0">Brand slugs are generated automatically from the name.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.brands.index') }}">Back to brands</a></div>
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="row g-4">
            @csrf @if ($brand->exists) @method('PUT') @endif
            <div class="col-md-6"><label class="form-label" for="name">Name <span class="text-danger">*</span></label><input class="form-control" id="name" name="name" value="{{ old('name', $brand->name) }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label" for="country">Country</label><input class="form-control" id="country" name="country" value="{{ old('country', $brand->country) }}" maxlength="120"></div>
            <div class="col-md-8"><label class="form-label" for="website">Website</label><input class="form-control" id="website" type="url" name="website" value="{{ old('website', $brand->website) }}" placeholder="https://example.com"></div>
            <div class="col-md-4"><label class="form-label" for="founded_year">Founded year</label><input class="form-control" id="founded_year" type="number" name="founded_year" value="{{ old('founded_year', $brand->founded_year) }}" min="1800" max="{{ now()->year }}"></div>
            <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $brand->description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="logo">Logo</label><input class="form-control" id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml"><div class="form-text">JPG, PNG, WEBP, or SVG up to 2 MB.</div>@if ($brand->logo)<img class="mt-3 rounded border" src="{{ \Illuminate\Support\Facades\Storage::disk('brand_media')->url($brand->logo) }}" alt="{{ $brand->name }} logo" style="max-width: 160px; max-height: 100px">@endif</div>
            <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="active" @selected(old('status', $brand->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $brand->status) === 'inactive')>Inactive</option></select></div>
            <div class="col-md-3"><label class="form-label" for="sort_order">Sort order</label><input class="form-control" id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $brand->sort_order ?? 0) }}" min="0"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">{{ $brand->exists ? 'Save changes' : 'Create brand' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.brands.index') }}">Cancel</a></div>
        </form>
    </div></div>
</main>
@endsection

@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div><h1 class="h3 mb-1">Categories</h1><p class="text-body-secondary mb-0">Organize automotive and future content data.</p></div>
        @if (Auth::user()->hasPermission('categories.create'))<a class="btn btn-primary" href="{{ route('admin.categories.create') }}"><i class="bx bx-plus me-1"></i>Add category</a>@endif
    </div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, slug, or parent"></div>
                <div class="col-md-2"><label class="form-label" for="category_type">Type</label><select class="form-select" id="category_type" name="category_type"><option value="">All types</option>@foreach($categoryTypes as $value => $label)<option value="{{ $value }}" @selected(request('category_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3"><label class="form-label" for="parent_id">Parent</label><select class="form-select" id="parent_id" name="parent_id"><option value="">All roots and children</option><option value="root" @selected(request('parent_id') === 'root')>Root categories only</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string) request('parent_id') === (string) $parent->id)>{{ $parent->name }} ({{ $categoryTypes[$parent->category_type] ?? $parent->category_type }})</option>@endforeach</select></div>
                <div class="col-md-2 d-flex flex-wrap gap-2"><button class="btn btn-primary flex-fill" type="submit">Filter</button><a class="btn btn-outline-secondary flex-fill" href="{{ route('admin.categories.index') }}">Clear</a></div>
            </form>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Name</th><th>Parent</th><th>Slug</th><th>Type</th><th>Status</th><th>Sort</th><th>Created</th><th class="text-end">Actions</th></tr></thead><tbody>
            @forelse($categories as $category)<tr><td><span class="fw-semibold">{{ $category->name }}</span><div class="small text-body-secondary">{{ $category->description ?: '—' }}</div></td><td>{{ $category->parent?->name ?: 'Root' }}</td><td>{{ $category->slug }}</td><td>{{ $categoryTypes[$category->category_type] ?? $category->category_type }}</td><td><span class="badge bg-label-{{ $category->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($category->status) }}</span></td><td>{{ $category->sort_order }}</td><td>{{ $category->created_at?->format('d M Y') }}</td><td class="text-end text-nowrap"><a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('admin.categories.show', $category) }}" title="View {{ $category->name }}" aria-label="View {{ $category->name }}"><i class="bx bx-show"></i></a>@if(Auth::user()->hasPermission('categories.update'))<a class="btn btn-sm btn-icon btn-outline-primary ms-1" href="{{ route('admin.categories.edit', $category) }}" title="Edit {{ $category->name }}" aria-label="Edit {{ $category->name }}"><i class="bx bx-edit"></i></a>@endif @if(Auth::user()->hasPermission('categories.delete'))<form class="d-inline" method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete {{ $category->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger ms-1" type="submit" title="Delete {{ $category->name }}" aria-label="Delete {{ $category->name }}"><i class="bx bx-trash"></i></button></form>@endif</td></tr>@empty<tr><td colspan="8" class="text-center py-6"><h2 class="h5">No categories found</h2><p class="text-body-secondary mb-0">Try changing your filters or add the first category.</p></td></tr>@endforelse
        </tbody></table></div>
        @if($categories->total() > 0)<div class="card-footer">@include('admin.shared.pagination', ['paginator' => $categories])</div>@endif
    </div>
</main>
@endsection
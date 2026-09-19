@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">Feature Categories</h1>
            <p class="text-body-secondary mb-0">Group related feature definitions by vehicle topic.</p>
        </div>
        @if (Auth::user()->hasPermission('feature_categories.create'))
            <a class="btn btn-primary" href="{{ route('admin.feature-categories.create') }}"><i class="bx bx-plus me-1"></i>Add category</a>
        @endif
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.feature-categories.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name or slug"></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.feature-categories.index') }}">Clear</a></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Name</th><th>Slug</th><th>Status</th><th>Sort</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="fw-semibold">{{ $category->name }}</td>
                        <td>{{ $category->slug }}</td>
                        <td><span class="badge bg-label-{{ $category->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($category->status) }}</span></td>
                        <td>{{ $category->sort_order }}</td>
                        <td>{{ $category->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('admin.feature-categories.show', $category) }}" title="View {{ $category->name }}"><i class="bx bx-show"></i></a>
                            @if (Auth::user()->hasPermission('feature_categories.update'))
                                <a class="btn btn-sm btn-icon btn-outline-primary ms-1" href="{{ route('admin.feature-categories.edit', $category) }}" title="Edit {{ $category->name }}"><i class="bx bx-edit"></i></a>
                            @endif
                            @if (Auth::user()->hasPermission('feature_categories.delete'))
                                <form class="d-inline" method="POST" action="{{ route('admin.feature-categories.destroy', $category) }}" onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger ms-1" type="submit" title="Delete {{ $category->name }}"><i class="bx bx-trash"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6"><h2 class="h5">No categories found</h2><p class="text-body-secondary mb-0">Try changing your filters or create the first feature category.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
    </div>
</main>
@endsection

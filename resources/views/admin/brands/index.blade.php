@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div><h1 class="h3 mb-1">Brands</h1><p class="text-body-secondary mb-0">Manage automotive manufacturers available to the platform.</p></div>
        @if (Auth::user()->hasPermission('brands.create'))
            <a class="btn btn-primary" href="{{ route('admin.brands.create') }}"><i class="bx bx-plus me-1"></i>Add brand</a>
        @endif
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.brands.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, slug, or country"></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
                <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.brands.index') }}">Clear</a></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Brand</th><th>Slug</th><th>Country</th><th>Status</th><th>Sort</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td><div class="d-flex align-items-center gap-3"><div class="avatar avatar-sm">@if ($brand->logo)<img src="{{ \Illuminate\Support\Facades\Storage::disk('brand_media')->url($brand->logo) }}" alt="{{ $brand->name }} logo" class="rounded">@else<span class="avatar-initial rounded bg-label-primary">{{ strtoupper(substr($brand->name, 0, 1)) }}</span>@endif</div><span class="fw-semibold">{{ $brand->name }}</span></div></td>
                        <td>{{ $brand->slug }}</td><td>{{ $brand->country ?: '—' }}</td>
                        <td><span class="badge bg-label-{{ $brand->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($brand->status) }}</span></td>
                        <td>{{ $brand->sort_order }}</td><td>{{ $brand->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap"><a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('admin.brands.show', $brand) }}" title="View {{ $brand->name }}" aria-label="View {{ $brand->name }}"><i class="bx bx-show"></i></a>
                            @if (Auth::user()->hasPermission('brands.update'))<a class="btn btn-sm btn-icon btn-outline-primary ms-1" href="{{ route('admin.brands.edit', $brand) }}" title="Edit {{ $brand->name }}" aria-label="Edit {{ $brand->name }}"><i class="bx bx-edit"></i></a>@endif
                            @if (Auth::user()->hasPermission('brands.delete'))<form class="d-inline" method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('Delete {{ addslashes($brand->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger ms-1" type="submit" title="Delete {{ $brand->name }}" aria-label="Delete {{ $brand->name }}"><i class="bx bx-trash"></i></button></form>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6"><h2 class="h5">No brands found</h2><p class="text-body-secondary mb-0">Try changing your filters or add the first brand.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($brands->hasPages())<div class="card-footer">{{ $brands->links() }}</div>@endif
    </div>
</main>
@endsection

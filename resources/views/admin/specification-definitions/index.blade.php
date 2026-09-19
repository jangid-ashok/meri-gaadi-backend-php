@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">Specification Definitions</h1>
            <p class="text-body-secondary mb-0">Manage reusable specification fields such as engine displacement or airbags.</p>
        </div>
        @if (Auth::user()->hasPermission('specification_definitions.create'))
            <a class="btn btn-primary" href="{{ route('admin.specification-definitions.create') }}"><i class="bx bx-plus me-1"></i>Add definition</a>
        @endif
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.specification-definitions.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, slug, or unit"></div>
                <div class="col-md-3"><label class="form-label" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="data_type">Data type</label><select class="form-select" id="data_type" name="data_type"><option value="">All</option>@foreach(
App\Models\SpecificationDefinition::DATA_TYPES as $type)
<option value="{{ $type }}" @selected(request('data_type') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
                <div class="col-md-1 d-flex gap-2"><button class="btn btn-primary" type="submit">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Name</th><th>Category</th><th>Type</th><th>Unit</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($definitions as $definition)
                    <tr>
                        <td><div class="fw-semibold">{{ $definition->name }}</div><small class="text-body-secondary">{{ $definition->slug }}</small></td>
                        <td>{{ $definition->category?->name ?? '—' }}</td>
                        <td>{{ ucfirst($definition->data_type) }}</td>
                        <td>{{ $definition->unit ?: '—' }}</td>
                        <td><span class="badge bg-label-{{ $definition->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($definition->status) }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('admin.specification-definitions.show', $definition) }}" title="View {{ $definition->name }}"><i class="bx bx-show"></i></a>
                            @if (Auth::user()->hasPermission('specification_definitions.update'))
                                <a class="btn btn-sm btn-icon btn-outline-primary ms-1" href="{{ route('admin.specification-definitions.edit', $definition) }}" title="Edit {{ $definition->name }}"><i class="bx bx-edit"></i></a>
                            @endif
                            @if (Auth::user()->hasPermission('specification_definitions.delete'))
                                <form class="d-inline" method="POST" action="{{ route('admin.specification-definitions.destroy', $definition) }}" onsubmit="return confirm('Delete {{ addslashes($definition->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger ms-1" type="submit" title="Delete {{ $definition->name }}"><i class="bx bx-trash"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-6"><h2 class="h5">No specification definitions found</h2><p class="text-body-secondary mb-0">Try changing your filters or create the first specification definition.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($definitions->hasPages())<div class="card-footer">{{ $definitions->links() }}</div>@endif
    </div>
</main>
@endsection

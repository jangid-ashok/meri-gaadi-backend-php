@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">FAQs</h1>
            <p class="text-body-secondary mb-0">Manage frequently asked questions for content and automotive pages.</p>
        </div>
        @if (Auth::user()->hasPermission('faqs.create'))
            <a class="btn btn-primary" href="{{ route('admin.faqs.create') }}"><i class="bx bx-plus me-1"></i>Add FAQ</a>
        @endif
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.faqs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label" for="search">Search</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Question or answer"></div>
                <div class="col-md-3"><label class="form-label" for="category_id">Category</label><select class="form-select" id="category_id" name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
                <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" type="submit">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Question</th><th>Category</th><th>Answer Preview</th><th>Status</th><th>Sort</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($faqs as $faq)
                    <tr>
                        <td class="fw-semibold">{{ Str::limit($faq->question, 80) }}</td>
                        <td>{{ $faq->category?->name ?? '—' }}</td>
                        <td class="text-body-secondary" style="max-width: 300px;">{{ Str::limit(strip_tags($faq->answer), 90) }}</td>
                        <td><span class="badge bg-label-{{ $faq->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($faq->status) }}</span></td>
                        <td>{{ $faq->sort_order }}</td>
                        <td>{{ $faq->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('admin.faqs.show', $faq) }}" title="View {{ addslashes($faq->question) }}"><i class="bx bx-show"></i></a>
                            @if (Auth::user()->hasPermission('faqs.update'))
                                <a class="btn btn-sm btn-icon btn-outline-primary ms-1" href="{{ route('admin.faqs.edit', $faq) }}" title="Edit {{ addslashes($faq->question) }}"><i class="bx bx-edit"></i></a>
                            @endif
                            @if (Auth::user()->hasPermission('faqs.delete'))
                                <form class="d-inline" method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" data-confirm="Delete {{ $faq->question }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger ms-1" type="submit" title="Delete {{ addslashes($faq->question) }}"><i class="bx bx-trash"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-6"><h2 class="h5">No FAQs found</h2><p class="text-body-secondary mb-0">Try changing your filters or add the first FAQ.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($faqs->total() > 0)<div class="card-footer">@include('admin.shared.pagination', ['paginator' => $faqs])</div>@endif
    </div>
</main>
@endsection

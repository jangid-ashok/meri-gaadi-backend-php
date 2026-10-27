@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div><h1 class="h3 mb-1">FAQ details</h1><p class="text-body-secondary mb-0">Review full content and metadata.</p></div>
        <div class="d-flex gap-2">
            @if (Auth::user()->hasPermission('faqs.update'))
                <a class="btn btn-primary" href="{{ route('admin.faqs.edit', $faq) }}">Edit</a>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('admin.faqs.index') }}">Back</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12"><label class="form-label text-body-secondary">Question</label><div class="fw-semibold fs-5">{{ $faq->question }}</div></div>
                <div class="col-md-3"><label class="form-label text-body-secondary">Category</label><div>{{ $faq->category?->name ?? '—' }}</div></div>
                <div class="col-md-3"><label class="form-label text-body-secondary">Status</label><div><span class="badge bg-label-{{ $faq->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($faq->status) }}</span></div></div>
                <div class="col-md-3"><label class="form-label text-body-secondary">Sort order</label><div>{{ $faq->sort_order }}</div></div>
                <div class="col-md-3"><label class="form-label text-body-secondary">Slug</label><div>{{ $faq->slug ?: '—' }}</div></div>
                <div class="col-12"><label class="form-label text-body-secondary">Answer</label><div class="border rounded p-3 bg-light">{{ $faq->answer }}</div></div>
                <div class="col-md-6"><label class="form-label text-body-secondary">Created</label><div>{{ $faq->created_at?->format('d M Y, h:i A') }}</div></div>
                <div class="col-md-6"><label class="form-label text-body-secondary">Updated</label><div>{{ $faq->updated_at?->format('d M Y, h:i A') }}</div></div>
            </div>
        </div>
    </div>
</main>
@endsection

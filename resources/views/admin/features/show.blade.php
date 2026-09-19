@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="h3 mb-1">{{ $feature->name }}</h1>
            <p class="text-body-secondary mb-0">Feature details.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.features.index') }}">Back</a>
            @if (Auth::user()->hasPermission('features.update'))
                <a class="btn btn-primary" href="{{ route('admin.features.edit', $feature) }}">Edit</a>
            @endif
        </div>
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $feature->name }}</dd>
                <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $feature->category?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Slug</dt><dd class="col-sm-9">{{ $feature->slug }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($feature->status) }}</dd>
                <dt class="col-sm-3">Sort order</dt><dd class="col-sm-9">{{ $feature->sort_order }}</dd>
                <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $feature->description ?: '—' }}</dd>
                <dt class="col-sm-3">Created</dt><dd class="col-sm-9">{{ $feature->created_at?->format('d M Y H:i') }}</dd>
            </dl>
        </div>
    </div>
</main>
@endsection

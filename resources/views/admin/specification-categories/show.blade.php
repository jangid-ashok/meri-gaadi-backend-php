@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div><h1 class="h3 mb-1">{{ $category->name }}</h1><p class="text-body-secondary mb-0">Specification category details</p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.specification-categories.index') }}">Back</a>
            @if (Auth::user()->hasPermission('specification_categories.update'))
                <a class="btn btn-primary" href="{{ route('admin.specification-categories.edit', $category) }}">Edit</a>
            @endif
        </div>
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $category->name }}</dd>
        <dt class="col-sm-3">Slug</dt><dd class="col-sm-9">{{ $category->slug }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($category->status) }}</dd>
        <dt class="col-sm-3">Sort order</dt><dd class="col-sm-9">{{ $category->sort_order }}</dd>
        <dt class="col-sm-3">Created</dt><dd class="col-sm-9">{{ $category->created_at?->format('d M Y H:i') }}</dd>
        <dt class="col-sm-3">Updated</dt><dd class="col-sm-9">{{ $category->updated_at?->format('d M Y H:i') }}</dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $category->description ?: 'No description provided.' }}</dd>
    </dl></div></div>
</main>
@endsection

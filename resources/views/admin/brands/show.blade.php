@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6"><div><h1 class="h3 mb-1">{{ $brand->name }}</h1><p class="text-body-secondary mb-0">Brand details</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route('admin.brands.index') }}">Back</a>@if (Auth::user()->hasPermission('brands.update'))<a class="btn btn-primary" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>@endif</div></div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card"><div class="card-body"><div class="row g-5">
        <div class="col-md-3 text-center">@if ($brand->logo)<img class="img-fluid rounded border" src="{{ \Illuminate\Support\Facades\Storage::disk('brand_media')->url($brand->logo) }}" alt="{{ $brand->name }} logo">@else<div class="avatar avatar-xl mx-auto"><span class="avatar-initial rounded bg-label-primary">{{ strtoupper(substr($brand->name, 0, 1)) }}</span></div>@endif</div>
        <div class="col-md-9"><dl class="row mb-0"><dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $brand->name }}</dd><dt class="col-sm-4">Slug</dt><dd class="col-sm-8">{{ $brand->slug }}</dd><dt class="col-sm-4">Country</dt><dd class="col-sm-8">{{ $brand->country ?: '—' }}</dd><dt class="col-sm-4">Website</dt><dd class="col-sm-8">@if ($brand->website)<a href="{{ $brand->website }}" target="_blank" rel="noopener">{{ $brand->website }}</a>@else — @endif</dd><dt class="col-sm-4">Founded year</dt><dd class="col-sm-8">{{ $brand->founded_year ?: '—' }}</dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ ucfirst($brand->status) }}</dd><dt class="col-sm-4">Sort order</dt><dd class="col-sm-8">{{ $brand->sort_order }}</dd><dt class="col-sm-4">Created</dt><dd class="col-sm-8">{{ $brand->created_at?->format('d M Y H:i') }}</dd><dt class="col-sm-4">Updated</dt><dd class="col-sm-8">{{ $brand->updated_at?->format('d M Y H:i') }}</dd></dl></div>
        <div class="col-12"><h2 class="h5">Description</h2><p class="mb-0">{{ $brand->description ?: 'No description provided.' }}</p></div>
    </div></div></div>
</main>
@endsection

@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div><h1 class="h3 mb-1">{{ $definition->name }}</h1><p class="text-body-secondary mb-0">Specification definition details</p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.specification-definitions.index') }}">Back</a>
            @if (Auth::user()->hasPermission('specification_definitions.update'))
                <a class="btn btn-primary" href="{{ route('admin.specification-definitions.edit', $definition) }}">Edit</a>
            @endif
        </div>
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card"><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">Category</dt><dd class="col-sm-9">{{ $definition->category?->name ?? '—' }}</dd>
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $definition->name }}</dd>
        <dt class="col-sm-3">Slug</dt><dd class="col-sm-9">{{ $definition->slug }}</dd>
        <dt class="col-sm-3">Data type</dt><dd class="col-sm-9">{{ ucfirst($definition->data_type) }}</dd>
        <dt class="col-sm-3">Unit</dt><dd class="col-sm-9">{{ $definition->unit ?: '—' }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($definition->status) }}</dd>
        <dt class="col-sm-3">Sort order</dt><dd class="col-sm-9">{{ $definition->sort_order }}</dd>
        <dt class="col-sm-3">Options</dt><dd class="col-sm-9">{{ is_array($definition->options) && count($definition->options) ? implode(', ', $definition->options) : '—' }}</dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $definition->description ?: 'No description provided.' }}</dd>
    </dl></div></div>
</main>
@endsection

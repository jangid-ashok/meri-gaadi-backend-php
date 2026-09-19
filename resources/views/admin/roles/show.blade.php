@extends('admin.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h1 class="h4">{{ $role->name }}</h1><p>{{ $role->description }}</p><p><strong>Slug:</strong> {{ $role->slug }} | <strong>Users:</strong> {{ $role->users->count() }}</p><h2 class="h6 mt-4">Permissions</h2><ul>@forelse($role->permissions->groupBy('module') as $module => $permissions)<li><strong>{{ $module }}</strong>: {{ $permissions->pluck('slug')->implode(', ') }}</li>@empty<li>No permissions assigned.</li>@endforelse</ul><a class="btn btn-secondary" href="{{ route('admin.roles.index') }}">Back</a></div>
@endsection
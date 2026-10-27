@extends('admin.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h1 class="h4 mb-4">{{ $role->exists ? 'Edit role' : 'Add role' }}</h1><x-auth-validation-errors class="mb-4" :errors="$errors" />
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="card p-4">@csrf @if($role->exists) @method('PUT') @endif
<label class="form-label" for="name">Name</label><input class="form-control mb-3" id="name" name="name" value="{{ old('name', $role->name) }}" required>
<label class="form-label" for="slug">Slug</label><input class="form-control mb-3" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" required>
<label class="form-label" for="description">Description</label><textarea class="form-control mb-4" id="description" name="description">{{ old('description', $role->description) }}</textarea>
<div class="mb-4"><label class="form-label" for="role-permissions-trigger">Permissions</label>@include('admin.shared.multi-select', ['id'=>'role-permissions','name'=>'permissions','options'=>$permissions->map(fn($permission)=>['value'=>$permission->id,'label'=>Str::headline(str($permission->slug)->after('.')->toString()),'description'=>$permission->module]),'selected'=>$role->exists ? $role->permissions->modelKeys() : [],'placeholder'=>'Select permissions...','searchPlaceholder'=>'Search permissions...'])</div>
<button class="btn btn-primary" type="submit">Save role</button></form></div>
@endsection

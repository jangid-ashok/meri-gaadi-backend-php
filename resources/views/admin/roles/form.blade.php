@extends('admin.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y"><h1 class="h4 mb-4">{{ $role->exists ? 'Edit role' : 'Add role' }}</h1><x-auth-validation-errors class="mb-4" :errors="$errors" />
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="card p-4">@csrf @if($role->exists) @method('PUT') @endif
<label class="form-label" for="name">Name</label><input class="form-control mb-3" id="name" name="name" value="{{ old('name', $role->name) }}" required>
<label class="form-label" for="slug">Slug</label><input class="form-control mb-3" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" required>
<label class="form-label" for="description">Description</label><textarea class="form-control mb-4" id="description" name="description">{{ old('description', $role->description) }}</textarea>
@foreach ($permissions->groupBy('module') as $module => $modulePermissions)<fieldset class="mb-4"><legend class="h6">{{ $module }}</legend>@foreach ($modulePermissions as $permission)<label class="d-block"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $role->exists ? $role->permissions->modelKeys() : [])))> {{ Str::headline(str($permission->slug)->after('.')->toString()) }}</label>@endforeach</fieldset>@endforeach
<button class="btn btn-primary" type="submit">Save role</button></form></div>
@endsection
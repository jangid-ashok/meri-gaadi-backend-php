@extends('admin.layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h4 mb-0">Roles</h1><a class="btn btn-primary" href="{{ route('admin.roles.create') }}">Add role</a></div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Users</th><th>Permissions</th><th>System</th><th>Actions</th></tr></thead><tbody>
    @forelse ($roles as $role)<tr><td>{{ $role->name }}</td><td>{{ $role->slug }}</td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td>{{ $role->is_system ? 'Yes' : 'No' }}</td><td><a href="{{ route('admin.roles.show', $role) }}">View</a>@unless($role->is_system) <a class="ms-2" href="{{ route('admin.roles.edit', $role) }}">Edit</a><form class="d-inline ms-2" method="POST" action="{{ route('admin.roles.destroy', $role) }}">@csrf @method('DELETE')<button class="btn btn-link p-0" type="submit">Delete</button></form>@endunless</td></tr>@empty<tr><td colspan="6">No roles found.</td></tr>@endforelse
    </tbody></table></div></div>{{ $roles->links() }}
</div>
@endsection
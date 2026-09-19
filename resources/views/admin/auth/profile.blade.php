@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-6"><h1 class="h3 mb-1">Admin profile</h1><p class="text-body-secondary mb-0">Manage your account details.</p></div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="row g-4">
        @csrf @method('PUT')
        <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control" id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" value="{{ $user->email }}" disabled></div>
        <div class="col-md-6"><label class="form-label" for="profile_image">Profile image</label><input class="form-control" id="profile_image" type="file" name="profile_image" accept="image/*"></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Save profile</button></div>
    </form></div></div>
</main>
@endsection

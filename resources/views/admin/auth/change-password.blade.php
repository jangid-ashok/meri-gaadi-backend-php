@extends('admin.layouts.app')

@section('content')
<main class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-6"><h1 class="h3 mb-1">Change password</h1><p class="text-body-secondary mb-0">Keep your admin account secure.</p></div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <x-auth-validation-errors class="mb-4" :errors="$errors" />
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.password.change') }}" class="row g-4">
        @csrf
        <div class="col-md-6"><label class="form-label" for="current_password">Current password</label><input class="form-control" id="current_password" type="password" name="current_password" required></div>
        <div class="col-md-6"><label class="form-label" for="password">New password</label><input class="form-control" id="password" type="password" name="password" required></div>
        <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Change password</button></div>
    </form></div></div>
</main>
@endsection

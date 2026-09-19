<!DOCTYPE html>
<html lang="en">
<head>@include('admin.shared.links')</head>
<body class="admin-theme">
    <main class="container-xxl py-6"><div class="row justify-content-center"><div class="col-12 col-sm-10 col-md-7 col-lg-5"><div class="card"><div class="card-body p-6">
        <h1 class="h4 mb-2">Reset admin password</h1><p class="text-body-secondary">Enter your email and we will send a reset link if an admin account exists.</p>
        @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
        <x-auth-validation-errors class="mb-4" :errors="$errors" />
        <form method="POST" action="{{ route('admin.password.email') }}" class="row g-4">
            @csrf
            <div class="col-12"><label class="form-label" for="email">Email</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
            <div class="col-12"><button class="btn btn-primary w-100" type="submit">Send reset link</button></div>
        </form>
        <a href="{{ route('admin.login') }}" class="d-block text-center mt-4">Back to login</a>
    </div></div></div></div></main>
</body>
</html>

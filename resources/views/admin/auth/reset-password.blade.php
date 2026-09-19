<!DOCTYPE html>
<html lang="en">
<head>@include('admin.shared.links')</head>
<body class="admin-theme">
    <main class="container-xxl py-6"><div class="row justify-content-center"><div class="col-12 col-sm-10 col-md-7 col-lg-5"><div class="card"><div class="card-body p-6">
        <h1 class="h4 mb-2">Set a new admin password</h1><x-auth-validation-errors class="mb-4" :errors="$errors" />
        <form method="POST" action="{{ route('admin.password.update') }}" class="row g-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="col-12"><label class="form-label" for="email">Email</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required></div>
            <div class="col-12"><label class="form-label" for="password">New password</label><input id="password" class="form-control" type="password" name="password" required></div>
            <div class="col-12"><label class="form-label" for="password_confirmation">Confirm password</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required></div>
            <div class="col-12"><button class="btn btn-primary w-100" type="submit">Reset password</button></div>
        </form>
    </div></div></div></div></main>
</body>
</html>

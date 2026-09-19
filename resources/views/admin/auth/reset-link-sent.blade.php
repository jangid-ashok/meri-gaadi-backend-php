<!DOCTYPE html>
<html lang="en">
<head>@include('admin.shared.links')</head>
<body class="admin-theme">
    <main class="container-xxl py-6"><div class="row justify-content-center"><div class="col-12 col-lg-7"><div class="card"><div class="card-body p-6">
        <h1 class="h4 mb-2">Password reset link ready</h1><p class="text-body-secondary">Local development reset link for {{ $email }}:</p>
        <a class="d-block bg-body-secondary rounded p-3 text-break" href="{{ $resetUrl }}">{{ $resetUrl }}</a>
        <a class="btn btn-primary mt-4" href="{{ $resetUrl }}">Open reset page</a>
    </div></div></div></div></main>
</body>
</html>

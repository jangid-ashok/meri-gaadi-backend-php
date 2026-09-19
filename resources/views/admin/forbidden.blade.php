<!DOCTYPE html>
<html lang="en">
<head>@include('admin.shared.links')</head>
<body class="admin-theme">
    <div class="layout-wrapper layout-content-navbar"><div class="layout-container">
        @include('admin.shared.left_nav_bar')
        <div class="layout-page">@include('admin.shared.top_nav_bar')<div class="content-wrapper">
            <main class="container-xxl flex-grow-1 container-p-y"><div class="card"><div class="card-body text-center py-6">
                <h1 class="h3">Access denied</h1><p class="text-body-secondary">You do not have permission to access this page.</p>
                @if (Auth::user()?->hasPermission('dashboard.view'))<a class="btn btn-primary" href="{{ route('admin.dashboard') }}">Back to dashboard</a>@endif
            </div></div></main>
            @include('admin.shared.footer')
        </div></div>
    </div><div class="layout-overlay layout-menu-toggle"></div></div>
</body>
</html>

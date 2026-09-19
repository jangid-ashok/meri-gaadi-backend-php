<!DOCTYPE html>
<html lang="en">

<head>
    @include('admin.shared.links')
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('admin.shared.left_nav_bar')
            <div class="layout-page">
                @include('admin.shared.top_nav_bar')
                <div class="content-wrapper">
                    <main class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
                            <div>
                                <h1 class="h3 mb-1">Dashboard</h1>
                                <p class="text-body-secondary mb-0">A live overview of your admin workspace.</p>
                            </div>
                            <button id="dashboard-retry" class="btn btn-outline-primary d-none" type="button">Retry</button>
                        </div>

                        <div id="dashboard-error" class="alert alert-danger d-none" role="alert">
                            Unable to load dashboard data.
                        </div>

                        <section id="dashboard-statistics" class="row g-4" aria-live="polite">
                            @foreach (['Total users', 'Total admins', 'Total roles', 'Total permissions', 'Blog categories', 'Total blogs', 'Published blogs', 'Draft blogs'] as $label)
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <span class="placeholder col-8"></span>
                                            <h2 class="placeholder col-5 mt-3 mb-2">&nbsp;</h2>
                                            <span class="placeholder col-6"></span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </section>

                        <section class="row g-4 mt-1">
                            <div class="col-lg-7">
                                <div class="card h-100"><div class="card-body">
                                    <h2 class="h5 mb-1">Quick actions</h2>
                                    <p class="text-body-secondary mb-4">Jump into the areas available to you.</p>
                                    @if (Auth::user()->hasPermission('roles.view'))
                                        <a class="btn btn-primary" href="{{ route('admin.roles.index') }}">Manage roles</a>
                                    @else
                                        <p class="text-body-secondary mb-0">No quick actions are available for your permissions.</p>
                                    @endif
                                </div></div>
                            </div>
                            <div class="col-lg-5">
                                <div class="card h-100"><div class="card-body">
                                    <h2 class="h5 mb-1">Recent activity</h2>
                                    <p class="text-body-secondary mb-0">No recent activity is available yet.</p>
                                </div></div>
                            </div>
                        </section>
                    </main>
                    @include('admin.shared.footer')
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script>
        const statistics = [
            ['total_users', 'Total users'], ['total_admins', 'Total admins'],
            ['total_roles', 'Total roles'], ['total_permissions', 'Total permissions'],
            ['total_blog_categories', 'Blog categories'], ['total_blogs', 'Total blogs'],
            ['published_blogs', 'Published blogs'], ['draft_blogs', 'Draft blogs'],
        ];
        const statisticsElement = document.getElementById('dashboard-statistics');
        const errorElement = document.getElementById('dashboard-error');
        const retryButton = document.getElementById('dashboard-retry');

        function renderStatistics(values) {
            statisticsElement.innerHTML = statistics.map(([key, label]) => `
                <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
                    <span class="text-body-secondary">${label}</span>
                    <h2 class="display-6 mb-0 mt-2">${values[key] ?? 0}</h2>
                </div></div></div>`).join('');
        }

        async function loadDashboard() {
            errorElement.classList.add('d-none');
            retryButton.classList.add('d-none');
            try {
                const response = await fetch('{{ url('/api/admin/dashboard') }}', { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Dashboard request failed');
                const payload = await response.json();
                renderStatistics(payload.statistics || {});
            } catch (error) {
                errorElement.classList.remove('d-none');
                retryButton.classList.remove('d-none');
            }
        }

        retryButton.addEventListener('click', loadDashboard);
        loadDashboard();
    </script>
</body>

</html>

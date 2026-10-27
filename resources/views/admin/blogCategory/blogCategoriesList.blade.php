<!DOCTYPE html>
<html lang="en">

<head>
    @extends('admin.shared.links')
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            @include('admin.shared.left_nav_bar')
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">

                @include('admin.shared.top_nav_bar')

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Hoverable Table rows -->
                        <div class="card">
                            <div class="row m-3 my-0 justify-content-between">
                                <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                                    <h5 class="card-header">Blog Categories</h5>
                                </div>
                                <div class="d-md-flex align-items-center dt-layout-end col-md-auto ms-auto justify-content-md-between justify-content-center d-flex flex-wrap gap-2 mb-md-0 mb-4 mt-0">
                                    <a class="btn add-new btn-primary rounded-pill" tabindex="0" href="{{ url('admin/blog-categories/create') }}"><span><i class="icon-base bx bx-plus me-0 me-sm-1 icon-xs"></i><span class="d-none d-sm-inline-block">Add New Category</span></span></a>
                                </div>
                            </div>
                            <form method="GET" class="row mx-3 mb-3 align-items-end flex-wrap gap-2">
                                @if(request()->filled('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                                <div class="col-auto flex-grow-1" style="min-width: 200px;">
                                    <label class="form-label" for="category-keyword">Search categories</label>
                                    <input type="search" class="form-control" id="category-keyword" name="search" placeholder="Title, subtitle, or slug" value="{{ request('search') }}" />
                                </div>
                                <div class="col-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    @if(request()->filled('search'))<a href="{{ route('admin.blog-categories.index') }}" class="btn btn-outline-secondary">Clear</a>@endif
                                </div>
                            </form>
                            <div class="alert-message-div" style="display: none;">
                                <div class="alert alert-dismissible" role="alert">
                                    <span class="error-message"></span>
                                    <button type="button" class="btn-close alert-close-btn" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Title</th>
                                            <th>Subtitle</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">@include('admin.blogCategory.getBlogCategoryList', ['blogCategories' => $blogCategories])</tbody>
                                </table>
                            </div>
                            @if($blogCategories->total() > 0)<div class="card-footer">@include('admin.shared.pagination', ['paginator' => $blogCategories])</div>@endif
                        </div>
                        <!--/ Hoverable Table rows -->
                        <hr class="my-12" />

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    @include('admin.shared.footer')
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <script src="{{ asset(config('constants.JS_PATH') . '/admin/blog-category.js') }}"></script>

</body>

</html>

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
                        <!-- Basic Layout & Basic with Icons -->
                        <div class="row mb-6 gy-6">
                            <!-- Basic with Icons -->
                            <div class="col-xxl">
								<div class="card">
									<div class="card-header d-flex align-items-center justify-content-between">
										<h5 class="mb-0">{{ ($pageType ?? '') === 'edit' ? 'Edit Blog' : 'Add New Blog' }}</h5>
										<div class="text=end">
										<a href="{{ url('admin/blogs') }}" class="btn btn-primary rounded-pill submit-form"><span class="icon-base bx bx-left-arrow-circle icon-sm me-2"></span> Go to All Blogs</a>
										</div>
									</div>
									<div class="card-body">
										<div class="alert-message-div" style="display: none;">
											<div class="alert alert-dismissible" role="alert">
												<span class="error-message"></span>
												<button type="button" class="btn-close alert-close-btn" data-bs-dismiss="alert" aria-label="Close"></button>
											</div>
										</div>
										<form id="blogForm" method="post" action="{{ $formAction ?? '' }}">
											@csrf
											<div class="row mb-6">
                                                <label for="exampleFormControlSelect1" class="col-sm-2 col-form-label">Categories</label>
                                                <div class="col-sm-10">
                                                    <select class="form-select" name="category_ids" id="category_ids" aria-label="Category IDs">
                                                        <option value="">Select Category</option>
														@if (isset($categories) && count($categories) > 0)
															@foreach ($categories as $category)
																<option value="{{ $category->id }}" {{ isset($selectedCategoryId) && (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
															@endforeach
														@endif
                                                    </select>
                                                </div>
											</div>
											<div class="row mb-6">
                                                <label class="col-sm-2 col-form-label" for="title">Blog Title</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control create-slug" id="title" name="title" placeholder="Enter the title" value="{{ isset($blogInfo) ? optional($blogInfo)->title : '' }}" />
                                                </div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="subtitle">Subtitle</label>
												<div class="col-sm-10">
													<input type="text" class="form-control" id="subtitle" name ="sub_title" placeholder="Enter the subtitle" value="{{ isset($blogInfo) ? optional($blogInfo)->sub_title : '' }}" />
												</div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="category-slug">Slug</label>
												<div class="col-sm-10">
													<input type="text" id="category-slug" name="slug" class="form-control created-slug" placeholder="Blog URL slug" value="{{ isset($blogInfo) ? optional($blogInfo)->slug : '' }}" readonly/>
												</div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="meta_title">Meta title</label>
												<div class="col-sm-10">
													<input type="text" class="form-control" id="meta_title" name ="meta_title" placeholder="Enter the meta title" value="{{ isset($blogInfo) ? optional($blogInfo)->meta_title : '' }}" />
												</div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="meta_description">Meta Description</label>
												<div class="col-sm-10">
													<textarea id="meta_description" name="meta_description" class="form-control" placeholder="Enter the meta description" aria-label="Enter the meta description" aria-describedby="basic-icon-default-message2">{{ isset($blogInfo) ? optional($blogInfo)->meta_description : '' }}</textarea>
												</div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="description">Description</label>
												<div class="col-sm-10">
													<textarea id="description" name="description" class="form-control" placeholder="Enter the description" aria-label="Enter the description" aria-describedby="basic-icon-default-message2">{{ isset($blogInfo) ? optional($blogInfo)->description : '' }}</textarea>
												</div>
											</div>
											<div class="row mb-6">
												<label class="col-sm-2 col-form-label" for="status">Status</label>
												<div class="col-sm-10">
													<div class="form-check form-check-inline">
														<input class="form-check-input" type="radio" name="status" id="inlineRadio1" value="active" {{ (isset($blogInfo) && isset($blogInfo->status) && ($blogInfo->status == 'active')) ? 'checked' : 'checked' }}>
														<label class="form-check-label" for="inlineRadio1">Active</label>
													</div>
													<div class="form-check form-check-inline">
														<input class="form-check-input" type="radio" name="status" id="inlineRadio2" value="inactive"  {{ (isset($blogInfo) && isset($blogInfo->status) && ($blogInfo->status == 'inactive')) ? 'checked' : '' }}>
														<label class="form-check-label" for="inlineRadio2">Inactive</label>
													</div>
												</div>
											</div>
											<div class="row text-center">
												<div class="col-sm-10">
													<button type="submit" class="btn btn-primary rounded-pill submit-form"><span class="icon-base bx bx-add-to-queue icon-sm me-2"></span> {{ ($pageType ?? '') === 'edit' ? 'Update' : 'Submit' }}</button>
													<button type="reset" class="btn btn-secondary rounded-pill reset-form"><span class="icon-base bx bx-reset icon-sm me-2"></span> Reset</button>
												</div>
											</div>
											<input type="hidden" name="id" value="{{ isset($blogInfo) ? optional($blogInfo)->id : '' }}">
										</form>
									</div>
								</div>
							</div>
                        </div>
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
    <!-- / Additional JS files -->
</body>
<script src="{{ asset(config('constants.JS_PATH') . '/admin/blogs.js') }}"></script>
</html>
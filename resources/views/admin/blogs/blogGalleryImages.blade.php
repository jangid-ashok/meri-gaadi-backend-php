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
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row mb-6 gy-6">
                            <div class="col-xxl">
								<div class="card">
									<div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
										<div>
											<h5 class="mb-1">Blog Gallery</h5>
											<p class="text-muted mb-0 small">{{ $blogInfo->title ?? '' }}</p>
										</div>
										<div class="text-end">
											<a href="{{ url('admin/blog/edit/' . ($blogId ?? '')) }}" class="btn btn-outline-primary rounded-pill me-2">
												<span class="icon-base bx bx-edit-alt icon-sm me-2"></span> Edit Blog
											</a>
											<a href="{{ url('admin/blogs') }}" class="btn btn-primary rounded-pill">
												<span class="icon-base bx bx-left-arrow-circle icon-sm me-2"></span> All Blogs
											</a>
										</div>
									</div>
									<div class="card-body">
										<div class="alert-message-div" style="display: none;">
											<div class="alert alert-dismissible" role="alert">
												<span class="error-message"></span>
												<button type="button" class="btn-close alert-close-btn" data-bs-dismiss="alert" aria-label="Close"></button>
											</div>
										</div>

										<div class="col-12 mb-4">
                                            <div class="card border border-2 border-dashed">
                                                <div class="card-body">
                                                    <input type="file" id="blogGalleryInput" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime" multiple>
                                                    <label for="blogGalleryInput" id="blogGalleryDropzone" class="d-flex align-items-center justify-content-center rounded p-4 cursor-pointer mb-0" style="min-height: 200px;">
                                                        <div class="text-center">
                                                            <i class="bx bx-cloud-upload fs-1 mb-2 text-muted"></i>
                                                            <h6 class="mb-2">Drag and drop images or videos here</h6>
                                                            <p class="text-muted mb-2">JPG, PNG, WEBP, GIF, MP4, WEBM, OGG, MOV (max {{ (int) config('constants.BLOG_GALLERY_MAX_FILE_KB', 51200) / 1024 }}MB each)</p>
                                                            <span class="btn btn-outline-primary rounded-pill">Browse files</span>
                                                        </div>
                                                    </label>
                                                    <div id="gallery-upload-progress" class="text-center mt-3" style="display: none;">
                                                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Uploading...</span></div>
                                                        <p class="text-muted small mb-0 mt-2">Uploading...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <h6 class="mb-3">Gallery items</h6>
                                        <div class="row" id="blog-gallery-grid">
                                            @include('admin.blogs.getBlogGalleryList', ['mediaItems' => $mediaItems ?? collect()])
                                        </div>
									</div>
								</div>
							</div>
                        </div>
                    </div>

                    @include('admin.shared.footer')
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    @csrf
    <input type="hidden" id="blogId" value="{{ $blogId ?? '' }}">
    <input type="hidden" id="galleryUploadUrl" value="{{ url('admin/blog/gallery/' . ($blogId ?? '') . '/upload') }}">
    <input type="hidden" id="galleryDeleteUrl" value="{{ url('admin/blog/gallery/media/delete') }}">
</body>
<script src="{{ asset(config('constants.JS_PATH') . '/admin/blog-gallery.js') }}"></script>
</html>

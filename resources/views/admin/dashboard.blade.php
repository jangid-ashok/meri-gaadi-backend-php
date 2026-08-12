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
					<div class="row">
						<div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
							<div class="row">
								<div class="col-12 mb-6 profile-report">
									<a href="{{url('admin/blog-categories')}}" class="">
										<div class="card h-100">
											<div class="card-body">
												<div class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-10 flex-wrap">
													<div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
														<div class="card-title mb-6 mt-sm-auto">
															<h4 class="text-nowrap mb-1">Blog Categories</h4>
															<span class="badge bg-label-success">0</span>
														</div>
													</div>
												</div>
											</div>
										</div>
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- / Content -->
				@extends('admin.shared.footer')
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

  </body>

</body>

</html>
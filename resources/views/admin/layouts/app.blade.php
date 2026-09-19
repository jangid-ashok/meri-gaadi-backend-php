<!DOCTYPE html>
<html lang="en">

<head>
	@include('admin.shared.links')
</head>

<body class="admin-theme">
	<div class="layout-wrapper layout-content-navbar">
		<div class="layout-container">
			@include('admin.shared.left_nav_bar')
			<div class="layout-page">
				@include('admin.shared.top_nav_bar')
				<div class="content-wrapper">
					@yield('content')
					@include('admin.shared.footer')
					<div class="content-backdrop fade"></div>
				</div>
			</div>
		</div>
		<div class="layout-overlay layout-menu-toggle"></div>
	</div>
</body>

</html>
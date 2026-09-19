<!-- Footer -->
<footer class="content-footer footer bg-footer-theme">
	<div class="container-xxl">
		<div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
			<div class="mb-2 mb-md-0">
				© {{ date('Y') }} , made with ❤️ by
				<a href="https://www.linkedin.com/in/ashu-k-jangid/" target="_blank" class="footer-link">Ashu Jangid</a>
			</div>
		</div>
	</div>
</footer>
<input type="hidden" id="pageType" value="{{ $pageType ?? '' }}" />
<input type="hidden" id="pageLimit" value="{{ config('constants.PAGE_LIMIT') }}" />
<!-- / Footer -->

<!-- Overlay -->
<div class="layout-overlay layout-menu-toggle"></div>

@include('admin.shared.modals')
@include('admin.shared.scripts')
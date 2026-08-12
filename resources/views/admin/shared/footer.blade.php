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

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="login.html">Logout</a>
            </div>
        </div>
    </div>
</div>

@include('admin.shared.modals');

@extends('admin.shared.scripts')
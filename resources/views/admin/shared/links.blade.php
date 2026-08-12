<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<meta name="description" content="">
<meta name="author" content="">
<title>{{($page_title ? $page_title . ' | ' : '') . config('constants.SITE_TITLE')}}</title>
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{asset('assets/images/favicon.ico')}}" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
	href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
	rel="stylesheet" />

<link rel="stylesheet" href="{{asset('assets/fonts/iconify-icons.css')}}" />

<!-- Core CSS -->
<!-- build:css assets/vendor/css/theme.css  -->

<link rel="stylesheet" href="{{asset('assets/css/core.css')}}" />
<link rel="stylesheet" href="{{asset('assets/css/demo.css')}}" />

<!-- Vendors CSS -->

<link rel="stylesheet" href="{{asset('assets/css/perfect-scrollbar.css')}}" />

<!-- endbuild -->

<!-- Page CSS -->
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/css/pages/page-auth.css')}}" />

<!-- Helpers -->
<script src="{{asset('assets/js/helpers.js')}}"></script>
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
<script src="{{asset('assets/js/config.js')}}"></script>

<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->
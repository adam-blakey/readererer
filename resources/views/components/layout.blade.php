@props(['page_name' => '', 'page_subname' => '', 'show_page_header' => true, 'show_nav_menu' => true])

@php
	if ($page_name == '') {
	    $title = config('app.name');
	    $page_name = $title;
	} else {
	    $title = config('app.name') . ' — ' . $page_name;
	}
@endphp

<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport" />
	<meta content="ie=edge" http-equiv="X-UA-Compatible" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $title }}</title>
	<link href="{{ Vite::asset('resources/images/favicon.png') }}" rel="shortcut icon">
	@vite(['resources/js/app.js', 'resources/css/app.css'])
	@stack('scripts')
</head>

<body>
	<div class="page">
		@if ($show_nav_menu)
			<x-nav-menu />
		@endif
		<div class="page-wrapper">
			@if ($show_page_header)
				<x-page-header :$page_name :$page_subname />
			@endif
			<div class="page-body">
				{{ $slot }}
			</div>
			<x-footer />
		</div>
	</div>
</body>

</html>

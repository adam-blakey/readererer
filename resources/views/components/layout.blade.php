@props(['show_page_header' => true, 'show_nav_menu' => true])

<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport" />
	<meta content="ie=edge" http-equiv="X-UA-Compatible" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($breadcrumb = Breadcrumbs::current()) ? $breadcrumb->title : config('app.name') }}</title>
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
				<x-page-header />
			@endif
			<div class="page-body">
				{{ $slot }}
			</div>
			<x-footer />
		</div>
	</div>
</body>

</html>

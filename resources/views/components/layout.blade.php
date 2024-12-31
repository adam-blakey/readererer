@props(['page_name' => '', 'show_page_header' => true])

@php
	if ($page_name == '') {
	    $title = env('APP_NAME');
	    $page_name = $title;
	} else {
	    $title = env('APP_NAME') . ' — ' . $page_name;
	}
@endphp

<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport" />
	<meta content="ie=edge" http-equiv="X-UA-Compatible" />
	<title>{{ $title }}</title>
	@vite(['resources/js/app.js', 'resources/css/app.css'])
	<link href="{{ Vite::asset('resources/images/favicon.png') }}" rel="shortcut icon">
</head>

<body>
	<div class="page">
		<x-nav-menu></x-nav-menu>
		<div class="page-wrapper">
			@if ($show_page_header)
				<x-page-header :$page_name />
			@endif
			<div class="page-body">
				{{ $slot }}
			</div>
			<x-footer />
		</div>
	</div>
</body>

</html>

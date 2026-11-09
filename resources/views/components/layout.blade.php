@props(['show_page_header' => true, 'show_nav_menu' => true])

<!doctype html>
<html @auth data-warn-unsaved @endauth data-bs-theme-primary="{{ config('app.readererer_primary_color') }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8" />
	<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport" />
	<meta content="ie=edge" http-equiv="X-UA-Compatible" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Breadcrumbs::exists() ? Breadcrumbs::current()?->title ?? config('app.name') : config('app.name') }}</title>
	<link href="{{ favicon_url() }}" rel="shortcut icon">
	@feature(\App\Features\InstallableApp::class)
		<link href="{{ route('manifest') }}" rel="manifest">
		<meta content="{{ color_name_to_hex(config('app.readererer_primary_color')) }}" name="theme-color">
		<script nonce="{{ Vite::cspNonce() }}">
			if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
    </script>
	@endfeature

	@feature(\App\Features\ThemeSwitch::class)
		<script nonce="{{ Vite::cspNonce() }}">
			try {
				document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
			} catch (e) {}
		</script>
	@endfeature
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

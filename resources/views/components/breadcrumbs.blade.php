{{-- The trail for the current route; nothing at all where a route has no definition in routes/breadcrumbs.php. --}}
@if (Breadcrumbs::exists())
	{{ Breadcrumbs::render() }}
@endif

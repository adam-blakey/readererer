@props(['ensemble', 'term', 'add_route' => true])

<a {!! $add_route ? "href='" . route('attendance.edit', ['ensemble' => $ensemble, 'term' => $term]) . "'" : '' !!} class="py-1 nav-link d-flex lh-1 text-reset">
    <div class="d-none d-xl-block ps-2">
        <div>{{ $term->name }}</div>
        <div class="mt-1 small text-muted">{{ $term->FormattedTermDateRange }}</div>
    </div>
</a>

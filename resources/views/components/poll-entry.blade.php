@props(['ensemble', 'term', 'add_route' => true])

<a {!! $add_route ? "href='" . route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]) . "'" : '' !!} class="py-1 nav-link d-flex lh-1 text-reset">
    <div class="entry-text ps-2">
        <div class="text-truncate">{{ $term->name }}</div>
        <div class="mt-1 small text-muted text-truncate">{{ $term->FormattedTermDateRange }}</div>
    </div>
</a>

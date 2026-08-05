@props(['term_date'])

<div class="ps-2">
    @if($term_date == null)
        <div class="text-muted">Nothing found.</div>
    @else
        <div>{{ $term_date->schedule_label }}</div>
        <div class="mt-1 small text-muted">{{ $term_date->relative_label }}</div>
    @endif
</div>

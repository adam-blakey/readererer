@props(['term_date'])

@if($term_date == null)
    <div class="d-none d-xl-block ps-2">
        <div class="text-muted">Nothing found.</div>
    </div>
@else
    <div class="d-none d-xl-block ps-2">
        <div>{{ $term_date->start_datetime }}</div>
        <div class="mt-1 small text-muted">{{ $term_date->start_datetime->diffForHumans() }}</div>
    </div>
@endif

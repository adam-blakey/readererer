@props(['term_date', 'empty_message' => 'Nothing found.'])

<div class="ps-2">
    @if($term_date == null)
        <div class="text-muted">{{ $empty_message }}</div>
    @else
        <div>{{ $term_date->schedule_label }}</div>
        <div class="mt-1 small text-muted">{{ $term_date->relative_label }}</div>
    @endif
</div>

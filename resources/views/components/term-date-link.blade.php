@props(['term_date'])

@if (Gate::allows('view', $term_date))
    <a {{ $attributes }} href="{{ route('term-dates.show', $term_date) }}">{{ $slot }}</a>
@else
    {{ $slot }}
@endif

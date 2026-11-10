@props(['term_date'])

@if (Laravel\Pennant\Feature::active(App\Features\TermDateOverview::class) && Gate::allows('view', $term_date))
    <a {{ $attributes }} href="{{ route('term-dates.show', $term_date) }}">{{ $slot }}</a>
@else
    {{ $slot }}
@endif

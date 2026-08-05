@props(['term_date', 'show_players_link' => false])

@if($term_date == null)
    <div class="d-none d-xl-block ps-2">
        <div class="text-muted">Nothing found.</div>
    </div>
@else
    <div class="d-none d-xl-block ps-2">
        <div>{{ $term_date->start_datetime }}</div>
        <div class="mt-1 small text-muted">{{ $term_date->start_datetime->diffForHumans() }}</div>
        @if ($show_players_link && Auth::user()?->can('viewPlayers', $term_date))
            <div class="mt-1 small">
                <x-a href="{{ route('playing.show', $term_date) }}">Who's playing?</x-a>
            </div>
        @endif
    </div>
@endif

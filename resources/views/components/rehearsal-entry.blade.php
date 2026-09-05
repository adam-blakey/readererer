@props(['term_date', 'ensembles' => null])

<div class="ps-2">
    @if($term_date == null)
        <div class="text-muted">{{ __('Nothing found.') }}</div>
    @else
        @php
            // Only ensembles the date actually applies to get a link: rehearsals are
            // for everyone, a concert only for the ensemble playing it.
            $linked_ensembles = collect($ensembles ?? [])
                ->filter()
                ->filter(fn ($ensemble) => $term_date->appliesToEnsemble($ensemble))
                ->values();

            $name_the_ensemble = $linked_ensembles->count() > 1;
            $can_take_registers = Gate::allows('viewAny', App\Models\RegisterEntry::class);
        @endphp
        <div>{{ $term_date->schedule_label }}</div>
        <div class="mt-1 small text-muted">{{ $term_date->relative_label }}</div>
        @if ($linked_ensembles->isNotEmpty())
            <div class="mt-2 btn-list">
                @foreach ($linked_ensembles as $ensemble)
                    @php
                        // With more than one ensemble in play, say which is which.
                        $suffix = $name_the_ensemble ? ': ' . $ensemble->name : '';
                    @endphp
                    <x-a class="btn btn-sm" href="{{ route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term_date->term]) }}">
                        <x-icon name="square-check" />
                        {{ __('Poll') . $suffix }}
                    </x-a>
                    @if ($can_take_registers)
                        <x-a class="btn btn-sm" href="{{ route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $term_date]) }}">
                            <x-icon name="list-check" />
                            {{ __('Register') . $suffix }}
                        </x-a>
                    @endif
                @endforeach
            </div>
        @endif
    @endif
</div>

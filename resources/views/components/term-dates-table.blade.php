@props(['term_dates'])

@php
    $dates = ($term_dates ?? collect())->sortBy('start_datetime');
    $show_setup_groups = config('app.readererer_setup_groups');
    $show_van_drivers = config('app.readererer_van_drivers');
@endphp

<div class="table-responsive">
    @if ($dates->isEmpty())
        <p class="p-3 mb-0 text-muted">{{ __('Nothing scheduled.') }}</p>
    @else
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Time') }}</th>
                    <th>{{ __('Location') }}</th>
                    <th>{{ __('Type') }}</th>
                    @if ($show_setup_groups)
                        <th>{{ __('Setup group') }}</th>
                    @endif
                    @if ($show_van_drivers)
                        <th>{{ __('Van driver') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($dates as $td)
                    <tr>
                        <td>
                            <x-term-date-link :term_date="$td">
                                @if ($td->start_datetime->isPast())
                                    <strike>{{ $td->date_label }}</strike>
                                @else
                                    {{ $td->date_label }}
                                @endif
                            </x-term-date-link>
                        </td>
                        <td class="text-nowrap">{{ $td->time_label }}</td>
                        <td><x-location-link :location="$td->inferred_location" /></td>
                        <td>
                            @if ($td->is_concert)
                                <span class="badge bg-primary text-primary-fg">{{ __('Concert') }} ({{ $td->concert_ensemble_names }})</span>
                            @else
                                <span class="badge bg-gray text-muted">{{ __('Rehearsal') }}</span>
                            @endif
                        </td>
                        @if ($show_setup_groups)
                            <td>
                                @if ($td->setup_group != null)
                                    <x-setup-group-badge :setup_group="$td->setup_group" />
                                @else
                                    <span class="text-muted" data-bs-toggle="tooltip" title="{{ __('No setup group is on duty for this date') }}">—</span>
                                @endif
                            </td>
                        @endif
                        @if ($show_van_drivers)
                            <td>
                                @if ($td->inferred_van_driver == null)
                                    <span class="badge bg-red text-red-fg">{{ __('No van driver!') }}</span>
                                @else
                                    {{ $td->inferred_van_driver->name }}
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

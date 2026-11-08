{{-- One term date in the term form; also rendered with $i = '__INDEX__' as the template that "Add date" and "Duplicate" clone. --}}
@php
    $selectedEnsembles = collect($date['ensemble_ids'] ?? [])->map(fn ($id) => (int) $id);
    $selectedSetupGroup = $date['setup_group_id'] ?? null;
    $selectedVanDriver = $date['van_driver_id'] ?? null;
    $selectedLocation = $date['location_id'] ?? null;
@endphp

<div class="card card-sm mb-3 term-date-row" data-index="{{ $i }}">
    @if (!empty($date['id']))
        <input type="hidden" name="term_dates[{{ $i }}][id]" value="{{ $date['id'] }}">
    @endif
    <div class="card-header">
        <h4 class="card-title">{{ $orig?->schedule_label ?? __('New date') }}</h4>
        <div class="card-actions btn-list">
            <button class="btn btn-sm duplicate-term-date" type="button"><x-icon name="copy" /> {{ __('Duplicate') }}</button>
            <button class="btn btn-sm btn-outline-danger remove-term-date" type="button"><x-icon name="trash" /> {{ __('Remove') }}</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">{{ __('Start') }}</label>
                <input class="form-control @error('term_dates.'.$i.'.start_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][start_datetime]" type="datetime-local" value="{{ $date['start_datetime'] ?? '' }}" data-initial="{{ $orig?->start_datetime?->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('End') }}</label>
                <input class="form-control @error('term_dates.'.$i.'.end_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][end_datetime]" type="datetime-local" value="{{ $date['end_datetime'] ?? '' }}" data-initial="{{ $orig?->end_datetime?->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-check mb-2">
                    <input class="form-check-input" name="term_dates[{{ $i }}][time_tbc]" type="checkbox" value="1" data-initial="{{ $orig?->time_tbc ? '1' : '' }}" @checked(!empty($date['time_tbc']))>
                    <span class="form-check-label">{{ __('Time TBC') }}</span>
                </label>
            </div>
            @foreach (['start_datetime', 'end_datetime'] as $field)
                @error('term_dates.'.$i.'.'.$field)
                    <div class="col-12 mt-1"><x-forms.input-error :messages="$message" /></div>
                @enderror
            @endforeach
        </div>
        <div class="row g-3 mt-0 align-items-start">
            <div class="col-md-6 col-xl-3">
                <label class="form-label">{{ __('Location') }}</label>
                <select class="form-select @error('term_dates.'.$i.'.location_id') is-invalid @enderror" name="term_dates[{{ $i }}][location_id]" data-initial="{{ $orig?->location_id ?? '' }}" data-location-select>
                    <option class="text-secondary" value="" data-default-location @selected(empty($selectedLocation))>{{ __('Default: :location', ['location' => $default_location_name]) }}</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected((int) $selectedLocation === $location->id)>{{ $location->name }}</option>
                    @endforeach
                    @if ($can_add_location)
                        <option value="new">{{ __('Add new location…') }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label">{{ __('Concert ensembles') }}</label>
                <select class="form-select" name="term_dates[{{ $i }}][ensemble_ids][]" multiple size="3" data-initial="{{ $orig?->concert_ensembles->pluck('id')->join(',') }}">
                    @foreach ($ensembles as $ens)
                        <option value="{{ $ens->id }}" @selected($selectedEnsembles->contains($ens->id))>{{ $ens->name }}</option>
                    @endforeach
                </select>
                <small class="form-hint">{{ __('Select none for a rehearsal') }}</small>
            </div>
            @if (config('app.readererer_setup_groups'))
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">{{ __('Setup group') }}</label>
                    <select class="form-select" name="term_dates[{{ $i }}][setup_group_id]" data-initial="{{ $orig?->setup_group_id ?? '' }}">
                        <option value="" @selected(empty($selectedSetupGroup))>{{ __('No setup group') }}</option>
                        @foreach ($setup_groups as $setup_group)
                            <option value="{{ $setup_group->id }}" @selected((int) $selectedSetupGroup === $setup_group->id)>{{ $setup_group->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if (config('app.readererer_van_drivers'))
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">{{ config('app.readererer_setup_groups') ? __('Van driver override') : __('Van driver') }}</label>
                    <select class="form-select" name="term_dates[{{ $i }}][van_driver_id]" data-initial="{{ $orig?->van_driver_id ?? '' }}">
                        <option value="" @selected(empty($selectedVanDriver))>{{ config('app.readererer_setup_groups') ? __('Infer van driver') : __('No van driver') }}</option>
                        @foreach ($van_drivers as $van_driver)
                            <option value="{{ $van_driver->id }}" @selected((int) $selectedVanDriver === $van_driver->id)>{{ $van_driver->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>
</div>

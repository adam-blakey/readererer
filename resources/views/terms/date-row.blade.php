{{-- One term date in the term form; also rendered with $i = '__INDEX__' as the template that "Add date" and "Duplicate" clone. --}}
@php
    $selectedEnsembles = collect($date['ensemble_ids'] ?? [])->map(fn ($id) => (int) $id);
    $selectedSetupGroup = $date['setup_group_id'] ?? null;
    $selectedVanDriver = $date['van_driver_id'] ?? null;
    $selectedLocation = $date['location_id'] ?? null;
    $selectedDressCode = $date['dress_code_id'] ?? null;
@endphp

<div class="card card-sm mb-3 term-date-row" data-index="{{ $i }}" data-new="{{ empty($date['id']) ? '1' : '' }}">
    @if (!empty($date['id']))
        <input type="hidden" name="term_dates[{{ $i }}][id]" value="{{ $date['id'] }}">
    @endif
    @php($open = empty($date['id']) || $errors->has('term_dates.'.$i.'.*'))
    <div class="card-header flex-wrap gap-2 py-2 border-bottom-0">
        <button class="btn btn-ghost-secondary px-2 term-date-toggle" type="button" aria-expanded="{{ $open ? 'true' : 'false' }}">
            <x-icon name="chevron-down" />
            <span class="text-body fw-bold term-date-title">{{ $orig?->schedule_label ?? __('New date') }}</span>
        </button>
        <span class="badge bg-green-lt d-none term-date-new">{{ __('New') }}</span>
        <span class="badge bg-azure-lt d-none term-date-changed">{{ __('Changed') }}</span>
        <span class="text-danger d-none term-date-removed">{{ __('Removed when you save') }}</span>
        <span class="text-secondary small term-date-summary"></span>
        <div class="card-actions btn-list my-0">
            <button class="btn btn-sm duplicate-term-date" type="button"><x-icon name="copy" /> {{ __('Duplicate') }}</button>
            <button class="btn btn-sm btn-outline-danger remove-term-date" type="button"><x-icon name="trash" /> {{ __('Remove') }}</button>
            <button class="btn btn-sm d-none restore-term-date" type="button"><x-icon name="arrow-back-up" /> {{ __('Undo') }}</button>
        </div>
    </div>
    <div @class(['card-body collapse term-date-body border-top', 'show' => $open])>
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">{{ __('Start') }}</label>
                <x-forms.datetime-input :initial="$orig?->start_datetime?->format('Y-m-d\TH:i') ?? ''" :invalid="$errors->has('term_dates.'.$i.'.start_datetime')" :name="'term_dates['.$i.'][start_datetime]'" required :value="$date['start_datetime'] ?? ''" />
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('End') }}</label>
                <x-forms.datetime-input :initial="$orig?->end_datetime?->format('Y-m-d\TH:i') ?? ''" :invalid="$errors->has('term_dates.'.$i.'.end_datetime')" :name="'term_dates['.$i.'][end_datetime]'" required :value="$date['end_datetime'] ?? ''" />
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
                @if ($single_ensemble)
                    <label class="form-label">{{ __('Type') }}</label>
                    <label class="form-check">
                        <input class="form-check-input" name="term_dates[{{ $i }}][ensemble_ids][]" type="checkbox" value="{{ $single_ensemble->id }}" data-initial="{{ $orig?->is_concert ? '1' : '' }}" @checked($selectedEnsembles->isNotEmpty())>
                        <span class="form-check-label">{{ __('Concert') }}</span>
                    </label>
                    <small class="form-hint">{{ __('Leave unticked for a rehearsal') }}</small>
                @else
                    <div class="form-label">{{ __('Concert ensembles') }}</div>
                    <div class="form-selectgroup">
                        @foreach ($ensembles as $ens)
                            <label class="form-selectgroup-item">
                                <input class="form-selectgroup-input" name="term_dates[{{ $i }}][ensemble_ids][]" type="checkbox" value="{{ $ens->id }}" data-initial="{{ $orig?->concert_ensembles->contains($ens) ? '1' : '' }}" @checked($selectedEnsembles->contains($ens->id))>
                                <span class="form-selectgroup-label">{{ $ens->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <small class="form-hint">{{ __('Tick none for a rehearsal') }}</small>
                @endif
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
        <div class="row g-3 mt-0 align-items-start">
            <div class="col-md-6 col-xl-3">
                <label class="form-label">{{ __('Dress code') }}</label>
                <select class="form-select @error('term_dates.'.$i.'.dress_code_id') is-invalid @enderror" name="term_dates[{{ $i }}][dress_code_id]" data-initial="{{ $orig?->dress_code_id ?? '' }}">
                    <option value="" @selected(empty($selectedDressCode))>{{ __('No dress code') }}</option>
                    @foreach ($dress_codes as $dress_code)
                        <option value="{{ $dress_code->id }}" @selected((int) $selectedDressCode === $dress_code->id)>{{ $dress_code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-xl-5">
                <label class="form-label">{{ __('Description') }}</label>
                <textarea class="form-control @error('term_dates.'.$i.'.description') is-invalid @enderror" name="term_dates[{{ $i }}][description]" rows="3" data-initial="{{ $orig?->description ?? '' }}">{{ $date['description'] ?? '' }}</textarea>
            </div>
            <div class="col-md-12 col-xl-4">
                <label class="form-label">{{ __('Concert order') }}</label>
                <textarea class="form-control @error('term_dates.'.$i.'.concert_order') is-invalid @enderror" name="term_dates[{{ $i }}][concert_order]" rows="3" data-initial="{{ $orig?->concert_order ?? '' }}">{{ $date['concert_order'] ?? '' }}</textarea>
            </div>
        </div>
    </div>
</div>

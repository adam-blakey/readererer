@props(['term', 'ensembles', 'setup_groups', 'van_drivers', 'locations', 'form_route', 'form_method'])

@php
	$can_add_location = Gate::allows('create', App\Models\Location::class);
	$default_location_name = $locations->firstWhere('id', (int) old('location_id', $term->location_id))?->name ?? __('TBC');
@endphp

<x-layout>
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="my-0 font-bold">{{ __('Edit term: :name', ['name' => $term->name]) }}</h1>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <button aria-label="{{ __('Save') }}" class="btn btn-primary" form="term-edit-form" type="submit">
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

	<div class="page-body">
		<div class="container-xl">
			<div class="row g-3">
				<div class="col-lg-12">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">{{ __('Edit term details') }}</h2>
						</div>
						<div class="card-body">
							<x-forms.error-summary :fields="['name', 'slug', 'location_id', 'term_dates.*.start_datetime', 'term_dates.*.end_datetime', 'term_dates.*.time_tbc', 'term_dates.*.location_id']" />

							<form action="{{ $form_route }}" id="term-edit-form" method="POST" data-dirty-check data-default-label="{{ __('Default: :location') }}" data-tbc-label="{{ __('TBC') }}">
								@csrf
                                @method($form_method)

								<div class="row g-5">
									<div class="col-xl-12">
										<div class="mb-3">
											<label class="form-label">{{ __('ID') }}</label>
											<input class="form-control" disabled id="id" name="id" type="text" value="{{ $term->id }}">
										</div>
										<div class="mb-3">
											<label class="form-label" for="name">{{ __('Name') }}</label>
   								            <input class="form-control" id="name" name="name" placeholder="{{ __('Term name') }}" type="text" value="{{ old('name', $term->name) }}" data-initial="{{ $term->name }}">
											@error('name')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div class="mb-3">
											<label class="form-label" for="slug">{{ __('Slug') }}</label>
   								            <input class="form-control" id="slug" name="slug" placeholder="term-slug" type="text" value="{{ old('slug', $term->slug) }}" data-initial="{{ $term->slug }}">
											@error('slug')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div class="mb-3">
											<label class="form-label" for="location_id">{{ __('Default location') }}</label>
											<select class="form-select" id="location_id" name="location_id" data-initial="{{ $term->location_id ?? '' }}" data-location-select>
												<option value="">{{ __('TBC') }}</option>
												@foreach ($locations as $location)
													<option value="{{ $location->id }}" @selected((int) old('location_id', $term->location_id) === $location->id)>{{ $location->name }}</option>
												@endforeach
												@if ($can_add_location)
													<option value="new">{{ __('Add new location…') }}</option>
												@endif
											</select>
											<small class="form-hint">{{ __('Dates without a location of their own take this one.') }}</small>
											@error('location_id')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
                                        <hr />
										<div class="mb-3">
											<h3 class="card-title">{{ __('Term dates') }}</h3>

											@php
												$prefillDates = collect(old('term_dates'));
												if ($prefillDates->isEmpty()) {
													$prefillDates = ($term->term_dates ?? collect())->sortBy('start_datetime')->map(fn ($d) => [
														'id' => $d->id,
														'start_datetime' => $d->start_datetime?->format('Y-m-d\TH:i'),
														'end_datetime' => $d->end_datetime?->format('Y-m-d\TH:i'),
														'ensemble_ids' => $d->concert_ensembles->modelKeys(),
														'setup_group_id' => $d->setup_group_id,
														'van_driver_id' => $d->van_driver_id,
														'time_tbc' => $d->time_tbc,
														'location_id' => $d->location_id,
													]);
												}
											@endphp

											<template id="term-date-row-template">
												@include('terms.date-row', ['i' => '__INDEX__', 'date' => [], 'orig' => null])
											</template>

											<div id="term-dates-list">
												@foreach ($prefillDates as $i => $date)
													@include('terms.date-row', ['orig' => empty($date['id']) ? null : $term->term_dates->firstWhere('id', $date['id'])])
												@endforeach
											</div>
											<div class="mt-2">
												<button class="btn btn-outline-primary" id="add-term-date" type="button"><x-icon name="plus" /> {{ __('Add date') }}</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	@if ($can_add_location)
		<x-modals.add-location />
	@endif

	<script nonce="{{ Vite::cspNonce() }}">
		document.addEventListener('DOMContentLoaded', function () {
			const form = document.getElementById('term-edit-form');
			const list = document.getElementById('term-dates-list');
			const addBtn = document.getElementById('add-term-date');
			const template = document.getElementById('term-date-row-template');

			function attachChangeWatcher(input) {
				if (!input) return;
				const initial = input.getAttribute('data-initial') ?? '';
				function apply() {
					const changed = valueOf(input) !== initial;
					input.classList.toggle('border-2', changed);
					input.classList.toggle('border-solid', changed);
					input.classList.toggle('border-info', changed);
				}
				input.addEventListener('input', apply);
				input.addEventListener('change', apply);
				apply();
			}

			function valueOf(input) {
				if (input.type === 'checkbox') {
					return input.checked ? '1' : '';
				}
				if (input.multiple) {
					return Array.from(input.selectedOptions, option => option.value).join(',');
				}
				return input.value ?? '';
			}

			function nextIndex() {
				let max = -1;
				list.querySelectorAll('.term-date-row').forEach(row => {
					const idx = parseInt(row.getAttribute('data-index'));
					if (!isNaN(idx)) max = Math.max(max, idx);
				});
				return max + 1;
			}

			function buildRow(index) {
				const row = template.content.firstElementChild.cloneNode(true);
				row.setAttribute('data-index', index);
				row.querySelectorAll('[name]').forEach(el => {
					el.name = el.name.replace(/__INDEX__/g, index);
				});
				return row;
			}

			function registerRow(row) {
				row.querySelector('.remove-term-date')?.addEventListener('click', () => row.remove());
				row.querySelector('.duplicate-term-date')?.addEventListener('click', () => duplicateRow(row));
				row.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
			}

			function addRow() {
				const row = buildRow(nextIndex());
				list.appendChild(row);
				registerRow(row);
			}

			function duplicateRow(sourceRow) {
				const row = buildRow(nextIndex());
				// The template has no hidden id, so the duplicate is saved as a new term date.
				row.querySelectorAll('[name]').forEach(el => {
					const field = (el.name.match(/\[[^\]]+\](\[\])?$/) || [])[0];
					const source = field ? sourceRow.querySelector(`[name$="${field}"]`) : null;
					if (source && source.multiple) {
						Array.from(el.options).forEach(option => option.selected = source.querySelector(`option[value="${option.value}"]`)?.selected ?? false);
					} else if (source && source.type === 'checkbox') {
						el.checked = source.checked;
					} else if (source && source.type !== 'hidden') {
						el.value = source.value;
					}
				});
				sourceRow.insertAdjacentElement('afterend', row);
				registerRow(row);
			}

			addBtn?.addEventListener('click', addRow);

			const defaultSelect = document.getElementById('location_id');
			function updateDefaultLabels() {
				const label = form.dataset.defaultLabel.replace(':location', defaultSelect.value ? defaultSelect.selectedOptions[0].text : form.dataset.tbcLabel);
				[document, template.content].forEach(root => root.querySelectorAll('[data-default-location]').forEach(option => option.text = label));
			}

			// "Add new location…" keeps the previous choice and opens the modal; the created location is then selected.
			let pendingSelect = null;
			document.querySelectorAll('[data-location-select]').forEach(select => select.dataset.current = select.value);
			form.addEventListener('change', event => {
				const select = event.target;
				if (!select.matches('[data-location-select]')) return;
				if (select.value === 'new') {
					select.value = select.dataset.current ?? '';
					pendingSelect = select;
					document.getElementById('open-add-location')?.click();
					return;
				}
				select.dataset.current = select.value;
				if (select === defaultSelect) updateDefaultLabels();
			});

			document.addEventListener('location-added', event => {
				const { id, name } = event.detail;
				[document, template.content].forEach(root => root.querySelectorAll('[data-location-select]').forEach(select => {
					select.querySelector('option[value="new"]')?.before(new Option(name, id));
				}));
				if (pendingSelect) {
					pendingSelect.value = id;
					pendingSelect.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});

			form?.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
			list.querySelectorAll('.term-date-row').forEach(row => {
				row.querySelector('.remove-term-date')?.addEventListener('click', () => row.remove());
				row.querySelector('.duplicate-term-date')?.addEventListener('click', () => duplicateRow(row));
			});
		});
	</script>
</x-layout>

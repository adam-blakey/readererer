@props(['term', 'ensembles', 'setup_groups', 'van_drivers', 'locations', 'dress_codes', 'form_route', 'form_method'])

@php
	$can_add_location = Gate::allows('create', App\Models\Location::class);
	$single_ensemble = single_ensemble();
	$default_location_name = $locations->firstWhere('id', (int) old('location_id', $term->location_id))?->name ?? __('TBC');
	$compact = Feature::active(App\Features\CompactTermForm::class);
@endphp

<x-layout>
    <div @class(['container', 'sticky-top bg-body py-2' => $compact])>
        <div class="row g-2 align-items-center">
            <div class="col">
                <h1 class="my-0 font-bold">{{ $term->exists ? __('Edit term: :name', ['name' => $term->name]) : __('New term') }}</h1>
            </div>
            <div class="col-12 col-md-auto ms-auto">
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
							<h2 class="mb-0 card-heading">{{ __('Term details') }}</h2>
						</div>
						<div class="card-body">
							<x-forms.error-summary :fields="['name', 'slug', 'location_id', 'hidden', 'term_dates.*.start_datetime', 'term_dates.*.end_datetime', 'term_dates.*.time_tbc', 'term_dates.*.location_id', 'term_dates.*.description', 'term_dates.*.concert_order', 'term_dates.*.dress_code_id']" />

							<form action="{{ $form_route }}" id="term-edit-form" method="POST" data-dirty-check @if ($compact) data-compact @endif data-default-label="{{ __('Default: :location') }}" data-tbc-label="{{ __('TBC') }}">
								@csrf
                                @method($form_method)

								<div class="row g-5">
									<div class="col-xl-12">
										<div @class(['row' => $compact])>
										@unless ($compact)
											<div class="mb-3">
												<label class="form-label">{{ __('ID') }}</label>
												<input class="form-control" disabled id="id" name="id" type="text" value="{{ $term->id }}">
											</div>
										@endunless
										<div @class(['mb-3', 'col-md-6' => $compact])>
											<label class="form-label" for="name">{{ __('Name') }}</label>
   								            <input class="form-control" id="name" name="name" placeholder="{{ __('Term name') }}" type="text" value="{{ old('name', $term->name) }}" data-initial="{{ $term->name }}">
											@error('name')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div @class(['mb-3', 'col-md-6' => $compact])>
											<label class="form-label" for="slug">{{ __('Slug') }}</label>
   								            <input class="form-control" id="slug" name="slug" placeholder="term-slug" type="text" value="{{ old('slug', $term->slug) }}" data-initial="{{ $term->slug }}">
											@error('slug')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div @class(['mb-3', 'col-md-6' => $compact])>
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
										<div @class(['mb-3', 'col-md-6 align-self-center' => $compact])>
											<input name="hidden" type="hidden" value="0">
											<label class="form-check form-switch">
												<input class="form-check-input" name="hidden" type="checkbox" value="1" @checked(old('hidden', $term->hidden))>
												<span class="form-check-label">{{ __('Hide from members') }}</span>
											</label>
											<small class="form-hint">{{ __('Members cannot see or reply to the polls of a hidden term. Moderators and admins still can.') }}</small>
											@error('hidden')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										</div>
                                        <hr />
										<div class="mb-3">
											@if ($compact)
												<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
													<h3 class="card-title mb-0 me-auto">{{ __('Term dates') }}</h3>
													<div class="btn-list">
														<button class="btn" type="button" aria-controls="repeat-dates-panel" aria-expanded="false" data-bs-target="#repeat-dates-panel" data-bs-toggle="collapse"><x-icon name="repeat" /> {{ __('Add repeating dates') }}</button>
														<button class="btn btn-ghost-secondary" id="expand-term-dates" type="button">{{ __('Expand all') }}</button>
														<button class="btn btn-ghost-secondary" id="collapse-term-dates" type="button">{{ __('Collapse all') }}</button>
													</div>
												</div>
												<div class="card card-sm mb-3 collapse" id="repeat-dates-panel">
													<div class="card-body">
														<p class="text-secondary">{{ __('Adds a date on the same day of the week from the first date to the last. Change any of them before you save.') }}</p>
														<div class="row g-3 align-items-end">
															<div class="col-sm-6 col-lg-3">
																<label class="form-label required" for="repeat-first">{{ __('First date') }}</label>
																<input class="form-control" form="term-repeat-dates" id="repeat-first" type="date" required>
															</div>
															<div class="col-sm-6 col-lg-3">
																<label class="form-label required" for="repeat-last">{{ __('Last date') }}</label>
																<input class="form-control" form="term-repeat-dates" id="repeat-last" type="date" required>
															</div>
															<div class="col-sm-4 col-lg-2">
																<label class="form-label" for="repeat-weeks">{{ __('Repeat') }}</label>
																<select class="form-select" form="term-repeat-dates" id="repeat-weeks">
																	@foreach ([1, 2, 3, 4] as $weeks)
																		<option value="{{ $weeks }}">{{ trans_choice('Every week|Every :count weeks', $weeks) }}</option>
																	@endforeach
																</select>
															</div>
															<div class="col-6 col-sm-4 col-lg-2">
																<label class="form-label required" for="repeat-start">{{ __('Start time') }}</label>
																<x-forms.time-input form="term-repeat-dates" id="repeat-start" name="start_time" required />
															</div>
															<div class="col-6 col-sm-4 col-lg-2">
																<label class="form-label required" for="repeat-end">{{ __('End time') }}</label>
																<x-forms.time-input form="term-repeat-dates" id="repeat-end" name="end_time" required />
															</div>
														</div>
														<div class="invalid-feedback d-block" id="repeat-dates-error"></div>
														<div class="btn-list mt-3">
															<button class="btn btn-primary" form="term-repeat-dates" type="submit" data-dirty-ignore><x-icon name="plus" /> {{ __('Add dates') }}</button>
															<button class="btn" type="button" data-bs-target="#repeat-dates-panel" data-bs-toggle="collapse">{{ __('Cancel') }}</button>
														</div>
													</div>
												</div>
											@else
												<h3 class="card-title">{{ __('Term dates') }}</h3>
											@endif

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
														'description' => $d->description,
														'concert_order' => $d->concert_order,
														'dress_code_id' => $d->dress_code_id,
													]);
												}
											@endphp

											<template id="term-date-row-template">
												@include('terms.date-row', ['i' => '__INDEX__', 'date' => [], 'orig' => null])
											</template>

											<div id="term-dates-list" data-labels="{{ json_encode(['new_date' => __('New date'), 'tbc' => __('TBC'), 'rehearsal' => __('Rehearsal'), 'concert' => __('Concert'), 'van_driver' => __('Van driver: :name'), 'too_many' => __('That is more than :count dates. Choose a shorter range.', ['count' => 100]), 'last_before_first' => __('The last date must be on or after the first.')]) }}">
												@foreach ($prefillDates as $i => $date)
													@include('terms.date-row', ['orig' => empty($date['id']) ? null : $term->term_dates->firstWhere('id', $date['id'])])
												@endforeach
											</div>
											<div class="mt-2 d-flex flex-wrap gap-2">
												<button class="btn btn-outline-primary" id="add-term-date" type="button"><x-icon name="plus" /> {{ __('Add date') }}</button>
												@if ($compact)
													<button class="btn btn-primary ms-auto" type="submit">{{ __('Save') }}</button>
												@endif
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

	@if ($compact)
		<form id="term-repeat-dates"></form>
	@endif

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

			const compact = form.hasAttribute('data-compact');
			const labels = JSON.parse(list.dataset.labels);

			function bindRowButtons(row) {
				row.querySelector('.remove-term-date')?.addEventListener('click', () => compact ? setRemoved(row, true) : row.remove());
				row.querySelector('.restore-term-date')?.addEventListener('click', () => setRemoved(row, false));
				row.querySelector('.duplicate-term-date')?.addEventListener('click', () => duplicateRow(row));
				row.querySelector('.term-date-toggle')?.addEventListener('click', () => setOpen(row, !row.querySelector('.term-date-body').classList.contains('show')));
			}

			function registerRow(row) {
				bindRowButtons(row);
				row.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
				summarise(row);
			}

			function setOpen(row, open) {
				row.querySelector('.term-date-body')?.classList.toggle('show', open);
				row.querySelector('.term-date-toggle')?.setAttribute('aria-expanded', open ? 'true' : 'false');
			}

			// Disabled fields aren't submitted, so saving deletes the date as if its row were gone.
			function setRemoved(row, removed) {
				row.querySelectorAll('input, select, textarea').forEach(el => el.disabled = removed);
				row.querySelector('.term-date-title').classList.toggle('text-decoration-line-through', removed);
				row.querySelector('.term-date-removed').classList.toggle('d-none', !removed);
				row.querySelectorAll('.duplicate-term-date, .remove-term-date').forEach(button => button.classList.toggle('d-none', removed));
				row.querySelector('.restore-term-date').classList.toggle('d-none', !removed);
				if (removed) setOpen(row, false);
				summarise(row);
				row.dispatchEvent(new Event('change', { bubbles: true }));
			}

			function field(row, suffix) {
				return row.querySelector(`[name$="${suffix}"]`);
			}

			function formatDate(value) {
				const parts = Object.fromEntries(new Intl.DateTimeFormat(document.documentElement.lang || undefined, { timeZone: 'UTC', weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
					.formatToParts(new Date(`${value}T00:00:00Z`)).map(part => [part.type, part.value]));
				return `${parts.weekday}, ${parts.day} ${parts.month} ${parts.year}`;
			}

			function scheduleLabel(row) {
				const startDate = field(row, '[start_datetime][date]').value;
				const endDate = field(row, '[end_datetime][date]').value;
				const startTime = field(row, '[start_datetime][time]').value;
				const endTime = field(row, '[end_datetime][time]').value;
				if (!startDate) return labels.new_date;
				if (field(row, '[time_tbc]').checked) return `${formatDate(startDate)}, ${labels.tbc}`;
				if (!endDate || endDate === startDate) return `${formatDate(startDate)}, ${startTime}–${endTime}`;
				return `${formatDate(startDate)}, ${startTime} – ${formatDate(endDate)}, ${endTime}`;
			}

			function selectedText(select) {
				return select?.value ? select.selectedOptions[0].text : '';
			}

			function summarise(row) {
				if (!compact) return;
				const ensembles = Array.from(row.querySelectorAll('[name$="[ensemble_ids][]"]:checked'), input => input.closest('label').textContent.trim());
				const isSingle = row.querySelector('.form-selectgroup') === null;
				const vanDriver = field(row, '[van_driver_id]');
				const parts = [
					ensembles.length === 0 ? labels.rehearsal : (isSingle ? labels.concert : `${labels.concert}: ${ensembles.join(', ')}`),
					field(row, '[location_id]').selectedOptions[0]?.text,
					selectedText(field(row, '[setup_group_id]')),
					vanDriver?.value ? labels.van_driver.replace(':name', selectedText(vanDriver)) : '',
				];
				row.querySelector('.term-date-title').textContent = scheduleLabel(row);
				row.querySelector('.term-date-summary').textContent = parts.filter(Boolean).join(' · ');
				const isNew = row.dataset.new === '1';
				const removed = !row.querySelector('.term-date-removed').classList.contains('d-none');
				const changed = Array.from(row.querySelectorAll('[data-initial]')).some(input => valueOf(input) !== (input.getAttribute('data-initial') ?? ''));
				row.querySelector('.term-date-new').classList.toggle('d-none', !isNew || removed);
				row.querySelector('.term-date-changed').classList.toggle('d-none', isNew || removed || !changed);
			}

			function addRow() {
				const row = buildRow(nextIndex());
				list.appendChild(row);
				registerRow(row);
				return row;
			}

			function duplicateRow(sourceRow) {
				const row = buildRow(nextIndex());
				// The template has no hidden id, so the duplicate is saved as a new term date.
				row.querySelectorAll('[name]').forEach(el => {
					const suffix = el.name.replace(/^term_dates\[[^\]]+\]/, '');
					const source = suffix ? sourceRow.querySelector(`[name$="${suffix}"]${el.type === 'checkbox' ? `[value="${el.value}"]` : ''}`) : null;
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

			addBtn?.addEventListener('click', () => addRow());

			const defaultSelect = document.getElementById('location_id');
			function updateDefaultLabels() {
				const label = form.dataset.defaultLabel.replace(':location', defaultSelect.value ? defaultSelect.selectedOptions[0].text : form.dataset.tbcLabel);
				[document, template.content].forEach(root => root.querySelectorAll('[data-default-location]').forEach(option => option.text = label));
				list.querySelectorAll('.term-date-row').forEach(row => summarise(row));
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
				bindRowButtons(row);
				summarise(row);
			});

			if (compact) {
				form.addEventListener('input', event => event.target.closest?.('.term-date-row') && summarise(event.target.closest('.term-date-row')));
				form.addEventListener('change', event => event.target.closest?.('.term-date-row') && summarise(event.target.closest('.term-date-row')));
				document.getElementById('expand-term-dates').addEventListener('click', () => list.querySelectorAll('.term-date-row').forEach(row => setOpen(row, true)));
				document.getElementById('collapse-term-dates').addEventListener('click', () => list.querySelectorAll('.term-date-row').forEach(row => setOpen(row, false)));

				const repeatError = document.getElementById('repeat-dates-error');
				document.getElementById('term-repeat-dates').addEventListener('submit', event => {
					event.preventDefault();
					const first = new Date(`${document.getElementById('repeat-first').value}T00:00:00Z`);
					const last = new Date(`${document.getElementById('repeat-last').value}T00:00:00Z`);
					const stepDays = 7 * parseInt(document.getElementById('repeat-weeks').value);
					const dates = [];
					for (let day = new Date(first); day <= last && dates.length <= 100; day.setUTCDate(day.getUTCDate() + stepDays)) {
						dates.push(day.toISOString().slice(0, 10));
					}
					repeatError.textContent = last < first ? labels.last_before_first : (dates.length > 100 ? labels.too_many : '');
					if (repeatError.textContent) return;

					const added = dates.map(date => {
						const row = buildRow(nextIndex());
						const next = Array.from(list.children).find(other => (field(other, '[start_datetime][date]').value || '9999') > date);
						list.insertBefore(row, next ?? null);
						registerRow(row);
						field(row, '[start_datetime][date]').value = date;
						field(row, '[end_datetime][date]').value = date;
						field(row, '[start_datetime][time]').value = document.getElementById('repeat-start').value;
						field(row, '[end_datetime][time]').value = document.getElementById('repeat-end').value;
						setOpen(row, false);
						summarise(row);
						return row;
					});
					event.target.reset();
					document.querySelector('#repeat-dates-panel [data-bs-toggle="collapse"]').click();
					added[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
					list.dispatchEvent(new Event('change', { bubbles: true }));
				});
			}
		});
	</script>
</x-layout>

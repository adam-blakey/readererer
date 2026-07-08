@props(['term', 'page_name', 'ensembles', 'setup_groups', 'van_drivers', 'form_route', 'form_method'])

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col">
					<h1 class="my-0 font-bold">Edit term: {{ $term->name }}</h1>
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<button aria-label="Save" class="btn btn-primary" form="term-edit-form" type="submit">
							Save
						</button>
					</div>
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
							<h2 class="mb-0 card-heading">Edit term details</h2>
						</div>
						<div class="card-body">
							<form action="{{ $form_route }}" id="term-edit-form" method="POST">
								@csrf
                                @method($form_method)

								<div class="row g-5">
									<div class="col-xl-12">
										<div class="mb-3">
											<label class="form-label">ID</label>
											<input class="form-control" disabled id="id" name="id" type="text" value="{{ $term->id }}">
										</div>
										<div class="mb-3">
											<label class="form-label" for="name">Name</label>
   								            <input class="form-control" id="name" name="name" placeholder="Term name" type="text" value="{{ old('name', $term->name) }}" data-initial="{{ $term->name }}">
											@error('name')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div class="mb-3">
											<label class="form-label" for="slug">Slug</label>
   								            <input class="form-control" id="slug" name="slug" placeholder="term-slug" type="text" value="{{ old('slug', $term->slug) }}" data-initial="{{ $term->slug }}">
											@error('slug')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
                                        <hr />
										<div class="mb-3">
    							            <label class="card-title">Term dates</label>

                                            {{--
                                                A single source of truth for a term-date row. The server-rendered
                                                rows below reuse the same column layout, and the "Add date" /
                                                "Duplicate" buttons clone this template, so new rows always carry
                                                every field (start, end, concert, setup group, van driver) and
                                                stay consistent with the existing ones.
                                            --}}
                                            <template id="term-date-row-template">
                                                <div class="row g-2 align-items-end term-date-row mb-2" data-index="__INDEX__">
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <label class="form-label">Start</label>
                                                        <input class="form-control" name="term_dates[__INDEX__][start_datetime]" type="datetime-local" data-initial="" required>
                                                    </div>
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <label class="form-label">End</label>
                                                        <input class="form-control" name="term_dates[__INDEX__][end_datetime]" type="datetime-local" data-initial="" required>
                                                    </div>
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <label class="form-label">Concert</label>
                                                        <select class="form-select" name="term_dates[__INDEX__][ensemble_id]" data-initial="">
                                                            <option value="">Not a concert</option>
                                                            @foreach(($ensembles ?? collect()) as $ens)
                                                                <option value="{{ $ens->id }}">{{ $ens->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <label class="form-label">Setup group</label>
                                                        <select class="form-select" name="term_dates[__INDEX__][setup_group_id]" data-initial="">
                                                            <option value="">No setup group</option>
                                                            @foreach(($setup_groups ?? collect()) as $setup_group)
                                                                <option value="{{ $setup_group->id }}">{{ $setup_group->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-6 col-md-4 col-lg-2">
                                                        <label class="form-label">Van driver override</label>
                                                        <select class="form-select" name="term_dates[__INDEX__][van_driver_id]" data-initial="">
                                                            <option value="">Infer van driver</option>
                                                            @foreach(($van_drivers ?? collect()) as $van_driver)
                                                                <option value="{{ $van_driver->id }}">{{ $van_driver->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-2">
                                                        <div class="btn-list">
                                                            <button class="btn btn-outline-secondary duplicate-term-date" type="button">Duplicate</button>
                                                            <button class="btn btn-outline-danger remove-term-date" type="button">Remove</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

    							            <div id="term-dates-list">
                                                @php
                                                    $existingDates = $term->term_dates?->sortBy('start_datetime') ?? collect();
                                                    $prefillDates = collect(old('term_dates'));
                                                    if ($prefillDates->isEmpty()) {
                                                        $prefillDates = $existingDates->map(function ($d) {
                                                            return [
                                                                'id' => $d->id,
                                                                'start_datetime' => optional($d->start_datetime)->format('Y-m-d\TH:i'),
                                                                'end_datetime' => optional($d->end_datetime)->format('Y-m-d\TH:i'),
                                                                'ensemble_id' => $d->concert_ensemble_id,
                                                                'setup_group_id' => $d->setup_group_id,
                                                                'van_driver_id' => $d->van_driver_id,
                                                            ];
                                                        });
                                                    }
                                                @endphp
                                                @foreach ($prefillDates as $i => $date)
                                                    @php
                                                        $orig = !empty($date['id'])
                                                            ? ($term->term_dates ?? collect())->firstWhere('id', $date['id'])
                                                            : null;
                                                        $initialStart = optional($orig?->start_datetime)->format('Y-m-d\TH:i') ?? '';
                                                        $initialEnd = optional($orig?->end_datetime)->format('Y-m-d\TH:i') ?? '';
                                                        $selectedEnsemble = $date['ensemble_id'] ?? ($orig->concert_ensemble_id ?? null);
                                                        $selectedSetupGroup = $date['setup_group_id'] ?? ($orig->setup_group_id ?? null);
                                                        $selectedVanDriver = $date['van_driver_id'] ?? ($orig->van_driver_id ?? null);
                                                    @endphp
                                                    <div class="row g-2 align-items-end term-date-row mb-2" data-index="{{ $i }}">
                                                        @if (!empty($date['id']))
                                                            <input type="hidden" name="term_dates[{{ $i }}][id]" value="{{ $date['id'] }}">
                                                        @endif
                                                        <div class="col-6 col-md-4 col-lg-2">
                                                            <label class="form-label">Start</label>
                                                            @error('term_dates.' . $i . '.start_datetime')
                                                                <x-forms.input-error :messages="$message" />
                                                            @enderror
                                                            <input class="form-control @error('term_dates.' . $i . '.start_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][start_datetime]" type="datetime-local" value="{{ $date['start_datetime'] ?? '' }}" data-initial="{{ $initialStart }}" required>
                                                        </div>
                                                        <div class="col-6 col-md-4 col-lg-2">
                                                            <label class="form-label">End</label>
                                                            @error('term_dates.' . $i . '.end_datetime')
                                                                <x-forms.input-error :messages="$message" />
                                                            @enderror
                                                            <input class="form-control @error('term_dates.' . $i . '.end_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][end_datetime]" type="datetime-local" value="{{ $date['end_datetime'] ?? '' }}" data-initial="{{ $initialEnd }}" required>
                                                        </div>
                                                        <div class="col-6 col-md-4 col-lg-2">
                                                            <label class="form-label">Concert</label>
                                                            <select class="form-select" name="term_dates[{{ $i }}][ensemble_id]" data-initial="{{ $orig?->concert_ensemble_id ?? '' }}">
                                                                <option value="" {{ empty($selectedEnsemble) ? 'selected' : '' }}>Not a concert</option>
                                                                @foreach(($ensembles ?? collect()) as $ens)
                                                                    <option value="{{ $ens->id }}" {{ (int)$selectedEnsemble === (int)$ens->id ? 'selected' : '' }}>{{ $ens->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-6 col-md-4 col-lg-2">
                                                            <label class="form-label">Setup group</label>
                                                            <select class="form-select" name="term_dates[{{ $i }}][setup_group_id]" data-initial="{{ $orig?->setup_group_id ?? '' }}">
                                                                <option value="" {{ empty($selectedSetupGroup) ? 'selected' : '' }}>No setup group</option>
                                                                @foreach(($setup_groups ?? collect()) as $setup_group)
                                                                    <option value="{{ $setup_group->id }}" {{ (int)$selectedSetupGroup === (int)$setup_group->id ? 'selected' : '' }}>{{ $setup_group->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-6 col-md-4 col-lg-2">
                                                            <label class="form-label">Van driver override</label>
                                                            <select class="form-select" name="term_dates[{{ $i }}][van_driver_id]" data-initial="{{ $orig?->van_driver_id ?? '' }}">
                                                                <option value="" {{ empty($selectedVanDriver) ? 'selected' : '' }}>Infer van driver</option>
                                                                @foreach(($van_drivers ?? collect()) as $van_driver)
                                                                    <option value="{{ $van_driver->id }}" {{ (int)$selectedVanDriver === (int)$van_driver->id ? 'selected' : '' }}>{{ $van_driver->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-lg-2">
                                                            <div class="btn-list">
                                                                <button class="btn btn-outline-secondary duplicate-term-date" type="button">Duplicate</button>
                                                                <button class="btn btn-outline-danger remove-term-date" type="button">Remove</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
											</div>
											<div class="mt-2">
												<button class="btn btn-outline-primary" id="add-term-date" type="button">Add date</button>
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

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const form = document.getElementById('term-edit-form');
			const list = document.getElementById('term-dates-list');
			const addBtn = document.getElementById('add-term-date');
			const template = document.getElementById('term-date-row-template');

			// Highlight a field once its value differs from the value it started with.
			function attachChangeWatcher(input) {
				if (!input) return;
				const initial = input.getAttribute('data-initial') ?? '';
				function apply() {
					const changed = (input.value ?? '') !== initial;
					input.classList.toggle('border-2', changed);
					input.classList.toggle('border-solid', changed);
					input.classList.toggle('border-info', changed);
				}
				input.addEventListener('input', apply);
				input.addEventListener('change', apply);
				apply();
			}

			function nextIndex() {
				let max = -1;
				list.querySelectorAll('.term-date-row').forEach(row => {
					const idx = parseInt(row.getAttribute('data-index'));
					if (!isNaN(idx)) max = Math.max(max, idx);
				});
				return max + 1;
			}

			// Clone the shared template, stamping the row's index into every field name.
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
				// Copy the source row's values across; the template carries no hidden
				// id, so the duplicate is always saved as a brand new term date.
				row.querySelectorAll('[name]').forEach(el => {
					const field = (el.name.match(/\[([^\]]+)\]$/) || [])[1];
					const source = field ? sourceRow.querySelector(`[name$="[${field}]"]`) : null;
					if (source && source.type !== 'hidden') {
						el.value = source.value;
					}
				});
				sourceRow.insertAdjacentElement('afterend', row);
				registerRow(row);
			}

			addBtn?.addEventListener('click', addRow);

			// Wire up the fields and rows rendered by the server.
			form?.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
			list.querySelectorAll('.term-date-row').forEach(row => {
				row.querySelector('.remove-term-date')?.addEventListener('click', () => row.remove());
				row.querySelector('.duplicate-term-date')?.addEventListener('click', () => duplicateRow(row));
			});
		});
	</script>
</x-layout>

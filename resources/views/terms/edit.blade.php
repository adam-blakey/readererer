@props(['term', 'page_name'])

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
							<form action="{{ route('terms.update', ['term' => $term]) }}" id="term-edit-form" method="POST">
								@csrf
								@method('PUT')

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
                                            <script type="text/template" id="ensemble-options-template">
                                                <option value="">Not a concert</option>
                                                @foreach(($ensembles ?? collect()) as $ens)
                                                    <option value="{{ $ens->id }}">{{ $ens->name }}</option>
                                                @endforeach
                                            </script>
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
                                                                'concert_ensemble_id' => $d->concert_ensemble_id,
                                                            ];
                                                        });
                                                    }
                                                @endphp
                                                @foreach ($prefillDates as $i => $date)
                                                    @php
                                                        $initialStart = '';
                                                        $initialEnd = '';
                                                        if (!empty($date['id'])) {
                                                            $orig = ($term->term_dates ?? collect())->firstWhere('id', $date['id']);
                                                            $initialStart = optional($orig?->start_datetime)->format('Y-m-d\TH:i');
                                                            $initialEnd = optional($orig?->end_datetime)->format('Y-m-d\TH:i');
                                                        }
                                                        $selectedEnsemble = $date['ensemble_id'] ?? ($orig->concert_ensemble_id ?? null);
                                                    @endphp
                                                    <div class="row g-2 align-items-end term-date-row mb-2" data-index="{{ $i }}">
                                                        @if (!empty($date['id']))
                                                            <input type="hidden" name="term_dates[{{ $i }}][id]" value="{{ $date['id'] }}">
                                                        @endif
                                                        <div class="col-md-4">
                                                            <label class="form-label">Start</label>
                                                            @error('term_dates.' . $i . '.start_datetime')
                                                                <x-forms.input-error :messages="$message" />
                                                            @enderror
                                                            <input class="form-control @error('term_dates.' . $i . '.start_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][start_datetime]" type="datetime-local" value="{{ $date['start_datetime'] ?? '' }}" data-initial="{{ $initialStart }}" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">End</label>
                                                            @error('term_dates.' . $i . '.end_datetime')
                                                                <x-forms.input-error :messages="$message" />
                                                            @enderror
                                                            <input class="form-control @error('term_dates.' . $i . '.end_datetime') is-invalid @enderror" name="term_dates[{{ $i }}][end_datetime]" type="datetime-local" value="{{ $date['end_datetime'] ?? '' }}" data-initial="{{ $initialEnd }}" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Concert</label>
                                                            <select class="form-select" name="term_dates[{{ $i }}][ensemble_id]" data-initial="{{ $orig->concert_ensemble_id ?? '' }}">
                                                                <option value="" {{ empty($selectedEnsemble) ? 'selected' : '' }}>Not a concert</option>
                                                                @foreach(($ensembles ?? collect()) as $ens)
                                                                    <option value="{{ $ens->id }}" {{ (int)$selectedEnsemble === (int)$ens->id ? 'selected' : '' }}>{{ $ens->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="btn-list w-100 d-flex">
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
            const optionsTpl = document.getElementById('ensemble-options-template');
            const ensembleOptions = optionsTpl ? (optionsTpl.textContent || '').trim() : '<option value="">Not a concert</option>';

			function attachChangeWatcher(input) {
				if (!input) return;
				const initial = input.getAttribute('data-initial') ?? '';
				function apply() {
					const current = input.value ?? '';
					if (current !== initial) {
                        input.classList.add('border-2');
                        input.classList.add('border-solid');
                        input.classList.add('border-info');
					}
				}
				input.addEventListener('input', apply);
				input.addEventListener('change', apply);
				apply();
			}

			// Attach to existing fields
			form?.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);

			function nextIndex() {
				const rows = list.querySelectorAll('.term-date-row');
				let max = -1;
				rows.forEach(r => {
					const idx = parseInt(r.getAttribute('data-index'));
					if (!isNaN(idx)) max = Math.max(max, idx);
				});
				return max + 1;
			}

			function makeRow(i) {
				const row = document.createElement('div');
				row.className = 'row g-2 align-items-end term-date-row';
				row.setAttribute('data-index', i);
				row.innerHTML = `
					<div class="col-md-4">
						<label class="form-label">Start</label>
						<input class="form-control border-2 border-solid border-info" name="term_dates[${i}][start_datetime]" type="datetime-local" data-initial="">
					</div>
					<div class="col-md-4">
						<label class="form-label">End</label>
						<input class="form-control border-2 border-solid border-info" name="term_dates[${i}][end_datetime]" type="datetime-local" data-initial="">
					</div>
					<div class="col-md-2">
						<label class="form-label">Concert</label>
							<select class="form-select border-2 border-solid border-info" name="term_dates[${i}][ensemble_id]" data-initial="">
							${ensembleOptions}
						</select>
					</div>
					<div class="col-md-2">
						<div class="btn-list w-100 d-flex">
							<button class="btn btn-outline-secondary duplicate-term-date" type="button">Duplicate</button>
							<button class="btn btn-outline-danger remove-term-date" type="button">Remove</button>
						</div>
					</div>
				`;
				return row;
			}

			function bindRemoveButtons(scope=document) {
				scope.querySelectorAll('.remove-term-date').forEach(btn => {
					btn.addEventListener('click', function () {
						const row = this.closest('.term-date-row');
						if (row) row.remove();
					});
				});
			}

			function bindDuplicateButtons(scope=document) {
				scope.querySelectorAll('.duplicate-term-date').forEach(btn => {
					btn.addEventListener('click', function () {
						const srcRow = this.closest('.term-date-row');
						if (!srcRow) return;
						const i = nextIndex();
						const clone = srcRow.cloneNode(true);
						clone.setAttribute('data-index', i);
						// Update names and prepare fields
						clone.querySelectorAll('input, select, textarea').forEach(el => {
							if (el.name) {
								el.name = el.name.replace(/term_dates\[\d+\]/, `term_dates[${i}]`);
							}
							// Remove hidden id field or neutralize it
							if (el.type === 'hidden' && /\[id\]$/.test(el.name || '')) {
								el.remove();
								return;
							}
							// Mark as changed relative to empty initial
							el.setAttribute('data-initial', '');
							el.classList.add('border-2','border-solid','border-info');
						});
						// Ensure no duplicate hidden id remains
						clone.querySelectorAll('input[type="hidden"]').forEach(h => { if (/\[id\]$/.test(h.name || '')) h.remove(); });
						// Insert after the source row
						srcRow.insertAdjacentElement('afterend', clone);
						bindRemoveButtons(clone);
						bindDuplicateButtons(clone);
						clone.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
					});
				});
			}

			addBtn?.addEventListener('click', function () {
				const i = nextIndex();
				const row = makeRow(i);
				list.appendChild(row);
				bindRemoveButtons(row);
				bindDuplicateButtons(row);
				row.querySelectorAll('[data-initial]').forEach(attachChangeWatcher);
			});

			bindRemoveButtons();
			bindDuplicateButtons();
		});
	</script>
</x-layout>

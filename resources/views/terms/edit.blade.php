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
											<input class="form-control" id="name" name="name" placeholder="Term name" type="text" value="{{ old('name', $term->name) }}">
											@error('name')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<div class="mb-3">
											<label class="form-label" for="slug">Slug</label>
											<input class="form-control" id="slug" name="slug" placeholder="term-slug" type="text" value="{{ old('slug', $term->slug) }}">
											@error('slug')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
                                        <hr />
										<div class="mb-3">
											<label class="card-title">Term dates</label>
											<div id="term-dates-list">
												@php $existingDates = $term->term_dates?->sortBy('start_datetime') ?? collect(); @endphp
                                                @foreach ($existingDates as $i => $date)
													<div class="row g-2 align-items-end term-date-row mb-2" data-index="{{ $i }}">
														<input type="hidden" name="term_dates[{{ $i }}][id]" value="{{ $date->id }}">
														<div class="col-md-5">
															<label class="form-label">Start</label>
															<input class="form-control" name="term_dates[{{ $i }}][start_datetime]" type="datetime-local" value="{{ optional($date->start_datetime)->format('Y-m-d\TH:i') }}">
														</div>
														<div class="col-md-5">
															<label class="form-label">End</label>
															<input class="form-control" name="term_dates[{{ $i }}][end_datetime]" type="datetime-local" value="{{ optional($date->end_datetime)->format('Y-m-d\TH:i') }}">
														</div>
														<div class="col-md-2">
															<button class="btn btn-outline-danger w-100 remove-term-date" type="button">Remove</button>
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
			const list = document.getElementById('term-dates-list');
			const addBtn = document.getElementById('add-term-date');

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
					<div class="col-md-5">
						<label class="form-label">Start</label>
						<input class="form-control" name="term_dates[${i}][start_datetime]" type="datetime-local">
					</div>
					<div class="col-md-5">
						<label class="form-label">End</label>
						<input class="form-control" name="term_dates[${i}][end_datetime]" type="datetime-local">
					</div>
					<div class="col-md-2">
						<button class="btn btn-outline-danger w-100 remove-term-date" type="button">Remove</button>
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

			addBtn?.addEventListener('click', function () {
				const i = nextIndex();
				const row = makeRow(i);
				list.appendChild(row);
				bindRemoveButtons(row);
			});

			bindRemoveButtons();
		});
	</script>
</x-layout>

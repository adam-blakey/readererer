@props(['term', 'page_name', 'ensembles', 'nextRehearsal' => null, 'nextConcert' => null])

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col">
					<h1 class="my-0 font-bold">{{ $term->name }}</h1>
					@if($term->term_dates_count ?? $term->term_dates?->count())
						<span class="badge bg-blue text-blue-fg">{{ $term->term_dates_count ?? $term->term_dates->count() }} dates</span>
					@endif
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<a aria-label="Edit" class="btn" href="{{ route('terms.edit', ['term' => $term]) }}">
							<svg class="icon icon-tabler icons-tabler-outline icon-tabler-pencil" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
								<path d="M0 0h24v24H0z" fill="none" stroke="none" />
								<path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
								<path d="M13.5 6.5l4 4" />
							</svg>
							Edit
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="page-body">
		<div class="container-xl">
			<div class="row g-3">
				<div class="col">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">All dates</h2>
						</div>
                        <x-term-dates-table :term_dates="$term->term_dates" :ensembles="$ensembles" />
					</div>
				</div>
				<div class="col-lg-4">
					<div class="row row-cards">
						<div class="col-12">
							<div class="card">
                                <div class="card-header">
                                    <h2 class="mb-0 card-heading">Term details</h2>
                                </div>
								<div class="card-body">
									<div class="mb-2"><strong>Created:</strong> {{ $term->created_at?->diffForHumans() }}</div>
									<div class="mb-2"><strong>Updated:</strong> {{ $term->updated_at?->diffForHumans() }}</div>
                                    <div class="mb-2"><strong>Slug:</strong> {{ $term->slug }}</div>
                                    <div class="mb-2"><strong>Range:</strong> {{ $term->formattedTermDateRange }}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

@props(['term', 'page_name', 'nextRehearsal' => null, 'nextConcert' => null])

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
						<div class="card-body">
                            @if(($term->term_dates?->count() ?? 0) === 0)
                                Nothing scheduled.
                            @else
                                <div class="accordion" id="term-date-accordion">
                                    @foreach($term->term_dates->sortBy('start_datetime') as $td)
                                        <div class="accordion-item">
                                            <div class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $td->id }}-default" aria-expanded="false">
                                                    @if($td->start_datetime->isPast())
                                                        <strike>{{ $td->name }}</strike>
                                                    @else
                                                        {{ $td->name }}
                                                    @endif
                                                    @if($td->concert_ensemble_id)
                                                        <span class="badge bg-green text-green-fg ms-2">Concert @if($td->concert_ensemble) ({{ $td->concert_ensemble->name }}) @endif</span>
                                                    @else
                                                        <span class="badge bg-gray text-muted mx-2">Rehearsal</span>
                                                    @endif
                                                    @if ($td->setup_group != null)
                                                        <x-setup-group-badge :setup_group="$td->setup_group" />
                                                    @endif
                                                    @if($td->inferred_van_driver == null)
                                                        <span class="badge bg-red text-red-fg ms-2">No van driver!</span>
                                                    @else
                                                        <span class="badge bg-info text-info-fg ms-2">Van: {{ ($td->inferred_van_driver == null) ? 'None' : $td->inferred_van_driver->name }}</span>
                                                    @endif
                                                </button>
                                            </div>
                                            <div id="collapse-{{ $td->id }}-default" class="accordion-collapse collapse" data-bs-parent="#term-date-accordion">
                                                <div class="accordion-body">
                                                    <div class="mb-2">
                                                        <div>
                                                            <x-icon name="user-edit" /><strong>Attending: </strong> 0
                                                        </div>
                                                        <div>
                                                            <x-icon name="user-off" /><strong>Absent: </strong> 0
                                                        </div>
                                                        <div>
                                                            <x-icon name="mail" /><strong>Email history: </strong>
                                                            <div class="mx-2">
                                                                <div><x-icon name="check" /> Setup reminder to Group B: about 2 days ago</div>
                                                                <div><x-icon name="alert-square" />Failed setup reminder to Ensemble A: about 1 day ago</div>
                                                                <div><x-icon name="check" />Setup reminder to Ensemble A: 34 minutes ago</div>
                                                                <div><x-icon name="check" />Setup reminder to Group B: 34 minutes ago</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- TODO: Obviously these need to work. -->
                                                    <div class="btn-list">
                                                        <a class="btn bg-orange text-orange-fg disabled">
                                                            <x-icon name="list-check" />
                                                            Send attendance list now
                                                            <div class="badge bg-white text-white-fg ms-2">scheduled Sun 8:00</div>
                                                        </a>
                                                        <!-- TODO: This currently just gets for the first ensemble. -->
                                                        <x-a class="btn bg-orange text-orange-fg" href="{{ route('seating-plan.show', ['ensemble' => \App\Models\Ensemble::first(), 'termDate' => $td]) }}" target="_blank">
                                                            <x-icon name="armchair" />
                                                            View seating plan
                                                        </x-a>
                                                        <a class="btn bg-info text-info-fg disabled">
                                                            <x-icon name="bell-ringing" />
                                                            Resend setup reminder
                                                            <div class="badge bg-white text-white-fg ms-2">sent Thu 8:00</div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
						</div>
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

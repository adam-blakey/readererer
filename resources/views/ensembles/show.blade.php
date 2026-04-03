@props(['ensemble', 'page_name'])

@php
	$terms = App\Models\Term::all()->sortBy('earliest_date');
@endphp

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-auto">
					<span class="rounded avatar avatar-lg" style="background-image: url({{ $ensemble->image }})"></span>
				</div>
				<div class="col">
					<h1 class="my-0 font-bold">{{ $ensemble->name }}</h1>
					<span class="badge bg-blue text-blue-fg">You're a member!</span>
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
                        <x-a href="{{ route('ensembles.seating-plan.show', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="users-group" />Seating plan</x-a>
                        <x-a href="{{ route('ensembles.edit', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="pencil" />Edit</x-a>
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
                            <h2 class="mb-0 card-heading">Active polls</h2>
                        </div>
                        <div class="card-body">
                            @if($upcomingTerms->count() == 0)
                                Nothing upcoming.
                            @else
                                @foreach($upcomingTerms as $term)
                                    <x-poll-entry :ensemble="$ensemble" :term="$term" />
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="mb-3 card">
                        <div class="card-header">
                            <h2 class="mb-0 card-heading">Upcoming rehearsals and concerts</h2>
                        </div>
                        <div class="card-body">
                            <div class="card-title">Next rehearsal</div>
                            <x-rehearsal-entry :term_date="$nextRehearsal" />
                        </div>
                        <div class="card-body">
                            <div class="card-title">Next concert</div>
                            <x-rehearsal-entry :term_date="$nextConcert" />
                        </div>
                    </div>
				</div>
				<div class="col-lg-4">
					<div class="row row-cards">
						<div class="col-12">
							<div class="card">
								<div class="card-body">
									<div class="card-title">Member info</div>
									<div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-cog" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                           <path d="M6 21v-2a4 4 0 0 1 4 -4h2.5"></path>
                                           <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                           <path d="M19.001 15.5v1.5"></path>
                                           <path d="M19.001 21v1.5"></path>
                                           <path d="M22.032 17.25l-1.299 .75"></path>
                                           <path d="M17.27 20.75l-1.3 .75"></path>
                                           <path d="M15.97 17.25l1.3 .75"></path>
                                           <path d="M20.733 20.75l1.3 .75"></path>
                                        </svg>
										Admins ({{ $ensemble->admins->count() }}):
										@foreach ($ensemble->admins as $admin)
											<x-user-entry :user="$admin" />
										@endforeach
									</div>
									<div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                           <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                           <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                                           <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                           <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                           <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path>
                                        </svg>
										Members ({{ $ensemble->users->count() }}):
										@foreach ($ensemble->users as $user)
											<x-user-entry :user="$user" />
										@endforeach
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

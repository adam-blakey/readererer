@props(['ensemble'])

@use('App\Enums\UserRole')
@use('Illuminate\Support\Str')

@php
	$currentUser = auth()->user();
	$isMember = $currentUser->ensembles->contains($ensemble);
	$canManage = $currentUser->role->value >= UserRole::Moderator->value;
@endphp

<x-layout>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="rounded avatar avatar-lg" style="background-image: url({{ $ensemble->image }})"></span>
            </div>
            <div class="col">
                <h1 class="my-0 font-bold">{{ $ensemble->name }}</h1>
                <div class="mt-1 list-inline list-inline-dots text-muted">
                    <span class="list-inline-item">
                        <x-icon name="users" />{{ $ensemble->number_of_members }} {{ Str::plural('member', $ensemble->number_of_members) }}
                    </span>
                    @if ($isMember)
                        <span class="list-inline-item"><span class="badge bg-blue text-blue-fg">You're a member!</span></span>
                    @endif
                </div>
            </div>
            @if ($canManage)
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <x-a href="{{ route('ensembles.members', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="users" />Members</x-a>
                        @if ($ensemble->seating_plan_enabled)
                            <x-a href="{{ route('ensembles.seating-plan.show', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="users-group" />Seating plan</x-a>
                        @endif
                        <x-a href="{{ route('ensembles.edit', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="pencil" />Edit</x-a>
                    </div>
                </div>
            @endif
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
                            @if($ensemble->users->isEmpty())
                                <p class="mb-0 text-muted">This ensemble has no members yet. Add members before polls become available.</p>
                            @elseif($upcomingTerms->count() == 0)
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
                            <x-rehearsal-entry :ensembles="collect([$ensemble])" :term_date="$nextRehearsal" />
                        </div>
                        <div class="card-body">
                            <div class="card-title">Next concert</div>
                            <x-rehearsal-entry :ensembles="collect([$ensemble])" :term_date="$nextConcert" />
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
                                        <x-icon name="user-cog" />
										Admins ({{ $ensemble->admins->count() }}):
										@foreach ($ensemble->admins as $admin)
											<x-user-entry :user="$admin" secondary_info=" " />
										@endforeach
									</div>
									<div class="mb-2">
                                        <x-icon name="users" />
										Members ({{ $ensemble->number_of_members }}):
										@foreach ($ensemble->users as $user)
                                            <x-user-entry :user="$user" :add_route="false" :secondary_info="$user->membership($ensemble)" />
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

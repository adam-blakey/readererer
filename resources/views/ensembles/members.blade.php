@props(['ensemble'])

<x-layout>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="rounded avatar avatar-lg" style="background-image: url({{ $ensemble->image }})"></span>
            </div>
            <div class="col">
                <div class="page-pretitle">{{ $page_subname }}</div>
                <h1 class="my-0 font-bold">{{ $ensemble->name }}</h1>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <x-a href="{{ route('ensembles.show', ['ensemble' => $ensemble]) }}" class="btn"><x-icon name="arrow-left" />{{ __('Back to ensemble') }}</x-a>
                    @can('update', $ensemble)
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-user-ensemble"><x-icon name="user-plus" />{{ __('Add user') }}</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

	<div class="page-body">
		<div class="container-xl">
			<div class="card">
				<div class="card-header">
					<h2 class="mb-0 card-heading">{{ __('Members (:count)', ['count' => $ensemble->users->count()]) }}</h2>
				</div>
				<div class="table-responsive">
					@if ($ensemble->users->isEmpty())
						<p class="p-5 mb-0 text-muted">{{ __('This ensemble has no members yet.') }}</p>
					@else
						<table class="table table-vcenter card-table">
							<thead>
								<tr>
									<th>{{ __('Member') }}</th>
									<th>{{ __('Instrument family') }}</th>
									<th>{{ __('Seat') }}</th>
									<th>{{ __('Setup group') }}</th>
									@can('update', $ensemble)
										<th class="w-1"></th>
									@endcan
								</tr>
							</thead>
							<tbody>
								@foreach ($ensemble->users as $member)
									<tr>
										<td>
											<div class="d-flex align-items-center">
												<x-avatar :user="$member" size="sm" />
												<div class="ps-2">
													<x-clickable-model :model="$member" />
													<div class="mt-1 small text-muted">{{ $member->role_description }}</div>
												</div>
											</div>
										</td>
										<td>{{ $member->pivot->instrumentFamily?->name ?? '-' }}</td>
										<td>{{ $member->pivot->seat ?: '-' }}</td>
										<td>
											@if ($member->setup_group != null)
												<x-setup-group-badge :setup_group="$member->setup_group" />
											@else
												-
											@endif
										</td>
										@can('update', $ensemble)
											<td>
												<form method="POST" action="{{ route('ensembles.remove_user', [$ensemble, $member]) }}" onsubmit="return confirm('{{ __('Remove :member from :ensemble?', ['member' => $member->name, 'ensemble' => $ensemble->name]) }}');">
													@csrf
													<button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Remove') }}</button>
												</form>
											</td>
										@endcan
									</tr>
								@endforeach
							</tbody>
						</table>
					@endif
				</div>
			</div>
		</div>
	</div>
</x-layout>

@can('update', $ensemble)
	<x-modals.add-user-ensemble :ensemble="$ensemble" />
@endcan

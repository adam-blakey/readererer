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
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<button aria-label="Save" class="btn btn-primary" form="ensemble-edit-form" type="submit">
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
				<div class="col-lg-6">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Edit ensemble details</h2>
						</div>
						<div class="card-body">
							<form action="{{ route('ensembles.update', ['ensemble' => $ensemble]) }}" id="ensemble-edit-form" method="POST">
								@csrf
								@method('PUT')

								<div class="row g-5">
									<div class="col-xl-6">
										<div class="mb-3">
											<label class="form-label">ID</label>
											<input class="form-control" disabled id="id" name="id" type="text" value="{{ $ensemble->id }}">
										</div>
										<div class="mb-3">
											<label class="form-label" for="name">Name</label>
											<input class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Ensemble name" type="text" value="{{ old('name', $ensemble->name) }}">
											@error('name')
												<x-forms.input-error :messages="$message" />
											@enderror
										</div>
										<hr />
										<div class="mb-3">
											<label class="form-check form-switch">
												<input class="form-check-input" type="checkbox" name="seating_plan_enabled" value="1" @checked(old('seating_plan_enabled', $ensemble->seating_plan_enabled))>
												<span class="form-check-label">Seating plan enabled</span>
											</label>
											<div class="form-hint">When off, this ensemble has no seating plan and members are added without a seat.</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3 card">
                        <div class="card-header">
                            <h2 class="mb-0 card-heading">Edit members ({{ count($ensemble->users) }})</h2>
                            <div class="card-actions">
                                <a href="#" class="btn btn-primary btn-3" data-bs-toggle="modal" data-bs-target="#modal-add-user-ensemble">
                                    Add user
                                </a>
                            </div>
                        </div>
						<div class="card-body">
                            @foreach($ensemble->users as $user)
                                <x-user-entry :user="$user" :remove_from_ensemble="$ensemble" :secondary_info="$user->membership($ensemble)" />
                            @endforeach
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

<x-modals.add-user-ensemble :ensemble="$ensemble" />

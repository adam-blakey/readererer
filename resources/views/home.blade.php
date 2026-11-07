@props(['ensembles', 'terms'])

<x-layout :show_page_header="false">
	<div class="container-xl">
		@guest
			<div class="d-flex align-items-center justify-content-between mb-3">
				<span class="text-muted">{{ __('Log in to reply to a poll.') }}</span>
				<a class="btn btn-primary" href="{{ route('login') }}"><x-icon name="login" /> {{ __('Log in') }}</a>
			</div>
		@endguest
		<div class="row row-cards">
			@forelse($ensembles as $ensemble)
				@php($linked = Auth::guest() || Gate::allows('view', $ensemble))
				<div class="col-md-6 col-lg-4">
					<x-card>
						<div class="card-header">
							<span class="avatar avatar-sm me-2" style="background-image: url({{ $ensemble->image_url }})"></span>
							<h2 class="mb-0 card-title">
								@if($linked)
									<a href="{{ route('ensembles.show', $ensemble) }}">{{ $ensemble->name }}</a>
								@else
									{{ $ensemble->name }}
								@endif
							</h2>
						</div>
						@if($terms->isEmpty())
							<x-card-body>
								<p class="mb-0 text-muted">{{ __('Nothing upcoming.') }}</p>
							</x-card-body>
						@else
							<div class="list-group list-group-flush">
								@foreach($terms as $term)
									@if($linked)
										<a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between" href="{{ route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]) }}">
											<span class="text-primary">{{ $term->name }}</span>
											<x-icon name="chevron-right" />
										</a>
									@else
										<div class="list-group-item">{{ $term->name }}</div>
									@endif
								@endforeach
							</div>
						@endif
					</x-card>
				</div>
			@empty
				<div class="col-12">
					<x-card>
						<x-card-body>
							<p class="mb-0 text-muted">{{ __('Nothing upcoming.') }}</p>
						</x-card-body>
					</x-card>
				</div>
			@endforelse
		</div>
	</div>
</x-layout>

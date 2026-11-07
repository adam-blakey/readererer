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
				<div class="col-md-6 col-lg-4">
					<x-card>
						<div class="card-header">
							<span class="avatar avatar-sm me-2" style="background-image: url({{ $ensemble->image_url }})"></span>
							<h2 class="mb-0 card-title">{{ $ensemble->name }}</h2>
						</div>
						<x-card-body>
							@forelse($terms as $term)
								<x-poll-entry :$ensemble :$term :add_route="Gate::allows('view', $ensemble)" />
							@empty
								<p class="mb-0 text-muted">{{ __('Nothing upcoming.') }}</p>
							@endforelse
						</x-card-body>
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

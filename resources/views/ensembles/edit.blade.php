@props(['ensemble', 'page_name'])

@php
	$terms = App\Models\Term::all()->sortBy('earliest_date');
@endphp

<x-layout :$page_name page_subname="Edit ensemble">
	<div class="container-xl">
		<div class="row row-cards">
			<div class="col-12">
				<x-card>
					<x-card-header>
						<h2 class="mb-0 card-heading">Core details</h2>
					</x-card-header>
					<x-card-body>
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
						<p class="mb-0 card-subtitle">Active polls</p>
						@foreach ($terms as $term)
							<p class="my-0 card-text"><a href="{{ route('attendance.poll_slug', ['ensemble' => $ensemble->slug, 'term' => $term->slug]) }}">{{ $term->name }}</a></p>
						@endforeach
					</x-card-body>
				</x-card>
			</div>

			<div class="col-md-12 col-lg-6">
				<x-card>
					<x-card-header>
						<h2 class="mb-0 card-heading">Members</h2>
					</x-card-header>
					<x-card-body>
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
					</x-card-body>
				</x-card>
			</div>

			<div class="col-md-12 col-lg-6">
				<x-card>
					<x-card-header>
						<h2 class="mb-0 card-heading">Admins</h2>
					</x-card-header>
					<x-card-body>
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
					</x-card-body>
				</x-card>
			</div>
		</div>
</x-layout>

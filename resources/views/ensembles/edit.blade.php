@props(['ensemble', 'page_name'])

@php
	$terms = App\Models\Term::all()->sortBy('earliest_date');
@endphp

<x-layout :$page_name>
	<div class="container-xl">
		<div class="row row-cards">
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h2 class="mb-0 card-heading">Core details</h2>
					</div>
					<div class="card-body">
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
						<p class="mb-0 card-subtitle">Active polls</p>
						@foreach ($terms as $term)
							<p class="my-0 card-text"><a href="{{ url('attendance/poll/' . $ensemble->slug . '/' . $term->slug) }}">{{ $term->name }}</a></p>
						@endforeach
					</div>
				</div>
			</div>

			<div class="col-md-12 col-lg-6">
				<div class="card">
					<div class="card-header">
						<h2 class="mb-0 card-heading">Members</h2>
					</div>
					<div class="card-body">
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
					</div>
				</div>
			</div>

			<div class="col-md-12 col-lg-6">
				<div class="card">
					<div class="card-header">
						<h2 class="mb-0 card-heading">Admins</h2>
					</div>
					<div class="card-body">
						<p class="mb-0 card-subtitle">Name</p>
						<p class="card-text">{{ $ensemble->name }}</p>
						<p class="mb-0 card-subtitle">Slug</p>
						<p class="card-text">{{ $ensemble->slug }}</p>
						<p class="mb-0 card-subtitle">Image</p>
						<img alt="{{ $ensemble->name }}" class="avatar avatar-xl" src="{{ asset($ensemble->image) }}">
						<p class="card-text">{{ $ensemble->image }}</p>
					</div>
				</div>
			</div>
		</div>
</x-layout>

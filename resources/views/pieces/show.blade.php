@props(['piece', 'page_name'])

<x-layout :$page_name :show_page_header="0">
	<div class="page-header">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-auto">
					<span class="rounded avatar avatar-lg" style="background-image: url({{ $piece->image }})"></span>
				</div>
				<div class="col">
					<h1 class="my-0 font-bold">{{ $piece->name }}</h1>
					<span class="badge bg-blue text-blue-fg">Wow</span>
				</div>
				<div class="col-auto ms-auto">
					<div class="btn-list">
						<a aria-label="Button" class="btn" href="{{ route('pieces.edit', ['piece' => $piece]) }}">
							<x-icon name="pencil" />
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
							<h2 class="mb-0 card-heading">Parts ({{ $piece->parts->count() }})</h2>
						</div>
						<div class="card-body">
							<ul>
								@foreach ($piece->parts as $part)
									<li>{{ $part->name }}</li>
								@endforeach
							</ul>
						</div>
					</div>
				</div>
				<div class="col">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Setlists</h2>
						</div>
						<div class="card-body">

						</div>
					</div>
				</div>
				<div class="col">
					<div class="mb-3 card">
						<div class="card-header">
							<h2 class="mb-0 card-heading">Upcoming concerts</h2>
						</div>
						<div class="card-body">

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-layout>

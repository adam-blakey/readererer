@props(['members', 'term', 'page_name', 'ensemble'])

<x-layout :$page_name page_subname="Attendance register">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<div class="card-header">
						<h2 class="mb-0 card-heading">Attendance register</h2>
						<div class="ms-auto">
							<x-a class="btn" href="{{ route('attendance.edit', ['ensemble' => $ensemble, 'term' => $term]) }}">
								<x-icon name="pencil" />
								Edit
							</x-a>
						</div>
					</div>
					<x-attendances.register :$ensemble :$members :$term />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

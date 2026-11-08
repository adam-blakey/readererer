@props(['ensemble', 'term_date', 'members', 'entries', 'polled_statuses', 'polled_dep_names', 'instrument_families', 'sortby'])

<x-layout>
	<div class="container-xl">
		@if (session('status'))
			<div class="mb-3 alert alert-success" role="alert">{{ session('status') }}</div>
		@endif
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.register :$ensemble :$entries :$instrument_families :$members :$polled_statuses :$polled_dep_names :$sortby :$term_date />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

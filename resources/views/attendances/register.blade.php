@props(['ensemble', 'term_date', 'members', 'entries', 'polled_statuses', 'instrument_families', 'sortby'])

<x-layout page_subname="Attendance register">
	<div class="container-xl">
		@if (session('status'))
			<div class="mb-3 alert alert-success" role="alert">{{ session('status') }}</div>
		@endif
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.register :$ensemble :$entries :$instrument_families :$members :$polled_statuses :$sortby :$term_date />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

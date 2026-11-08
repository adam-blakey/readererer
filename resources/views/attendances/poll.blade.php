@props(['members', 'term', 'term_dates', 'instrument_families', 'van_driver_ids', 'ensemble', 'sortby', 'sortdir', 'ended', 'next_term'])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.poll :$ensemble :$members :$sortby :$term :$term_dates :$instrument_families :$van_driver_ids :$sortdir :$ended :$next_term />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

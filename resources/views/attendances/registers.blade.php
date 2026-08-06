@props(['term_dates', 'ensembles', 'page_name'])

<x-layout :$page_name page_subname="Attendance registers overview">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.registers-table :$ensembles :$term_dates />
				</x-card>
			</div>
		</x-card-row>
		{{ $term_dates->links() }}
	</div>
</x-layout>

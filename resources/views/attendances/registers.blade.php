@props(['term_dates', 'ensembles'])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.registers-table :$ensembles :$term_dates />
				</x-card>
			</div>
		</x-card-row>
		<x-per-page-select :paginator="$term_dates" />
		{{ $term_dates->links() }}
	</div>
</x-layout>

@props(['ensembles', 'page_name'])

<x-layout :$page_name page_subname="Ensemble overview">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :entities=$ensembles entities_name="ensembles" />
				</x-card>
			</div>
		</x-card-row>
		{{ $ensembles->links() }}
	</div>
</x-layout>

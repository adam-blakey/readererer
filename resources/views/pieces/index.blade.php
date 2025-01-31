@props(['pieces', 'page_name'])

<x-layout :$page_name page_subname="Pieces overview">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :entities=$pieces />
				</x-card>
			</div>
		</x-card-row>
		{{ $pieces->links() }}
	</div>
</x-layout>

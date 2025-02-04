@props(['entities', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :$entities />
				</x-card>
			</div>
		</x-card-row>
		{{ $entities->links() }}
	</div>
</x-layout>

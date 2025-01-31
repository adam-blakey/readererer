@props(['composers', 'page_name'])

<x-layout :$page_name page_subname="Composers">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :entities=$composers />
				</x-card>
			</div>
		</x-card-row>
		{{ $composers->links() }}
	</div>
</x-layout>

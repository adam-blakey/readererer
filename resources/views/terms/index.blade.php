@props(['terms', 'page_name'])

<x-layout :$page_name page_subname="Terms overview">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :entities=$terms />
				</x-card>
			</div>
		</x-card-row>
		{{ $terms->links() }}
	</div>
</x-layout>

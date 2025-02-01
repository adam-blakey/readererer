@props(['users', 'page_name'])

<x-layout :$page_name page_subname="Users overview">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-table :entities=$users />
				</x-card>
			</div>
		</x-card-row>
		{{ $users->links() }}
	</div>
</x-layout>

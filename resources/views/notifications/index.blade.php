@props(['logs'])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-notifications.table :$logs />
				</x-card>
			</div>
		</x-card-row>
		{{ $logs->links() }}
	</div>
</x-layout>

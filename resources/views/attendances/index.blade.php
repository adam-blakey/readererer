@props(['attendances'])

<x-layout>
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.table :$attendances />
				</x-card>
			</div>
		</x-card-row>
		{{ $attendances->links() }}
	</div>
</x-layout>

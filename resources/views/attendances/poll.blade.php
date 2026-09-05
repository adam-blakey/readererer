@props(['members', 'term', 'ensemble', 'sortby'])

<x-layout page_subname="Poll">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.poll :$ensemble :$members :$sortby :$term />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

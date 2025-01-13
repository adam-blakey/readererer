@props(['members', 'term', 'page_name', 'ensemble'])

<x-layout :$page_name page_subname="Poll">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.poll :$ensemble :$members :$term />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

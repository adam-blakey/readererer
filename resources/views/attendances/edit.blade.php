@props(['members', 'term', 'page_name', 'ensemble', 'sortby'])

<x-layout :$page_name page_subname="Edit attendance register">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-attendances.form :$ensemble :$members :$sortby :$term />
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

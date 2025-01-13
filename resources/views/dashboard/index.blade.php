@props(['page_name'])

<x-layout :$page_name page_subname="Dashboard">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-card-body>
						This is the dashboard
					</x-card-body>
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

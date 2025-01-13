@props(['page_name', 'page_subname'])

<div class="page-header d-print-none">
	<div class="container-xl">
		<div class="row g-2 align-items-center">
			<div class="col">
				<!-- Page pre-title -->
				<div class="page-pretitle">
					{{ $page_subname }}
				</div>
				<h2 class="page-title">
					{{ $page_name }}
				</h2>
			</div>
		</div>
	</div>
</div>

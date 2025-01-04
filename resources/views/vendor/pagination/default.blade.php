<div class="mt-1 row row-cards">
	<div class="col-12">
		<x-card>
			<x-card-body class="align-right">
				<nav aria-label="{{ __('Pagination Navigation') }}" role="navigation">
					@if ($paginator->hasPages())
						<ul class="pagination">
							{{-- Previous Page Link --}}
							@if ($paginator->onFirstPage())
								<li aria-disabled="true" class="page-item disabled">
									<span aria-disabled="true" class="page-link" tabindex="-1">
										<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
										<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
											<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
											<path d="M15 6l-6 6l6 6"></path>
										</svg>
										prev
									</span>
								</li>
							@else
								<li aria-disabled="true" class="page-item" rel="prev">
									<a class="page-link" href="{{ $paginator->previousPageUrl() }}" tabindex="-1">
										<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
										<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
											<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
											<path d="M15 6l-6 6l6 6"></path>
										</svg>
										prev
									</a>
								</li>
							@endif

							{{-- Pagination Elements --}}
							@foreach ($elements as $element)
								{{-- "Three Dots" Separator --}}
								@if (is_string($element))
									<li aria-disabled="true" class="page-item disabled">
										<span class="page-link">{{ $element }}</span>
									</li>
								@endif

								{{-- Array Of Links --}}
								@if (is_array($element))
									@foreach ($element as $page => $url)
										@if ($page == $paginator->currentPage())
											<li aria-current="page" class="page-item">
												<a class="page-link active">{{ $page }}</a>
											</li>
										@else
											<li aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="page-item">
												<a class="page-link" href="{{ $url }}">{{ $page }}</a>
											</li>
										@endif
									@endforeach
								@endif
							@endforeach

							{{-- Next Page Link --}}
							@if ($paginator->hasMorePages())
								<li aria-label="{{ __('pagination.next') }}" class="page-item" rel="next">
									<a class="page-link" href="{{ $paginator->nextPageUrl() }}">
										next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
										<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
											<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
											<path d="M9 6l6 6l-6 6"></path>
										</svg>
									</a>
								</li>
							@else
								<li aria-disabled="true" class="page-item disabled">
									<span class="page-link">
										next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
										<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
											<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
											<path d="M9 6l6 6l-6 6"></path>
										</svg>
									</span>
								</li>
							@endif
						</ul>
					@else
						<ul class="pagination">
							<li aria-disabled="true" class="page-item disabled">
								<span aria-disabled="true" class="page-link" tabindex="-1">
									<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
									<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
										<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
										<path d="M15 6l-6 6l6 6"></path>
									</svg>
									prev
								</span>
							</li>

							<li aria-current="page" class="page-item">
								<a class="page-link active">1</a>
							</li>

							<li aria-disabled="true" class="page-item disabled">
								<span class="page-link">
									next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
									<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
										<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
										<path d="M9 6l6 6l-6 6"></path>
									</svg>
								</span>
							</li>
						</ul>
					@endif
				</nav>
			</x-card-body>
		</x-card>
	</div>
</div>

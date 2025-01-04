<x-layout :show_page_header="false" page_name="@yield('error_code') error">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<div class="empty">
					<div class="empty-header">@yield('error_code')</div>
					<p class="empty-title">@yield('error_title')</p>
					<p class="empty-subtitle text-secondary">
						@yield('subtitle')
					</p>
					<div class="empty-action">
						<a class="btn btn-primary" href="{{ url('/') }}">
							<!-- Download SVG icon from http://tabler.io/icons/icon/arrow-left -->
							<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
								<path d="M0 0h24v24H0z" fill="none" stroke="none"></path>
								<path d="M5 12l14 0"></path>
								<path d="M5 12l6 6"></path>
								<path d="M5 12l6 -6"></path>
							</svg>
							Take me home
						</a>
					</div>
				</div>
			</div>
		</x-card-row>
	</div>
</x-layout>

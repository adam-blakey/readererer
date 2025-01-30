<x-layout :show_page_header="false" page_name="{{ app()->view->getSections()['error_code'] }} error">
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
						<a class="btn btn-primary" href="@yield('button-route')">
							<x-icon name="{{ app()->view->getSections()['button-icon'] }}" />
							@yield('button-text', __('Login'))
						</a>
					</div>
				</div>
			</div>
		</x-card-row>
	</div>
</x-layout>

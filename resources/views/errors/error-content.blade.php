<x-layout :show_page_header="false" :page_name="__(':code error', ['code' => app()->view->getSections()['error_code']])">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<div class="empty">
					@hasSection('illustration')
						<div class="empty-img">
							<img src="{{ Vite::asset('resources/images/tabler/' . app()->view->getSections()['illustration'] . '-light.png') }}" class="hide-theme-dark" width="341" alt="">
							<img src="{{ Vite::asset('resources/images/tabler/' . app()->view->getSections()['illustration'] . '-dark.png') }}" class="hide-theme-light" width="341" alt="">
						</div>
					@else
						<div class="empty-header">@yield('error_code')</div>
					@endif
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

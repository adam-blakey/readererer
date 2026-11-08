@php
	$nav_items = [
	    [
	        'title' => __('Upcoming rehearsals'),
	        'icon' => 'list-check',
	        'route' => 'attendance.register.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\RegisterEntry::class),
	    ],
	    [
	        'title' => __('Users'),
	        'icon' => 'user',
	        'route' => 'users.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\User::class),
	    ],
	    [
	        'title' => __('Settings'),
	        'icon' => 'settings',
	        'route' => 'settings.edit',
	        'auth' => Auth::user()?->can('view.settings'),
	    ],
	    [
	        'title' => __('Feature flags'),
	        'icon' => 'flag',
	        'route' => 'feature-flags.edit',
	        'auth' => Auth::user()?->can('edit.settings'),
	    ],
	];

	$number_of_accessible_nav_items = 0;
	if (!Auth::guest()) {
	    foreach ($nav_items as $nav_item) {
	        if ($nav_item['auth']) {
	            $number_of_accessible_nav_items++;
	        }
	    }
	}
@endphp

<header class="navbar navbar-expand-md navbar-light d-print-none">
	<div class="container-xl">
		@if ($number_of_accessible_nav_items > 0)
			<button aria-controls="navbar-menu" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}" class="navbar-toggler" data-bs-target="#navbar-menu" data-bs-toggle="collapse" type="button">
				<span class="navbar-toggler-icon"></span>
			</button>
		@endif
			<h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
				<x-a :route="Auth::check() ? 'dashboard' : 'home'">
					<img class="navbar-brand-image" src="{{ logo_url() }}">
				</x-a>
			</h1>
		<div class="flex-row navbar-nav order-md-last">
			<div class="nav-item dropdown">
				@guest
					<a class="px-0 nav-link" data-bs-placement="bottom" data-bs-toggle="tooltip" href="{{ route('login') }}" title="{{ __('Login') }}">
						<x-icon name="login" />
					</a>
				@endguest
				@auth
					<a aria-label="{{ __('Open user menu') }}" class="p-0 nav-link d-flex lh-1 text-reset" data-bs-toggle="dropdown" href="#">
						<x-avatar :user="Auth::user()" size="sm" :tooltip="false" />
						<x-name-and-role :user="Auth::user()" />
					</a>
					<div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        @can('view', Auth::user())
                            <x-a class="dropdown-item" :route="'users.show'" :user="Auth::user()">{{ __('My profile') }}</x-a>
                        @endcan
                        <x-a class="dropdown-item" :route="'password.edit'">{{ __('Change password') }}</x-a>
						<form action="{{ route('logout') }}" method="POST">
							@csrf
							<button class="dropdown-item" type="submit">{{ __('Logout') }}</button>
						</form>
					</div>
				@endauth
			</div>
		</div>
	</div>
</header>
@auth
	<div class="navbar-expand-md">
		<div class="collapse navbar-collapse" id="navbar-menu">
			<div class="navbar navbar-light">
				<div class="container-xl">
					<ul class="navbar-nav">
						@foreach ($nav_items as $nav_item)
							@if (!$nav_item['auth'])
								@continue
							@endif

							<li class="nav-item {{ Request::routeIs($nav_item['route']) ? 'active' : '' }}">
								<a class="nav-link" href="{{ route($nav_item['route']) }}">
									<span class="nav-link-icon d-md-none d-lg-inline-block">
										<x-icon name="{{ $nav_item['icon'] }}" />
									</span>
									<span class="nav-link-title">
										{{ $nav_item['title'] }}
									</span>
								</a>
							</li>
						@endforeach
					</ul>
				</div>
			</div>
		</div>
	</div>
@endauth

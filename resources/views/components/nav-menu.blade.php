@php
	$nav_items = [
	    [
	        'title' => 'Dashboard',
	        'icon' => 'dashboard',
	        'route' => 'dashboard',
	        'auth' => Gate::inspect('view.dashboard')->allowed(),
	    ],
	    [
	        'title' => 'Attendance updates',
	        'icon' => 'square-check',
	        'route' => 'attendance.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Attendance::class),
	    ],
	    [
	        'title' => 'Composers',
	        'icon' => 'old',
	        'route' => 'composers.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Composer::class),
	    ],
	    [
	        'title' => 'Ensembles',
	        'icon' => 'music',
	        'route' => 'ensembles.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Ensemble::class),
	    ],
	    [
	        'title' => 'Pieces',
	        'icon' => 'file-description',
	        'route' => 'pieces.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Piece::class),
	    ],
	    [
	        'title' => 'Setlists',
	        'icon' => 'list-numbers',
	        'route' => 'setlists.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Setlist::class),
	    ],
	    [
	        'title' => 'Terms',
	        'icon' => 'calendar-month',
	        'route' => 'terms.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\Term::class),
	    ],
	    [
	        'title' => 'Setup groups',
	        'icon' => 'users',
	        'route' => 'setup-groups.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\SetupGroup::class),
	    ],
	    [
	        'title' => 'Users',
	        'icon' => 'user',
	        'route' => 'users.index',
	        'auth' => Auth::user()?->can('viewAny', App\Models\User::class),
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
			<button aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler" data-bs-target="#navbar-menu" data-bs-toggle="collapse" type="button">
				<span class="navbar-toggler-icon"></span>
			</button>
		@endif
			<h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
				<x-a :route="'home'">
					<img class="navbar-brand-image" src="{{ Vite::asset('resources/images/readererer-long-logo.svg') }}">
				</x-a>
			</h1>
		<div class="flex-row navbar-nav order-md-last">
			<div class="nav-item dropdown">
				@guest
					<a class="px-0 nav-link" data-bs-placement="bottom" data-bs-toggle="tooltip" href="{{ route('login') }}" title="Login">
						<x-icon name="login" />
					</a>
				@endguest
				@auth
					<a aria-label="Open user menu" class="p-0 nav-link d-flex lh-1 text-reset" data-bs-toggle="dropdown" href="#">
						<x-avatar :user="Auth::user()" size="sm" />
						<x-name-and-role :user="Auth::user()" />
					</a>
					<div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <x-a class="dropdown-item" :route="'settings.edit'">Settings</x-a>
						<form action="{{ route('logout') }}" method="POST">
							@csrf
							<a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Logout</a>
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
					<div class="order-first my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-md-last">
						<form action="./" autocomplete="off" method="get" novalidate>
							<div class="input-icon">
								<span class="input-icon-addon">
									<!-- Download SVG icon from http://tabler-icons.io/i/search -->
									<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
										<path d="M0 0h24v24H0z" fill="none" stroke="none" />
										<circle cx="10" cy="10" r="7" />
										<line x1="21" x2="15" y1="21" y2="15" />
									</svg>
								</span>
								<input aria-label="Search in website" class="form-control" placeholder="Search…" type="text" value="">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endauth

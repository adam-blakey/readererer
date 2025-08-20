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
			<div class="d-none d-md-flex">
				<a class="px-0 nav-link hide-theme-dark" data-bs-placement="bottom" data-bs-toggle="tooltip" href="?theme=dark" title="Enable dark mode">
					<!-- Download SVG icon from http://tabler-icons.io/i/moon -->
					<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
						<path d="M0 0h24v24H0z" fill="none" stroke="none" />
						<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
					</svg>
				</a>
				<a class="px-0 nav-link hide-theme-light" data-bs-placement="bottom" data-bs-toggle="tooltip" href="?theme=light" title="Enable light mode">
					<!-- Download SVG icon from http://tabler-icons.io/i/sun -->
					<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
						<path d="M0 0h24v24H0z" fill="none" stroke="none" />
						<circle cx="12" cy="12" r="4" />
						<path
							d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
					</svg>
				</a>
				@auth
					<div class="nav-item dropdown d-none d-md-flex me-3">
						<a aria-label="Show notifications" class="px-0 nav-link" data-bs-toggle="dropdown" href="#" tabindex="-1">
							<!-- Download SVG icon from http://tabler-icons.io/i/bell -->
							<svg class="icon" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
								<path d="M0 0h24v24H0z" fill="none" stroke="none" />
								<path
									d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
								<path d="M9 17v1a3 3 0 0 0 6 0v-1" />
							</svg>
							<span class="badge bg-red"></span>
						</a>
						<div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
							<div class="card">
								<div class="card-header">
									<h3 class="card-title">Last updates</h3>
								</div>
								<div class="list-group list-group-flush list-group-hoverable">
									<div class="list-group-item">
										<div class="row align-items-center">
											<div class="col-auto"><span
													class="status-dot status-dot-animated bg-red d-block"></span>
											</div>
											<div class="col text-truncate">
												<a class="text-body d-block" href="#">Example 1</a>
												<div class="d-block text-muted text-truncate mt-n1">
													Change deprecated html tags to text decoration classes (#29604)
												</div>
											</div>
											<div class="col-auto">
												<a class="list-group-item-actions" href="#">
													<!-- Download SVG icon from http://tabler-icons.io/i/star -->
													<svg class="icon text-muted" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
														<path d="M0 0h24v24H0z" fill="none" stroke="none" />
														<path
															d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
													</svg>
												</a>
											</div>
										</div>
									</div>
									<div class="list-group-item">
										<div class="row align-items-center">
											<div class="col-auto"><span class="status-dot d-block"></span></div>
											<div class="col text-truncate">
												<a class="text-body d-block" href="#">Example 2</a>
												<div class="d-block text-muted text-truncate mt-n1">
													justify-content:between ⇒ justify-content:space-between (#29734)
												</div>
											</div>
											<div class="col-auto">
												<a class="list-group-item-actions show" href="#">
													<!-- Download SVG icon from http://tabler-icons.io/i/star -->
													<svg class="icon text-yellow" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
														<path d="M0 0h24v24H0z" fill="none" stroke="none" />
														<path
															d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
													</svg>
												</a>
											</div>
										</div>
									</div>
									<div class="list-group-item">
										<div class="row align-items-center">
											<div class="col-auto"><span class="status-dot d-block"></span></div>
											<div class="col text-truncate">
												<a class="text-body d-block" href="#">Example 3</a>
												<div class="d-block text-muted text-truncate mt-n1">
													Update change-version.js (#29736)
												</div>
											</div>
											<div class="col-auto">
												<a class="list-group-item-actions" href="#">
													<!-- Download SVG icon from http://tabler-icons.io/i/star -->
													<svg class="icon text-muted" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
														<path d="M0 0h24v24H0z" fill="none" stroke="none" />
														<path
															d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
													</svg>
												</a>
											</div>
										</div>
									</div>
									<div class="list-group-item">
										<div class="row align-items-center">
											<div class="col-auto"><span
													class="status-dot status-dot-animated bg-green d-block"></span>
											</div>
											<div class="col text-truncate">
												<a class="text-body d-block" href="#">Example 4</a>
												<div class="d-block text-muted text-truncate mt-n1">
													Regenerate package-lock.json (#29730)
												</div>
											</div>
											<div class="col-auto">
												<a class="list-group-item-actions" href="#">
													<!-- Download SVG icon from http://tabler-icons.io/i/star -->
													<svg class="icon text-muted" fill="none" height="24" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
														<path d="M0 0h24v24H0z" fill="none" stroke="none" />
														<path
															d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
													</svg>
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				@endauth
			</div>
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
						<a class="dropdown-item" href="#">Status</a>
						<a class="dropdown-item" href="#">Profile</a>
						<a class="dropdown-item" href="#">Feedback</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="./settings.html">Settings</a>
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

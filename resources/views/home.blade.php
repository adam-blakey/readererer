@props(['terms'])

<x-layout :show_page_header="false">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-card-header>
						{{ __('Welcome to Readererer!') }}
					</x-card-header>
					<x-card-body>
						<p>
							@auth
								{{ __('Hello, :name!', ['name' => Auth::user()->name]) }}
							@endauth
							{{ __('Readererer is an app designed to help amateur music groups manage their pieces of music and rehearsal attendance efficiently.') }}</p>
						<h2>{{ __('Rough feature list') }}</h2>
						<ul>
							<li><strong>{{ __('Manage pieces of music:') }}</strong> {{ __('Easily add, edit, and organize your music pieces.') }}</li>
							<li><strong>{{ __('Track rehearsal attendance:') }}</strong> {{ __('Keep track of who is attending rehearsals and who is not; receive reminders and attendance summaries via email.') }}</li>
							<li><strong>{{ __('Organize ensembles:') }}</strong> {{ __('Manage different ensembles and their members along with their contact details.') }}</li>
							<li><strong>{{ __('Term management:') }}</strong> {{ __('Organize your rehearsals and performances by term.') }}</li>
						</ul>
						@guest
							<a class="btn btn-primary" href="{{ route('login') }}"><x-icon name="login" /> {{ __('Log in') }}</a>
						@endguest
						@auth
							<form action="{{ route('logout') }}" method="POST">
								@csrf
								<button class="btn btn-primary" type="submit"><x-icon name="logout" /> {{ __('Log out') }}</button>
							</form>
						@endauth
					</x-card-body>
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

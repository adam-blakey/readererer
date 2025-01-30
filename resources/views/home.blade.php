@props(['terms', 'page_name'])

<x-layout :$page_name :show_page_header="false">
	<div class="container-xl">
		<x-card-row>
			<div class="col-md-12">
				<x-card>
					<x-card-header>
						Welcome to Readererer!
					</x-card-header>
					<x-card-body>
						<p>
							@auth
								Hello, {{ Auth::user()->name }}!
							@endauth
							Readererer is an app designed to help amateur music groups manage their pieces of music and rehearsal attendance efficiently.</p>
						<h2>Rough feature list</h2>
						<ul>
							<li><strong>Manage pieces of music:</strong> Easily add, edit, and organize your music pieces.</li>
							<li><strong>Track rehearsal attendance:</strong> Keep track of who is attending rehearsals and who is not; receive reminders and attendance summaries via email.</li>
							<li><strong>Organize ensembles:</strong> Manage different ensembles and their members along with their contact details.</li>
							<li><strong>Term management:</strong> Organize your rehearsals and performances by term.</li>
						</ul>
						@guest
							<a class="btn btn-primary" href="{{ route('login') }}"><x-icon name="login" /> Log in</a>
						@endguest
						@auth
							<form action="{{ route('logout') }}" method="POST">
								@csrf
								<button class="btn btn-primary" type="submit"><x-icon name="logout" /> Log out</button>
							</form>
						@endauth
					</x-card-body>
				</x-card>
			</div>
		</x-card-row>
	</div>
</x-layout>

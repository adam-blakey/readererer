@props(['messages'])

@if ($messages)
	<ul class="list-unstyled">
		@foreach ((array) $messages as $message)
			<li class="my-1 ms-2 w-90 alert alert-danger">{{ $message }}</li>
		@endforeach
	</ul>
@endif

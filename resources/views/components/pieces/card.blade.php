@props(['piece'])

<div class="flex gap-x-6">
	<div>
		<x-pieces.thumbnail />
	</div>

	<div class="flex flex-col flex-1">
		<a class="self-start text-gray-400" href="#">
			{{ $piece->composer->first_name . ' ' . $piece->composer->last_name }}
		</a>

		<a class="mt-3 text-xl font-bold transition-colors duration-300 group-hover:text-blue-700" href="#" target="_blank">
			{{ $piece->name }}
		</a>
		<p class="mt-auto text-sm text-gray-400">Wow</p>
	</div>

	<div>
		Tags
	</div>
</div>

@props(['title', 'value', 'icon'])

<div {{ $attributes->merge(['class' => 'card']) }}>
	<div class="card-body">
		<div class="row align-items-center">
			<div class="col">
				<h2 class="subheader mb-1">{{ $title }}</h2>
				<div class="h3 m-0">{{ $value }}</div>
			</div>
			<div class="col-auto">
				<span class="avatar avatar-lg"><x-icon :name="$icon" /></span>
			</div>
		</div>
	</div>
</div>

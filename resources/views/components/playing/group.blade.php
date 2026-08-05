@props(['title', 'members', 'ensemble' => null, 'badge_class' => 'bg-green text-green-fg', 'icon' => 'check', 'empty' => 'Nobody.'])

@php
	$by_instrument_family = $members
	    ->groupBy(fn ($member) => member_instrument_family_name($member, $ensemble))
	    ->sortKeys();
	$current_user_id = auth()->id();
@endphp

<x-card>
	<div class="card-header">
		<h3 class="mb-0 card-title">
			<x-icon name="{{ $icon }}" />
			{{ $title }}
			<span class="badge {{ $badge_class }} ms-1">{{ $members->count() }}</span>
		</h3>
	</div>
	<x-card-body>
		@if ($members->isEmpty())
			<p class="mb-0 text-muted">{{ $empty }}</p>
		@else
			@foreach ($by_instrument_family as $instrument_family_name => $family_members)
				<div class="mb-3">
					<div class="card-title">{{ $instrument_family_name }} ({{ $family_members->count() }})</div>
					@foreach ($family_members as $member)
						<x-user-entry :user="$member" :add_route="false" :secondary_info="$member->id === $current_user_id ? 'You' : ' '" show_setup_group="true" />
					@endforeach
				</div>
			@endforeach
		@endif
	</x-card-body>
</x-card>

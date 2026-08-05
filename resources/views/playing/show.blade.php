@props(['term_date', 'ensemble', 'groups', 'totals', 'page_name', 'page_subname'])

<x-layout :$page_name :$page_subname>
	<div class="container-xl">
		<x-card-row>
			<div class="col-12">
				<x-card>
					<x-card-body>
						<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
							<div>
								<h2 class="mb-1 card-heading">{{ $term_date->name }}</h2>
								<div>
									@if ($term_date->isConcert())
										<span class="badge bg-green text-green-fg">Concert @if ($ensemble) ({{ $ensemble->name }}) @endif</span>
									@else
										<span class="badge bg-gray text-muted">Rehearsal</span>
									@endif
									<span class="ms-2 small text-muted">{{ $term_date->start_datetime->diffForHumans() }}</span>
								</div>
							</div>
							<x-playing.summary :$totals />
						</div>
					</x-card-body>
					<x-card-body class="border-top">
						<div class="btn-list">
							<span>
								<x-icon name="users" />
								Setup group:
								@if ($term_date->setup_group != null)
									<x-setup-group-badge :setup_group="$term_date->setup_group" />
									{{ $term_date->setup_group->name }}
								@else
									<span class="text-muted">—</span>
								@endif
							</span>
							<span>
								<x-icon name="car" />
								Van driver: {{ optional($term_date->inferred_van_driver)->name ?? '—' }}
							</span>
							<x-a class="ms-auto" route="playing.index">Back to all upcoming dates</x-a>
						</div>
					</x-card-body>
				</x-card>
			</div>

			<div class="col-md-6">
				<x-playing.group title="Playing" icon="check" badge_class="bg-green text-green-fg" :$ensemble
					:members="$groups['attending']"
					empty="Nobody has said they are playing yet." />
			</div>
			<div class="col-md-6">
				<x-playing.group title="Not playing" icon="x" badge_class="bg-red text-red-fg" :$ensemble
					:members="$groups['not_attending']"
					empty="Everybody is available." />

				@isset($groups['unknown'])
					<div class="mt-3">
						<x-playing.group title="Not answered yet" icon="question-mark" badge_class="bg-gray text-muted" :$ensemble
							:members="$groups['unknown']"
							empty="Everybody has answered." />
					</div>
				@endisset
			</div>
		</x-card-row>
	</div>
</x-layout>

@props(['users'])

<x-layout>
	<div class="page-body">
		<div class="container-xl">
			<x-forms.error-summary :fields="[]" />
			@if (session('status'))
				<div class="alert alert-success" role="alert">{{ session('status') }}</div>
			@endif
			<div class="card">
				<div class="card-header">
					<div>
						<h2 class="mb-0 card-title">{{ __('Find yourself') }}</h2>
						<div class="text-secondary">{{ __('Claim your account and we will email you a login link. Members who have already set up their account can log in with the username shown.') }}</div>
					</div>
				</div>
				<div class="table-responsive">
					<table class="table table-vcenter card-table">
						<thead>
							<tr>
								<th><x-larasort-link :display_name="__('First name')" name="first_name" /></th>
								<th><x-larasort-link :display_name="__('Last name')" name="last_name" /></th>
								<th class="w-1"><x-larasort-link :display_name="__('Account')" name="last_login_at" /></th>
							</tr>
						</thead>
						<tbody>
							@forelse ($users as $user)
								<tr>
									<td>{{ $user->first_name }}</td>
									<td>{{ $user->last_name }}</td>
									<td class="text-nowrap">
										@if ($user->last_login_at)
											<button type="button" class="btn btn-sm" disabled>{{ __('Claimed') }}</button>
											<span class="ms-2 text-secondary">{{ $user->username }}</span>
										@elseif (blank($user->email))
											<button type="button" class="btn btn-sm" disabled>{{ __('Claim') }}</button>
											<span class="ms-2 text-secondary">{{ __('No email address: ask an admin') }}</span>
										@else
											<form method="POST" action="{{ route('claim-account.store', $user) }}" data-confirm="{{ __('Email a login link to :name?', ['name' => $user->name]) }}">
												@csrf
												<button type="submit" class="btn btn-primary btn-sm">{{ __('Claim') }}</button>
											</form>
										@endif
									</td>
								</tr>
							@empty
								<tr><td colspan="3" class="text-muted">{{ __('This ensemble has no members yet.') }}</td></tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</x-layout>

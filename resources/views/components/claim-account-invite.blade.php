@can('claim.accounts')
	@feature(\App\Features\ClaimAccounts::class)
		<div class="alert alert-info" role="alert">
			<div class="d-flex flex-wrap align-items-center gap-2">
				<x-icon name="user-plus" />
				<div class="flex-fill">
					<h4 class="alert-title">{{ __('Get your own account') }}</h4>
					<div class="text-secondary">{{ __('You are using a shared ensemble login. Set up your own account to reply to polls and manage your details yourself.') }}</div>
				</div>
				<a class="btn btn-primary" href="{{ route('claim-account.index') }}">{{ __('Set up my account') }}</a>
			</div>
		</div>
	@endfeature
@endcan

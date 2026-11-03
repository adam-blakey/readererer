@props(['user'])

<div class="modal modal-blur fade" id="modal-reset-password" tabindex="-1" role="dialog" aria-labelledby="modal-reset-password-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modal-reset-password-title">{{ __('Reset password') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <x-forms.error-summary :fields="['password', 'password_confirmation']" />
                <h5>{{ __('Send a password reset email') }}</h5>
                @if (filled($user->email))
                    <form method="POST" action="{{ route('users.password-reset-link', $user) }}">
                        @csrf
                        <p class="text-secondary">{{ __('Emails :email a link to choose a new password.', ['email' => $user->email]) }}</p>
                        <button class="btn" type="submit">
                            <x-icon name="mail" />
                            {{ __('Send reset email') }}
                        </button>
                    </form>
                @else
                    <p class="text-secondary">{{ __('This user has no email address.') }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('users.password', $user) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <h5>{{ __('Set a new password') }}</h5>
                    <div class="mb-3">
                        <label class="form-label" for="password">{{ __('New password') }}</label>
                        <input autocomplete="new-password" class="form-control" id="password" name="password" required type="password">
                        <x-forms.input-error :messages="$errors->get('password')" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
                        <input autocomplete="new-password" class="form-control" id="password_confirmation" name="password_confirmation" required type="password">
                        <x-forms.input-error :messages="$errors->get('password_confirmation')" />
                    </div>
                    <label class="form-check">
                        <input checked class="form-check-input" name="must_change_password" type="checkbox" value="1">
                        <span class="form-check-label">{{ __('Make them choose a new password when they next log in') }}</span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-primary" type="submit">
                        <x-icon name="key" />
                        {{ __('Set password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->hasAny(['reset_link', 'password', 'password_confirmation']))
    @push('scripts')
        <script>
            window.addEventListener('load', () => document.querySelector('[data-bs-target="#modal-reset-password"]')?.click());
        </script>
    @endpush
@endif

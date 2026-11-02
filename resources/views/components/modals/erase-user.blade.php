@props(['user'])

<div class="modal modal-blur fade" id="modal-erase-user" tabindex="-1" role="dialog" aria-labelledby="modal-erase-user-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('users.erase', $user) }}">
                @csrf
                @method('DELETE')
                <div class="modal-status bg-danger"></div>
                <div class="modal-header">
                    <h4 class="modal-title text-danger" id="modal-erase-user-title">{{ __('Erase personal data') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <x-forms.error-summary :fields="['confirm_name']" />
                    <p>{{ __('This permanently removes everything that identifies this person. Their attendance and register records are kept anonymously. This cannot be undone.') }}</p>
                    <label class="form-label" for="confirm_name">{{ __('Type :name to confirm', ['name' => $user->name]) }}</label>
                    <input autocomplete="off" class="form-control" id="confirm_name" name="confirm_name" required type="text">
                    <x-forms.input-error :messages="$errors->get('confirm_name')" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-danger" type="submit">
                        <x-icon name="trash" />
                        {{ __('Erase personal data') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('confirm_name'))
    @push('scripts')
        <script>
            window.addEventListener('load', () => document.querySelector('[data-bs-target="#modal-erase-user"]')?.click());
        </script>
    @endpush
@endif

{{-- Creates a location without leaving the page; a successful save dispatches a "location-added" event with its id and name. --}}
<button class="d-none" data-bs-target="#modal-add-location" data-bs-toggle="modal" id="open-add-location" type="button"></button>

<div class="modal modal-blur fade" id="modal-add-location" tabindex="-1" role="dialog" aria-labelledby="modal-add-location-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('locations.store') }}" class="modal-content" id="add-location-form" method="POST">
            <div class="modal-header">
                <h4 class="modal-title" id="modal-add-location-title">{{ __('Add new location') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required" for="new-location-name">{{ __('Name') }}</label>
                    <input class="form-control" id="new-location-name" name="name" required type="text">
                    <div class="invalid-feedback" data-error-for="name"></div>
                </div>
                <div>
                    <label class="form-label" for="new-location-address">{{ __('Address') }}</label>
                    <input class="form-control" id="new-location-address" name="address" type="text">
                    <div class="invalid-feedback" data-error-for="address"></div>
                    <small class="form-hint">{{ __('The map pin is looked up from the address on OpenStreetMap.') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" data-bs-dismiss="modal" type="button">{{ __('Cancel') }}</button>
                <button class="btn btn-primary ms-auto" type="submit">{{ __('Add location') }}</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ Vite::cspNonce() }}">
    document.getElementById('add-location-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.target;
        const submit = form.querySelector('[type="submit"]');
        form.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
        submit.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(form),
            });
            const body = await response.json();

            if (!response.ok) {
                Object.entries(body.errors ?? { name: [body.message] }).forEach(([field, messages]) => {
                    form.elements[field]?.classList.add('is-invalid');
                    const feedback = form.querySelector(`[data-error-for="${field}"]`);
                    if (feedback) feedback.textContent = messages[0];
                });
                return;
            }

            document.dispatchEvent(new CustomEvent('location-added', { detail: body }));
            form.reset();
            form.querySelector('[data-bs-dismiss="modal"]').click();
        } catch {
            form.elements.name.classList.add('is-invalid');
            form.querySelector('[data-error-for="name"]').textContent = @json(__('The location could not be saved. Try again.'));
        } finally {
            submit.disabled = false;
        }
    });
</script>

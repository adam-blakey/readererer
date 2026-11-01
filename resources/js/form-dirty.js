// Disables the submit buttons of forms marked data-dirty-check until their values
// change; a button can opt out with data-dirty-ignore.

const FORM_SELECTOR = 'form[data-dirty-check]';

const IGNORED_FIELDS = ['_token', '_method'];

// Files can't be serialised, so identify them by what a change would alter.
function fieldValue(value) {
    return value instanceof File ? `${value.name}:${value.size}:${value.lastModified}` : value;
}

function signature(form) {
    const entries = [];

    for (const [name, value] of new FormData(form)) {
        if (!IGNORED_FIELDS.includes(name)) {
            entries.push([name, fieldValue(value)]);
        }
    }

    return JSON.stringify(entries);
}

function isSubmitButton(element) {
    if (element.hasAttribute('data-dirty-ignore')) {
        return false;
    }

    return element.tagName === 'BUTTON'
        ? (element.getAttribute('type') ?? 'submit') === 'submit'
        : element.type === 'submit' || element.type === 'image';
}

function submitButtons(form) {
    const candidates = Array.from(form.querySelectorAll('button, input'));

    // Not form.id: a field named "id" shadows the form's own property.
    const id = form.getAttribute('id');

    if (id) {
        candidates.push(...document.querySelectorAll(`[form="${CSS.escape(id)}"]`));
    }

    return candidates.filter(isSubmitButton);
}

function setDisabled(button, disabled) {
    if (disabled && !button.hasAttribute('data-dirty-title')) {
        button.setAttribute('data-dirty-title', button.getAttribute('title') ?? '');
    }

    button.disabled = disabled;
    button.classList.toggle('disabled', disabled);

    if (disabled) {
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('title', 'No changes to save');
    } else {
        button.removeAttribute('aria-disabled');

        const original = button.getAttribute('data-dirty-title');

        if (original) {
            button.setAttribute('title', original);
        } else {
            button.removeAttribute('title');
        }
    }
}

export function watchForm(form) {
    if (form.hasAttribute('data-dirty-watching')) {
        return;
    }

    form.setAttribute('data-dirty-watching', '');

    const baseline = signature(form);

    // A form re-rendered after failed validation holds unsaved input, so it starts dirty.
    const startsDirty = form.querySelector('.is-invalid, .invalid-feedback') !== null;

    function refresh() {
        const dirty = startsDirty || signature(form) !== baseline;

        submitButtons(form).forEach((button) => setDisabled(button, !dirty));
    }

    // On the document, to catch fields outside the form that name it with form="...".
    function handle(event) {
        if (event.target.form === form || form.contains(event.target)) {
            refresh();
        }
    }

    document.addEventListener('input', handle);
    document.addEventListener('change', handle);

    // Rows added or removed after load change what the form submits without an input event.
    new MutationObserver(refresh).observe(form, { childList: true, subtree: true });

    refresh();
}

export function watchDirtyForms(root = document) {
    root.querySelectorAll(FORM_SELECTOR).forEach(watchForm);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => watchDirtyForms());
} else {
    watchDirtyForms();
}

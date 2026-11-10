// Disables the submit buttons of forms marked data-dirty-check until their values
// change; a button can opt out with data-dirty-ignore. When the page's <html> has
// data-warn-unsaved, leaving it with any changed POST form asks for confirmation.

const FORM_SELECTOR = 'form[data-dirty-check]';

// These submit themselves as soon as they change, so they never hold unsaved input.
const AUTO_SUBMIT_SELECTOR = '[data-submit-on-change], [data-submit-on-close]';

const IGNORED_FIELDS = ['_token', '_method'];

// Files can't be serialised, so identify them by what a change would alter.
function fieldValue(value) {
    if (!(value instanceof File)) {
        return value;
    }

    // An empty file input submits a nameless File whose lastModified is the time it was read.
    return value.name === '' ? '' : `${value.name}:${value.size}:${value.lastModified}`;
}

function entries(form) {
    const entries = [];

    for (const [name, value] of new FormData(form)) {
        if (!IGNORED_FIELDS.includes(name)) {
            entries.push([name, fieldValue(value)]);
        }
    }

    return entries;
}

function signature(form) {
    return JSON.stringify(entries(form));
}

function changedFields(form, baseline) {
    const before = baseline.map(([name, value]) => `${name}=${value}`);
    const after = entries(form).map(([name, value]) => `${name}=${value}`);

    return [
        ...before.filter((entry) => !after.includes(entry)).map((entry) => `- ${entry}`),
        ...after.filter((entry) => !before.includes(entry)).map((entry) => `+ ${entry}`),
    ];
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

const dirtyChecks = new Map();

function track(form) {
    if (!dirtyChecks.has(form)) {
        const baseline = entries(form);
        const baselineSignature = JSON.stringify(baseline);

        // A form re-rendered after failed validation holds unsaved input, so it starts dirty.
        // Empty .invalid-feedback elements are placeholders (e.g. the add-location modal's), not errors.
        const startsDirty = form.querySelector('.is-invalid, .invalid-feedback:not(:empty)') !== null;

        const isDirty = () => startsDirty || signature(form) !== baselineSignature;
        isDirty.describe = () => (startsDirty ? ['re-rendered with validation errors'] : changedFields(form, baseline));

        dirtyChecks.set(form, isDirty);
    }

    return dirtyChecks.get(form);
}

export function watchForm(form) {
    if (form.hasAttribute('data-dirty-watching')) {
        return;
    }

    form.setAttribute('data-dirty-watching', '');

    const isDirty = track(form);

    function refresh() {
        const dirty = isDirty();

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

    root.querySelectorAll('form').forEach((form) => {
        if (form.method === 'post' && !form.querySelector(AUTO_SUBMIT_SELECTOR)) {
            track(form);
        }
    });
}

let submittedForm = null;

// On the window, so it runs after document listeners that may cancel the submission.
window.addEventListener('submit', (event) => {
    submittedForm = event.defaultPrevented ? null : event.target;
});

window.addEventListener('pageshow', () => {
    submittedForm = null;
});

window.addEventListener('beforeunload', (event) => {
    if (!document.documentElement.hasAttribute('data-warn-unsaved')) {
        return;
    }

    for (const [form, isDirty] of dirtyChecks) {
        if (form !== submittedForm && form.isConnected && isDirty()) {
            console.warn('Unsaved changes prompt for form', form, isDirty.describe());
            event.preventDefault();
            event.returnValue = '';

            return;
        }
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => watchDirtyForms());
} else {
    watchDirtyForms();
}

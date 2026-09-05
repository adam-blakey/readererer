// Greys out a form's save buttons until something on the form has actually
// changed.
//
// Opt a form in with data-dirty-check: its submit buttons start disabled and
// are enabled as soon as the form's values differ from the ones the page was
// rendered with. Buttons placed outside the form and wired up with the
// form="..." attribute are picked up too; an individual button can opt out
// with data-dirty-ignore.

const FORM_SELECTOR = 'form[data-dirty-check]';

// Laravel's own bookkeeping fields never count as a change.
const IGNORED_FIELDS = ['_token', '_method'];

// Files can't be serialised, so identify them by what a change would alter.
function fieldValue(value) {
    return value instanceof File ? `${value.name}:${value.size}:${value.lastModified}` : value;
}

// A snapshot of everything the form would submit right now.
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

// Buttons inside the form, plus any outside it that name it with form="...".
function submitButtons(form) {
    const candidates = Array.from(form.querySelectorAll('button, input'));

    // Read the id from the attribute: a form's own named controls shadow its
    // properties, so a field called "id" (as the term and ensemble forms have)
    // would otherwise hand back that input instead of the form's id.
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

    // A form re-rendered after a failed validation already holds the user's
    // unsaved input, so it starts dirty — otherwise their changes would be
    // stuck behind a greyed-out button.
    const startsDirty = form.querySelector('.is-invalid, .invalid-feedback') !== null;

    function refresh() {
        const dirty = startsDirty || signature(form) !== baseline;

        submitButtons(form).forEach((button) => setDisabled(button, !dirty));
    }

    // Listened for on the document so that fields associated with the form
    // from outside it (form="...") are covered as well as the ones inside.
    function handle(event) {
        if (event.target.form === form || form.contains(event.target)) {
            refresh();
        }
    }

    document.addEventListener('input', handle);
    document.addEventListener('change', handle);

    // Rows and fields can be added or removed after load (term dates, the
    // attendance poll's hidden status inputs), which changes what the form
    // would submit without firing an input event.
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

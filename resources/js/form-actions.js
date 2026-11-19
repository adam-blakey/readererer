document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;

    if (message !== undefined && !confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('submit', (event) => {
    const button = event.submitter;

    if (event.defaultPrevented || !button || button.dataset.loadingText === undefined) {
        return;
    }

    const original = button.innerHTML;
    const spinner = document.createElement('span');
    spinner.className = 'spinner-border spinner-border-sm me-2';
    spinner.setAttribute('role', 'status');
    button.replaceChildren(spinner, button.dataset.loadingText);
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    window.addEventListener('pageshow', (pageshow) => {
        if (pageshow.persisted) {
            button.innerHTML = original;
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }, { once: true });
});

document.addEventListener('change', (event) => {
    if (event.target.hasAttribute('data-submit-on-change')) {
        event.target.form.submit();
    }
});

document.addEventListener('change', (event) => {
    document.querySelectorAll(`[data-show-when="${event.target.id}"]`).forEach((element) => {
        element.hidden = !element.dataset.showValues.split(',').includes(event.target.value);
    });
});

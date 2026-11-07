document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;

    if (message !== undefined && !confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    if (event.target.hasAttribute('data-submit-on-change')) {
        event.target.form.submit();
    }
});

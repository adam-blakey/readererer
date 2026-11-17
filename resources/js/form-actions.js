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

document.addEventListener('change', (event) => {
    document.querySelectorAll(`[data-show-when="${event.target.id}"]`).forEach((element) => {
        element.hidden = !element.dataset.showValues.split(',').includes(event.target.value);
    });
});

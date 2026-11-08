function options(root) {
    return Array.from(root.querySelectorAll('.multi-select-option'));
}

function checkedValues(root) {
    return options(root).filter((option) => option.checked).map((option) => option.value).join(',');
}

function refresh(root) {
    const all = options(root);
    const chosen = all.filter((option) => option.checked);
    const selectAll = root.querySelector('.multi-select-all');

    selectAll.checked = chosen.length === all.length;
    selectAll.indeterminate = chosen.length > 0 && chosen.length < all.length;

    root.querySelector('.multi-select-summary').textContent = chosen.length === 0 || chosen.length === all.length
        ? root.dataset.placeholder
        : chosen.map((option) => option.dataset.label).join(', ');
}

document.querySelectorAll('.multi-select').forEach((root) => {
    const toggle = root.querySelector('.multi-select-toggle');
    let valuesWhenOpened = checkedValues(root);

    refresh(root);

    root.querySelector('.multi-select-all').addEventListener('change', (event) => {
        options(root).forEach((option) => { option.checked = event.target.checked; });
        refresh(root);
    });

    options(root).forEach((option) => option.addEventListener('change', () => refresh(root)));

    toggle.addEventListener('show.bs.dropdown', () => { valuesWhenOpened = checkedValues(root); });

    toggle.addEventListener('hidden.bs.dropdown', () => {
        if (root.hasAttribute('data-submit-on-close') && checkedValues(root) !== valuesWhenOpened) {
            root.closest('form')?.requestSubmit();
        }
    });
});

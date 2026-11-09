document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) {
        return;
    }

    const theme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-bs-theme', theme);

    try {
        localStorage.setItem('theme', theme);
    } catch (e) {
        // Storage can be blocked; the choice then lasts for this page only.
    }
});

let installPrompt;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    document.querySelectorAll('[data-install-app]').forEach((button) => (button.hidden = false));
});

window.addEventListener('appinstalled', () => {
    document.querySelectorAll('[data-install-app]').forEach((button) => (button.hidden = true));
});

document.addEventListener('click', async (event) => {
    if (!event.target.closest('[data-install-app]') || !installPrompt) {
        return;
    }

    const prompt = installPrompt;
    installPrompt = null;
    document.querySelectorAll('[data-install-app]').forEach((button) => (button.hidden = true));
    await prompt.prompt();
});

// iOS Safari has no install prompt; navigator.standalone is false there until the site is opened from the home screen.
if (navigator.standalone === false) {
    document.querySelectorAll('[data-install-app-ios]').forEach((hint) => (hint.hidden = false));
}

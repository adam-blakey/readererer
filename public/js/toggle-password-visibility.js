function togglePasswordVisibility(inputId, showId, hideId) {
    const inputField = document.getElementById(inputId);
    if(!inputField) {
        console.error(`Element with ID ${inputId} not found.`);
        return;
    }

    const showButton = document.getElementById(showId);
    if(!showButton) {
        console.error(`Element with ID ${showId} not found.`);
        return;
    }

    const hideButton = document.getElementById(hideId);
    if(!hideButton) {
        console.error(`Element with ID ${hideId} not found.`);
        return;
    }

    if (inputField.type === 'password') {
        inputField.type = 'text';
        showButton.classList.add('hidden');
        hideButton.classList.remove('hidden');
    } else {
        inputField.type = 'password';
        showButton.classList.remove('hidden');
        hideButton.classList.add('hidden');
    }
}

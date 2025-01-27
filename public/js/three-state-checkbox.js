const threeStateCheckboxClasses = ['three-state-checkbox-unknown', 'three-state-checkbox-attending', 'three-state-checkbox-not-attending'];

function switchThreeStateCheckbox(buttonField, inputFieldId) {
    // Some quick validation.
    if (buttonField == null) {
        console.error('buttonField is required.');
        return;
    }

    // Insert a new hidden input field if it doesn't exist.
    var inputField = document.getElementById(inputFieldId);
    if (inputField == null) {
        inputField = document.createElement("input");
        inputField.type = 'hidden';
        inputField.id = inputFieldId;
        inputField.name = inputFieldId;

        // Set the original value of the input field.
        var originalValue = buttonField.getAttribute('data-original-value');
        inputField.value = originalValue;

        buttonField.parentNode.appendChild(inputField);
    }

    // Set the value of the input field.
    var value = Number(inputField.value);
    value = (value + 1) % 3;

    // If we assume attending, then we advance from unknown to attending.
    var assumeAttending = buttonField.getAttribute('data-assume-attending');
    if (assumeAttending && value == 0) {
        value += 1;
    }

    // Set value in input field and display the button appropriately.
    inputField.value = value;
    buttonField.classList.remove(threeStateCheckboxClasses[0], threeStateCheckboxClasses[1], threeStateCheckboxClasses[2]);
    buttonField.classList.add(threeStateCheckboxClasses[value]);
}

function initializeThreeStateCheckbox(buttonField, originalValue) {
    buttonField.classList.add(threeStateCheckboxClasses[originalValue]);
}

function initializeAllThreeStateCheckboxes() {
    var buttons = document.getElementsByClassName('three-state-checkbox')
    for(let i=0; i<buttons.length; i++) {
        var button = buttons[i];
        var originalValue = button.getAttribute('data-original-value');
        initializeThreeStateCheckbox(button, originalValue);
    }
}

window.addEventListener('DOMContentLoaded', (event) => {
    initializeAllThreeStateCheckboxes();
});

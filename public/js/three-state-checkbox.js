const threeStateCheckboxClasses = ['three-state-checkbox-unknown', 'three-state-checkbox-attending', 'three-state-checkbox-not-attending'];

function switchThreeStateCheckbox(buttonField, inputFieldId) {
    if (buttonField == null) {
        console.error('buttonField is required.');
        return;
    }

    var inputField = document.getElementById(inputFieldId);
    if (inputField == null) {
        inputField = document.createElement("input");
        inputField.type = 'hidden';
        inputField.id = inputFieldId;
        inputField.name = inputFieldId;

        var originalValue = buttonField.getAttribute('data-original-value');

        inputField.value = originalValue;

        buttonField.parentNode.appendChild(inputField);
    }

    buttonField.classList.remove(threeStateCheckboxClasses[0], threeStateCheckboxClasses[1], threeStateCheckboxClasses[2]);

    var value = Number(inputField.value.replace('m', ''));
    value = (value + 1) % 3;

    buttonField.classList.add(threeStateCheckboxClasses[value]);
    inputField.value = value + 'm';
}

function initializeThreeStateCheckbox(buttonField, originalValue) {
    buttonField.classList.add(threeStateCheckboxClasses[originalValue]);
    console.log("Test");
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

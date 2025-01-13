function switchThreeStateCheckbox(buttonField, inputFieldId) {
    var inputField = document.getElementById(inputFieldId);

    if (buttonField == null) {
        console.error('buttonField is required.');
        return;
    }

    // N.B.: This is how we keep track of fields that have changed.
    var value = Number(buttonField.value.replace('m', ''));
    value = (value + 1) % 3;
    value += 'm';

    if (inputField == null) {
        inputField = document.createElement("input");
        inputField.type = 'hidden';
        inputField.id = inputFieldId;
        inputField.name = inputFieldId;

        buttonField.parentNode.appendChild(inputField);
    }

    buttonField.value = value;
    buttonField.innerHTML = value;
    inputField.value = value;
}

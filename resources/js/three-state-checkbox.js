function switchThreeStateCheckbox(buttonField, inputFieldId) {
    var inputField = document.getElementById(inputFieldId);

    if (!buttonField) {
        console.error('buttonField is required.');
        return;
    }

    if (!inputField) {
        console.error('inputFieldId with ' + inputFieldId + ' not found.');
        return;
    }

    // N.B.: This is how we keep track of fields that have changed.
    var value = Number(buttonField.value.replace('m', ''));
    value = (value + 1) % 3;
    value += 'm';

    buttonField.value = value;
    buttonField.innerHTML = value;
    inputField.value = value;
}

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
        inputField.value = 0; // TODO

        buttonField.parentNode.appendChild(inputField);
    }

    var classes = ['three-state-checkbox-unknown', 'three-state-checkbox-not-attending', 'three-state-checkbox-attending'];
    buttonField.classList.remove(classes[0], classes[1], classes[2]);

    var value = Number(inputField.value.replace('m', ''));
    value = (value + 1) % 3;

    buttonField.classList.add(classes[value]);
    inputField.value = value + 'm';
}

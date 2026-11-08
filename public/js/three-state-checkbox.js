const threeStateCheckboxClasses = ['three-state-checkbox-unknown', 'three-state-checkbox-attending', 'three-state-checkbox-not-attending', 'three-state-checkbox-depping'];
const DEPPING = 3;

// Insert the hidden input field holding the status if it doesn't exist yet, so only changed statuses are submitted.
function statusInputField(buttonField, inputFieldId) {
    var inputField = document.getElementById(inputFieldId);
    if (inputField == null) {
        inputField = document.createElement("input");
        inputField.type = 'hidden';
        inputField.id = inputFieldId;
        inputField.name = inputFieldId;
        inputField.value = buttonField.getAttribute('data-original-value');

        buttonField.parentNode.appendChild(inputField);
    }

    return inputField;
}

function depNameField(buttonField) {
    return buttonField.parentNode.querySelector('.dep-name');
}

function openDepName(buttonField) {
    var depName = depNameField(buttonField);
    if (depName != null) {
        buttonField.parentNode.classList.add('open');
        depName.focus();
    }
}

function switchThreeStateCheckbox(buttonField, inputFieldId) {
    // Some quick validation.
    if (buttonField == null) {
        console.error('buttonField is required.');
        return;
    }

    // Without hover, the first tap on a dep shows their name rather than moving on.
    var cell = buttonField.parentNode;
    if (cell.classList.contains('depping') && !cell.classList.contains('open') && window.matchMedia('(hover: none)').matches) {
        openDepName(buttonField);
        return;
    }

    var inputField = statusInputField(buttonField, inputFieldId);

    // Set the value of the input field.
    var depping = buttonField.getAttribute('data-depping');
    var value = Number(inputField.value);
    value = (value + 1) % (depping ? 4 : 3);

    // If we assume attending or we don't allow changing to unknown, then we advance from unknown to attending.
    var assumeAttending = buttonField.getAttribute('data-assume-attending');
    var allowChangeToUnknown = buttonField.getAttribute('data-allow-change-to-unknown');
    if ((assumeAttending || !allowChangeToUnknown) && value == 0) {
        value += 1;
    }

    // Set value in input field and display the button appropriately.
    inputField.value = value;
    buttonField.classList.remove(...threeStateCheckboxClasses);
    buttonField.classList.add(threeStateCheckboxClasses[value]);

    cell.classList.toggle('depping', value == DEPPING);
    cell.classList.remove('open');
    var depName = depNameField(buttonField);
    if (depName != null) {
        depName.required = value == DEPPING;
        if (value == DEPPING) {
            openDepName(buttonField);
        }
    }
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
        button.addEventListener('click', (event) => switchThreeStateCheckbox(event.currentTarget, event.currentTarget.dataset.inputId));
    }

    // Changing only the dep's name still has to submit the status it belongs to.
    for (const depName of document.querySelectorAll('.dep-name')) {
        depName.addEventListener('input', () => {
            var button = depName.parentNode.querySelector('.three-state-checkbox');
            statusInputField(button, button.dataset.inputId);
        });
        depName.addEventListener('invalid', () => depName.parentNode.classList.add('open'));
    }

    document.addEventListener('click', (event) => {
        for (const cell of document.querySelectorAll('.dep-cell.open')) {
            if (!cell.contains(event.target)) {
                cell.classList.remove('open');
            }
        }
    });
}

window.addEventListener('DOMContentLoaded', (event) => {
    initializeAllThreeStateCheckboxes();
});

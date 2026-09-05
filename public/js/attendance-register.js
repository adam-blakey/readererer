// Bulk actions for the attendance register. Each row carries the register
// status implied by that member's poll answer in data-poll-status.

function registerRows() {
    const table = document.getElementById('attendance-register');

    return table == null ? [] : Array.from(table.querySelectorAll('tbody tr[data-poll-status]'));
}

function setRegisterRowStatus(row, value) {
    const radio = row.querySelector('.register-status[value="' + value + '"]');

    if (radio != null) {
        radio.checked = true;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function setWholeRegister(value) {
    registerRows().forEach((row) => setRegisterRowStatus(row, value));
}

// Marks everyone who answered the poll; anyone who didn't answer is left alone
// so that a genuine "we don't know" stays visible.
function fillRegisterFromPoll() {
    registerRows().forEach((row) => {
        const value = Number(row.getAttribute('data-poll-status'));

        if (value > 0) {
            setRegisterRowStatus(row, value);
        }
    });
}

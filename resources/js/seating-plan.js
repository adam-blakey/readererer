document.addEventListener('DOMContentLoaded', function () {
    const containers = Array.from(document.querySelectorAll('.seating-row .card-body .row, .row[data-row="unassigned"]'));
    let draggedItem = null;

    function updateSeatingPositions() {
        document.querySelectorAll('.seating-row').forEach(rowEl => {
            const row = rowEl.dataset.row;
            rowEl.querySelectorAll('.user-entry').forEach((userEl, index) => {
                const positionEl = userEl.querySelector('.seating-position');
                if (positionEl) {
                    if (row == "unassigned") {
                        positionEl.textContent = null;
                    }
                    else {
                        positionEl.textContent = `${row}${index + 1}`;
                    }
                }

                const originalRow = userEl.dataset.originalRow;
                const originalColumn = userEl.dataset.originalColumn;
                const changedIndicator = userEl.querySelector('.seating-position-changed');

                console.log(originalColumn);

                if (originalRow == "" && row == "unassigned") {
                    changedIndicator.style.display = 'none';
                }
                else if (originalRow !== row || originalColumn != (index + 1)) {
                    changedIndicator.style.display = 'inline';
                } else {
                    changedIndicator.style.display = 'none';
                }
            });
        });

        document.querySelectorAll('.row[data-row="unassigned"] .user-entry').forEach(userEl => {
            const positionEl = userEl.querySelector('.seating-position');
            if (positionEl) {
                positionEl.textContent = '';
            }

            const originalRow = userEl.dataset.originalRow;
            const changedIndicator = userEl.querySelector('.seating-position-changed');

            if (originalRow) {
                changedIndicator.style.display = 'inline';
            } else {
                changedIndicator.style.display = 'none';
            }
        });
    }

    function setupDraggable(item) {
        item.draggable = true;
        item.addEventListener('dragstart', (e) => {
            draggedItem = e.target.closest('.user-entry');
            setTimeout(() => {
                draggedItem.style.opacity = '0.5';
            }, 0);
            containers.forEach(c => c.closest('.seating-row, .card-body').classList.add('drop-highlight'));
        });

        item.addEventListener('dragend', (e) => {
            setTimeout(() => {
                if (draggedItem) {
                    draggedItem.style.opacity = '';
                    draggedItem = null;
                }
                containers.forEach(c => c.closest('.seating-row, .card-body').classList.remove('drop-highlight'));
                updateSeatingPositions();
            }, 0);
        });
    }

    document.querySelectorAll('.user-entry').forEach(setupDraggable);

    containers.forEach(container => {
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientX);
            if (draggedItem) {
                if (afterElement == null) {
                    container.appendChild(draggedItem);
                } else {
                    container.insertBefore(draggedItem, afterElement);
                }
            }
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            if (draggedItem) {
                const parentRow = draggedItem.closest('.seating-row');
                if (parentRow && parentRow.nextElementSibling && parentRow.nextElementSibling.style.display === 'none') {
                    const newRow = parentRow.nextElementSibling;
                    newRow.style.display = '';
                    const newRowLetter = String.fromCharCode(parentRow.dataset.row.charCodeAt(0) + 1);
                    newRow.dataset.row = newRowLetter;
                    newRow.querySelector('.card-title').textContent = `Row ${newRowLetter}`;

                    const nextNewRow = newRow.cloneNode(true);
                    const nextRowLetter = String.fromCharCode(newRowLetter.charCodeAt(0) + 1);
                    nextNewRow.dataset.row = nextRowLetter;
                    nextNewRow.querySelector('.card-title').textContent = `Row ${nextRowLetter}`;
                    nextNewRow.querySelector('.row').innerHTML = '';
                    newRow.parentNode.appendChild(nextNewRow);

                    containers.push(nextNewRow.querySelector('.row'));
                    nextNewRow.querySelector('.row').addEventListener('dragover', (e) => {
                        e.preventDefault();
                        const afterElement = getDragAfterElement(nextNewRow.querySelector('.row'), e.clientX);
                        if (draggedItem) {
                            if (afterElement == null) {
                                nextNewRow.querySelector('.row').appendChild(draggedItem);
                            } else {
                                nextNewRow.querySelector('.row').insertBefore(draggedItem, afterElement);
                            }
                        }
                    });
                }
            }
        });
    });

    function getDragAfterElement(container, x) {
        const draggableElements = [...container.querySelectorAll('.user-entry:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    const saveButton = document.getElementById('save-seating-plan');
    saveButton.addEventListener('click', () => {
        const seatingPlan = {};
        document.querySelectorAll('.seating-row .card-body .row').forEach(rowEl => {
            const row = rowEl.closest('[data-row]').dataset.row;
            const users = [];
            rowEl.querySelectorAll('.user-entry').forEach((userEl, index) => {
                if (row == "unassigned") {
                    users.push({
                        id: userEl.dataset.userId,
                        column: null
                    });
                }
                else {
                    users.push({
                        id: userEl.dataset.userId,
                        column: index + 1
                    });
                }
            });

            seatingPlan[row] = users;
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        const dataInput = document.createElement('input');
        dataInput.type = 'hidden';
        dataInput.name = 'seating_plan';
        dataInput.value = JSON.stringify(seatingPlan);
        form.appendChild(dataInput);

        document.body.appendChild(form);
        form.submit();
    });

    updateSeatingPositions();
});

document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('seating-rows');
    let draggedItem = null;
    let positionsUpdated = false;

    window.onbeforeunload = function (e) {
        return positionsUpdated ? 1 : null;
    }

    function assignedRows() {
        return Array.from(rowsContainer.querySelectorAll('.seating-row'));
    }

    function createRow(letter) {
        const card = document.createElement('div');
        card.className = 'card mb-3 seating-row';
        card.dataset.row = letter;

        const header = document.createElement('div');
        header.className = 'card-header';
        const title = document.createElement('h2');
        title.className = 'card-title';
        title.textContent = `Row ${letter}`;
        header.appendChild(title);

        const body = document.createElement('div');
        body.className = 'card-body';
        const container = document.createElement('div');
        container.className = 'row min-h-2 drop-container';
        body.appendChild(container);

        card.appendChild(header);
        card.appendChild(body);

        return card;
    }

    // Counts like spreadsheet columns, matching PHP's str_increment: Z is followed by AA.
    function nextRowName(name) {
        const last = name.slice(-1);
        if (last !== 'Z') {
            return name.slice(0, -1) + String.fromCharCode(last.charCodeAt(0) + 1);
        }
        return (name.length > 1 ? nextRowName(name.slice(0, -1)) : 'A') + 'A';
    }

    // Exactly one empty row after the last occupied one, so there is always somewhere to drop a member.
    function normaliseRows() {
        const rows = assignedRows();
        let lastOccupied = -1;
        rows.forEach((row, index) => {
            if (row.querySelector('.user-entry')) {
                lastOccupied = index;
            }
        });

        for (let index = rows.length - 1; index > lastOccupied + 1; index--) {
            rows[index].remove();
        }

        if (lastOccupied === rows.length - 1) {
            const lastRow = rows[lastOccupied];
            rowsContainer.appendChild(createRow(lastRow ? nextRowName(lastRow.dataset.row) : 'A'));
        }
    }

    function updateSeatingPositions() {
        let anyUpdated = false;

        document.querySelectorAll('.seating-row').forEach(rowEl => {
            const row = rowEl.dataset.row;
            const unassigned = row === 'unassigned';

            rowEl.querySelectorAll('.user-entry').forEach((userEl, index) => {
                const positionEl = userEl.querySelector('.seating-position');
                if (positionEl) {
                    positionEl.textContent = unassigned ? '' : `${row}${index + 1}`;
                }

                const originalRow = userEl.dataset.originalRow;
                const originalColumn = userEl.dataset.originalColumn;
                const changed = unassigned
                    ? originalRow !== ''
                    : (originalRow !== row || originalColumn != (index + 1));

                const changedIndicator = userEl.querySelector('.seating-position-changed');
                if (changedIndicator) {
                    changedIndicator.style.display = changed ? 'inline' : 'none';
                }

                const originalPositionEl = userEl.querySelector('.seating-position-original');
                if (originalPositionEl) {
                    originalPositionEl.textContent = originalRow ? `${originalRow}${originalColumn}` : '';
                    originalPositionEl.style.display = (changed && originalRow) ? 'inline-block' : 'none';
                }

                if (changed) {
                    anyUpdated = true;
                }
            });
        });

        positionsUpdated = anyUpdated;
    }

    document.querySelectorAll('.user-entry').forEach(item => {
        item.draggable = true;
    });

    function startDrag(entry) {
        draggedItem = entry;
        setTimeout(() => {
            entry.style.opacity = '0.5';
        }, 0);
        document.querySelectorAll('.seating-row').forEach(rowEl => rowEl.classList.add('drop-highlight'));
    }

    function endDrag() {
        if (draggedItem) {
            draggedItem.style.opacity = '';
            draggedItem = null;
        }
        document.querySelectorAll('.seating-row').forEach(rowEl => rowEl.classList.remove('drop-highlight'));
        normaliseRows();
        updateSeatingPositions();
    }

    function moveDraggedInto(container, x, y) {
        const afterElement = getDragAfterElement(container, x, y);
        if (afterElement == null) {
            container.appendChild(draggedItem);
        } else if (afterElement !== draggedItem.nextElementSibling) {
            container.insertBefore(draggedItem, afterElement);
        }
    }

    // Delegated from the document so rows added after load behave like server-rendered ones.
    document.addEventListener('dragstart', (e) => {
        const entry = e.target.closest('.user-entry');
        if (entry) {
            startDrag(entry);
        }
    });

    document.addEventListener('dragend', endDrag);

    document.addEventListener('dragover', (e) => {
        if (!draggedItem) {
            return;
        }

        const container = e.target.closest('.drop-container');
        if (!container) {
            return;
        }

        e.preventDefault();
        moveDraggedInto(container, e.clientX, e.clientY);
    });

    document.addEventListener('drop', (e) => {
        if (e.target.closest('.drop-container')) {
            e.preventDefault();
        }
    });

    // Touch and pen drag from the grab handle; a mouse keeps using the native drag above.
    let pointer = null;

    function movePointerDrag() {
        const container = document.elementFromPoint(pointer.x, pointer.y)?.closest('.drop-container');
        if (container) {
            moveDraggedInto(container, pointer.x, pointer.y);
        }
    }

    function autoScroll() {
        if (!pointer) {
            return;
        }

        const edge = 60;
        const speed = pointer.y < edge ? -10 : (pointer.y > window.innerHeight - edge ? 10 : 0);
        if (speed) {
            window.scrollBy(0, speed);
            movePointerDrag();
        }
        requestAnimationFrame(autoScroll);
    }

    document.addEventListener('pointerdown', (e) => {
        const handle = e.target.closest('.seat-handle');
        if (!handle || e.pointerType === 'mouse' || pointer) {
            return;
        }

        e.preventDefault();
        handle.setPointerCapture(e.pointerId);
        pointer = { id: e.pointerId, x: e.clientX, y: e.clientY };
        startDrag(handle.closest('.user-entry'));
        requestAnimationFrame(autoScroll);
    });

    document.addEventListener('pointermove', (e) => {
        if (pointer?.id !== e.pointerId) {
            return;
        }

        pointer.x = e.clientX;
        pointer.y = e.clientY;
        movePointerDrag();
    });

    function endPointerDrag(e) {
        if (pointer?.id !== e.pointerId) {
            return;
        }

        pointer = null;
        endDrag();
    }

    document.addEventListener('pointerup', endPointerDrag);
    document.addEventListener('pointercancel', endPointerDrag);

    // The first entry, in reading order, that the point is above or to the left of; entries wrap onto several lines on narrow screens.
    function getDragAfterElement(container, x, y) {
        return [...container.querySelectorAll('.user-entry')]
            .filter(el => el !== draggedItem)
            .find(child => {
                const box = child.getBoundingClientRect();
                return y < box.top || (y <= box.bottom && x < box.left + box.width / 2);
            }) ?? null;
    }

    const downloadMenu = document.getElementById('download-menu');
    if (downloadMenu) {
        const searchInput = downloadMenu.querySelector('#download-search');
        const noResults = downloadMenu.querySelector('#download-no-results');

        function applyDownloadFilter() {
            const scope = downloadMenu.querySelector('input[name="download-scope"]:checked').value;
            const query = searchInput.value.trim().toLowerCase();
            let visible = 0;

            downloadMenu.querySelectorAll('.download-option').forEach(item => {
                const matches = (scope === 'all' || item.dataset.when === scope)
                    && item.dataset.search.includes(query);
                item.style.display = matches ? '' : 'none';
                if (matches) {
                    visible++;
                }
            });

            noResults.style.display = visible ? 'none' : '';
        }

        searchInput.addEventListener('input', applyDownloadFilter);
        downloadMenu.querySelectorAll('input[name="download-scope"]').forEach(radio => {
            radio.addEventListener('change', applyDownloadFilter);
        });
        applyDownloadFilter();
    }

    const saveButton = document.getElementById('save-seating-plan');
    saveButton.addEventListener('click', () => {
        const seatingPlan = {};
        document.querySelectorAll('.seating-row').forEach(rowEl => {
            const row = rowEl.dataset.row;
            const users = [];
            rowEl.querySelectorAll('.user-entry').forEach((userEl, index) => {
                users.push({
                    id: userEl.dataset.userId,
                    column: row === 'unassigned' ? null : index + 1,
                });
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

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        form.appendChild(methodInput);

        const dataInput = document.createElement('input');
        dataInput.type = 'hidden';
        dataInput.name = 'seating_plan';
        dataInput.value = JSON.stringify(seatingPlan);
        form.appendChild(dataInput);

        document.body.appendChild(form);
        // Saving is an intentional navigation; don't trigger the unsaved-changes prompt.
        positionsUpdated = false;
        form.submit();
    });

    normaliseRows();
    updateSeatingPositions();
});

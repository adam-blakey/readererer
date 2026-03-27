document.addEventListener('DOMContentLoaded', function () {
    const containers = document.querySelectorAll('.card-body .row');
    let draggedItem = null;

    document.querySelectorAll('.col-md-3').forEach(item => {
        item.draggable = true;
        item.addEventListener('dragstart', (e) => {
            draggedItem = e.target.closest('.col-md-3');
            setTimeout(() => {
                draggedItem.style.display = 'none';
            }, 0);
        });

        item.addEventListener('dragend', (e) => {
            setTimeout(() => {
                if (draggedItem) {
                    draggedItem.style.display = '';
                    draggedItem = null;
                }
            }, 0);
        });
    });

    containers.forEach(container => {
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientX);
            if (afterElement == null) {
                container.appendChild(draggedItem);
            } else {
                container.insertBefore(draggedItem, afterElement);
            }
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            if (draggedItem) {
                draggedItem.style.display = '';
            }
        });
    });

    function getDragAfterElement(container, x) {
        const draggableElements = [...container.querySelectorAll('.col-md-3:not(.dragging)')];

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
        document.querySelectorAll('.card-body .row').forEach(rowEl => {
            const row = rowEl.dataset.row;
            const users = [];
            rowEl.querySelectorAll('.col-md-3').forEach((userEl, index) => {
                users.push({
                    id: userEl.dataset.userId,
                    column: index + 1
                });
            });
            seatingPlan[row] = users;
        });

        const ensembleSlug = window.location.pathname.split('/')[2];
        fetch(`/ensembles/${ensembleSlug}/seating-plan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(seatingPlan)
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Failed to save seating plan');
            }
        });
    });
});

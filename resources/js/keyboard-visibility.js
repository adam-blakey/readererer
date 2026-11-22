const keepFocusedFieldVisible = () => {
    const field = document.activeElement;

    if (field?.matches('input:not([type=checkbox], [type=radio], [type=file]), textarea, select')) {
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
};

window.visualViewport?.addEventListener('resize', keepFocusedFieldVisible);

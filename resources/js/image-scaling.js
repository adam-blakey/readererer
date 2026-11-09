const MAX_SIDE = 1600;

document.addEventListener('change', async (event) => {
    const input = event.target;
    const file = input.files?.[0];

    if (!input.hasAttribute('data-scale-image') || !file || !/^image\/(jpeg|png|webp)$/.test(file.type)) {
        return;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const ratio = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));

        if (ratio === 1) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * ratio);
        canvas.height = Math.round(bitmap.height * ratio);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, file.type, 0.85));
        const transfer = new DataTransfer();
        transfer.items.add(new File([blob], file.name, { type: file.type }));
        input.files = transfer.files;
    } catch {
        // An image the browser cannot decode is uploaded unchanged for the server to judge.
    }
});

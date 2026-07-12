import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/seating-plan.js',
                'resources/images/favicon.png',
                'resources/images/readererer-long-logo.svg',
                'resources/images/readererer-square-logo.svg'
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'node_modules/@tabler/icons/icons/outline/*',
                    dest: 'icons',
                    // vite-plugin-static-copy v4 preserves the matched file's
                    // full directory path under dest by default; stripBase
                    // flattens it back to icons/<name>.svg (the path the
                    // <x-icon> component reads).
                    rename: { stripBase: true },
                },
            ],
        }),
    ],
    treeShake: false,
});

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
                    // v4 keeps the source directory path by default; <x-icon> reads icons/<name>.svg.
                    rename: { stripBase: true },
                },
            ],
        }),
    ],
    treeShake: false,
});

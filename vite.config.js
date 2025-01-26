import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/images/favicon.png',
                'resources/images/readererer-long-logo.svg',
                'resources/images/readererer-square-logo.svg'
            ],
            refresh: true,
        }),
    ],
    treeShake: false,
});

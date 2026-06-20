import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/auth.css',
                'resources/js/app.js',
                'resources/js/site.js',
                'resources/js/home.js',
                'resources/css/epaper-viewer.css',
                'resources/js/epaper-viewer.js',
                'resources/js/epaper-covers.js',
                'resources/js/mobile-bridge.js',
                'resources/css/submission-editor.css',
                'resources/js/submission-editor.js',
            ],
            refresh: true,
        }),
    ],
});

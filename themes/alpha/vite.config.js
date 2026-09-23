import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/css/app.css',
                'resources/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: '../../public/themes/alpha/build',
        emptyOutDir: true,
    },
});
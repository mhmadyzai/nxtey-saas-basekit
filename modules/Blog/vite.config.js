import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: 'resources/assets/js/app.js',
            name: 'Blog',
            fileName: (format) => `blog.${format}.js`
        },
        rollupOptions: {
            output: {
                dir: '../../public/build/blog',
            }
        }
    },
    // This allows the root loader to extract inputs easily
    modular: {
        inputs: [
            'resources/assets/js/app.js',
            'resources/assets/css/app.css'
        ]
    }
});

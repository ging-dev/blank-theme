import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path'

process.env['APP_URL'] = 'http://localhost:8000'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/app.js', 'style.css'],
            refresh: ['**.php'],
            hotFile: resolve(__dirname, 'hot'),
            publicDirectory: './',
        }),
    ],
});

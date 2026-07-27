import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // tree.js is a separate entry so the D3 (and later Three.js)
            // bundle weight is only ever downloaded on the /tree page.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/tree.js', 'resources/js/dashboard-tree.js'],
            refresh: true,
        }),
    ],
});

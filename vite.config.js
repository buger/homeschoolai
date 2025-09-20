import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/enhanced-markdown.css',
                'resources/css/unified-markdown-editor.css',
                'resources/js/app.js',
                'resources/js/rich-content-editor.js',
                'resources/js/enhanced-markdown.js',
                'resources/js/unified-markdown-editor.js'
            ],
            refresh: true,
        }),
    ],
});

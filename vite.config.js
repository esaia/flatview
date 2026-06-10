import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/irep-admin/src/main.ts',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        dedupe: ['react', 'react-dom', '@wordpress/element'],
        alias: [
            {
                find: /^ire-preview$/,
                replacement: resolve(__dirname, 'resources/js/ire-preview-library/dist/lib.es.js'),
            },
            {
                find: 'ire-preview/dist/styles.css',
                replacement: resolve(__dirname, 'resources/js/ire-preview-library/dist/styles.css'),
            },
            {
                find: '@components',
                replacement: resolve(__dirname, 'resources/js/irep-admin/src/components'),
            },
            {
                find: /^@\/(src|types)(\/.*)?$/,
                replacement: `${resolve(__dirname, 'resources/js/irep-admin')}/$1$2`,
            },
        ],
    },
});

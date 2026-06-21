import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const viteHost = process.env.VITE_DEV_HOST || 'localhost';

export default defineConfig({
    server: {
        host: viteHost,
        cors: true,
        hmr: {
            host: viteHost,
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
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
});

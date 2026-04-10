import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: Number(process.env.VITE_PORT ?? 5173),
        strictPort: true,
        origin: process.env.VITE_DEV_SERVER_ORIGIN ?? 'http://astrocopa.app.test:5173',
        cors: {
            origin: ['http://astrocopa.app.test'],
        },
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'astrocopa.app.test',
            port: Number(process.env.VITE_PORT ?? 5173),
            protocol: 'ws',
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
                compilerOptions: {
                    isCustomElement: (tag) => tag === 'api-sports-widget',
                },
            },
        }),
    ],
});

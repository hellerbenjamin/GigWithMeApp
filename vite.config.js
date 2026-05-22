import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue(),
        Components({
            resolvers: [PrimeVueResolver()],
            // Where to write the generated type declarations for the
            // auto-imported components.
            dts: 'resources/js/components.d.ts',
        }),
        tailwindcss(),
    ],
    server: {
        // Listen on all interfaces inside the ddev web container so the
        // dev server is reachable through ddev-router.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Allow the request to come in over the ddev hostname.
        cors: true,
        allowedHosts: ['.ddev.site'],
        // Inside ddev, advertise asset + HMR websocket URLs at the routed
        // https port (set via .ddev/config.vite.yaml). Outside ddev this is
        // skipped so a plain host `npm run dev` still works.
        ...(process.env.DDEV_PRIMARY_URL
            ? { origin: `${process.env.DDEV_PRIMARY_URL}:5173` }
            : {}),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

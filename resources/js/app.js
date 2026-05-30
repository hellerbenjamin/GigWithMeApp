import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import PrimeVue from 'primevue/config';
import { BandPreset } from './theme';

const appName = import.meta.env.VITE_APP_NAME || 'Band';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: BandPreset,
                    options: {
                        // Tailwind v4 reset utilities live in this layer; keeping
                        // PrimeVue styles below them avoids specificity clashes.
                        darkModeSelector: '.dark',
                        cssLayer: {
                            name: 'primevue',
                            order: 'theme, base, primevue',
                        },
                    },
                },
            })
            .mount(el);
    },
    progress: {
        color: '#6c4fd8', // Amp Violet
    },
});

// Register the push/PWA service worker at the origin root so its scope covers
// every page (public RSVP, member home, the app). Best-effort — unsupported
// browsers just don't get push.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

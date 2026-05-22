import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import { BandPreset } from './theme';

import App from './App.vue';

const app = createApp(App);

app.use(PrimeVue, {
    theme: {
        preset: BandPreset,
        options: {
            // Tailwind v4 reset utilities live in this layer; keeping PrimeVue
            // styles below them avoids specificity clashes.
            darkModeSelector: '.dark',
            cssLayer: {
                name: 'primevue',
                order: 'theme, base, primevue',
            },
        },
    },
});

app.mount('#app');

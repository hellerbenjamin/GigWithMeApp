import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

// Brand preset for the band booking app. Built on PrimeVue's Aura preset and
// overridden with the palette documented in docs/band-booking-color-palette.md.
//
//   Amp Violet  #6C4FD8  -> primary (buttons, links, active states)
//   Warm neutrals (Canvas/Surface/Muted/Ink) -> light-mode surface ramp
//
// Palettes were generated with the `palette()` / `mix()` helpers from
// @primeuix/themes so each shade stays tonally consistent.
export const BandPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#f8f6fd',
            100: '#dcd5f6',
            200: '#c0b3ee',
            300: '#a492e7',
            400: '#8870df',
            500: '#6c4fd8', // Amp Violet
            600: '#5c43b8',
            700: '#4c3797',
            800: '#3b2b77',
            900: '#2b2056',
            950: '#1b1436',
        },
        colorScheme: {
            light: {
                surface: {
                    0: '#ffffff',
                    50: '#faf8f5', // Canvas — page background
                    100: '#edeae3', // Surface — cards, raised surfaces
                    200: '#cac7c1',
                    300: '#a6a49f',
                    400: '#83817c',
                    500: '#5f5e5a', // Muted — borders, secondary text
                    600: '#4b4a4a',
                    700: '#3e3d3f',
                    800: '#302f34',
                    900: '#26252b',
                    950: '#1c1b23', // Ink — primary text
                },
            },
            // Dark mode is built on Stage Indigo per docs/band-booking-color-palette.md
            // ("invert the neutrals rather than rethinking the system"). The ramp
            // runs light violet-tinted text (0–100) down to deep indigo grounds
            // (800–950) so PrimeVue surfaces sit alongside the indigo chrome.
            dark: {
                surface: {
                    0: '#ffffff',
                    50: '#f5f3fb',
                    100: '#e7e2f2',
                    200: '#c8c0de',
                    300: '#a79ec8',
                    400: '#7d7399',
                    500: '#5a5175',
                    600: '#433c59',
                    700: '#332d47',
                    800: '#221b34', // Riser — cards, raised surfaces
                    900: '#1b1530', // panels
                    950: '#16121f', // Backstage — deepest ground
                },
            },
        },
    },
});

export default BandPreset;

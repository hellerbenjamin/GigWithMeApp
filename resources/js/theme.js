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
    primitive: {
        // App-wide 4px border radius. Collapsing Aura's xs–xl scale to a single
        // value means every component that resolves `{border.radius.*}` lands on
        // 4px. `none` stays 0 so square-cornered elements are still possible.
        borderRadius: {
            none: '0',
            xs: '4px',
            sm: '4px',
            md: '4px',
            lg: '4px',
            xl: '4px',
        },
    },
    semantic: {
        // PREVIEW: earthy palette (dbd56e / 88ab75 / 2d93ad / 7d7c84 / de8f6e),
        // pushed more vibrant (less pastel). Primary = Pacific Cyan, saturated.
        primary: {
            50: '#ebf8fb',
            100: '#c4ecf3',
            200: '#93dde9',
            300: '#58cadc',
            400: '#29b2cc',
            500: '#1296b8', // Pacific Cyan — vibrant
            600: '#0f7c99',
            700: '#0c627a',
            800: '#0b4b5d',
            900: '#0a3340',
            950: '#06222b',
        },
        colorScheme: {
            // PREVIEW: warm-gray neutral ramp built around Rosy Granite (#7d7c84,
            // the 500 muted step), deepening to a near-black ink for text.
            light: {
                surface: {
                    0: '#ffffff',
                    50: '#f7f6f5', // Canvas — page background (warm off-white)
                    100: '#eceae9', // Surface — cards, raised surfaces
                    200: '#d6d4d6',
                    300: '#b6b4b8',
                    400: '#949298',
                    500: '#7d7c84', // Rosy Granite — Muted (borders, secondary text)
                    600: '#636269',
                    700: '#4e4d53',
                    800: '#3a393e',
                    900: '#2c2b30',
                    950: '#1f1e22', // Ink — primary text
                },
            },
            // Dark mode: neutral charcoal ramp that matches the sidebar's character.
            // Light end (0–300) stays slightly cool for component text; dark end
            // (700–950) drops the purple cast in favour of unified warm-neutral.
            dark: {
                surface: {
                    0: '#ffffff',
                    50: '#f5f4f8',
                    100: '#e8e7ee',
                    200: '#cbc9d6',
                    300: '#a8a6b8',
                    400: '#7e7b8e',
                    500: '#5c596c',
                    600: '#403e4e',
                    700: '#2c2b38',
                    800: '#1c1b24', // Riser — cards, raised surfaces
                    900: '#17161e', // panels
                    950: '#131219', // Backstage — deepest ground
                },
            },
        },
    },
});

export default BandPreset;

import { useColorScheme } from 'react-native';

/**
 * Brand theme for the mobile app, mirroring the web app's design tokens.
 * Source of truth on web: resources/css/app.css (@theme) and
 * resources/js/theme.js (PrimeVue BandPreset).
 *
 * Primary is Pacific Cyan; surfaces use the warm-neutral ramp in light mode
 * and the neutral-charcoal ramp in dark mode. Radius is a flat 4px everywhere
 * (pills excepted), matching the web's collapsed radius scale.
 */

// Raw palette — keep these 1:1 with the web @theme values.
export const palette = {
    // Primary — Pacific Cyan (50..950)
    primary: {
        50: '#ebf8fb',
        100: '#c4ecf3',
        200: '#93dde9',
        300: '#58cadc',
        400: '#29b2cc',
        500: '#1296b8',
        600: '#0f7c99',
        700: '#0c627a',
        800: '#0b4b5d',
        900: '#0a3340',
        950: '#06222b',
    },
    // Warm-neutral surface ramp — light mode (Canvas -> Ink)
    surfaceLight: {
        0: '#ffffff',
        50: '#f7f6f5', // Canvas — page background
        100: '#eceae9', // Surface — cards, raised surfaces
        200: '#d6d4d6',
        300: '#b6b4b8',
        400: '#949298',
        500: '#7d7c84', // Muted — borders, secondary text
        600: '#636269',
        700: '#4e4d53',
        800: '#3a393e',
        900: '#2c2b30',
        950: '#1f1e22', // Ink — primary text
    },
    // Neutral-charcoal ramp — dark mode (Backstage / Riser)
    surfaceDark: {
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
    // Accents
    coral: '#e8794c', // Encore Coral — warm CTA pop
    sage: '#6fa84a', // Soundcheck Teal — positive highlight
    amber: '#d6c63a', // Stage Amber — support tint
    // Status (fill + text pairs)
    status: {
        confirmed: '#2dd4a8',
        confirmedText: '#0f6e56',
        pending: '#ef9f27',
        pendingText: '#854f0b',
        cancelled: '#e24b4a',
        cancelledText: '#a32d2d',
    },
} as const;

// Flat 4px radius scale (pills stay round).
export const radius = { sm: 4, md: 4, lg: 4, xl: 4, pill: 999 } as const;

// 4px spacing unit, matching the web's rhythm.
export const space = (n: number) => n * 4;

// Type faces. The display face (Bricolage Grotesque) is loaded at runtime in
// the root layout. Body stays on the system UI font.
export const fonts = {
    display: 'BricolageGrotesque_700Bold',
    displaySemibold: 'BricolageGrotesque_600SemiBold',
} as const;

export type Scheme = 'light' | 'dark';

export interface Theme {
    scheme: Scheme;
    isDark: boolean;
    colors: {
        background: string; // page background
        card: string; // primary raised surface (list rows, cards)
        surface: string; // secondary fill (info panels)
        surfaceAlt: string; // subtle fill (read-only inputs)
        border: string;
        borderStrong: string;
        divider: string; // hairline separators inside grouped rows
        text: string; // primary text (Ink)
        textMuted: string; // secondary text
        textSubtle: string; // tertiary / placeholders
        primary: string; // brand fills (buttons, switches)
        primaryText: string; // brand as foreground (links, back button)
        primaryTint: string; // faint brand background
        onPrimary: string; // text on a primary fill
        accent: string; // Encore Coral
        // status foregrounds + soft backgrounds
        confirmed: string;
        confirmedBg: string;
        confirmedSolid: string; // fill with onPrimary text
        pending: string;
        pendingBg: string;
        cancelled: string;
        cancelledBg: string;
        cancelledSolid: string; // fill with onPrimary text
        danger: string; // destructive text (sign out)
    };
    radius: typeof radius;
    fonts: typeof fonts;
    space: typeof space;
}

function build(scheme: Scheme): Theme {
    const isDark = scheme === 'dark';
    const s = isDark ? palette.surfaceDark : palette.surfaceLight;
    const p = palette.primary;
    const st = palette.status;

    return {
        scheme,
        isDark,
        colors: {
            background: isDark ? s[950] : s[50],
            card: isDark ? s[800] : s[0],
            surface: isDark ? s[900] : s[100],
            surfaceAlt: isDark ? s[900] : s[50],
            border: isDark ? s[700] : s[200],
            borderStrong: isDark ? s[600] : s[300],
            divider: isDark ? s[700] : s[100],
            text: isDark ? s[50] : s[950],
            textMuted: isDark ? s[400] : s[500],
            textSubtle: isDark ? s[500] : s[400],
            primary: p[500],
            primaryText: isDark ? p[300] : p[600],
            primaryTint: isDark ? p[950] : p[50],
            onPrimary: '#ffffff',
            accent: palette.coral,
            confirmed: isDark ? st.confirmed : st.confirmedText,
            confirmedBg: isDark ? 'rgba(45,212,168,0.16)' : '#e7f8f2',
            confirmedSolid: st.confirmedText,
            pending: isDark ? st.pending : st.pendingText,
            pendingBg: isDark ? 'rgba(239,159,39,0.16)' : '#fbf1e0',
            cancelled: isDark ? st.cancelled : st.cancelledText,
            cancelledBg: isDark ? 'rgba(226,75,74,0.16)' : '#fbebeb',
            cancelledSolid: st.cancelledText,
            danger: isDark ? '#f18a89' : st.cancelledText,
        },
        radius,
        fonts,
        space,
    };
}

export const themes = { light: build('light'), dark: build('dark') };

/** Active theme, following the OS light/dark setting. */
export function useTheme(): Theme {
    return useColorScheme() === 'dark' ? themes.dark : themes.light;
}

/** Foreground + soft background for a gig or RSVP status token. */
export function statusColors(theme: Theme, status: string) {
    switch (status) {
        case 'confirmed':
        case 'available':
            return {
                fg: theme.colors.confirmed,
                bg: theme.colors.confirmedBg,
                solid: theme.colors.confirmedSolid,
            };
        case 'cancelled':
        case 'unavailable':
            return {
                fg: theme.colors.cancelled,
                bg: theme.colors.cancelledBg,
                solid: theme.colors.cancelledSolid,
            };
        case 'pending':
        default:
            return {
                fg: theme.colors.pending,
                bg: theme.colors.pendingBg,
                solid: theme.colors.pending,
            };
    }
}

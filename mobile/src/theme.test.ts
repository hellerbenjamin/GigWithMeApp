import { palette, radius, statusColors, themes } from './theme';

describe('palette', () => {
    it('keeps the primary in sync with the web brand (Pacific Cyan)', () => {
        expect(palette.primary[500]).toBe('#1296b8');
    });
});

describe('radius', () => {
    it('is a flat 4px scale, with pills staying round', () => {
        expect(radius.sm).toBe(4);
        expect(radius.md).toBe(4);
        expect(radius.lg).toBe(4);
        expect(radius.xl).toBe(4);
        expect(radius.pill).toBe(999);
    });
});

describe('themes', () => {
    it('exposes distinct light and dark schemes', () => {
        expect(themes.light.scheme).toBe('light');
        expect(themes.dark.scheme).toBe('dark');
        expect(themes.light.isDark).toBe(false);
        expect(themes.dark.isDark).toBe(true);
    });

    it('inverts background and text between light and dark', () => {
        expect(themes.light.colors.background).not.toBe(themes.dark.colors.background);
        expect(themes.light.colors.text).not.toBe(themes.dark.colors.text);
        // Light background is the warm off-white Canvas; dark is Backstage.
        expect(themes.light.colors.background).toBe('#f7f6f5');
        expect(themes.dark.colors.background).toBe('#131219');
    });

    it('uses the same brand primary in both schemes', () => {
        expect(themes.light.colors.primary).toBe(palette.primary[500]);
        expect(themes.dark.colors.primary).toBe(palette.primary[500]);
    });
});

describe('statusColors', () => {
    const t = themes.light;

    it('maps confirmed and available to the confirmed tokens', () => {
        for (const status of ['confirmed', 'available']) {
            const c = statusColors(t, status);
            expect(c.fg).toBe(t.colors.confirmed);
            expect(c.bg).toBe(t.colors.confirmedBg);
            expect(c.solid).toBe(t.colors.confirmedSolid);
        }
    });

    it('maps cancelled and unavailable to the cancelled tokens', () => {
        for (const status of ['cancelled', 'unavailable']) {
            const c = statusColors(t, status);
            expect(c.fg).toBe(t.colors.cancelled);
            expect(c.bg).toBe(t.colors.cancelledBg);
            expect(c.solid).toBe(t.colors.cancelledSolid);
        }
    });

    it('falls back to pending tokens for pending and unknown statuses', () => {
        for (const status of ['pending', 'something-else']) {
            const c = statusColors(t, status);
            expect(c.fg).toBe(t.colors.pending);
            expect(c.bg).toBe(t.colors.pendingBg);
        }
    });
});

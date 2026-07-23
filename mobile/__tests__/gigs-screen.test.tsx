import { render, screen, waitFor } from '@testing-library/react-native';
import React from 'react';

const mockApiFetch = jest.fn();
jest.mock('@/src/lib/api', () => ({
    apiFetch: (...args: unknown[]) => mockApiFetch(...args),
}));
jest.mock('@/src/context/AuthContext', () => ({
    useAuth: () => ({ token: 'tok', user: { name: 'Ben Heller' } }),
}));
jest.mock('expo-router', () => ({
    useRouter: () => ({ push: jest.fn() }),
}));

import GigsScreen from '../app/(tabs)/gigs';

function gigsResponse(data: unknown[]) {
    return { ok: true, json: async () => ({ data }) };
}

describe('GigsScreen', () => {
    beforeEach(() => mockApiFetch.mockReset());

    it("shows the member's RSVP for poll gigs and the gig status otherwise", async () => {
        mockApiFetch.mockResolvedValue(
            gigsResponse([
                {
                    id: 1, name: 'Show A', status: 'pending', date: '2026-08-01', start_time: '20:00',
                    band: { id: 1, name: 'The Band', slug: 'b' }, venue_name: 'The Venue',
                    rsvp: { status: 'available', label: 'Available' },
                },
                {
                    id: 2, name: 'Show B', status: 'confirmed', date: '2026-08-02', start_time: null,
                    band: { id: 1, name: 'The Band', slug: 'b' }, venue_name: null,
                    rsvp: null,
                },
            ]),
        );

        render(<GigsScreen />);

        // Poll gig with a response shows the RSVP label...
        expect(await screen.findByText('Available')).toBeTruthy();
        // ...while a gig without a response falls back to the gig status.
        expect(screen.getByText('confirmed')).toBeTruthy();
    });

    it('greets the member by first name', async () => {
        mockApiFetch.mockResolvedValue(gigsResponse([]));

        render(<GigsScreen />);

        await waitFor(() => expect(screen.getByText(/Ben/)).toBeTruthy());
    });
});

import { fireEvent, render, screen, waitFor } from '@testing-library/react-native';
import React from 'react';

// Variables referenced inside a jest.mock factory must be prefixed with "mock".
const mockApiFetch = jest.fn();
jest.mock('@/src/lib/api', () => ({
    apiFetch: (...args: unknown[]) => mockApiFetch(...args),
}));
jest.mock('@/src/context/AuthContext', () => ({
    useAuth: () => ({ signIn: jest.fn() }),
}));

// Imported after the mocks so the screen picks up the mocked modules.
import LoginScreen from '../app/(auth)/login';

describe('LoginScreen', () => {
    beforeEach(() => {
        mockApiFetch.mockReset().mockResolvedValue({ ok: true });
    });

    it('requests a magic link for the entered email and shows the confirmation', async () => {
        render(<LoginScreen />);

        expect(screen.getByText('Sign in to GigWithMe')).toBeTruthy();

        fireEvent.changeText(screen.getByPlaceholderText('your@email.com'), 'ben@example.com');
        fireEvent.press(screen.getByText('Send sign-in link'));

        await waitFor(() => {
            expect(mockApiFetch).toHaveBeenCalledWith(
                '/auth/magic-link',
                expect.objectContaining({
                    method: 'POST',
                    body: JSON.stringify({ email: 'ben@example.com' }),
                }),
            );
        });

        // The screen swaps to the "check your email" confirmation on success.
        expect(await screen.findByText('Check your email')).toBeTruthy();
    });

    it('does not call the API when no email has been entered', () => {
        render(<LoginScreen />);

        // The button is disabled until an email is present.
        fireEvent.press(screen.getByText('Send sign-in link'));

        expect(mockApiFetch).not.toHaveBeenCalled();
    });
});

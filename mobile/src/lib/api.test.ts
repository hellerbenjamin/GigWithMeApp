import { apiFetch } from './api';

// EXPO_PUBLIC_* vars are inlined by babel-preset-expo at transform time, so the
// base URL can't be overridden from here; assert path-append behavior instead of
// a specific host.

describe('apiFetch', () => {
    const fetchMock = jest.fn();

    beforeEach(() => {
        fetchMock.mockReset().mockResolvedValue({ ok: true });
        global.fetch = fetchMock as unknown as typeof fetch;
    });

    function lastCall() {
        const [url, init] = fetchMock.mock.calls[0];
        return { url: url as string, init: init as RequestInit };
    }

    it('appends the path to the /api/v1 base URL', async () => {
        await apiFetch('/gigs');
        expect(lastCall().url).toMatch(/^https?:\/\/.+\/api\/v1\/gigs$/);
    });

    it('always sends JSON Accept and Content-Type headers', async () => {
        await apiFetch('/gigs');
        expect(lastCall().init.headers).toMatchObject({
            Accept: 'application/json',
            'Content-Type': 'application/json',
        });
    });

    it('adds a bearer Authorization header when a token is given', async () => {
        await apiFetch('/gigs', { token: 'abc123' });
        expect(lastCall().init.headers).toMatchObject({ Authorization: 'Bearer abc123' });
    });

    it('omits the Authorization header when no token is given', async () => {
        await apiFetch('/gigs');
        expect(lastCall().init.headers).not.toHaveProperty('Authorization');
    });

    it('passes method and body through, and does not leak the token into fetch options', async () => {
        await apiFetch('/gigs/1/rsvp', {
            method: 'POST',
            token: 'abc123',
            body: JSON.stringify({ available: true }),
        });
        const { init } = lastCall();
        expect(init.method).toBe('POST');
        expect(init.body).toBe('{"available":true}');
        expect(init).not.toHaveProperty('token');
    });

    it('lets caller headers override the defaults', async () => {
        await apiFetch('/profile/avatar', {
            method: 'POST',
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        expect(lastCall().init.headers).toMatchObject({ 'Content-Type': 'multipart/form-data' });
    });
});

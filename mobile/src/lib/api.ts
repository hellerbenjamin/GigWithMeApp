const BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? 'https://gigwithme.app/api/v1';

export async function apiFetch(
    path: string,
    options: RequestInit & { token?: string } = {},
): Promise<Response> {
    const { token, headers, ...rest } = options;

    return fetch(`${BASE_URL}${path}`, {
        ...rest,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...headers,
        },
    });
}

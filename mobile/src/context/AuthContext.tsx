import { apiFetch } from '@/src/lib/api';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';
import { createContext, useCallback, useContext, useEffect, useState } from 'react';

const TOKEN_KEY = 'gigwithme_token';

export type BandRole = 'owner' | 'admin' | 'member' | 'roadie';

export interface AuthBand {
    id: number;
    name: string;
    slug: string;
    role: BandRole;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    phone_number: string | null;
    avatar_path: string | null;
    timezone: string | null;
}

interface AuthState {
    token: string | null;
    user: AuthUser | null;
    bands: AuthBand[];
    isLoading: boolean;
}

interface AuthContextValue extends AuthState {
    /** Call after a successful API login/exchange response. */
    signIn: (token: string, user: AuthUser, bands: AuthBand[]) => Promise<void>;
    signOut: () => Promise<void>;
    /** True if the user is owner or admin of at least one band. */
    isAdmin: boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [state, setState] = useState<AuthState>({
        token: null,
        user: null,
        bands: [],
        isLoading: true,
    });

    // Rehydrate from secure storage on mount.
    useEffect(() => {
        SecureStore.getItemAsync(TOKEN_KEY).then((stored) => {
            if (stored) {
                const { token, user, bands } = JSON.parse(stored);
                setState({ token, user, bands, isLoading: false });
            } else {
                setState((s) => ({ ...s, isLoading: false }));
            }
        });
    }, []);

    const signIn = useCallback(async (token: string, user: AuthUser, bands: AuthBand[]) => {
        await SecureStore.setItemAsync(TOKEN_KEY, JSON.stringify({ token, user, bands }));
        setState({ token, user, bands, isLoading: false });
    }, []);

    const signOut = useCallback(async () => {
        if (state.token) {
            try {
                // Best-effort: send the push token so the server can remove it.
                const expoPushToken = await Notifications.getExpoPushTokenAsync()
                    .then((t) => t.data)
                    .catch(() => null);

                await apiFetch('/auth/logout', {
                    method: 'POST',
                    token: state.token,
                    body: JSON.stringify({ push_token: expoPushToken }),
                });
            } catch {
                // Ignore network errors during sign-out.
            }
        }

        await SecureStore.deleteItemAsync(TOKEN_KEY);
        setState({ token: null, user: null, bands: [], isLoading: false });
    }, [state.token]);

    const isAdmin = state.bands.some((b) => b.role === 'owner' || b.role === 'admin');

    return (
        <AuthContext.Provider value={{ ...state, signIn, signOut, isAdmin }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth(): AuthContextValue {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}

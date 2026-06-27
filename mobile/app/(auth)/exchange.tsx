import { apiFetch } from '@/src/lib/api';
import { useAuth } from '@/src/context/AuthContext';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, Text, View } from 'react-native';
import { getDeviceNameAsync } from 'expo-device';

// Handles deep links of the form: gigwithme://exchange?token=<token>
// The root _layout redirects here after parsing the URL via expo-router.
export default function ExchangeScreen() {
    const { token } = useLocalSearchParams<{ token: string }>();
    const { signIn } = useAuth();
    const router = useRouter();
    const [status, setStatus] = useState<'exchanging' | 'error'>('exchanging');

    useEffect(() => {
        if (!token) {
            setStatus('error');
            return;
        }

        async function exchange() {
            try {
                const deviceName = (await getDeviceNameAsync()) ?? 'Mobile device';
                const res = await apiFetch('/auth/magic-link/exchange', {
                    method: 'POST',
                    body: JSON.stringify({ token, device_name: deviceName }),
                });

                if (!res.ok) {
                    setStatus('error');
                    return;
                }

                const data = await res.json();
                await signIn(data.token, data.user, data.bands);
                router.replace('/(tabs)/gigs');
            } catch {
                setStatus('error');
            }
        }

        exchange();
    }, [token]);

    if (status === 'error') {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32 }}>
                <Text style={{ fontSize: 18, fontWeight: '600', marginBottom: 8 }}>Link expired</Text>
                <Text style={{ color: '#6B7280', textAlign: 'center' }}>
                    This sign-in link has expired or already been used. Go back and request a new one.
                </Text>
            </View>
        );
    }

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
            <Text style={{ color: '#6B7280' }}>Signing you in…</Text>
        </View>
    );
}

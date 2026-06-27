import { AuthProvider, useAuth } from '@/src/context/AuthContext';
import { usePushNotifications } from '@/src/hooks/usePushNotifications';
import { Slot, useRouter, useSegments } from 'expo-router';
import { useEffect } from 'react';

function Guard() {
    const { token, isLoading } = useAuth();
    const segments = useSegments();
    const router = useRouter();

    usePushNotifications(token);

    useEffect(() => {
        if (isLoading) return;

        const inAuth = segments[0] === '(auth)';

        if (!token && !inAuth) {
            router.replace('/(auth)/login');
        } else if (token && inAuth) {
            router.replace('/(tabs)/gigs');
        }
    }, [token, isLoading, segments]);

    return <Slot />;
}

export default function RootLayout() {
    return (
        <AuthProvider>
            <Guard />
        </AuthProvider>
    );
}

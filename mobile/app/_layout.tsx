import { AuthProvider, useAuth } from '@/src/context/AuthContext';
import { usePushNotifications } from '@/src/hooks/usePushNotifications';
import { useTheme } from '@/src/theme';
import {
    BricolageGrotesque_600SemiBold,
    BricolageGrotesque_700Bold,
    useFonts,
} from '@expo-google-fonts/bricolage-grotesque';
import { Slot, useRouter, useSegments } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { View } from 'react-native';

function Guard() {
    const { token, isLoading } = useAuth();
    const segments = useSegments();
    const router = useRouter();
    const theme = useTheme();

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

    return (
        <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
            <StatusBar style={theme.isDark ? 'light' : 'dark'} />
            <Slot />
        </View>
    );
}

export default function RootLayout() {
    const [fontsLoaded, fontError] = useFonts({
        BricolageGrotesque_600SemiBold,
        BricolageGrotesque_700Bold,
    });

    // Hold the first frame until the display face is ready (or has failed), so
    // headings never flash in a fallback font. On failure we render anyway.
    if (!fontsLoaded && !fontError) return null;

    return (
        <AuthProvider>
            <Guard />
        </AuthProvider>
    );
}

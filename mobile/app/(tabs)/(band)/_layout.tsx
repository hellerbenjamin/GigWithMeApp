import { useTheme } from '@/src/theme';
import { Stack } from 'expo-router';

export default function BandLayout() {
    const theme = useTheme();

    return (
        <Stack
            screenOptions={{
                headerShown: false,
                contentStyle: { backgroundColor: theme.colors.background },
            }}
        />
    );
}

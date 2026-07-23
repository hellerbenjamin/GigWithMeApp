import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/theme';
import { Stack } from 'expo-router';
import { useState } from 'react';
import { Text, TouchableOpacity, View } from 'react-native';

export default function AdminLayout() {
    const { bands } = useAuth();
    const theme = useTheme();
    const adminBands = bands.filter((b) => b.role === 'owner' || b.role === 'admin');

    const [activeBandId, setActiveBandId] = useState<number>(adminBands[0]?.id ?? 0);
    const activeBand = adminBands.find((b) => b.id === activeBandId);

    return (
        <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
            {/* Band switcher header */}
            {adminBands.length > 1 && (
                <View style={{ flexDirection: 'row', paddingHorizontal: 16, paddingTop: 56, paddingBottom: 8, gap: 8 }}>
                    {adminBands.map((band) => {
                        const active = band.id === activeBandId;
                        return (
                            <TouchableOpacity
                                key={band.id}
                                onPress={() => setActiveBandId(band.id)}
                                style={{
                                    paddingHorizontal: 12,
                                    paddingVertical: 6,
                                    borderRadius: theme.radius.pill,
                                    backgroundColor: active ? theme.colors.primary : theme.colors.surface,
                                }}
                            >
                                <Text style={{ color: active ? theme.colors.onPrimary : theme.colors.textMuted, fontSize: 13, fontWeight: '600' }}>
                                    {band.name}
                                </Text>
                            </TouchableOpacity>
                        );
                    })}
                </View>
            )}

            <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: theme.colors.background } }} />
        </View>
    );
}

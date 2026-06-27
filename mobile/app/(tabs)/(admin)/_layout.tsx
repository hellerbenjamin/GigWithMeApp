import { useAuth } from '@/src/context/AuthContext';
import { Stack } from 'expo-router';
import { useState } from 'react';
import { Text, TouchableOpacity, View } from 'react-native';

export default function AdminLayout() {
    const { bands } = useAuth();
    const adminBands = bands.filter((b) => b.role === 'owner' || b.role === 'admin');

    const [activeBandId, setActiveBandId] = useState<number>(adminBands[0]?.id ?? 0);
    const activeBand = adminBands.find((b) => b.id === activeBandId);

    return (
        <View style={{ flex: 1 }}>
            {/* Band switcher header */}
            {adminBands.length > 1 && (
                <View style={{ flexDirection: 'row', paddingHorizontal: 16, paddingTop: 56, paddingBottom: 8, gap: 8 }}>
                    {adminBands.map((band) => (
                        <TouchableOpacity
                            key={band.id}
                            onPress={() => setActiveBandId(band.id)}
                            style={{
                                paddingHorizontal: 12,
                                paddingVertical: 6,
                                borderRadius: 999,
                                backgroundColor: band.id === activeBandId ? '#7C3AED' : '#F3F4F6',
                            }}
                        >
                            <Text style={{ color: band.id === activeBandId ? '#fff' : '#374151', fontSize: 13, fontWeight: '600' }}>
                                {band.name}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>
            )}

            <Stack screenOptions={{ headerShown: false }} />
        </View>
    );
}

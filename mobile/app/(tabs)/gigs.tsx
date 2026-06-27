import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import type { GigSummary } from '@/src/types/gig';
import { useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    FlatList,
    RefreshControl,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';

function formatDate(iso: string): string {
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

const STATUS_COLORS: Record<string, string> = {
    confirmed: '#16A34A',
    pending: '#D97706',
    cancelled: '#DC2626',
};

export default function GigsScreen() {
    const { token } = useAuth();
    const router = useRouter();
    const [gigs, setGigs] = useState<GigSummary[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        try {
            const res = await apiFetch('/gigs', { token: token! });
            if (!res.ok) throw new Error('Failed to load gigs');
            const json = await res.json();
            setGigs(json.data);
            setError(null);
        } catch {
            setError('Could not load gigs. Pull to retry.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [token]);

    useEffect(() => { load(); }, [load]);

    if (loading) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <ActivityIndicator />
            </View>
        );
    }

    return (
        <FlatList
            data={gigs}
            keyExtractor={(g) => String(g.id)}
            contentContainerStyle={{ paddingTop: 60, paddingBottom: 32 }}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} />}
            ListHeaderComponent={
                <Text style={{ fontSize: 28, fontWeight: '700', paddingHorizontal: 20, marginBottom: 16 }}>
                    Gigs
                </Text>
            }
            ListEmptyComponent={
                <View style={{ alignItems: 'center', paddingTop: 60 }}>
                    <Text style={{ color: '#6B7280' }}>{error ?? 'No upcoming gigs.'}</Text>
                </View>
            }
            renderItem={({ item }) => (
                <TouchableOpacity
                    onPress={() => router.push(`/gigs/${item.id}`)}
                    style={{
                        marginHorizontal: 16,
                        marginBottom: 10,
                        backgroundColor: '#fff',
                        borderRadius: 12,
                        padding: 16,
                        shadowColor: '#000',
                        shadowOpacity: 0.05,
                        shadowRadius: 4,
                        shadowOffset: { width: 0, height: 1 },
                        elevation: 1,
                    }}
                >
                    <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
                        <Text style={{ flex: 1, fontSize: 16, fontWeight: '600' }}>
                            {item.name ?? item.band.name}
                        </Text>
                        <View style={{
                            backgroundColor: STATUS_COLORS[item.status] + '20',
                            borderRadius: 999,
                            paddingHorizontal: 8,
                            paddingVertical: 2,
                        }}>
                            <Text style={{ fontSize: 11, fontWeight: '600', color: STATUS_COLORS[item.status] }}>
                                {item.status}
                            </Text>
                        </View>
                    </View>
                    <Text style={{ color: '#6B7280', fontSize: 13 }}>
                        {item.band.name}{item.venue_name ? ` · ${item.venue_name}` : ''}
                    </Text>
                    <Text style={{ color: '#9CA3AF', fontSize: 12, marginTop: 4 }}>
                        {formatDate(item.date)}{item.start_time ? ` · ${item.start_time}` : ''}
                    </Text>
                </TouchableOpacity>
            )}
        />
    );
}

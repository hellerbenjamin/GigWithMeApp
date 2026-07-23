import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import { statusColors, useTheme } from '@/src/theme';
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

export default function GigsScreen() {
    const { token } = useAuth();
    const theme = useTheme();
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
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background }}>
                <ActivityIndicator color={theme.colors.primary} />
            </View>
        );
    }

    return (
        <FlatList
            data={gigs}
            keyExtractor={(g) => String(g.id)}
            style={{ backgroundColor: theme.colors.background }}
            contentContainerStyle={{ paddingTop: 60, paddingBottom: 32 }}
            refreshControl={
                <RefreshControl
                    refreshing={refreshing}
                    onRefresh={() => { setRefreshing(true); load(); }}
                    tintColor={theme.colors.textMuted}
                    colors={[theme.colors.primary]}
                />
            }
            ListHeaderComponent={
                <Text style={{ fontSize: 28, fontFamily: theme.fonts.display, color: theme.colors.text, paddingHorizontal: 20, marginBottom: 16 }}>
                    Gigs
                </Text>
            }
            ListEmptyComponent={
                <View style={{ alignItems: 'center', paddingTop: 60 }}>
                    <Text style={{ color: theme.colors.textMuted }}>{error ?? 'No upcoming gigs.'}</Text>
                </View>
            }
            renderItem={({ item }) => {
                const status = statusColors(theme, item.status);
                return (
                    <TouchableOpacity
                        onPress={() => router.push(`/gigs/${item.id}`)}
                        style={{
                            marginHorizontal: 16,
                            marginBottom: 10,
                            backgroundColor: theme.colors.card,
                            borderRadius: theme.radius.lg,
                            borderWidth: theme.isDark ? 1 : 0,
                            borderColor: theme.colors.border,
                            padding: 16,
                            shadowColor: '#000',
                            shadowOpacity: theme.isDark ? 0 : 0.05,
                            shadowRadius: 4,
                            shadowOffset: { width: 0, height: 1 },
                            elevation: theme.isDark ? 0 : 1,
                        }}
                    >
                        <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
                            <Text style={{ flex: 1, fontSize: 16, fontWeight: '600', color: theme.colors.text }}>
                                {item.name ?? item.band.name}
                            </Text>
                            <View style={{
                                backgroundColor: status.bg,
                                borderRadius: theme.radius.pill,
                                paddingHorizontal: 8,
                                paddingVertical: 2,
                            }}>
                                <Text style={{ fontSize: 11, fontWeight: '600', color: status.fg }}>
                                    {item.status}
                                </Text>
                            </View>
                        </View>
                        <Text style={{ color: theme.colors.textMuted, fontSize: 13 }}>
                            {item.band.name}{item.venue_name ? ` · ${item.venue_name}` : ''}
                        </Text>
                        <Text style={{ color: theme.colors.textSubtle, fontSize: 12, marginTop: 4 }}>
                            {formatDate(item.date)}{item.start_time ? ` · ${item.start_time}` : ''}
                        </Text>
                    </TouchableOpacity>
                );
            }}
        />
    );
}

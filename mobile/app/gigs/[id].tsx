import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import { statusColors, type Theme, useTheme } from '@/src/theme';
import type { GigDetail, RsvpStatus } from '@/src/types/gig';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    ScrollView,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';

function TimeRow({ label, time, theme }: { label: string; time: string | null; theme: Theme }) {
    if (!time) return null;
    return (
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 }}>
            <Text style={{ color: theme.colors.textMuted }}>{label}</Text>
            <Text style={{ fontWeight: '500', color: theme.colors.text }}>{time}</Text>
        </View>
    );
}

const RSVP_LABELS: Record<RsvpStatus, string> = {
    pending: 'Not yet responded',
    available: 'Available',
    unavailable: "Can't make it",
};

export default function GigDetailScreen() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const { token } = useAuth();
    const theme = useTheme();
    const router = useRouter();
    const [gig, setGig] = useState<GigDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    const load = useCallback(async () => {
        const res = await apiFetch(`/gigs/${id}`, { token: token! });
        if (!res.ok) { router.back(); return; }
        const json = await res.json();
        setGig(json.data);
        setLoading(false);
    }, [id, token]);

    useEffect(() => { load(); }, [load]);

    async function submitRsvp(available: boolean) {
        if (!gig) return;
        setSubmitting(true);
        try {
            const res = await apiFetch(`/gigs/${gig.id}/rsvp`, {
                method: 'POST',
                token: token!,
                body: JSON.stringify({ available }),
            });
            if (!res.ok) throw new Error();
            const json = await res.json();
            setGig((g) => g ? { ...g, rsvp: json.data } : g);
        } catch {
            Alert.alert('Error', 'Could not save your response. Please try again.');
        } finally {
            setSubmitting(false);
        }
    }

    if (loading || !gig) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background }}>
                <ActivityIndicator color={theme.colors.primary} />
            </View>
        );
    }

    const isPollOpen = gig.rsvp?.open === true;
    const rsvp = statusColors(theme, gig.rsvp?.status ?? 'pending');
    const available = statusColors(theme, 'available');
    const unavailable = statusColors(theme, 'unavailable');
    const panel = {
        backgroundColor: theme.colors.surface,
        borderRadius: theme.radius.lg,
        padding: 16,
        marginBottom: 16,
    };

    return (
        <ScrollView
            style={{ backgroundColor: theme.colors.background }}
            contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}
        >
            {/* Header */}
            <TouchableOpacity onPress={() => router.back()} style={{ marginBottom: 16 }}>
                <Text style={{ color: theme.colors.primaryText }}>← Back</Text>
            </TouchableOpacity>

            <Text style={{ fontSize: 24, fontFamily: theme.fonts.display, color: theme.colors.text, marginBottom: 4 }}>
                {gig.name ?? gig.band.name}
            </Text>
            <Text style={{ color: theme.colors.textMuted, marginBottom: 24 }}>
                {gig.band.name}{gig.venue ? ` · ${gig.venue.name}` : ''}
            </Text>

            {/* Times */}
            <View style={panel}>
                <TimeRow theme={theme} label="Date" time={new Date(gig.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })} />
                <TimeRow theme={theme} label="Load in" time={gig.load_in_time} />
                <TimeRow theme={theme} label="Soundcheck" time={gig.soundcheck_time} />
                <TimeRow theme={theme} label="Doors" time={gig.doors_time} />
                <TimeRow theme={theme} label="Start" time={gig.start_time} />
                <TimeRow theme={theme} label="End" time={gig.end_time} />
            </View>

            {/* Notes */}
            {gig.notes && (
                <View style={panel}>
                    <Text style={{ color: theme.colors.text }}>{gig.notes}</Text>
                </View>
            )}

            {/* RSVP */}
            {gig.rsvp && (
                <View style={{ ...panel, marginBottom: 0 }}>
                    <Text style={{ fontWeight: '600', color: theme.colors.text, marginBottom: 12 }}>Your RSVP</Text>

                    <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: isPollOpen ? 16 : 0 }}>
                        <View style={{
                            width: 8, height: 8, borderRadius: theme.radius.pill,
                            backgroundColor: rsvp.fg,
                            marginRight: 8,
                        }} />
                        <Text style={{ color: rsvp.fg, fontWeight: '500' }}>
                            {RSVP_LABELS[gig.rsvp.status]}
                        </Text>
                    </View>

                    {isPollOpen && (
                        <View style={{ flexDirection: 'row', gap: 10 }}>
                            <TouchableOpacity
                                onPress={() => submitRsvp(true)}
                                disabled={submitting || gig.rsvp?.status === 'available'}
                                style={{
                                    flex: 1, paddingVertical: 10, borderRadius: theme.radius.md, alignItems: 'center',
                                    backgroundColor: gig.rsvp?.status === 'available' ? available.solid : available.bg,
                                    borderWidth: 1, borderColor: available.solid,
                                    opacity: submitting ? 0.5 : 1,
                                }}
                            >
                                <Text style={{ fontWeight: '600', color: gig.rsvp?.status === 'available' ? theme.colors.onPrimary : available.fg }}>
                                    Available
                                </Text>
                            </TouchableOpacity>

                            <TouchableOpacity
                                onPress={() => submitRsvp(false)}
                                disabled={submitting || gig.rsvp?.status === 'unavailable'}
                                style={{
                                    flex: 1, paddingVertical: 10, borderRadius: theme.radius.md, alignItems: 'center',
                                    backgroundColor: gig.rsvp?.status === 'unavailable' ? unavailable.solid : unavailable.bg,
                                    borderWidth: 1, borderColor: unavailable.solid,
                                    opacity: submitting ? 0.5 : 1,
                                }}
                            >
                                <Text style={{ fontWeight: '600', color: gig.rsvp?.status === 'unavailable' ? theme.colors.onPrimary : unavailable.fg }}>
                                    Can't make it
                                </Text>
                            </TouchableOpacity>
                        </View>
                    )}
                </View>
            )}
        </ScrollView>
    );
}

import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
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

function TimeRow({ label, time }: { label: string; time: string | null }) {
    if (!time) return null;
    return (
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 }}>
            <Text style={{ color: '#6B7280' }}>{label}</Text>
            <Text style={{ fontWeight: '500' }}>{time}</Text>
        </View>
    );
}

const RSVP_LABELS: Record<RsvpStatus, string> = {
    pending: 'Not yet responded',
    available: 'Available',
    unavailable: "Can't make it",
};

const RSVP_COLORS: Record<RsvpStatus, string> = {
    pending: '#D97706',
    available: '#16A34A',
    unavailable: '#DC2626',
};

export default function GigDetailScreen() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const { token } = useAuth();
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
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <ActivityIndicator />
            </View>
        );
    }

    const isPollOpen = gig.rsvp?.open === true;

    return (
        <ScrollView contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}>
            {/* Header */}
            <TouchableOpacity onPress={() => router.back()} style={{ marginBottom: 16 }}>
                <Text style={{ color: '#7C3AED' }}>← Back</Text>
            </TouchableOpacity>

            <Text style={{ fontSize: 24, fontWeight: '700', marginBottom: 4 }}>
                {gig.name ?? gig.band.name}
            </Text>
            <Text style={{ color: '#6B7280', marginBottom: 24 }}>
                {gig.band.name}{gig.venue ? ` · ${gig.venue.name}` : ''}
            </Text>

            {/* Times */}
            <View style={{ backgroundColor: '#F9FAFB', borderRadius: 12, padding: 16, marginBottom: 16 }}>
                <TimeRow label="Date" time={new Date(gig.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })} />
                <TimeRow label="Load in" time={gig.load_in_time} />
                <TimeRow label="Soundcheck" time={gig.soundcheck_time} />
                <TimeRow label="Doors" time={gig.doors_time} />
                <TimeRow label="Start" time={gig.start_time} />
                <TimeRow label="End" time={gig.end_time} />
            </View>

            {/* Notes */}
            {gig.notes && (
                <View style={{ backgroundColor: '#F9FAFB', borderRadius: 12, padding: 16, marginBottom: 16 }}>
                    <Text style={{ color: '#374151' }}>{gig.notes}</Text>
                </View>
            )}

            {/* RSVP */}
            {gig.rsvp && (
                <View style={{ backgroundColor: '#F9FAFB', borderRadius: 12, padding: 16 }}>
                    <Text style={{ fontWeight: '600', marginBottom: 12 }}>Your RSVP</Text>

                    <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: isPollOpen ? 16 : 0 }}>
                        <View style={{
                            width: 8, height: 8, borderRadius: 4,
                            backgroundColor: RSVP_COLORS[gig.rsvp.status],
                            marginRight: 8,
                        }} />
                        <Text style={{ color: RSVP_COLORS[gig.rsvp.status], fontWeight: '500' }}>
                            {RSVP_LABELS[gig.rsvp.status]}
                        </Text>
                    </View>

                    {isPollOpen && (
                        <View style={{ flexDirection: 'row', gap: 10 }}>
                            <TouchableOpacity
                                onPress={() => submitRsvp(true)}
                                disabled={submitting || gig.rsvp?.status === 'available'}
                                style={{
                                    flex: 1, paddingVertical: 10, borderRadius: 8, alignItems: 'center',
                                    backgroundColor: gig.rsvp?.status === 'available' ? '#16A34A' : '#F0FDF4',
                                    borderWidth: 1, borderColor: '#16A34A',
                                    opacity: submitting ? 0.5 : 1,
                                }}
                            >
                                <Text style={{ fontWeight: '600', color: gig.rsvp?.status === 'available' ? '#fff' : '#16A34A' }}>
                                    Available
                                </Text>
                            </TouchableOpacity>

                            <TouchableOpacity
                                onPress={() => submitRsvp(false)}
                                disabled={submitting || gig.rsvp?.status === 'unavailable'}
                                style={{
                                    flex: 1, paddingVertical: 10, borderRadius: 8, alignItems: 'center',
                                    backgroundColor: gig.rsvp?.status === 'unavailable' ? '#DC2626' : '#FEF2F2',
                                    borderWidth: 1, borderColor: '#DC2626',
                                    opacity: submitting ? 0.5 : 1,
                                }}
                            >
                                <Text style={{ fontWeight: '600', color: gig.rsvp?.status === 'unavailable' ? '#fff' : '#DC2626' }}>
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

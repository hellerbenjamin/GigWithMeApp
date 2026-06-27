import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    ScrollView,
    Switch,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';

interface Preferences {
    channels: string[];
    days: number[];
    available_days: number[];
}

function dayLabel(d: number): string {
    if (d === 0) return 'Day of the gig';
    if (d === 1) return '1 day before';
    return `${d} days before`;
}

export default function NotificationsScreen() {
    const { token } = useAuth();
    const [prefs, setPrefs] = useState<Preferences | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        const res = await apiFetch('/notifications/preferences', { token: token! });
        if (!res.ok) return;
        setPrefs((await res.json()).data);
        setLoading(false);
    }, [token]);

    useEffect(() => { load(); }, [load]);

    function toggleChannel(ch: string) {
        setPrefs((p) => {
            if (!p) return p;
            const has = p.channels.includes(ch);
            return { ...p, channels: has ? p.channels.filter((c) => c !== ch) : [...p.channels, ch] };
        });
    }

    function toggleDay(d: number) {
        setPrefs((p) => {
            if (!p) return p;
            const has = p.days.includes(d);
            return { ...p, days: has ? p.days.filter((x) => x !== d) : [...p.days, d] };
        });
    }

    async function save() {
        if (!prefs) return;
        setSaving(true);
        try {
            const res = await apiFetch('/notifications/preferences', {
                method: 'PUT',
                token: token!,
                body: JSON.stringify({ channels: prefs.channels, days: prefs.days }),
            });
            if (!res.ok) {
                Alert.alert('Error', 'Could not save preferences. Please try again.');
                return;
            }
            Alert.alert('Saved', 'Notification preferences updated.');
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <ActivityIndicator />
            </View>
        );
    }

    const CHANNELS: { key: string; label: string; description: string }[] = [
        { key: 'mobile', label: 'Push notifications', description: 'Alerts sent to this device' },
        { key: 'email', label: 'Email', description: 'Reminders sent to your inbox' },
    ];

    return (
        <ScrollView contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}>
            <Text style={{ fontSize: 28, fontWeight: '700', marginBottom: 8 }}>Notifications</Text>
            <Text style={{ fontSize: 15, color: '#6B7280', marginBottom: 32 }}>
                Choose how and when GigWithMe reminds you about upcoming gigs.
            </Text>

            {/* Channels */}
            <Text style={{ fontSize: 13, fontWeight: '600', color: '#6B7280', marginBottom: 12, textTransform: 'uppercase', letterSpacing: 0.5 }}>
                How to notify me
            </Text>
            <View style={{ backgroundColor: '#fff', borderRadius: 12, borderWidth: 1, borderColor: '#E5E7EB', overflow: 'hidden', marginBottom: 28 }}>
                {CHANNELS.map((ch, i) => (
                    <View
                        key={ch.key}
                        style={{
                            flexDirection: 'row',
                            alignItems: 'center',
                            paddingHorizontal: 16,
                            paddingVertical: 14,
                            borderTopWidth: i > 0 ? 1 : 0,
                            borderTopColor: '#F3F4F6',
                        }}
                    >
                        <View style={{ flex: 1 }}>
                            <Text style={{ fontSize: 16, fontWeight: '500' }}>{ch.label}</Text>
                            <Text style={{ fontSize: 13, color: '#9CA3AF', marginTop: 2 }}>{ch.description}</Text>
                        </View>
                        <Switch
                            value={prefs!.channels.includes(ch.key)}
                            onValueChange={() => toggleChannel(ch.key)}
                            trackColor={{ true: '#7C3AED' }}
                        />
                    </View>
                ))}
            </View>

            {/* Timing */}
            <Text style={{ fontSize: 13, fontWeight: '600', color: '#6B7280', marginBottom: 12, textTransform: 'uppercase', letterSpacing: 0.5 }}>
                When to notify me
            </Text>
            <View style={{ backgroundColor: '#fff', borderRadius: 12, borderWidth: 1, borderColor: '#E5E7EB', overflow: 'hidden', marginBottom: 36 }}>
                {prefs!.available_days.map((d, i) => (
                    <TouchableOpacity
                        key={d}
                        onPress={() => toggleDay(d)}
                        style={{
                            flexDirection: 'row',
                            alignItems: 'center',
                            paddingHorizontal: 16,
                            paddingVertical: 14,
                            borderTopWidth: i > 0 ? 1 : 0,
                            borderTopColor: '#F3F4F6',
                        }}
                    >
                        <Text style={{ flex: 1, fontSize: 16 }}>{dayLabel(d)}</Text>
                        <View style={{
                            width: 22,
                            height: 22,
                            borderRadius: 11,
                            borderWidth: 2,
                            borderColor: prefs!.days.includes(d) ? '#7C3AED' : '#D1D5DB',
                            backgroundColor: prefs!.days.includes(d) ? '#7C3AED' : 'transparent',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}>
                            {prefs!.days.includes(d) && (
                                <Text style={{ color: '#fff', fontSize: 13, fontWeight: '700' }}>✓</Text>
                            )}
                        </View>
                    </TouchableOpacity>
                ))}
            </View>

            <TouchableOpacity
                onPress={save}
                disabled={saving}
                style={{
                    backgroundColor: '#7C3AED',
                    borderRadius: 10,
                    paddingVertical: 14,
                    alignItems: 'center',
                    opacity: saving ? 0.5 : 1,
                }}
            >
                <Text style={{ color: '#fff', fontWeight: '600', fontSize: 16 }}>
                    {saving ? 'Saving…' : 'Save preferences'}
                </Text>
            </TouchableOpacity>
        </ScrollView>
    );
}

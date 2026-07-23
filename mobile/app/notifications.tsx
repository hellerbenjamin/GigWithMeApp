import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import { type Theme, useTheme } from '@/src/theme';
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

function sectionLabel(theme: Theme) {
    return {
        fontSize: 13,
        fontWeight: '600' as const,
        color: theme.colors.textMuted,
        marginBottom: 12,
        textTransform: 'uppercase' as const,
        letterSpacing: 0.5,
    };
}

export default function NotificationsScreen() {
    const { token } = useAuth();
    const theme = useTheme();
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
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background }}>
                <ActivityIndicator color={theme.colors.primary} />
            </View>
        );
    }

    const CHANNELS: { key: string; label: string; description: string }[] = [
        { key: 'mobile', label: 'Push notifications', description: 'Alerts sent to this device' },
        { key: 'email', label: 'Email', description: 'Reminders sent to your inbox' },
    ];

    const groupCard = {
        backgroundColor: theme.colors.card,
        borderRadius: theme.radius.lg,
        borderWidth: 1,
        borderColor: theme.colors.border,
        overflow: 'hidden' as const,
    };

    return (
        <ScrollView
            style={{ backgroundColor: theme.colors.background }}
            contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}
        >
            <Text style={{ fontSize: 28, fontFamily: theme.fonts.display, color: theme.colors.text, marginBottom: 8 }}>Notifications</Text>
            <Text style={{ fontSize: 15, color: theme.colors.textMuted, marginBottom: 32 }}>
                Choose how and when GigWithMe reminds you about upcoming gigs.
            </Text>

            {/* Channels */}
            <Text style={sectionLabel(theme)}>How to notify me</Text>
            <View style={{ ...groupCard, marginBottom: 28 }}>
                {CHANNELS.map((ch, i) => (
                    <View
                        key={ch.key}
                        style={{
                            flexDirection: 'row',
                            alignItems: 'center',
                            paddingHorizontal: 16,
                            paddingVertical: 14,
                            borderTopWidth: i > 0 ? 1 : 0,
                            borderTopColor: theme.colors.divider,
                        }}
                    >
                        <View style={{ flex: 1 }}>
                            <Text style={{ fontSize: 16, fontWeight: '500', color: theme.colors.text }}>{ch.label}</Text>
                            <Text style={{ fontSize: 13, color: theme.colors.textSubtle, marginTop: 2 }}>{ch.description}</Text>
                        </View>
                        <Switch
                            value={prefs!.channels.includes(ch.key)}
                            onValueChange={() => toggleChannel(ch.key)}
                            trackColor={{ true: theme.colors.primary }}
                        />
                    </View>
                ))}
            </View>

            {/* Timing */}
            <Text style={sectionLabel(theme)}>When to notify me</Text>
            <View style={{ ...groupCard, marginBottom: 36 }}>
                {prefs!.available_days.map((d, i) => {
                    const on = prefs!.days.includes(d);
                    return (
                        <TouchableOpacity
                            key={d}
                            onPress={() => toggleDay(d)}
                            style={{
                                flexDirection: 'row',
                                alignItems: 'center',
                                paddingHorizontal: 16,
                                paddingVertical: 14,
                                borderTopWidth: i > 0 ? 1 : 0,
                                borderTopColor: theme.colors.divider,
                            }}
                        >
                            <Text style={{ flex: 1, fontSize: 16, color: theme.colors.text }}>{dayLabel(d)}</Text>
                            <View style={{
                                width: 22,
                                height: 22,
                                borderRadius: theme.radius.pill,
                                borderWidth: 2,
                                borderColor: on ? theme.colors.primary : theme.colors.borderStrong,
                                backgroundColor: on ? theme.colors.primary : 'transparent',
                                alignItems: 'center',
                                justifyContent: 'center',
                            }}>
                                {on && (
                                    <Text style={{ color: theme.colors.onPrimary, fontSize: 13, fontWeight: '700' }}>✓</Text>
                                )}
                            </View>
                        </TouchableOpacity>
                    );
                })}
            </View>

            <TouchableOpacity
                onPress={save}
                disabled={saving}
                style={{
                    backgroundColor: theme.colors.primary,
                    borderRadius: theme.radius.md,
                    paddingVertical: 14,
                    alignItems: 'center',
                    opacity: saving ? 0.5 : 1,
                }}
            >
                <Text style={{ color: theme.colors.onPrimary, fontWeight: '600', fontSize: 16 }}>
                    {saving ? 'Saving…' : 'Save preferences'}
                </Text>
            </TouchableOpacity>
        </ScrollView>
    );
}

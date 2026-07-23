import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
import { type Theme, useTheme } from '@/src/theme';
import type { Profile } from '@/src/types/profile';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    FlatList,
    Image,
    Modal,
    ScrollView,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';

// A representative list of IANA timezone identifiers.
const TIMEZONES = [
    'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'America/Anchorage', 'America/Honolulu', 'America/Phoenix', 'America/Detroit',
    'America/Indiana/Indianapolis', 'America/Kentucky/Louisville',
    'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Rome', 'Europe/Madrid',
    'Europe/Amsterdam', 'Europe/Brussels', 'Europe/Vienna', 'Europe/Warsaw',
    'Europe/Stockholm', 'Europe/Oslo', 'Europe/Helsinki', 'Europe/Lisbon',
    'Europe/Athens', 'Europe/Bucharest', 'Europe/Istanbul', 'Europe/Moscow',
    'Asia/Dubai', 'Asia/Kolkata', 'Asia/Dhaka', 'Asia/Bangkok', 'Asia/Singapore',
    'Asia/Shanghai', 'Asia/Tokyo', 'Asia/Seoul', 'Asia/Hong_Kong',
    'Australia/Sydney', 'Australia/Melbourne', 'Australia/Brisbane', 'Australia/Perth',
    'Pacific/Auckland', 'Pacific/Fiji',
    'Africa/Johannesburg', 'Africa/Cairo', 'Africa/Lagos', 'Africa/Nairobi',
    'America/Toronto', 'America/Vancouver', 'America/Sao_Paulo', 'America/Argentina/Buenos_Aires',
    'America/Bogota', 'America/Lima', 'America/Santiago', 'America/Mexico_City',
].sort();

function inputStyle(focused: boolean, theme: Theme) {
    return {
        borderWidth: 1,
        borderColor: focused ? theme.colors.primary : theme.colors.borderStrong,
        borderRadius: theme.radius.md,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 16,
        color: theme.colors.text,
        backgroundColor: theme.colors.card,
    };
}

function label(theme: Theme) {
    return {
        fontSize: 13,
        fontWeight: '600' as const,
        color: theme.colors.textMuted,
        marginBottom: 4,
        textTransform: 'uppercase' as const,
        letterSpacing: 0.5,
    };
}

export default function ProfileScreen() {
    const { token, user: authUser, signOut } = useAuth();
    const theme = useTheme();
    const router = useRouter();
    const [profile, setProfile] = useState<Profile | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploadingAvatar, setUploadingAvatar] = useState(false);

    const [name, setName] = useState('');
    const [timezone, setTimezone] = useState<string | null>(null);
    const [tzSearch, setTzSearch] = useState('');
    const [tzModalOpen, setTzModalOpen] = useState(false);
    const [nameFocused, setNameFocused] = useState(false);

    const filteredTz = TIMEZONES.filter((tz) =>
        tz.toLowerCase().includes(tzSearch.toLowerCase()),
    );

    const load = useCallback(async () => {
        const res = await apiFetch('/profile', { token: token! });
        if (!res.ok) return;
        const json = await res.json();
        setProfile(json.data);
        setName(json.data.name);
        setTimezone(json.data.timezone);
        setLoading(false);
    }, [token]);

    useEffect(() => { load(); }, [load]);

    async function save() {
        setSaving(true);
        try {
            const res = await apiFetch('/profile', {
                method: 'PUT',
                token: token!,
                body: JSON.stringify({ name, timezone }),
            });
            if (!res.ok) {
                Alert.alert('Error', 'Could not save profile. Please try again.');
                return;
            }
            const json = await res.json();
            setProfile(json.data);
            Alert.alert('Saved', 'Your profile has been updated.');
        } finally {
            setSaving(false);
        }
    }

    async function pickAvatar() {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
        });

        if (result.canceled || !result.assets[0]) return;

        const asset = result.assets[0];
        const filename = asset.uri.split('/').pop() ?? 'avatar.jpg';
        const ext = filename.split('.').pop()?.toLowerCase() ?? 'jpg';
        const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';

        const form = new FormData();
        form.append('avatar', { uri: asset.uri, name: filename, type: mimeType } as any);

        setUploadingAvatar(true);
        try {
            const res = await apiFetch('/profile/avatar', {
                method: 'POST',
                token: token!,
                headers: { 'Content-Type': 'multipart/form-data' },
                body: form,
            });
            if (!res.ok) {
                Alert.alert('Error', 'Could not upload photo. Please try again.');
                return;
            }
            const json = await res.json();
            setProfile(json.data);
        } finally {
            setUploadingAvatar(false);
        }
    }

    if (loading) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background }}>
                <ActivityIndicator color={theme.colors.primary} />
            </View>
        );
    }

    const isDirty = name !== profile?.name || timezone !== profile?.timezone;

    return (
        <>
            <ScrollView
                style={{ backgroundColor: theme.colors.background }}
                contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}
            >
                <Text style={{ fontSize: 28, fontFamily: theme.fonts.display, color: theme.colors.text, marginBottom: 24 }}>Profile</Text>

                {/* Avatar */}
                <TouchableOpacity onPress={pickAvatar} disabled={uploadingAvatar} style={{ alignSelf: 'center', marginBottom: 32 }}>
                    <View style={{ width: 96, height: 96, borderRadius: 48, backgroundColor: theme.colors.surface, overflow: 'hidden', alignItems: 'center', justifyContent: 'center' }}>
                        {uploadingAvatar ? (
                            <ActivityIndicator color={theme.colors.primary} />
                        ) : profile?.avatar_url ? (
                            <Image source={{ uri: profile.avatar_url }} style={{ width: 96, height: 96 }} />
                        ) : (
                            <Text style={{ fontSize: 36, color: theme.colors.textMuted }}>
                                {profile?.name?.charAt(0).toUpperCase() ?? '?'}
                            </Text>
                        )}
                    </View>
                    <Text style={{ textAlign: 'center', color: theme.colors.primaryText, fontSize: 13, marginTop: 8 }}>
                        Change photo
                    </Text>
                </TouchableOpacity>

                {/* Email (read-only) */}
                <Text style={label(theme)}>Email</Text>
                <View style={{ ...inputStyle(false, theme), backgroundColor: theme.colors.surfaceAlt, marginBottom: 16 }}>
                    <Text style={{ fontSize: 16, color: theme.colors.textSubtle }}>{profile?.email}</Text>
                </View>

                {/* Name */}
                <Text style={label(theme)}>Name</Text>
                <TextInput
                    value={name}
                    onChangeText={setName}
                    onFocus={() => setNameFocused(true)}
                    onBlur={() => setNameFocused(false)}
                    style={{ ...inputStyle(nameFocused, theme), marginBottom: 16 }}
                    placeholderTextColor={theme.colors.textSubtle}
                    autoCapitalize="words"
                />

                {/* Timezone */}
                <Text style={label(theme)}>Timezone</Text>
                <TouchableOpacity onPress={() => { setTzSearch(''); setTzModalOpen(true); }} style={{ ...inputStyle(false, theme), flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 32 }}>
                    <Text style={{ fontSize: 16, color: timezone ? theme.colors.text : theme.colors.textSubtle }}>
                        {timezone ?? 'Select timezone'}
                    </Text>
                    <Text style={{ color: theme.colors.textSubtle }}>›</Text>
                </TouchableOpacity>

                {/* Save */}
                <TouchableOpacity
                    onPress={save}
                    disabled={saving || !isDirty}
                    style={{
                        backgroundColor: theme.colors.primary,
                        borderRadius: theme.radius.md,
                        paddingVertical: 14,
                        alignItems: 'center',
                        opacity: saving || !isDirty ? 0.4 : 1,
                        marginBottom: 32,
                    }}
                >
                    <Text style={{ color: theme.colors.onPrimary, fontWeight: '600', fontSize: 16 }}>
                        {saving ? 'Saving…' : 'Save changes'}
                    </Text>
                </TouchableOpacity>

                {/* Notifications link */}
                <TouchableOpacity
                    onPress={() => router.push('/notifications')}
                    style={{ flexDirection: 'row', alignItems: 'center', paddingVertical: 14, borderTopWidth: 1, borderTopColor: theme.colors.divider, marginBottom: 8 }}
                >
                    <Text style={{ flex: 1, fontSize: 16, color: theme.colors.text }}>Notification preferences</Text>
                    <Text style={{ color: theme.colors.textSubtle }}>›</Text>
                </TouchableOpacity>

                {/* Sign out */}
                <TouchableOpacity onPress={signOut} style={{ alignItems: 'center', padding: 12 }}>
                    <Text style={{ color: theme.colors.danger }}>Sign out</Text>
                </TouchableOpacity>
            </ScrollView>

            {/* Timezone picker modal */}
            <Modal visible={tzModalOpen} animationType="slide" presentationStyle="pageSheet">
                <View style={{ flex: 1, paddingTop: 20, backgroundColor: theme.colors.background }}>
                    <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, marginBottom: 12 }}>
                        <Text style={{ flex: 1, fontSize: 18, fontFamily: theme.fonts.display, color: theme.colors.text }}>Select timezone</Text>
                        <TouchableOpacity onPress={() => setTzModalOpen(false)}>
                            <Text style={{ color: theme.colors.primaryText, fontWeight: '600' }}>Done</Text>
                        </TouchableOpacity>
                    </View>
                    <TextInput
                        value={tzSearch}
                        onChangeText={setTzSearch}
                        placeholder="Search…"
                        placeholderTextColor={theme.colors.textSubtle}
                        style={{ marginHorizontal: 16, marginBottom: 8, borderWidth: 1, borderColor: theme.colors.borderStrong, borderRadius: theme.radius.md, paddingHorizontal: 12, paddingVertical: 9, fontSize: 15, color: theme.colors.text, backgroundColor: theme.colors.card }}
                        autoFocus
                    />
                    <FlatList
                        data={filteredTz}
                        keyExtractor={(tz) => tz}
                        keyboardShouldPersistTaps="always"
                        renderItem={({ item }) => (
                            <TouchableOpacity
                                onPress={() => { setTimezone(item); setTzModalOpen(false); }}
                                style={{ paddingHorizontal: 20, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: theme.colors.divider, flexDirection: 'row', alignItems: 'center' }}
                            >
                                <Text style={{ flex: 1, fontSize: 16, color: theme.colors.text }}>{item}</Text>
                                {item === timezone && <Text style={{ color: theme.colors.primaryText, fontWeight: '700' }}>✓</Text>}
                            </TouchableOpacity>
                        )}
                    />
                </View>
            </Modal>
        </>
    );
}

import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/lib/api';
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

function inputStyle(focused: boolean) {
    return {
        borderWidth: 1,
        borderColor: focused ? '#7C3AED' : '#D1D5DB',
        borderRadius: 10,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 16,
        backgroundColor: '#fff',
    };
}

export default function ProfileScreen() {
    const { token, user: authUser, signOut } = useAuth();
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
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <ActivityIndicator />
            </View>
        );
    }

    const isDirty = name !== profile?.name || timezone !== profile?.timezone;

    return (
        <>
            <ScrollView contentContainerStyle={{ paddingTop: 60, paddingBottom: 48, paddingHorizontal: 20 }}>
                <Text style={{ fontSize: 28, fontWeight: '700', marginBottom: 24 }}>Profile</Text>

                {/* Avatar */}
                <TouchableOpacity onPress={pickAvatar} disabled={uploadingAvatar} style={{ alignSelf: 'center', marginBottom: 32 }}>
                    <View style={{ width: 96, height: 96, borderRadius: 48, backgroundColor: '#E5E7EB', overflow: 'hidden', alignItems: 'center', justifyContent: 'center' }}>
                        {uploadingAvatar ? (
                            <ActivityIndicator color="#7C3AED" />
                        ) : profile?.avatar_url ? (
                            <Image source={{ uri: profile.avatar_url }} style={{ width: 96, height: 96 }} />
                        ) : (
                            <Text style={{ fontSize: 36 }}>
                                {profile?.name?.charAt(0).toUpperCase() ?? '?'}
                            </Text>
                        )}
                    </View>
                    <Text style={{ textAlign: 'center', color: '#7C3AED', fontSize: 13, marginTop: 8 }}>
                        Change photo
                    </Text>
                </TouchableOpacity>

                {/* Email (read-only) */}
                <Text style={{ fontSize: 13, fontWeight: '600', color: '#6B7280', marginBottom: 4, textTransform: 'uppercase', letterSpacing: 0.5 }}>Email</Text>
                <View style={{ ...inputStyle(false), backgroundColor: '#F9FAFB', marginBottom: 16 }}>
                    <Text style={{ fontSize: 16, color: '#9CA3AF' }}>{profile?.email}</Text>
                </View>

                {/* Name */}
                <Text style={{ fontSize: 13, fontWeight: '600', color: '#6B7280', marginBottom: 4, textTransform: 'uppercase', letterSpacing: 0.5 }}>Name</Text>
                <TextInput
                    value={name}
                    onChangeText={setName}
                    onFocus={() => setNameFocused(true)}
                    onBlur={() => setNameFocused(false)}
                    style={{ ...inputStyle(nameFocused), marginBottom: 16 }}
                    autoCapitalize="words"
                />

                {/* Timezone */}
                <Text style={{ fontSize: 13, fontWeight: '600', color: '#6B7280', marginBottom: 4, textTransform: 'uppercase', letterSpacing: 0.5 }}>Timezone</Text>
                <TouchableOpacity onPress={() => { setTzSearch(''); setTzModalOpen(true); }} style={{ ...inputStyle(false), flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 32 }}>
                    <Text style={{ fontSize: 16, color: timezone ? '#111827' : '#9CA3AF' }}>
                        {timezone ?? 'Select timezone'}
                    </Text>
                    <Text style={{ color: '#9CA3AF' }}>›</Text>
                </TouchableOpacity>

                {/* Save */}
                <TouchableOpacity
                    onPress={save}
                    disabled={saving || !isDirty}
                    style={{
                        backgroundColor: '#7C3AED',
                        borderRadius: 10,
                        paddingVertical: 14,
                        alignItems: 'center',
                        opacity: saving || !isDirty ? 0.4 : 1,
                        marginBottom: 32,
                    }}
                >
                    <Text style={{ color: '#fff', fontWeight: '600', fontSize: 16 }}>
                        {saving ? 'Saving…' : 'Save changes'}
                    </Text>
                </TouchableOpacity>

                {/* Notifications link */}
                <TouchableOpacity
                    onPress={() => router.push('/notifications')}
                    style={{ flexDirection: 'row', alignItems: 'center', paddingVertical: 14, borderTopWidth: 1, borderTopColor: '#F3F4F6', marginBottom: 8 }}
                >
                    <Text style={{ flex: 1, fontSize: 16 }}>Notification preferences</Text>
                    <Text style={{ color: '#9CA3AF' }}>›</Text>
                </TouchableOpacity>

                {/* Sign out */}
                <TouchableOpacity onPress={signOut} style={{ alignItems: 'center', padding: 12 }}>
                    <Text style={{ color: '#DC2626' }}>Sign out</Text>
                </TouchableOpacity>
            </ScrollView>

            {/* Timezone picker modal */}
            <Modal visible={tzModalOpen} animationType="slide" presentationStyle="pageSheet">
                <View style={{ flex: 1, paddingTop: 20 }}>
                    <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, marginBottom: 12 }}>
                        <Text style={{ flex: 1, fontSize: 18, fontWeight: '700' }}>Select timezone</Text>
                        <TouchableOpacity onPress={() => setTzModalOpen(false)}>
                            <Text style={{ color: '#7C3AED', fontWeight: '600' }}>Done</Text>
                        </TouchableOpacity>
                    </View>
                    <TextInput
                        value={tzSearch}
                        onChangeText={setTzSearch}
                        placeholder="Search…"
                        style={{ marginHorizontal: 16, marginBottom: 8, borderWidth: 1, borderColor: '#D1D5DB', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 9, fontSize: 15 }}
                        autoFocus
                    />
                    <FlatList
                        data={filteredTz}
                        keyExtractor={(tz) => tz}
                        keyboardShouldPersistTaps="always"
                        renderItem={({ item }) => (
                            <TouchableOpacity
                                onPress={() => { setTimezone(item); setTzModalOpen(false); }}
                                style={{ paddingHorizontal: 20, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#F3F4F6', flexDirection: 'row', alignItems: 'center' }}
                            >
                                <Text style={{ flex: 1, fontSize: 16 }}>{item}</Text>
                                {item === timezone && <Text style={{ color: '#7C3AED', fontWeight: '700' }}>✓</Text>}
                            </TouchableOpacity>
                        )}
                    />
                </View>
            </Modal>
        </>
    );
}

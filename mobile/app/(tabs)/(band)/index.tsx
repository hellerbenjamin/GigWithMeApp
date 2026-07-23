import { useAuth } from '@/src/context/AuthContext';
import { MemberFormModal } from '@/src/components/MemberFormModal';
import { apiFetch } from '@/src/lib/api';
import { type Theme, useTheme } from '@/src/theme';
import type { MemberRole, RoleOption, RosterMember, RosterResponse } from '@/src/types/member';
import { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    FlatList,
    Linking,
    RefreshControl,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';

function roleBadge(theme: Theme, role: MemberRole) {
    switch (role) {
        case 'owner':
            return { bg: theme.colors.primaryTint, fg: theme.colors.primaryText };
        case 'admin':
            return { bg: theme.colors.confirmedBg, fg: theme.colors.confirmed };
        default:
            return { bg: theme.colors.surface, fg: theme.colors.textMuted };
    }
}

export default function BandRosterScreen() {
    const { token, bands } = useAuth();
    const theme = useTheme();

    const [bandId, setBandId] = useState<number | null>(bands[0]?.id ?? null);
    const [members, setMembers] = useState<RosterMember[]>([]);
    const [roles, setRoles] = useState<RoleOption[]>([]);
    const [canManage, setCanManage] = useState(false);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<RosterMember | null>(null);

    const load = useCallback(async () => {
        if (bandId == null) { setLoading(false); return; }
        try {
            const res = await apiFetch(`/bands/${bandId}/members`, { token: token! });
            if (!res.ok) throw new Error();
            const json: RosterResponse = await res.json();
            setMembers(json.data);
            setRoles(json.roles);
            setCanManage(json.can_manage);
            setError(null);
        } catch {
            setError('Could not load the roster. Pull to retry.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [bandId, token]);

    useEffect(() => { setLoading(true); load(); }, [load]);

    function applyRoster(json: RosterResponse) {
        setMembers(json.data);
        setRoles(json.roles);
        setCanManage(json.can_manage);
    }

    if (bands.length === 0) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background, padding: 32 }}>
                <Text style={{ color: theme.colors.textMuted, textAlign: 'center' }}>
                    You're not in any bands yet.
                </Text>
            </View>
        );
    }

    return (
        <View style={{ flex: 1, backgroundColor: theme.colors.background }}>
            <FlatList
                data={members}
                keyExtractor={(m) => String(m.id)}
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
                    <View style={{ paddingHorizontal: 20, marginBottom: 12 }}>
                        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                            <Text style={{ flex: 1, fontSize: 28, fontFamily: theme.fonts.display, color: theme.colors.text }}>
                                Band
                            </Text>
                            {canManage && (
                                <TouchableOpacity
                                    onPress={() => { setEditing(null); setFormOpen(true); }}
                                    style={{ backgroundColor: theme.colors.primary, borderRadius: theme.radius.md, paddingHorizontal: 14, paddingVertical: 8 }}
                                >
                                    <Text style={{ color: theme.colors.onPrimary, fontWeight: '600' }}>Add</Text>
                                </TouchableOpacity>
                            )}
                        </View>

                        {/* Band switcher (only when the member is in more than one band) */}
                        {bands.length > 1 && (
                            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 14 }}>
                                {bands.map((band) => {
                                    const active = band.id === bandId;
                                    return (
                                        <TouchableOpacity
                                            key={band.id}
                                            onPress={() => setBandId(band.id)}
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
                    </View>
                }
                ListEmptyComponent={
                    loading ? (
                        <View style={{ alignItems: 'center', paddingTop: 40 }}>
                            <ActivityIndicator color={theme.colors.primary} />
                        </View>
                    ) : (
                        <View style={{ alignItems: 'center', paddingTop: 40 }}>
                            <Text style={{ color: theme.colors.textMuted }}>{error ?? 'No members yet.'}</Text>
                        </View>
                    )
                }
                renderItem={({ item }) => {
                    const badge = roleBadge(theme, item.role);
                    const contact = item.phone_number ?? item.email;
                    const contactUrl = item.phone_number ? `tel:${item.phone_number}` : `mailto:${item.email}`;
                    return (
                        <View
                            style={{
                                marginHorizontal: 16,
                                marginBottom: 10,
                                backgroundColor: theme.colors.card,
                                borderRadius: theme.radius.lg,
                                borderWidth: theme.isDark ? 1 : 0,
                                borderColor: theme.colors.border,
                                padding: 14,
                                flexDirection: 'row',
                                alignItems: 'center',
                                shadowColor: '#000',
                                shadowOpacity: theme.isDark ? 0 : 0.05,
                                shadowRadius: 4,
                                shadowOffset: { width: 0, height: 1 },
                                elevation: theme.isDark ? 0 : 1,
                            }}
                        >
                            {/* Avatar initial */}
                            <View style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: theme.colors.surface, alignItems: 'center', justifyContent: 'center', marginRight: 12 }}>
                                <Text style={{ fontSize: 18, fontWeight: '600', color: theme.colors.textMuted }}>
                                    {item.name.charAt(0).toUpperCase()}
                                </Text>
                            </View>

                            {/* Name + contact */}
                            <View style={{ flex: 1 }}>
                                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                    <Text style={{ fontSize: 16, fontWeight: '600', color: theme.colors.text }}>{item.name}</Text>
                                    {item.is_you && (
                                        <Text style={{ fontSize: 12, color: theme.colors.textSubtle, marginLeft: 6 }}>You</Text>
                                    )}
                                </View>
                                <TouchableOpacity onPress={() => Linking.openURL(contactUrl)}>
                                    <Text style={{ fontSize: 13, color: theme.colors.primaryText, marginTop: 2 }}>{contact}</Text>
                                </TouchableOpacity>
                            </View>

                            {/* Role badge */}
                            <View style={{ backgroundColor: badge.bg, borderRadius: theme.radius.pill, paddingHorizontal: 10, paddingVertical: 3 }}>
                                <Text style={{ fontSize: 11, fontWeight: '600', color: badge.fg }}>{item.role_label}</Text>
                            </View>

                            {/* Manage affordance */}
                            {canManage && (
                                <TouchableOpacity
                                    onPress={() => { setEditing(item); setFormOpen(true); }}
                                    hitSlop={10}
                                    style={{ paddingLeft: 12 }}
                                >
                                    <Text style={{ color: theme.colors.textSubtle, fontSize: 20 }}>›</Text>
                                </TouchableOpacity>
                            )}
                        </View>
                    );
                }}
            />

            {bandId != null && (
                <MemberFormModal
                    visible={formOpen}
                    bandId={bandId}
                    token={token!}
                    roles={roles}
                    member={editing}
                    onClose={() => setFormOpen(false)}
                    onSaved={applyRoster}
                />
            )}
        </View>
    );
}

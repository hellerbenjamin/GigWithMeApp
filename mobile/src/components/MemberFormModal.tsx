import { apiFetch } from '@/src/lib/api';
import { useTheme } from '@/src/theme';
import type { MemberRole, RoleOption, RosterMember, RosterResponse } from '@/src/types/member';
import { useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    Modal,
    ScrollView,
    Switch,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';

interface Props {
    visible: boolean;
    bandId: number;
    token: string;
    roles: RoleOption[];
    /** The member being edited, or null when adding. */
    member: RosterMember | null;
    onClose: () => void;
    /** Called with the fresh roster returned by the API after any change. */
    onSaved: (roster: RosterResponse) => void;
}

/**
 * Add or edit a band member. Adding creates/invites by email; editing updates
 * the shared account name/email and the band role, and can remove the member.
 * Server-side guards (duplicate email, last owner) surface as inline errors.
 */
export function MemberFormModal({ visible, bandId, token, roles, member, onClose, onSaved }: Props) {
    const theme = useTheme();
    const editing = member !== null;

    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [role, setRole] = useState<MemberRole>('member');
    const [critical, setCritical] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Reset the form whenever the modal opens for a (possibly different) member.
    useEffect(() => {
        if (!visible) return;
        setName(member?.name ?? '');
        setEmail(member?.email ?? '');
        setRole(member?.role ?? 'member');
        setCritical(member?.critical ?? true);
        setError(null);
        setSubmitting(false);
    }, [visible, member]);

    async function submit() {
        setSubmitting(true);
        setError(null);
        try {
            const res = await apiFetch(
                editing ? `/bands/${bandId}/members/${member!.id}` : `/bands/${bandId}/members`,
                {
                    method: editing ? 'PUT' : 'POST',
                    token,
                    body: JSON.stringify({ name, email, role, critical }),
                },
            );
            const json = await res.json();
            if (!res.ok) {
                setError(firstError(json) ?? 'Could not save. Please try again.');
                return;
            }
            onSaved(json as RosterResponse);
            onClose();
        } catch {
            setError('Something went wrong. Please try again.');
        } finally {
            setSubmitting(false);
        }
    }

    function confirmRemove() {
        if (!member) return;
        Alert.alert(
            member.is_you ? 'Leave band' : `Remove ${member.name}`,
            member.is_you
                ? 'You will be removed from this band. This does not delete your account.'
                : `${member.name} will be removed from the roster. Their account stays intact.`,
            [
                { text: 'Cancel', style: 'cancel' },
                { text: member.is_you ? 'Leave' : 'Remove', style: 'destructive', onPress: remove },
            ],
        );
    }

    async function remove() {
        if (!member) return;
        setSubmitting(true);
        setError(null);
        try {
            const res = await apiFetch(`/bands/${bandId}/members/${member.id}`, {
                method: 'DELETE',
                token,
            });
            const json = await res.json();
            if (!res.ok) {
                setError(firstError(json) ?? 'Could not remove this member.');
                return;
            }
            onSaved(json as RosterResponse);
            onClose();
        } catch {
            setError('Something went wrong. Please try again.');
        } finally {
            setSubmitting(false);
        }
    }

    const input = {
        borderWidth: 1,
        borderColor: theme.colors.borderStrong,
        borderRadius: theme.radius.md,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 16,
        color: theme.colors.text,
        backgroundColor: theme.colors.card,
    };
    const label = {
        fontSize: 13,
        fontWeight: '600' as const,
        color: theme.colors.textMuted,
        marginBottom: 6,
        marginTop: 18,
        textTransform: 'uppercase' as const,
        letterSpacing: 0.5,
    };

    return (
        <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
            <View style={{ flex: 1, paddingTop: 20, backgroundColor: theme.colors.background }}>
                {/* Header */}
                <View style={{ flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, marginBottom: 4 }}>
                    <TouchableOpacity onPress={onClose} disabled={submitting}>
                        <Text style={{ color: theme.colors.textMuted, fontSize: 16 }}>Cancel</Text>
                    </TouchableOpacity>
                    <Text style={{ flex: 1, textAlign: 'center', fontFamily: theme.fonts.display, fontSize: 17, color: theme.colors.text }}>
                        {editing ? 'Edit member' : 'Add member'}
                    </Text>
                    <TouchableOpacity onPress={submit} disabled={submitting || !name || !email}>
                        <Text style={{ color: submitting || !name || !email ? theme.colors.textSubtle : theme.colors.primaryText, fontSize: 16, fontWeight: '600' }}>
                            {editing ? 'Save' : 'Add'}
                        </Text>
                    </TouchableOpacity>
                </View>

                <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 48 }}>
                    {error && (
                        <View style={{ backgroundColor: theme.colors.cancelledBg, borderRadius: theme.radius.md, padding: 12, marginTop: 12 }}>
                            <Text style={{ color: theme.colors.cancelled }}>{error}</Text>
                        </View>
                    )}

                    <Text style={label}>Name</Text>
                    <TextInput
                        value={name}
                        onChangeText={setName}
                        placeholder="Full name"
                        placeholderTextColor={theme.colors.textSubtle}
                        autoCapitalize="words"
                        style={input}
                    />

                    <Text style={label}>Email</Text>
                    <TextInput
                        value={email}
                        onChangeText={setEmail}
                        placeholder="their@email.com"
                        placeholderTextColor={theme.colors.textSubtle}
                        keyboardType="email-address"
                        autoCapitalize="none"
                        autoComplete="email"
                        style={input}
                    />
                    {!editing && (
                        <Text style={{ color: theme.colors.textSubtle, fontSize: 12, marginTop: 6 }}>
                            We'll email them an invitation to set up their gig alerts.
                        </Text>
                    )}

                    <Text style={label}>Role</Text>
                    <View style={{ flexDirection: 'row', gap: 8 }}>
                        {roles.map((r) => {
                            const active = role === r.value;
                            return (
                                <TouchableOpacity
                                    key={r.value}
                                    onPress={() => setRole(r.value)}
                                    style={{
                                        flex: 1,
                                        paddingVertical: 10,
                                        borderRadius: theme.radius.md,
                                        alignItems: 'center',
                                        backgroundColor: active ? theme.colors.primary : theme.colors.surface,
                                        borderWidth: 1,
                                        borderColor: active ? theme.colors.primary : theme.colors.border,
                                    }}
                                >
                                    <Text style={{ color: active ? theme.colors.onPrimary : theme.colors.textMuted, fontWeight: '600', fontSize: 14 }}>
                                        {r.label}
                                    </Text>
                                </TouchableOpacity>
                            );
                        })}
                    </View>

                    <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 24 }}>
                        <View style={{ flex: 1 }}>
                            <Text style={{ fontSize: 16, fontWeight: '500', color: theme.colors.text }}>Critical for gigs</Text>
                            <Text style={{ fontSize: 13, color: theme.colors.textSubtle, marginTop: 2 }}>
                                Their availability is needed to confirm a gig.
                            </Text>
                        </View>
                        <Switch value={critical} onValueChange={setCritical} trackColor={{ true: theme.colors.primary }} />
                    </View>

                    {editing && (
                        <TouchableOpacity onPress={confirmRemove} disabled={submitting} style={{ alignItems: 'center', paddingVertical: 16, marginTop: 32 }}>
                            <Text style={{ color: theme.colors.danger, fontWeight: '600' }}>
                                {member?.is_you ? 'Leave band' : 'Remove from band'}
                            </Text>
                        </TouchableOpacity>
                    )}

                    {submitting && <ActivityIndicator color={theme.colors.primary} style={{ marginTop: 16 }} />}
                </ScrollView>
            </View>
        </Modal>
    );
}

/** Pull the first human-readable message out of a Laravel error response. */
function firstError(json: any): string | null {
    if (json?.errors && typeof json.errors === 'object') {
        const first = Object.values(json.errors)[0];
        if (Array.isArray(first) && first.length) return String(first[0]);
    }
    return typeof json?.message === 'string' ? json.message : null;
}

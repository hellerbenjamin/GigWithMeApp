import { apiFetch } from '@/src/lib/api';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/theme';
import { useState } from 'react';
import { Alert, Text, TextInput, TouchableOpacity, View } from 'react-native';

export default function LoginScreen() {
    const { signIn } = useAuth();
    const theme = useTheme();
    const [email, setEmail] = useState('');
    const [sent, setSent] = useState(false);
    const [loading, setLoading] = useState(false);

    async function requestLink() {
        setLoading(true);
        try {
            await apiFetch('/auth/magic-link', {
                method: 'POST',
                body: JSON.stringify({ email }),
            });
            setSent(true);
        } catch {
            Alert.alert('Error', 'Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    if (sent) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32, backgroundColor: theme.colors.background }}>
                <Text style={{ fontSize: 22, fontFamily: theme.fonts.display, color: theme.colors.text, marginBottom: 12 }}>Check your email</Text>
                <Text style={{ color: theme.colors.textMuted, textAlign: 'center' }}>
                    We sent a sign-in link to {email}. Tap the link in the email to open the app.
                </Text>
            </View>
        );
    }

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32, backgroundColor: theme.colors.background }}>
            <Text style={{ fontSize: 24, fontFamily: theme.fonts.display, color: theme.colors.text, marginBottom: 8 }}>Sign in to GigWithMe</Text>
            <Text style={{ color: theme.colors.textMuted, marginBottom: 32 }}>We'll send a link to your email.</Text>

            <TextInput
                value={email}
                onChangeText={setEmail}
                placeholder="your@email.com"
                placeholderTextColor={theme.colors.textSubtle}
                keyboardType="email-address"
                autoCapitalize="none"
                autoComplete="email"
                style={{
                    width: '100%',
                    borderWidth: 1,
                    borderColor: theme.colors.borderStrong,
                    borderRadius: theme.radius.md,
                    paddingHorizontal: 14,
                    paddingVertical: 12,
                    fontSize: 16,
                    color: theme.colors.text,
                    backgroundColor: theme.colors.card,
                    marginBottom: 16,
                }}
            />

            <TouchableOpacity
                onPress={requestLink}
                disabled={loading || !email}
                style={{
                    width: '100%',
                    backgroundColor: theme.colors.primary,
                    borderRadius: theme.radius.md,
                    paddingVertical: 14,
                    alignItems: 'center',
                    opacity: loading || !email ? 0.5 : 1,
                }}
            >
                <Text style={{ color: theme.colors.onPrimary, fontWeight: '600', fontSize: 16 }}>
                    {loading ? 'Sending…' : 'Send sign-in link'}
                </Text>
            </TouchableOpacity>
        </View>
    );
}

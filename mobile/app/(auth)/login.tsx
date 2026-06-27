import { apiFetch } from '@/src/lib/api';
import { useAuth } from '@/src/context/AuthContext';
import { useState } from 'react';
import { Alert, Text, TextInput, TouchableOpacity, View } from 'react-native';

export default function LoginScreen() {
    const { signIn } = useAuth();
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
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32 }}>
                <Text style={{ fontSize: 22, fontWeight: '700', marginBottom: 12 }}>Check your email</Text>
                <Text style={{ color: '#6B7280', textAlign: 'center' }}>
                    We sent a sign-in link to {email}. Tap the link in the email to open the app.
                </Text>
            </View>
        );
    }

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32 }}>
            <Text style={{ fontSize: 22, fontWeight: '700', marginBottom: 8 }}>Sign in to GigWithMe</Text>
            <Text style={{ color: '#6B7280', marginBottom: 32 }}>We'll send a link to your email.</Text>

            <TextInput
                value={email}
                onChangeText={setEmail}
                placeholder="your@email.com"
                keyboardType="email-address"
                autoCapitalize="none"
                autoComplete="email"
                style={{
                    width: '100%',
                    borderWidth: 1,
                    borderColor: '#D1D5DB',
                    borderRadius: 10,
                    paddingHorizontal: 14,
                    paddingVertical: 12,
                    fontSize: 16,
                    marginBottom: 16,
                }}
            />

            <TouchableOpacity
                onPress={requestLink}
                disabled={loading || !email}
                style={{
                    width: '100%',
                    backgroundColor: '#7C3AED',
                    borderRadius: 10,
                    paddingVertical: 14,
                    alignItems: 'center',
                    opacity: loading || !email ? 0.5 : 1,
                }}
            >
                <Text style={{ color: '#fff', fontWeight: '600', fontSize: 16 }}>
                    {loading ? 'Sending…' : 'Send sign-in link'}
                </Text>
            </TouchableOpacity>
        </View>
    );
}

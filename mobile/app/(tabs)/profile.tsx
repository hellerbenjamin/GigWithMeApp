import { useAuth } from '@/src/context/AuthContext';
import { Text, TouchableOpacity, View } from 'react-native';

export default function ProfileScreen() {
    const { user, signOut } = useAuth();

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: 16 }}>
            <Text style={{ fontSize: 18, fontWeight: '600' }}>{user?.name}</Text>
            <Text style={{ color: '#6B7280' }}>{user?.email}</Text>
            <TouchableOpacity onPress={signOut} style={{ marginTop: 24, padding: 12 }}>
                <Text style={{ color: '#EF4444' }}>Sign out</Text>
            </TouchableOpacity>
        </View>
    );
}

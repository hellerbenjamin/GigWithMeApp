import { useAuth } from '@/src/context/AuthContext';
import { Text, View } from 'react-native';

export default function GigsScreen() {
    const { user } = useAuth();

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
            <Text>Gigs — coming soon</Text>
        </View>
    );
}

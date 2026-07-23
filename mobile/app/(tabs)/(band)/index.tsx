import { useTheme } from '@/src/theme';
import { Text, View } from 'react-native';

export default function AdminHomeScreen() {
    const theme = useTheme();
    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.background }}>
            <Text style={{ color: theme.colors.textMuted }}>Admin, coming soon</Text>
        </View>
    );
}

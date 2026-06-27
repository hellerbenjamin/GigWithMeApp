import { useAuth } from '@/src/context/AuthContext';
import { Tabs } from 'expo-router';

export default function TabLayout() {
    const { isAdmin } = useAuth();

    return (
        <Tabs screenOptions={{ headerShown: false }}>
            <Tabs.Screen
                name="gigs"
                options={{
                    title: 'Gigs',
                    tabBarIcon: () => null,
                }}
            />
            <Tabs.Screen
                name="profile"
                options={{
                    title: 'Profile',
                    tabBarIcon: () => null,
                }}
            />
            {isAdmin && (
                <Tabs.Screen
                    name="(admin)"
                    options={{
                        title: 'Admin',
                        tabBarIcon: () => null,
                    }}
                />
            )}
        </Tabs>
    );
}

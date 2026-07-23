import { useTheme } from '@/src/theme';
import { Tabs } from 'expo-router';

export default function TabLayout() {
    const theme = useTheme();

    return (
        <Tabs
            screenOptions={{
                headerShown: false,
                tabBarActiveTintColor: theme.colors.primary,
                tabBarInactiveTintColor: theme.colors.textSubtle,
                tabBarStyle: {
                    backgroundColor: theme.colors.card,
                    borderTopColor: theme.colors.border,
                },
            }}
        >
            <Tabs.Screen
                name="gigs"
                options={{
                    title: 'Gigs',
                    tabBarIcon: () => null,
                }}
            />
            {/*
             * The band roster is member-facing (everyone sees who's in their
             * bands); owners/admins additionally get the manage actions, gated
             * per-band by the API's can_manage flag.
             */}
            <Tabs.Screen
                name="(band)"
                options={{
                    title: 'Band',
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
        </Tabs>
    );
}

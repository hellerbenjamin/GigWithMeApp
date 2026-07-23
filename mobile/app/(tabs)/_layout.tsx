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
            {/*
             * Always register the admin screen so it is never added to or
             * removed from the navigator at runtime (that churn triggers the
             * "removed natively but not from JS state" error). Hide the tab
             * for non-admins with href: null instead. Tab screens render
             * lazily, so AdminLayout never mounts while the tab is hidden.
             */}
            <Tabs.Screen
                name="(admin)"
                options={{
                    title: 'Admin',
                    tabBarIcon: () => null,
                    href: isAdmin ? undefined : null,
                }}
            />
        </Tabs>
    );
}

import { apiFetch } from '@/src/lib/api';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';

Notifications.setNotificationHandler({
    handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: true,
        shouldSetBadge: false,
        shouldShowBanner: true,
        shouldShowList: true,
    }),
});

export function usePushNotifications(token: string | null) {
    const registered = useRef(false);

    useEffect(() => {
        if (!token || registered.current) return;

        registerForPushNotifications(token).then(() => {
            registered.current = true;
        });
    }, [token]);
}

async function registerForPushNotifications(bearerToken: string): Promise<void> {
    if (!Device.isDevice) return;

    const { status: existing } = await Notifications.getPermissionsAsync();
    let finalStatus = existing;

    if (existing !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
    }

    if (finalStatus !== 'granted') return;

    if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('default', {
            name: 'GigWithMe',
            importance: Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 250, 250, 250],
        });
    }

    const pushToken = await Notifications.getExpoPushTokenAsync();

    await apiFetch('/notifications/push-token', {
        method: 'POST',
        token: bearerToken,
        body: JSON.stringify({
            token: pushToken.data,
            device_name: Device.deviceName ?? undefined,
        }),
    });
}

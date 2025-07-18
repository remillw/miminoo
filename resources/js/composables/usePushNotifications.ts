import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

export function usePushNotifications() {
    const isRegistered = ref(false);
    const permissionStatus = ref<'prompt' | 'granted' | 'denied'>('prompt');

    /**
     * Initialiser les notifications push
     */
    const initializePushNotifications = async () => {
        // Vérifier si on est sur une plateforme native (pas web)
        if (!Capacitor.isNativePlatform()) {
            console.log('Push notifications only available on native platforms');
            return;
        }

        try {
            // Demander les permissions
            const permission = await PushNotifications.requestPermissions();

            if (permission.receive === 'granted') {
                permissionStatus.value = 'granted';
                await registerForPushNotifications();
            } else {
                permissionStatus.value = 'denied';
                console.log('Push notification permission denied');
            }
        } catch (error) {
            console.error('Error initializing push notifications:', error);
        }
    };

    /**
     * Enregistrer pour recevoir les notifications push
     */
    const registerForPushNotifications = async () => {
        try {
            await PushNotifications.register();

            // Configurer les listeners pour les notifications push
            setupPushListeners();
        } catch (error) {
            console.error('Error registering for push notifications:', error);
        }
    };

    /**
     * Configurer les listeners pour les notifications push
     */
    const setupPushListeners = () => {
        // Token reçu - l'envoyer au backend
        PushNotifications.addListener('registration', async (token) => {
            console.log('Push registration success, token: ' + token.value);
            await sendTokenToBackend(token.value);
        });

        // Erreur d'enregistrement
        PushNotifications.addListener('registrationError', (error) => {
            console.error('Error on registration: ' + JSON.stringify(error));
        });

        // Notification reçue quand l'app est ouverte
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Push notification received: ', notification);
        });

        // Notification cliquée - action utilisateur
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('Push notification action performed', notification);

            // Gérer les actions selon le type de notification
            const data = notification.notification.data;

            if (data?.action_url) {
                router.visit(data.action_url);
            } else if (data?.type === 'new_announcement') {
                router.visit('/annonces');
            }
        });
    };

    /**
     * Envoyer le device token au backend Laravel
     */
    const sendTokenToBackend = async (token: string) => {
        try {
            // Détecter le type d'appareil
            const deviceType = Capacitor.getPlatform(); // 'ios' ou 'android'

            await router.post(
                '/device-token',
                {
                    device_token: token,
                    device_type: deviceType,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('Device token sent to backend successfully');
                        isRegistered.value = true;
                    },
                    onError: (errors) => {
                        console.error('Error sending device token to backend:', errors);
                    },
                },
            );
        } catch (error) {
            console.error('Error sending token to backend:', error);
        }
    };

    /**
     * Désactiver les notifications push
     */
    const disablePushNotifications = async () => {
        try {
            await router.delete('/device-token', {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    console.log('Device token removed from backend');
                    isRegistered.value = false;
                },
            });
        } catch (error) {
            console.error('Error disabling push notifications:', error);
        }
    };

    /**
     * Mettre à jour les préférences de notifications
     */
    const updateNotificationPreferences = async (enabled: boolean) => {
        try {
            await router.put(
                '/device-token/preferences',
                {
                    push_notifications: enabled,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('Notification preferences updated');
                    },
                },
            );
        } catch (error) {
            console.error('Error updating notification preferences:', error);
        }
    };

    // Initialiser automatiquement quand le composable est utilisé
    onMounted(() => {
        initializePushNotifications();
    });

    return {
        isRegistered,
        permissionStatus,
        initializePushNotifications,
        disablePushNotifications,
        updateNotificationPreferences,
    };
}

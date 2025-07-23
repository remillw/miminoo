import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { router, usePage } from '@inertiajs/vue3';
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
     * Vérifier si l'enregistrement du device token a été demandé après connexion
     */
    const checkForTriggeredRegistration = async () => {
        // Vérifier s'il y a un flag dans l'URL pour déclencher l'enregistrement
        const urlParams = new URLSearchParams(window.location.search);
        const shouldRegisterFromUrl = urlParams.get('register_device_token') === '1' || urlParams.get('mobile_auth') === 'success';

        // Vérifier aussi s'il y a une session Laravel qui indique qu'on doit s'enregistrer
        // Cela sera automatiquement disponible via les props Inertia
        const page = usePage();
        const shouldRegisterFromSession = (page.props as any).triggerDeviceTokenRegistration;

        const shouldRegister = shouldRegisterFromUrl || shouldRegisterFromSession;

        if (shouldRegister && Capacitor.isNativePlatform()) {
            console.log("🔔 Déclenchement de l'enregistrement device token après connexion", {
                from_url: shouldRegisterFromUrl,
                from_session: shouldRegisterFromSession
            });
            await initializePushNotifications();
            
            // Nettoyer le flag de session après usage
            if (shouldRegisterFromSession) {
                await router.post('/clear-device-token-flag', {}, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        }
    };

    /**
     * Enregistrer pour recevoir les notifications push
     */
    const registerForPushNotifications = async () => {
        try {
            console.log('🔔 Enregistrement pour les notifications push...');

            // Configurer les listeners AVANT l'enregistrement
            setupPushListeners();

            await PushNotifications.register();
            console.log('✅ PushNotifications.register() terminé');
        } catch (error) {
            console.error("❌ Erreur lors de l'enregistrement:", error);
        }
    };

    /**
     * Configurer les listeners pour les notifications push
     */
    const setupPushListeners = () => {
        console.log('🔧 Configuration des listeners push...');

        // Token reçu - l'envoyer au backend
        PushNotifications.addListener('registration', async (token) => {
            console.log('🎯 Token reçu!', token.value);
            await sendTokenToBackend(token.value);
        });

        // Erreur d'enregistrement
        PushNotifications.addListener('registrationError', (error) => {
            console.error('❌ Erreur registration:', JSON.stringify(error));
        });

        // Notification reçue quand l'app est ouverte
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('📱 Notification reçue:', notification);
        });

        // Notification cliquée - action utilisateur
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('👆 Notification cliquée:', notification);

            // Gérer les actions selon le type de notification
            const data = notification.notification.data;

            if (data?.action_url) {
                router.visit(data.action_url);
            } else if (data?.type === 'new_announcement') {
                router.visit('/annonces');
            }
        });

        console.log('✅ Listeners configurés');
    };

    /**
     * Envoyer le device token au backend Laravel
     */
    const sendTokenToBackend = async (token: string) => {
        try {
            // Détecter le type d'appareil
            const deviceType = Capacitor.getPlatform(); // 'ios' ou 'android'

            console.log('📤 Envoi token au backend...', {
                device_type: deviceType,
                token_preview: token.substring(0, 20) + '...',
            });

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
                        console.log('✅ Token envoyé avec succès au backend');
                        isRegistered.value = true;
                    },
                    onError: (errors) => {
                        console.error('❌ Erreur envoi token au backend:', errors);
                    },
                },
            );
        } catch (error) {
            console.error('❌ Erreur envoi token:', error);
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
        // Vérifier d'abord si on doit s'enregistrer suite à une connexion
        checkForTriggeredRegistration();

        // Puis initialiser normalement
        initializePushNotifications();
    });

    return {
        isRegistered,
        permissionStatus,
        initializePushNotifications,
        disablePushNotifications,
        updateNotificationPreferences,
        checkForTriggeredRegistration,
    };
}

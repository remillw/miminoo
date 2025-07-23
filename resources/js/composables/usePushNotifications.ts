import { Capacitor } from '@capacitor/core';
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Déclaration globale pour OneSignal
declare global {
    interface Window {
        OneSignal: any;
        plugins: any;
    }
}

// Variable globale pour éviter les multiples initialisations
let isPushNotificationsInitialized = false;

export function usePushNotifications() {
    const isRegistered = ref(false);
    const permissionStatus = ref<'prompt' | 'granted' | 'denied'>('prompt');

    /**
     * Initialiser les notifications push avec OneSignal
     */
    const initializePushNotifications = async () => {
        // Vérifier si on est sur une plateforme native (pas web)
        if (!Capacitor.isNativePlatform()) {
            console.log('🌐 Push notifications avec OneSignal uniquement sur mobile');
            return;
        }

        // Éviter les initialisations multiples
        if (isPushNotificationsInitialized) {
            console.log('⚠️ OneSignal déjà initialisé, skip');
            return;
        }

        try {
            console.log('🔔 Initialisation de OneSignal...');
            isPushNotificationsInitialized = true;

            // Attendre que OneSignal soit disponible
            await waitForOneSignal();

            // Récupérer l'ID du joueur OneSignal (équivalent du device token)
            const playerId = await getOneSignalPlayerId();
            
            if (playerId) {
                console.log('🎯 OneSignal Player ID reçu:', playerId);
                await sendTokenToBackend(playerId);
                isRegistered.value = true;
                permissionStatus.value = 'granted';
            } else {
                console.warn('⚠️ Impossible de récupérer le Player ID OneSignal');
                permissionStatus.value = 'denied';
            }

        } catch (error) {
            console.error('❌ Erreur initialisation OneSignal:', error);
            isPushNotificationsInitialized = false; // Reset en cas d'erreur
        }
    };

    /**
     * Attendre que OneSignal soit disponible
     */
    const waitForOneSignal = async (): Promise<void> => {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 20; // 10 secondes max
            
            const checkOneSignal = () => {
                attempts++;
                
                if (window.OneSignal) {
                    console.log('✅ OneSignal disponible');
                    resolve();
                    return;
                }
                
                if (attempts >= maxAttempts) {
                    reject(new Error('OneSignal non disponible après 10 secondes'));
                    return;
                }
                
                console.log(`🔄 Attente OneSignal... (${attempts}/${maxAttempts})`);
                setTimeout(checkOneSignal, 500);
            };
            
            checkOneSignal();
        });
    };

    /**
     * Récupérer l'ID du joueur OneSignal
     */
    const getOneSignalPlayerId = async (): Promise<string | null> => {
        try {
            return new Promise((resolve) => {
                window.OneSignal.getDeviceState((deviceState: any) => {
                    console.log('📱 OneSignal Device State:', deviceState);
                    const playerId = deviceState?.userId || deviceState?.playerId;
                    resolve(playerId || null);
                });
            });
        } catch (error) {
            console.error('❌ Erreur récupération Player ID OneSignal:', error);
            return null;
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
     * Configurer les listeners OneSignal
     */
    const setupOneSignalListeners = () => {
        if (!window.OneSignal) {
            console.warn('⚠️ OneSignal non disponible pour configurer les listeners');
            return;
        }

        console.log('🔧 Configuration des listeners OneSignal...');

        // Notification cliquée
        window.OneSignal.setNotificationOpenedHandler((result: any) => {
            console.log('👆 Notification OneSignal cliquée:', result);
            
            const data = result.notification?.payload?.additionalData;
            if (data?.action_url) {
                router.visit(data.action_url);
            } else if (data?.type === 'new_announcement') {
                router.visit('/annonces');
            }
        });

        // Notification reçue
        window.OneSignal.setNotificationWillShowInForegroundHandler((notification: any) => {
            console.log('📱 Notification OneSignal reçue:', notification);
            // Afficher la notification même en premier plan
            window.OneSignal.showNotification(notification);
        });

        console.log('✅ Listeners OneSignal configurés');
    };

    /**
     * Envoyer le OneSignal Player ID au backend Laravel
     */
    const sendTokenToBackend = async (playerId: string) => {
        try {
            // Détecter le type d'appareil
            const deviceType = Capacitor.getPlatform(); // 'ios' ou 'android'

            console.log('📤 Envoi OneSignal Player ID au backend...', {
                device_type: deviceType,
                player_id_preview: playerId.substring(0, 20) + '...',
            });

            await router.post(
                '/device-token',
                {
                    device_token: playerId, // OneSignal Player ID comme device token
                    device_type: deviceType,
                    notification_provider: 'onesignal', // Indiquer qu'on utilise OneSignal
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        console.log('✅ OneSignal Player ID envoyé avec succès au backend');
                        isRegistered.value = true;
                    },
                    onError: (errors) => {
                        console.error('❌ Erreur envoi Player ID au backend:', errors);
                    },
                },
            );
        } catch (error) {
            console.error('❌ Erreur envoi Player ID:', error);
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

        // Initialisation normale seulement si pas déjà fait
        if (!isPushNotificationsInitialized && Capacitor.isNativePlatform()) {
            console.log('🔔 Initialisation automatique de OneSignal');
            // Configurer les listeners d'abord
            setupOneSignalListeners();
            // Puis initialiser
            initializePushNotifications();
        }
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

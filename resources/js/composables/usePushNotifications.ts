import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Import Capacitor dynamiquement seulement pour mobile
let Capacitor: any = null;

// Déclaration globale pour OneSignal
declare global {
    interface Window {
        OneSignal: any;
        plugins: any;
    }
}

// Variable globale pour éviter les multiples initialisations
let isPushNotificationsInitialized = false;

// Fonction pour charger Capacitor dynamiquement
const loadCapacitor = async () => {
    if (Capacitor) return Capacitor;
    
    try {
        // Vérifier si on est dans un environnement web
        if (typeof window === 'undefined' || !window.location.protocol.startsWith('capacitor')) {
            return null;
        }
        
        const capacitorModule = await import('@capacitor/core');
        Capacitor = capacitorModule.Capacitor;
        return Capacitor;
    } catch (error) {
        console.log('🌐 Capacitor non disponible, environnement web détecté');
        return null;
    }
};

// Fonction helper pour vérifier si on est sur mobile
const isNativePlatform = async () => {
    const capacitor = await loadCapacitor();
    return capacitor?.isNativePlatform() ?? false;
};

export function usePushNotifications() {
    const isRegistered = ref(false);
    const permissionStatus = ref<'prompt' | 'granted' | 'denied'>('prompt');

    /**
     * Initialiser les notifications push avec OneSignal
     */
    const initializePushNotifications = async () => {
        // Vérifier si on est sur une plateforme native (pas web)
        const isNative = await isNativePlatform();
        if (!isNative) {
            console.log('🌐 OneSignal uniquement disponible sur mobile');
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

            // Initialiser OneSignal via le plugin Cordova
            await initializeOneSignalPlugin();

            console.log('✅ OneSignal initialisé avec succès');

        } catch (error) {
            console.error('❌ Erreur initialisation OneSignal:', error);
            isPushNotificationsInitialized = false; // Reset en cas d'erreur
        }
    };

    /**
     * Initialiser le plugin OneSignal Cordova
     */
    const initializeOneSignalPlugin = async (): Promise<void> => {
        return new Promise((resolve, reject) => {
            // Vérifier si OneSignal est disponible
            if (!(window as any).plugins?.OneSignal) {
                reject(new Error('Plugin OneSignal non disponible'));
                return;
            }

            const OneSignal = (window as any).plugins.OneSignal;
            
            // Initialiser OneSignal
            OneSignal.setAppId("fa561331-c9a6-496e-8218-3897dd3a04a2");
            
            console.log('🔧 Configuration des listeners OneSignal...');
            
            // Listener pour quand l'utilisateur accepte les notifications
            OneSignal.setNotificationOpenedHandler((jsonData: any) => {
                console.log('👆 Notification OneSignal cliquée:', jsonData);
                handleNotificationOpened(jsonData);
            });

            // Listener pour les notifications reçues
            OneSignal.setNotificationWillShowInForegroundHandler((notification: any) => {
                console.log('📱 Notification OneSignal reçue:', notification);
                OneSignal.completeNotification(notification);
            });

            // Listener pour les changements de subscription
            OneSignal.setSubscriptionObserver((state: any) => {
                console.log('🔄 OneSignal subscription changed:', state);
                
                if (state.to.isSubscribed) {
                    const playerId = state.to.userId;
                    console.log('🎯 OneSignal Player ID reçu:', playerId);
                    
                    if (playerId) {
                        sendTokenToBackend(playerId);
                        isRegistered.value = true;
                        permissionStatus.value = 'granted';
                    }
                } else {
                    console.log('⚠️ Utilisateur non abonné aux notifications');
                    permissionStatus.value = 'denied';
                }
            });

            // Demander les permissions
            OneSignal.promptForPushNotificationsWithUserResponse((accepted: boolean) => {
                console.log('🔐 Permissions OneSignal:', accepted ? 'Acceptées' : 'Refusées');
                if (accepted) {
                    permissionStatus.value = 'granted';
                } else {
                    permissionStatus.value = 'denied';
                }
            });

            console.log('✅ OneSignal configuré');
            resolve();
        });
    };

    /**
     * Gérer l'ouverture d'une notification
     */
    const handleNotificationOpened = (jsonData: any) => {
        const additionalData = jsonData.notification?.payload?.additionalData;
        
        if (additionalData?.action_url) {
            router.visit(additionalData.action_url);
        } else if (additionalData?.type === 'new_announcement') {
            router.visit('/annonces');
        } else if (additionalData?.type === 'new_message') {
            router.visit('/messagerie');
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
        const isNative = await isNativePlatform();

        if (shouldRegister && isNative) {
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
     * Envoyer le OneSignal Player ID au backend Laravel
     */
    const sendTokenToBackend = async (playerId: string) => {
        try {
            // Détecter le type d'appareil
            const capacitor = await loadCapacitor();
            const deviceType = capacitor?.getPlatform() ?? 'web'; // 'ios' ou 'android'

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
    onMounted(async () => {
        try {
            // Charger Capacitor dynamiquement pour tester la plateforme
            const { Capacitor: CapacitorModule } = await import('@capacitor/core');
            
            if (!CapacitorModule.isNativePlatform()) {
                console.log('🌐 Environment web détecté - Push notifications désactivées');
                return;
            }

            console.log('📱 Plateforme native détectée pour OneSignal:', CapacitorModule.getPlatform());

            // Vérifier d'abord si on doit s'enregistrer suite à une connexion
            await checkForTriggeredRegistration();

            // Initialisation normale seulement si pas déjà fait
            if (!isPushNotificationsInitialized) {
                console.log('🔔 Initialisation automatique de OneSignal');
                initializePushNotifications();
            }
        } catch (error) {
            console.log('🌐 Capacitor non disponible - Push notifications désactivées');
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

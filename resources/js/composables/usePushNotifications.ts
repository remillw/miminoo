import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Déclaration globale pour Capacitor
declare global {
    interface Window {
        Capacitor: any;
    }
}

// États réactifs
const isRegistered = ref(false);
const permissionStatus = ref<'prompt' | 'prompt-with-rationale' | 'granted' | 'denied'>('prompt');
const deviceToken = ref<string | null>(null);

// Variable pour éviter les initialisations multiples
let isInitializing = false;
let listenersConfigured = false;

/**
 * Import dynamique de Capacitor Push Notifications
 */
const importPushNotifications = async () => {
    try {
        if (!(window as any).Capacitor) {
            return null;
        }

        const { PushNotifications } = await import('@capacitor/push-notifications');
        return PushNotifications;
    } catch (error) {
        return null;
    }
};

/**
 * Initialiser les notifications push avec Capacitor selon la documentation officielle
 */
const initializeNativePushNotifications = async (): Promise<void> => {
    if (isInitializing || deviceToken.value) {
        return;
    }

    try {
        isInitializing = true;

        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            return;
        }

        // ÉTAPE 1: Configurer les listeners AVANT toute opération
        setupPushNotificationListeners(PushNotifications);

        // ÉTAPE 2: Vérifier les permissions actuelles
        const permissionCheck = await PushNotifications.checkPermissions();
        permissionStatus.value = permissionCheck.receive;

        // ÉTAPE 3: Demander les permissions si nécessaire
        if (permissionCheck.receive === 'prompt' || permissionCheck.receive === 'prompt-with-rationale') {
            const permissionRequest = await PushNotifications.requestPermissions();
            permissionStatus.value = permissionRequest.receive;
        }

        // ÉTAPE 4: S'enregistrer si permissions accordées
        if (permissionStatus.value === 'granted') {
            await PushNotifications.register();
            isRegistered.value = true;
        }
    } catch (error) {
        console.error('Erreur initialisation push notifications:', error);
    } finally {
        isInitializing = false;
    }
};

/**
 * Configurer les listeners pour les notifications selon la documentation Capacitor
 */
const setupPushNotificationListeners = (PushNotifications: any) => {
    if (listenersConfigured) {
        return;
    }

    // Listener pour le token de registration - PRIORITAIRE selon documentation
    PushNotifications.addListener('registration', (token: any) => {
        deviceToken.value = token.value;
        sendTokenToBackend(token.value);
    });

    // Listener pour les erreurs de registration
    PushNotifications.addListener('registrationError', (error: any) => {
        console.error('Erreur registration push notifications:', error);
    });

    // Listener pour les notifications reçues (app en premier plan)
    PushNotifications.addListener('pushNotificationReceived', () => {
        // Traitement des notifications en premier plan
    });

    // Listener pour les notifications cliquées/ouvertes
    PushNotifications.addListener('pushNotificationActionPerformed', (notification: any) => {
        handleNotificationOpened(notification);
    });

    listenersConfigured = true;
};

/**
 * Gérer l'ouverture d'une notification
 */
const handleNotificationOpened = (notification: any) => {
    if (notification.data?.type) {
        switch (notification.data.type) {
            case 'message':
                if (notification.data.conversation_id) {
                    router.visit(`/messagerie/${notification.data.conversation_id}`);
                }
                break;
            case 'booking':
                if (notification.data.booking_id) {
                    router.visit(`/reservations/${notification.data.booking_id}`);
                }
                break;
            default:
                router.visit('/tableau-de-bord');
        }
    } else {
        router.visit('/tableau-de-bord');
    }
};

/**
 * Envoyer le device token au backend Laravel
 */
const sendTokenToBackend = async (token: string): Promise<void> => {
    try {
        if (!token) {
            return;
        }

        let csrfToken = (usePage().props as any).csrf_token;

        if (!csrfToken) {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            csrfToken = metaToken ? metaToken.getAttribute('content') : null;
        }

        const platform = (window as any).Capacitor?.getPlatform() || 'unknown';

        const response = await fetch('/device-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                Accept: 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                device_token: token,
                platform: platform,
                notification_provider: 'capacitor',
            }),
        });

        if (!response.ok) {
            throw new Error(`Failed to save token: ${response.status}`);
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi du token:", error);
    }
};

/**
 * Initialiser automatiquement les notifications push
 */
const initializePushNotifications = async (forceReinit: boolean = false): Promise<void> => {
    try {
        if (forceReinit) {
            isInitializing = false;
            listenersConfigured = false;
            deviceToken.value = null;
        }

        await initializeNativePushNotifications();
    } catch (error) {
        console.error('Erreur initialisation push notifications:', error);
    }
};

/**
 * Préparer les données du device token pour inclusion dans les requêtes de login
 */
const getDeviceTokenData = () => {
    const platform = (window as any).Capacitor?.getPlatform() || 'unknown';

    return {
        device_token: deviceToken.value,
        platform: platform,
        notification_provider: 'capacitor',
        mobile_auth: 'true',
    };
};

/**
 * Envoyer le token de façon intégrée au login
 */
const sendTokenWithLogin = (formData: any) => {
    if (deviceToken.value) {
        const tokenData = getDeviceTokenData();
        return { ...formData, ...tokenData };
    }

    return {
        ...formData,
        mobile_auth: 'true',
        platform: (window as any).Capacitor?.getPlatform() || 'unknown',
    };
};

/**
 * Récupérer le token FCM si disponible
 */
const getToken = async (): Promise<string | null> => {
    try {
        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            return null;
        }

        const permissions = await PushNotifications.checkPermissions();
        if (permissions.receive !== 'granted') {
            return null;
        }

        if (!listenersConfigured) {
            setupPushNotificationListeners(PushNotifications);
        }

        await PushNotifications.register();

        return new Promise((resolve) => {
            const timeout = setTimeout(() => {
                resolve(deviceToken.value);
            }, 3000);

            const checkToken = () => {
                if (deviceToken.value) {
                    clearTimeout(timeout);
                    resolve(deviceToken.value);
                } else {
                    setTimeout(checkToken, 100);
                }
            };

            checkToken();
        });
    } catch {
        return null;
    }
};

/**
 * Hook de composition pour les notifications push
 */
export function usePushNotifications() {
    onMounted(() => {
        initializePushNotifications();
    });

    return {
        isRegistered,
        permissionStatus,
        deviceToken,
        initializePushNotifications,
        sendTokenToBackend,
        getDeviceTokenData,
        sendTokenWithLogin,
        getToken,
    };
}

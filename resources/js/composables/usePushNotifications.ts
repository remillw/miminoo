import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Déclaration globale pour Capacitor
declare global {
    interface Window {
        Capacitor: any;
        plugins: any;
    }
}

// États réactifs
const isRegistered = ref(false);
const permissionStatus = ref<'prompt' | 'prompt-with-rationale' | 'granted' | 'denied'>('prompt');
const deviceToken = ref<string | null>(null);

/**
 * Import dynamique de Capacitor Push Notifications
 */
const importPushNotifications = async () => {
    try {
        // Vérifier si Capacitor est disponible et si on est sur mobile
        if (!(window as any).Capacitor?.isNativePlatform()) {
            console.log('🌐 Push notifications uniquement disponibles sur mobile');
            return null;
        }

        // Import dynamique pour éviter l'erreur sur web
        const { PushNotifications } = await import('@capacitor/push-notifications');
        return PushNotifications;
    } catch (error) {
        console.error('❌ Erreur import PushNotifications:', error);
        return null;
    }
};

/**
 * Initialiser les notifications push avec Capacitor natif
 */
const initializeNativePushNotifications = async (): Promise<void> => {
    try {
        // Import dynamique de PushNotifications
        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            return;
        }

        console.log('🔔 Initialisation des notifications push natives...');

        // Vérifier les permissions actuelles
        const permissionCheck = await PushNotifications.checkPermissions();
        console.log('📋 Permissions actuelles:', permissionCheck);

        permissionStatus.value = permissionCheck.receive;

        if (permissionCheck.receive === 'prompt') {
            // Demander les permissions
            console.log('🔐 Demande de permissions...');
            const permissionRequest = await PushNotifications.requestPermissions();
            console.log('✅ Permissions accordées:', permissionRequest);
            permissionStatus.value = permissionRequest.receive;
        }

        if (permissionStatus.value === 'granted') {
            // Enregistrer pour les notifications
            await PushNotifications.register();
            console.log('✅ Enregistrement pour notifications effectué');
            isRegistered.value = true;

            // Configurer les listeners
            setupPushNotificationListeners(PushNotifications);
        }
    } catch (error) {
        console.error('❌ Erreur initialisation push notifications:', error);
    }
};

/**
 * Configurer les listeners pour les notifications
 */
const setupPushNotificationListeners = (PushNotifications: any) => {
    // Listener pour le token de registration
    PushNotifications.addListener('registration', (token: any) => {
        console.log('🎯 Token reçu:', token.value);
        deviceToken.value = token.value;
        sendTokenToBackend(token.value);
    });

    // Listener pour les erreurs
    PushNotifications.addListener('registrationError', (error: any) => {
        console.error('❌ Erreur registration:', error);
    });

    // Listener pour les notifications reçues (app en premier plan)
    PushNotifications.addListener('pushNotificationReceived', (notification: any) => {
        console.log('📱 Notification reçue:', notification);
    });

    // Listener pour les notifications cliquées
    PushNotifications.addListener('pushNotificationActionPerformed', (notification: any) => {
        console.log('👆 Notification cliquée:', notification);
        handleNotificationOpened(notification);
    });

    console.log('✅ Listeners configurés');
};

/**
 * Gérer l'ouverture d'une notification
 */
const handleNotificationOpened = (notification: any) => {
    console.log('🔔 Traitement ouverture notification:', notification);

    // Traiter selon le type de notification
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
            console.log('⚠️ Pas de token à envoyer');
            return;
        }

        console.log('📤 Envoi device token au backend...', {
            token: token.substring(0, 20) + '...',
            length: token.length,
        });

        const response = await fetch('/api/device-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (usePage().props as any).csrf_token || '',
            },
            body: JSON.stringify({
                device_token: token,
                platform: (window as any).Capacitor?.getPlatform() || 'unknown',
                notification_provider: 'capacitor', // Indiquer qu'on utilise Capacitor
            }),
        });

        if (response.ok) {
            console.log('✅ Device token envoyé avec succès au backend');
        } else {
            const errorData = await response.text();
            console.error('❌ Erreur envoi token au backend:', response.status, errorData);
        }
    } catch (error) {
        console.error("❌ Erreur lors de l'envoi du token:", error);
    }
};

/**
 * Initialiser automatiquement les notifications push
 */
const initializePushNotifications = async (): Promise<void> => {
    try {
        // Utiliser Capacitor Push Notifications natif uniquement
        console.log('🔔 Initialisation automatique des notifications push natives');
        await initializeNativePushNotifications();

        console.log('✅ Push notifications initialisées avec succès');
    } catch (error) {
        console.error('❌ Erreur initialisation push notifications:', error);
    }
};

/**
 * Hook de composition pour les notifications push
 */
export function usePushNotifications() {
    // Initialiser automatiquement au montage
    onMounted(() => {
        initializePushNotifications();
    });

    return {
        isRegistered,
        permissionStatus,
        deviceToken,
        initializePushNotifications,
        sendTokenToBackend,
    };
}

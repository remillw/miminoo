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

// Variable pour éviter les initialisations multiples
let isInitializing = false;
let initializationComplete = false;

/**
 * Import dynamique de Capacitor Push Notifications
 */
const importPushNotifications = async () => {
    try {
        console.log('🔍 Vérification environnement Capacitor...');
        console.log('🔧 window.Capacitor:', (window as any).Capacitor);
        console.log('🔧 isNativePlatform:', (window as any).Capacitor?.isNativePlatform?.());
        console.log('🔧 getPlatform:', (window as any).Capacitor?.getPlatform?.());

        // Vérifier si Capacitor est disponible
        if (!(window as any).Capacitor) {
            console.log('⚠️ Capacitor non disponible (environnement web)');
            return null;
        }

        // Vérifier si on est sur une plateforme native
        const isNative = (window as any).Capacitor?.isNativePlatform?.();
        if (!isNative) {
            console.log('🌐 Pas sur plateforme native, mais Capacitor présent');
            console.log("🔧 Tentative d'import PushNotifications quand même...");
        }

        // Import dynamique pour éviter l'erreur sur web
        const { PushNotifications } = await import('@capacitor/push-notifications');
        console.log('✅ PushNotifications importé avec succès');
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
    // Vérifier si déjà en cours d'initialisation ou terminé
    if (isInitializing) {
        console.log('⚠️ Initialisation déjà en cours, skip...');
        return;
    }

    // TEMPORAIRE: désactiver le check d'initialisation terminée pour debug
    // if (initializationComplete) {
    //     console.log('✅ Initialisation déjà terminée, skip...');
    //     return;
    // }

    try {
        console.log('🚀 Début initializeNativePushNotifications...');
        isInitializing = true;

        // Import dynamique de PushNotifications
        console.log('🔄 Étape 1: Import PushNotifications...');
        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            console.log('❌ Échec import PushNotifications, arrêt initialisation');
            return;
        }
        console.log('✅ Étape 1 terminée: PushNotifications importé');

        console.log('🔔 Initialisation des notifications push natives...');

        // Vérifier les permissions actuelles
        console.log('🔄 Étape 2: Vérification des permissions...');
        console.log('📋 Vérification des permissions...');
        const permissionCheck = await PushNotifications.checkPermissions();
        console.log('📋 Permissions actuelles:', JSON.stringify(permissionCheck, null, 2));
        console.log('✅ Étape 2 terminée: Permissions vérifiées');

        permissionStatus.value = permissionCheck.receive;

        if (permissionCheck.receive === 'prompt' || permissionCheck.receive === 'prompt-with-rationale') {
            // Demander les permissions
            console.log('🔄 Étape 3: Demande de permissions...');
            console.log('🔐 Demande de permissions...');
            const permissionRequest = await PushNotifications.requestPermissions();
            console.log('✅ Réponse permissions:', JSON.stringify(permissionRequest, null, 2));
            permissionStatus.value = permissionRequest.receive;
            console.log('✅ Étape 3 terminée: Permissions demandées');
        } else {
            console.log('⏭️ Étape 3 sautée: Permissions déjà accordées');
        }

        console.log('🔍 Statut final permissions:', permissionStatus.value);

        if (permissionStatus.value === 'granted') {
            console.log("✅ Permissions accordées, tentative d'enregistrement...");

            // Configurer les listeners AVANT l'enregistrement
            console.log('🔄 Étape 4: Configuration des listeners...');
            setupPushNotificationListeners(PushNotifications);
            console.log('✅ Étape 4 terminée: Listeners configurés');

            // Enregistrer pour les notifications
            console.log('🔄 Étape 5: Enregistrement pour notifications...');
            console.log('📝 Appel PushNotifications.register()...');
            await PushNotifications.register();
            console.log('✅ Enregistrement pour notifications effectué');
            console.log('✅ Étape 5 terminée: Enregistrement effectué');
            isRegistered.value = true;
        } else {
            console.log('❌ Permissions non accordées:', permissionStatus.value);
            console.log("⏹️ Arrêt de l'initialisation: permissions requises");
        }

        initializationComplete = true;
        console.log('🎯 Initialisation push notifications terminée avec succès');
    } catch (error) {
        console.error('❌ Erreur initialisation push notifications:', error);
        console.error('Stack trace:', error instanceof Error ? error.stack : 'No stack trace');
    } finally {
        isInitializing = false;
        console.log('🏁 Finally: isInitializing mis à false');
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

        const response = await fetch('/device-token', {
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

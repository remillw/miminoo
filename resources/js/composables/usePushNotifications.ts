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
    // Vérifier si déjà en cours d'initialisation
    if (isInitializing) {
        console.log('⚠️ Initialisation déjà en cours, skip...');
        return;
    }

    // Si on a déjà un token, pas besoin de réinitialiser (sauf si on force)
    if (deviceToken.value) {
        console.log('✅ Token déjà disponible:', deviceToken.value.substring(0, 20) + '...');
        return;
    }

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

        // Configurer les listeners EN PREMIER
        console.log('🔄 Étape 2: Configuration des listeners...');
        setupPushNotificationListeners(PushNotifications);
        console.log('✅ Étape 2 terminée: Listeners configurés');

        // Vérifier les permissions actuelles
        console.log('🔄 Étape 3: Vérification des permissions...');
        console.log('📋 Vérification des permissions...');
        const permissionCheck = await PushNotifications.checkPermissions();
        console.log('📋 Permissions actuelles:', JSON.stringify(permissionCheck, null, 2));
        console.log('✅ Étape 3 terminée: Permissions vérifiées');

        permissionStatus.value = permissionCheck.receive;

        if (permissionCheck.receive === 'prompt' || permissionCheck.receive === 'prompt-with-rationale') {
            // Demander les permissions
            console.log('🔄 Étape 4: Demande de permissions...');
            console.log('🔐 Demande de permissions...');
            const permissionRequest = await PushNotifications.requestPermissions();
            console.log('✅ Réponse permissions:', JSON.stringify(permissionRequest, null, 2));
            permissionStatus.value = permissionRequest.receive;
            console.log('✅ Étape 4 terminée: Permissions demandées');
        } else {
            console.log('⏭️ Étape 4 sautée: Permissions déjà accordées');
        }

        console.log('🔍 Statut final permissions:', permissionStatus.value);

        if (permissionStatus.value === 'granted') {
            console.log("✅ Permissions accordées, tentative d'enregistrement...");

            // Enregistrer pour les notifications
            console.log('🔄 Étape 5: Enregistrement pour notifications...');
            console.log('📝 Appel PushNotifications.register()...');

            try {
                const registerResult = await PushNotifications.register();
                console.log('✅ PushNotifications.register() retourné:', registerResult);
                console.log('✅ Enregistrement pour notifications effectué');
                console.log('✅ Étape 5 terminée: Enregistrement effectué');
                isRegistered.value = true;

                // Essayer de récupérer un token existant si disponible
                console.log('🔄 Étape 6: Vérification token existant...');
                await checkExistingToken(PushNotifications);

                // Attendre un peu pour voir si les listeners se déclenchent
                setTimeout(() => {
                    console.log('⏰ Timeout 3s: Vérification token après registration');
                    console.log('📱 Device token actuel:', deviceToken.value);
                    if (!deviceToken.value) {
                        console.log('⚠️ Aucun token reçu après 3 secondes - tentative de récupération manuelle');
                        // Essayer une nouvelle fois de récupérer le token
                        checkExistingToken(PushNotifications);
                    }
                }, 3000);
            } catch (registerError) {
                console.error('❌ Erreur lors du register():', registerError);
                throw registerError;
            }
        } else {
            console.log('❌ Permissions non accordées:', permissionStatus.value);
            console.log("⏹️ Arrêt de l'initialisation: permissions requises");
        }

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
 * Vérifier s'il existe déjà un token (pour gérer les cas où le token est généré avant les listeners)
 */
const checkExistingToken = async (PushNotifications: any): Promise<void> => {
    try {
        console.log('🔍 Vérification token existant...');

        // Tenter d'obtenir les notifications livrées (peut contenir des infos de token)
        const deliveredNotifications = await PushNotifications.getDeliveredNotifications();
        console.log('📱 Notifications livrées:', deliveredNotifications);

        // Sur iOS, essayer de re-déclencher l'enregistrement pour forcer la génération d'événement
        console.log('🔄 Re-déclenchement registration pour forcer le token...');
        await PushNotifications.register();
    } catch (error) {
        console.log('⚠️ Erreur lors de la vérification du token existant:', error);
    }
};

/**
 * Configurer les listeners pour les notifications
 */
const setupPushNotificationListeners = (PushNotifications: any) => {
    console.log('🔧 Configuration des listeners push notifications...');

    // Listener pour le token de registration
    console.log('📝 Ajout listener: registration');
    PushNotifications.addListener('registration', (token: any) => {
        console.log('🎯 Token reçu via listener registration:', JSON.stringify(token, null, 2));
        console.log('🔑 Token value:', token.value);
        deviceToken.value = token.value;
        sendTokenToBackend(token.value);
    });

    // Listener pour les erreurs
    console.log('📝 Ajout listener: registrationError');
    PushNotifications.addListener('registrationError', (error: any) => {
        console.error('❌ Erreur registration détaillée:', JSON.stringify(error, null, 2));
        console.error('❌ Message erreur:', error.message || error);
    });

    // Listener pour les notifications reçues (app en premier plan)
    console.log('📝 Ajout listener: pushNotificationReceived');
    PushNotifications.addListener('pushNotificationReceived', (notification: any) => {
        console.log('📱 Notification reçue:', notification);
    });

    // Listener pour les notifications cliquées
    console.log('📝 Ajout listener: pushNotificationActionPerformed');
    PushNotifications.addListener('pushNotificationActionPerformed', (notification: any) => {
        console.log('👆 Notification cliquée:', notification);
        handleNotificationOpened(notification);
    });

    console.log('✅ Tous les listeners push notifications configurés');
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
            fullToken: token, // Debug: afficher token complet temporairement
        });

        // Récupérer le CSRF token depuis la meta tag ou Inertia
        let csrfToken = (usePage().props as any).csrf_token;

        // Si pas de token depuis Inertia, essayer depuis les meta tags
        if (!csrfToken) {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            csrfToken = metaToken ? metaToken.getAttribute('content') : null;
        }

        const platform = (window as any).Capacitor?.getPlatform() || 'unknown';

        console.log('🔧 Données envoi:', {
            url: '/device-token',
            platform,
            csrfToken: csrfToken ? 'Present' : 'Missing',
            csrfTokenValue: csrfToken, // Debug: voir token complet
            notification_provider: 'capacitor',
        });

        // Utiliser fetch avec gestion CSRF pour mobile
        const response = await fetch('/device-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                Accept: 'application/json',
            },
            credentials: 'include', // Important pour les cookies de session
            body: JSON.stringify({
                device_token: token,
                platform: platform,
                notification_provider: 'capacitor',
            }),
        });

        console.log('📥 Réponse backend:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            url: response.url,
        });

        if (response.ok) {
            const responseData = await response.json();
            console.log('✅ Device token envoyé avec succès au backend:', responseData);
        } else {
            const errorData = await response.text();
            console.error('❌ Erreur envoi token au backend:', {
                status: response.status,
                statusText: response.statusText,
                errorData,
                url: response.url,
            });
            throw new Error(`Failed to save token: ${response.status} ${response.statusText}`);
        }
    } catch (error) {
        console.error("❌ Erreur lors de l'envoi du token:", error);
    }
};

/**
 * Initialiser automatiquement les notifications push
 */
const initializePushNotifications = async (forceReinit: boolean = false): Promise<void> => {
    try {
        // Utiliser Capacitor Push Notifications natif uniquement
        console.log('🔔 Initialisation automatique des notifications push natives');

        if (forceReinit) {
            console.log('🔄 Force reinit: reset des variables de contrôle');
            isInitializing = false;
            deviceToken.value = null;
        }

        await initializeNativePushNotifications();

        console.log('✅ Push notifications initialisées avec succès');
    } catch (error) {
        console.error('❌ Erreur initialisation push notifications:', error);
    }
};

/**
 * Test manuel pour sauvegarder un token fictif (debug uniquement)
 */
const testTokenSaving = async (): Promise<void> => {
    console.log('🧪 Test manuel: envoi token fictif pour debug');
    const fakeToken = 'test_token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    await sendTokenToBackend(fakeToken);
};

/**
 * Forcer la réinitialisation des notifications push (debug)
 */
const forceReinitPushNotifications = async (): Promise<void> => {
    console.log('🔄 Force réinitialisation des push notifications');
    await initializePushNotifications(true);
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
        mobile_auth: 'true', // Flag pour indiquer que c'est un login mobile
    };
};

/**
 * Envoyer le token de façon intégrée au login
 */
const sendTokenWithLogin = (formData: any) => {
    if (deviceToken.value) {
        const tokenData = getDeviceTokenData();
        console.log('🔗 Intégration token au login:', tokenData);
        return { ...formData, ...tokenData };
    }

    // Si pas de token, marquer quand même comme mobile auth
    return {
        ...formData,
        mobile_auth: 'true',
        platform: (window as any).Capacitor?.getPlatform() || 'unknown',
    };
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
        testTokenSaving, // Pour debug uniquement
        forceReinitPushNotifications, // Pour debug uniquement
        getDeviceTokenData,
        sendTokenWithLogin,
    };
}

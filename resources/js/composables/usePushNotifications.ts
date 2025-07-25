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
let listenersConfigured = false;

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
 * Initialiser les notifications push avec Capacitor natif selon la documentation officielle
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
        console.log('🚀 Début initializeNativePushNotifications selon documentation Capacitor...');
        isInitializing = true;

        // Import dynamique de PushNotifications
        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            console.log('❌ Échec import PushNotifications, arrêt initialisation');
            return;
        }

        // ÉTAPE 1: Configurer les listeners AVANT toute opération (documentation Capacitor)
        console.log('🔄 Étape 1: Configuration des listeners...');
        setupPushNotificationListeners(PushNotifications);
        console.log('✅ Étape 1 terminée: Listeners configurés');

        // ÉTAPE 2: Vérifier les permissions actuelles
        console.log('🔄 Étape 2: Vérification des permissions...');
        const permissionCheck = await PushNotifications.checkPermissions();
        console.log('📋 Permissions actuelles:', JSON.stringify(permissionCheck, null, 2));
        permissionStatus.value = permissionCheck.receive;
        console.log('✅ Étape 2 terminée: Permissions vérifiées');

        // ÉTAPE 3: Demander les permissions si nécessaire
        if (permissionCheck.receive === 'prompt' || permissionCheck.receive === 'prompt-with-rationale') {
            console.log('🔄 Étape 3: Demande de permissions...');
            const permissionRequest = await PushNotifications.requestPermissions();
            console.log('✅ Réponse permissions:', JSON.stringify(permissionRequest, null, 2));
            permissionStatus.value = permissionRequest.receive;
            console.log('✅ Étape 3 terminée: Permissions demandées');
        } else {
            console.log('⏭️ Étape 3 sautée: Permissions déjà accordées');
        }

        // S'enregistrer si permissions accordées
        if (permissionStatus.value === 'granted') {
            // Utiliser le plugin natif sur iOS pour éviter les problèmes de timing
            if ((window as any).Capacitor?.getPlatform() === 'ios') {
                console.log('📱 iOS détecté - Utilisation du plugin natif');
                try {
                    // @ts-expect-error - Plugin natif custom
                    const result = await (window as any).Capacitor.Plugins.PushNotificationPlugin.initializeFirebasePushNotifications();
                    console.log('✅ Plugin natif iOS appelé avec succès');
                } catch (nativeError) {
                    console.log('⚠️ Plugin natif échoué, fallback vers Capacitor standard');
                    await PushNotifications.register();
                }
            } else {
                await PushNotifications.register();
            }
            isRegistered.value = true;
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
 * Appeler la méthode native pour récupérer le token FCM
 */
const callNativeGetToken = async (): Promise<string | null> => {
    try {
        console.log('🔥 Méthode alternative de récupération token...');

        // Méthode simple : re-enregistrement forcé
        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            console.log('❌ PushNotifications non disponible');
            return null;
        }

        if (!listenersConfigured) {
            setupPushNotificationListeners(PushNotifications);
        }

        await PushNotifications.register();

        return new Promise((resolve) => {
            const timeout = setTimeout(() => {
                resolve(deviceToken.value);
            }, 5000);

            const checkToken = () => {
                if (deviceToken.value) {
                    clearTimeout(timeout);
                    resolve(deviceToken.value);
                } else {
                    setTimeout(checkToken, 500);
                }
            };

            checkToken();
        });
    } catch (error) {
        return null;
    }
};

/**
 * Récupérer le token FCM directement depuis le côté natif
 */
const getFirebaseTokenDirect = async (): Promise<string | null> => {
    try {

        // Option 1: Utiliser @capacitor-community/fcm si disponible
        try {
            // @ts-expect-error - Le plugin FCM peut ne pas être disponible au build
            const { FCM } = await import('@capacitor-community/fcm');

            const result = await FCM.getToken();
            if (result && result.token) {
                deviceToken.value = result.token;
                await sendTokenToBackend(result.token);
                return result.token;
            }
        } catch (fcmError) {
            // Fallback vers Capacitor standard
        }

        const PushNotifications = await importPushNotifications();
        if (!PushNotifications) {
            return null;
        }

        const permissions = await PushNotifications.checkPermissions();

        if (permissions.receive === 'granted') {

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
        } else {
            return null;
        }
    } catch (error) {
        return null;
    }
};

/**
 * Configurer les listeners pour les notifications selon la documentation Capacitor
 */
const setupPushNotificationListeners = (PushNotifications: any) => {
    if (listenersConfigured) {
        console.log('⚠️ Listeners déjà configurés, skip...');
        return;
    }

    console.log('🔧 Configuration des listeners push notifications selon doc Capacitor...');

    // Listener pour le token de registration - PRIORITAIRE selon documentation
    console.log('📝 Ajout listener: registration');
    PushNotifications.addListener('registration', (token: any) => {
        console.log('🎯 Token reçu via listener registration:', JSON.stringify(token, null, 2));
        console.log('🌐 Token FCM natif :', token.value);
        
        // Sauvegarder le token
        deviceToken.value = token.value;
        
        // Envoyer au backend SEULEMENT après avoir reçu le token
        console.log('📤 Envoi du token via NotificationCenter officiel Capacitor');
        sendTokenToBackend(token.value);
    });

    // Listener pour les erreurs de registration
    console.log('📝 Ajout listener: registrationError');
    PushNotifications.addListener('registrationError', (error: any) => {
        console.error('❌ Erreur registration push notifications:', JSON.stringify(error, null, 2));
        console.error('❌ Message erreur:', error.message || error);
    });

    // Listener pour les notifications reçues (app en premier plan)
    console.log('📝 Ajout listener: pushNotificationReceived');
    PushNotifications.addListener('pushNotificationReceived', (notification: any) => {
        console.log('📱 Notification reçue en premier plan:', notification);
    });

    // Listener pour les notifications cliquées/ouvertes
    console.log('📝 Ajout listener: pushNotificationActionPerformed');
    PushNotifications.addListener('pushNotificationActionPerformed', (notification: any) => {
        console.log('👆 Action sur notification:', notification);
        handleNotificationOpened(notification);
    });

    listenersConfigured = true;
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
            listenersConfigured = false;
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
        getFirebaseTokenDirect, // Récupération directe FCM
        callNativeGetToken, // Appel méthode native
        getDeviceTokenData,
        sendTokenWithLogin,
    };
}

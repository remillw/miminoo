import { onMounted, onUnmounted, readonly, ref } from 'vue';

export function useDeviceToken() {
    const deviceToken = ref<string | null>(null);
    const platform = ref<string | null>(null);
    const notificationProvider = ref<string>('expo'); // Expo par défaut

    let messageHandler: ((event: MessageEvent) => void) | null = null;

    /**
     * Écouter les messages de la WebView Expo
     */
    const listenForDeviceToken = () => {
        messageHandler = (event: MessageEvent) => {
            try {
                // Pour Expo, les messages arrivent via postMessage
                const message = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

                if (message.type === 'DEVICE_TOKEN_RESPONSE') {
                    deviceToken.value = message.token;
                    platform.value = message.platform;
                    notificationProvider.value = message.notification_provider || 'expo';

                    console.log('useDeviceToken: Token reçu de Expo:', {
                        platform: platform.value,
                        provider: notificationProvider.value,
                        tokenPreview: deviceToken.value?.substring(0, 30) + '...',
                    });
                }
            } catch (error) {
                console.error('useDeviceToken: Erreur parsing message:', error);
            }
        };

        // Écouter les messages postMessage
        window.addEventListener('message', messageHandler);

        // Écouter l'événement personnalisé émis par le JavaScript injecté
        const deviceTokenReadyHandler = (event: CustomEvent) => {
            const data = event.detail;
            if (data && data.device_token) {
                deviceToken.value = data.device_token;
                platform.value = data.platform;
                notificationProvider.value = data.notification_provider || 'expo';

                console.log('useDeviceToken: Token reçu via événement custom:', {
                    platform: platform.value,
                    provider: notificationProvider.value,
                    tokenPreview: deviceToken.value?.substring(0, 30) + '...',
                });
            }
        };

        window.addEventListener('deviceTokenReady', deviceTokenReadyHandler as EventListener);

        return () => {
            if (messageHandler) {
                window.removeEventListener('message', messageHandler);
            }
            window.removeEventListener('deviceTokenReady', deviceTokenReadyHandler as EventListener);
        };
    };

    /**
     * Demander le device token à l'app Expo
     */
    const requestDeviceToken = () => {
        try {
            // Méthode 1: Via ReactNativeWebView (prioritaire)
            if (window.ReactNativeWebView) {
                const message = { type: 'REQUEST_DEVICE_TOKEN' };
                window.ReactNativeWebView.postMessage(JSON.stringify(message));
                console.log('useDeviceToken: Demande de token via ReactNativeWebView');
                return;
            }

            // Méthode 2: Via fonction injectée
            if (typeof (window as any).requestDeviceToken === 'function') {
                (window as any).requestDeviceToken();
                console.log('useDeviceToken: Demande de token via fonction injectée');
                return;
            }

            console.log('useDeviceToken: Pas dans une WebView Expo, token non demandé');
        } catch (error) {
            console.error('useDeviceToken: Erreur lors de la demande de token:', error);
        }
    };

    /**
     * Vérifier si on est dans une app mobile
     */
    const isMobileApp = (): boolean => {
        return !!(window.ReactNativeWebView || (window as any).requestDeviceToken);
    };

    /**
     * Obtenir les données du device token pour les formulaires
     */
    const getDeviceTokenData = () => {
        if (!deviceToken.value) return null;

        return {
            device_token: deviceToken.value,
            platform: platform.value || 'unknown',
            notification_provider: notificationProvider.value,
        };
    };

    onMounted(() => {
        const cleanup = listenForDeviceToken();

        // Vérifier d'abord si le token est déjà disponible
        if ((window as any).deviceTokenData) {
            const data = (window as any).deviceTokenData;
            deviceToken.value = data.device_token;
            platform.value = data.platform;
            notificationProvider.value = data.notification_provider || 'expo';
            console.log('useDeviceToken: Token déjà disponible au montage');
        } else {
            // Demander le token après un délai
            setTimeout(() => {
                requestDeviceToken();
            }, 100);
        }

        onUnmounted(() => {
            cleanup();
        });
    });

    return {
        deviceToken: readonly(deviceToken),
        platform: readonly(platform),
        notificationProvider: readonly(notificationProvider),
        isMobileApp,
        getDeviceTokenData,
        requestDeviceToken,
    };
}

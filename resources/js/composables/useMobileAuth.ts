import { ref } from 'vue';
import { route } from 'ziggy-js';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Authentification Google
     */
    const authenticateWithGoogle = async (deviceTokenData?: any) => {
        if (isAuthenticating.value) {
            return;
        }

        try {
            isAuthenticating.value = true;
            authError.value = null;

            // Construire l'URL d'authentification avec les paramètres du device token si disponibles
            let authUrl = route('google.redirect');
            
            if (deviceTokenData) {
                const params = new URLSearchParams({
                    device_token: deviceTokenData.device_token,
                    platform: deviceTokenData.platform,
                    notification_provider: deviceTokenData.notification_provider,
                    mobile_auth: 'true'
                });
                authUrl += '?' + params.toString();
                
                console.log('Google Auth: Ajout des paramètres device token:', {
                    platform: deviceTokenData.platform,
                    provider: deviceTokenData.notification_provider,
                    tokenPreview: deviceTokenData.device_token.substring(0, 20) + '...'
                });
            }

            // Redirection pour authentification
            window.location.href = authUrl;
        } catch (error) {
            console.error('Erreur authentification:', error);
            isAuthenticating.value = false;
            authError.value = "Erreur lors de l'authentification";
        }
    };

    const clearError = () => {
        authError.value = null;
        isAuthenticating.value = false;
    };

    return {
        isAuthenticating,
        authError,
        authenticateWithGoogle,
        clearError,
    };
}

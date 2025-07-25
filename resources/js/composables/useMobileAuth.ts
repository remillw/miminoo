import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { usePushNotifications } from './usePushNotifications';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);
    const { getDeviceTokenData } = usePushNotifications();

    /**
     * Authentification Google pour mobile et web
     */
    const authenticateWithGoogle = async () => {
        // Protection contre les appels multiples
        if (isAuthenticating.value) {
            return;
        }

        try {
            isAuthenticating.value = true;
            authError.value = null;

            if (Capacitor.isNativePlatform()) {
                // Récupérer les données du device token
                const tokenData = getDeviceTokenData();
                
                // Utiliser la fonction route() pour générer l'URL avec le token FCM
                const authUrl = route('google.redirect', { 
                    mobile: '1',
                    ...tokenData 
                });

                // Utiliser le navigateur intégré
                await Browser.open({
                    url: authUrl,
                    windowName: '_blank',
                });

                // Écouter les événements de retour à l'app
                const handleAppResume = () => {
                    // Petite pause pour laisser les callbacks se traiter
                    setTimeout(async () => {
                        try {
                            await Browser.close();
                        } catch (error) {
                            // Navigateur déjà fermé
                        }

                        if (isAuthenticating.value) {
                            isAuthenticating.value = false;
                        }
                    }, 1000);
                };

                // Écouter la visibilité de la page pour détecter le retour
                const handleVisibilityChange = () => {
                    if (!document.hidden && isAuthenticating.value) {
                        handleAppResume();
                    }
                };

                document.addEventListener('visibilitychange', handleVisibilityChange);
                window.addEventListener('focus', handleAppResume);

                // Timeout de sécurité
                setTimeout(() => {
                    if (isAuthenticating.value) {
                        isAuthenticating.value = false;

                        // Nettoyer les listeners
                        document.removeEventListener('visibilitychange', handleVisibilityChange);
                        window.removeEventListener('focus', handleAppResume);

                        // Tenter de fermer le navigateur
                        Browser.close().catch(() => {});
                    }
                }, 60000);
            } else {
                // Pour web : redirection normale
                const authUrl = route('google.redirect');
                window.location.href = authUrl;
            }
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

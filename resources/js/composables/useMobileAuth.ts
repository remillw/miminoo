import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { ref } from 'vue';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Authentification Google pour mobile et web
     */
    const authenticateWithGoogle = async () => {
        console.log('🚀 Démarrage authentification Google...');

        try {
            isAuthenticating.value = true;
            authError.value = null;

            // Construire l'URL d'authentification
            const authUrl = new URL('/auth/google', window.location.origin);

            if (Capacitor.isNativePlatform()) {
                console.log('📱 Mode mobile détecté');
                authUrl.searchParams.set('mobile', '1');

                // Pour mobile : ouvrir dans navigateur externe
                await Browser.open({
                    url: authUrl.toString(),
                    windowName: '_system',
                });
                console.log('✅ Navigateur externe ouvert');
            } else {
                console.log('🌐 Mode web - redirection normale');
                // Pour web : redirection normale
                window.location.href = authUrl.toString();
            }
        } catch (error) {
            console.error('❌ Erreur authentification:', error);
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

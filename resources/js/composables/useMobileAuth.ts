import { Capacitor } from '@capacitor/core';
import { ref } from 'vue';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Ouvrir l'authentification Google avec méthode simplifiée
     */
    const authenticateWithGoogle = async () => {
        console.log('🚀 Démarrage authentification Google mobile...');

        if (!Capacitor.isNativePlatform()) {
            console.log('📱 Non-native platform, redirection web normale');
            window.location.href = '/auth/google';
            return;
        }

        try {
            isAuthenticating.value = true;
            authError.value = null;

            console.log('📱 Plateforme native détectée:', Capacitor.getPlatform());

            // Construire l'URL d'authentification avec paramètre mobile
            const authUrl = new URL('/auth/google', window.location.origin);
            authUrl.searchParams.set('mobile', '1');

            console.log('🔄 URL Google à ouvrir:', authUrl.toString());

            // MÉTHODE ULTRA-SIMPLIFIÉE : Utiliser window.open au lieu du plugin Browser
            console.log('🌐 Ouverture avec window.open...');
            const authWindow = window.open(authUrl.toString(), '_system');

            if (authWindow) {
                console.log("✅ Fenêtre d'authentification ouverte");
            } else {
                console.log("❌ Impossible d'ouvrir la fenêtre d'authentification");
                throw new Error("Impossible d'ouvrir la fenêtre d'authentification");
            }

            // Timeout de sécurité (60 secondes)
            setTimeout(() => {
                if (isAuthenticating.value) {
                    console.log('⏰ Timeout authentification (60s)');
                    isAuthenticating.value = false;
                    authError.value = "Timeout d'authentification - Veuillez réessayer";
                }
            }, 60000);
        } catch (error) {
            console.error('❌ Erreur authentification Google:', error);
            isAuthenticating.value = false;

            // Gestion d'erreur plus spécifique
            if (error instanceof Error) {
                authError.value = `Erreur: ${error.message}`;
            } else {
                authError.value = "Erreur inconnue lors de l'authentification";
            }
        }
    };

    /**
     * Marquer l'authentification comme terminée (appelé par useCapacitor)
     */
    const completeAuthentication = () => {
        console.log('✅ Authentification mobile terminée');
        isAuthenticating.value = false;
        authError.value = null;
    };

    /**
     * Marquer l'authentification comme échouée
     */
    const failAuthentication = (error: string) => {
        console.log('❌ Authentification mobile échouée:', error);
        isAuthenticating.value = false;
        authError.value = error;
    };

    /**
     * Réinitialiser l'état d'erreur
     */
    const clearError = () => {
        authError.value = null;
    };

    /**
     * Annuler l'authentification en cours
     */
    const cancelAuthentication = () => {
        console.log('🚫 Annulation authentification');
        isAuthenticating.value = false;
    };

    return {
        isAuthenticating,
        authError,
        authenticateWithGoogle,
        completeAuthentication,
        failAuthentication,
        clearError,
        cancelAuthentication,
    };
}

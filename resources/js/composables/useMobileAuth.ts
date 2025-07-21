import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { ref } from 'vue';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Ouvrir l'authentification Google pour mobile avec gestion d'erreurs améliorée
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

            console.log('🔄 Ouverture URL Google dans navigateur externe:', authUrl.toString());

            // MÉTHODE SIMPLIFIÉE : Ouvrir simplement le navigateur externe
            // Le callback sera géré par le composable useCapacitor
            await Browser.open({
                url: authUrl.toString(),
                windowName: '_system',
            });

            console.log('✅ Navigateur externe ouvert');

            // Timeout de sécurité plus long (60 secondes)
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
    const cancelAuthentication = async () => {
        console.log('🚫 Annulation authentification');
        isAuthenticating.value = false;

        try {
            await Browser.close();
        } catch {
            // Ignorer l'erreur si le navigateur n'est pas ouvert
            console.log('ℹ️ Navigateur déjà fermé');
        }
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

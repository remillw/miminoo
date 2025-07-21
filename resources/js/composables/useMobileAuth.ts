import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { ref } from 'vue';
import { route } from 'ziggy-js';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Authentification Google pour mobile et web
     */
    const authenticateWithGoogle = async () => {
        // Protection contre les appels multiples
        if (isAuthenticating.value) {
            console.log('⚠️ Authentification déjà en cours, ignorée');
            return;
        }

        console.log('🚀 Démarrage authentification Google...');

        try {
            isAuthenticating.value = true;
            authError.value = null;

            if (Capacitor.isNativePlatform()) {
                console.log('📱 Mode mobile détecté');

                // Utiliser la fonction route() pour générer l'URL
                const authUrl = route('google.redirect', { mobile: '1' });

                console.log('🔗 URL générée avec Ziggy:', authUrl);

                // Pour mobile : ouvrir dans navigateur externe
                await Browser.open({
                    url: authUrl,
                    windowName: '_system',
                });
                console.log('✅ Navigateur externe ouvert');

                // Le flag isAuthenticating sera remis à false quand l'app reviendra au premier plan
                // ou après un timeout
                setTimeout(() => {
                    if (isAuthenticating.value) {
                        console.log('⏰ Timeout authentification, remise à zéro');
                        isAuthenticating.value = false;
                    }
                }, 30000); // 30 secondes
            } else {
                console.log('🌐 Mode web - redirection normale');
                const authUrl = route('google.redirect');
                console.log('🔗 URL de redirection web:', authUrl);
                // Pour web : redirection normale
                window.location.href = authUrl;
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

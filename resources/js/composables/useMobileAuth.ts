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

                // Utiliser le navigateur intégré
                await Browser.open({
                    url: authUrl,
                    windowName: '_blank', // Navigateur intégré
                });
                console.log('✅ Navigateur intégré ouvert');

                // Écouter les événements de retour à l'app
                const handleAppResume = () => {
                    console.log("📱 App revenue au premier plan - possible fin d'auth");

                    // Petite pause pour laisser les callbacks se traiter
                    setTimeout(async () => {
                        try {
                            // Tenter de fermer le navigateur au cas où il serait encore ouvert
                            await Browser.close();
                            console.log('🔧 Navigateur fermé automatiquement');
                        } catch (error) {
                            console.log('ℹ️ Navigateur déjà fermé ou erreur:', error);
                        }

                        // Réinitialiser le flag d'authentification
                        if (isAuthenticating.value) {
                            console.log("🔄 Remise à zéro du flag d'authentification");
                            isAuthenticating.value = false;
                        }
                    }, 1000);
                };

                // Écouter la visibilité de la page pour détecter le retour
                const handleVisibilityChange = () => {
                    if (!document.hidden && isAuthenticating.value) {
                        console.log('👀 Page visible - App probablement revenue');
                        handleAppResume();
                    }
                };

                document.addEventListener('visibilitychange', handleVisibilityChange);
                window.addEventListener('focus', handleAppResume);

                // Timeout de sécurité
                setTimeout(() => {
                    if (isAuthenticating.value) {
                        console.log('⏰ Timeout authentification, remise à zéro');
                        isAuthenticating.value = false;

                        // Nettoyer les listeners
                        document.removeEventListener('visibilitychange', handleVisibilityChange);
                        window.removeEventListener('focus', handleAppResume);

                        // Tenter de fermer le navigateur
                        Browser.close().catch(() => {});
                    }
                }, 60000); // 60 secondes
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

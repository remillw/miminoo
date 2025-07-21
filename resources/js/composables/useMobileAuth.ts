import { App } from '@capacitor/app';
import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useMobileAuth() {
    const isAuthenticating = ref(false);
    const authError = ref<string | null>(null);

    /**
     * Ouvrir l'authentification Google pour mobile
     */
    const authenticateWithGoogle = async () => {
        if (!Capacitor.isNativePlatform()) {
            console.log('Not on native platform, using regular web auth');
            window.location.href = '/auth/google';
            return;
        }

        try {
            isAuthenticating.value = true;
            authError.value = null;

            console.log('🚀 Démarrage authentification Google mobile...');

            // Configurer le listener pour le callback AVANT d'ouvrir le navigateur
            const removeListener = await App.addListener('appUrlOpen', handleAuthCallback);

            // Construire l'URL d'authentification avec paramètre mobile
            const authUrl = new URL('/auth/google', window.location.origin);
            authUrl.searchParams.set('mobile', '1');

            console.log('🔄 Ouverture URL Google:', authUrl.toString());

            // Ouvrir l'authentification dans le navigateur système
            await Browser.open({
                url: authUrl.toString(),
                windowName: '_system', // Utiliser le navigateur système
            });

            console.log('✅ Navigateur ouvert pour authentification');

            // Timeout de sécurité (30 secondes)
            setTimeout(() => {
                if (isAuthenticating.value) {
                    console.log('⏰ Timeout authentification');
                    cleanupAuth(removeListener);
                    authError.value = "Timeout d'authentification";
                }
            }, 30000);
        } catch (error) {
            console.error('❌ Erreur authentification Google:', error);
            authError.value = `Erreur d'authentification: ${error}`;
            isAuthenticating.value = false;
        }
    };

    /**
     * Gérer le callback d'authentification
     */
    const handleAuthCallback = async (event: any) => {
        console.log('🔗 Callback URL reçu:', event.url);

        try {
            const url = new URL(event.url);

            // Vérifier si c'est notre callback d'auth
            if (url.pathname === '/auth/callback') {
                const success = url.searchParams.get('success');

                if (success === '1') {
                    console.log('✅ Authentification réussie!');

                    // Fermer le navigateur
                    await Browser.close();

                    // Rediriger vers le tableau de bord avec les paramètres pour les device tokens
                    router.visit('/tableau-de-bord?mobile_auth=success&register_device_token=1', {
                        onSuccess: () => {
                            console.log('🏠 Redirection vers tableau de bord terminée');
                            isAuthenticating.value = false;
                        },
                        onError: (errors) => {
                            console.error('❌ Erreur redirection:', errors);
                            authError.value = 'Erreur lors de la redirection';
                            isAuthenticating.value = false;
                        },
                    });
                } else {
                    console.log('❌ Authentification échouée');
                    authError.value = 'Authentification échouée';
                    isAuthenticating.value = false;
                }
            }
        } catch (error) {
            console.error('❌ Erreur traitement callback:', error);
            authError.value = `Erreur de callback: ${error}`;
            isAuthenticating.value = false;
        }
    };

    /**
     * Nettoyer l'état d'authentification
     */
    const cleanupAuth = async (removeListener?: () => void) => {
        isAuthenticating.value = false;

        if (removeListener) {
            removeListener();
        }

        try {
            await Browser.close();
        } catch (error) {
            // Ignorer l'erreur si le navigateur n'est pas ouvert
        }
    };

    /**
     * Réinitialiser l'état d'erreur
     */
    const clearError = () => {
        authError.value = null;
    };

    return {
        isAuthenticating,
        authError,
        authenticateWithGoogle,
        clearError,
    };
}

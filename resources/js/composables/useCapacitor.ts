import { App } from '@capacitor/app';
import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { router } from '@inertiajs/vue3';

export function useCapacitor() {
    /**
     * Vérifie si l'application s'exécute dans un environnement Capacitor (mobile)
     */
    const isCapacitor = Capacitor.isNativePlatform();

    /**
     * Vérifie si l'application s'exécute sur iOS
     */
    const isIOS = Capacitor.getPlatform() === 'ios';

    /**
     * Vérifie si l'application s'exécute sur Android
     */
    const isAndroid = Capacitor.getPlatform() === 'android';

    /**
     * Ouvre une URL dans le navigateur en utilisant le plugin Browser de Capacitor
     * Cela maintient l'utilisateur dans l'application au lieu d'ouvrir le navigateur système
     */
    const openInAppBrowser = async (url: string) => {
        if (isCapacitor) {
            await Browser.open({
                url,
                windowName: '_blank',
            });
        } else {
            // Fallback pour le web
            window.location.href = url;
        }
    };

    /**
     * Configure le listener pour intercepter les custom URL schemes
     */
    const setupAppUrlListener = () => {
        if (isCapacitor) {
            App.addListener('appUrlOpen', (event) => {
                console.log('🔗 URL interceptée:', event.url);

                // Vérifier si c'est notre URL d'auth callback
                if (event.url.startsWith('trouvetababysitter://auth/callback')) {
                    console.log('✅ Authentification réussie, redirection vers dashboard...');

                    // Fermer le navigateur ouvert
                    Browser.close();

                    // Rediriger vers le dashboard
                    router.visit('/tableau-de-bord');
                }
            });
        }
    };

    /**
     * Navigue vers une URL d'authentification Google de manière appropriée
     * selon l'environnement (mobile vs web)
     */
    const navigateToGoogleAuth = async (googleAuthUrl: string) => {
        if (isCapacitor) {
            // Ajouter un paramètre pour identifier les requêtes mobiles
            const url = new URL(googleAuthUrl, window.location.origin);
            url.searchParams.set('mobile', '1');

            console.log('🔄 Ouverture URL Google dans navigateur externe:', url.toString());

            // Configurer le listener avant d'ouvrir le navigateur
            setupAppUrlListener();

            // Ouvrir l'authentification Google dans un navigateur externe
            await Browser.open({
                url: url.toString(),
                windowName: '_blank',
            });
        } else {
            // Sur web, navigation normale
            window.location.href = googleAuthUrl;
        }
    };

    /**
     * Configure les headers pour les requêtes Axios/HTTP quand on est dans Capacitor
     */
    const setupMobileHeaders = () => {
        if (isCapacitor && (window as any).axios) {
            // Ajouter un header personnalisé pour identifier l'app mobile
            (window as any).axios.defaults.headers.common['X-Capacitor-App'] = 'true';
            (window as any).axios.defaults.headers.common['X-App-Platform'] = Capacitor.getPlatform();
        }
    };

    return {
        isCapacitor,
        isIOS,
        isAndroid,
        openInAppBrowser,
        navigateToGoogleAuth,
        setupMobileHeaders,
        setupAppUrlListener,
    };
}

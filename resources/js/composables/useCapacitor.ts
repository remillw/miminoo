import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';

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
                windowName: '_self',
                presentationStyle: 'popover',
            });
        } else {
            // Fallback pour le web
            window.location.href = url;
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
            
            // Sur mobile, on utilise le navigateur intégré
            await Browser.open({
                url: url.toString(),
                windowName: '_self',
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
        if (isCapacitor && window.axios) {
            // Ajouter un header personnalisé pour identifier l'app mobile
            window.axios.defaults.headers.common['X-Capacitor-App'] = 'true';
            window.axios.defaults.headers.common['X-App-Platform'] = Capacitor.getPlatform();
        }
    };

    return {
        isCapacitor,
        isIOS,
        isAndroid,
        openInAppBrowser,
        navigateToGoogleAuth,
        setupMobileHeaders
    };
}

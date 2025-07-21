import { App } from '@capacitor/app';
import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';
import { router } from '@inertiajs/vue3';

// Variable pour éviter les listeners multiples
let listenerSetup = false;

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
     * ✅ AMÉLIORATION: Éviter les listeners multiples
     */
    const setupAppUrlListener = () => {
        if (isCapacitor && !listenerSetup) {
            listenerSetup = true;
            
            App.addListener('appUrlOpen', (event) => {
                console.log('🔗 URL interceptée dans l\'app:', event.url);

                // Vérifier si c'est notre URL d'auth callback
                if (event.url.startsWith('trouvetababysitter://auth/callback')) {
                    console.log('✅ Callback d\'authentification détecté!');
                    
                    // Attendre un peu pour laisser le temps au browser de se fermer
                    setTimeout(async () => {
                        try {
                            // Fermer le navigateur ouvert s'il existe
                            await Browser.close().catch(() => {
                                console.log('ℹ️ Navigateur déjà fermé ou non ouvert');
                            });
                            
                            console.log('🔄 Redirection vers le dashboard...');
                            
                            // Rediriger vers le dashboard avec rechargement complet
                            router.visit('/tableau-de-bord', {
                                method: 'get',
                                preserveState: false,
                                preserveScroll: false,
                                replace: true
                            });
                        } catch (error) {
                            console.error('❌ Erreur lors de la redirection:', error);
                            // Fallback: utiliser window.location
                            window.location.href = '/tableau-de-bord';
                        }
                    }, 500);
                }

                // Gestion d'autres types de deep links si nécessaire
                if (event.url.startsWith('trouvetababysitter://')) {
                    console.log('🔗 Deep link détecté:', event.url);
                    // Ici vous pouvez ajouter d'autres handlers pour différents deep links
                }
            });
            
            console.log('✅ Listener appUrlOpen configuré');
        } else if (listenerSetup) {
            console.log('ℹ️ Listener déjà configuré, évitement de doublons');
        }
    };

    /**
     * Navigue vers une URL d'authentification Google de manière appropriée
     * selon l'environnement (mobile vs web)
     * ✅ AMÉLIORATION: Ne pas reconfigurer le listener à chaque fois
     */
    const navigateToGoogleAuth = async (googleAuthUrl: string) => {
        if (isCapacitor) {
            // Ajouter un paramètre pour identifier les requêtes mobiles
            const url = new URL(googleAuthUrl, window.location.origin);
            url.searchParams.set('mobile', '1');

            console.log('🔄 Ouverture URL Google dans navigateur externe:', url.toString());

            // Le listener est déjà configuré au démarrage de l'app
            // Pas besoin de le reconfigurer ici

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

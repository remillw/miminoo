import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

// Variables pour les imports dynamiques
let App: any = null;
let Browser: any = null;
let Capacitor: any = null;

// Variable globale pour éviter les multiples initialisations
let isCapacitorInitialized = false;

// Détection synchrone de la plateforme native au chargement du module
const detectNativePlatform = (): boolean => {
    if (typeof window === 'undefined') return false;

    const windowCapacitor = (window as any).Capacitor;
    if (!windowCapacitor) return false;

    try {
        return windowCapacitor.isNativePlatform();
    } catch {
        return false;
    }
};

// Initialisation synchrone des valeurs
const initialIsNative = detectNativePlatform();
const initialPlatform = initialIsNative && (window as any).Capacitor ? (window as any).Capacitor.getPlatform() : '';

console.log('🔍 Détection plateforme native:', {
    isNative: initialIsNative,
    platform: initialPlatform,
    hasWindowCapacitor: !!(window as any)?.Capacitor,
});

export function useCapacitor() {
    const isNative = ref(initialIsNative);
    const platform = ref(initialPlatform);
    const appStateChangeListener = ref<any>(null);
    const appUrlOpenListener = ref<any>(null);

    /**
     * Gestion des deep links entrants
     */
    const handleAppUrlOpen = (event: any) => {
        console.log('🔗 Deep link reçu:', event.url);

        try {
            const url = new URL(event.url);
            console.log('📍 URL parsée:', {
                scheme: url.protocol,
                host: url.hostname,
                pathname: url.pathname,
                search: url.search,
            });

            if (url.pathname === '/auth/callback') {
                handleAuthCallback(url);
            } else {
                console.log('🔗 Deep link non géré:', url.pathname);
            }
        } catch (error) {
            console.error('❌ Erreur parsing deep link:', error);
        }
    };

    /**
     * Gestion du callback d'authentification
     */
    const handleAuthCallback = async (url: URL) => {
        const success = url.searchParams.get('success');
        console.log("🔐 Callback d'authentification détecté, success:", success);

        // Fermer le navigateur intégré AVANT la redirection
        try {
            await Browser.close();
            console.log('✅ Navigateur fermé avec succès');
        } catch (error) {
            console.warn('⚠️ Impossible de fermer le navigateur:', error);
        }

        // Petite pause pour laisser le navigateur se fermer
        setTimeout(() => {
            if (success === '1') {
                console.log('🏠 Redirection vers dashboard avec params mobile');
                router.visit('/tableau-de-bord?mobile_auth=success&register_device_token=1', {
                    replace: true,
                    preserveState: false,
                });
            } else {
                console.log('❌ Redirection vers login avec erreur');
                router.visit('/connexion?error=auth_failed');
            }
        }, 100);
    };

    /**
     * Fermer le navigateur intégré (pour usage externe)
     */
    const closeBrowser = async () => {
        try {
            await Browser.close();
            console.log('✅ Navigateur fermé manuellement');
            return true;
        } catch (error) {
            console.warn('⚠️ Impossible de fermer le navigateur:', error);
            return false;
        }
    };

    /**
     * Initialiser les modules Capacitor via les objets globaux
     */
    const loadCapacitorModules = async () => {
        // Vérifier d'abord si window.Capacitor existe (injecté dans la WebView)
        if (typeof window === 'undefined' || !(window as any).Capacitor) {
            console.log('🌐 Environnement web détecté, skip Capacitor');
            return false;
        }

        try {
            // Utiliser les objets globaux injectés par Capacitor
            Capacitor = (window as any).Capacitor;
            App = (window as any).CapacitorApp || Capacitor.Plugins?.App;
            Browser = (window as any).CapacitorBrowser || Capacitor.Plugins?.Browser;

            if (!App || !Browser) {
                console.log('⚠️ Plugins Capacitor non disponibles');
                return false;
            }

            console.log('📱 Capacitor chargé, plateforme native détectée:', Capacitor.getPlatform());
            return true;
        } catch (error) {
            console.log('❌ Erreur chargement modules Capacitor:', error);
            return false;
        }
    };

    /**
     * Initialisation de Capacitor
     */
    const initializeCapacitor = async () => {
        if (isCapacitorInitialized) {
            console.log('⚠️ Capacitor déjà initialisé, skip');
            return;
        }

        // Charger les modules d'abord
        const modulesLoaded = await loadCapacitorModules();
        if (!modulesLoaded) {
            console.log('🌐 Pas sur plateforme native, skip init Capacitor');
            return;
        }

        if (!Capacitor.isNativePlatform()) {
            console.log('🌐 Pas sur plateforme native, skip init Capacitor');
            return;
        }

        try {
            console.log('🚀 Initialisation Capacitor...');
            isCapacitorInitialized = true;

            // Les valeurs sont déjà définies au niveau du module, mais on les confirme
            if (!isNative.value) {
                isNative.value = true;
                console.log('✅ isNative mis à jour vers true');
            }
            
            if (!platform.value) {
                platform.value = Capacitor.getPlatform();
                console.log('✅ Platform mis à jour vers:', platform.value);
            }

            console.log('📱 Plateforme confirmée:', platform.value);

            // Écouter les changements d'état de l'app
            appStateChangeListener.value = await App.addListener('appStateChange', (state: any) => {
                console.log('📱 App state changed:', state.isActive);
            });

            // Écouter les deep links
            appUrlOpenListener.value = await App.addListener('appUrlOpen', handleAppUrlOpen);

            console.log('✅ Capacitor initialisé avec succès');
        } catch (error) {
            console.error('❌ Erreur initialisation Capacitor:', error);
            isCapacitorInitialized = false;
        }
    };

    /**
     * Nettoyage des listeners
     */
    const cleanupCapacitor = () => {
        if (!isCapacitorInitialized) return;

        try {
            if (appStateChangeListener.value) {
                appStateChangeListener.value.remove();
                appStateChangeListener.value = null;
            }

            if (appUrlOpenListener.value) {
                appUrlOpenListener.value.remove();
                appUrlOpenListener.value = null;
            }

            console.log('🧹 Listeners Capacitor nettoyés');
        } catch (error) {
            console.error('❌ Erreur nettoyage Capacitor:', error);
        }
    };

    // Initialisation au montage
    onMounted(async () => {
        await initializeCapacitor();
    });

    // Nettoyage au démontage
    onUnmounted(() => {
        cleanupCapacitor();
    });

    return {
        isNative,
        platform,
        closeBrowser, // Exposer la fonction de fermeture
        initializeCapacitor,
        cleanupCapacitor,
    };
}

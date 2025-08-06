import { computed, ref } from 'vue';

/**
 * Composable pour détecter si on est dans l'application mobile native (WebView)
 * vs le site web responsive
 */
export function useMobileApp() {
    const isMobileApp = ref(false);

    // Détecter si on est dans l'app mobile via le User Agent ou des paramètres spécifiques
    const detectMobileApp = () => {
        // Méthode 1: Via User Agent personnalisé de votre app Expo
        const userAgent = navigator.userAgent.toLowerCase();
        const isExpoApp = userAgent.includes('expo') || userAgent.includes('trouvetababysitter');

        // Méthode 2: Via paramètre URL ou localStorage pour forcer le mode app
        const urlParams = new URLSearchParams(window.location.search);
        const isForcedApp = urlParams.get('app') === 'true' || localStorage.getItem('mobileApp') === 'true';

        // Méthode 3: Via une variable globale que votre app Expo peut définir
        const isExpoGlobal = (window as any).isExpoApp === true;

        isMobileApp.value = isExpoApp || isForcedApp || isExpoGlobal;

        console.log('🔍 Mobile App Detection:', {
            userAgent,
            isExpoApp,
            isForcedApp,
            isExpoGlobal,
            finalResult: isMobileApp.value,
        });

        return isMobileApp.value;
    };

    // Fonction pour forcer le mode app (utile pour les tests)
    const enableMobileAppMode = () => {
        localStorage.setItem('mobileApp', 'true');
        isMobileApp.value = true;
    };

    // Fonction pour désactiver le mode app
    const disableMobileAppMode = () => {
        localStorage.removeItem('mobileApp');
        isMobileApp.value = false;
    };

    // Computed pour savoir si on doit utiliser le layout mobile
    const shouldUseMobileLayout = computed(() => {
        return isMobileApp.value;
    });

    // Computed pour savoir si on doit cacher header/footer
    const shouldHideHeaderFooter = computed(() => {
        return isMobileApp.value;
    });

    return {
        isMobileApp: computed(() => isMobileApp.value),
        shouldUseMobileLayout,
        shouldHideHeaderFooter,
        detectMobileApp,
        enableMobileAppMode,
        disableMobileAppMode,
    };
}

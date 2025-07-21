import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

export function useCapacitor() {
    const isNative = ref(Capacitor.isNativePlatform());
    const platform = ref(Capacitor.getPlatform());

    /**
     * Gérer les URL d'entrée dans l'app (deep links)
     */
    const handleAppUrlOpen = (event: any) => {
        console.log('🔗 Deep link reçu:', event.url);

        try {
            // Parser l'URL reçue
            const url = new URL(event.url);
            console.log('📍 URL parsée:', {
                scheme: url.protocol,
                host: url.hostname,
                pathname: url.pathname,
                search: url.search,
            });

            // Gérer les callbacks d'authentification
            if (url.pathname === '/auth/callback') {
                const success = url.searchParams.get('success');

                console.log("🔐 Callback d'authentification détecté, success:", success);

                if (success === '1') {
                    console.log('✅ Authentification réussie via deep link');

                    // Rediriger vers le tableau de bord avec flag pour déclencher l'enregistrement du device token
                    router.visit('/tableau-de-bord?mobile_auth=success&register_device_token=1', {
                        onSuccess: () => {
                            console.log('🏠 Redirection vers tableau de bord terminée');
                        },
                        onError: (errors) => {
                            console.error('❌ Erreur redirection:', errors);
                        },
                    });
                } else {
                    console.log('❌ Authentification échouée via deep link');
                    router.visit('/connexion?error=auth_failed');
                }
            } else {
                console.log('🔗 Deep link non géré:', url.pathname);
            }
        } catch (error) {
            console.error('❌ Erreur parsing deep link:', error);
        }
    };

    /**
     * Initialiser les listeners Capacitor
     */
    const initializeCapacitor = () => {
        if (!isNative.value) {
            console.log('📱 Non-native platform, skipping Capacitor initialization');
            return;
        }

        console.log('🚀 Initialisation des listeners Capacitor...');

        // Écouter les URLs d'entrée (deep links)
        App.addListener('appUrlOpen', handleAppUrlOpen);

        // Écouter les changements d'état de l'app
        App.addListener('appStateChange', (state) => {
            console.log('📱 App state changed:', state.isActive ? 'active' : 'background');
        });

        // Log de l'état initial
        App.getInfo().then((info) => {
            console.log('📋 App Info:', info);
        });

        console.log('✅ Listeners Capacitor configurés');
    };

    /**
     * Nettoyer les listeners
     */
    const cleanupCapacitor = () => {
        if (!isNative.value) return;

        console.log('🧹 Nettoyage des listeners Capacitor...');
        App.removeAllListeners();
    };

    // Initialiser automatiquement
    onMounted(() => {
        initializeCapacitor();
    });

    // Nettoyer lors du démontage
    onUnmounted(() => {
        cleanupCapacitor();
    });

    return {
        isNative,
        platform,
        initializeCapacitor,
        cleanupCapacitor,
        handleAppUrlOpen, // Exposer pour les tests
    };
}

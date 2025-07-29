import '../css/app.css';

import { Toaster } from '@/components/ui/sonner';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { waitForEcho } from './echo';
import { router } from '@inertiajs/vue3';
import { useGlobalErrorHandler } from './composables/useGlobalErrorHandler';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');
        const page = await resolvePageComponent(`./pages/${name}.vue`, pages);

        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () =>
                h('div', [
                    h(App, props),
                    h(Toaster, {
                        position: 'bottom-right',
                        expand: false,
                        richColors: false,
                        closeButton: true,
                        theme: 'light',
                        offset: 0,
                        visibleToasts: 5,
                        duration: 4000,
                        gap: 12,
                        invert: false,
                        toastOptions: {
                            style: {
                                position: 'fixed',
                                bottom: '20px',
                                right: '20px',
                                zIndex: 999999,
                                transform: 'none',
                                margin: 0,
                                padding: 0,
                            },
                        },
                    }),
                ]),
        });

        app.use(plugin).use(ZiggyVue).mount(el);

        // Installer le gestionnaire global d'erreurs
        const { installGlobalHandlers, isSessionExpiredError } = useGlobalErrorHandler();
        installGlobalHandlers();

        // Gestionnaire global d'erreurs Inertia pour les sessions expirées
        router.on('error', (errors) => {
            console.log('🔍 Erreur Inertia capturée:', errors);
            
            // Convertir l'erreur en format utilisable
            const errorData = {
                message: typeof errors === 'string' ? errors : JSON.stringify(errors),
                status: 500,
                data: errors
            };
            
            // Vérifier si c'est une erreur de session expirée ou Route [login] not defined
            if (isSessionExpiredError(errorData)) {
                console.log('🚨 Session expirée détectée, redirection vers login');
                import('./composables/useToast').then(({ useToast }) => {
                    const { handleAuthError } = useToast();
                    handleAuthError();
                });
                return;
            }
        });

        // Les listeners Capacitor sont maintenant gérés automatiquement
        // par le composable useCapacitor dans les layouts

        // Rendre Echo disponible globalement seulement côté client
        if (typeof window !== 'undefined') {
            waitForEcho().then((resolvedEcho) => {
                window.Echo = resolvedEcho;
                console.log('✅ Echo chargé dans app.ts', resolvedEcho);
            });
        }
    },
    progress: {
        color: '#4B5563',
    },
});

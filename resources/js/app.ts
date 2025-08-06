import '../css/app.css';

import { Toaster } from '@/components/ui/sonner';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { useGlobalErrorHandler } from './composables/useGlobalErrorHandler';
import { waitForEcho } from './echo';

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

        // Gestionnaire global d'erreurs Inertia pour les sessions expirées uniquement
        router.on('error', (errors) => {
            console.log('🔍 Erreur Inertia capturée:', errors);

            // Ne traiter que les vraies erreurs d'authentification
            // Ignorer les autres erreurs pour permettre la gestion normale des flash messages
            if (typeof errors === 'string' || (errors && typeof errors === 'object')) {
                const errorData = {
                    message: typeof errors === 'string' ? errors : JSON.stringify(errors),
                    status: 500,
                    data: errors,
                };

                // Vérifier si c'est spécifiquement une erreur de session expirée
                if (isSessionExpiredError(errorData)) {
                    console.log('🚨 Session expirée détectée, redirection vers login');
                    import('./composables/useToast').then(({ useToast }) => {
                        const { handleAuthError } = useToast();
                        handleAuthError();
                    });
                    return;
                }
            }

            // Laisser les autres erreurs être gérées normalement par Inertia
            console.log('ℹ️ Erreur non liée à la session, gestion normale');
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

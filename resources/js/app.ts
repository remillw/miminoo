import '../css/app.css';

import { Toaster } from '@/components/ui/sonner';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { waitForEcho } from './echo';
import { useCapacitor } from '@/composables/useCapacitor';

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

        // Configurer les headers mobiles et listeners si on est dans Capacitor
        if (typeof window !== 'undefined') {
            const { setupMobileHeaders, setupAppUrlListener, isCapacitor } = useCapacitor();
            setupMobileHeaders();
            
            // ✅ IMPORTANT: Configurer le listener dès le démarrage de l'app
            if (isCapacitor) {
                setupAppUrlListener();
                console.log('🔧 Listener URL scheme configuré au démarrage');
            }
        }

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



import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Helper pour gérer les routes Ziggy côté serveur
const createSafeRoute = (ziggyData: any) => {
    return (name: string, params?: any) => {
        try {
            if (!ziggyData?.routes?.[name]) {
                console.warn(`Route "${name}" not found in SSR context, using fallback`);
                switch (name) {
                    case 'dashboard':
                        return '/tableau-de-bord';
                    case 'creer.une.annonce':
                        return '/creer-une-annonce';
                    case 'messaging.index':
                        return '/messagerie';
                    case 'announcements.index':
                        return '/annonces';
                    case 'profil':
                        return '/profil';
                    case 'parent.announcements-reservations':
                        return '/mes-annonces-et-reservations';
                    case 'home':
                        return '/';
                    default:
                        return '#';
                }
            }

            const routeConfig = ziggyData.routes[name];
            if (routeConfig && routeConfig.uri) {
                let url = routeConfig.uri;
                if (params && typeof params === 'object') {
                    Object.keys(params).forEach((key) => {
                        url = url.replace(`{${key}}`, params[key]);
                    });
                }
                return url.startsWith('http') ? url : `/${url.replace(/^\//, '')}`;
            }

            return '#';
        } catch (error) {
            console.warn(`Error generating route "${name}":`, error);
            return '#';
        }
    };
};

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => `${title} - ${appName}`,
            resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue', { eager: true }) as any),
            setup({ App, props, plugin }) {
                const app = createSSRApp({ render: () => h(App, props) });

                app.use(plugin);

                const ziggyData = props.initialPage?.props?.ziggy || {};
                const safeRoute = createSafeRoute(ziggyData);

                app.config.globalProperties.route = safeRoute as any;
                app.provide('route', safeRoute);

                return app;
            },
        }),
    {
        port: 13715, // 👈 Port SSR explicitement défini ici
    },
);

// resources/js/echo.ts

import Echo from 'laravel-echo';

declare global {
    interface Window {
        Pusher: any;
        Echo: Echo;
    }
}

let echo: Echo | null = null;

let echoPromise: Promise<Echo> | null = null;

if (typeof window !== 'undefined') {
    echoPromise = import('pusher-js')
        .then(({ default: Pusher }) => {
            window.Pusher = Pusher;

            const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

            echo = new Echo({
                broadcaster: 'pusher', // ✅ même pour Reverb
                key: 'bhdonn8eanhd6h1txapi',
                cluster: '', // vide car c'est Reverb, pas Pusher
                wsHost: isLocal ? 'localhost' : 'trouvetababysitter.fr',
                wsPort: isLocal ? 8080 : 443,
                wssPort: isLocal ? 8080 : 443,
                wsPath: '/reverb',
                forceTLS: !isLocal,
                enabledTransports: ['wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            });

            // Debug
            echo.connector.pusher.connection.bind('connected', () => {
                console.log('🟢 Echo connecté');
            });
            echo.connector.pusher.connection.bind('disconnected', () => {
                console.warn('🔴 Echo déconnecté');
            });
            echo.connector.pusher.connection.bind('error', (err: any) => {
                console.error('❌ Erreur Echo :', err);
            });

            window.Echo = echo;
            return echo;
        })
        .catch((e) => {
            console.error('💥 Erreur chargement Echo:', e);
            return null;
        });
}

export const waitForEcho = (): Promise<Echo | null> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

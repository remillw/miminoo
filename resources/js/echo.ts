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
                broadcaster: 'reverb',
                key: 'bhdonn8eanhd6h1txapi',
                wsHost: isLocal ? 'localhost' : 'trouvetababysitter.fr',
                wsPort: isLocal ? 8080 : 443,
                wssPort: isLocal ? 8080 : 443,
                wsPath: '/reverb',
                forceTLS: !isLocal,
                enabledTransports: ['websocket'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            });

            waitForConnectionEstablished(echo);
            window.Echo = echo;
            return echo;
        })
        .catch((e) => {
            console.error('💥 Erreur chargement Echo:', e);
            return null;
        });
}

/**
 * Attend que la connexion Echo soit bien établie pour debugger proprement
 */
function waitForConnectionEstablished(echoInstance: Echo, retry = 0): void {
    const maxRetries = 10;
    const connector = echoInstance.connector;
    const connection = connector?.pusher?.connection;

    if (connection?.state === 'connected') {
        console.log('🟢 Echo connecté');
        console.log('🔧 Connector:', connector?.name);
        console.log('🔧 State:', connection?.state);
        return;
    }

    if (retry >= maxRetries) {
        console.warn('❌ Echo non connecté après plusieurs tentatives');
        return;
    }

    setTimeout(() => {
        waitForConnectionEstablished(echoInstance, retry + 1);
    }, 500);
}

export const waitForEcho = (): Promise<Echo | null> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

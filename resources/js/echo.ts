import Echo from 'laravel-echo';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}

let echo: any = null;
let echoPromise: Promise<any> | null = null;

if (typeof window !== 'undefined') {
    echoPromise = import('pusher-js')
        .then(({ default: Pusher }) => {
            window.Pusher = Pusher;

            const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

            const config = {
                broadcaster: 'reverb',
                key: 'bhdonn8eanhd6h1txapi',
                wsHost: isLocal ? 'localhost' : 'trouvetababysitter.fr',
                wsPort: isLocal ? 8080 : 443,
                wssPort: isLocal ? 8080 : 443,
                wsPath: '/reverb',
                forceTLS: !isLocal,
                enabledTransports: isLocal ? ['ws'] : ['wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            };

            console.log('🔧 Configuration Echo utilisée:', config);
            echo = new Echo(config as any);

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
function waitForConnectionEstablished(echoInstance: any, retry = 0): void {
    const maxRetries = 10;
    const connector = echoInstance.connector;
    const connection = connector?.pusher?.connection;

    console.log(`🔍 [Tentative ${retry + 1}/${maxRetries}] État de la connexion:`, {
        connector: connector?.name,
        state: connection?.state,
        pusher: !!connector?.pusher,
        connection: !!connection,
        readyState: connection?.readyState,
        url: connection?.transport?.url || 'N/A',
    });

    if (connection?.state === 'connected') {
        console.log('🟢 Echo connecté avec succès !');
        console.log('🔧 Connector:', connector?.name);
        console.log('🔧 State:', connection?.state);
        console.log('🔧 URL:', connection?.transport?.url);

        // Ajouter les listeners d'événements globaux
        connection.bind('connected', () => console.log('🎉 Événement connected reçu'));
        connection.bind('disconnected', () => console.log('🔴 Événement disconnected reçu'));
        connection.bind('error', (err: any) => console.error('❌ Événement error reçu:', err));

        return;
    }

    if (retry >= maxRetries) {
        console.warn('❌ Echo non connecté après plusieurs tentatives');
        console.warn('🔧 Dernière info connection:', {
            connector: connector?.name,
            state: connection?.state,
            pusher: !!connector?.pusher,
            connection: !!connection,
        });
        return;
    }

    setTimeout(() => {
        waitForConnectionEstablished(echoInstance, retry + 1);
    }, 500);
}

export const waitForEcho = (): Promise<any> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

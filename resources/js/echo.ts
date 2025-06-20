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
                broadcaster: 'pusher', // 👈 HACK nécessaire pour Echo avec Reverb
                key: 'bhdonn8eanhd6h1txapi',
                cluster: '', // 👈 Cluster vide requis pour Pusher (ignoré par Reverb)
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
            };

            console.log('🔧 Configuration Echo utilisée:', config);

            // Important : cast vers any pour éviter mauvaise détection TS
            const EchoConstructor = Echo as any;
            echo = new EchoConstructor(config);

            window.Echo = echo;

            // Attendre le temps que `connector` se setup (car avec Reverb c’est asynchrone)
            waitForConnectionEstablished();

            return echo;
        })
        .catch((e) => {
            console.error('💥 Erreur chargement Echo:', e);
            return null;
        });
}

function waitForConnectionEstablished(retry = 0) {
    const maxRetries = 10;
    const connector = window.Echo?.connector as any;
    const connection = connector?.pusher?.connection;

    console.log(`🔍 [Tentative ${retry + 1}/${maxRetries}] Connexion :`, {
        connector: connector?.name,
        state: connection?.state,
        readyState: connection?.readyState,
        url: connection?.transport?.url ?? 'N/A',
    });

    if (connection?.state === 'connected') {
        console.log('✅ Echo connecté à Reverb');
        connection.bind?.('connected', () => console.log('🎉 Connecté'));
        connection.bind?.('disconnected', () => console.warn('🔴 Déconnecté'));
        connection.bind?.('error', (err: any) => console.error('❌ Erreur Echo :', err));
        return;
    }

    if (retry >= maxRetries) {
        console.warn('❌ Echo non connecté après plusieurs tentatives');
        return;
    }

    setTimeout(() => {
        waitForConnectionEstablished(retry + 1);
    }, 500);
}

export const waitForEcho = (): Promise<any> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

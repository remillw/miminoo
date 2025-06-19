import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Déclaration pour TypeScript
declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

// Vérifier si on est côté client (pas en SSR)
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

// Fonction pour obtenir le token CSRF de manière sécurisée
const getCsrfToken = (): string => {
    if (typeof window === 'undefined') return '';
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
};

// Configuration Reverb
const getReverbConfig = () => {
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    // Configuration par défaut selon l'environnement
    const config = {
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'bhdonn8eanhd6h1txapi',
        wsHost: isLocal ? 'localhost' : window.location.hostname,
        wsPort: isLocal ? 8080 : 443,
        wssPort: isLocal ? 8080 : 443,
        forceTLS: !isLocal, // TLS seulement en production
        enabledTransports: isLocal ? ['ws'] : ['wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    };

    console.log('🔧 Configuration Reverb:', {
        environment: isLocal ? 'local' : 'production',
        wsHost: config.wsHost,
        wsPort: config.wsPort,
        forceTLS: config.forceTLS,
        transports: config.enabledTransports,
        key: config.key,
    });

    return config;
};

// Créer Echo seulement côté client
let echo: any = null;

if (typeof window !== 'undefined') {
    try {
        const config = getReverbConfig();

        console.log('🚀 Initialisation Echo...');
        echo = new Echo(config as any);

        // Logs avec typage correct
        if (echo.connector && 'pusher' in echo.connector) {
            const pusherConnector = echo.connector as any;

            console.log('🔧 Connector trouvé:', pusherConnector.name || 'unknown');

            pusherConnector.pusher?.connection?.bind('state_change', (states: any) => {
                console.log("🔄 Changement d'état Reverb:", states.previous + ' → ' + states.current);
            });

            pusherConnector.pusher?.connection?.bind('connected', () => {
                console.log('🟢 Echo Reverb CONNECTÉ!');
            });

            pusherConnector.pusher?.connection?.bind('connecting', () => {
                console.log('🟡 Echo Reverb en cours de connexion...');
            });

            pusherConnector.pusher?.connection?.bind('disconnected', () => {
                console.log('🔴 Echo Reverb DÉCONNECTÉ!');
            });

            pusherConnector.pusher?.connection?.bind('error', (error: any) => {
                console.error('❌ Erreur connexion Reverb:', error);
            });

            pusherConnector.pusher?.connection?.bind('failed', () => {
                console.error('💥 Connexion Reverb ÉCHOUÉE!');
            });
        } else {
            console.error('❌ Aucun connector Pusher trouvé!');
        }
    } catch (error) {
        console.error("💥 Erreur lors de l'initialisation d'Echo:", error);
    }
}

export default echo;

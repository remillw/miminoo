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

// Créer Echo seulement côté client
let echo: any = null;

if (typeof window !== 'undefined') {
    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'babysitter-key',
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT || 443,
        wssPort: import.meta.env.VITE_REVERB_PORT || 443,
        wsPath: '/reverb', // 👈 AJOUT ICI
        forceTLS: true,
        enabledTransports: ['wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });


    // Logs avec typage correct
    if (echo.connector && 'pusher' in echo.connector) {
        const pusherConnector = echo.connector as any;

        pusherConnector.pusher?.connection?.bind('connected', () => {
            console.log('🟢 Echo Reverb CONNECTÉ!');
        });

        pusherConnector.pusher?.connection?.bind('disconnected', () => {
            console.log('🔴 Echo Reverb DÉCONNECTÉ!');
        });

        pusherConnector.pusher?.connection?.bind('error', (error: any) => {
            console.error('❌ Erreur connexion Reverb:', error);
        });
    }
}

export default echo;

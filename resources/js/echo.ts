import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Injection globale
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

// Récupération du CSRF
const getCsrfToken = (): string => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// Configuration d’Echo avec Reverb
const echo = new Echo({
    broadcaster: 'pusher',
    key: 'bhdonn8eanhd6h1txapi', // Ton REVERB_APP_KEY
    cluster: '', // important : doit être une string vide ou undefined (pas "undefined")
    wsHost: 'trouvetababysitter.fr',
    wsPort: 443,
    wssPort: 443,
    wsPath: '/reverb',
    forceTLS: true,
    enabledTransports: ['wss'],
    disableStats: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
});

// Debug
if (echo.connector?.pusher?.connection) {
    echo.connector.pusher.connection.bind('connected', () => {
        console.log('🟢 Echo Reverb CONNECTÉ !');
    });
    echo.connector.pusher.connection.bind('disconnected', () => {
        console.log('🔴 Echo Reverb DÉCONNECTÉ !');
    });
    echo.connector.pusher.connection.bind('error', (err: any) => {
        console.error('❌ Erreur Reverb :', err);
    });
}

export default echo;

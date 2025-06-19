import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Injection globale Pusher pour Echo
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

// Récupération du CSRF token
const getCsrfToken = (): string => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// Création de l'instance Echo
const echo = new Echo({
    broadcaster: 'pusher', // ✅ même pour Reverb
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT),
    wsPath: '/reverb',
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['wss'], // 'ws' si tu veux aussi http local
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
});

// Logs utiles
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

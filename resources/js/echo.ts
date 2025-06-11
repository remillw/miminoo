import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configuration de Laravel Echo avec Reverb (Pusher-compatible)
window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'miminoo-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
    authEndpoint: '/broadcasting/auth',
});

// Debug de connexion
echo.connector.pusher.connection.bind('connected', () => {
    console.log('🟢 Echo Reverb CONNECTÉ!');
});

echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('🔴 Echo Reverb DÉCONNECTÉ!');
});

echo.connector.pusher.connection.bind('error', (error) => {
    console.error('❌ Erreur connexion Reverb:', error);
});

export default echo; 
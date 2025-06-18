import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'babysitter-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname, // ← domaine auto
    wsPort: import.meta.env.VITE_REVERB_PORT || 443,
    wssPort: import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS: true, // ← obligatoire en HTTPS
    enabledTransports: ['wss'], // ← forcer wss uniquement
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
});

// Logs
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

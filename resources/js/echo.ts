import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // 👈 Import Pusher requis pour Reverb

declare global {
    interface Window {
        Echo: Echo;
        Pusher: any;
    }
}

if (typeof window !== 'undefined') {
    window.Pusher = Pusher; // 👈 Doit être mis avant new Echo

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const appKey = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST;
    const path = `/reverb/app/${appKey}`;

    console.log('🔧 Préparation de Laravel Echo...');
    console.log('🔧 Clé Reverb :', appKey);
    console.log('🔧 Host Reverb :', host);
    console.log('🔧 Path WebSocket :', path);
    console.log('🔧 Token CSRF :', token);

    try {
        window.Echo = new Echo({
            broadcaster: 'pusher', // Reverb émule Pusher
            key: appKey,
            wsHost: host,
            wsPort: 443,
            wssPort: 443,
            wsPath: path,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        });

        console.log('✅ Laravel Echo initialisé avec succès');
        console.log('📡 Echo instance:', window.Echo);
        console.log('📡 Options Echo:', window.Echo?.connector?.options);
        console.log('📡 Connector:', window.Echo?.connector);
    } catch (e) {
        console.error("❌ Erreur lors de l'initialisation de Laravel Echo :", e);
    }
}

export const waitForEcho = (): Promise<Echo> => {
    return new Promise((resolve, reject) => {
        if (typeof window.Echo !== 'undefined') {
            console.log('🟢 Echo prêt à être utilisé');
            resolve(window.Echo);
        } else {
            console.warn('⏳ Echo pas encore prêt');
            reject('Echo non initialisé');
        }
    });
};

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'bhdonn8eanhd6h1txapi',
        cluster: '', // Requis par Pusher mais ignoré par Reverb
        wsHost: 'trouvetababysitter.fr',
        wsPort: 443,
        wssPort: 443,
        wsPath: '/reverb/app/bhdonn8eanhd6h1txapi',
        forceTLS: true,
        enabledTransports: ['websocket'] as any,
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    console.log('✅ Echo (Reverb) initialisé:', window.Echo);
    console.log('🔧 Connector:', window.Echo.connector?.name); // doit être "reverb"
}
export const waitForEcho = (): Promise<any> => {
    return Promise.resolve(window.Echo);
};

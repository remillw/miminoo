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

    const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: 'bhdonn8eanhd6h1txapi',
        wsHost: isLocal ? 'localhost' : 'trouvetababysitter.fr',
        wsPort: isLocal ? 8080 : 443,
        wssPort: isLocal ? 8080 : 443,
        forceTLS: !isLocal,
        enabledTransports: ['websocket'] as any,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    console.log('✅ Echo (Reverb) initialisé:', window.Echo);
    console.log('🔧 Echo.connector:', window.Echo.connector);
    console.log('🔧 Echo.connector.name:', window.Echo.connector?.name);
}

export const waitForEcho = (): Promise<any> => {
    return Promise.resolve(window.Echo);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default typeof window !== 'undefined' ? window.Echo : null;

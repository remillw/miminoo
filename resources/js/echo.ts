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
        broadcaster: 'reverb',
        key: 'bhdonn8eanhd6h1txapi',
        host: 'wss://trouvetababysitter.fr/reverb',
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

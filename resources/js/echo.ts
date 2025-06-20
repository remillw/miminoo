import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo;
    }
}

if (typeof window !== 'undefined') {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: 'bhdonn8eanhd6h1txapi',
        wsHost: 'trouvetababysitter.fr',
        wsPort: 443,
        wssPort: 443,
        wsPath: '/reverb/app/bhdonn8eanhd6h1txapi',
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    console.log('✅ Echo (Reverb) initialisé:', window.Echo);
    console.log('🔧 Echo maintenant disponible:', !!window.Echo);
    console.log('🔧 Echo options:', window.Echo?.connector?.options ?? 'Non défini');
}

export const waitForEcho = (): Promise<Echo> => {
    return Promise.resolve(window.Echo);
};

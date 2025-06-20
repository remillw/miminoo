import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo;
    }
}

if (typeof window !== 'undefined') {
    window.Echo = new Echo({
        broadcaster: 'reverb',
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
}

export const waitForEcho = (): Promise<any> => {
    return Promise.resolve(window.Echo);
};
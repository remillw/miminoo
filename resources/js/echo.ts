import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo;
    }
}

if (typeof window !== 'undefined') {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        host: 'wss://trouvetababysitter.fr/reverb', // 👈 pas de port ici
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    console.log('✅ Echo (Reverb) initialisé:', window.Echo);
    console.log('🔧 Connector:', window.Echo.connector?.name); // doît être "reverb"
}

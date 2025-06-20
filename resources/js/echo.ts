import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: Echo;
    }
}

let echo: Echo | null = null;
let echoPromise: Promise<Echo> | null = null;

if (typeof window !== 'undefined') {
    const config = {
        broadcaster: 'reverb',
        host: 'wss://trouvetababysitter.fr/reverb',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    };

    console.log('🔧 Configuration Reverb native utilisée:', config);

    echo = new Echo(config);
    window.Echo = echo;

    echoPromise = Promise.resolve(echo);

    console.log('✅ Echo Reverb créé:', echo);
    console.log('🔧 Echo.connector:', echo.connector);
    console.log('🔧 Echo.connector.name:', echo.connector?.name);
}

export const waitForEcho = (): Promise<Echo | null> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;
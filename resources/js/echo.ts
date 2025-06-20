import Echo from 'laravel-echo';

declare global {
    interface Window {
        Echo: any;
    }
}

let echo: any = null;
let echoPromise: Promise<any> | null = null;

if (typeof window !== 'undefined') {
    const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

    const config = {
        broadcaster: 'reverb',
        key: 'bhdonn8eanhd6h1txapi',
        wsHost: isLocal ? 'localhost' : 'trouvetababysitter.fr',
        wsPort: isLocal ? 8080 : 443,
        wssPort: isLocal ? 8080 : 443,
        forceTLS: !isLocal,
        enabledTransports: ['websocket'],
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

    console.log('✅ Echo Reverb créé:', echo);
    console.log('🔧 Echo.connector:', echo.connector);
    console.log('🔧 Echo.connector.name:', echo.connector?.name);

    echoPromise = Promise.resolve(echo);
}

export const waitForEcho = (): Promise<any> => {
    return echoPromise || Promise.resolve(null);
};

export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

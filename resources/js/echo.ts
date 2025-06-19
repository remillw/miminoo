// resources/js/echo.ts

import Echo from 'laravel-echo';

// Déclaration TypeScript
declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}

let echo: any = null;

// ✅ Promesse pour attendre que Echo soit prêt
let echoPromise: Promise<any> | null = null;

// ✅ S'assurer d'être côté client
if (typeof window !== 'undefined') {
    echoPromise = import('pusher-js')
        .then(({ default: Pusher }) => {
            window.Pusher = Pusher;

            // Détection environnement
            const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

            if (isLocal) {
                // Configuration locale avec Reverb
                echo = new Echo({
                    broadcaster: 'reverb',
                    key: 'bhdonn8eanhd6h1txapi',
                    wsHost: 'localhost',
                    wsPort: 8080,
                    wssPort: 8080,
                    forceTLS: false,
                    enabledTransports: ['ws'],
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });
            } else {
                // Configuration production avec Pusher
                echo = new Echo({
                    broadcaster: 'pusher',
                    key: import.meta.env.VITE_PUSHER_APP_KEY || 'votre-pusher-key',
                    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu',
                    forceTLS: true,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });
            }

            // 🔧 Debug état de connexion
            echo.connector.pusher.connection.bind('connected', () => {
                console.log('🟢 Echo Reverb CONNECTÉ !');
            });
            echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('🔴 Echo Reverb DÉCONNECTÉ !');
            });
            echo.connector.pusher.connection.bind('error', (err: any) => {
                console.error('❌ Erreur Echo Reverb :', err);
            });

            // ✅ Attacher à window pour accès global
            window.Echo = echo;

            console.log('✅ Echo initialisé et attaché à window.Echo');
            return echo;
        })
        .catch((error) => {
            console.error('💥 Erreur initialisation Echo:', error);
            return null;
        });
}

// ✅ Fonction pour attendre que Echo soit prêt
export const waitForEcho = (): Promise<any> => {
    if (typeof window === 'undefined') {
        return Promise.resolve(null);
    }

    return echoPromise || Promise.resolve(null);
};

// ✅ Fonction pour vérifier si Echo est déjà prêt
export const isEchoReady = (): boolean => {
    return typeof window !== 'undefined' && !!window.Echo;
};

export default echo;

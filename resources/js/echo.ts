import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // 👈 Import Pusher requis pour Reverb

declare global {
    interface Window {
        Echo: Echo<any>;
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
            cluster: 'mt1', // ✅ requis même avec Reverb
            wsPath: path,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                // Forcer l'inclusion des cookies
                withCredentials: true,
            },
            // Configuration supplémentaire pour Pusher
            authorizer: (channel: any) => {
                return {
                    authorize: (socketId: string, callback: (error: any, data: any) => void) => {
                        console.log('🔐 AUTHORIZER APPELÉ:', {
                            channel: channel.name,
                            socketId: socketId,
                            authEndpoint: '/broadcasting/auth',
                        });

                        fetch('/broadcasting/auth', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                            },
                            credentials: 'include', // Forcer l'inclusion des cookies
                            body: JSON.stringify({
                                socket_id: socketId,
                                channel_name: channel.name,
                            }),
                        })
                            .then((response) => {
                                console.log('🔐 AUTH RESPONSE STATUS:', response.status);
                                if (response.ok) {
                                    return response.json();
                                } else {
                                    throw new Error(`Auth failed: ${response.status}`);
                                }
                            })
                            .then((data) => {
                                console.log('🔐 ✅ AUTH SUCCESS:', data);
                                callback(null, data);
                            })
                            .catch((error) => {
                                console.error('🔐 ❌ AUTH ERROR:', error);
                                callback(error, null);
                            });
                    },
                };
            },
        } as any); // Contournement TypeScript

        console.log('✅ Laravel Echo initialisé avec succès');
        console.log('📡 Echo instance:', window.Echo);
        console.log('📡 Options Echo:', window.Echo?.connector?.options);
        console.log('📡 Connector:', window.Echo?.connector);

        // Debug des événements de connexion/authentification
        if (window.Echo?.connector?.pusher) {
            const pusher = window.Echo.connector.pusher;

            pusher.connection.bind('connected', () => {
                console.log('🟢 WebSocket connecté à Reverb');
            });

            pusher.connection.bind('disconnected', () => {
                console.log('🔴 WebSocket déconnecté');
            });

            pusher.connection.bind('error', (error: any) => {
                console.error('❌ Erreur WebSocket:', error);
            });

            // Debug spécifique pour l'authentification des canaux privés
            pusher.bind('pusher:subscription_error', (error: any) => {
                console.error("❌ Erreur d'authentification canal:", error);
                console.error('❌ Status:', error.status);
                console.error('❌ Type:', error.type);
            });

            pusher.bind('pusher:subscription_succeeded', (data: any) => {
                console.log('✅ Authentification canal réussie:', data);
            });
        }
    } catch (e) {
        console.error("❌ Erreur lors de l'initialisation de Laravel Echo :", e);
    }
}

export const waitForEcho = (): Promise<Echo<any>> => {
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

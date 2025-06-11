import Echo from 'laravel-echo';
import { io } from 'socket.io-client';

// Configuration de Laravel Echo avec Socket.io
window.io = io;

const echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001',
    transports: ['websocket', 'polling', 'flashsocket'],
    auth: {
        headers: {
            Authorization: `Bearer ${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')}`,
        },
    },
    authEndpoint: '/broadcasting/auth',
    enabledTransports: ['ws', 'xhr-polling'],
});

export default echo; 
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let sharedEcho = null;
let sharedToken = null;
let sharedRefCount = 0;

export const initEcho = (token) => {
    Pusher.logToConsole = Boolean(import.meta.env?.DEV);
    const isHttps = window.location.protocol === 'https:';
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const defaultWsPort = isLocalhost ? 8085 : 8080;

    if (sharedEcho && sharedToken === token) {
        sharedRefCount += 1;
        return sharedEcho;
    }

    if (sharedEcho && sharedToken !== token) {
        try {
            sharedEcho.disconnect();
        } catch (e) {}
        sharedEcho = null;
        sharedRefCount = 0;
    }

    sharedToken = token;
    sharedRefCount = 1;
    sharedEcho = new Echo({
        broadcaster: 'reverb',
        key: 'eaixn9wuthqzi8mjryhc', // VITE_REVERB_APP_KEY
        // Jika akses via public domain, arahkan websocket host ke domain admin/backend (atau domain poskasir yang sama jika diproxy)
        wsHost: window.location.hostname,
        wsPort: defaultWsPort,
        wssPort: isHttps ? 443 : defaultWsPort, // Production HTTPS biasanya menggunakan port 443 (diproxy Nginx)
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/api/v1/broadcasting/auth', // Sanctum auth endpoint inside api.php
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                'X-Device-UUID': localStorage.getItem('pos_device_uuid') || ''
            }
        }
    });

    return sharedEcho;
};

export const releaseEcho = (instance) => {
    if (!instance) return;
    if (instance === sharedEcho) {
        sharedRefCount = Math.max(0, sharedRefCount - 1);
        if (sharedRefCount === 0) {
            try {
                sharedEcho.disconnect();
            } catch (e) {}
            sharedEcho = null;
            sharedToken = null;
        }
        return;
    }
    try {
        instance.disconnect();
    } catch (e) {}
};

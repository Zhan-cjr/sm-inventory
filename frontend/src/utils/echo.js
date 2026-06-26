import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const initEcho = (token) => {
    Pusher.logToConsole = true; // Aktifkan log Pusher di console
    const isHttps = window.location.protocol === 'https:';
    
    return new Echo({
        broadcaster: 'reverb',
        key: 'eaixn9wuthqzi8mjryhc', // VITE_REVERB_APP_KEY
        // Jika akses via public domain, arahkan websocket host ke domain admin/backend (atau domain poskasir yang sama jika diproxy)
        wsHost: window.location.hostname.includes('toserbaselamat.id') ? 'admin.toserbaselamat.id' : window.location.hostname,
        wsPort: 8080,
        wssPort: isHttps ? 443 : 8080, // Production HTTPS biasanya menggunakan port 443 (diproxy Nginx)
        forceTLS: isHttps,
        enabledTransports: isHttps ? ['wss'] : ['ws'],
        authEndpoint: '/api/v1/broadcasting/auth', // Sanctum auth endpoint inside api.php
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                'X-Device-UUID': localStorage.getItem('pos_device_uuid') || ''
            }
        }
    });
};

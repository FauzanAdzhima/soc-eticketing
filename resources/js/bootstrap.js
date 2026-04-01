import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const userIdMeta = document.querySelector('meta[name="user-id"]');

if (reverbKey && userIdMeta?.content) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
    const useTls = scheme === 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT ?? (useTls ? 443 : 8080));

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: useTls,
        enabledTransports: useTls ? ['ws', 'wss'] : ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                Accept: 'application/json',
            },
        },
    });

    const userId = userIdMeta.content;

    window.Echo.private(`user.${userId}`).listen('.ticket.assigned', (payload) => {
        window.dispatchEvent(new CustomEvent('ticket-assigned', { detail: payload }));
    });

    window.Echo.private(`user.${userId}`).listen('.ticket.resolved', (payload) => {
        window.dispatchEvent(new CustomEvent('ticket-resolved', { detail: payload }));
    });
}

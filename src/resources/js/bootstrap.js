import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Pusher = Pusher;

const runtimeReverb = window.__APP_REVERB__ ?? {};
const reverbKey = runtimeReverb.key || import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = runtimeReverb.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = Number(runtimeReverb.port || import.meta.env.VITE_REVERB_PORT || 8080);
const reverbScheme = runtimeReverb.scheme || import.meta.env.VITE_REVERB_SCHEME || 'http';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        withCredentials: true,
        auth: {
            headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        },
    });
}

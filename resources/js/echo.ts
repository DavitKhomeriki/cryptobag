import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

const echo =
    typeof window === 'undefined'
        ? null
        : new Echo({
              broadcaster: 'reverb',

              key: import.meta.env.VITE_REVERB_APP_KEY,

              wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',

              wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),

              wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),

              forceTLS:
                  (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',

              enabledTransports: ['ws', 'wss'],
          });

export default echo;

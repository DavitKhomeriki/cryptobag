import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: "reverb",

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST ?? "localhost",

    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),

    forceTLS: false,

    enabledTransports: ["ws"],
});

if (import.meta.hot) {
    import.meta.hot.dispose(() => echo.disconnect());
}

export default echo;

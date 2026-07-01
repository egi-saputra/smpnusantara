/**
 * Axios
 */
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

/**
 * CSRF TOKEN
 */
// const token = document.head.querySelector('meta[name="csrf-token"]');

// if (token) {
//     window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
// } else {
//     console.error("CSRF token not found");
// }

/**
 * Pusher (WAJIB SEBELUM Echo)
 */
// import Pusher from "pusher-js";
// window.Pusher = Pusher;

// /**
//  * Echo
//  */
// import Echo from "laravel-echo";

// // Tentukan scheme otomatis
// const isSecure =
//     (import.meta.env.VITE_REVERB_SCHEME ??
//         window.location.protocol.replace(":", "")) === "https";

// window.Echo = new Echo({
//     broadcaster: "reverb",
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
//     wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080, // Port WS
//     wssPort: import.meta.env.VITE_REVERB_PORT ?? 443, // Port WSS
//     forceTLS: isSecure, // true jika server pakai https
//     enabledTransports: isSecure ? ["wss", "ws"] : ["ws"], // pakai wss kalau production
// });

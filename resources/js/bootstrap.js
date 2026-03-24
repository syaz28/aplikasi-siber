import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set base URL for API calls
window.axios.defaults.baseURL = window.location.origin;

// Set withCredentials for cookie-based authentication (required for XSRF-TOKEN cookie)
window.axios.defaults.withCredentials = true;

// ============================================================
// CSRF Token handling — robust approach for SPA / long sessions
// ============================================================

/**
 * Read the latest XSRF-TOKEN value from the browser cookie.
 * Laravel sets this cookie automatically on every response.
 */
function getXsrfTokenFromCookie() {
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Fallback: read the CSRF token from the <meta> tag (set at page load).
 */
function getMetaCsrfToken() {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : null;
}

// Set initial token from meta tag (page-load baseline)
const initialToken = getMetaCsrfToken();
if (initialToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialToken;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * REQUEST interceptor:
 * Before every mutating request (POST/PUT/PATCH/DELETE), read the
 * freshest XSRF-TOKEN from the cookie so it's never stale.
 */
window.axios.interceptors.request.use((config) => {
    const method = (config.method || 'get').toLowerCase();
    if (['post', 'put', 'patch', 'delete'].includes(method)) {
        const cookieToken = getXsrfTokenFromCookie();
        if (cookieToken) {
            config.headers['X-XSRF-TOKEN'] = cookieToken;
        }
    }
    return config;
});

/**
 * RESPONSE interceptor:
 * If we get a 419 (CSRF token mismatch), automatically:
 *   1. Hit /sanctum/csrf-cookie to get a fresh XSRF-TOKEN cookie
 *   2. Also update the meta tag so Inertia stays in sync
 *   3. Retry the original request ONCE
 */
let isRefreshingCsrf = false;
let csrfRefreshPromise = null;

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // Only handle 419 and only retry once
        if (error.response?.status === 419 && !originalRequest._csrfRetried) {
            originalRequest._csrfRetried = true;

            // If not already refreshing, start a refresh
            if (!isRefreshingCsrf) {
                isRefreshingCsrf = true;
                csrfRefreshPromise = axios.get('/sanctum/csrf-cookie')
                    .then(() => {
                        // Update the meta tag with the fresh cookie value
                        const freshToken = getXsrfTokenFromCookie();
                        if (freshToken) {
                            const meta = document.head.querySelector('meta[name="csrf-token"]');
                            if (meta) meta.content = freshToken;
                            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = freshToken;
                        }
                    })
                    .finally(() => {
                        isRefreshingCsrf = false;
                    });
            }

            // Wait for the refresh to complete, then retry
            await csrfRefreshPromise;

            // Set fresh token on the retry request
            const freshToken = getXsrfTokenFromCookie();
            if (freshToken) {
                originalRequest.headers['X-XSRF-TOKEN'] = freshToken;
            }

            return axios(originalRequest);
        }

        return Promise.reject(error);
    }
);

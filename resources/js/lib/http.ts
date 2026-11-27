/**
 * POST outside Inertia (audio upload, small background actions). Sends the
 * XSRF-TOKEN cookie as a header, like axios does, so Laravel's CSRF check
 * passes, and asks for JSON so errors come back as JSON instead of redirects.
 */
export function post(url: string, body?: FormData): Promise<Response> {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return fetch(url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : '',
        },
    });
}

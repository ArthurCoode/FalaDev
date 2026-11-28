/**
 * POST outside Inertia (audio upload, small background actions). Sends the
 * XSRF-TOKEN cookie as a header, like axios does, so Laravel's CSRF check
 * passes, and asks for JSON so errors come back as JSON instead of redirects.
 */
export function post(url: string, body?: FormData): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: baseHeaders(),
    });
}

/** Same as post(), with a JSON body. */
export function postJson(url: string, data: unknown): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        body: JSON.stringify(data),
        credentials: 'same-origin',
        headers: { ...baseHeaders(), 'Content-Type': 'application/json' },
    });
}

function baseHeaders(): Record<string, string> {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : '',
    };
}

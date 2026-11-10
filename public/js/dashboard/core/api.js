const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') ?? '';

const apiBaseUrl = meta('dashboard-api-base');
const apiKey = meta('dashboard-api-key');
const apiToken = meta('dashboard-api-token');

export class ApiError extends Error {
    constructor(message, status = 0, payload = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }
}

const redirectToLogin = () => {
    const loginPath = window.location.pathname.startsWith('/user/')
        ? '/user/login'
        : '/dashboard/login';

    window.location.replace(`${loginPath}?expired=1`);
};

const request = async (path, options = {}) => {
    const {
        method = 'GET',
        body = null,
        query = null,
    } = options;

    const url = new URL(`${apiBaseUrl}${path.startsWith('/') ? path : `/${path}`}`, window.location.origin);

    if (query) {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, String(value));
            }
        });
    }

    const headers = {
        Accept: 'application/json',
        'X-API-Token': apiKey,
    };

    if (body !== null) {
        headers['Content-Type'] = 'application/json';
    }

    if (apiToken) {
        headers.Authorization = `Bearer ${apiToken}`;
    }

    const response = await fetch(url, {
        method,
        headers,
        body: body === null ? undefined : JSON.stringify(body),
    });

    const payload = await response.json().catch(() => null);
    const message = payload?.message || `Request failed with status ${response.status}.`;

    if (!response.ok) {
        if (response.status === 401) {
            redirectToLogin();
        }

        throw new ApiError(message, response.status, payload);
    }

    return payload;
};

export const apiGet = (path, query = null) => request(path, { query });

export const apiPost = (path, body = null, options = {}) => request(path, {
    method: 'POST',
    body,
    ...options,
});

export const apiPut = (path, body = null) => request(path, { method: 'PUT', body });

export const apiPatch = (path, body = null) => request(path, { method: 'PATCH', body });

export const apiDelete = (path) => request(path, { method: 'DELETE' });

export const apiDownload = async (path, fallbackName = 'download.csv') => {
    const url = new URL(`${apiBaseUrl}${path.startsWith('/') ? path : `/${path}`}`, window.location.origin);
    const headers = {
        Accept: '*/*',
        'X-API-Token': apiKey,
    };

    if (apiToken) {
        headers.Authorization = `Bearer ${apiToken}`;
    }

    const response = await fetch(url, { headers });

    if (!response.ok) {
        if (response.status === 401) {
            redirectToLogin();
        }

        const payload = await response.json().catch(() => null);
        throw new ApiError(payload?.message || 'The file could not be downloaded.', response.status, payload);
    }

    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') || '';
    const match = /filename="?([^"]+)"?/i.exec(disposition);
    const fileName = match?.[1] || fallbackName;
    const link = document.createElement('a');

    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);

    return { file_name: fileName };
};


export const apiUpload = async (path, formData, method = 'POST') => {
    const url = new URL(`${apiBaseUrl}${path.startsWith('/') ? path : `/${path}`}`, window.location.origin);
    const headers = {
        Accept: 'application/json',
        'X-API-Token': apiKey,
    };

    if (method !== 'POST') {
        headers['X-HTTP-Method-Override'] = method;
    }

    if (apiToken) {
        headers.Authorization = `Bearer ${apiToken}`;
    }

    const response = await fetch(url, {
        method: 'POST',
        headers,
        body: formData,
    });

    const payload = await response.json().catch(() => null);
    const message = payload?.message || `Request failed with status ${response.status}.`;

    if (!response.ok) {
        if (response.status === 401) {
            redirectToLogin();
        }

        throw new ApiError(message, response.status, payload);
    }

    return payload;
};

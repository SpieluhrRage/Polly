const API_BASE_URL = '/api/';

function getAuthToken() {
    return localStorage.getItem('auth_token');
}

function getCurrentUser() {
    const raw = localStorage.getItem('auth_user');
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (e) {
        console.error('Failed to parse auth_user from localStorage:', raw, e);
        return null;
    }
}

function saveAuth(token, user) {
    localStorage.setItem('auth_token', token);

    if (user) {
        localStorage.setItem('auth_user', JSON.stringify(user));
    } else {
        localStorage.removeItem('auth_user');
    }
}

function clearAuth() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
}

async function apiRequest(path, options = {}) {
    const headers = options.headers ? { ...options.headers } : {};
    headers['Content-Type'] = 'application/json';

    const token = getAuthToken();
    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    const response = await fetch(API_BASE_URL + path, {
        ...options,
        headers
    });

    let data = null;
    try {
        data = await response.json();
    } catch (e) {
        data = null;
    }

    if (!response.ok) {
        const message =
            (data && (data.error || data.message)) ||
            `HTTP ${response.status}`;
        throw new Error(message);
    }

    return data;
}

// ------- auth -------

function apiLogin(username, password) {
    return apiRequest('login', {
        method: 'POST',
        body: JSON.stringify({ username, password })
    });
}

function apiRegister(username, password) {
    return apiRequest('register', {
        method: 'POST',
        body: JSON.stringify({ username, password })
    });
}

function apiMe() {
    return apiRequest('me', {
        method: 'GET'
    });
}

// ------- polls -------

function apiGetPolls() {
    return apiRequest('polls', {
        method: 'GET'
    });
}

function apiGetPoll(pollId) {
    return apiRequest(`polls/${pollId}`, {
        method: 'GET'
    });
}

function apiCreatePoll(payload) {
    return apiRequest('polls', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
}

function apiEditPoll(pollId, payload) {
    return apiRequest(`polls/${pollId}/edit`, {
        method: 'POST',
        body: JSON.stringify(payload)
    });
}

function apiClosePoll(pollId) {
    return apiRequest(`polls/${pollId}/close`, {
        method: 'POST'
    });
}

function apiOpenPoll(pollId) {
    return apiRequest(`polls/${pollId}/open`, {
        method: 'POST'
    });
}

function apiDeletePoll(pollId) {
    return apiRequest(`polls/${pollId}/delete`, {
        method: 'POST'
    });
}

// ------- voting -------

function apiGetPollResults(pollId) {
    return apiRequest(`polls/${pollId}/results`, {
        method: 'GET'
    });
}

function apiCastVote(pollId, optionId) {
    return apiRequest(`polls/${pollId}/vote`, {
        method: 'POST',
        body: JSON.stringify({ option_id: optionId })
    });
}

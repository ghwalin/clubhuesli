/**
 * Thin wrapper around the JSON API of the backend.
 *
 * Every method returns the parsed payload and throws an {@link ApiError} when
 * the backend answers with an error status.
 */

/** Base URL of the API, derived from the page the application was loaded from. */
const API_BASE = new URL('api/', document.baseURI);

/**
 * An error answer of the API.
 */
export class ApiError extends Error {
    /**
     * @param {number} status HTTP status code.
     * @param {string} message Message shown to the user.
     * @param {Array<{field: string, message: string}>} errors Field errors, if any.
     */
    constructor(status, message, errors = []) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    /**
     * Returns the message belonging to a single field.
     *
     * @param {string} field Name of the field.
     * @returns {string|null} The message, or null when the field is fine.
     */
    messageFor(field) {
        const error = this.errors.find((item) => item.field === field);
        return error ? error.message : null;
    }
}

/**
 * Sends a request to the API.
 *
 * @param {string} path Path below the API root, e.g. `persons/5`.
 * @param {{method?: string, body?: unknown, query?: Record<string, string>}} options Request options.
 * @returns {Promise<any>} The parsed payload; null for an empty answer.
 * @throws {ApiError} When the backend answers with an error status.
 */
async function request(path, options = {}) {
    const url = new URL(path, API_BASE);
    Object.entries(options.query ?? {}).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, value);
        }
    });

    const init = {
        method: options.method ?? 'GET',
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    };
    if (options.body !== undefined) {
        init.headers['Content-Type'] = 'application/json';
        init.body = JSON.stringify(options.body);
    }

    let response;
    try {
        response = await fetch(url, init);
    } catch (cause) {
        throw new ApiError(0, 'Server nicht erreichbar');
    }

    if (response.status === 204) {
        return null;
    }

    let payload = null;
    const text = await response.text();
    if (text !== '') {
        try {
            payload = JSON.parse(text);
        } catch (cause) {
            throw new ApiError(response.status, 'Unerwartete Antwort des Servers');
        }
    }

    if (!response.ok) {
        throw new ApiError(
            response.status,
            payload?.error ?? 'Unerwarteter Fehler',
            payload?.errors ?? [],
        );
    }

    return payload;
}

export const api = {
    /**
     * Logs in with e-mail address and password.
     *
     * @param {string} email Login e-mail address.
     * @param {string} password Password.
     * @returns {Promise<object>} The logged-in account.
     */
    async login(email, password) {
        const payload = await request('auth/login', { method: 'POST', body: { email, password } });
        return payload.user;
    },

    /**
     * Logs the current account out.
     *
     * @returns {Promise<void>}
     */
    async logout() {
        await request('auth/logout', { method: 'POST' });
    },

    /**
     * Returns the logged-in account, or null when nobody is logged in.
     *
     * @returns {Promise<object|null>} The account or null.
     */
    async me() {
        try {
            const payload = await request('auth/me');
            return payload.user;
        } catch (error) {
            if (error instanceof ApiError && (error.status === 401 || error.status === 403)) {
                return null;
            }
            throw error;
        }
    },

    /**
     * Returns the values the forms offer (person types, member types).
     *
     * @returns {Promise<object>} The metadata.
     */
    meta() {
        return request('meta');
    },

    /**
     * Returns the person list.
     *
     * @param {{search?: string, personType?: string}} filter Optional filter.
     * @returns {Promise<Array<object>>} The persons found.
     */
    async persons(filter = {}) {
        const payload = await request('persons', { query: filter });
        return payload.persons;
    },

    /**
     * Returns a single person including guardians and children.
     *
     * @param {number|string} id Id of the person.
     * @returns {Promise<object>} The person.
     */
    async person(id) {
        const payload = await request(`persons/${id}`);
        return payload.person;
    },

    /**
     * Creates a person.
     *
     * @param {object} person Attributes of the person.
     * @returns {Promise<object>} The created person.
     */
    async createPerson(person) {
        const payload = await request('persons', { method: 'POST', body: person });
        return payload.person;
    },

    /**
     * Updates a person.
     *
     * @param {number|string} id Id of the person.
     * @param {object} person Attributes of the person.
     * @returns {Promise<object>} The updated person.
     */
    async updatePerson(id, person) {
        const payload = await request(`persons/${id}`, { method: 'PUT', body: person });
        return payload.person;
    },

    /**
     * Deletes a person.
     *
     * @param {number|string} id Id of the person.
     * @returns {Promise<void>}
     */
    async deletePerson(id) {
        await request(`persons/${id}`, { method: 'DELETE' });
    },

    /**
     * Replaces the guardians of a minor team member.
     *
     * @param {number|string} id Id of the team member.
     * @param {Array<number>} guardianIds Ids of the guardians.
     * @returns {Promise<object>} The updated team member.
     */
    async updateGuardians(id, guardianIds) {
        const payload = await request(`persons/${id}/guardians`, {
            method: 'PUT',
            body: { guardianIds },
        });
        return payload.person;
    },
};

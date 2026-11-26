/**
 * Single-page application of Clubadmin, sprint 1: login, person list, the
 * form for natural persons, team members and legal persons, seasons, groups
 * with their hierarchy and the assignment of persons to groups.
 */

import { api, ApiError } from './api.js';
import {
    renderGroupDetail,
    renderGroupList,
    renderPersonGroups,
    renderSeasons,
} from './groups.js';

/** Age at which a person is of full age; overwritten by the metadata. */
let ageOfMajority = 18;

/** @type {{user: object|null, meta: object|null, naturalPersons: Array<object>}} Application state. */
const state = {
    user: null,
    meta: null,
    naturalPersons: [],
};

/** @type {Map<string, string>} Cache of the loaded view templates. */
const viewCache = new Map();

const appElement = document.getElementById('app');

/** Helpers handed to the views of `groups.js`. */
const viewContext = {
    showView: (name) => showView(name),
    setMessage: (id, message) => setMessage(id, message),
    escapeHtml: (value) => escapeHtml(value),
    formatDate: (isoDate) => formatDate(isoDate),
    showToast: (message) => showToast(message),
    navigate: (hash) => navigate(hash),
};

/**
 * Loads a view template from `views/` and caches it.
 *
 * @param {string} name File name without extension.
 * @returns {Promise<string>} The HTML of the view.
 */
async function loadView(name) {
    if (!viewCache.has(name)) {
        // `no-cache`: always revalidate, so a changed view is never combined with a newer script.
        const response = await fetch(new URL(`views/${name}.html`, document.baseURI), { cache: 'no-cache' });
        if (!response.ok) {
            throw new Error(`Ansicht «${name}» konnte nicht geladen werden`);
        }
        viewCache.set(name, await response.text());
    }
    return viewCache.get(name);
}

/**
 * Replaces the content of the main area with a view.
 *
 * @param {string} name File name of the view without extension.
 * @returns {Promise<void>}
 */
async function showView(name) {
    appElement.innerHTML = await loadView(name);
}

/**
 * Shows a message in an element and reveals it.
 *
 * @param {string} id Id of the element.
 * @param {string|null} message Message, or null to hide the element.
 */
function setMessage(id, message) {
    const element = document.getElementById(id);
    if (!element) {
        return;
    }
    element.textContent = message ?? '';
    element.hidden = !message;
}

/**
 * Escapes text for use in HTML.
 *
 * @param {unknown} value Raw value.
 * @returns {string} The escaped text.
 */
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[character]));
}

/**
 * Formats a date for display.
 *
 * @param {string|null} isoDate Date in `YYYY-MM-DD` notation.
 * @returns {string} The date as `DD.MM.YYYY`, or an empty string.
 */
function formatDate(isoDate) {
    if (!isoDate) {
        return '';
    }
    const [year, month, day] = isoDate.split('-');
    return `${day}.${month}.${year}`;
}

/**
 * Returns the age in full years for a birth date.
 *
 * @param {string} isoDate Birth date in `YYYY-MM-DD` notation.
 * @returns {number|null} The age, or null when the date is empty or invalid.
 */
function ageFromBirthDate(isoDate) {
    if (!isoDate) {
        return null;
    }
    const birthDate = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(birthDate.getTime())) {
        return null;
    }
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDifference = today.getMonth() - birthDate.getMonth();
    if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
        age -= 1;
    }
    return age;
}

/**
 * Updates the top bar to the logged-in account.
 */
function renderTopbar() {
    const nav = document.getElementById('topbar-nav');
    const user = document.getElementById('topbar-user');
    const label = document.getElementById('current-user');

    const loggedIn = state.user !== null;
    nav.hidden = !loggedIn;
    user.hidden = !loggedIn;
    if (loggedIn) {
        label.textContent = `${state.user.email} (${state.user.roleLabel})`;
    }
}

/* -------------------------------------------------------------------------- */
/* Login                                                                      */
/* -------------------------------------------------------------------------- */

/**
 * Shows the login form and processes it.
 *
 * @returns {Promise<void>}
 */
async function renderLogin() {
    await showView('login');

    document.getElementById('login-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        setMessage('login-error', null);

        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        try {
            state.user = await api.login(email, password);
            await ensureMeta();
            renderTopbar();
            navigate('#/persons');
            await route();
        } catch (error) {
            setMessage('login-error', error instanceof ApiError ? error.message : 'Anmeldung fehlgeschlagen');
        }
    });
}

/* -------------------------------------------------------------------------- */
/* Person list                                                                */
/* -------------------------------------------------------------------------- */

/**
 * Shows the person list.
 *
 * @returns {Promise<void>}
 */
async function renderPersonList() {
    await showView('person-list');

    /** @type {Array<object>} All persons, loaded once; sorting and filtering happen in the browser. */
    let persons = [];
    /** @type {{key: string, direction: 'asc'|'desc'}} Current sort order. */
    let sort = { key: 'displayName', direction: 'asc' };
    const collator = new Intl.Collator('de-CH', { sensitivity: 'base', numeric: true });
    const table = document.getElementById('person-table');

    /**
     * Reads the values of the filter fields.
     *
     * @returns {Record<string, string>} Filter text per column.
     */
    function currentFilters() {
        const filters = {};
        table.querySelectorAll('[data-filter]').forEach((input) => {
            filters[input.dataset.filter] = input.value.trim().toLowerCase();
        });
        return filters;
    }

    /**
     * Applies filters and sort order and renders the rows.
     */
    function renderRows() {
        const filters = currentFilters();
        const visible = persons.filter((person) => Object.entries(filters).every(([key, needle]) => {
            if (needle === '') {
                return true;
            }
            const value = String(person[key] ?? '').toLowerCase();
            return key === 'personType' ? value === needle : value.includes(needle);
        }));

        const factor = sort.direction === 'asc' ? 1 : -1;
        visible.sort((a, b) => factor * collator.compare(String(a[sort.key] ?? ''), String(b[sort.key] ?? '')));

        const tbody = document.getElementById('person-rows');
        tbody.innerHTML = visible.length === 0
            ? '<tr><td colspan="5" class="hint">Keine Personen gefunden.</td></tr>'
            : visible.map(personRow).join('');
        document.getElementById('person-count').textContent = visible.length === persons.length
            ? `${persons.length} ${persons.length === 1 ? 'Person' : 'Personen'}`
            : `${visible.length} von ${persons.length} Personen`;

        table.querySelectorAll('[data-sort]').forEach((button) => {
            if (button.dataset.sort === sort.key) {
                button.dataset.direction = sort.direction;
            } else {
                delete button.dataset.direction;
            }
        });
    }

    table.querySelectorAll('[data-sort]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.sort;
            sort = { key, direction: sort.key === key && sort.direction === 'asc' ? 'desc' : 'asc' };
            renderRows();
        });
    });
    table.querySelectorAll('[data-filter]').forEach((input) => {
        input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', renderRows);
    });

    setMessage('person-list-error', null);
    try {
        persons = await api.persons();
        renderRows();
    } catch (error) {
        document.getElementById('person-rows').innerHTML = '';
        setMessage('person-list-error', error instanceof ApiError ? error.message : 'Liste konnte nicht geladen werden');
    }
}

/**
 * Renders a single row of the person list.
 *
 * @param {object} person The person.
 * @returns {string} The HTML of the row.
 */
function personRow(person) {
    const badges = [];
    if (person.teamMember) {
        badges.push('<span class="badge">Teammitglied</span>');
    }
    if (person.isMinor) {
        badges.push('<span class="badge badge--minor">minderjährig</span>');
    }

    return `
        <tr>
            <td>
                <a href="#/persons/${person.id}">${escapeHtml(person.displayName)}</a>
                ${badges.join(' ')}
            </td>
            <td>${person.personType === 'LEGAL' ? 'Juristisch' : 'Natürlich'}</td>
            <td>${person.email ? `<a href="mailto:${escapeHtml(person.email)}">${escapeHtml(person.email)}</a>` : '–'}</td>
            <td>${escapeHtml(person.phone ?? '–')}</td>
            <td class="table__actions"><a class="button button--small" href="#/persons/${person.id}">Bearbeiten</a></td>
        </tr>`;
}

/* -------------------------------------------------------------------------- */
/* Person form                                                                */
/* -------------------------------------------------------------------------- */

/**
 * Loads the metadata of the forms once per session.
 *
 * @returns {Promise<void>}
 */
async function ensureMeta() {
    if (state.meta !== null) {
        return;
    }
    try {
        state.meta = await api.meta();
        ageOfMajority = state.meta.ageOfMajority ?? ageOfMajority;
    } catch (error) {
        state.meta = {};
    }
}

/**
 * Shows the form for a new or an existing person.
 *
 * @param {{personType: string, id: number|null}} target Person type and id of the person.
 * @returns {Promise<void>}
 */
async function renderPersonForm(target) {
    await ensureMeta();
    await showView('person-form');

    let person = null;
    if (target.id !== null) {
        try {
            person = await api.person(target.id);
        } catch (error) {
            setMessage('form-error', error instanceof ApiError ? error.message : 'Person konnte nicht geladen werden');
            return;
        }
    }

    const personType = person ? person.personType : target.personType;
    const form = document.getElementById('person-form');
    const teamMemberBox = document.getElementById('teamMember');
    const birthDateInput = document.getElementById('birthDate');
    const guardiansSelect = document.getElementById('guardians');

    document.getElementById('form-title').textContent = person
        ? `${person.displayName} bearbeiten`
        : (personType === 'LEGAL' ? 'Neue juristische Person' : 'Neue natürliche Person');

    await fillGuardianOptions(person);

    if (person) {
        fillForm(person);
    }

    teamMemberBox.addEventListener('change', updateSections);
    birthDateInput.addEventListener('change', updateSections);
    guardiansSelect.addEventListener('change', updateSections);
    updateSections();

    /**
     * Shows and hides the parts of the form that depend on the person type, on
     * «team member» and on whether the person is a minor.
     */
    function updateSections() {
        const isNatural = personType === 'NATURAL';
        const isTeamMember = isNatural && teamMemberBox.checked;
        const age = ageFromBirthDate(birthDateInput.value);
        const isMinor = age !== null && age < ageOfMajority;
        const hasGuardians = Array.from(guardiansSelect.selectedOptions).length > 0;

        toggleSection('natural', isNatural);
        toggleSection('legal', !isNatural);
        toggleSection('teamMember', isTeamMember);
        toggleSection('minor', isTeamMember && isMinor);
        toggleMarker('teamMember', isTeamMember);

        // A minor team member with a guardian needs no e-mail address of its own.
        const emailRequired = !(isTeamMember && isMinor && hasGuardians);
        toggleMarker('emailRequired', emailRequired);
        document.getElementById('email-hint').textContent = emailRequired
            ? ''
            : 'E-Mails an die Gruppen gehen an die Erziehungsberechtigten.';

        document.getElementById('age-hint').textContent = age === null
            ? ''
            : `${age} Jahre${isMinor ? ' – minderjährig' : ''}`;

        if (!isMinor || !isTeamMember) {
            document.getElementById('photoConsent').checked = false;
            Array.from(guardiansSelect.options).forEach((option) => {
                option.selected = false;
            });
        }

        const children = document.querySelector('[data-section="children"]');
        children.hidden = !(isNatural && (person?.children?.length ?? 0) > 0);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitForm();
    });

    setUpDeleteButton(person);

    if (person) {
        await renderPersonGroups(viewContext, person);
    }

    /**
     * Collects the form, sends it to the API and shows the errors of the
     * backend, which validates in the setters of the models.
     *
     * @returns {Promise<void>}
     */
    async function submitForm() {
        clearFieldErrors();
        setMessage('form-error', null);

        const payload = collectForm(personType);
        try {
            const saved = person
                ? await api.updatePerson(person.id, payload)
                : await api.createPerson(payload);
            navigate(`#/persons/${saved.id}`, true);
            setMessage('form-error', null);
            await renderPersonForm({ personType, id: saved.id });
            showToast('Gespeichert.');
        } catch (error) {
            if (!(error instanceof ApiError)) {
                setMessage('form-error', 'Speichern fehlgeschlagen');
                return;
            }
            setMessage('form-error', error.message);
            error.errors.forEach(({ field, message }) => {
                const element = document.querySelector(`[data-error-for="${field}"]`);
                if (element) {
                    element.textContent = message;
                }
            });
        }
    }
}

/**
 * Shows or hides every part of the form belonging to a section.
 *
 * @param {string} section Name of the section.
 * @param {boolean} visible Whether the section is shown.
 */
function toggleSection(section, visible) {
    document.querySelectorAll(`[data-section="${section}"]`).forEach((element) => {
        element.hidden = !visible;
    });
}

/**
 * Shows or hides the mandatory markers of a condition.
 *
 * @param {string} marker Name of the marker.
 * @param {boolean} visible Whether the marker is shown.
 */
function toggleMarker(marker, visible) {
    document.querySelectorAll(`[data-when="${marker}"]`).forEach((element) => {
        element.hidden = !visible;
    });
}

/**
 * Fills the natural persons available as guardians into the select element.
 *
 * @param {object|null} person The person being edited, excluded from the list.
 * @returns {Promise<void>}
 */
async function fillGuardianOptions(person) {
    const select = document.getElementById('guardians');
    try {
        state.naturalPersons = await api.persons({ personType: 'NATURAL' });
    } catch (error) {
        state.naturalPersons = [];
    }

    const selectedIds = (person?.guardians ?? []).map((guardian) => String(guardian.id));
    select.innerHTML = state.naturalPersons
        .filter((candidate) => candidate.id !== person?.id)
        .map((candidate) => {
            const selected = selectedIds.includes(String(candidate.id)) ? ' selected' : '';
            const email = candidate.email ? ` – ${candidate.email}` : '';
            return `<option value="${candidate.id}"${selected}>${escapeHtml(candidate.displayName + email)}</option>`;
        })
        .join('');
}

/**
 * Copies a person into the form.
 *
 * @param {object} person The person to show.
 */
function fillForm(person) {
    const values = {
        firstName: person.firstName ?? '',
        lastName: person.lastName ?? '',
        birthDate: person.birthDate ?? '',
        name: person.name ?? '',
        contactFirstName: person.contactFirstName ?? '',
        contactLastName: person.contactLastName ?? '',
        support: person.support ?? '',
        ahvNumber: person.ahvNumber ?? '',
        jsNumber: person.jsNumber ?? '',
        email: person.email ?? '',
        phone: person.phone ?? '',
        street: person.street ?? '',
        houseNumber: person.houseNumber ?? '',
        addressAddition: person.addressAddition ?? '',
        zip: person.zip ?? '',
        city: person.city ?? '',
        country: person.country ?? 'CH',
        note: person.note ?? '',
    };

    Object.entries(values).forEach(([field, value]) => {
        const element = document.getElementById(field);
        if (element) {
            element.value = value;
        }
    });

    document.getElementById('teamMember').checked = Boolean(person.teamMember);
    document.getElementById('photoConsent').checked = Boolean(person.photoConsent);

    const list = document.getElementById('children-list');
    list.innerHTML = (person.children ?? [])
        .map((child) => `<li><a href="#/persons/${child.id}">${escapeHtml(child.displayName)}</a></li>`)
        .join('');
}

/**
 * Reads the form into the payload of the API.
 *
 * @param {string} personType Person type, `NATURAL` or `LEGAL`.
 * @returns {object} The payload.
 */
function collectForm(personType) {
    /**
     * Reads the value of a form field.
     *
     * @param {string} id Id of the field.
     * @returns {string} The trimmed value.
     */
    const value = (id) => (document.getElementById(id)?.value ?? '').trim();

    const payload = {
        personType,
        email: value('email'),
        phone: value('phone'),
        street: value('street'),
        houseNumber: value('houseNumber'),
        addressAddition: value('addressAddition'),
        zip: value('zip'),
        city: value('city'),
        country: value('country') || 'CH',
        note: document.getElementById('note').value.trim(),
    };

    if (personType === 'LEGAL') {
        payload.name = value('name');
        payload.contactFirstName = value('contactFirstName');
        payload.contactLastName = value('contactLastName');
        payload.support = document.getElementById('support').value.trim();

        return payload;
    }

    payload.firstName = value('firstName');
    payload.lastName = value('lastName');
    payload.birthDate = value('birthDate');

    const isTeamMember = document.getElementById('teamMember').checked;
    payload.teamMember = isTeamMember;
    if (isTeamMember) {
        payload.ahvNumber = value('ahvNumber');
        payload.jsNumber = value('jsNumber');
        payload.photoConsent = document.getElementById('photoConsent').checked;
        payload.guardianIds = Array.from(document.getElementById('guardians').selectedOptions)
            .map((option) => Number(option.value));
    }

    return payload;
}

/**
 * Clears every field error of the form.
 */
function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });
}

/**
 * Wires the delete button, which asks for confirmation inline before deleting.
 *
 * @param {object|null} person The person being edited; null for a new one.
 */
function setUpDeleteButton(person) {
    const button = document.getElementById('delete-button');
    if (!person) {
        return;
    }
    button.hidden = false;

    button.addEventListener('click', () => {
        if (document.getElementById('delete-confirm')) {
            return;
        }

        const confirmation = document.createElement('div');
        confirmation.id = 'delete-confirm';
        confirmation.className = 'confirm';
        confirmation.innerHTML = `
            <p>
                <strong>${escapeHtml(person.displayName)}</strong> wirklich löschen?
                Die Person, ihre Beziehungen und ihre Adresse werden entfernt.
            </p>
            <button type="button" class="button button--danger" id="delete-yes">Ja, löschen</button>
            <button type="button" class="button" id="delete-no">Abbrechen</button>`;
        button.insertAdjacentElement('afterend', confirmation);

        document.getElementById('delete-no').addEventListener('click', () => confirmation.remove());
        document.getElementById('delete-yes').addEventListener('click', async () => {
            try {
                await api.deletePerson(person.id);
                navigate('#/persons');
                showToast('Person gelöscht.');
            } catch (error) {
                confirmation.remove();
                setMessage('form-error', error instanceof ApiError ? error.message : 'Löschen fehlgeschlagen');
            }
        });
    });
}

/**
 * Shows a short confirmation message.
 *
 * @param {string} message The message.
 */
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    window.setTimeout(() => toast.remove(), 2500);
}

/* -------------------------------------------------------------------------- */
/* Routing                                                                    */
/* -------------------------------------------------------------------------- */

/**
 * Changes the route.
 *
 * @param {string} hash The new hash, e.g. `#/persons`.
 * @param {boolean} silent Whether to suppress the re-rendering.
 */
function navigate(hash, silent = false) {
    if (window.location.hash === hash) {
        if (!silent) {
            void route();
        }
        return;
    }
    if (silent) {
        window.history.replaceState(null, '', hash);
        return;
    }
    window.location.hash = hash;
}

/**
 * Renders the view belonging to the current hash.
 *
 * @returns {Promise<void>}
 */
async function route() {
    if (state.user === null) {
        await renderLogin();
        return;
    }

    const segments = window.location.hash.replace(/^#\/?/, '').split('/').filter(Boolean);

    if (segments[0] === 'seasons') {
        await renderSeasons(viewContext);
        return;
    }
    if (segments[0] === 'groups') {
        if (segments[1]) {
            await renderGroupDetail(viewContext, Number(segments[1]));
        } else {
            await renderGroupList(viewContext);
        }
        return;
    }
    if (segments[0] !== 'persons') {
        navigate('#/persons');
        return;
    }
    if (segments[1] === 'new') {
        await renderPersonForm({
            personType: segments[2] === 'legal' ? 'LEGAL' : 'NATURAL',
            id: null,
        });
        return;
    }
    if (segments[1]) {
        await renderPersonForm({ personType: 'NATURAL', id: Number(segments[1]) });
        return;
    }

    await renderPersonList();
}

/**
 * Starts the application: it checks the session, loads the metadata and renders
 * the first view.
 *
 * @returns {Promise<void>}
 */
async function start() {
    document.getElementById('logout-button').addEventListener('click', async () => {
        await api.logout();
        state.user = null;
        renderTopbar();
        navigate('#/login');
        await route();
    });

    window.addEventListener('hashchange', () => {
        void route();
    });

    try {
        state.user = await api.me();
    } catch (error) {
        appElement.innerHTML = '<p class="form-error">Server nicht erreichbar.</p>';
        return;
    }

    if (state.user !== null) {
        await ensureMeta();
    }

    renderTopbar();
    await route();
}

void start();

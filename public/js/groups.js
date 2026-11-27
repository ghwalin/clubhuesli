/**
 * Views for seasons, groups, the group hierarchy and the assignment of persons
 * to groups.
 *
 * Every render function receives the helpers of the application shell
 * (`ctx`) so that this module does not depend on `app.js`.
 *
 * @typedef {object} Context
 * @property {(name: string) => Promise<void>} showView Replaces the main area with a view.
 * @property {(id: string, message: string|null) => void} setMessage Shows or hides a message.
 * @property {(value: unknown) => string} escapeHtml Escapes text for HTML.
 * @property {(isoDate: string|null) => string} formatDate Formats a date for display.
 * @property {(message: string) => void} showToast Shows a short confirmation.
 * @property {(hash: string) => void} navigate Changes the route.
 */

import { api, ApiError } from './api.js';

/** @type {object|null} Cached values of the group forms. */
let groupMeta = null;

/**
 * Loads the values of the group forms once.
 *
 * @returns {Promise<object>} Group types, statuses and roles.
 */
async function ensureGroupMeta() {
    if (groupMeta === null) {
        groupMeta = await api.groupMeta();
    }
    return groupMeta;
}

/**
 * Builds `<option>` elements.
 *
 * @param {Array<{value: string|number, label: string}>} items Options.
 * @param {string|number|null} selected Selected value.
 * @param {Context} ctx Application helpers.
 * @returns {string} The HTML.
 */
function options(items, selected, ctx) {
    return items
        .map((item) => `<option value="${ctx.escapeHtml(item.value)}"${String(item.value) === String(selected) ? ' selected' : ''}>${ctx.escapeHtml(item.label)}</option>`)
        .join('');
}

/**
 * Shows the field errors of a validation error and returns whether there were any.
 *
 * @param {unknown} error The caught error.
 * @param {string} messageId Id of the element for the general message.
 * @param {Context} ctx Application helpers.
 * @param {string} fallback Message for unexpected errors.
 */
function showError(error, messageId, ctx, fallback) {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });
    if (!(error instanceof ApiError)) {
        ctx.setMessage(messageId, fallback);
        return;
    }
    let shown = false;
    error.errors.forEach((item) => {
        const element = document.querySelector(`[data-error-for="${item.field}"]`);
        if (element) {
            element.textContent = item.message;
            shown = true;
        }
    });
    ctx.setMessage(messageId, shown ? null : error.message);
}

/**
 * Clears the field errors on the page.
 */
function clearErrors() {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });
}

/**
 * Shows an inline confirmation after an element and runs an action on «yes».
 *
 * @param {HTMLElement} anchor Element after which the confirmation appears.
 * @param {string} messageHtml Message (already escaped).
 * @param {string} yesLabel Label of the confirm button.
 * @param {() => Promise<void>} onYes Action to run.
 */
function confirmInline(anchor, messageHtml, yesLabel, onYes) {
    document.querySelectorAll('.confirm[data-inline]').forEach((element) => element.remove());
    const box = document.createElement('div');
    box.className = 'confirm';
    box.dataset.inline = '1';
    box.innerHTML = `<p>${messageHtml}</p>
        <button type="button" class="button button--danger" data-yes>${yesLabel}</button>
        <button type="button" class="button" data-no>Abbrechen</button>`;
    anchor.insertAdjacentElement('afterend', box);
    box.querySelector('[data-no]').addEventListener('click', () => box.remove());
    box.querySelector('[data-yes]').addEventListener('click', async () => {
        box.remove();
        await onYes();
    });
}

/** Warning shown before a membership is deleted. */
const DELETE_MEMBERSHIP_WARNING =
    'Zuordnungen sollten nur gelöscht werden, wenn sie <strong>fehlerhaft erfasst</strong> wurden. '
    + 'Bei einem regulären Abgang bitte stattdessen das Austrittsdatum erfassen. Wirklich löschen?';

/* -------------------------------------------------------------------------- */
/* Seasons                                                                    */
/* -------------------------------------------------------------------------- */

/**
 * Shows the seasons with a form to create and edit them.
 *
 * @param {Context} ctx Application helpers.
 * @returns {Promise<void>}
 */
export async function renderSeasons(ctx) {
    await ctx.showView('seasons');
    const form = document.getElementById('season-form');
    const idField = document.getElementById('season-id');
    const cancel = document.getElementById('season-cancel');

    /** Resets the form to «new season». */
    function resetForm() {
        form.reset();
        idField.value = '';
        document.getElementById('season-form-title').textContent = 'Neue Saison';
        cancel.hidden = true;
        clearErrors();
    }

    /**
     * Loads and renders the season list.
     *
     * @returns {Promise<void>}
     */
    async function refresh() {
        ctx.setMessage('season-error', null);
        const rows = document.getElementById('season-rows');
        try {
            const seasons = await api.seasons();
            rows.innerHTML = seasons.length === 0
                ? '<tr><td colspan="4" class="hint">Noch keine Saison erfasst.</td></tr>'
                : seasons.map((season) => `
                    <tr data-id="${season.id}">
                        <td>${ctx.escapeHtml(season.name)} ${season.isCurrent ? '<span class="badge">aktuell</span>' : ''}</td>
                        <td>${ctx.formatDate(season.startDate)}</td>
                        <td>${ctx.formatDate(season.endDate)}</td>
                        <td class="table__actions">
                            <button type="button" class="button button--small" data-edit="${season.id}">Bearbeiten</button>
                        </td>
                    </tr>`).join('');

            rows.querySelectorAll('[data-edit]').forEach((button) => button.addEventListener('click', () => {
                const season = seasons.find((item) => item.id === Number(button.dataset.edit));
                idField.value = season.id;
                document.getElementById('season-name').value = season.name;
                document.getElementById('season-start').value = season.startDate;
                document.getElementById('season-end').value = season.endDate;
                document.getElementById('season-form-title').textContent = `${season.name} bearbeiten`;
                cancel.hidden = false;
                clearErrors();
            }));
        } catch (error) {
            rows.innerHTML = '';
            ctx.setMessage('season-error', error instanceof ApiError ? error.message : 'Saisons konnten nicht geladen werden');
        }
    }

    cancel.addEventListener('click', resetForm);
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        const body = {
            name: document.getElementById('season-name').value,
            startDate: document.getElementById('season-start').value,
            endDate: document.getElementById('season-end').value,
        };
        try {
            if (idField.value) {
                await api.updateSeason(Number(idField.value), body);
            } else {
                await api.createSeason(body);
            }
            ctx.showToast('Saison gespeichert.');
            resetForm();
            await refresh();
        } catch (error) {
            showError(error, 'season-error', ctx, 'Speichern fehlgeschlagen');
        }
    });

    await refresh();
}

/* -------------------------------------------------------------------------- */
/* Group list and tree                                                        */
/* -------------------------------------------------------------------------- */

/**
 * Shows the groups as a list or tree with a form to create a group.
 *
 * @param {Context} ctx Application helpers.
 * @returns {Promise<void>}
 */
export async function renderGroupList(ctx) {
    const meta = await ensureGroupMeta();
    await ctx.showView('groups');

    document.getElementById('filter-group-type').innerHTML =
        `<option value="">Alle</option>${options(meta.groupTypes, null, ctx)}`;
    document.getElementById('filter-group-status').innerHTML =
        `<option value="">Alle</option>${options(meta.groupStatuses, null, ctx)}`;
    document.getElementById('new-group-type').innerHTML =
        `<option value="">Bitte wählen</option>${options(meta.groupTypes, null, ctx)}`;

    const table = document.getElementById('group-table');
    /** @type {Array<object>} All groups, loaded once; sorting and filtering happen in the browser. */
    let groups = [];
    /** @type {{key: string, direction: 'asc'|'desc'}} Current sort order. */
    let sort = { key: 'name', direction: 'asc' };
    const collator = new Intl.Collator('de-CH', { sensitivity: 'base', numeric: true });

    /** Applies filters and sort order and renders the table or the tree. */
    function renderList() {
        const isTree = document.getElementById('group-view').value === 'tree';
        table.hidden = isTree;
        document.getElementById('group-tree').hidden = !isTree;
        if (isTree) {
            document.getElementById('group-tree').innerHTML = groupTree(groups, {}, ctx);
            document.getElementById('group-count').textContent = '';
            return;
        }

        const filters = {};
        table.querySelectorAll('[data-filter]').forEach((input) => {
            filters[input.dataset.filter] = input.value.trim().toLowerCase();
        });
        const visible = groups.filter((group) => Object.entries(filters).every(([key, needle]) => {
            if (needle === '') {
                return true;
            }
            const value = String(group[key] ?? '').toLowerCase();
            return key === 'groupType' || key === 'status' ? value === needle : value.includes(needle);
        }));
        const factor = sort.direction === 'asc' ? 1 : -1;
        visible.sort((a, b) => factor * collator.compare(String(a[sort.key] ?? ''), String(b[sort.key] ?? '')));

        document.getElementById('group-rows').innerHTML = visible.length === 0
            ? '<tr><td colspan="6" class="hint">Keine Gruppen gefunden.</td></tr>'
            : visible.map((group) => `
            <tr>
                <td><a href="#/groups/${group.id}">${ctx.escapeHtml(group.name)}</a></td>
                <td>${ctx.escapeHtml(group.groupTypeLabel)}</td>
                <td>${group.status === 'ACTIVE' ? 'aktiv' : '<span class="badge badge--minor">inaktiv</span>'}</td>
                <td>${ctx.escapeHtml(group.parentNames || '–')}</td>
                <td>${ctx.escapeHtml(group.childNames || '–')}</td>
                <td class="table__actions"><a class="button button--small" href="#/groups/${group.id}">Öffnen</a></td>
            </tr>`).join('');
        document.getElementById('group-count').textContent = visible.length === groups.length
            ? `${groups.length} ${groups.length === 1 ? 'Gruppe' : 'Gruppen'}`
            : `${visible.length} von ${groups.length} Gruppen`;

        table.querySelectorAll('[data-sort]').forEach((button) => {
            if (button.dataset.sort === sort.key) {
                button.dataset.direction = sort.direction;
            } else {
                delete button.dataset.direction;
            }
        });
    }

    table.querySelectorAll('[data-sort]').forEach((button) => button.addEventListener('click', () => {
        const key = button.dataset.sort;
        sort = { key, direction: sort.key === key && sort.direction === 'asc' ? 'desc' : 'asc' };
        renderList();
    }));
    table.querySelectorAll('[data-filter]').forEach((input) => {
        input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', renderList);
    });
    document.getElementById('group-view').addEventListener('change', renderList);

    /**
     * Loads the groups and renders them in the chosen view.
     *
     * @returns {Promise<void>}
     */
    async function refresh() {
        ctx.setMessage('group-list-error', null);
        try {
            const all = await api.groups();
            const byId = new Map(all.map((group) => [group.id, group.name]));
            const names = (ids) => ids.map((id) => byId.get(id)).filter(Boolean).join(', ');
            groups = all.map((group) => ({
                ...group,
                parentNames: names(group.parentIds),
                childNames: names(group.childIds),
            }));
            renderList();
        } catch (error) {
            document.getElementById('group-rows').innerHTML = '';
            ctx.setMessage('group-list-error', error instanceof ApiError ? error.message : 'Gruppen konnten nicht geladen werden');
        }
    }

    document.getElementById('group-create-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        try {
            const group = await api.createGroup({
                name: document.getElementById('new-group-name').value,
                groupType: document.getElementById('new-group-type').value,
            });
            ctx.showToast('Gruppe angelegt.');
            ctx.navigate(`#/groups/${group.id}`);
        } catch (error) {
            showError(error, 'group-list-error', ctx, 'Gruppe konnte nicht angelegt werden');
        }
    });

    await refresh();
}

/**
 * Renders the group hierarchy as a tree. A group with several parents appears
 * below each of them.
 *
 * @param {Array<object>} groups All groups.
 * @param {{groupType: string, status: string}} filter Highlight filter: groups that do not match are dimmed.
 * @param {Context} ctx Application helpers.
 * @returns {string} The HTML.
 */
function groupTree(groups, filter, ctx) {
    if (groups.length === 0) {
        return '<p class="hint">Keine Gruppen gefunden.</p>';
    }
    const byId = new Map(groups.map((group) => [group.id, group]));
    const matches = (group) => (!filter.groupType || group.groupType === filter.groupType)
        && (!filter.status || group.status === filter.status);

    /**
     * Renders a group and its children.
     *
     * @param {object} group The group.
     * @param {Set<number>} path Ids of the ancestors, as a guard against cycles.
     * @returns {string} The HTML.
     */
    const node = (group, path) => {
        const children = group.childIds.filter((id) => byId.has(id) && !path.has(id));
        const label = `<a href="#/groups/${group.id}">${ctx.escapeHtml(group.name)}</a>
            <span class="hint">${ctx.escapeHtml(group.groupTypeLabel)}${group.status === 'ACTIVE' ? '' : ', inaktiv'}</span>`;
        const inner = children.length === 0
            ? ''
            : `<ul>${children.map((id) => node(byId.get(id), new Set([...path, group.id]))).join('')}</ul>`;
        return `<li${matches(group) ? '' : ' style="opacity:.5"'}>${label}${inner}</li>`;
    };

    const roots = groups.filter((group) => group.parentIds.length === 0);
    return `<ul class="tree">${roots.map((group) => node(group, new Set([group.id]))).join('')}</ul>`;
}

/* -------------------------------------------------------------------------- */
/* Group detail                                                               */
/* -------------------------------------------------------------------------- */

/**
 * Shows a group: master data, hierarchy and members of a season.
 *
 * @param {Context} ctx Application helpers.
 * @param {number} id Id of the group.
 * @returns {Promise<void>}
 */
export async function renderGroupDetail(ctx, id) {
    const meta = await ensureGroupMeta();
    await ctx.showView('group-detail');

    /** @type {{group: object, allGroups: Array<object>, seasons: Array<object>, persons: Array<object>}} */
    const state = { group: null, allGroups: [], seasons: [], persons: [] };

    try {
        [state.group, state.allGroups, state.seasons, state.persons] = await Promise.all([
            api.group(id), api.groups(), api.seasons(), api.persons(),
        ]);
    } catch (error) {
        ctx.setMessage('group-error', error instanceof ApiError ? error.message : 'Gruppe konnte nicht geladen werden');
        return;
    }
    const isTeam = () => state.group.groupType === 'TEAM';

    document.getElementById('group-type').innerHTML = options(meta.groupTypes, state.group.groupType, ctx);
    document.getElementById('group-status').innerHTML = options(meta.groupStatuses, state.group.status, ctx);

    /** Fills the master data form and the hierarchy panel. */
    function renderGroup() {
        document.getElementById('group-title').textContent = state.group.name;
        document.getElementById('group-name').value = state.group.name;
        document.getElementById('group-type').value = state.group.groupType;
        document.getElementById('group-status').value = state.group.status;
        renderHierarchy();
        updateAssignForm();
    }

    document.getElementById('group-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        ctx.setMessage('group-error', null);
        try {
            state.group = await api.updateGroup(id, {
                name: document.getElementById('group-name').value,
                groupType: document.getElementById('group-type').value,
                status: document.getElementById('group-status').value,
            });
            state.allGroups = await api.groups();
            ctx.showToast('Gruppe gespeichert.');
            renderGroup();
        } catch (error) {
            showError(error, 'group-error', ctx, 'Speichern fehlgeschlagen');
            document.getElementById('group-status').value = state.group.status;
        }
    });

    /* --------------------------- hierarchy --------------------------- */

    /** Renders parents, children and the selects to add more. */
    function renderHierarchy() {
        const byId = new Map(state.allGroups.map((group) => [group.id, group]));
        const item = (groupId, removeKey) => {
            const other = byId.get(groupId);
            return `<li><a href="#/groups/${groupId}">${ctx.escapeHtml(other?.name ?? groupId)}</a>
                <button type="button" class="button button--small" data-${removeKey}="${groupId}">Entfernen</button></li>`;
        };
        const parentList = document.getElementById('parent-list');
        const childList = document.getElementById('child-list');
        parentList.innerHTML = state.group.parentIds.map((parentId) => item(parentId, 'remove-parent')).join('')
            || '<li class="hint">Keine Obergruppen</li>';
        childList.innerHTML = state.group.childIds.map((childId) => item(childId, 'remove-child')).join('')
            || '<li class="hint">Keine Untergruppen</li>';

        const candidates = state.allGroups
            .filter((group) => group.id !== state.group.id && group.status === 'ACTIVE')
            .map((group) => ({ value: group.id, label: group.name }));
        const free = (taken) => candidates.filter((candidate) => !taken.includes(candidate.value));
        const placeholder = '<option value="" selected>--- auswählen ---</option>';
        document.getElementById('parent-select').innerHTML = placeholder + options(free(state.group.parentIds), null, ctx);
        document.getElementById('child-select').innerHTML = placeholder + options(free(state.group.childIds), null, ctx);

        parentList.querySelectorAll('[data-remove-parent]').forEach((button) => button.addEventListener('click', () =>
            changeLink(() => api.removeChildGroup(Number(button.dataset.removeParent), id))));
        childList.querySelectorAll('[data-remove-child]').forEach((button) => button.addEventListener('click', () =>
            changeLink(() => api.removeChildGroup(id, Number(button.dataset.removeChild)))));
    }

    /**
     * Runs a link change and reloads group and members.
     *
     * @param {() => Promise<object>} action The API call.
     * @returns {Promise<void>}
     */
    async function changeLink(action) {
        ctx.setMessage('link-error', null);
        try {
            await action();
            [state.group, state.allGroups] = await Promise.all([api.group(id), api.groups()]);
            renderHierarchy();
            await refreshMembers();
        } catch (error) {
            ctx.setMessage('link-error', error instanceof ApiError ? error.message : 'Zuordnung fehlgeschlagen');
        }
    }

    document.getElementById('parent-add').addEventListener('click', () => {
        const parentId = Number(document.getElementById('parent-select').value);
        if (!parentId) {
            ctx.setMessage('link-error', 'Bitte eine Obergruppe auswählen');
            return;
        }
        void changeLink(() => api.addChildGroup(parentId, id));
    });
    document.getElementById('child-add').addEventListener('click', () => {
        const childId = Number(document.getElementById('child-select').value);
        if (!childId) {
            ctx.setMessage('link-error', 'Bitte eine Untergruppe auswählen');
            return;
        }
        void changeLink(() => api.addChildGroup(id, childId));
    });

    /* ---------------------------- members ---------------------------- */

    const seasonSelect = document.getElementById('member-season');
    const current = state.seasons.find((season) => season.isCurrent) ?? state.seasons[0] ?? null;
    seasonSelect.innerHTML = options(
        state.seasons.map((season) => ({ value: season.id, label: season.name + (season.isCurrent ? ' (aktuell)' : '') })),
        current?.id ?? null,
        ctx,
    );
    document.getElementById('assign-season').innerHTML = seasonSelect.innerHTML;

    /**
     * Loads and renders the members of the chosen season.
     *
     * @returns {Promise<void>}
     */
    async function refreshMembers() {
        ctx.setMessage('member-error', null);
        const rows = document.getElementById('member-rows');
        const previous = document.getElementById('previous-members');
        if (!seasonSelect.value) {
            rows.innerHTML = '<tr><td colspan="6" class="hint">Bitte zuerst eine Saison erfassen.</td></tr>';
            previous.innerHTML = '';
            return;
        }
        try {
            const result = await api.groupMembers(id, {
                seasonId: seasonSelect.value,
                includeFormer: document.getElementById('member-former').checked ? '1' : '',
            });
            rows.innerHTML = result.members.length === 0
                ? '<tr><td colspan="6" class="hint">Keine Mitglieder in dieser Saison.</td></tr>'
                : result.members.map((member) => memberRow(member, ctx)).join('');
            previous.innerHTML = result.previousSeasons.length === 0 ? '' : `
                <h3>Frühere Saisons</h3>
                <ul class="list">${result.previousSeasons.map((member) =>
        `<li><a href="#/persons/${member.personId}">${ctx.escapeHtml(member.displayName)}</a>
                        <span class="hint">${ctx.escapeHtml(member.seasons.join(', '))}</span></li>`).join('')}</ul>`;

            rows.querySelectorAll('[data-edit-membership]').forEach((button) => button.addEventListener('click', () => {
                const member = result.members.find((item) => item.membershipId === Number(button.dataset.editMembership));
                startEdit(member);
            }));
            rows.querySelectorAll('[data-delete-membership]').forEach((button) => button.addEventListener('click', () => {
                const membershipId = Number(button.dataset.deleteMembership);
                confirmInline(button.closest('table'), DELETE_MEMBERSHIP_WARNING, 'Ja, löschen', async () => {
                    try {
                        await api.deleteMembership(membershipId);
                        ctx.showToast('Zuordnung gelöscht.');
                        await refreshMembers();
                    } catch (error) {
                        ctx.setMessage('member-error', error instanceof ApiError ? error.message : 'Löschen fehlgeschlagen');
                    }
                });
            }));
        } catch (error) {
            rows.innerHTML = '';
            ctx.setMessage('member-error', error instanceof ApiError ? error.message : 'Mitglieder konnten nicht geladen werden');
        }
    }

    seasonSelect.addEventListener('change', () => void refreshMembers());
    document.getElementById('member-former').addEventListener('change', () => void refreshMembers());

    /* ------------------------ assign / correct ------------------------ */

    const assignForm = document.getElementById('assign-form');
    const membershipField = document.getElementById('assign-membership-id');
    const roleSelect = document.getElementById('assign-role');
    /** @type {Map<string, string>} Label shown in the person filter and the id of the person. */
    const personLabels = new Map();

    document.getElementById('assign-person-search').addEventListener('input', (event) => {
        document.getElementById('assign-person').value = personLabels.get(event.target.value) ?? '';
    });

    /** Shows the fields that depend on the group type and the role. */
    function updateAssignForm() {
        const team = isTeam();
        document.getElementById('assign-role-field').hidden = !team;
        document.getElementById('assign-shirt-field').hidden = !(team && roleSelect.value === 'PLAYER');

        const personSearch = document.getElementById('assign-person-search');
        const personId = document.getElementById('assign-person');
        const persons = state.persons.filter((person) =>
            !team || (person.personType === 'NATURAL' && person.teamMember));
        personLabels.clear();
        persons.forEach((person) => {
            const base = person.email ? `${person.displayName} – ${person.email}` : person.displayName;
            personLabels.set(personLabels.has(base) ? `${base} (#${person.id})` : base, String(person.id));
        });
        document.getElementById('assign-person-list').innerHTML =
            Array.from(personLabels.keys()).map((label) => `<option value="${ctx.escapeHtml(label)}"></option>`).join('');
        if (membershipField.value === '') {
            // The group type may have changed: forget a person that is no longer offered.
            personId.value = personLabels.get(personSearch.value) ?? '';
        }
        personSearch.disabled = membershipField.value !== '';
        document.getElementById('assign-card').hidden = state.group.status !== 'ACTIVE' && membershipField.value === '';
    }
    roleSelect.innerHTML = options(meta.membershipRoles.filter((role) => role.value !== 'MEMBER'), 'PLAYER', ctx);
    roleSelect.addEventListener('change', updateAssignForm);

    /** Resets the form to «assign a new person». */
    function resetAssign() {
        assignForm.reset();
        membershipField.value = '';
        document.getElementById('assign-title').textContent = 'Person zuordnen';
        document.getElementById('assign-save').textContent = 'Zuordnen';
        document.getElementById('assign-cancel').hidden = true;
        document.getElementById('assign-season').value = seasonSelect.value;
        clearErrors();
        ctx.setMessage('assign-error', null);
        updateAssignForm();
    }

    /**
     * Fills the form with a membership to correct it.
     *
     * @param {object} member A row of the member list.
     */
    function startEdit(member) {
        membershipField.value = member.membershipId;
        document.getElementById('assign-title').textContent = `Zuordnung von ${member.displayName} korrigieren`;
        document.getElementById('assign-save').textContent = 'Speichern';
        document.getElementById('assign-cancel').hidden = false;
        document.getElementById('assign-person').value = member.personId;
        document.getElementById('assign-person-search').value = member.displayName;
        document.getElementById('assign-season').value = seasonSelect.value;
        roleSelect.value = member.role === 'COACH' ? 'COACH' : 'PLAYER';
        document.getElementById('assign-function').value = member.function ?? '';
        document.getElementById('assign-shirt').value = member.shirtNumber ?? '';
        document.getElementById('assign-exit').value = member.exitDate ?? '';
        updateAssignForm();
        document.getElementById('assign-card').hidden = false;
        document.getElementById('assign-card').scrollIntoView({ behavior: 'smooth' });
    }

    document.getElementById('assign-cancel').addEventListener('click', resetAssign);
    assignForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        ctx.setMessage('assign-error', null);
        const team = isTeam();
        const body = {
            seasonId: Number(document.getElementById('assign-season').value) || null,
            function: document.getElementById('assign-function').value,
            exitDate: document.getElementById('assign-exit').value || null,
        };
        if (team) {
            body.role = roleSelect.value;
            body.shirtNumber = roleSelect.value === 'PLAYER' ? (document.getElementById('assign-shirt').value || null) : null;
        }
        if (!membershipField.value && !document.getElementById('assign-person').value) {
            const element = document.querySelector('[data-error-for="personId"]');
            element.textContent = 'Bitte eine Person aus der Liste auswählen';
            return;
        }
        try {
            if (membershipField.value) {
                await api.updateMembership(Number(membershipField.value), body);
                ctx.showToast('Zuordnung gespeichert.');
            } else {
                await api.createMembership({
                    ...body,
                    groupId: id,
                    personId: Number(document.getElementById('assign-person').value),
                });
                ctx.showToast('Person zugeordnet.');
            }
            seasonSelect.value = String(body.seasonId ?? seasonSelect.value);
            resetAssign();
            await refreshMembers();
        } catch (error) {
            showError(error, 'assign-error', ctx, 'Speichern fehlgeschlagen');
        }
    });

    renderGroup();
    resetAssign();
    await refreshMembers();
}

/**
 * Renders one row of the member list.
 *
 * @param {object} member The member.
 * @param {Context} ctx Application helpers.
 * @returns {string} The HTML.
 */
function memberRow(member, ctx) {
    const badges = [];
    if (member.former) {
        badges.push('<span class="badge badge--minor">ausgetreten</span>');
    }
    if (member.derived) {
        badges.push(`<span class="badge">über ${ctx.escapeHtml(member.via.join(', '))}</span>`);
    }
    const actions = member.membershipId === null
        ? ''
        : `<button type="button" class="button button--small" data-edit-membership="${member.membershipId}">Bearbeiten</button>
           <button type="button" class="button button--small button--danger" data-delete-membership="${member.membershipId}">Löschen</button>`;

    return `<tr>
        <td><a href="#/persons/${member.personId}">${ctx.escapeHtml(member.displayName)}</a> ${badges.join(' ')}</td>
        <td>${member.derived ? '–' : ctx.escapeHtml(member.roleLabel)}</td>
        <td>${ctx.escapeHtml(member.function ?? '–')}</td>
        <td>${member.shirtNumber ?? '–'}</td>
        <td>${member.exitDate ? ctx.formatDate(member.exitDate) : '–'}</td>
        <td class="table__actions">${actions}</td>
    </tr>`;
}

/* -------------------------------------------------------------------------- */
/* Groups of a person                                                         */
/* -------------------------------------------------------------------------- */

/**
 * Appends the group panel to the form of a stored person: memberships per
 * season, a form to assign the person to a group and the exit from the club.
 *
 * @param {Context} ctx Application helpers.
 * @param {object} person The stored person.
 * @returns {Promise<void>}
 */
export async function renderPersonGroups(ctx, person) {
    const meta = await ensureGroupMeta();
    const host = document.createElement('div');
    host.innerHTML = await (await fetch(new URL('views/person-groups.html', document.baseURI), { cache: 'no-cache' })).text();
    (document.getElementById('panel-groups') ?? document.getElementById('app')).appendChild(host);

    const [groups, seasons] = await Promise.all([api.groups({ status: 'ACTIVE' }), api.seasons()]);
    const eligible = (group) => group.groupType !== 'TEAM'
        || (person.personType === 'NATURAL' && person.teamMember);
    const available = groups.filter(eligible);

    const groupSelect = document.getElementById('pa-group');
    const roleSelect = document.getElementById('pa-role');
    groupSelect.innerHTML = options(available.map((group) => ({ value: group.id, label: group.name })), null, ctx);
    const current = seasons.find((season) => season.isCurrent) ?? seasons[0] ?? null;
    document.getElementById('pa-season').innerHTML = options(
        seasons.map((season) => ({ value: season.id, label: season.name })), current?.id ?? null, ctx);
    roleSelect.innerHTML = options(meta.membershipRoles.filter((role) => role.value !== 'MEMBER'), 'PLAYER', ctx);

    /** Shows the role field for teams only. */
    function updateRole() {
        const group = groups.find((item) => item.id === Number(groupSelect.value));
        document.getElementById('pa-role-field').hidden = group?.groupType !== 'TEAM';
    }
    groupSelect.addEventListener('change', updateRole);
    updateRole();

    /**
     * Renders the memberships of the person.
     *
     * @param {{memberships: Array<object>, derived: Array<object>}} data Direct and derived memberships.
     */
    function render(data) {
        const rows = document.getElementById('person-group-rows');
        const today = new Date().toISOString().slice(0, 10);
        const period = (start, end) => `${start ? ctx.formatDate(start) : '–'} – ${end ? ctx.formatDate(end) : ''}`;
        const direct = data.memberships.map((membership) => {
            const id = membership.id;
            const actions = membership.exitDate
                ? `<button type="button" class="button button--small" data-reactivate="${id}">Wieder aufnehmen</button>
                   <button type="button" class="button button--small button--danger" data-delete="${id}">Löschen</button>`
                : `<button type="button" class="button button--small" data-edit="${id}">Bearbeiten</button>
                   <button type="button" class="button button--small" data-end="${id}">Beenden</button>`;
            return `<tr data-row="${id}">
            <td>${ctx.escapeHtml(membership.seasonName)}</td>
            <td><a href="#/groups/${membership.groupId}">${ctx.escapeHtml(membership.groupName)}</a></td>
            <td data-cell="role">${membership.groupType === 'TEAM' ? ctx.escapeHtml(membership.roleLabel) : '–'}</td>
            <td data-cell="function">${ctx.escapeHtml(membership.function ?? '–')}</td>
            <td>${period(membership.seasonStart, membership.exitDate)}</td>
            <td class="table__actions" data-cell="actions">${actions}</td></tr>`;
        });
        const derived = data.derived.map((entry) => `<tr>
            <td>${ctx.escapeHtml(entry.seasonName)}</td>
            <td><a href="#/groups/${entry.groupId}">${ctx.escapeHtml(entry.groupName)}</a>
                <span class="badge">über ${ctx.escapeHtml(entry.via.join(', '))}</span></td>
            <td>–</td><td>–</td>
            <td>${period(entry.seasonStart, entry.exitDate)}</td><td></td></tr>`);
        const all = [...direct, ...derived];
        rows.innerHTML = all.length === 0 ? '<tr><td colspan="6" class="hint">Keiner Gruppe zugeordnet.</td></tr>' : all.join('');

        /**
         * Sends a change of a membership and reloads the list.
         *
         * @param {number} id Id of the membership.
         * @param {object} changes Changed attributes.
         * @param {string} success Toast shown on success.
         */
        const change = async (id, changes, success) => {
            ctx.setMessage('person-groups-error', null);
            try {
                await api.updateMembership(id, changes);
                ctx.showToast(success);
                render(await api.personMemberships(person.id));
            } catch (error) {
                ctx.setMessage('person-groups-error', error instanceof ApiError ? error.message : 'Änderung fehlgeschlagen');
            }
        };
        const find = (button, attribute) => data.memberships.find((item) => item.id === Number(button.dataset[attribute]));

        rows.querySelectorAll('[data-reactivate]').forEach((button) => button.addEventListener('click', () => {
            change(Number(button.dataset.reactivate), { exitDate: null }, 'Zuordnung wieder aktiv.');
        }));

        rows.querySelectorAll('[data-delete]').forEach((button) => button.addEventListener('click', async () => {
            const warning = 'Zuordnungen sollten nur gelöscht werden, wenn sie fehlerhaft erfasst wurden. '
                + 'Bei einem regulären Abgang bitte stattdessen «Beenden» verwenden.\n\nWirklich löschen?';
            if (!window.confirm(warning)) {
                return;
            }
            try {
                await api.deleteMembership(Number(button.dataset.delete));
                ctx.showToast('Zuordnung gelöscht.');
                render(await api.personMemberships(person.id));
            } catch (error) {
                ctx.setMessage('person-groups-error', error instanceof ApiError ? error.message : 'Löschen fehlgeschlagen');
            }
        }));

        rows.querySelectorAll('[data-end]').forEach((button) => button.addEventListener('click', () => {
            const membership = find(button, 'end');
            const cell = button.closest('[data-cell="actions"]');
            const clamped = [today, membership.seasonStart, membership.seasonEnd].sort()[1];
            cell.innerHTML = `<input type="date" value="${clamped}" min="${membership.seasonStart ?? ''}" max="${membership.seasonEnd ?? ''}" aria-label="Enddatum">
                <button type="button" class="button button--small button--primary" data-ok>OK</button>
                <button type="button" class="button button--small" data-cancel>Abbrechen</button>`;
            cell.querySelector('[data-cancel]').addEventListener('click', () => render(data));
            cell.querySelector('[data-ok]').addEventListener('click', () => {
                const value = cell.querySelector('input').value;
                if (value) {
                    change(membership.id, { exitDate: value }, 'Zuordnung beendet.');
                }
            });
        }));

        rows.querySelectorAll('[data-edit]').forEach((button) => button.addEventListener('click', () => {
            const membership = find(button, 'edit');
            const row = button.closest('tr');
            if (membership.groupType === 'TEAM') {
                row.querySelector('[data-cell="role"]').innerHTML = `<select aria-label="Rolle">${
                    meta.membershipRoles.filter((role) => role.value !== 'MEMBER')
                        .map((role) => `<option value="${role.value}"${role.value === membership.role ? ' selected' : ''}>${ctx.escapeHtml(role.label)}</option>`)
                        .join('')}</select>`;
            }
            row.querySelector('[data-cell="function"]').innerHTML =
                `<input type="text" maxlength="100" aria-label="Funktion" value="${ctx.escapeHtml(membership.function ?? '')}">`;
            const cell = row.querySelector('[data-cell="actions"]');
            cell.innerHTML = `<button type="button" class="button button--small button--primary" data-save>Speichern</button>
                <button type="button" class="button button--small" data-cancel>Abbrechen</button>`;
            cell.querySelector('[data-cancel]').addEventListener('click', () => render(data));
            cell.querySelector('[data-save]').addEventListener('click', () => {
                const changes = { function: row.querySelector('[data-cell="function"] input').value };
                const roleSelect = row.querySelector('[data-cell="role"] select');
                if (roleSelect) {
                    changes.role = roleSelect.value;
                }
                change(membership.id, changes, 'Zuordnung gespeichert.');
            });
        }));
    }

    document.getElementById('person-assign-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        ctx.setMessage('person-groups-error', null);
        const group = groups.find((item) => item.id === Number(groupSelect.value));
        if (!group) {
            return;
        }
        try {
            await api.createMembership({
                groupId: group.id,
                personId: person.id,
                seasonId: Number(document.getElementById('pa-season').value) || null,
                role: group.groupType === 'TEAM' ? roleSelect.value : null,
                function: document.getElementById('pa-function').value,
            });
            ctx.showToast('Person zugeordnet.');
            render(await api.personMemberships(person.id));
        } catch (error) {
            ctx.setMessage('person-groups-error', error instanceof ApiError ? error.message : 'Zuordnung fehlgeschlagen');
        }
    });

    document.getElementById('leave-club-button').addEventListener('click', (event) => {
        confirmInline(
            event.currentTarget.closest('header'),
            `Austritt von <strong>${ctx.escapeHtml(person.displayName)}</strong> aus dem Verein erfassen? `
            + 'Alle Zuordnungen der aktuellen Saison enden mit dem Saisonende, Zuordnungen in künftigen Saisons werden entfernt.',
            'Ja, Austritt erfassen',
            async () => {
                try {
                    render(await api.leaveClub(person.id));
                    ctx.showToast('Austritt erfasst.');
                } catch (error) {
                    ctx.setMessage('person-groups-error', error instanceof ApiError ? error.message : 'Austritt fehlgeschlagen');
                }
            },
        );
    });

    render(await api.personMemberships(person.id));
}

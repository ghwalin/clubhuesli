'use strict';
const $ = (s, el = document) => el.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const app = $('#app');
let user = null, lookups = null;

const ROLE_LABEL = {admin:'Admin', office:'Geschäftsstelle', trainer:'Trainer', communication:'Kommunikation', member:'Mitglied'};
const KIND_LABEL = {team:'Team', functionary:'Funktionär', other:'Andere'};
const has = (...r) => user && (user.roles.includes('admin') || r.some(x => user.roles.includes(x)));

async function api(method, path, data) {
  const r = await fetch('api/index.php?r=' + path, {
    method, credentials: 'same-origin',
    headers: {'X-Requested-With': 'clubadmin', ...(data ? {'Content-Type': 'application/json'} : {})},
    body: data ? JSON.stringify(data) : undefined,
  });
  const j = await r.json().catch(() => ({}));
  if (!r.ok) { const e = new Error(j.error || 'Fehler'); e.status = r.status; throw e; }
  return j;
}
const get = p => api('GET', p), post = (p, d = {}) => api('POST', p, d), put = (p, d = {}) => api('PUT', p, d), del = p => api('DELETE', p);

function toast(msg, bad) {
  const t = $('#toast'); t.textContent = msg; t.className = 'show' + (bad ? ' bad' : '');
  clearTimeout(toast.t); toast.t = setTimeout(() => t.className = '', 2600);
}
const guard = fn => async (...a) => { try { return await fn(...a); } catch (e) { if (e.status === 401) return boot(); toast(e.message, true); } };

// ---------- helpers ----------
const name = p => p.kind === 'legal' || (!p.first_name && p.org_name) ? p.org_name : `${p.first_name ?? ''} ${p.last_name ?? ''}`.trim();
const fmtDate = d => d ? new Date(d.replace(' ', 'T')).toLocaleDateString('de-CH') : '';
const fmtDT = d => d ? new Date(d.replace(' ', 'T')).toLocaleString('de-CH', {weekday:'short', day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '';
const inpDT = d => d ? d.replace(' ', 'T').slice(0, 16) : '';
const opts = (list, sel, label = x => x.name, blank) => (blank != null ? `<option value="">${esc(blank)}</option>` : '') +
  list.map(x => `<option value="${x.id}" ${String(x.id) === String(sel) ? 'selected' : ''}>${esc(label(x))}</option>`).join('');
const field = (label, n, v = '', type = 'text', extra = '') => `<div><label>${esc(label)}</label><input type="${type}" name="${n}" value="${esc(v)}" ${extra}></div>`;
const formData = f => {
  const d = {};
  for (const el of f.elements) {
    if (!el.name) continue;
    if (el.type === 'checkbox') d[el.name] = el.checked ? 1 : 0;
    else if (el.multiple) d[el.name] = [...el.selectedOptions].map(o => +o.value);
    else d[el.name] = el.value;
  }
  return d;
};
const confirmDel = msg => confirm(msg || 'Wirklich löschen?');

// ---------- layout ----------
function nav() {
  const items = [['termine', 'Termine']];
  if (has('office', 'trainer', 'communication')) items.push(['personen', 'Personen']);
  items.push(['gruppen', 'Gruppen'], ['helfer', 'Helfereinsätze']);
  if (has('communication')) items.push(['mail', 'E-Mail']);
  if (has()) items.push(['benutzer', 'Benutzer']);
  const cur = location.hash.split('/')[1];
  $('#nav').innerHTML = items.map(([k, l]) => `<a href="#/${k}" class="${cur === k ? 'on' : ''}">${l}</a>`).join('');
}

// ---------- views ----------
function viewLogin() {
  $('#top').hidden = true;
  app.innerHTML = `<form class="card login" id="f"><h1>Anmelden</h1>
    <p>${field('E-Mail', 'email', '', 'email', 'required autocomplete="username"')}</p>
    <p>${field('Passwort', 'password', '', 'password', 'required autocomplete="current-password"')}</p>
    <button>Anmelden</button> <span class="err" id="e"></span></form>`;
  $('#f').onsubmit = async ev => {
    ev.preventDefault();
    try { user = (await post('login', formData(ev.target))).user; location.hash = '#/termine'; boot(); }
    catch (e) { $('#e').textContent = e.message; }
  };
}

// Termine
async function viewEvents() {
  const [events, mine, groups] = await Promise.all([get('events'), get('my-persons'), get('groups')]);
  const pn = Object.fromEntries(mine.map(p => [p.id, p]));
  const teams = groups.filter(g => g.kind === 'team');
  const canCreate = has('trainer');
  app.innerHTML = `<div class="row sp"><h1>Termine</h1>${canCreate ? '<button id="new">+ Termin</button>' : ''}</div><div id="form"></div>` +
    (events.length ? events.map(e => {
      const closed = e.deadline && e.deadline < new Date().toISOString().replace('T', ' ');
      return `<div class="card"><div class="row sp"><div><a href="#/termine/${e.id}"><strong>${esc(e.title)}</strong></a>
        <span class="tag">${e.kind === 'game' ? 'Spiel' : 'Training'}</span> <span class="tag">${esc(e.group_name)}</span>
        <div class="mut">${fmtDT(e.starts_at)} · ${esc(e.location || '')}</div>
        ${e.deadline ? `<div class="mut">Anmeldung bis ${fmtDT(e.deadline)}</div>` : ''}</div>
        <div class="mut">✔ ${e.counts.yes || 0} · ✘ ${e.counts.no || 0}</div></div>
        ${mine.filter(p => e.eligible.includes(p.id)).map(p => {
          const m = e.mine.find(x => x.person_id == p.id);
          return `<div class="row" style="margin-top:.5rem" data-ev="${e.id}" data-p="${p.id}"><span>${esc(name(p))}</span>
            <button class="yes sm ${m?.status === 'yes' ? 'on' : ''}" data-s="yes" ${closed ? 'disabled' : ''}>Zusage</button>
            <button class="no sm ${m?.status === 'no' ? 'on' : ''}" data-s="no" ${closed ? 'disabled' : ''}>Absage</button>
            <input style="flex:1;min-width:120px" placeholder="Kommentar" value="${esc(m?.comment || '')}" ${closed ? 'disabled' : ''}></div>`;
        }).join('')}</div>`;
    }).join('') : '<p class="mut">Keine kommenden Termine.</p>');
  app.onclick = guard(async ev => {
    const b = ev.target.closest('button[data-s]'); if (!b) return;
    const row = b.closest('[data-ev]');
    const cur = b.classList.contains('on');
    await put(`events/${row.dataset.ev}/attendance/${row.dataset.p}`, {status: cur ? '' : b.dataset.s, comment: $('input', row).value});
    viewEvents();
  });
  if (canCreate) $('#new').onclick = () => eventForm(teams);
}
function eventForm(teams, e = {}) {
  $('#form').innerHTML = `<form class="card" id="ef"><div class="grid">
    <div><label>Team</label><select name="group_id" required>${opts(teams, e.group_id)}</select></div>
    <div><label>Art</label><select name="kind"><option value="training">Training</option><option value="game" ${e.kind === 'game' ? 'selected' : ''}>Spiel</option></select></div>
    ${field('Titel', 'title', e.title, 'text', 'required')}${field('Ort', 'location', e.location)}
    ${field('Beginn', 'starts_at', inpDT(e.starts_at), 'datetime-local', 'required')}${field('Ende', 'ends_at', inpDT(e.ends_at), 'datetime-local')}
    ${field('Anmeldefrist', 'deadline', inpDT(e.deadline), 'datetime-local')}
    ${e.id ? '' : field('Wöchentlich wiederholen (Anzahl)', 'repeat_weeks', 0, 'number', 'min="0" max="52"')}</div>
    <p><label>Bemerkungen</label><textarea name="notes">${esc(e.notes || '')}</textarea></p>
    <button>Speichern</button> <button type="button" class="sec" id="cancel">Abbrechen</button></form>`;
  $('#cancel').onclick = () => $('#form').innerHTML = '';
  $('#ef').onsubmit = guard(async ev => {
    ev.preventDefault();
    const d = formData(ev.target);
    if (e.id) { await put('events/' + e.id, d); viewEventDetail(e.id); } else { await post('events', d); viewEvents(); }
    toast('Gespeichert');
  });
}
async function viewEventDetail(id) {
  const e = await get('events/' + id);
  app.innerHTML = `<p><a href="#/termine">← Termine</a></p><div class="card"><div class="row sp"><h1>${esc(e.title)}</h1>
    ${e.can_manage ? '<div><button class="sec sm" id="edit">Bearbeiten</button> <button class="del sm" id="rm">Löschen</button></div>' : ''}</div>
    <div class="mut">${e.kind === 'game' ? 'Spiel' : 'Training'} · ${esc(e.group_name)} · ${fmtDT(e.starts_at)}${e.ends_at ? ' – ' + fmtDT(e.ends_at).slice(-5) : ''} · ${esc(e.location || '')}</div>
    ${e.deadline ? `<div class="mut">Anmeldefrist: ${fmtDT(e.deadline)}</div>` : ''}<p>${esc(e.notes || '')}</p></div>
    <div id="form"></div>
    <div class="card"><table class="stack"><thead><tr><th>Name</th><th>Status</th><th>Kommentar</th></tr></thead><tbody>
    ${e.participants.map(p => `<tr data-p="${p.id}"><td>${esc(name(p))}</td><td class="row">
      <button class="yes sm ${p.status === 'yes' ? 'on' : ''}" data-s="yes">Zusage</button><button class="no sm ${p.status === 'no' ? 'on' : ''}" data-s="no">Absage</button></td>
      <td><input value="${esc(p.comment || '')}"></td></tr>`).join('')}</tbody></table>
    <p class="mut">✔ ${e.participants.filter(p => p.status === 'yes').length} · ✘ ${e.participants.filter(p => p.status === 'no').length} · offen ${e.participants.filter(p => !p.status).length}</p></div>`;
  app.onclick = guard(async ev => {
    const b = ev.target.closest('button[data-s]'); if (!b) return;
    const tr = b.closest('tr');
    await put(`events/${id}/attendance/${tr.dataset.p}`, {status: b.classList.contains('on') ? '' : b.dataset.s, comment: $('input', tr).value});
    viewEventDetail(id);
  });
  if (e.can_manage) {
    const teams = (await get('groups')).filter(g => g.kind === 'team');
    $('#edit').onclick = () => eventForm(teams, e);
    $('#rm').onclick = guard(async () => { if (confirmDel('Termin löschen?')) { await del('events/' + id); location.hash = '#/termine'; } });
  }
}

// Personen
async function viewPersons() {
  const q = new URLSearchParams(location.hash.split('?')[1] || '');
  const list = await get('persons&' + new URLSearchParams({q: q.get('q') || '', kind: q.get('kind') || ''}));
  app.innerHTML = `<div class="row sp"><h1>Personen</h1>${has('office') ? '<a class="btn" href="#/personen/neu">+ Person</a>' : ''}</div>
    <form class="row card" id="s"><input name="q" placeholder="Suchen…" value="${esc(q.get('q') || '')}" style="flex:1">
    <select name="kind" style="width:auto"><option value="">Alle</option><option value="natural" ${q.get('kind') === 'natural' ? 'selected' : ''}>Natürlich</option><option value="legal" ${q.get('kind') === 'legal' ? 'selected' : ''}>Juristisch</option></select><button>Suchen</button></form>
    <div class="card"><table class="stack"><thead><tr><th>Name</th><th>E-Mail</th><th>Ort</th><th>Mitgliederart</th></tr></thead><tbody>
    ${list.map(p => `<tr class="click" onclick="location.hash='#/personen/${p.id}'"><td>${esc(name(p))}</td><td>${esc(p.email || '')}</td><td>${esc(p.city || '')}</td><td>${esc(p.member_type || '')}</td></tr>`).join('')}
    </tbody></table>${list.length ? '' : '<p class="mut">Keine Treffer.</p>'}</div>`;
  $('#s').onsubmit = ev => { ev.preventDefault(); location.hash = '#/personen?' + new URLSearchParams(formData(ev.target)); };
}
async function viewPerson(id) {
  const isNew = id === 'neu';
  const p = isNew ? {kind: 'natural', photo_consent: 0, parents: [], children: [], memberships: []} : await get('persons/' + id);
  const edit = has('office');
  const all = edit ? (await get('persons')).filter(x => x.kind === 'natural' && x.id != id) : [];
  const dis = edit ? '' : 'disabled';
  const lbl = x => name(x) + (x.id ? '' : '');
  app.innerHTML = `<p><a href="#/personen">← Personen</a></p><form class="card" id="f"><h1>${isNew ? 'Neue Person' : esc(name(p))}</h1>
    <p><label>Art</label><select name="kind" ${dis}><option value="natural">Natürliche Person</option><option value="legal" ${p.kind === 'legal' ? 'selected' : ''}>Juristische Person (Sponsor, Gemeinde)</option></select></p>
    <fieldset id="nat" style="border:0;padding:0"><div class="grid">${field('Vorname', 'first_name', p.first_name, 'text', dis)}${field('Nachname', 'last_name', p.last_name, 'text', dis)}
      ${field('Geburtsdatum', 'birthdate', p.birthdate, 'date', dis)}
      <div><label>Mitgliederart</label><select name="member_type_id" ${dis}>${opts(lookups.member_types, p.member_type_id, x => x.name, '–')}</select></div>
      ${'ahv_number' in p || isNew ? field('AHV-Nummer (Spielerinnen)', 'ahv_number', p.ahv_number, 'text', dis + ' placeholder="756.XXXX.XXXX.XX"') : ''}
      ${'js_number' in p || isNew ? field('J+S-Nummer', 'js_number', p.js_number, 'text', dis) : ''}</div>
      <p><label><input type="checkbox" name="photo_consent" ${p.photo_consent == 1 ? 'checked' : ''} ${dis}> Einwilligung Foto</label></p></fieldset>
    <fieldset id="leg" style="border:0;padding:0"><div class="grid">${field('Name', 'org_name', p.org_name, 'text', dis)}${field('Kontaktperson', 'contact_person', p.contact_person, 'text', dis)}</div></fieldset>
    <div class="grid">${field('E-Mail', 'email', p.email, 'email', dis)}${field('Strasse', 'street', p.street, 'text', dis)}${field('Nr.', 'street_no', p.street_no, 'text', dis)}
      ${field('PLZ', 'zip', p.zip, 'text', dis)}${field('Ort', 'city', p.city, 'text', dis)}</div>
    ${edit ? `<div class="grid" id="rel" style="margin-top:.75rem"><div><label>Eltern (Mehrfachauswahl)</label><select name="parent_ids" multiple size="5">${opts(all, 0, lbl).replace(/ selected/g, '')}</select></div>
      <div><label>Kinder</label><select name="child_ids" multiple size="5">${opts(all, 0, lbl)}</select></div></div>` : ''}
    ${!edit ? `<p>Eltern: ${p.parents.map(x => esc(name(x))).join(', ') || '–'}<br>Kinder: ${p.children.map(x => esc(name(x))).join(', ') || '–'}</p>` : ''}
    <p><label>Notizen</label><textarea name="notes" ${dis}>${esc(p.notes || '')}</textarea></p>
    ${edit ? `<button>Speichern</button> ${!isNew && has() ? '<button type="button" class="del" id="rm">Löschen</button>' : ''}` : ''}</form>
    ${isNew ? '' : `<div class="card"><h2>Gruppen</h2><table><thead><tr><th>Gruppe</th><th>Rolle</th><th>Von</th><th>Bis</th></tr></thead><tbody>
    ${p.memberships.map(m => `<tr><td><a href="#/gruppen/${m.group_id}">${esc(m.group_name)}</a></td><td>${esc(m.role_name)}</td><td>${fmtDate(m.start_date)}</td><td>${fmtDate(m.end_date)}</td></tr>`).join('') || '<tr><td class="mut">Keine</td></tr>'}</tbody></table></div>`}`;
  const sync = () => { const l = $('[name=kind]').value === 'legal'; $('#nat').hidden = l; $('#leg').hidden = !l; };
  $('[name=kind]').onchange = sync; sync();
  if (edit) {
    for (const [n, list] of [['parent_ids', p.parents], ['child_ids', p.children]]) {
      for (const o of $(`[name=${n}]`).options) o.selected = list.some(x => x.id == o.value);
    }
    $('#f').onsubmit = guard(async ev => {
      ev.preventDefault();
      const d = formData(ev.target);
      if (isNew) { const r = await post('persons', d); location.hash = '#/personen/' + r.id; }
      else { await put('persons/' + id, d); viewPerson(id); }
      toast('Gespeichert');
    });
    if ($('#rm')) $('#rm').onclick = guard(async () => { if (confirmDel('Person samt Zuordnungen löschen?')) { await del('persons/' + id); location.hash = '#/personen'; } });
  }
}

// Gruppen
async function viewGroups() {
  const list = await get('groups');
  app.innerHTML = `<div class="row sp"><h1>Gruppen</h1>${has('office') ? '<a class="btn" href="#/gruppen/neu">+ Gruppe</a>' : ''}</div><div class="card"><table><thead><tr><th>Bezeichnung</th><th>Art</th><th>Mitglieder</th></tr></thead><tbody>
    ${list.map(g => `<tr class="click" onclick="location.hash='#/gruppen/${g.id}'"><td>${esc(g.name)}</td><td>${KIND_LABEL[g.kind]}</td><td>${g.member_count}</td></tr>`).join('')}</tbody></table></div>`;
}
async function viewGroup(id) {
  const isNew = id === 'neu';
  const g = isNew ? {kind: 'team', parents: [], children: [], members: []} : await get('groups/' + id);
  const edit = has('office');
  const [groups, persons] = edit ? await Promise.all([get('groups'), isNew ? [] : get('persons')]) : [[], []];
  const others = groups.filter(x => x.id != id);
  const today = new Date().toISOString().slice(0, 10);
  const isCurrent = m => m.start_date <= today && (!m.end_date || m.end_date >= today);
  app.innerHTML = `<p><a href="#/gruppen">← Gruppen</a></p><form class="card" id="f"><h1>${isNew ? 'Neue Gruppe' : esc(g.name)}</h1>
    <div class="grid">${field('Bezeichnung', 'name', g.name, 'text', 'required ' + (edit ? '' : 'disabled'))}
    <div><label>Art</label><select name="kind" ${edit ? '' : 'disabled'}>${Object.entries(KIND_LABEL).map(([k, l]) => `<option value="${k}" ${g.kind === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div>
    ${edit ? `<div><label>Enthalten in (Obergruppen)</label><select name="parent_ids" multiple size="4">${opts(others, 0)}</select></div>
    <div><label>Enthält (Untergruppen)</label><select name="child_ids" multiple size="4">${opts(others, 0)}</select></div>` : ''}</div>
    ${edit ? `<p><button>Speichern</button> ${!isNew && has() ? '<button type="button" class="del" id="rm">Löschen</button>' : ''}</p>` : `<p class="mut">Obergruppen: ${g.parents.map(x => esc(x.name)).join(', ') || '–'} · Untergruppen: ${g.children.map(x => esc(x.name)).join(', ') || '–'}</p>`}</form>
    ${isNew ? '' : `<div class="card"><h2>Mitglieder</h2><table class="stack"><thead><tr><th>Person</th><th>Rolle</th><th>Von</th><th>Bis</th><th></th></tr></thead><tbody>
    ${g.members.map(m => `<tr data-id="${m.id}" style="${isCurrent(m) ? '' : 'opacity:.55'}"><td><a href="#/personen/${m.person_id}">${esc(name(m))}</a></td>
      <td>${edit ? `<select data-f="role_id">${opts(lookups.roles, m.role_id)}</select>` : esc(m.role_name)}</td>
      <td>${edit ? `<input type="date" data-f="start_date" value="${m.start_date}">` : fmtDate(m.start_date)}</td>
      <td>${edit ? `<input type="date" data-f="end_date" value="${m.end_date || ''}">` : fmtDate(m.end_date)}</td>
      <td>${edit ? '<button class="del sm" data-rm>✕</button>' : ''}</td></tr>`).join('')}</tbody></table>
    ${edit ? `<form class="row" id="add" style="margin-top:1rem"><select name="person_id" required style="flex:2">${opts(persons, '', name, 'Person wählen…')}</select>
      <select name="role_id" style="flex:1">${opts(lookups.roles, '', x => x.name)}</select><input type="date" name="start_date" value="${today}" style="flex:1"><button>Zuordnen</button></form>` : ''}</div>`}`;
  if (!edit) return;
  for (const [n, list] of [['parent_ids', g.parents], ['child_ids', g.children]]) for (const o of $(`[name=${n}]`).options) o.selected = list.some(x => x.id == o.value);
  $('#f').onsubmit = guard(async ev => {
    ev.preventDefault();
    const d = formData(ev.target);
    if (isNew) { const r = await post('groups', d); location.hash = '#/gruppen/' + r.id; } else { await put('groups/' + id, d); viewGroup(id); }
    toast('Gespeichert');
  });
  if (isNew) return;
  if ($('#rm')) $('#rm').onclick = guard(async () => { if (confirmDel('Gruppe löschen?')) { await del('groups/' + id); location.hash = '#/gruppen'; } });
  $('#add').onsubmit = guard(async ev => { ev.preventDefault(); await post(`groups/${id}/members`, formData(ev.target)); viewGroup(id); });
  app.onchange = guard(async ev => {
    const tr = ev.target.closest('tr[data-id]'); if (!tr || !ev.target.dataset.f) return;
    await put('members/' + tr.dataset.id, {[ev.target.dataset.f]: ev.target.value}); toast('Gespeichert'); viewGroup(id);
  });
  app.onclick = guard(async ev => {
    const b = ev.target.closest('[data-rm]'); if (!b) return;
    if (confirmDel('Zuordnung entfernen? (Tipp: besser Enddatum setzen)')) { await del('members/' + b.closest('tr').dataset.id); viewGroup(id); }
  });
}

// Helfereinsätze
async function viewHelpers() {
  const list = await get('helper-events');
  const edit = has('office');
  app.innerHTML = `<div class="row sp"><h1>Helfereinsätze</h1>${edit ? '<button id="new">+ Event</button>' : ''}</div><div id="form"></div>
    <div class="card"><table class="stack"><thead><tr><th>Event</th><th>Datum</th><th>Ort</th><th>Besetzt</th></tr></thead><tbody>
    ${list.map(e => `<tr class="click" onclick="location.hash='#/helfer/${e.id}'"><td>${esc(e.name)}</td><td>${fmtDate(e.event_date)}</td><td>${esc(e.location || '')}</td><td>${e.filled}/${e.slots}</td></tr>`).join('')}</tbody></table></div>
    ${edit ? '<div class="card"><h2>Einsätze pro aktivem Mitglied</h2><div id="rep" class="mut">Lade…</div></div>' : ''}`;
  if (!edit) return;
  $('#new').onclick = () => {
    $('#form').innerHTML = `<form class="card" id="hf"><div class="grid">${field('Name', 'name', '', 'text', 'required')}${field('Datum', 'event_date', '', 'date', 'required')}${field('Ort', 'location')}</div><p><button>Anlegen</button></p></form>`;
    $('#hf').onsubmit = guard(async ev => { ev.preventDefault(); const r = await post('helper-events', formData(ev.target)); location.hash = '#/helfer/' + r.id; });
  };
  const rep = await get('helper-report');
  $('#rep').innerHTML = `<table><tbody>${rep.map(r => `<tr><td>${esc(name(r))}</td><td style="text-align:right"><strong>${r.count}</strong></td></tr>`).join('')}</tbody></table>`;
}
async function viewHelper(id) {
  const e = await get('helper-events/' + id);
  const mine = e.my_person_ids;
  const persons = e.can_manage ? (await get('persons')).filter(p => p.kind === 'natural') : [];
  app.innerHTML = `<p><a href="#/helfer">← Helfereinsätze</a></p><div class="card"><div class="row sp"><h1>${esc(e.name)}</h1>
    ${e.can_manage ? '<button class="del sm" id="rm">Event löschen</button>' : ''}</div><div class="mut">${fmtDate(e.event_date)} · ${esc(e.location || '')}</div></div>
    ${e.shifts.map(s => {
      const full = s.people.length >= s.slots;
      return `<div class="card" data-s="${s.id}"><div class="row sp"><strong>${esc(s.name)}</strong><span class="mut">${s.starts_at ? fmtDT(s.starts_at) : ''} · ${s.people.length}/${s.slots}</span></div>
      <ul>${s.people.map(p => `<li>${esc(name(p))} ${e.can_manage || mine.includes(p.id) ? `<button class="del sm" data-un="${p.id}">✕</button>` : ''}</li>`).join('')}</ul>
      <div class="row">${mine.filter(m => !s.people.some(p => p.id === m)).map(m => `<button class="sm" data-me="${m}" ${full ? 'disabled' : ''}>Eintragen${mine.length > 1 ? ' (' + m + ')' : ''}</button>`).join('')}
      ${e.can_manage ? `<select data-pick style="width:auto">${opts(persons, '', name, 'Person…')}</select><button class="sec sm" data-add>Zuteilen</button> <button class="del sm" data-rmshift>Schicht löschen</button>` : ''}</div></div>`;
    }).join('')}
    ${e.can_manage ? `<form class="card row" id="sf">${field('Schicht', 'name', '', 'text', 'required placeholder="z.B. Kasse 1. Schicht"')}${field('Beginn', 'starts_at', '', 'datetime-local')}${field('Ende', 'ends_at', '', 'datetime-local')}${field('Plätze', 'slots', 1, 'number', 'min="1"')}<button>+ Schicht</button></form>` : ''}`;
  const reload = () => viewHelper(id);
  app.onclick = guard(async ev => {
    const t = ev.target, card = t.closest('[data-s]'); if (!card) return;
    const sid = card.dataset.s;
    if (t.dataset.me) await post(`shifts/${sid}/assign/${t.dataset.me}`);
    else if (t.dataset.un) await del(`shifts/${sid}/assign/${t.dataset.un}`);
    else if (t.hasAttribute('data-add')) { const v = $('[data-pick]', card).value; if (!v) return; await post(`shifts/${sid}/assign/${v}`); }
    else if (t.hasAttribute('data-rmshift')) { if (!confirmDel('Schicht löschen?')) return; await del('shifts/' + sid); }
    else return;
    reload();
  });
  if (e.can_manage) {
    $('#sf').onsubmit = guard(async ev => { ev.preventDefault(); await post(`helper-events/${id}/shifts`, formData(ev.target)); reload(); });
    $('#rm').onclick = guard(async () => { if (confirmDel('Event samt Schichten löschen?')) { await del('helper-events/' + id); location.hash = '#/helfer'; } });
  }
}

// E-Mail
async function viewMail() {
  const groups = await get('groups');
  app.innerHTML = `<h1>E-Mail an Gruppe</h1><form class="card" id="f"><p><label>Gruppe (inkl. Untergruppen, aktuelle Mitglieder)</label><select name="group_id" required>${opts(groups, '', g => g.name, 'Wählen…')}</select></p>
    <p class="mut" id="info"></p><p>${field('Betreff', 'subject', '', 'text', 'required')}</p><p><label>Text</label><textarea name="body" required style="min-height:200px"></textarea></p><button>Senden</button></form>`;
  $('[name=group_id]').onchange = guard(async ev => {
    if (!ev.target.value) return $('#info').textContent = '';
    const r = await get('mail-recipients/' + ev.target.value);
    $('#info').textContent = `${r.with_email} von ${r.total} Personen haben eine E-Mail-Adresse.`;
  });
  $('#f').onsubmit = guard(async ev => {
    ev.preventDefault();
    if (!confirm('E-Mail jetzt versenden?')) return;
    const r = await post('mail', formData(ev.target));
    toast(`${r.sent} von ${r.total} gesendet`); ev.target.reset();
  });
}

// Benutzer
async function viewUsers() {
  const [users, persons] = await Promise.all([get('users'), get('persons')]);
  app.innerHTML = `<div class="row sp"><h1>Benutzer</h1><button id="new">+ Benutzer</button></div><div id="form"></div>
    <div class="card"><table class="stack"><thead><tr><th>E-Mail</th><th>Person</th><th>Rollen</th><th>Aktiv</th></tr></thead><tbody>
    ${users.map(u => `<tr class="click" data-id="${u.id}"><td>${esc(u.email)}</td><td>${esc(u.person || '')}</td><td>${u.roles.map(r => ROLE_LABEL[r]).join(', ')}</td><td>${u.active == 1 ? '✔' : '✘'}</td></tr>`).join('')}</tbody></table></div>`;
  const form = (u = {roles: ['member'], active: 1}) => {
    $('#form').innerHTML = `<form class="card" id="uf"><div class="grid">${field('E-Mail', 'email', u.email, 'email', 'required')}${field(u.id ? 'Neues Passwort (leer = unverändert)' : 'Passwort', 'password', '', 'password', (u.id ? '' : 'required ') + 'minlength="8" autocomplete="new-password"')}
      <div><label>Person</label><select name="person_id">${opts(persons, u.person_id, name, '–')}</select></div></div>
      <p>${Object.entries(ROLE_LABEL).map(([k, l]) => `<label style="display:inline-block;margin-right:1rem"><input type="checkbox" data-role="${k}" ${u.roles.includes(k) ? 'checked' : ''}> ${l}</label>`).join('')}
      <label style="display:inline-block"><input type="checkbox" name="active" ${u.active == 1 ? 'checked' : ''}> Aktiv</label></p>
      <button>Speichern</button> ${u.id ? '<button type="button" class="del" id="rm">Löschen</button>' : ''}</form>`;
    $('#uf').onsubmit = guard(async ev => {
      ev.preventDefault();
      const d = formData(ev.target); d.roles = [...ev.target.querySelectorAll('[data-role]:checked')].map(x => x.dataset.role);
      if (!d.password) delete d.password;
      u.id ? await put('users/' + u.id, d) : await post('users', d);
      viewUsers(); toast('Gespeichert');
    });
    if (u.id) $('#rm').onclick = guard(async () => { if (confirmDel('Benutzer löschen?')) { await del('users/' + u.id); viewUsers(); } });
    $('#form').scrollIntoView();
  };
  $('#new').onclick = () => form();
  app.onclick = ev => { const tr = ev.target.closest('tr[data-id]'); if (tr) form(users.find(u => u.id == tr.dataset.id)); };
}

// ---------- router ----------
const routes = [
  [/^#\/termine$/, viewEvents], [/^#\/termine\/(\d+)$/, viewEventDetail],
  [/^#\/personen(\?.*)?$/, viewPersons], [/^#\/personen\/(\d+|neu)$/, viewPerson],
  [/^#\/gruppen$/, viewGroups], [/^#\/gruppen\/(\d+|neu)$/, viewGroup],
  [/^#\/helfer$/, viewHelpers], [/^#\/helfer\/(\d+)$/, viewHelper],
  [/^#\/mail$/, viewMail], [/^#\/benutzer$/, viewUsers],
];
const render = guard(async () => {
  if (!user) return viewLogin();
  nav(); app.onclick = app.onchange = null;
  const h = location.hash || '#/termine';
  for (const [re, fn] of routes) { const m = h.match(re); if (m) return fn(...m.slice(1).filter(x => x && !x.startsWith('?'))); }
  location.hash = '#/termine';
});
async function boot() {
  user = (await get('me')).user;
  if (user) { lookups = await get('lookups'); $('#top').hidden = false; }
  render();
}
window.addEventListener('hashchange', render);
$('#logout').onclick = async () => { await post('logout'); user = null; location.hash = ''; render(); };
boot();

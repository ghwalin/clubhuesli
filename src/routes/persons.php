<?php
const PERSON_FIELDS = ['kind', 'first_name', 'last_name', 'org_name', 'contact_person', 'email', 'birthdate', 'street',
    'street_no', 'zip', 'city', 'ahv_number', 'js_number', 'member_type_id', 'photo_consent', 'notes'];

function can_edit_persons(): bool { return has_role('admin', 'office'); }

route('GET', 'lookups', function () {
    require_login();
    return [
        'member_types' => Db::all('SELECT * FROM member_type ORDER BY name'),
        'roles' => Db::all('SELECT * FROM role ORDER BY name'),
    ];
});

route('GET', 'persons', function () {
    require_login();
    $q = trim($_GET['q'] ?? '');
    $where = [];
    $p = [];
    if (!can_edit_persons() && !has_role('communication')) {
        // trainers: members of own teams; others: self + children
        $ids = my_persons();
        foreach (trainer_groups() as $g) foreach (group_persons($g) as $gp) $ids[] = (int) $gp['id'];
        $ids = array_values(array_unique($ids)) ?: [0];
        $where[] = 'p.id IN (' . Db::in($ids) . ')';
        $p = $ids;
    }
    if ($q !== '') {
        $where[] = '(p.first_name LIKE ? OR p.last_name LIKE ? OR p.org_name LIKE ? OR p.email LIKE ?)';
        array_push($p, ...array_fill(0, 4, "%$q%"));
    }
    if (!empty($_GET['kind'])) { $where[] = 'p.kind=?'; $p[] = $_GET['kind']; }
    return Db::all('SELECT p.id, p.kind, p.first_name, p.last_name, p.org_name, p.email, p.city, mt.name AS member_type
                    FROM person p LEFT JOIN member_type mt ON mt.id=p.member_type_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY COALESCE(p.last_name, p.org_name), p.first_name LIMIT 500', $p);
});

route('GET', 'persons/{id}', function (int $id) {
    require_login();
    $allowed = can_edit_persons() || has_role('communication') || in_array($id, my_persons(), true);
    if (!$allowed && has_role('trainer')) {
        foreach (trainer_groups() as $g) if (in_array($id, array_column(group_persons($g), 'id'))) $allowed = true;
    }
    if (!$allowed) throw new HttpError('Keine Berechtigung', 403);
    $p = Db::one('SELECT * FROM person WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    if (!can_edit_persons()) { unset($p['ahv_number']); if (!has_role('trainer')) unset($p['js_number']); }
    $p['parents'] = Db::all('SELECT p.id, p.first_name, p.last_name FROM person_relation r JOIN person p ON p.id=r.parent_id WHERE r.child_id=?', [$id]);
    $p['children'] = Db::all('SELECT p.id, p.first_name, p.last_name FROM person_relation r JOIN person p ON p.id=r.child_id WHERE r.parent_id=?', [$id]);
    $p['memberships'] = Db::all('SELECT gm.id, gm.group_id, g.name AS group_name, r.name AS role_name, gm.start_date, gm.end_date
                                 FROM group_member gm JOIN grp g ON g.id=gm.group_id JOIN role r ON r.id=gm.role_id
                                 WHERE gm.person_id=? ORDER BY gm.start_date DESC', [$id]);
    return $p;
});

function validate_person(array $d, bool $create): void
{
    if (($d['kind'] ?? 'natural') === 'legal') { if ($create || array_key_exists('org_name', $d)) required($d, ['org_name']); }
    elseif ($create) required($d, ['first_name', 'last_name']);
    if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) throw new HttpError('Ungültige E-Mail', 422);
    if (!empty($d['ahv_number']) && !preg_match('/^756\.\d{4}\.\d{4}\.\d{2}$/', $d['ahv_number'])) throw new HttpError('AHV-Nummer: Format 756.XXXX.XXXX.XX', 422);
}

function save_relations(int $id): void
{
    $b = body();
    if (isset($b['parent_ids'])) {
        Db::exec('DELETE FROM person_relation WHERE child_id=?', [$id]);
        foreach (array_unique($b['parent_ids']) as $pid) if ((int) $pid !== $id) Db::exec('INSERT INTO person_relation VALUES (?,?)', [(int) $pid, $id]);
    }
    if (isset($b['child_ids'])) {
        Db::exec('DELETE FROM person_relation WHERE parent_id=?', [$id]);
        foreach (array_unique($b['child_ids']) as $cid) if ((int) $cid !== $id) Db::exec('INSERT INTO person_relation VALUES (?,?)', [$id, (int) $cid]);
    }
}

route('POST', 'persons', function () {
    require_role('office');
    $d = fields(PERSON_FIELDS) + ['kind' => 'natural'];
    validate_person($d, true);
    $id = insert('person', $d);
    save_relations($id);
    return ['id' => $id];
});

route('PUT', 'persons/{id}', function (int $id) {
    require_role('office');
    $d = fields(PERSON_FIELDS);
    validate_person($d, false);
    update('person', $id, $d);
    save_relations($id);
});

route('DELETE', 'persons/{id}', function (int $id) { require_role('admin'); Db::exec('DELETE FROM person WHERE id=?', [$id]); });

// Helper counts per member
route('GET', 'persons/{id}/helper-count', function (int $id) {
    require_login();
    return ['count' => (int) Db::val('SELECT COUNT(*) FROM helper_assignment WHERE person_id=?', [$id])];
});

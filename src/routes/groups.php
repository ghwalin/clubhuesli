<?php
route('GET', 'groups', function () {
    require_login();
    $rows = Db::all('SELECT g.*, (SELECT COUNT(*) FROM group_member m WHERE m.group_id=g.id AND m.start_date<=CURDATE() AND (m.end_date IS NULL OR m.end_date>=CURDATE())) AS member_count
                     FROM grp g ORDER BY g.kind, g.name');
    if (!has_role('admin', 'office', 'communication')) {
        $mine = array_merge(my_groups(), trainer_groups());
        $rows = array_values(array_filter($rows, fn($g) => in_array((int) $g['id'], $mine, true)));
    }
    return $rows;
});

route('GET', 'groups/{id}', function (int $id) {
    require_login();
    $g = Db::one('SELECT * FROM grp WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    $g['parents'] = Db::all('SELECT g.id, g.name FROM group_group gg JOIN grp g ON g.id=gg.parent_id WHERE gg.child_id=?', [$id]);
    $g['children'] = Db::all('SELECT g.id, g.name FROM group_group gg JOIN grp g ON g.id=gg.child_id WHERE gg.parent_id=?', [$id]);
    $g['members'] = Db::all('SELECT gm.id, gm.person_id, p.first_name, p.last_name, p.org_name, r.id AS role_id, r.name AS role_name, gm.start_date, gm.end_date
                             FROM group_member gm JOIN person p ON p.id=gm.person_id JOIN role r ON r.id=gm.role_id
                             WHERE gm.group_id=? ORDER BY (gm.end_date IS NOT NULL), p.last_name, p.first_name', [$id]);
    return $g;
});

function save_group_links(int $id): void
{
    $b = body();
    foreach (['parent_ids' => ['child_id', 'parent_id'], 'child_ids' => ['parent_id', 'child_id']] as $key => [$me, $other]) {
        if (!isset($b[$key])) continue;
        Db::exec("DELETE FROM group_group WHERE $me=?", [$id]);
        foreach (array_unique($b[$key]) as $oid) {
            $oid = (int) $oid;
            if ($oid === $id) continue;
            // prevent cycles: the new parent must not be inside the child's subtree
            [$parent, $child] = $key === 'parent_ids' ? [$oid, $id] : [$id, $oid];
            if (in_array($parent, group_tree($child), true)) throw new HttpError('Zyklische Gruppenstruktur', 422);
            Db::exec('INSERT INTO group_group (parent_id, child_id) VALUES (?,?)', [$parent, $child]);
        }
    }
}

route('POST', 'groups', function () {
    require_role('office');
    $d = fields(['name', 'kind']);
    required($d, ['name']);
    $id = insert('grp', $d);
    save_group_links($id);
    return ['id' => $id];
});
route('PUT', 'groups/{id}', function (int $id) { require_role('office'); update('grp', $id, fields(['name', 'kind'])); save_group_links($id); });
route('DELETE', 'groups/{id}', function (int $id) { require_role('admin'); Db::exec('DELETE FROM grp WHERE id=?', [$id]); });

// Memberships (Zuordnung Person <-> Gruppe, mit Rolle und Zeitraum)
route('POST', 'groups/{id}/members', function (int $gid) {
    require_role('office');
    $d = fields(['person_id', 'role_id', 'start_date', 'end_date']);
    required($d, ['person_id', 'role_id']);
    $d['start_date'] ??= date('Y-m-d');
    $d['group_id'] = $gid;
    return ['id' => insert('group_member', $d)];
});
route('PUT', 'members/{id}', function (int $id) { require_role('office'); update('group_member', $id, fields(['role_id', 'start_date', 'end_date'])); });
route('DELETE', 'members/{id}', function (int $id) { require_role('office'); Db::exec('DELETE FROM group_member WHERE id=?', [$id]); });

route('POST', 'roles', function () { require_role('admin'); $d = fields(['name', 'is_trainer']); required($d, ['name']); return ['id' => insert('role', $d)]; });

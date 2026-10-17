<?php
const EVENT_FIELDS = ['kind', 'title', 'location', 'starts_at', 'ends_at', 'notes', 'deadline'];

function visible_team_ids(): array
{
    if (has_role('admin', 'office')) return array_map('intval', array_column(Db::all('SELECT id FROM grp WHERE kind="team"'), 'id'));
    return array_values(array_unique([...my_groups(), ...trainer_groups()]));
}

route('GET', 'events', function () {
    require_login();
    $ids = visible_team_ids();
    if (!empty($_GET['group_id'])) $ids = array_values(array_intersect($ids, [(int) $_GET['group_id']]));
    if (!$ids) return [];
    $from = $_GET['from'] ?? date('Y-m-d');
    $pids = my_persons();
    $rows = Db::all('SELECT e.*, g.name AS group_name FROM event e JOIN grp g ON g.id=e.group_id
                     WHERE e.group_id IN (' . Db::in($ids) . ') AND e.starts_at>=? ORDER BY e.starts_at LIMIT 200', [...$ids, $from]);
    foreach ($rows as &$r) {
        $r['can_manage'] = can_manage_team((int) $r['group_id']);
        $r['counts'] = Db::one('SELECT SUM(status="yes") AS yes, SUM(status="no") AS no FROM attendance WHERE event_id=?', [$r['id']]);
        $r['eligible'] = array_values(array_intersect($pids, array_map('intval', array_column(group_persons((int) $r['group_id'], substr($r['starts_at'], 0, 10)), 'id'))));
        $r['mine'] = $pids ? Db::all('SELECT person_id, status, comment FROM attendance WHERE event_id=? AND person_id IN (' . Db::in($pids) . ')', [$r['id'], ...$pids]) : [];
    }
    return $rows;
});

route('GET', 'my-persons', function () {
    require_login();
    $ids = my_persons();
    return $ids ? Db::all('SELECT id, first_name, last_name FROM person WHERE id IN (' . Db::in($ids) . ')', $ids) : [];
});

route('GET', 'events/{id}', function (int $id) {
    require_login();
    $e = Db::one('SELECT e.*, g.name AS group_name FROM event e JOIN grp g ON g.id=e.group_id WHERE e.id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    if (!in_array((int) $e['group_id'], visible_team_ids(), true)) throw new HttpError('Keine Berechtigung', 403);
    $e['can_manage'] = can_manage_team((int) $e['group_id']);
    $att = [];
    foreach (Db::all('SELECT person_id, status, comment FROM attendance WHERE event_id=?', [$id]) as $a) $att[$a['person_id']] = $a;
    $e['participants'] = array_map(fn($p) => $p + ['status' => $att[$p['id']]['status'] ?? null, 'comment' => $att[$p['id']]['comment'] ?? null],
        group_persons((int) $e['group_id'], substr($e['starts_at'], 0, 10)));
    if (!$e['can_manage']) {
        $mine = my_persons();
        $e['participants'] = array_values(array_filter($e['participants'], fn($p) => in_array((int) $p['id'], $mine, true)));
    }
    return $e;
});

route('POST', 'events', function () {
    $d = fields(EVENT_FIELDS);
    $gid = (int) (body()['group_id'] ?? 0);
    require_login();
    if (!can_manage_team($gid)) throw new HttpError('Keine Berechtigung', 403);
    required($d, ['title', 'starts_at']);
    $d['group_id'] = $gid;
    $ids = [];
    // optional weekly repetition for trainings
    $repeat = min(52, max(0, (int) (body()['repeat_weeks'] ?? 0)));
    for ($i = 0; $i <= $repeat; $i++) {
        $x = $d;
        foreach (['starts_at', 'ends_at', 'deadline'] as $f) if (!empty($x[$f])) $x[$f] = date('Y-m-d H:i:s', strtotime($x[$f] . " +$i week"));
        $ids[] = insert('event', $x);
    }
    return ['id' => $ids[0], 'created' => count($ids)];
});

route('PUT', 'events/{id}', function (int $id) {
    require_login();
    $e = Db::one('SELECT group_id FROM event WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    if (!can_manage_team((int) $e['group_id'])) throw new HttpError('Keine Berechtigung', 403);
    update('event', $id, fields(EVENT_FIELDS));
});

route('DELETE', 'events/{id}', function (int $id) {
    require_login();
    $e = Db::one('SELECT group_id FROM event WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    if (!can_manage_team((int) $e['group_id'])) throw new HttpError('Keine Berechtigung', 403);
    Db::exec('DELETE FROM event WHERE id=?', [$id]);
});

// An-/Abmeldung: Trainer für alle seiner Spielerinnen (auch nach Frist), Mitglieder für sich/Kinder bis zur Frist
route('PUT', 'events/{id}/attendance/{pid}', function (int $id, int $pid) {
    $u = require_login();
    $e = Db::one('SELECT * FROM event WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    $manage = can_manage_team((int) $e['group_id']);
    if (!$manage) {
        if (!in_array($pid, my_persons(), true)) throw new HttpError('Keine Berechtigung', 403);
        if ($e['deadline'] && $e['deadline'] < date('Y-m-d H:i:s')) throw new HttpError('Anmeldefrist abgelaufen', 422);
    }
    if (!in_array($pid, array_column(group_persons((int) $e['group_id'], substr($e['starts_at'], 0, 10)), 'id'))) throw new HttpError('Person nicht im Team', 422);
    $b = body();
    if (($b['status'] ?? '') === '') { Db::exec('DELETE FROM attendance WHERE event_id=? AND person_id=?', [$id, $pid]); return; }
    if (!in_array($b['status'], ['yes', 'no'], true)) throw new HttpError('Ungültiger Status', 422);
    Db::exec('INSERT INTO attendance (event_id, person_id, status, comment, updated_by) VALUES (?,?,?,?,?)
              ON DUPLICATE KEY UPDATE status=VALUES(status), comment=VALUES(comment), updated_by=VALUES(updated_by)',
        [$id, $pid, $b['status'], ($b['comment'] ?? '') ?: null, $u['id']]);
});

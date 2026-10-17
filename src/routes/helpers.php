<?php
// Helfereinsätze: Event -> Schichten -> Zuteilungen
route('GET', 'helper-events', function () {
    require_login();
    return Db::all('SELECT he.*, (SELECT COALESCE(SUM(slots),0) FROM helper_shift s WHERE s.helper_event_id=he.id) AS slots,
                    (SELECT COUNT(*) FROM helper_assignment a JOIN helper_shift s ON s.id=a.shift_id WHERE s.helper_event_id=he.id) AS filled
                    FROM helper_event he ORDER BY event_date DESC LIMIT 200');
});

route('GET', 'helper-events/{id}', function (int $id) {
    require_login();
    $e = Db::one('SELECT * FROM helper_event WHERE id=?', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
    $e['shifts'] = Db::all('SELECT * FROM helper_shift WHERE helper_event_id=? ORDER BY starts_at, id', [$id]);
    foreach ($e['shifts'] as &$s) {
        $s['people'] = Db::all('SELECT p.id, p.first_name, p.last_name FROM helper_assignment a JOIN person p ON p.id=a.person_id WHERE a.shift_id=? ORDER BY p.last_name', [$s['id']]);
    }
    $e['my_person_ids'] = my_persons();
    $e['can_manage'] = has_role('admin', 'office');
    return $e;
});

route('POST', 'helper-events', function () {
    require_role('office');
    $d = fields(['name', 'event_date', 'location', 'notes']);
    required($d, ['name', 'event_date']);
    return ['id' => insert('helper_event', $d)];
});
route('PUT', 'helper-events/{id}', function (int $id) { require_role('office'); update('helper_event', $id, fields(['name', 'event_date', 'location', 'notes'])); });
route('DELETE', 'helper-events/{id}', function (int $id) { require_role('office'); Db::exec('DELETE FROM helper_event WHERE id=?', [$id]); });

route('POST', 'helper-events/{id}/shifts', function (int $id) {
    require_role('office');
    $d = fields(['name', 'starts_at', 'ends_at', 'slots']);
    required($d, ['name']);
    $d['helper_event_id'] = $id;
    return ['id' => insert('helper_shift', $d)];
});
route('PUT', 'shifts/{id}', function (int $id) { require_role('office'); update('helper_shift', $id, fields(['name', 'starts_at', 'ends_at', 'slots'])); });
route('DELETE', 'shifts/{id}', function (int $id) { require_role('office'); Db::exec('DELETE FROM helper_shift WHERE id=?', [$id]); });

// Zuteilung: Büro beliebig, Mitglieder für sich/Kinder (solange Plätze frei)
route('POST', 'shifts/{id}/assign/{pid}', function (int $id, int $pid) {
    require_login();
    if (!has_role('admin', 'office') && !in_array($pid, my_persons(), true)) throw new HttpError('Keine Berechtigung', 403);
    Pdo_tx(function () use ($id, $pid) {
        $s = Db::one('SELECT slots FROM helper_shift WHERE id=? FOR UPDATE', [$id]) ?? throw new HttpError('Nicht gefunden', 404);
        if (Db::val('SELECT COUNT(*) FROM helper_assignment WHERE shift_id=?', [$id]) >= $s['slots']) throw new HttpError('Schicht ist voll', 422);
        Db::exec('INSERT IGNORE INTO helper_assignment (shift_id, person_id) VALUES (?,?)', [$id, $pid]);
    });
});
route('DELETE', 'shifts/{id}/assign/{pid}', function (int $id, int $pid) {
    require_login();
    if (!has_role('admin', 'office') && !in_array($pid, my_persons(), true)) throw new HttpError('Keine Berechtigung', 403);
    Db::exec('DELETE FROM helper_assignment WHERE shift_id=? AND person_id=?', [$id, $pid]);
});

function Pdo_tx(callable $fn): void
{
    $pdo = Db::pdo();
    $pdo->beginTransaction();
    try { $fn(); $pdo->commit(); } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}

// Anzahl Einsätze pro Mitglied
route('GET', 'helper-report', function () {
    require_role('office');
    return Db::all('SELECT p.id, p.first_name, p.last_name, COUNT(a.shift_id) AS count
                    FROM person p JOIN member_type mt ON mt.id=p.member_type_id AND mt.name="Aktiv"
                    LEFT JOIN helper_assignment a ON a.person_id=p.id
                    WHERE p.kind="natural" GROUP BY p.id ORDER BY count DESC, p.last_name');
});

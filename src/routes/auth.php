<?php
route('POST', 'login', function () {
    $b = body();
    $u = Db::one('SELECT id, password_hash FROM app_user WHERE email=? AND active=1', [trim($b['email'] ?? '')]);
    if (!$u || !password_verify($b['password'] ?? '', $u['password_hash'])) throw new HttpError('Login fehlgeschlagen', 401);
    session_start_safe();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return ['user' => me_payload((int) $u['id'])];
});

route('POST', 'logout', function () { session_start_safe(); session_destroy(); });

route('GET', 'me', fn() => ['user' => me() ? me_payload(me()['id']) : null]);

function me_payload(int $id): array
{
    $_SESSION['uid'] = $id;
    $u = Db::one('SELECT id, person_id, email FROM app_user WHERE id=?', [$id]);
    $u['roles'] = array_column(Db::all('SELECT role FROM user_role WHERE user_id=?', [$id]), 'role');
    return $u;
}

// ---- User administration (admin) ----
route('GET', 'users', function () {
    require_role('admin');
    $rows = Db::all('SELECT u.id, u.email, u.active, u.person_id, CONCAT_WS(" ", p.first_name, p.last_name) AS person
                     FROM app_user u LEFT JOIN person p ON p.id=u.person_id ORDER BY u.email');
    foreach ($rows as &$r) $r['roles'] = array_column(Db::all('SELECT role FROM user_role WHERE user_id=?', [$r['id']]), 'role');
    return $rows;
});

function save_user(?int $id): int
{
    $b = body();
    $d = fields(['email', 'person_id', 'active']);
    if (!empty($b['password'])) {
        if (strlen($b['password']) < 8) throw new HttpError('Passwort: mind. 8 Zeichen', 422);
        $d['password_hash'] = password_hash($b['password'], PASSWORD_DEFAULT);
    }
    if ($id === null) { required($d + ['p' => $b['password'] ?? ''], ['email', 'p']); $id = insert('app_user', $d); }
    else update('app_user', $id, $d);
    if (isset($b['roles'])) {
        Db::exec('DELETE FROM user_role WHERE user_id=?', [$id]);
        foreach (array_intersect($b['roles'], ROLES) as $r) Db::exec('INSERT INTO user_role (user_id, role) VALUES (?,?)', [$id, $r]);
    }
    return $id;
}
route('POST', 'users', function () { require_role('admin'); return ['id' => save_user(null)]; });
route('PUT', 'users/{id}', function (int $id) { require_role('admin'); save_user($id); });
route('DELETE', 'users/{id}', function (int $id) {
    require_role('admin');
    if ($id === me()['id']) throw new HttpError('Eigenen Benutzer nicht löschbar', 422);
    Db::exec('DELETE FROM app_user WHERE id=?', [$id]);
});

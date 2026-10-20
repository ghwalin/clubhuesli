<?php
require_once __DIR__ . '/Db.php';

const ROLES = ['admin', 'office', 'trainer', 'communication', 'member'];

$GLOBALS['routes'] = [];

function route(string $method, string $pattern, callable $fn): void
{
    $re = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
    $GLOBALS['routes'][] = [$method, $re, $fn];
}

class HttpError extends Exception
{
    public function __construct(string $msg, public int $status = 400) { parent::__construct($msg); }
}

function out($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function body(): array
{
    static $b = null;
    if ($b === null) {
        $b = json_decode(file_get_contents('php://input') ?: '[]', true);
        if (!is_array($b)) throw new HttpError('Invalid JSON');
    }
    return $b;
}

/** Pick known fields from input; '' becomes NULL. */
function fields(array $names, ?array $src = null): array
{
    $src ??= body();
    $r = [];
    foreach ($names as $n) {
        if (array_key_exists($n, $src)) $r[$n] = ($src[$n] === '' ? null : $src[$n]);
    }
    return $r;
}

function required(array $data, array $names): void
{
    foreach ($names as $n) if (!isset($data[$n]) || $data[$n] === '') throw new HttpError("Feld '$n' fehlt", 422);
}

function insert(string $table, array $data): int
{
    Db::exec("INSERT INTO `$table` (`" . implode('`,`', array_keys($data)) . '`) VALUES (' . Db::in($data) . ')', array_values($data));
    return Db::lastId();
}

function update(string $table, int $id, array $data): void
{
    if (!$data) return;
    $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    Db::exec("UPDATE `$table` SET $set WHERE id=?", [...array_values($data), $id]);
}

// ---------- Auth ----------

function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
        session_name('clubadmin');
        session_start();
    }
}

function me(): ?array
{
    static $u = false;
    if ($u === false) {
        session_start_safe();
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $row = Db::one('SELECT id, person_id, email FROM app_user WHERE id=? AND active=1', [$_SESSION['uid']]);
            if ($row) {
                $row['roles'] = array_column(Db::all('SELECT role FROM user_role WHERE user_id=?', [$row['id']]), 'role');
                $u = $row;
            }
        }
    }
    return $u;
}

function require_login(): array { return me() ?? throw new HttpError('Nicht angemeldet', 401); }

function has_role(string ...$roles): bool { return (bool) array_intersect(me()['roles'] ?? [], $roles); }

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!array_intersect($u['roles'], [...$roles, 'admin'])) throw new HttpError('Keine Berechtigung', 403);
    return $u;
}

// ---------- Domain helpers ----------

/** All group ids below (and including) $gid, cycle-safe. */
function group_tree(int $gid): array
{
    $seen = [$gid];
    $queue = [$gid];
    while ($queue) {
        $rows = Db::all('SELECT child_id FROM group_group WHERE parent_id IN (' . Db::in($queue) . ')', $queue);
        $queue = [];
        foreach ($rows as $r) {
            $c = (int) $r['child_id'];
            if (!in_array($c, $seen, true)) { $seen[] = $c; $queue[] = $c; }
        }
    }
    return $seen;
}

/** Persons currently in group (incl. subgroups). Returns person rows. */
function group_persons(int $gid, ?string $onDate = null): array
{
    $ids = group_tree($gid);
    $d = $onDate ?? date('Y-m-d');
    return Db::all(
        'SELECT DISTINCT p.id, p.first_name, p.last_name, p.org_name, p.email
         FROM group_member gm JOIN person p ON p.id=gm.person_id
         WHERE gm.group_id IN (' . Db::in($ids) . ') AND gm.start_date<=? AND (gm.end_date IS NULL OR gm.end_date>=?)
         ORDER BY p.last_name, p.first_name',
        [...$ids, $d, $d]
    );
}

/** Group ids where the current user is a (current) trainer. */
function trainer_groups(): array
{
    $u = me();
    if (!$u || !$u['person_id']) return [];
    return array_map('intval', array_column(Db::all(
        'SELECT DISTINCT gm.group_id FROM group_member gm JOIN role r ON r.id=gm.role_id
         WHERE gm.person_id=? AND r.is_trainer=1 AND gm.start_date<=CURDATE() AND (gm.end_date IS NULL OR gm.end_date>=CURDATE())',
        [$u['person_id']]
    ), 'group_id'));
}

/** Team group ids the current user belongs to as participant. */
function my_groups(): array
{
    $u = me();
    if (!$u || !$u['person_id']) return [];
    $direct = array_column(Db::all(
        'SELECT DISTINCT group_id FROM group_member WHERE person_id=? AND start_date<=CURDATE() AND (end_date IS NULL OR end_date>=CURDATE())',
        [$u['person_id']]
    ), 'group_id');
    // include parent groups: membership of a subgroup implies membership of the container
    $all = array_map('intval', $direct);
    $queue = $all;
    while ($queue) {
        $rows = Db::all('SELECT parent_id FROM group_group WHERE child_id IN (' . Db::in($queue) . ')', $queue);
        $queue = [];
        foreach ($rows as $r) if (!in_array((int) $r['parent_id'], $all, true)) { $all[] = $queue[] = (int) $r['parent_id']; }
    }
    return $all;
}

/** Persons the user may act for: self + own children. */
function my_persons(): array
{
    $u = me();
    if (!$u || !$u['person_id']) return [];
    $kids = array_column(Db::all('SELECT child_id FROM person_relation WHERE parent_id=?', [$u['person_id']]), 'child_id');
    return array_map('intval', [$u['person_id'], ...$kids]);
}

function can_manage_team(int $gid): bool
{
    return has_role('admin') || (has_role('trainer') && in_array($gid, trainer_groups(), true));
}

function dispatch(): void
{
    $path = trim($_GET['r'] ?? '', '/');
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'GET' && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'clubadmin') throw new HttpError('CSRF', 403);
    foreach ($GLOBALS['routes'] as [$m, $re, $fn]) {
        if ($m === $method && preg_match($re, $path, $mt)) {
            $args = array_filter($mt, 'is_string', ARRAY_FILTER_USE_KEY);
            out($fn(...array_map('intval', $args)) ?? ['ok' => true]);
        }
    }
    throw new HttpError('Not found', 404);
}

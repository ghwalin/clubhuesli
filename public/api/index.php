<?php
require __DIR__ . '/../../src/core.php';
foreach (glob(__DIR__ . '/../../src/routes/*.php') as $f) require $f;

try {
    dispatch();
} catch (HttpError $e) {
    out(['error' => $e->getMessage()], $e->status);
} catch (PDOException $e) {
    $dbg = Db::config()['debug'];
    $dup = ($e->errorInfo[1] ?? 0) === 1062;
    out(['error' => $dup ? 'Eintrag existiert bereits' : ($dbg ? $e->getMessage() : 'Datenbankfehler')], $dup ? 409 : 500);
} catch (Throwable $e) {
    out(['error' => Db::config()['debug'] ? $e->getMessage() : 'Serverfehler'], 500);
}

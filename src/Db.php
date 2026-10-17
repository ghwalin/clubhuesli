<?php
final class Db
{
    private static ?PDO $pdo = null;
    public static array $cfg = [];

    public static function config(): array
    {
        return self::$cfg ?: (self::$cfg = require __DIR__ . '/../config/config.php');
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            $c = self::config()['db'];
            self::$pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    public static function all(string $sql, array $p = []): array { $s = self::pdo()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { return self::all($sql, $p)[0] ?? null; }
    public static function val(string $sql, array $p = []) { $r = self::one($sql, $p); return $r ? reset($r) : null; }
    public static function exec(string $sql, array $p = []): int { $s = self::pdo()->prepare($sql); $s->execute($p); return $s->rowCount(); }
    public static function lastId(): int { return (int) self::pdo()->lastInsertId(); }

    /** Build "?,?,?" for IN lists. */
    public static function in(array $v): string { return implode(',', array_fill(0, count($v), '?')); }
}

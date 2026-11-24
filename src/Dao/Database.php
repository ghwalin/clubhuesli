<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Config;
use PDO;

/**
 * Holds the PDO connection used by the data access objects.
 */
final class Database
{
    /** @var PDO|null The shared connection. */
    private static ?PDO $connection = null;

    /**
     * Returns the shared connection and opens it on first use.
     *
     * @return PDO The connection.
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            /** @var array{host: string, port: int|string, name: string, user: string, pass: string} $db */
            $db  = Config::get('db', []);
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $db['host'] ?? '127.0.0.1',
                (string) ($db['port'] ?? 3306),
                $db['name'] ?? 'clubhuesli'
            );

            self::$connection = new PDO($dsn, $db['user'] ?? '', $db['pass'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$connection;
    }

    /**
     * Injects a connection, e.g. an in-memory database used by tests.
     *
     * @param PDO|null $connection The connection, or null to reconnect on next use.
     */
    public static function setConnection(?PDO $connection): void
    {
        self::$connection = $connection;
    }
}

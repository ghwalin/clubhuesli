<?php

declare(strict_types=1);

namespace Clubhuesli;

/**
 * Application configuration.
 *
 * The built-in defaults are merged with `config/config.php` and then with
 * `config/config.local.php`; both files are optional and later values win.
 */
final class Config
{
    /** @var array<string, mixed>|null Cached configuration values. */
    private static ?array $values = null;

    /**
     * Returns the whole configuration.
     *
     * @return array<string, mixed> The merged configuration.
     */
    public static function all(): array
    {
        if (self::$values === null) {
            $values = [
                'db' => [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'name' => 'clubhuesli',
                    'user' => 'clubhuesli',
                    'pass' => '',
                ],
                'debug'     => false,
                'mail_from' => 'club@example.org',
            ];

            foreach (['config.php', 'config.local.php'] as $file) {
                $path = dirname(__DIR__) . '/config/' . $file;
                if (is_file($path)) {
                    /** @var array<string, mixed> $overrides */
                    $overrides = require $path;
                    $values    = self::merge($values, $overrides);
                }
            }

            self::$values = $values;
        }

        return self::$values;
    }

    /**
     * Returns a single configuration value.
     *
     * @param string $key     Key, dot-separated for nested values (e.g. `db.host`).
     * @param mixed  $default Value returned when the key is unknown.
     *
     * @return mixed The configuration value or the given default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all();
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Replaces the configuration at runtime (used by tests).
     *
     * @param array<string, mixed>|null $values New values, or null to reload from disk.
     */
    public static function set(?array $values): void
    {
        self::$values = $values;
    }

    /**
     * Merges two configuration arrays recursively.
     *
     * @param array<string, mixed> $base      Base values.
     * @param array<string, mixed> $overrides Overriding values.
     *
     * @return array<string, mixed> The merged values.
     */
    private static function merge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }
}

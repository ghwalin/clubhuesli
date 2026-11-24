<?php
/**
 * PSR-4 autoloader for the `Clubhuesli\` namespace.
 *
 * Used when the Composer autoloader is not available, so the application also
 * runs on a plain PHP installation without `composer install`.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix  = 'Clubhuesli\\';
    $baseDir = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

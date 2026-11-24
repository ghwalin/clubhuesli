<?php
/**
 * Router script for the built-in PHP development server:
 *
 *   php -S localhost:8000 -t public bin/dev_router.php
 *
 * It serves existing files from `public/`, routes `/api/...` to the API front
 * controller and everything else to the single-page application.
 */

declare(strict_types=1);

$publicDir = dirname(__DIR__) . '/public';
$path      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/api')) {
    $_SERVER['PATH_INFO'] = substr($path, 4) ?: '/';
    require $publicDir . '/api/index.php';

    return true;
}

$file = $publicDir . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the development server serve the file
}

require $publicDir . '/index.html';

return true;

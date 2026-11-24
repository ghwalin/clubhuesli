<?php
/**
 * Loads the class autoloader: the Composer one when it exists, otherwise the
 * built-in PSR-4 autoloader.
 */

declare(strict_types=1);

$composerAutoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composerAutoloader)) {
    require $composerAutoloader;
} else {
    require __DIR__ . '/autoload.php';
}

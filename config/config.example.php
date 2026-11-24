<?php
/**
 * Configuration template. Copy to config/config.php and adjust, or put the
 * secrets into config/config.local.php (both are ignored by git).
 */

declare(strict_types=1);

return [
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

<?php
// Copy values to config/config.local.php to override (not versioned).
$cfg = [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'clubhuesli',
        'user' => 'clubhuesli',
        'pass' => 'change-me',
    ],
    'mail_from' => 'noreply@example.org',
    'club_name' => 'Clubhüsli',
    'debug'     => false,
];
if (is_file(__DIR__ . '/config.local.php')) {
    $cfg = array_replace_recursive($cfg, require __DIR__ . '/config.local.php');
}
return $cfg;

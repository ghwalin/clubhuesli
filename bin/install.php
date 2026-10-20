<?php
// Usage: php bin/install.php admin@example.org 'Password'
// Loads schema and creates the first admin user.
require __DIR__ . '/../src/Db.php';
[$_, $email, $pass] = $argv + [null, null, null];
if (!$email || !$pass) { fwrite(STDERR, "Usage: php bin/install.php EMAIL PASSWORD\n"); exit(1); }
$pdo = Db::pdo();
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
if (!Db::val('SELECT COUNT(*) FROM app_user')) {
    Db::exec('INSERT INTO app_user (email, password_hash) VALUES (?,?)', [$email, password_hash($pass, PASSWORD_DEFAULT)]);
    Db::exec('INSERT INTO user_role (user_id, role) VALUES (?, "admin")', [Db::lastId()]);
    echo "Admin created: $email\n";
}
echo "Done.\n";

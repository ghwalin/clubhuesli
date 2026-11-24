<?php
/**
 * Loads the database schema and creates the first account.
 *
 * Usage: php bin/install.php <e-mail> <password> [admin|office]
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Clubhuesli\Dao\Database;
use Clubhuesli\Dao\UserDao;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script runs on the command line only.\n";
    exit(1);
}

$email    = $argv[1] ?? null;
$password = $argv[2] ?? null;
$role     = $argv[3] ?? Role::ADMIN->value;

if ($email === null || $password === null) {
    echo "Usage: php bin/install.php <e-mail> <password> [admin|office]\n";
    exit(1);
}

try {
    $connection = Database::connection();

    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('database/schema.sql konnte nicht gelesen werden');
    }
    $connection->exec($schema);
    echo "Schema geladen.\n";

    $userDao = new UserDao($connection);
    if ($userDao->findByEmail($email) !== null) {
        echo "Konto {$email} existiert bereits – unverändert gelassen.\n";
        exit(0);
    }

    $user = new User();
    $user->setEmail($email);
    $user->setPassword($password);
    $user->setRole($role);
    $userDao->save($user);

    echo "Konto {$email} mit der Rolle {$role} angelegt.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Installation fehlgeschlagen: ' . $exception->getMessage() . "\n");
    exit(1);
}

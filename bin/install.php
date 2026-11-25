<?php
/**
 * Creates the first account.
 *
 * The schema must already exist: the application's database user has no
 * CREATE TABLE privilege, so an administrator loads database/schema.sql
 * directly on the database server (see README, installation).
 *
 * Usage: php bin/install.php <e-mail> <password> [admin|office]
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Clubhuesli\Config;
use Clubhuesli\Dao\Database;
use Clubhuesli\Dao\UserDao;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\User;

/** @var list<string> The tables of database/schema.sql; keep in sync with that file. */
const REQUIRED_TABLES = ['person', 'natural_person', 'team_member', 'legal_person', 'guardianship', 'app_user'];

/**
 * Makes sure the schema is loaded before the script touches a table.
 *
 * The application's database user may not create tables, so a missing schema
 * is a setup step the administrator still has to do, not an error this script
 * can fix.
 *
 * @param PDO $connection The connection to check.
 *
 * @throws RuntimeException When tables of database/schema.sql are missing.
 */
function assertSchemaLoaded(PDO $connection): void
{
    $statement = $connection->query('SHOW TABLES');
    $existing  = $statement === false ? [] : $statement->fetchAll(PDO::FETCH_COLUMN);

    $missing = array_values(array_diff(REQUIRED_TABLES, $existing));
    if ($missing === []) {
        return;
    }

    /** @var array{name?: string} $db */
    $db       = Config::get('db', []);
    $database = $db['name'] ?? 'clubhuesli';

    throw new RuntimeException(
        'das Schema ist nicht geladen, es fehlen die Tabellen: ' . implode(', ', $missing) . '.'
        . PHP_EOL . 'Bitte zuerst Schritt 3 der Installation ausführen (als DB-Administrator):'
        . PHP_EOL . '  mysql -u root -p ' . $database . ' < database/schema.sql'
    );
}

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
    assertSchemaLoaded($connection);

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

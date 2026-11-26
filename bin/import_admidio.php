<?php
/**
 * Imports the current members from a backup of the old Admidio database.
 *
 * Reads the SQL dump directly (no second database needed), maps the data to
 * Clubadmin (see src/Import/AdmidioMapper.php for the rules) and writes it
 * with the models and data access objects of the application. The import can be
 * repeated: what exists already is not created twice.
 *
 * Usage:
 *   php bin/import_admidio.php <dump.sql> [--dry-run] [--date=YYYY-MM-DD] [--org=1] [--plan-only]
 *
 *   --dry-run     does everything in a transaction and rolls it back at the end
 *   --plan-only   only prints what would be imported; needs no database
 *   --date        reference day for «current» (default: today)
 *   --org         id of the Admidio organisation to import (default: 1)
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Clubhuesli\Dao\Database;
use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Import\AdmidioImporter;
use Clubhuesli\Import\AdmidioMapper;
use Clubhuesli\Import\SqlDumpReader;

$arguments = array_slice($argv, 1);
$options   = ['dry-run' => false, 'plan-only' => false, 'date' => 'today', 'org' => '1'];
$file      = null;
foreach ($arguments as $argument) {
    if ($argument === '--dry-run' || $argument === '--plan-only') {
        $options[substr($argument, 2)] = true;
    } elseif (preg_match('/^--(date|org)=(.+)$/', $argument, $matches) === 1) {
        $options[$matches[1]] = $matches[2];
    } elseif (!str_starts_with($argument, '--') && $file === null) {
        $file = $argument;
    } else {
        fwrite(STDERR, "Unbekannte Option: {$argument}\n");
        exit(2);
    }
}
if ($file === null) {
    fwrite(STDERR, "Usage: php bin/import_admidio.php <dump.sql> [--dry-run] [--plan-only] [--date=YYYY-MM-DD] [--org=1]\n");
    exit(2);
}

try {
    $today  = new DateTimeImmutable((string) $options['date']);
    $tables = (new SqlDumpReader($file))->collect([
        'adm_users', 'adm_user_data', 'adm_user_fields', 'adm_members', 'adm_roles', 'adm_categories',
    ]);
    $plan = (new AdmidioMapper($tables, $today->setTime(0, 0), (int) $options['org']))->plan();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Fehler beim Lesen: ' . $exception->getMessage() . "\n");
    exit(1);
}

$summary = static function (array $plan): void {
    $kinds = [];
    $members = 0;
    foreach ($plan['persons'] as $person) {
        $kind         = $person['payload']['personType']
            . (($person['payload']['teamMember'] ?? false) ? ' (Teammitglied)' : '');
        $kinds[$kind] = ($kinds[$kind] ?? 0) + 1;
        $members     += count($person['memberships']);
    }
    ksort($kinds);
    echo "Saison:       {$plan['season']['name']} ({$plan['season']['start']} bis {$plan['season']['end']})\n";
    echo 'Gruppen:      ' . count($plan['groups']) . ' (Standardgruppen von Chilis, siehe chilis/groups.md)' . "\n";
    echo 'Personen:     ' . count($plan['persons']) . "\n";
    foreach ($kinds as $kind => $count) {
        echo "  {$count} × {$kind}\n";
    }
    echo "Zuordnungen:  {$members}\n";
    echo 'Übersprungen: ' . count($plan['skipped']) . "\n";
    foreach ($plan['unmapped'] as $role => $count) {
        echo "  Admidio-Rolle «{$role}» hat keine Gruppe: {$count} aktive Mitgliedschaft(en) nicht importiert\n";
    }
};

if ($options['plan-only']) {
    $summary($plan);
    foreach ($plan['skipped'] as $skipped) {
        echo "  übersprungen: {$skipped['name']} (Id {$skipped['id']}): {$skipped['reason']}\n";
    }
    exit(0);
}

$connection = Database::connection();
$connection->beginTransaction();
try {
    $result = (new AdmidioImporter(
        new PersonDao(),
        new SeasonDao(),
        new GroupDao(),
        new MembershipDao()
    ))->import($plan);

    if ($options['dry-run']) {
        $connection->rollBack();
    } else {
        $connection->commit();
    }
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'Import abgebrochen, nichts wurde gespeichert: ' . $exception->getMessage() . "\n");
    exit(1);
}

$summary($plan);
echo "\nErgebnis" . ($options['dry-run'] ? ' (Probelauf, nichts gespeichert)' : '') . ":\n";
foreach ($result['counts'] as $name => $count) {
    echo "  {$name}: {$count}\n";
}
if ($result['failures'] !== []) {
    echo "\nNicht importiert / übersprungen:\n";
    foreach ($result['failures'] as $line) {
        echo "  - {$line}\n";
    }
}
if ($result['warnings'] !== []) {
    echo "\nHinweise (" . count($result['warnings']) . "):\n";
    foreach ($result['warnings'] as $line) {
        echo "  - {$line}\n";
    }
}
exit($result['failures'] === [] ? 0 : 3);

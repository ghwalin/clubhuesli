<?php

declare(strict_types=1);

namespace Clubhuesli\Tests;

use Clubhuesli\Import\AdmidioImporter;
use Clubhuesli\Import\AdmidioMapper;
use Clubhuesli\Import\SqlDumpReader;
use Clubhuesli\Tests\Fake\InMemoryGroupDao;
use Clubhuesli\Tests\Fake\InMemoryMembershipDao;
use Clubhuesli\Tests\Fake\InMemoryPersonDao;
use Clubhuesli\Tests\Fake\InMemorySeasonDao;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests the import of an Admidio dump: reading the SQL, mapping and writing.
 */
final class AdmidioImportTest extends TestCase
{
    /** @var string Reference day of the tests. */
    private const TODAY = '2026-11-26';

    /**
     * The reader handles NULL, numbers, escapes and separators inside strings.
     */
    public function testReaderParsesValues(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dump');
        file_put_contents($path, implode("\n", [
            '-- comment',
            "INSERT INTO `adm_users` (`usr_id`, `usr_text`, `usr_photo`, `usr_valid`) VALUES (1, 'O\\'Brien, (Hans) \\\\ \"x\" ''y''', NULL, 1);",
            "INSERT INTO `adm_other` (`a`) VALUES (5);",
            "INSERT INTO `adm_users` (`usr_id`, `usr_text`, `usr_photo`, `usr_valid`) VALUES (2, 'Zeile\\nzwei', NULL, 0);",
        ]) . "\n");

        $rows = (new SqlDumpReader($path))->collect(['adm_users'])['adm_users'];
        unlink($path);

        self::assertCount(2, $rows);
        self::assertSame("O'Brien, (Hans) \\ \"x\" 'y'", $rows[0]['usr_text']);
        self::assertNull($rows[0]['usr_photo']);
        self::assertSame(1, $rows[0]['usr_valid']);
        self::assertSame("Zeile\nzwei", $rows[1]['usr_text']);
    }

    /**
     * The plan contains the current season and only active memberships; system
     * roles, archived persons and ended memberships are left out.
     */
    public function testMapperPlansCurrentState(): void
    {
        $plan = $this->mapper()->plan();

        self::assertSame('2026/27', $plan['season']['name']);
        self::assertCount(24, $plan['groups'], 'the standard groups of Chilis are always created');
        self::assertSame('TEAM', $plan['groups']['NLB']);
        self::assertSame('FUNCTIONARY', $plan['groups']['Vorstand']);
        self::assertContains(['Aktivmitglied', 'Vorstand'], $plan['links']);
        self::assertSame([], $plan['unmapped']);
        self::assertSame([10, 11, 12, 14, 15], array_keys($plan['persons']));
        self::assertSame(['archiviert am 2022-04-27'], array_column($plan['skipped'], 'reason'));
    }

    /**
     * Team leaders become coaches, players get the shirt number; members of a team
     * or of «Aktivmitglied» with a birth date become team members.
     */
    public function testMapperMapsTeamMembers(): void
    {
        $persons = $this->mapper()->plan()['persons'];

        $player = $persons[10];
        self::assertTrue($player['payload']['teamMember']);
        self::assertSame('PLAYER', $player['memberships'][0]['role']);
        self::assertSame(7, $player['memberships'][0]['shirtNumber']);
        self::assertSame('Dorfstrasse', $player['payload']['street']);
        self::assertSame('12a', $player['payload']['houseNumber']);
        self::assertSame('CH', $player['payload']['country']);

        $coach = $persons[11];
        self::assertSame('COACH', $coach['memberships'][0]['role']);
        self::assertNull($coach['memberships'][0]['shirtNumber']);
        // a team leader also belongs to the group «Coach», a player to «Spielerin»
        self::assertSame(['NLB', 'Coach', 'Vorstand'], array_column($coach['memberships'], 'group'));
        self::assertSame(['NLB', 'Spielerin', 'Aktivmitglied'], array_column($player['memberships'], 'group'));
        self::assertSame('Kassier', $coach['memberships'][2]['function']);
    }

    /**
     * A sponsor with a company name becomes a legal person; the end of a
     * membership inside the season becomes the exit date.
     */
    public function testMapperMapsLegalPersonAndExitDate(): void
    {
        $persons = $this->mapper()->plan()['persons'];

        self::assertSame('LEGAL', $persons[12]['payload']['personType']);
        self::assertSame('Jensen AG', $persons[12]['payload']['name']);
        self::assertSame('2027-01-31', $persons[12]['memberships'][0]['exitDate']);
    }

    /**
     * Members of «Gemeindepräsidenten», «Sponsor» and «Persönliche Sponsoren» are
     * always legal persons, even without a company name and even when they are
     * also active members; team memberships then drop out.
     */
    public function testSponsorRolesAreAlwaysLegalPersons(): void
    {
        $persons = $this->mapper(true)->plan()['persons'];

        $municipality = $persons[17];
        self::assertSame('LEGAL', $municipality['payload']['personType']);
        self::assertSame('Gerda Gemeinde', $municipality['payload']['name']);
        self::assertSame(['Gemeinden', 'Aktivmitglied'], array_column($municipality['memberships'], 'group'));
        self::assertNotContains('NLB', array_column($municipality['memberships'], 'group'));

        $personal = $persons[18];
        self::assertSame('LEGAL', $personal['payload']['personType']);
        self::assertSame('Paul Privat', $personal['payload']['name']);
        self::assertSame('Paul', $personal['payload']['contactFirstName']);

        // the sponsor with a company keeps the company name
        self::assertSame('Jensen AG', $persons[12]['payload']['name']);
    }

    /**
     * A minor gets a guardian when name and e-mail address of a parent are
     * known; the parents' e-mail address is not needed twice.
     */
    public function testMapperCreatesGuardianForMinor(): void
    {
        $minor = $this->mapper()->plan()['persons'][14];

        self::assertSame('Peter', $minor['guardian']['firstName']);
        self::assertSame('Muster', $minor['guardian']['lastName']);
        self::assertSame('NATURAL', $minor['guardian']['personType']);
    }

    /**
     * Importing writes seasons, groups, persons, guardians and memberships; the
     * result is the plan, and a second run creates nothing twice.
     */
    public function testImportAndRepeat(): void
    {
        $person      = new InMemoryPersonDao();
        $season      = new InMemorySeasonDao();
        $group       = new InMemoryGroupDao();
        $membership  = new InMemoryMembershipDao();
        $importer    = new AdmidioImporter($person, $season, $group, $membership);
        $plan        = $this->mapper()->plan();

        $first = $importer->import($plan);

        self::assertSame(1, $first['counts']['seasons']);
        self::assertSame(24, $first['counts']['groups']);
        self::assertSame(12, $first['counts']['links']);
        self::assertSame(1, $first['counts']['guardians']);
        self::assertSame(4, $first['counts']['persons'], 'a minor without e-mail is valid with its guardian');
        self::assertSame(1, $first['counts']['failed'], 'the person without e-mail address is reported');
        self::assertSame(9, $first['counts']['memberships']);
        self::assertCount(1, $season->findAll());
        self::assertNotNull($group->findByName('NLB'));

        $second = $importer->import($plan);

        self::assertSame(0, $second['counts']['groups']);
        self::assertSame(0, $second['counts']['links']);
        self::assertSame(0, $second['counts']['persons']);
        self::assertSame(0, $second['counts']['memberships']);
        self::assertSame($first['counts']['memberships'], $second['counts']['membershipsExisting']);
        self::assertSame(0, $second['counts']['guardians']);
    }

    /**
     * An invalid AHV number is dropped and kept in the note; a person without
     * e-mail address is reported and not imported.
     */
    public function testImportDropsInvalidOptionalValuesAndReportsMissingEmail(): void
    {
        $person   = new InMemoryPersonDao();
        $importer = new AdmidioImporter($person, new InMemorySeasonDao(), new InMemoryGroupDao(), new InMemoryMembershipDao());

        $result = $importer->import($this->mapper()->plan());

        self::assertCount(1, array_filter(
            $result['failures'],
            static fn (string $line): bool => str_contains($line, 'E-Mail-Adresse')
        ));
        self::assertNotEmpty(array_filter(
            $result['warnings'],
            static fn (string $line): bool => str_contains($line, 'AHV-Nummer')
        ));
        $stored = array_values(array_filter(
            $person->findAll('Muster'),
            static fn ($p): bool => $p->getDisplayName() === 'Anna Muster'
        ));
        self::assertCount(1, $stored);
        self::assertStringContainsString('756.1234.5678.90', (string) $stored[0]->getNote());
    }

    /**
     * Builds a mapper for a small synthetic dump.
     *
     * @param bool $withSponsors Whether to add a municipality president and a personal sponsor.
     *
     * @return AdmidioMapper The mapper.
     */
    private function mapper(bool $withSponsors = false): AdmidioMapper
    {
        $fields = [
            1 => ['LAST_NAME', 1, 'Nachname'], 2 => ['FIRST_NAME', 1, 'Vorname'], 3 => ['STREET', 1, 'Strasse'],
            4 => ['POSTCODE', 1, 'PLZ'], 5 => ['CITY', 1, 'Ort'], 6 => ['COUNTRY', 1, 'Land'],
            8 => ['MOBILE', 1, 'Mobil'], 10 => ['BIRTHDAY', 1, 'Geburtstag'], 12 => ['EMAIL', 1, 'E-Mail'],
            19 => ['EMAIL_ELTERN', 1, 'E-Mail Eltern'], 97 => ['NAME_ELTERN', 1, 'Name Eltern'],
            100 => ['AHVNR', 1, 'AHV-Nr'], 92 => ['TRIKOTNR', 1, 'Trikot'], 103 => ['FIRMA', 1, 'Firma'],
            66 => ['ARCHIVIERT', 20, 'archiviert'], 33 => ['KASSIER', 18, 'Kassier'], 38 => ['KOMMUNIKATION', 18, 'Kommunikation'],
            55 => ['EINVERSTNDNIS_FOTO_UNTER_18', 1, 'Foto'],
        ];
        $userFields = [];
        foreach ($fields as $id => [$intern, $category, $name]) {
            $userFields[] = ['usf_id' => $id, 'usf_cat_id' => $category, 'usf_name_intern' => $intern, 'usf_name' => $name];
        }

        $data = [];
        $set  = static function (int $user, array $values) use (&$data, $fields): void {
            foreach ($values as $intern => $value) {
                foreach ($fields as $id => [$name]) {
                    if ($name === $intern) {
                        $data[] = ['usd_usr_id' => $user, 'usd_usf_id' => $id, 'usd_value' => $value];
                    }
                }
            }
        };
        $minorBirth = (new DateTimeImmutable(self::TODAY))->modify('-14 years')->format('Y-m-d');
        $set(10, ['FIRST_NAME' => 'Anna', 'LAST_NAME' => 'Muster', 'BIRTHDAY' => '2000-03-01', 'EMAIL' => 'anna@example.ch',
            'STREET' => 'Dorfstrasse 12a', 'POSTCODE' => '8000', 'CITY' => 'Zürich', 'COUNTRY' => 'CHE',
            'AHVNR' => '756.1234.5678.90', 'TRIKOTNR' => '7']);
        $set(11, ['FIRST_NAME' => 'Lea', 'LAST_NAME' => 'Meier', 'BIRTHDAY' => '1990-05-05', 'EMAIL' => 'lea@example.ch', 'KASSIER' => '1']);
        $set(12, ['FIRST_NAME' => 'Beat', 'LAST_NAME' => 'Keller', 'EMAIL' => 'info@jensen.example', 'FIRMA' => 'Jensen AG']);
        $set(13, ['FIRST_NAME' => 'Nina', 'LAST_NAME' => 'Archiv', 'EMAIL' => 'nina@example.ch', 'ARCHIVIERT' => '2022-04-27']);
        $set(14, ['FIRST_NAME' => 'Mia', 'LAST_NAME' => 'Muster', 'BIRTHDAY' => $minorBirth, 'NAME_ELTERN' => 'Peter Muster',
            'EMAIL_ELTERN' => 'peter@example.ch', 'EINVERSTNDNIS_FOTO_UNTER_18' => '1']);
        $set(15, ['FIRST_NAME' => 'Ohne', 'LAST_NAME' => 'Mail']);

        $users = [];
        foreach ([10, 11, 12, 13, 14, 15, 16] as $id) {
            $users[] = ['usr_id' => $id, 'usr_valid' => 1];
        }

        $members = [
            ['mem_rol_id' => 9, 'mem_usr_id' => 10, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 2, 'mem_usr_id' => 10, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 9, 'mem_usr_id' => 11, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 1],
            ['mem_rol_id' => 3, 'mem_usr_id' => 11, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 30, 'mem_usr_id' => 12, 'mem_begin' => '2020-01-01', 'mem_end' => '2027-01-31', 'mem_leader' => 0],
            ['mem_rol_id' => 30, 'mem_usr_id' => 13, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 9, 'mem_usr_id' => 14, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 2, 'mem_usr_id' => 15, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 1, 'mem_usr_id' => 16, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            ['mem_rol_id' => 9, 'mem_usr_id' => 16, 'mem_begin' => '2019-01-01', 'mem_end' => '2020-06-30', 'mem_leader' => 0],
        ];

        $cat = static fn (int $id, int $org, string $type, string $intern): array =>
            ['cat_id' => $id, 'cat_org_id' => $org, 'cat_type' => $type, 'cat_name_intern' => $intern];
        $role = static fn (int $id, int $cat, string $name): array =>
            ['rol_id' => $id, 'rol_cat_id' => $cat, 'rol_name' => $name, 'rol_valid' => 1];

        if ($withSponsors) {
            // 17: municipality president who is also an active team player; 18: personal sponsor without company.
            $set(17, ['FIRST_NAME' => 'Gerda', 'LAST_NAME' => 'Gemeinde', 'BIRTHDAY' => '1970-01-01', 'EMAIL' => 'gerda@example.ch']);
            $set(18, ['FIRST_NAME' => 'Paul', 'LAST_NAME' => 'Privat', 'EMAIL' => 'paul@example.ch']);
            $users[] = ['usr_id' => 17, 'usr_valid' => 1];
            $users[] = ['usr_id' => 18, 'usr_valid' => 1];
            array_push(
                $members,
                ['mem_rol_id' => 32, 'mem_usr_id' => 17, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
                ['mem_rol_id' => 2, 'mem_usr_id' => 17, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
                ['mem_rol_id' => 9, 'mem_usr_id' => 17, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
                ['mem_rol_id' => 33, 'mem_usr_id' => 18, 'mem_begin' => '2020-01-01', 'mem_end' => '9999-12-31', 'mem_leader' => 0],
            );
        }

        return new AdmidioMapper([
            'adm_users'       => $users,
            'adm_user_data'   => $data,
            'adm_user_fields' => $userFields,
            'adm_members'     => $members,
            'adm_categories'  => [
                $cat(4, 1, 'ROL', 'COMMON'), $cat(5, 1, 'ROL', 'GROUPS'), $cat(7, 1, 'ROL', 'TEAMS'),
                $cat(25, 1, 'ROL', 'MITGLIEDER'), $cat(18, 0, 'USF', 'VORSTAND'), $cat(1, 0, 'USF', 'BASIC_DATA'),
                $cat(20, 0, 'USF', 'ARCHIV'),
            ],
            'adm_roles'       => [
                $role(1, 4, 'Administrator'), $role(9, 7, 'NLB'), $role(2, 25, 'Aktivmitglied'),
                $role(3, 25, 'Vorstand'), $role(30, 5, 'Sponsor'), $role(32, 5, 'Gemeindepräsidenten'),
                $role(33, 5, 'Persönliche Sponsoren'),
            ],
        ], new DateTimeImmutable(self::TODAY));
    }
}

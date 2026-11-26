<?php

declare(strict_types=1);

namespace Clubhuesli\Import;

use DateTimeImmutable;

/**
 * Turns the tables of an Admidio dump into an import plan for Clubadmin.
 *
 * The mapper only does the thinking and touches no database: it decides which
 * persons are imported, of which kind, which groups and memberships they get
 * and which data had to be dropped. {@see AdmidioImporter} writes the plan.
 *
 * Rules (see `docs` in the README of the import):
 *  - Only memberships that are active on the reference day (end date not
 *    reached) or start within the current season are imported, so the result
 *    is the current state. Only valid accounts that are not archived count.
 *  - Roles of the organisation come over as groups. System roles (category
 *    `COMMON`, e.g. Administrator) are not groups. Roles in the category
 *    `TEAMS` become groups of type team; a leader becomes coach, everybody
 *    else a player.
 *  - Persons in a team role or in the role «Aktivmitglied» get the member type
 *    active (and become team members when they have a birth date). Others
 *    become honorary members or passive members. Persons of a sponsor role
 *    with a company name become legal persons.
 */
final class AdmidioMapper
{
    /** Role category names (intern) that are no groups. */
    private const EXCLUDED_CATEGORIES = ['COMMON', 'EVENTS'];

    /** Category of the team roles. */
    private const TEAM_CATEGORY = 'TEAMS';

    /** Category of the sponsor roles. */
    private const SPONSOR_CATEGORY = 'GROUPS';

    /** Name of the role that marks active members. */
    private const ACTIVE_ROLE = 'Aktivmitglied';

    /** Roles outside of the teams that are groups of officials. */
    private const FUNCTIONARY_ROLES = ['Vorstand', 'Funktionäre', 'Spielsekretär:innen'];

    /** Member type of legal persons by sponsor role. */
    private const LEGAL_MEMBER_TYPES = [
        'Gönner'              => 'PATRON',
        'Sponsor'             => 'SPONSOR',
        'Persönliche Sponsoren' => 'SPONSOR',
        'Gemeindepräsidenten' => 'MUNICIPALITY',
    ];

    /** Country codes of Admidio (ISO 3166-1 alpha-3) to the codes of Clubadmin. */
    private const COUNTRIES = [
        'CHE' => 'CH', 'DEU' => 'DE', 'AUT' => 'AT', 'FRA' => 'FR', 'ITA' => 'IT', 'LIE' => 'LI',
        'GBR' => 'GB', 'USA' => 'US', 'ESP' => 'ES', 'NLD' => 'NL', 'BEL' => 'BE', 'PRT' => 'PT',
    ];

    /** @var array<int, array<string, mixed>> Roles that are imported as groups, keyed by role id. */
    private array $groups = [];

    /** @var array<int, array<string, string>> Field values per user, keyed by user id and internal field name. */
    private array $userData = [];

    /** @var array<int, string> Internal names of the user fields, keyed by field id. */
    private array $fieldNames = [];

    /** @var array<string, int> Category id of the user fields by internal field name, for functions. */
    private array $fieldCategories = [];

    /** @var array<int, string> Internal category names of the user fields, keyed by category id. */
    private array $fieldCategoryNames = [];

    /**
     * @param array<string, array<int, array<string, mixed>>> $tables       Rows of the Admidio tables.
     * @param DateTimeImmutable                               $today        Reference day.
     * @param int                                             $organisation Id of the Admidio organisation to import.
     */
    public function __construct(
        private readonly array $tables,
        private readonly DateTimeImmutable $today,
        private readonly int $organisation = 1,
    ) {
    }

    /**
     * Builds the import plan.
     *
     * @return array{
     *     season: array{name: string, start: string, end: string},
     *     groups: array<string, string>,
     *     persons: array<int, array<string, mixed>>,
     *     skipped: array<int, array{id: int, name: string, reason: string}>
     * } The plan.
     */
    public function plan(): array
    {
        $season = $this->season();
        $this->indexGroups();
        $this->indexUserData();

        $memberships = [];
        foreach ($this->tables['adm_members'] ?? [] as $member) {
            $roleId = (int) $member['mem_rol_id'];
            if (!isset($this->groups[$roleId])) {
                continue;
            }
            $begin = (string) $member['mem_begin'];
            $end   = (string) $member['mem_end'];
            if ($end < $this->today->format('Y-m-d') || $begin > $season['end']) {
                continue;
            }
            $memberships[(int) $member['mem_usr_id']][] = [
                'role'   => $this->groups[$roleId],
                'leader' => (int) $member['mem_leader'] === 1,
                'end'    => $end,
            ];
        }

        $users   = [];
        foreach ($this->tables['adm_users'] ?? [] as $user) {
            $users[(int) $user['usr_id']] = $user;
        }

        $persons = [];
        $skipped = [];
        $groups  = [];
        foreach ($memberships as $userId => $entries) {
            $data = $this->userData[$userId] ?? [];
            $name = trim(($data['FIRST_NAME'] ?? '') . ' ' . ($data['LAST_NAME'] ?? ''));

            if ((int) ($users[$userId]['usr_valid'] ?? 0) !== 1) {
                $skipped[] = ['id' => $userId, 'name' => $name, 'reason' => 'Konto ist nicht gültig'];
                continue;
            }
            $archived = $data['ARCHIVIERT'] ?? null;
            if ($archived !== null && $archived <= $this->today->format('Y-m-d')) {
                $skipped[] = ['id' => $userId, 'name' => $name, 'reason' => 'archiviert am ' . $archived];
                continue;
            }
            if (($data['FIRST_NAME'] ?? '') === '' || ($data['LAST_NAME'] ?? '') === '') {
                $skipped[] = ['id' => $userId, 'name' => $name, 'reason' => 'Vor- oder Nachname fehlt'];
                continue;
            }

            $person = $this->mapPerson($userId, $data, $entries, $season);
            foreach ($person['memberships'] as $membership) {
                $groups[$membership['group']] = $membership['groupType'];
            }
            $persons[$userId] = $person;
        }

        ksort($groups);

        return ['season' => $season, 'groups' => $groups, 'persons' => $persons, 'skipped' => $skipped];
    }

    /**
     * Returns the season that contains the reference day (1 July to 30 June).
     *
     * @return array{name: string, start: string, end: string} The season.
     */
    public function season(): array
    {
        $year = (int) $this->today->format('Y') - ((int) $this->today->format('n') < 7 ? 1 : 0);

        return [
            'name'  => sprintf('%d/%02d', $year, ($year + 1) % 100),
            'start' => sprintf('%d-07-01', $year),
            'end'   => sprintf('%d-06-30', $year + 1),
        ];
    }

    /**
     * Collects the roles that become groups.
     */
    private function indexGroups(): void
    {
        $categories = [];
        foreach ($this->tables['adm_categories'] ?? [] as $category) {
            if ($category['cat_type'] === 'ROL' && (int) $category['cat_org_id'] === $this->organisation
                && !in_array($category['cat_name_intern'], self::EXCLUDED_CATEGORIES, true)) {
                $categories[(int) $category['cat_id']] = (string) $category['cat_name_intern'];
            }
        }

        foreach ($this->tables['adm_roles'] ?? [] as $role) {
            $category = $categories[(int) $role['rol_cat_id']] ?? null;
            if ($category === null || (int) $role['rol_valid'] !== 1) {
                continue;
            }
            $name = trim((string) $role['rol_name']);
            if ($category === self::TEAM_CATEGORY) {
                $type = 'TEAM';
            } elseif (in_array($name, self::FUNCTIONARY_ROLES, true)) {
                $type = 'FUNCTIONARY';
            } else {
                $type = 'OTHER';
            }
            $this->groups[(int) $role['rol_id']] = [
                'name'     => $name,
                'type'     => $type,
                'category' => $category,
            ];
        }
    }

    /**
     * Indexes the user fields and their values; empty values are dropped.
     */
    private function indexUserData(): void
    {
        foreach ($this->tables['adm_categories'] ?? [] as $category) {
            $this->fieldCategoryNames[(int) $category['cat_id']] = (string) $category['cat_name_intern'];
        }
        foreach ($this->tables['adm_user_fields'] ?? [] as $field) {
            $this->fieldNames[(int) $field['usf_id']]         = (string) $field['usf_name_intern'];
            $this->fieldCategories[(string) $field['usf_name_intern']] = (int) $field['usf_cat_id'];
        }
        foreach ($this->tables['adm_user_data'] ?? [] as $entry) {
            $value = trim((string) $entry['usd_value']);
            $name  = $this->fieldNames[(int) $entry['usd_usf_id']] ?? null;
            if ($value !== '' && $name !== null) {
                $this->userData[(int) $entry['usd_usr_id']][$name] = $value;
            }
        }
    }

    /**
     * Maps a user to a person with its memberships.
     *
     * @param int                              $userId  Admidio user id.
     * @param array<string, string>            $data    Field values of the user.
     * @param array<int, array<string, mixed>> $entries Active role memberships of the user.
     * @param array{name: string, start: string, end: string} $season The season.
     *
     * @return array<string, mixed> The person of the plan.
     */
    private function mapPerson(int $userId, array $data, array $entries, array $season): array
    {
        $warnings  = [];
        $roleNames = array_map(static fn (array $entry): string => (string) $entry['role']['name'], $entries);
        $inTeam    = array_filter($entries, static fn (array $entry): bool => $entry['role']['type'] === 'TEAM') !== [];
        $isActive  = $inTeam || in_array(self::ACTIVE_ROLE, $roleNames, true);

        $company = $data['SPONSORENNAME'] ?? $data['FIRMA'] ?? null;
        $legalRole = null;
        foreach ($roleNames as $roleName) {
            if (isset(self::LEGAL_MEMBER_TYPES[$roleName])) {
                $legalRole = $roleName;
            }
        }
        $isLegal = !$isActive && $legalRole !== null && $company !== null;

        $notes = [];
        foreach (['EMAIL_2' => 'E-Mail 2', 'CHILISMAIL' => 'Chilis-Mail', 'LIZENZNUMMER' => 'Lizenznummer',
            'VEREIN_DOPPELLIZENZ' => 'Verein Doppellizenz', 'BEMERKUNGEN' => 'Bemerkungen',
            'BEMERKUNG_MITGLIEDERORDNUNG' => 'Bemerkung Vereinsordnung', 'PERSNLICHER_SPONSOR' => 'Persönlicher Sponsor',
        ] as $field => $label) {
            if (isset($data[$field])) {
                $notes[] = $label . ': ' . $data[$field];
            }
        }
        if (!$isLegal && isset($data['FIRMA'])) {
            $notes[] = 'Firma: ' . $data['FIRMA'];
        }

        $payload = [
            'email'   => $data['EMAIL'] ?? null,
            'phone'   => $this->phone($data['MOBILE'] ?? null) ?? $this->phone($data['PHONE'] ?? null),
        ];
        $otherPhone = isset($data['MOBILE']) ? $this->phone($data['PHONE'] ?? null) : null;
        if ($otherPhone !== null && $otherPhone !== $payload['phone']) {
            $notes[] = 'Telefon: ' . $otherPhone;
        }
        $payload += $this->address($data, $warnings, $notes);

        $birthDate = $this->date($data['BIRTHDAY'] ?? null);
        $guardian  = null;

        if ($isLegal) {
            $payload += [
                'personType'       => 'LEGAL',
                'memberType'       => self::LEGAL_MEMBER_TYPES[$legalRole],
                'name'             => $company,
                'contactFirstName' => $data['FIRST_NAME'],
                'contactLastName'  => $data['LAST_NAME'],
            ];
        } else {
            $teamMember = $isActive && $birthDate !== null;
            if ($isActive && $birthDate === null) {
                $warnings[] = 'kein Geburtsdatum: nicht als Teammitglied importiert, Teamzuordnungen entfallen';
            }
            $memberType = $isActive ? 'ACTIVE' : (in_array('Ehrenmitglieder', $roleNames, true) ? 'HONORARY' : 'PASSIVE');
            $payload += [
                'personType' => 'NATURAL',
                'memberType' => $memberType,
                'firstName'  => $data['FIRST_NAME'],
                'lastName'   => $data['LAST_NAME'],
                'birthDate'  => $birthDate,
                'teamMember' => $teamMember,
            ];
            if ($teamMember) {
                $isMinor = (new DateTimeImmutable($birthDate))->diff($this->today)->y < 18;
                $payload['ahvNumber']    = $data['AHVNR'] ?? null;
                $payload['jsNumber']     = isset($data['JSNUMMER']) ? $data['JSNUMMER'] : null;
                $payload['photoConsent'] = $isMinor && ($data['EINVERSTNDNIS_FOTO_UNTER_18'] ?? '0') === '1';
                if ($isMinor) {
                    $guardian = $this->guardian($data, $payload, $warnings, $notes);
                }
            } elseif (isset($data['AHVNR'])) {
                $notes[] = 'AHV-Nr: ' . $data['AHVNR'];
            }
        }
        $payload['note'] = $notes === [] ? null : implode("\n", $notes);

        return [
            'userId'      => $userId,
            'displayName' => $isLegal ? (string) $company : $data['FIRST_NAME'] . ' ' . $data['LAST_NAME'],
            'payload'     => $payload,
            'guardian'    => $guardian,
            'warnings'    => $warnings,
            'memberships' => $this->mapMemberships($entries, $data, (bool) ($payload['teamMember'] ?? false), $season, $warnings),
        ];
    }

    /**
     * Maps the memberships of a person.
     *
     * @param array<int, array<string, mixed>> $entries    Active role memberships.
     * @param array<string, string>            $data       Field values of the user.
     * @param bool                             $teamMember Whether the person is imported as a team member.
     * @param array{name: string, start: string, end: string} $season The season.
     * @param array<int, string>               $warnings   Collects warnings.
     *
     * @return array<int, array<string, mixed>> The memberships.
     */
    private function mapMemberships(array $entries, array $data, bool $teamMember, array $season, array &$warnings): array
    {
        $memberships = [];
        foreach ($entries as $entry) {
            $role  = $entry['role'];
            $isTeam = $role['type'] === 'TEAM';
            if ($isTeam && !$teamMember) {
                $warnings[] = 'Zuordnung zu «' . $role['name'] . '» entfällt (kein Teammitglied)';
                continue;
            }
            $end  = (string) $entry['end'];
            $exit = $end !== '9999-12-31' && $end <= $season['end'] ? $end : null;

            $memberships[] = [
                'group'       => $role['name'],
                'groupType'   => $role['type'],
                'role'        => $isTeam ? ($entry['leader'] ? 'COACH' : 'PLAYER') : null,
                'function'    => $this->functionText($role['name'], $data),
                'shirtNumber' => $isTeam && !$entry['leader'] ? ($this->shirtNumber($data['TRIKOTNR'] ?? null)) : null,
                'exitDate'    => $exit,
            ];
        }

        return $memberships;
    }

    /**
     * Builds the function text from the checked function fields.
     *
     * The board role takes the fields of the category «Vorstand», the role
     * «Funktionäre» those of the category «Funktionen».
     *
     * @param string                $roleName Name of the role.
     * @param array<string, string> $data     Field values of the user.
     *
     * @return string|null The function, or null when none is checked.
     */
    private function functionText(string $roleName, array $data): ?string
    {
        $category = match ($roleName) {
            'Vorstand'    => 'VORSTAND',
            'Funktionäre' => 'FUNKTIONEN',
            default       => null,
        };
        if ($category === null) {
            return null;
        }
        $labels = [];
        foreach ($this->tables['adm_user_fields'] ?? [] as $field) {
            $internal = (string) $field['usf_name_intern'];
            $catName  = $this->fieldCategoryNames[(int) $field['usf_cat_id']] ?? '';
            if ($catName === $category && ($data[$internal] ?? '0') === '1') {
                $labels[] = (string) $field['usf_name'];
            }
        }

        return $labels === [] ? null : mb_substr(implode(', ', $labels), 0, 100);
    }

    /**
     * Reads a shirt number.
     *
     * @param string|null $value Raw value.
     *
     * @return int|null The number, or null when empty or invalid.
     */
    private function shirtNumber(?string $value): ?int
    {
        return $value !== null && preg_match('/^\d{1,3}$/', $value) === 1 ? (int) $value : null;
    }

    /**
     * Maps the postal address; an incomplete address moves to the note.
     *
     * @param array<string, string> $data     Field values of the user.
     * @param array<int, string>    $warnings Collects warnings.
     * @param array<int, string>    $notes    Collects note lines.
     *
     * @return array<string, string|null> `street`, `houseNumber`, `zip`, `city`, `country`.
     */
    private function address(array $data, array &$warnings, array &$notes): array
    {
        $street  = $data['STREET'] ?? null;
        $zip     = $data['POSTCODE'] ?? null;
        $city    = $data['CITY'] ?? null;
        $code    = $data['COUNTRY'] ?? 'CHE';
        $country = self::COUNTRIES[strtoupper($code)] ?? (strlen($code) === 2 ? strtoupper($code) : null);
        $empty   = ['street' => null, 'houseNumber' => null, 'zip' => null, 'city' => null, 'country' => 'CH'];

        if ($street === null && $zip === null && $city === null) {
            return $empty;
        }
        if ($street === null || $zip === null || $city === null || $country === null) {
            $warnings[] = 'Adresse unvollständig: in die Notiz übernommen';
            $notes[]    = 'Adresse: ' . implode(', ', array_filter([$street, trim(($zip ?? '') . ' ' . ($city ?? '')), $code]));

            return $empty;
        }
        $houseNumber = null;
        if (preg_match('/^(.*?)\s+(\d+\s*[a-zA-Z]?(?:\s*[-\/]\s*\d+\s*[a-zA-Z]?)?)$/u', $street, $matches) === 1) {
            $street      = trim($matches[1]);
            $houseNumber = preg_replace('/\s+/', '', $matches[2]);
        }

        return ['street' => $street, 'houseNumber' => $houseNumber, 'zip' => $zip, 'city' => $city, 'country' => $country];
    }

    /**
     * Normalises a phone number; placeholders such as «0» become null.
     *
     * @param string|null $value Raw value.
     *
     * @return string|null The number, or null.
     */
    private function phone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_split('/\s*(?:\/|\s{2,})\s*(?=\+|\d{3})/', $value)[0]);

        return preg_match('/^\+?[0-9 ()\/.-]{6,40}$/', $value) === 1 ? $value : null;
    }

    /**
     * Reads a date; empty or invalid dates become null.
     *
     * @param string|null $value Raw value.
     *
     * @return string|null The date in `YYYY-MM-DD` notation, or null.
     */
    private function date(?string $value): ?string
    {
        if ($value === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1 || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return $value;
    }

    /**
     * Maps the parent data of a minor team member.
     *
     * With a name of the form «First Last» and an e-mail address a guardian is
     * created. Otherwise the data goes to the note and the e-mail address of the
     * parents serves as the contact address of the minor.
     *
     * @param array<string, string> $data     Field values of the user.
     * @param array<string, mixed>  $payload  Payload of the minor; may receive an e-mail address.
     * @param array<int, string>    $warnings Collects warnings.
     * @param array<int, string>    $notes    Collects note lines.
     *
     * @return array<string, mixed>|null The payload of the guardian, or null.
     */
    private function guardian(array $data, array &$payload, array &$warnings, array &$notes): ?array
    {
        $parentName  = $data['NAME_ELTERN'] ?? null;
        $parentEmail = $data['EMAIL_ELTERN'] ?? null;
        $parentPhone = $this->phone($data['MOBIL_ELTERN'] ?? null);

        if ($parentName === null && $parentEmail === null && $parentPhone === null) {
            return null;
        }
        if ($parentName !== null && $parentEmail !== null
            && preg_match('/^(\p{Lu}[\p{L}\'-]+(?: \p{Lu}[\p{L}\'-]+)?) (\p{Lu}[\p{L}\'-]+)$/u', $parentName, $matches) === 1) {
            return [
                'personType' => 'NATURAL',
                'memberType' => 'PARENT',
                'firstName'  => $matches[1],
                'lastName'   => $matches[2],
                'email'      => $parentEmail,
                'phone'      => $parentPhone,
                'street'     => $payload['street'],
                'houseNumber' => $payload['houseNumber'],
                'zip'        => $payload['zip'],
                'city'       => $payload['city'],
                'country'    => $payload['country'],
            ];
        }

        $notes[] = 'Eltern: ' . implode(', ', array_filter([$parentName, $parentEmail, $parentPhone]));
        if ($payload['email'] === null && $parentEmail !== null) {
            $payload['email'] = $parentEmail;
            $warnings[]       = 'Elternteil nicht eindeutig: E-Mail der Eltern als Kontaktadresse übernommen';
        } else {
            $warnings[] = 'Elternteil nicht eindeutig: in die Notiz übernommen, kein Erziehungsberechtigter verknüpft';
        }

        return null;
    }
}

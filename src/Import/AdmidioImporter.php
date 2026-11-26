<?php

declare(strict_types=1);

namespace Clubhuesli\Import;

use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Model\AbstractPerson;
use Clubhuesli\Model\Group;
use Clubhuesli\Model\LegalPerson;
use Clubhuesli\Model\Membership;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\Season;
use Clubhuesli\Model\TeamMember;
use Clubhuesli\Validation\ValidationException;
use RuntimeException;

/**
 * Writes an import plan of {@see AdmidioMapper} to the database.
 *
 * Everything goes through the models and data access objects, so the same
 * validation applies as in the application. Optional values that fail the
 * validation (e.g. a wrong AHV number) are dropped, kept in the note and
 * reported; a person that still fails is skipped and reported.
 *
 * The import can be repeated: existing seasons, groups, persons and
 * memberships are recognised and not created twice.
 */
final class AdmidioImporter
{
    /** Optional fields that are dropped when their value is invalid: error field => payload fields to clear. */
    private const DROPPABLE = [
        'phone'       => ['phone'],
        'ahvNumber'   => ['ahvNumber'],
        'jsNumber'    => ['jsNumber'],
        'email'       => ['email'],
        'photoConsent' => ['photoConsent'],
        'houseNumber' => ['street', 'houseNumber', 'zip', 'city'],
        'street'      => ['street', 'houseNumber', 'zip', 'city'],
        'zip'         => ['street', 'houseNumber', 'zip', 'city'],
        'city'        => ['street', 'houseNumber', 'zip', 'city'],
        'country'     => ['street', 'houseNumber', 'zip', 'city', 'country'],
    ];

    /**
     * @param PersonDao     $personDao     Data access for persons.
     * @param SeasonDao     $seasonDao     Data access for seasons.
     * @param GroupDao      $groupDao      Data access for groups.
     * @param MembershipDao $membershipDao Data access for memberships.
     */
    public function __construct(
        private readonly PersonDao $personDao,
        private readonly SeasonDao $seasonDao,
        private readonly GroupDao $groupDao,
        private readonly MembershipDao $membershipDao,
    ) {
    }

    /**
     * Writes the plan.
     *
     * @param array<string, mixed> $plan Plan of {@see AdmidioMapper::plan()}.
     *
     * @return array{
     *     counts: array<string, int>,
     *     warnings: array<int, string>,
     *     failures: array<int, string>
     * } The result.
     *
     * @throws RuntimeException When the season overlaps with an existing one.
     */
    public function import(array $plan): array
    {
        $counts = [
            'seasons' => 0, 'groups' => 0, 'links' => 0, 'persons' => 0, 'personsExisting' => 0, 'guardians' => 0,
            'memberships' => 0, 'membershipsExisting' => 0, 'skipped' => count($plan['skipped']),
            'failed' => 0,
        ];
        $warnings = [];
        $failures = [];
        foreach ($plan['skipped'] as $skipped) {
            $failures[] = sprintf('übersprungen: %s (Admidio-Id %d): %s', $skipped['name'], $skipped['id'], $skipped['reason']);
        }

        $season = $this->ensureSeason($plan['season'], $counts);
        $groups = $this->ensureGroups($plan['groups'], $counts, $warnings);
        $this->ensureLinks($plan['links'] ?? [], $groups, $counts);

        /** @var array<string, int> $guardianIds Ids of guardians created in this run, by name and e-mail. */
        $guardianIds = [];

        foreach ($plan['persons'] as $entry) {
            $label       = sprintf('%s (Admidio-Id %d)', $entry['displayName'], $entry['userId']);
            $entryWarnings = $entry['warnings'];

            $result = $this->buildWithGuardian($entry, $guardianIds, $counts, $entryWarnings);
            if (is_string($result)) {
                $counts['failed']++;
                $failures[] = "nicht importiert: {$label}: {$result}";
                continue;
            }

            $existing = $this->findExisting($result);
            if ($existing !== null) {
                $person = $existing;
                $counts['personsExisting']++;
            } else {
                $this->personDao->save($result);
                $person = $result;
                $counts['persons']++;
            }

            foreach ($entry['memberships'] as $membership) {
                $problem = $this->saveMembership($person, $season, $groups[$membership['group']] ?? null, $membership, $counts);
                if ($problem !== null) {
                    $entryWarnings[] = $problem;
                }
            }
            foreach ($entryWarnings as $warning) {
                $warnings[] = "{$label}: {$warning}";
            }
        }

        return ['counts' => $counts, 'warnings' => $warnings, 'failures' => $failures];
    }

    /**
     * Returns the season, creating it when needed.
     *
     * @param array{name: string, start: string, end: string} $data   Season of the plan.
     * @param array<string, int>                              $counts Counters.
     *
     * @return Season The season.
     *
     * @throws RuntimeException When the dates overlap with another season.
     */
    private function ensureSeason(array $data, array &$counts): Season
    {
        $existing = $this->seasonDao->findByName($data['name']);
        if ($existing !== null) {
            return $existing;
        }
        $season = Season::fromArray(['name' => $data['name'], 'startDate' => $data['start'], 'endDate' => $data['end']]);
        $season->assertValid();
        foreach ($this->seasonDao->findAll() as $other) {
            if ($season->overlaps($other)) {
                throw new RuntimeException(
                    "Die Saison {$data['name']} überschneidet sich mit der bestehenden Saison {$other->getName()}"
                );
            }
        }
        $this->seasonDao->save($season);
        $counts['seasons']++;

        return $season;
    }

    /**
     * Returns the groups of the plan by name, creating the missing ones.
     *
     * @param array<string, string> $planned  Group types by name.
     * @param array<string, int>    $counts   Counters.
     * @param array<int, string>    $warnings Collects warnings.
     *
     * @return array<string, Group> The groups by name.
     */
    private function ensureGroups(array $planned, array &$counts, array &$warnings): array
    {
        $groups = [];
        foreach ($planned as $name => $type) {
            $group = $this->groupDao->findByName($name);
            if ($group === null) {
                $group = Group::fromArray(['name' => $name, 'groupType' => $type]);
                $group->assertValid();
                $this->groupDao->save($group);
                $counts['groups']++;
            } elseif ($group->getGroupType()?->value !== $type) {
                $warnings[] = "Gruppe «{$name}» besteht bereits mit der Gruppenart {$group->getGroupType()?->value} (erwartet {$type})";
            }
            $groups[$name] = $group;
        }

        return $groups;
    }

    /**
     * Creates the parent/child links of the group hierarchy that do not exist yet.
     *
     * @param array<int, array{0: string, 1: string}> $links  Pairs of parent name and child name.
     * @param array<string, Group>                    $groups The groups by name.
     * @param array<string, int>                      $counts Counters.
     */
    private function ensureLinks(array $links, array $groups, array &$counts): void
    {
        $existing = [];
        foreach ($this->groupDao->findLinks() as [$parentId, $childId]) {
            $existing[$parentId . '-' . $childId] = true;
        }
        foreach ($links as [$parent, $child]) {
            if (!isset($groups[$parent], $groups[$child])) {
                continue;
            }
            $key = $groups[$parent]->getId() . '-' . $groups[$child]->getId();
            if (!isset($existing[$key])) {
                $this->groupDao->addLink((int) $groups[$parent]->getId(), (int) $groups[$child]->getId());
                $counts['links']++;
            }
        }
    }

    /**
     * Builds the person of a plan entry together with its guardian.
     *
     * A minor without own e-mail address is only valid with a guardian, so the
     * guardian is created (or found) first when the person is not valid alone.
     *
     * @param array<string, mixed> $entry       Person of the plan.
     * @param array<string, int>   $guardianIds Guardians created in this run.
     * @param array<string, int>   $counts      Counters.
     * @param array<int, string>   $warnings    Collects warnings.
     *
     * @return AbstractPerson|string The valid person, or the reason why it cannot be imported.
     */
    private function buildWithGuardian(array $entry, array &$guardianIds, array &$counts, array &$warnings): AbstractPerson|string
    {
        $attemptWarnings = [];
        $person          = $this->buildPerson($entry['payload'], $attemptWarnings);

        if ($entry['guardian'] !== null && (is_string($person) || $person instanceof TeamMember)) {
            $isNew = !is_string($person) && $this->findExisting($person) === null;
            if (is_string($person) || $isNew) {
                $guardian = $this->ensureGuardian($entry['guardian'], $guardianIds, $counts, $warnings);
                if ($guardian !== null) {
                    $attemptWarnings = [];
                    $person          = $this->buildPerson($entry['payload'], $attemptWarnings, $guardian);
                }
            }
        }
        if (!is_string($person)) {
            array_push($warnings, ...$attemptWarnings);
        }

        return $person;
    }

    /**
     * Builds and validates a person from a payload.
     *
     * Invalid optional values are dropped (and kept in the note) until the
     * person is valid or a mandatory value is wrong.
     *
     * @param array<string, mixed> $payload  Payload of the plan.
     * @param array<int, string>   $warnings Collects warnings.
     * @param NaturalPerson|null   $guardian Guardian to link to a team member.
     *
     * @return AbstractPerson|string The valid person, or the reason why it cannot be imported.
     */
    private function buildPerson(array $payload, array &$warnings, ?NaturalPerson $guardian = null): AbstractPerson|string
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            try {
                $person = $this->createPerson($payload);
                if ($guardian !== null && $person instanceof TeamMember && $person->isMinor()) {
                    $person->setGuardians([$guardian]);
                }
                $person->assertValid();

                return $person;
            } catch (ValidationException $exception) {
                $dropped = false;
                foreach ($exception->getErrors() as $error) {
                    foreach (self::DROPPABLE[$error['field']] ?? [] as $field) {
                        if (($payload[$field] ?? null) === null || $payload[$field] === false) {
                            continue;
                        }
                        $payload['note'] = trim(($payload['note'] ?? '') . "\nNicht übernommen ({$field}): " . (string) $payload[$field]);
                        $payload[$field] = $field === 'photoConsent' ? false : null;
                        $dropped         = true;
                    }
                    if ($dropped) {
                        $warnings[] = 'Wert verworfen: ' . $error['message'];
                    }
                }
                if (!$dropped) {
                    return $exception->getMessage();
                }
            }
        }

        return 'zu viele ungültige Werte';
    }

    /**
     * Creates the model object that belongs to a payload.
     *
     * @param array<string, mixed> $payload Payload of the plan.
     *
     * @return AbstractPerson The person.
     *
     * @throws ValidationException When a value is invalid.
     */
    private function createPerson(array $payload): AbstractPerson
    {
        if ($payload['personType'] === 'LEGAL') {
            return LegalPerson::fromArray($payload);
        }

        return ($payload['teamMember'] ?? false) ? TeamMember::fromArray($payload) : NaturalPerson::fromArray($payload);
    }

    /**
     * Finds a stored person that corresponds to a person of the import.
     *
     * Natural persons match by name and birth date (or e-mail address when a
     * birth date is missing), legal persons by name.
     *
     * @param AbstractPerson $candidate The person of the import.
     *
     * @return AbstractPerson|null The stored person, or null.
     */
    private function findExisting(AbstractPerson $candidate): ?AbstractPerson
    {
        $needle = $candidate instanceof NaturalPerson ? (string) $candidate->getLastName() : (string) $candidate->getDisplayName();
        foreach ($this->personDao->findAll($needle) as $stored) {
            if ($stored->getPersonType() !== $candidate->getPersonType()
                || mb_strtolower($stored->getDisplayName()) !== mb_strtolower($candidate->getDisplayName())) {
                continue;
            }
            if (!$candidate instanceof NaturalPerson || !$stored instanceof NaturalPerson) {
                return $stored;
            }
            $sameBirth = $candidate->getBirthDate() !== null && $stored->getBirthDate() !== null
                && $candidate->getBirthDate() == $stored->getBirthDate();
            $sameEmail = $candidate->getEmail() !== null && $candidate->getEmail() === $stored->getEmail();
            if ($sameBirth || ($candidate->getBirthDate() === null && $sameEmail)) {
                return $stored;
            }
        }

        return null;
    }

    /**
     * Returns the guardian, creating it when needed. Siblings share a guardian.
     *
     * @param array<string, mixed> $payload     Payload of the guardian.
     * @param array<string, int>   $guardianIds Guardians created in this run, by name and e-mail.
     * @param array<string, int>   $counts      Counters.
     * @param array<int, string>   $warnings    Collects warnings.
     *
     * @return NaturalPerson|null The guardian, or null when it cannot be created.
     */
    private function ensureGuardian(array $payload, array &$guardianIds, array &$counts, array &$warnings): ?NaturalPerson
    {
        $key = mb_strtolower($payload['firstName'] . '|' . $payload['lastName'] . '|' . $payload['email']);
        if (isset($guardianIds[$key])) {
            $stored = $this->personDao->find($guardianIds[$key]);

            return $stored instanceof NaturalPerson ? $stored : null;
        }

        $guardian = $this->buildPerson($payload, $warnings);
        if (is_string($guardian) || !$guardian instanceof NaturalPerson) {
            $warnings[] = 'Erziehungsberechtigte konnte nicht angelegt werden: ' . (is_string($guardian) ? $guardian : 'Fehler');

            return null;
        }
        $existing = $this->findExisting($guardian);
        if ($existing instanceof NaturalPerson) {
            $guardianIds[$key] = (int) $existing->getId();

            return $existing;
        }
        $this->personDao->save($guardian);
        $guardianIds[$key] = (int) $guardian->getId();
        $counts['guardians']++;

        return $guardian;
    }

    /**
     * Stores a membership unless it exists already.
     *
     * @param AbstractPerson       $person     The stored person.
     * @param Season               $season     The season.
     * @param Group|null           $group      The stored group.
     * @param array<string, mixed> $data       Membership of the plan.
     * @param array<string, int>   $counts     Counters.
     *
     * @return string|null A problem to report, or null when everything went well.
     */
    private function saveMembership(AbstractPerson $person, Season $season, ?Group $group, array $data, array &$counts): ?string
    {
        if ($group === null) {
            return "Gruppe «{$data['group']}» fehlt";
        }
        if (!$group->isActive()) {
            return "Zuordnung zu «{$data['group']}» entfällt: Gruppe ist inaktiv";
        }

        try {
            $membership = new Membership();
            $membership->setGroupId($group->getId());
            $membership->setPersonId($person->getId());
            $membership->setSeasonId($season->getId());
            $membership->fillFromArray([
                'role'        => $data['role'],
                'function'    => $data['function'],
                'shirtNumber' => $data['shirtNumber'],
                'exitDate'    => $data['exitDate'],
            ]);
            $errors = $membership->validateAgainst($group, $season, $person);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
        }
        if ($errors !== []) {
            return "Zuordnung zu «{$data['group']}» entfällt: " . $errors[0]['message'];
        }
        if ($this->membershipDao->findDuplicate($membership) !== null) {
            $counts['membershipsExisting']++;

            return null;
        }
        $this->membershipDao->save($membership);
        $counts['memberships']++;

        return null;
    }
}

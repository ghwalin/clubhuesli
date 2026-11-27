<?php

declare(strict_types=1);

namespace Clubhuesli\Service;

use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Model\Group;
use Clubhuesli\Model\GroupHierarchy;
use Clubhuesli\Model\Membership;
use Clubhuesli\Model\MembershipRole;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\Season;
use DateTimeImmutable;

/**
 * Answers the read questions around memberships: who belongs to a group in a
 * season (including the members of the child groups, which belong to the parent
 * groups transitively) and to which groups a person belongs.
 */
final class MembershipReader
{
    /**
     * @param GroupDao      $groupDao      Data access for groups.
     * @param SeasonDao     $seasonDao     Data access for seasons.
     * @param MembershipDao $membershipDao Data access for memberships.
     * @param PersonDao     $personDao     Data access for persons.
     */
    public function __construct(
        private readonly GroupDao $groupDao,
        private readonly SeasonDao $seasonDao,
        private readonly MembershipDao $membershipDao,
        private readonly PersonDao $personDao,
    ) {
    }

    /**
     * Returns the hierarchy of all groups.
     *
     * @return GroupHierarchy The hierarchy.
     */
    public function hierarchy(): GroupHierarchy
    {
        return new GroupHierarchy($this->groupDao->findLinks());
    }

    /**
     * Returns the season that contains a day.
     *
     * @param DateTimeImmutable|null $today The day; defaults to today.
     *
     * @return Season|null The current season, or null when there is none.
     */
    public function currentSeason(?DateTimeImmutable $today = null): ?Season
    {
        foreach ($this->seasonDao->findAll() as $season) {
            if ($season->isCurrent($today)) {
                return $season;
            }
        }

        return null;
    }

    /**
     * Lists the members of a group in a season.
     *
     * Direct memberships come first; persons who belong to the group only via a
     * child group appear once with the names of those child groups. Persons who
     * have left (exit date reached) are marked `former` and only listed on request.
     *
     * @param Group                  $group         The group.
     * @param Season                 $season        The season.
     * @param bool                   $includeFormer Whether to include members who have left, and
     *                                              persons of other seasons in `previousSeasons`.
     * @param DateTimeImmutable|null $today         The reference day; defaults to today.
     *
     * @return array{members: array<int, array<string, mixed>>, previousSeasons: array<int, array<string, mixed>>}
     */
    public function membersOf(
        Group $group,
        Season $season,
        bool $includeFormer = false,
        ?DateTimeImmutable $today = null
    ): array {
        $today    = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $groupId  = (int) $group->getId();
        $hierarchy = $this->hierarchy();
        $groups    = $this->groupsById();
        $seasons   = $this->seasonsById();

        $descendantIds = $hierarchy->descendantsOf($groupId);
        $all           = $this->membershipDao->findByGroups(array_merge([$groupId], $descendantIds));

        $rows          = [];
        $directPersons = [];
        $derived       = [];
        foreach ($all as $membership) {
            if ($membership->getSeasonId() !== $season->getId()) {
                continue;
            }
            if ($membership->getGroupId() === $groupId) {
                $directPersons[$membership->getPersonId()] = true;
                $rows[] = ['membership' => $membership, 'via' => [], 'exitDate' => $membership->getExitDate()];
                continue;
            }
            $derived[(int) $membership->getPersonId()][] = $membership;
        }
        foreach ($derived as $personId => $memberships) {
            if (isset($directPersons[$personId])) {
                continue;
            }
            $via = [];
            foreach ($memberships as $membership) {
                $via[(string) $groups[$membership->getGroupId()]?->getName()] = true;
            }
            $rows[] = [
                'membership' => $memberships[0],
                'via'        => array_keys($via),
                'exitDate'   => self::effectiveExitDate($memberships),
            ];
        }

        $members = [];
        foreach ($rows as $row) {
            $entry = $this->describe($row['membership'], $row['exitDate'], $row['via'], $season, $today);
            if ($entry === null) {
                continue;
            }
            if ($entry['former'] && !$includeFormer) {
                continue;
            }
            $members[] = $entry;
        }
        usort($members, static function (array $a, array $b) use ($group): int {
            $order = [MembershipRole::COACH->value => 0, MembershipRole::PLAYER->value => 1, MembershipRole::MEMBER->value => 2];
            if ($group->isTeam() && $a['role'] !== $b['role']) {
                return $order[$a['role']] <=> $order[$b['role']];
            }

            return strcmp($a['sortKey'], $b['sortKey']);
        });

        $previous = [];
        if ($includeFormer) {
            $shown = array_column($members, 'personId');
            $names = [];
            foreach ($all as $membership) {
                $personId = (int) $membership->getPersonId();
                if ($membership->getSeasonId() === $season->getId() || in_array($personId, $shown, true)) {
                    continue;
                }
                $names[$personId][(string) $seasons[$membership->getSeasonId()]?->getName()] = $membership->getExitDate();
            }
            foreach ($names as $personId => $seasonNames) {
                $person = $this->personDao->find($personId);
                if ($person === null) {
                    continue;
                }
                $previous[] = [
                    'personId'    => $personId,
                    'displayName' => $person->getDisplayName(),
                    'personType'  => $person->getPersonType()->value,
                    'seasons'     => array_keys($seasonNames),
                    'sortKey'     => self::sortKey($person),
                ];
            }
            usort($previous, static fn (array $a, array $b): int => strcmp($a['sortKey'], $b['sortKey']));
        }

        return ['members' => $members, 'previousSeasons' => $previous];
    }

    /**
     * Lists the groups of a person: the direct memberships and the memberships
     * derived from child groups, all seasons.
     *
     * @param int $personId Id of the person.
     *
     * @return array{memberships: array<int, array<string, mixed>>, derived: array<int, array<string, mixed>>}
     */
    public function groupsOf(int $personId): array
    {
        $hierarchy = $this->hierarchy();
        $groups    = $this->groupsById();
        $seasons   = $this->seasonsById();
        $direct    = $this->membershipDao->findByPerson($personId);

        $directKeys = [];
        $memberships = [];
        foreach ($direct as $membership) {
            $directKeys[$membership->getGroupId() . '-' . $membership->getSeasonId()] = true;
            $season = $seasons[$membership->getSeasonId()] ?? null;
            $group  = $groups[$membership->getGroupId()] ?? null;
            $memberships[] = $membership->toArray() + [
                'groupName'   => $group?->getName(),
                'groupType'   => $group?->getGroupType()?->value,
                'seasonName'  => $season?->getName(),
                'seasonStart' => $season?->getStartDate()?->format('Y-m-d'),
                'seasonEnd'   => $season?->getEndDate()?->format('Y-m-d'),
            ];
        }

        $buckets = [];
        foreach ($direct as $membership) {
            foreach ($hierarchy->ancestorsOf((int) $membership->getGroupId()) as $ancestorId) {
                $key = $ancestorId . '-' . $membership->getSeasonId();
                if (isset($directKeys[$key])) {
                    continue;
                }
                $buckets[$key]['groupId']   = $ancestorId;
                $buckets[$key]['seasonId']  = $membership->getSeasonId();
                $buckets[$key]['entries'][] = $membership;
            }
        }
        $derived = [];
        foreach ($buckets as $bucket) {
            $via = [];
            foreach ($bucket['entries'] as $membership) {
                $via[(string) ($groups[$membership->getGroupId()]?->getName())] = true;
            }
            $season    = $seasons[$bucket['seasonId']] ?? null;
            $derived[] = [
                'groupId'     => $bucket['groupId'],
                'groupName'   => $groups[$bucket['groupId']]?->getName(),
                'seasonId'    => $bucket['seasonId'],
                'seasonName'  => $season?->getName(),
                'seasonStart' => $season?->getStartDate()?->format('Y-m-d'),
                'via'         => array_keys($via),
                'exitDate'    => self::effectiveExitDate($bucket['entries'])?->format('Y-m-d'),
            ];
        }

        $byRecent = static fn (array $a, array $b): int => strcmp((string) $b['seasonStart'], (string) $a['seasonStart']);
        usort($memberships, $byRecent);
        usort($derived, $byRecent);

        return ['memberships' => $memberships, 'derived' => $derived];
    }

    /**
     * Returns the exit date a derived membership has: the person stays a member
     * of the parent group as long as one child membership lasts.
     *
     * @param array<int, Membership> $memberships The memberships in the child groups.
     *
     * @return DateTimeImmutable|null The latest exit date, or null when one membership has none.
     */
    public static function effectiveExitDate(array $memberships): ?DateTimeImmutable
    {
        $latest = null;
        foreach ($memberships as $membership) {
            $exit = $membership->getExitDate();
            if ($exit === null) {
                return null;
            }
            if ($latest === null || $exit > $latest) {
                $latest = $exit;
            }
        }

        return $latest;
    }

    /**
     * Builds one row of a member list.
     *
     * @param Membership             $membership Membership the row is based on.
     * @param DateTimeImmutable|null $exitDate   Effective exit date.
     * @param array<int, string>     $via        Names of the child groups for a derived membership.
     * @param Season                 $season     The season.
     * @param DateTimeImmutable      $today      The reference day.
     *
     * @return array<string, mixed>|null The row, or null when the person no longer exists.
     */
    private function describe(
        Membership $membership,
        ?DateTimeImmutable $exitDate,
        array $via,
        Season $season,
        DateTimeImmutable $today
    ): ?array {
        $person = $this->personDao->find((int) $membership->getPersonId());
        if ($person === null) {
            return null;
        }
        $former = $season->isCurrent($today) && $exitDate !== null && $exitDate < $today;

        return [
            'membershipId' => $via === [] ? $membership->getId() : null,
            'derived'      => $via !== [],
            'via'          => $via,
            'personId'     => $person->getId(),
            'displayName'  => $person->getDisplayName(),
            'personType'   => $person->getPersonType()->value,
            'role'         => $via === [] ? $membership->getRole()->value : MembershipRole::MEMBER->value,
            'roleLabel'    => $via === [] ? $membership->getRole()->label() : MembershipRole::MEMBER->label(),
            'function'     => $via === [] ? $membership->getFunction() : null,
            'shirtNumber'  => $via === [] ? $membership->getShirtNumber() : null,
            'exitDate'     => $exitDate?->format('Y-m-d'),
            'former'       => $former,
            'sortKey'      => self::sortKey($person),
        ];
    }

    /**
     * Returns the key by which persons are sorted: last name, then first name.
     *
     * @param \Clubhuesli\Model\AbstractPerson $person The person.
     *
     * @return string The sort key.
     */
    private static function sortKey(\Clubhuesli\Model\AbstractPerson $person): string
    {
        if ($person instanceof NaturalPerson) {
            return mb_strtolower(($person->getLastName() ?? '') . ' ' . ($person->getFirstName() ?? ''));
        }

        return mb_strtolower($person->getDisplayName());
    }

    /**
     * Returns all groups keyed by id.
     *
     * @return array<int, Group> The groups.
     */
    private function groupsById(): array
    {
        $groups = [];
        foreach ($this->groupDao->findAll() as $group) {
            $groups[(int) $group->getId()] = $group;
        }

        return $groups;
    }

    /**
     * Returns all seasons keyed by id.
     *
     * @return array<int, Season> The seasons.
     */
    private function seasonsById(): array
    {
        $seasons = [];
        foreach ($this->seasonDao->findAll() as $season) {
            $seasons[(int) $season->getId()] = $season;
        }

        return $seasons;
    }
}

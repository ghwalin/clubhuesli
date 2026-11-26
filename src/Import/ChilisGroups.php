<?php

declare(strict_types=1);

namespace Clubhuesli\Import;

/**
 * The standard groups and teams of Chilis and their hierarchy (see
 * `chilis/groups.md` and `database/seed_groups.sql`).
 */
final class ChilisGroups
{
    /** Names of the teams. */
    public const TEAMS = ['NLB', '1. Liga KF', 'U21B', 'U17B', 'Jun B', 'Jun C', 'Jun D', 'Starters'];

    /** Group types of the groups that are no teams; every group not listed here is of type OTHER. */
    private const FUNCTIONARY_GROUPS = ['Vorstand', 'Funktionärin', 'Spielsekretärin'];

    /** Group hierarchy: parent => children, in the order of `chilis/groups.md`. */
    public const HIERARCHY = [
        'Aktivmitglied'  => ['Vorstand', 'Funktionärin', 'Spielerin', 'Coach', 'Ehrenmitglied', 'Schiedsrichter'],
        'Funktionärin'   => ['Spielsekretärin'],
        'Passivmitglied' => ['Freimitglied', '200er Club'],
        'Partner'        => ['Sponsoren', 'Persönliche Sponsoren', 'Gemeinden'],
    ];

    /** Groups without parent or children that are not mentioned in the hierarchy. */
    private const SINGLE_GROUPS = ['Alumni'];

    /**
     * Returns all groups with their group type, teams first.
     *
     * @return array<string, string> Group type (TEAM, FUNCTIONARY, OTHER) by group name.
     */
    public static function groups(): array
    {
        $groups = [];
        foreach (self::TEAMS as $team) {
            $groups[$team] = 'TEAM';
        }
        $names = [];
        foreach (self::HIERARCHY as $parent => $children) {
            $names[] = $parent;
            array_push($names, ...$children);
        }
        array_push($names, ...self::SINGLE_GROUPS);
        foreach (array_unique($names) as $name) {
            $groups[$name] = in_array($name, self::FUNCTIONARY_GROUPS, true) ? 'FUNCTIONARY' : 'OTHER';
        }

        return $groups;
    }

    /**
     * Returns the parent/child links of the hierarchy.
     *
     * @return array<int, array{0: string, 1: string}> Pairs of parent name and child name.
     */
    public static function links(): array
    {
        $links = [];
        foreach (self::HIERARCHY as $parent => $children) {
            foreach ($children as $child) {
                $links[] = [$parent, $child];
            }
        }

        return $links;
    }
}

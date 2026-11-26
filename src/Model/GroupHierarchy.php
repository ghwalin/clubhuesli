<?php

declare(strict_types=1);

namespace Clubhuesli\Model;

/**
 * The parent/child relations between groups (n:n, acyclic).
 *
 * The class works on a list of edges and answers the questions of the
 * hierarchy: ancestors, descendants and whether a new link would close a cycle.
 */
final class GroupHierarchy
{
    /** @var array<int, array<int, int>> Child ids, keyed by the parent id. */
    private array $children = [];

    /** @var array<int, array<int, int>> Parent ids, keyed by the child id. */
    private array $parents = [];

    /**
     * @param array<int, array{0: int, 1: int}> $edges Pairs of parent id and child id.
     */
    public function __construct(array $edges = [])
    {
        foreach ($edges as [$parentId, $childId]) {
            $this->children[$parentId][] = $childId;
            $this->parents[$childId][]   = $parentId;
        }
    }

    /**
     * Returns the direct parents of a group.
     *
     * @param int $groupId Id of the group.
     *
     * @return array<int, int> Ids of the parent groups.
     */
    public function parentsOf(int $groupId): array
    {
        return $this->parents[$groupId] ?? [];
    }

    /**
     * Returns the direct children of a group.
     *
     * @param int $groupId Id of the group.
     *
     * @return array<int, int> Ids of the child groups.
     */
    public function childrenOf(int $groupId): array
    {
        return $this->children[$groupId] ?? [];
    }

    /**
     * Returns all direct and indirect parents of a group.
     *
     * @param int $groupId Id of the group.
     *
     * @return array<int, int> Ids of the ancestors, each once.
     */
    public function ancestorsOf(int $groupId): array
    {
        return self::walk($this->parents, $groupId);
    }

    /**
     * Returns all direct and indirect children of a group.
     *
     * @param int $groupId Id of the group.
     *
     * @return array<int, int> Ids of the descendants, each once.
     */
    public function descendantsOf(int $groupId): array
    {
        return self::walk($this->children, $groupId);
    }

    /**
     * Tells whether a link already exists.
     *
     * @param int $parentId Id of the parent group.
     * @param int $childId  Id of the child group.
     *
     * @return bool True when the group is a direct child of the parent.
     */
    public function hasLink(int $parentId, int $childId): bool
    {
        return in_array($childId, $this->childrenOf($parentId), true);
    }

    /**
     * Tells whether linking a parent and a child would create a cycle, i.e.
     * the parent is the child itself or one of its descendants.
     *
     * @param int $parentId Id of the future parent group.
     * @param int $childId  Id of the future child group.
     *
     * @return bool True when the link is not allowed.
     */
    public function wouldCreateCycle(int $parentId, int $childId): bool
    {
        return $parentId === $childId || in_array($parentId, $this->descendantsOf($childId), true);
    }

    /**
     * Collects everything reachable from a start node.
     *
     * @param array<int, array<int, int>> $edges Adjacency list.
     * @param int                         $start Node to start from.
     *
     * @return array<int, int> Reachable nodes without the start node, each once.
     */
    private static function walk(array $edges, int $start): array
    {
        $seen  = [];
        $queue = $edges[$start] ?? [];
        while ($queue !== []) {
            $node = array_shift($queue);
            if ($node === $start || isset($seen[$node])) {
                continue;
            }
            $seen[$node] = $node;
            foreach ($edges[$node] ?? [] as $next) {
                $queue[] = $next;
            }
        }

        return array_values($seen);
    }
}

<?php

declare(strict_types=1);

namespace Clubhuesli\Tests\Fake;

use Clubhuesli\Dao\GroupDao;
use Clubhuesli\Model\Group;

/**
 * Group DAO keeping its data in memory, used by the tests.
 */
final class InMemoryGroupDao extends GroupDao
{
    /** @var array<int, Group> Stored groups, keyed by id. */
    private array $groups = [];

    /** @var array<int, array{0: int, 1: int}> Parent/child links. */
    private array $links = [];

    /** @var int Id handed out to the next group. */
    private int $nextId = 1;

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $groups = array_values($this->groups);
        usort($groups, static fn (Group $a, Group $b): int => strcmp((string) $a->getName(), (string) $b->getName()));

        return $groups;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Group
    {
        return $this->groups[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByName(string $name): ?Group
    {
        foreach ($this->groups as $group) {
            if ($group->getName() === $name) {
                return $group;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Group $group): int
    {
        $id = $group->getId() ?? $this->nextId++;
        $group->setId($id);
        $this->groups[$id] = $group;

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function findLinks(): array
    {
        return $this->links;
    }

    /**
     * {@inheritDoc}
     */
    public function addLink(int $parentId, int $childId): void
    {
        $this->links[] = [$parentId, $childId];
    }

    /**
     * {@inheritDoc}
     */
    public function removeLink(int $parentId, int $childId): bool
    {
        $before      = count($this->links);
        $this->links = array_values(array_filter(
            $this->links,
            static fn (array $link): bool => $link !== [$parentId, $childId]
        ));

        return count($this->links) < $before;
    }
}

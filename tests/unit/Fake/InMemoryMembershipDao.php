<?php

declare(strict_types=1);

namespace Clubhuesli\Tests\Fake;

use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Model\Membership;

/**
 * Membership DAO keeping its data in memory, used by the tests.
 */
final class InMemoryMembershipDao extends MembershipDao
{
    /** @var array<int, Membership> Stored memberships, keyed by id. */
    private array $memberships = [];

    /** @var int Id handed out to the next membership. */
    private int $nextId = 1;

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Membership
    {
        return $this->memberships[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByGroups(array $groupIds, ?int $seasonId = null): array
    {
        return array_values(array_filter(
            $this->memberships,
            static fn (Membership $m): bool => in_array($m->getGroupId(), $groupIds, true)
                && ($seasonId === null || $m->getSeasonId() === $seasonId)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function findByPerson(int $personId): array
    {
        return array_values(array_filter(
            $this->memberships,
            static fn (Membership $m): bool => $m->getPersonId() === $personId
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function findDuplicate(Membership $membership): ?Membership
    {
        foreach ($this->memberships as $other) {
            if ($other->getId() !== $membership->getId()
                && $other->getGroupId() === $membership->getGroupId()
                && $other->getPersonId() === $membership->getPersonId()
                && $other->getSeasonId() === $membership->getSeasonId()
                && $other->getRole() === $membership->getRole()) {
                return $other;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function existsForSeason(int $seasonId): bool
    {
        foreach ($this->memberships as $membership) {
            if ($membership->getSeasonId() === $seasonId) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Membership $membership): int
    {
        $id = $membership->getId() ?? $this->nextId++;
        $membership->setId($id);
        $this->memberships[$id] = $membership;

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $exists = isset($this->memberships[$id]);
        unset($this->memberships[$id]);

        return $exists;
    }
}

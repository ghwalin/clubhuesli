<?php

declare(strict_types=1);

namespace Clubhuesli\Tests\Fake;

use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Model\AbstractPerson;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\PersonType;
use Clubhuesli\Model\TeamMember;

/**
 * Person DAO keeping its data in memory, used by the API tests so they run
 * without a database.
 */
final class InMemoryPersonDao extends PersonDao
{
    /** @var array<int, AbstractPerson> Stored persons, keyed by their id. */
    private array $persons = [];

    /** @var array<int, array<int, int>> Guardian ids, keyed by the id of the child. */
    private array $guardianships = [];

    /** @var int Id handed out to the next person. */
    private int $nextId = 1;

    /**
     * Builds the DAO without a connection.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(?string $search = null, ?PersonType $personType = null): array
    {
        $persons = array_values(array_filter(
            $this->persons,
            static function (AbstractPerson $person) use ($search, $personType): bool {
                if ($personType !== null && $person->getPersonType() !== $personType) {
                    return false;
                }
                if ($search === null || trim($search) === '') {
                    return true;
                }
                $haystack = mb_strtolower($person->getDisplayName() . ' ' . ($person->getEmail() ?? ''));

                return str_contains($haystack, mb_strtolower(trim($search)));
            }
        ));

        usort(
            $persons,
            static fn (AbstractPerson $a, AbstractPerson $b): int => strcmp($a->getDisplayName(), $b->getDisplayName())
        );

        return $persons;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?AbstractPerson
    {
        $person = $this->persons[$id] ?? null;
        if ($person instanceof TeamMember && $person->isMinor()) {
            $person->setGuardians($this->findGuardiansOf($id));
        }

        return $person;
    }

    /**
     * {@inheritDoc}
     */
    public function findGuardiansOf(int $childId): array
    {
        $guardians = [];
        foreach ($this->guardianships[$childId] ?? [] as $guardianId) {
            $guardian = $this->persons[$guardianId] ?? null;
            if ($guardian instanceof NaturalPerson) {
                $guardians[] = $guardian;
            }
        }

        return $guardians;
    }

    /**
     * {@inheritDoc}
     */
    public function findChildrenOf(int $guardianId): array
    {
        $children = [];
        foreach ($this->guardianships as $childId => $guardianIds) {
            if (!in_array($guardianId, $guardianIds, true)) {
                continue;
            }
            $child = $this->persons[$childId] ?? null;
            if ($child !== null) {
                $children[] = ['id' => $childId, 'displayName' => $child->getDisplayName()];
            }
        }

        return $children;
    }

    /**
     * {@inheritDoc}
     */
    public function findPersonType(int $id): ?PersonType
    {
        return ($this->persons[$id] ?? null)?->getPersonType();
    }

    /**
     * {@inheritDoc}
     */
    public function save(AbstractPerson $person): int
    {
        $id = $person->getId();
        if ($id === null) {
            $id = $this->nextId++;
            $person->setId($id);
        }
        $this->persons[$id] = $person;

        if ($person instanceof TeamMember) {
            $this->guardianships[$id] = array_map('intval', array_keys($person->getGuardians()));
        } else {
            unset($this->guardianships[$id]);
        }

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        if (!isset($this->persons[$id])) {
            return false;
        }
        unset($this->persons[$id], $this->guardianships[$id]);
        foreach ($this->guardianships as $childId => $guardianIds) {
            $this->guardianships[$childId] = array_values(array_filter(
                $guardianIds,
                static fn (int $guardianId): bool => $guardianId !== $id
            ));
        }

        return true;
    }
}

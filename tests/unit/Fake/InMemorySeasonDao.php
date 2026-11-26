<?php

declare(strict_types=1);

namespace Clubhuesli\Tests\Fake;

use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Model\Season;

/**
 * Season DAO keeping its data in memory, used by the tests.
 */
final class InMemorySeasonDao extends SeasonDao
{
    /** @var array<int, Season> Stored seasons, keyed by id. */
    private array $seasons = [];

    /** @var int Id handed out to the next season. */
    private int $nextId = 1;

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $seasons = array_values($this->seasons);
        usort($seasons, static fn (Season $a, Season $b): int => $b->getStartDate() <=> $a->getStartDate());

        return $seasons;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id): ?Season
    {
        return $this->seasons[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByName(string $name): ?Season
    {
        foreach ($this->seasons as $season) {
            if ($season->getName() === $name) {
                return $season;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Season $season): int
    {
        $id = $season->getId() ?? $this->nextId++;
        $season->setId($id);
        $this->seasons[$id] = $season;

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $exists = isset($this->seasons[$id]);
        unset($this->seasons[$id]);

        return $exists;
    }
}

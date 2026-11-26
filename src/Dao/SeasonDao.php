<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Model\Season;
use PDO;

/**
 * Database access for seasons.
 */
class SeasonDao
{
    /** @var PDO|null The connection used by this DAO; resolved on first use. */
    private ?PDO $connection;

    /**
     * @param PDO|null $connection Connection to use; defaults to the shared one.
     */
    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the connection and opens the shared one on first use.
     *
     * @return PDO The connection.
     */
    private function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = Database::connection();
        }

        return $this->connection;
    }

    /**
     * Returns all seasons, the latest first.
     *
     * @return array<int, Season> The seasons.
     */
    public function findAll(): array
    {
        $rows = $this->connection()->query('SELECT * FROM season ORDER BY start_date DESC')->fetchAll();

        return array_map(fn (array $row): Season => $this->hydrate($row), $rows);
    }

    /**
     * Finds a season by id.
     *
     * @param int $id Id of the season.
     *
     * @return Season|null The season, or null when it does not exist.
     */
    public function find(int $id): ?Season
    {
        $statement = $this->connection()->prepare('SELECT * FROM season WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Finds a season by its unique name.
     *
     * @param string $name Name of the season.
     *
     * @return Season|null The season, or null when it does not exist.
     */
    public function findByName(string $name): ?Season
    {
        $statement = $this->connection()->prepare('SELECT * FROM season WHERE name = :name');
        $statement->execute(['name' => $name]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Inserts or updates a season.
     *
     * @param Season $season The season; receives its id when inserted.
     *
     * @return int The id of the season.
     */
    public function save(Season $season): int
    {
        $values = [
            'name'  => $season->getName(),
            'start' => $season->getStartDate()?->format('Y-m-d'),
            'end'   => $season->getEndDate()?->format('Y-m-d'),
        ];
        if ($season->getId() === null) {
            $this->connection()
                ->prepare('INSERT INTO season (name, start_date, end_date) VALUES (:name, :start, :end)')
                ->execute($values);
            $season->setId((int) $this->connection()->lastInsertId());
        } else {
            $this->connection()
                ->prepare('UPDATE season SET name = :name, start_date = :start, end_date = :end WHERE id = :id')
                ->execute($values + ['id' => $season->getId()]);
        }

        return (int) $season->getId();
    }

    /**
     * Deletes a season. Fails with a database error while memberships refer to it.
     *
     * @param int $id Id of the season.
     *
     * @return bool True when a season was deleted.
     */
    public function delete(int $id): bool
    {
        $statement = $this->connection()->prepare('DELETE FROM season WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Builds a season from a database row.
     *
     * @param array<string, mixed> $row The row.
     *
     * @return Season The season.
     */
    protected function hydrate(array $row): Season
    {
        $season = new Season();
        $season->setId((int) $row['id']);
        $season->setName((string) $row['name']);
        $season->setStartDate((string) $row['start_date']);
        $season->setEndDate((string) $row['end_date']);

        return $season;
    }
}

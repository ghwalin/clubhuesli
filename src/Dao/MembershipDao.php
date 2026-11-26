<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Model\Membership;
use PDO;

/**
 * Database access for group memberships.
 */
class MembershipDao
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
     * Finds a membership by id.
     *
     * @param int $id Id of the membership.
     *
     * @return Membership|null The membership, or null when it does not exist.
     */
    public function find(int $id): ?Membership
    {
        $statement = $this->connection()->prepare('SELECT * FROM membership WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Returns the direct memberships of several groups, optionally for one season.
     *
     * @param array<int, int> $groupIds Ids of the groups.
     * @param int|null        $seasonId Id of the season, or null for all seasons.
     *
     * @return array<int, Membership> The memberships.
     */
    public function findByGroups(array $groupIds, ?int $seasonId = null): array
    {
        if ($groupIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $sql          = "SELECT * FROM membership WHERE group_id IN ($placeholders)";
        $parameters   = array_values($groupIds);
        if ($seasonId !== null) {
            $sql         .= ' AND season_id = ?';
            $parameters[] = $seasonId;
        }
        $statement = $this->connection()->prepare($sql . ' ORDER BY id');
        $statement->execute($parameters);

        return array_map(fn (array $row): Membership => $this->hydrate($row), $statement->fetchAll());
    }

    /**
     * Returns the direct memberships of a person in all seasons.
     *
     * @param int $personId Id of the person.
     *
     * @return array<int, Membership> The memberships.
     */
    public function findByPerson(int $personId): array
    {
        $statement = $this->connection()->prepare('SELECT * FROM membership WHERE person_id = :person ORDER BY id');
        $statement->execute(['person' => $personId]);

        return array_map(fn (array $row): Membership => $this->hydrate($row), $statement->fetchAll());
    }

    /**
     * Finds the membership with the same group, person, season and role.
     *
     * @param Membership $membership The membership to compare.
     *
     * @return Membership|null The other membership, or null when there is none.
     */
    public function findDuplicate(Membership $membership): ?Membership
    {
        $statement = $this->connection()->prepare(
            'SELECT * FROM membership WHERE group_id = :group AND person_id = :person'
            . ' AND season_id = :season AND role = :role AND id <> :id'
        );
        $statement->execute([
            'group'  => $membership->getGroupId(),
            'person' => $membership->getPersonId(),
            'season' => $membership->getSeasonId(),
            'role'   => $membership->getRole()->value,
            'id'     => $membership->getId() ?? 0,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /**
     * Tells whether memberships exist for a season.
     *
     * @param int $seasonId Id of the season.
     *
     * @return bool True when at least one membership refers to the season.
     */
    public function existsForSeason(int $seasonId): bool
    {
        $statement = $this->connection()->prepare('SELECT 1 FROM membership WHERE season_id = :season LIMIT 1');
        $statement->execute(['season' => $seasonId]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Inserts or updates a membership.
     *
     * @param Membership $membership The membership; receives its id when inserted.
     *
     * @return int The id of the membership.
     */
    public function save(Membership $membership): int
    {
        $values = [
            'group'    => $membership->getGroupId(),
            'person'   => $membership->getPersonId(),
            'season'   => $membership->getSeasonId(),
            'role'     => $membership->getRole()->value,
            'function' => $membership->getFunction(),
            'shirt'    => $membership->getShirtNumber(),
            'exit'     => $membership->getExitDate()?->format('Y-m-d'),
        ];
        if ($membership->getId() === null) {
            $this->connection()->prepare(
                'INSERT INTO membership (group_id, person_id, season_id, role, `function`, shirt_number, exit_date)'
                . ' VALUES (:group, :person, :season, :role, :function, :shirt, :exit)'
            )->execute($values);
            $membership->setId((int) $this->connection()->lastInsertId());
        } else {
            $this->connection()->prepare(
                'UPDATE membership SET group_id = :group, person_id = :person, season_id = :season, role = :role,'
                . ' `function` = :function, shirt_number = :shirt, exit_date = :exit WHERE id = :id'
            )->execute($values + ['id' => $membership->getId()]);
        }

        return (int) $membership->getId();
    }

    /**
     * Deletes a membership.
     *
     * @param int $id Id of the membership.
     *
     * @return bool True when a membership was deleted.
     */
    public function delete(int $id): bool
    {
        $statement = $this->connection()->prepare('DELETE FROM membership WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Builds a membership from a database row.
     *
     * @param array<string, mixed> $row The row.
     *
     * @return Membership The membership.
     */
    protected function hydrate(array $row): Membership
    {
        $membership = new Membership();
        $membership->setId((int) $row['id']);
        $membership->setGroupId((int) $row['group_id']);
        $membership->setPersonId((int) $row['person_id']);
        $membership->setSeasonId((int) $row['season_id']);
        $membership->setRole((string) $row['role']);
        $membership->setFunction($row['function'] === null ? null : (string) $row['function']);
        $membership->setShirtNumber($row['shirt_number'] === null ? null : (int) $row['shirt_number']);
        $membership->setExitDate($row['exit_date'] === null ? null : (string) $row['exit_date']);

        return $membership;
    }
}
